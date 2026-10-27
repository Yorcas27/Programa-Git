<?php
$titulo = "NUEVA ORDEN";
$css = "OrdenesProduccion.css";

require_once("../../includes/conexion.php");
include("../../includes/header.php");

$error = "";
$fecha = date("Y-m-d");
$fecha_entrega = "";
$id_cliente = "";
$tipo_cliente = "registrado";
$nombre_cliente = "";
$documento_cliente = "";
$telefono_cliente = "";
$correo_cliente = "";
$id_producto = "";
$cantidad = "";
$responsable = "";
$estado = "Pendiente";
$observaciones = "";

/* CÓDIGO AUTOMÁTICO */

$resultado = $conexion->query(
    "SELECT MAX(id_orden) AS ultimo FROM ordenes_produccion"
);

$fila = $resultado->fetch_assoc();
$ultimo = (int)($fila["ultimo"] ?? 0);
$codigo = "OP-" . str_pad($ultimo + 1, 4, "0", STR_PAD_LEFT);

/* GUARDAR ORDEN */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $tipo_cliente = $_POST["tipo_cliente"] ?? "registrado";
    $id_cliente = (int)($_POST["id_cliente"] ?? 0);

    $nombre_cliente = trim($_POST["nombre_cliente"] ?? "");
    $documento_cliente = trim($_POST["documento_cliente"] ?? "");
    $telefono_cliente = trim($_POST["telefono_cliente"] ?? "");
    $correo_cliente = trim($_POST["correo_cliente"] ?? "");

    $fecha = $_POST["fecha"] ?? date("Y-m-d");
    $fecha_entrega = $_POST["fecha_entrega"] ?? "";

    $id_producto = (int)($_POST["id_producto"] ?? 0);
    $cantidad = (int)($_POST["cantidad"] ?? 0);
    $responsable = trim($_POST["responsable"] ?? "");

    $estado = $_POST["estado"] ?? "Pendiente";
    $observaciones = trim($_POST["observaciones"] ?? "");

    if (!in_array($tipo_cliente, ["registrado", "ocasional"], true)) {
        $error = "El tipo de cliente seleccionado no es válido.";

    } elseif ($tipo_cliente === "registrado" && $id_cliente <= 0) {
        $error = "Debe seleccionar un cliente.";

    } elseif ($tipo_cliente === "ocasional" && $nombre_cliente === "") {
        $error = "Debe ingresar el nombre del cliente ocasional.";

    } elseif ($id_producto <= 0) {
        $error = "Debe seleccionar un producto.";

    } elseif ($cantidad <= 0) {
        $error = "La cantidad debe ser mayor que cero.";

    } elseif ($responsable === "") {
        $error = "Debe ingresar el responsable.";

    } elseif (!in_array($estado, ["Pendiente", "En Producción", "Finalizada"], true)) {
        $error = "El estado seleccionado no es válido.";

    } else {

        $conexion->begin_transaction();

        try {

            /* VALIDAR PRODUCTO ACTIVO */

            $stmtProducto = $conexion->prepare(
                "SELECT id_producto
                 FROM productos
                 WHERE id_producto = ?
                 AND estado = 'activo'"
            );

            $stmtProducto->bind_param("i", $id_producto);
            $stmtProducto->execute();

            $productoValido = $stmtProducto->get_result()->fetch_assoc();
            $stmtProducto->close();

            if (!$productoValido) {
                throw new Exception(
                    "El producto seleccionado no existe o está inactivo."
                );
            }

            /* CLIENTE REGISTRADO */

            if ($tipo_cliente === "registrado") {

                $stmtCliente = $conexion->prepare(
                    "SELECT id_cliente
                     FROM clientes
                     WHERE id_cliente = ?
                     AND estado = 'Activo'"
                );

                $stmtCliente->bind_param("i", $id_cliente);
                $stmtCliente->execute();

                $clienteValido = $stmtCliente->get_result()->fetch_assoc();
                $stmtCliente->close();

                if (!$clienteValido) {
                    throw new Exception(
                        "El cliente seleccionado no existe o está inactivo."
                    );
                }
            }

            /* CLIENTE OCASIONAL */

            if ($tipo_cliente === "ocasional") {

                $resultadoCodigo = $conexion->query(
                    "SELECT MAX(id_cliente) AS ultimo FROM clientes"
                );

                $filaCodigo = $resultadoCodigo->fetch_assoc();
                $ultimoCliente = (int)($filaCodigo["ultimo"] ?? 0);

                $codigo_cliente = "CLI-" . str_pad(
                    $ultimoCliente + 1,
                    4,
                    "0",
                    STR_PAD_LEFT
                );

                $documento = $documento_cliente !== ""
                    ? $documento_cliente
                    : null;

                $telefono = $telefono_cliente !== ""
                    ? $telefono_cliente
                    : null;

                $correo = $correo_cliente !== ""
                    ? $correo_cliente
                    : null;

                $stmtCliente = $conexion->prepare(
                    "INSERT INTO clientes
                    (codigo, nombre, documento, telefono, correo, estado)
                    VALUES (?, ?, ?, ?, ?, 'Activo')"
                );

                if (!$stmtCliente) {
                    throw new Exception(
                        "No fue posible preparar el registro del cliente."
                    );
                }

                $stmtCliente->bind_param(
                    "sssss",
                    $codigo_cliente,
                    $nombre_cliente,
                    $documento,
                    $telefono,
                    $correo
                );

                if (!$stmtCliente->execute()) {
                    throw new Exception(
                        "No fue posible registrar el cliente: "
                        . $stmtCliente->error
                    );
                }

                $id_cliente = $conexion->insert_id;
                $stmtCliente->close();
            }

            /* GUARDAR ORDEN */

            $stmt = $conexion->prepare(
                "INSERT INTO ordenes_produccion
                (
                    codigo,
                    fecha,
                    id_cliente,
                    id_producto,
                    cantidad,
                    responsable,
                    fecha_entrega,
                    estado,
                    observaciones
                )
                VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, ''), ?, ?)"
            );

            if (!$stmt) {
                throw new Exception(
                    "No fue posible preparar la orden: "
                    . $conexion->error
                );
            }

            $stmt->bind_param(
                "ssiiissss",
                $codigo,
                $fecha,
                $id_cliente,
                $id_producto,
                $cantidad,
                $responsable,
                $fecha_entrega,
                $estado,
                $observaciones
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    "No fue posible guardar la orden: "
                    . $stmt->error
                );
            }

            $stmt->close();
            $conexion->commit();

            header(
                "Location: OrdenesProduccion.php?mensaje="
                . urlencode("Orden creada correctamente.")
            );

            exit;

        } catch (Exception $e) {

            $conexion->rollback();

            $error = "No fue posible guardar la orden: "
                . $e->getMessage();
        }
    }
}

/* CLIENTES ACTIVOS */

$clientes = $conexion->query(
    "SELECT id_cliente, codigo, nombre
     FROM clientes
     WHERE estado = 'Activo'
     ORDER BY nombre"
);

/* PRODUCTOS ACTIVOS CON PRESENTACIÓN */

$productos = $conexion->query(
    "SELECT id_producto, nombre, presentacion
     FROM productos
     WHERE estado = 'activo'
     ORDER BY nombre, presentacion"
);

/* ACTUALIZAR CÓDIGO DESPUÉS DE UN ERROR */

if ($_SERVER["REQUEST_METHOD"] === "POST" && $error !== "") {

    $resultado = $conexion->query(
        "SELECT MAX(id_orden) AS ultimo FROM ordenes_produccion"
    );

    $fila = $resultado->fetch_assoc();

    $codigo = "OP-" . str_pad(
        (int)($fila["ultimo"] ?? 0) + 1,
        4,
        "0",
        STR_PAD_LEFT
    );
}
?>

<main class="contenido">

    <section class="barra-superior">

        <a href="OrdenesProduccion.php" class="btn btn-principal">
            ← Volver a Órdenes de Producción
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </section>

    <header class="encabezado">

        <h1>Nueva Orden de Producción</h1>

        <p>
            Registra una nueva orden y programa el proceso de producción.
        </p>

    </header>

    <?php if ($error !== ""): ?>

        <div class="mensaje-error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <section class="panel">

        <form method="POST" action="NuevaOrden.php" id="formOrden">

            <div class="grupo-formulario">

                <div class="campo">

                    <label for="codigo">Código</label>

                    <input
                        type="text"
                        id="codigo"
                        value="<?= htmlspecialchars($codigo) ?>"
                        readonly
                    >

                </div>

                <div class="campo">

                    <label for="fecha">Fecha</label>

                    <input
                        type="date"
                        id="fecha"
                        name="fecha"
                        value="<?= htmlspecialchars($fecha) ?>"
                        required
                    >

                </div>

                <div class="campo campo-completo">

                    <label>Tipo de Cliente</label>

                    <div class="tipo-cliente">

                        <label>
                            <input
                                type="radio"
                                name="tipo_cliente"
                                value="registrado"
                                id="clienteRegistrado"
                                <?= $tipo_cliente === "registrado" ? "checked" : "" ?>
                            >
                            Cliente registrado
                        </label>

                        <label>
                            <input
                                type="radio"
                                name="tipo_cliente"
                                value="ocasional"
                                id="clienteOcasional"
                                <?= $tipo_cliente === "ocasional" ? "checked" : "" ?>
                            >
                            Cliente ocasional
                        </label>

                    </div>

                </div>

                <div class="campo" id="seccionRegistrado">

                    <label for="id_cliente">Cliente</label>

                    <select id="id_cliente" name="id_cliente">

                        <option value="">Seleccione un cliente</option>

                        <?php while ($cliente = $clientes->fetch_assoc()): ?>

                            <option
                                value="<?= $cliente["id_cliente"] ?>"
                                <?= (string)$id_cliente === (string)$cliente["id_cliente"] ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars(
                                    $cliente["codigo"] . " - " . $cliente["nombre"]
                                ) ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>

                <div id="seccionOcasional" class="campo-completo" style="display:none;">

                    <div class="grupo-formulario">

                        <div class="campo">

                            <label for="nombre_cliente">Nombre</label>

                            <input
                                type="text"
                                id="nombre_cliente"
                                name="nombre_cliente"
                                value="<?= htmlspecialchars($nombre_cliente) ?>"
                                placeholder="Nombre del cliente"
                            >

                        </div>

                        <div class="campo">

                            <label for="documento_cliente">Documento</label>

                            <input
                                type="text"
                                id="documento_cliente"
                                name="documento_cliente"
                                value="<?= htmlspecialchars($documento_cliente) ?>"
                                placeholder="Documento"
                            >

                        </div>

                        <div class="campo">

                            <label for="telefono_cliente">Teléfono</label>

                            <input
                                type="text"
                                id="telefono_cliente"
                                name="telefono_cliente"
                                value="<?= htmlspecialchars($telefono_cliente) ?>"
                                placeholder="Teléfono"
                            >

                        </div>

                        <div class="campo">

                            <label for="correo_cliente">Correo</label>

                            <input
                                type="email"
                                id="correo_cliente"
                                name="correo_cliente"
                                value="<?= htmlspecialchars($correo_cliente) ?>"
                                placeholder="Correo electrónico"
                            >

                        </div>

                    </div>

                </div>

                <div class="campo">

                    <label for="id_producto">Producto</label>

                    <select id="id_producto" name="id_producto" required>

                        <option value="">Seleccione un producto</option>

                        <?php while ($producto = $productos->fetch_assoc()): ?>

                            <option
                                value="<?= $producto["id_producto"] ?>"
                                <?= (string)$id_producto === (string)$producto["id_producto"] ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars(
                                    $producto["nombre"] . " — " . $producto["presentacion"]
                                ) ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>

                <div class="campo">

                    <label for="cantidad">Cantidad</label>

                    <input
                        type="number"
                        id="cantidad"
                        name="cantidad"
                        min="1"
                        value="<?= htmlspecialchars((string)$cantidad) ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="responsable">Responsable</label>

                    <input
                        type="text"
                        id="responsable"
                        name="responsable"
                        value="<?= htmlspecialchars($responsable) ?>"
                        placeholder="Nombre del responsable"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="estado">Estado</label>

                    <select id="estado" name="estado">

                        <option value="Pendiente"
                            <?= $estado === "Pendiente" ? "selected" : "" ?>>
                            Pendiente
                        </option>

                        <option value="En Producción"
                            <?= $estado === "En Producción" ? "selected" : "" ?>>
                            En Producción
                        </option>

                        <option value="Finalizada"
                            <?= $estado === "Finalizada" ? "selected" : "" ?>>
                            Finalizada
                        </option>

                    </select>

                </div>

                <div class="campo">

                    <label for="fecha_entrega">Fecha de Entrega</label>

                    <input
                        type="date"
                        id="fecha_entrega"
                        name="fecha_entrega"
                        value="<?= htmlspecialchars($fecha_entrega) ?>"
                    >

                </div>

                <div class="campo campo-completo">

                    <label for="observaciones">Observaciones</label>

                    <textarea
                        id="observaciones"
                        name="observaciones"
                        rows="4"
                        placeholder="Ingrese observaciones de la orden..."
                    ><?= htmlspecialchars($observaciones) ?></textarea>

                </div>

            </div>

            <div class="botones-formulario">

                <button type="submit" class="btn btn-guardar">
                    Guardar Orden
                </button>

                <a href="OrdenesProduccion.php" class="btn btn-cancelar">
                    Cancelar
                </a>

            </div>

        </form>

    </section>

</main>

<script>
const registrado = document.getElementById("clienteRegistrado");
const ocasional = document.getElementById("clienteOcasional");
const seccionRegistrado = document.getElementById("seccionRegistrado");
const seccionOcasional = document.getElementById("seccionOcasional");
const selectCliente = document.getElementById("id_cliente");
const nombreCliente = document.getElementById("nombre_cliente");

function cambiarTipoCliente() {

    if (ocasional.checked) {

        seccionRegistrado.style.display = "none";
        seccionOcasional.style.display = "block";

        selectCliente.required = false;
        nombreCliente.required = true;

    } else {

        seccionRegistrado.style.display = "block";
        seccionOcasional.style.display = "none";

        selectCliente.required = true;
        nombreCliente.required = false;
    }
}

registrado.addEventListener("change", cambiarTipoCliente);
ocasional.addEventListener("change", cambiarTipoCliente);

cambiarTipoCliente();
</script>

<?php include("../../includes/footer.php"); ?>