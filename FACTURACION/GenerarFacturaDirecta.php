<?php
$titulo = "FACTURAR VENTA DIRECTA";
$css = "Facturacion.css";

require_once("../includes/config.php");
require_once("../includes/conexion.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = "";
$productos = [];
$subtotal = 0;
$iva = 0;
$total = 0;

function obtenerCarritoDirecto(): array {
    $ids = $_POST["productos"] ?? [];
    $cantidades = $_POST["cantidades"] ?? [];
    $carrito = [];

    if (!is_array($ids) || !is_array($cantidades) || count($ids) !== count($cantidades)) {
        return [];
    }

    foreach ($ids as $i => $id) {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        $cantidad = filter_var($cantidades[$i], FILTER_VALIDATE_INT);

        if (!$id || !$cantidad || $cantidad < 1 || $cantidad > 999) {
            continue;
        }

        $carrito[$id] = ($carrito[$id] ?? 0) + $cantidad;
    }

    return $carrito;
}

$carrito = obtenerCarritoDirecto();

if (empty($carrito) && !isset($_POST["confirmar_factura"])) {
    header("Location: Catalogos.php");
    exit;
}

try {
    if (!empty($carrito)) {
        $ids = array_keys($carrito);
        $marcadores = implode(",", array_fill(0, count($ids), "?"));
        $tipos = str_repeat("i", count($ids));

        $sql = "SELECT id_producto, nombre, presentacion, precio
                FROM productos
                WHERE id_producto IN ($marcadores)
                  AND LOWER(estado) = 'activo'";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($tipos, ...$ids);
        $stmt->execute();
        $resultado = $stmt->get_result();

        while ($fila = $resultado->fetch_assoc()) {
            $id = (int)$fila["id_producto"];
            $cantidad = $carrito[$id] ?? 0;

            if ($cantidad < 1) {
                continue;
            }

            $precio = (float)$fila["precio"];
            $linea = round($precio * $cantidad, 2);

            $productos[] = [
                "id" => $id,
                "nombre" => $fila["nombre"],
                "presentacion" => $fila["presentacion"] ?: "Única",
                "precio" => $precio,
                "cantidad" => $cantidad,
                "subtotal" => $linea
            ];

            $subtotal += $linea;
        }

        $stmt->close();

        if (count($productos) !== count($carrito)) {
            throw new RuntimeException("Uno o más productos ya no están activos o no existen.");
        }
    }
} catch (Throwable $e) {
    $error = $e instanceof RuntimeException
        ? $e->getMessage()
        : "No fue posible consultar los productos seleccionados.";
}

$subtotal = round($subtotal, 2);
$iva = round($subtotal * 0.19, 2);
$total = round($subtotal + $iva, 2);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["confirmar_factura"]) && $error === "") {
    $tipoCliente = $_POST["tipo_cliente"] ?? "ocasional";
    $metodo = $_POST["metodo_pago"] ?? "";
    $estado = $_POST["estado"] ?? "Pendiente";
    $idCliente = null;
    $nombreCliente = trim($_POST["nombre_cliente"] ?? "");
    $documentoCliente = trim($_POST["documento_cliente"] ?? "");
    $telefonoCliente = trim($_POST["telefono_cliente"] ?? "");
    $correoCliente = trim($_POST["correo_cliente"] ?? "");

    if (empty($productos)) {
        $error = "No hay productos válidos para facturar.";
    } elseif (!in_array($metodo, ["Efectivo", "Transferencia", "Tarjeta"], true)) {
        $error = "Selecciona un medio de pago válido.";
    } elseif (!in_array($estado, ["Pendiente", "Pagada"], true)) {
        $error = "Selecciona un estado válido.";
    } elseif ($tipoCliente === "registrado") {
        $idCliente = filter_var($_POST["id_cliente"] ?? "", FILTER_VALIDATE_INT);

        if (!$idCliente) {
            $error = "Selecciona un cliente registrado.";
        } else {
            $stmt = $conexion->prepare(
                "SELECT nombre, documento, telefono, correo
                 FROM clientes
                 WHERE id_cliente = ? AND estado = 'Activo'
                 LIMIT 1"
            );
            $stmt->bind_param("i", $idCliente);
            $stmt->execute();
            $cliente = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$cliente) {
                $error = "El cliente seleccionado no existe o está inactivo.";
            } else {
                $nombreCliente = $cliente["nombre"] ?? "";
                $documentoCliente = $cliente["documento"] ?? "";
                $telefonoCliente = $cliente["telefono"] ?? "";
                $correoCliente = $cliente["correo"] ?? "";
            }
        }
    } elseif ($nombreCliente === "") {
        $error = "Ingresa el nombre del cliente ocasional.";
    }

    if ($error === "") {
        try {
            $conexion->begin_transaction();

            $consulta = $conexion->query(
                "SELECT numero_factura
                 FROM facturas
                 ORDER BY id_factura DESC
                 LIMIT 1
                 FOR UPDATE"
            );

            $ultima = $consulta->fetch_assoc();
            $numeroAnterior = (int)preg_replace(
                "/\D/",
                "",
                $ultima["numero_factura"] ?? "FAC000"
            );
            $numeroFactura = "FAC" . str_pad((string)($numeroAnterior + 1), 3, "0", STR_PAD_LEFT);

            $sqlFactura = "INSERT INTO facturas
                (numero_factura, id_pedido, id_cliente, fecha, metodo_pago, estado,
                 total, nombre_cliente, documento_cliente, telefono_cliente, correo_cliente)
                VALUES (?, NULL, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conexion->prepare($sqlFactura);
            $stmt->bind_param(
                "sissdssss",
                $numeroFactura,
                $idCliente,
                $metodo,
                $estado,
                $total,
                $nombreCliente,
                $documentoCliente,
                $telefonoCliente,
                $correoCliente
            );
            $stmt->execute();
            $idFactura = $conexion->insert_id;
            $stmt->close();

            $stmtDetalle = $conexion->prepare(
                "INSERT INTO detalle_factura
                 (id_factura, id_producto, cantidad, valor, subtotal)
                 VALUES (?, ?, ?, ?, ?)"
            );

            foreach ($productos as $producto) {
                $idProducto = $producto["id"];
                $cantidad = $producto["cantidad"];
                $precio = $producto["precio"];
                $linea = $producto["subtotal"];

                $stmtDetalle->bind_param(
                    "iiidd",
                    $idFactura,
                    $idProducto,
                    $cantidad,
                    $precio,
                    $linea
                );
                $stmtDetalle->execute();
            }

            $stmtDetalle->close();
            $conexion->commit();

            header("Location: VerFactura.php?id_factura=" . $idFactura);
            exit;
        } catch (Throwable $e) {
            $conexion->rollback();
            $error = "No fue posible guardar la factura. Verifica los datos y vuelve a intentarlo.";
        }
    }
}

$clientes = [];

try {
    $resultadoClientes = $conexion->query(
        "SELECT id_cliente, nombre, documento
         FROM clientes
         WHERE estado = 'Activo'
         ORDER BY nombre"
    );

    while ($fila = $resultadoClientes->fetch_assoc()) {
        $clientes[] = $fila;
    }
} catch (Throwable $e) {
    $error = $error ?: "No fue posible cargar los clientes.";
}

include("../includes/header.php");
?>

<main class="contenido">
    <section class="barra-superior">
        <a href="../CATALOGOS/Catalogos.php" class=" btn-volver">← Volver al Catálogo</a>
    </section>

    <section class="encabezado">
        <h1><?= htmlspecialchars($titulo) ?></h1>
        <p>Registra los datos del cliente y confirma los productos de la venta directa.</p>
    </section>

    <?php if ($error !== ""): ?>
        <div class="sin-resultados"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <section class="panel">
        <h2>Productos de la venta</h2>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Presentación</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?= htmlspecialchars($producto["nombre"]) ?></td>
                            <td><?= htmlspecialchars($producto["presentacion"]) ?></td>
                            <td><?= $producto["cantidad"] ?></td>
                            <td>$<?= number_format($producto["precio"], 0, ",", ".") ?></td>
                            <td>$<?= number_format($producto["subtotal"], 0, ",", ".") ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($productos)): ?>
                        <tr><td colspan="5">No hay productos para mostrar.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align:right;margin-top:20px;">
            <p>Subtotal: <strong>$<?= number_format($subtotal, 0, ",", ".") ?></strong></p>
            <p>IVA (19 %): <strong>$<?= number_format($iva, 0, ",", ".") ?></strong></p>
            <h2>Total: $<?= number_format($total, 0, ",", ".") ?></h2>
        </div>
    </section>

    
<section class="panel">
    <h2>Datos de facturación</h2>

    <form method="POST" class="formulario-directa">
        <?php foreach ($productos as $producto): ?>
            <input type="hidden" name="productos[]" value="<?= $producto["id"] ?>">
            <input type="hidden" name="cantidades[]" value="<?= $producto["cantidad"] ?>">
        <?php endforeach; ?>

        <div class="campo-directa campo-tipo-cliente">
            <label for="tipo_cliente">Tipo de cliente</label>
            <select name="tipo_cliente" id="tipo_cliente" required onchange="cambiarTipoCliente()">
                <option value="ocasional">Cliente ocasional</option>
                <option value="registrado">Cliente registrado</option>
            </select>
        </div>

        <div id="datosOcasional" class="datos-cliente-directa">
            <div class="campo-directa">
                <label for="nombre_cliente">Nombre del cliente *</label>
                <input type="text" name="nombre_cliente" id="nombre_cliente" maxlength="150">
            </div>

            <div class="campo-directa">
                <label for="documento_cliente">Documento</label>
                <input type="text" name="documento_cliente" id="documento_cliente" maxlength="50">
            </div>

            <div class="campo-directa">
                <label for="telefono_cliente">Teléfono</label>
                <input type="text" name="telefono_cliente" id="telefono_cliente" maxlength="30">
            </div>

            <div class="campo-directa">
                <label for="correo_cliente">Correo</label>
                <input type="email" name="correo_cliente" id="correo_cliente" maxlength="150">
            </div>
        </div>

        <div id="datosRegistrado" class="datos-cliente-directa" style="display:none;">
            <div class="campo-directa">
                <label for="id_cliente">Seleccionar cliente *</label>
                <select name="id_cliente" id="id_cliente">
                    <option value="">Selecciona un cliente</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= (int)$cliente["id_cliente"] ?>">
                            <?= htmlspecialchars($cliente["nombre"] . " - " . $cliente["documento"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="opciones-factura-directa">
            <div class="campo-directa">
                <label for="metodo_pago">Medio de pago</label>
                <select name="metodo_pago" id="metodo_pago" required>
                    <option value="Efectivo">Efectivo</option>
                    <option value="Transferencia">Transferencia</option>
                    <option value="Tarjeta">Tarjeta</option>
                </select>
            </div>

            <div class="campo-directa">
                <label for="estado">Estado de la factura</label>
                <select name="estado" id="estado" required>
                    <option value="Pendiente">Pendiente</option>
                    <option value="Pagada">Pagada</option>
                </select>
            </div>
        </div>

        <div class="acciones-directa">
            <button type="submit" name="confirmar_factura" value="1"
                    <?= empty($productos) ? "disabled" : "" ?>>
                Generar factura
            </button>

            <a href="../CATALOGOS/Catalogos.php" class="btn-cancelar-directa">
                Cancelar
            </a>
        </div>
    </form>
</section>
</main>

<script>
function cambiarTipoCliente() {
    const registrado = document.getElementById("tipo_cliente").value === "registrado";
    document.getElementById("datosRegistrado").style.display = registrado ? "block" : "none";
    document.getElementById("datosOcasional").style.display = registrado ? "none" : "block";

    document.getElementById("id_cliente").required = registrado;
    document.getElementById("nombre_cliente").required = !registrado;
}

cambiarTipoCliente();
</script>

<?php include("../includes/footer.php"); ?>