<?php
$titulo = "NUEVO PEDIDO";
$css = "Pedidos.css";

require_once("../includes/conexion.php");

$error = "";
$fecha = date("Y-m-d");
$fecha_entrega = "";
$id_cliente = "";
$tipo_cliente = "registrado";
$nombre_cliente = "";
$documento_cliente = "";
$telefono_cliente = "";
$correo_cliente = "";
$estado = "Pendiente";
$observaciones = "";

/* CÓDIGO DEL PEDIDO */
$resultado = $conexion->query("SELECT MAX(id_pedido) AS ultimo FROM pedidos");
$fila = $resultado->fetch_assoc();
$codigo_pedido = "PED-" . str_pad((int)($fila["ultimo"] ?? 0) + 1, 4, "0", STR_PAD_LEFT);

/* CLIENTES Y PRODUCTOS ACTIVOS */
$clientes = $conexion->query(
    "SELECT id_cliente, codigo, nombre
     FROM clientes
     WHERE estado = 'Activo'
     ORDER BY nombre"
);

$productos = $conexion->query(
    "SELECT id_producto, nombre, presentacion, precio
     FROM productos
     WHERE LOWER(estado) = 'activo'
     ORDER BY nombre, presentacion"
);

/* GUARDAR PEDIDO */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tipo_cliente = $_POST["tipo_cliente"] ?? "registrado";
    $id_cliente = (int)($_POST["id_cliente"] ?? 0);
    $nombre_cliente = trim($_POST["nombre_cliente"] ?? "");
    $documento_cliente = trim($_POST["documento_cliente"] ?? "");
    $telefono_cliente = trim($_POST["telefono_cliente"] ?? "");
    $correo_cliente = trim($_POST["correo_cliente"] ?? "");
    $fecha = $_POST["fecha"] ?? date("Y-m-d");
    $fecha_entrega = $_POST["fecha_entrega"] ?? "";
    $estado = $_POST["estado"] ?? "Pendiente";
    $observaciones = trim($_POST["observaciones"] ?? "");
    $productos_pedido = $_POST["producto"] ?? [];
    $cantidades = $_POST["cantidad"] ?? [];

    if (!in_array($tipo_cliente, ["registrado", "ocasional"], true)) {
        $error = "El tipo de cliente seleccionado no es válido.";
    } elseif ($tipo_cliente === "registrado" && $id_cliente <= 0) {
        $error = "Debe seleccionar un cliente.";
    } elseif ($tipo_cliente === "ocasional" && $nombre_cliente === "") {
        $error = "Debe ingresar el nombre del cliente ocasional.";
    } elseif (!is_array($productos_pedido) || empty($productos_pedido)) {
        $error = "Debe agregar al menos un producto.";
    } elseif (!in_array($estado, ["Pendiente", "En Producción", "Entregado"], true)) {
        $error = "El estado seleccionado no es válido.";
    } else {
        $subtotal = 0;
        $detalles = [];

        $stmtProducto = $conexion->prepare(
            "SELECT precio
             FROM productos
             WHERE id_producto = ?
             AND LOWER(estado) = 'activo'"
        );

        if (!$stmtProducto) {
            $error = "No fue posible validar los productos.";
        } else {
            foreach ($productos_pedido as $i => $id_producto) {
                $id_producto = (int)$id_producto;
                $cantidad = (int)($cantidades[$i] ?? 0);

                if ($id_producto <= 0 || $cantidad <= 0) {
                    $error = "Verifique los productos y sus cantidades.";
                    break;
                }

                $stmtProducto->bind_param("i", $id_producto);

                if (!$stmtProducto->execute()) {
                    $error = "No fue posible consultar el precio del producto.";
                    break;
                }

                $productoBD = $stmtProducto->get_result()->fetch_assoc();

                if (!$productoBD) {
                    $error = "Uno de los productos no existe o está inactivo.";
                    break;
                }

                $precio = (float)$productoBD["precio"];
                $subtotalProducto = $cantidad * $precio;
                $subtotal += $subtotalProducto;

                $detalles[] = [
                    "id_producto" => $id_producto,
                    "cantidad" => $cantidad,
                    "precio" => $precio,
                    "subtotal" => $subtotalProducto
                ];
            }

            $stmtProducto->close();
        }

        if ($error === "" && empty($detalles)) {
            $error = "Debe agregar productos válidos.";
        }

        /* VALIDAR CLIENTE REGISTRADO */
        if ($error === "" && $tipo_cliente === "registrado") {
            $stmtCliente = $conexion->prepare(
                "SELECT id_cliente
                 FROM clientes
                 WHERE id_cliente = ?
                 AND estado = 'Activo'"
            );

            if (!$stmtCliente) {
                $error = "No fue posible validar el cliente.";
            } else {
                $stmtCliente->bind_param("i", $id_cliente);
                $stmtCliente->execute();
                $clienteValido = $stmtCliente->get_result()->fetch_assoc();
                $stmtCliente->close();

                if (!$clienteValido) {
                    $error = "El cliente seleccionado no existe o está inactivo.";
                } else {
                    $nombre_cliente = null;
                    $documento_cliente = null;
                    $telefono_cliente = null;
                    $correo_cliente = null;
                }
            }
        }

        if ($error === "") {
            $iva = $subtotal * 0.19;
            $total = $subtotal + $iva;
            $idClientePedido = $tipo_cliente === "ocasional" ? null : $id_cliente;

            $conexion->begin_transaction();

            try {
                /* REGISTRAR PEDIDO */
                $sql = "INSERT INTO pedidos
                    (codigo_pedido, id_cliente, nombre_cliente, documento_cliente,
                     telefono_cliente, correo_cliente, fecha, fecha_entrega,
                     subtotal, iva, total, estado, observaciones)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), ?, ?, ?, ?, ?)";

                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    throw new Exception("Error al preparar el pedido: " . $conexion->error);
                }

                $stmt->bind_param(
                    "sissssssdddss",
                    $codigo_pedido,
                    $idClientePedido,
                    $nombre_cliente,
                    $documento_cliente,
                    $telefono_cliente,
                    $correo_cliente,
                    $fecha,
                    $fecha_entrega,
                    $subtotal,
                    $iva,
                    $total,
                    $estado,
                    $observaciones
                );

                if (!$stmt->execute()) {
                    throw new Exception("Error al guardar el pedido: " . $stmt->error);
                }

                $id_pedido = $conexion->insert_id;
                $stmt->close();

                /* REGISTRAR DETALLES DEL PEDIDO */
                $stmtDetalle = $conexion->prepare(
                    "INSERT INTO detalle_pedido
                     (id_pedido, id_producto, cantidad, precio, subtotal)
                     VALUES (?, ?, ?, ?, ?)"
                );

                if (!$stmtDetalle) {
                    throw new Exception("Error al preparar los detalles: " . $conexion->error);
                }

                foreach ($detalles as $detalle) {
                    $stmtDetalle->bind_param(
                        "iiidd",
                        $id_pedido,
                        $detalle["id_producto"],
                        $detalle["cantidad"],
                        $detalle["precio"],
                        $detalle["subtotal"]
                    );

                    if (!$stmtDetalle->execute()) {
                        throw new Exception("Error al guardar un producto: " . $stmtDetalle->error);
                    }
                }

                $stmtDetalle->close();

                /* OBTENER CLIENTE PARA PRODUCCIÓN */
                $idClienteProduccion = $idClientePedido;

                if ($tipo_cliente === "ocasional") {
                    $clienteExistente = null;

                    if ($documento_cliente !== "") {
                        $stmtBuscar = $conexion->prepare(
                            "SELECT id_cliente, estado
                             FROM clientes
                             WHERE documento = ?
                             LIMIT 1"
                        );

                        if (!$stmtBuscar) {
                            throw new Exception("No fue posible validar el documento del cliente.");
                        }

                        $stmtBuscar->bind_param("s", $documento_cliente);
                        $stmtBuscar->execute();
                        $clienteExistente = $stmtBuscar->get_result()->fetch_assoc();
                        $stmtBuscar->close();
                    }

                    if ($clienteExistente) {
                        if ($clienteExistente["estado"] !== "Activo") {
                            throw new Exception(
                                "El documento del cliente ocasional pertenece a un cliente inactivo."
                            );
                        }

                        $idClienteProduccion = (int)$clienteExistente["id_cliente"];
                    } else {
                        $resultadoCodigoCliente = $conexion->query(
                            "SELECT MAX(id_cliente) AS ultimo FROM clientes"
                        );

                        $filaCodigoCliente = $resultadoCodigoCliente->fetch_assoc();
                        $codigoCliente = "CLI-" . str_pad(
                            (int)($filaCodigoCliente["ultimo"] ?? 0) + 1,
                            4,
                            "0",
                            STR_PAD_LEFT
                        );

                        $documento = $documento_cliente !== "" ? $documento_cliente : null;
                        $telefono = $telefono_cliente !== "" ? $telefono_cliente : null;
                        $correo = $correo_cliente !== "" ? $correo_cliente : null;

                        $stmtClienteNuevo = $conexion->prepare(
                            "INSERT INTO clientes
                             (codigo, nombre, documento, telefono, correo, estado)
                             VALUES (?, ?, ?, ?, ?, 'Activo')"
                        );

                        if (!$stmtClienteNuevo) {
                            throw new Exception("No fue posible preparar el cliente ocasional.");
                        }

                        $stmtClienteNuevo->bind_param(
                            "sssss",
                            $codigoCliente,
                            $nombre_cliente,
                            $documento,
                            $telefono,
                            $correo
                        );

                        if (!$stmtClienteNuevo->execute()) {
                            throw new Exception(
                                "No fue posible registrar el cliente para producción: "
                                . $stmtClienteNuevo->error
                            );
                        }

                        $idClienteProduccion = $conexion->insert_id;
                        $stmtClienteNuevo->close();
                    }
                }

                if (!$idClienteProduccion) {
                    throw new Exception("No fue posible determinar el cliente de producción.");
                }

                /* GENERAR CÓDIGOS DE ÓRDENES */
                $resultadoOrden = $conexion->query(
                    "SELECT MAX(id_orden) AS ultimo FROM ordenes_produccion"
                );

                $filaOrden = $resultadoOrden->fetch_assoc();
                $numeroOrden = (int)($filaOrden["ultimo"] ?? 0) + 1;

                $stmtOrden = $conexion->prepare(
                    "INSERT INTO ordenes_produccion
                     (id_pedido, codigo, fecha, id_cliente, id_producto, cantidad,
                      responsable, fecha_entrega, estado, observaciones)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), 'Pendiente', ?)"
                );

                if (!$stmtOrden) {
                    throw new Exception(
                        "Error al preparar las órdenes de producción: " . $conexion->error
                    );
                }

                /* UNA ORDEN POR CADA PRODUCTO DEL PEDIDO */
                foreach ($detalles as $detalle) {
                    $codigoOrden = "OP-" . str_pad($numeroOrden, 4, "0", STR_PAD_LEFT);
                    $responsable = "Por asignar";
                    $numeroOrden++;

                    $stmtOrden->bind_param(
                        "issiiisss",
                        $id_pedido,
                        $codigoOrden,
                        $fecha,
                        $idClienteProduccion,
                        $detalle["id_producto"],
                        $detalle["cantidad"],
                        $responsable,
                        $fecha_entrega,
                        $observaciones
                    );

                    if (!$stmtOrden->execute()) {
                        throw new Exception(
                            "Error al crear una orden de producción: " . $stmtOrden->error
                        );
                    }
                }

                $stmtOrden->close();

                /* CONFIRMAR TODO */
                $conexion->commit();

                header(
                    "Location: Pedidos.php?mensaje="
                    . urlencode("Pedido y órdenes de producción creados correctamente.")
                );
                exit;

            } catch (Throwable $e) {
                $conexion->rollback();
                $error = "No fue posible guardar el pedido y sus órdenes de producción: "
                    . $e->getMessage();
            }
        }
    }
}

/* INCLUIR ENCABEZADO DESPUÉS DEL PROCESAMIENTO */
include("../includes/header.php");
?>

<main class="contenido">
    <section class="barra-superior">
        <a href="Pedidos.php" class="btn btn-principal">← Volver a Pedidos</a>
    </section>

    <header class="encabezado">
        <h1>Nuevo Pedido</h1>
        <p>Registra un nuevo pedido y sus productos.</p>
    </header>

    <?php if ($error !== ""): ?>
        <script>
            alert(<?= json_encode(
                $error,
                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
            ) ?>);
        </script>
    <?php endif; ?>

    <section class="panel">
        <form method="POST" action="NuevoPedido.php" id="formPedido">
            <div class="grupo-formulario">
                <div class="campo">
                    <label>Código</label>
                    <input type="text" value="<?= htmlspecialchars($codigo_pedido) ?>" readonly>
                </div>

                <div class="campo">
                    <label for="fecha">Fecha</label>
                    <input type="date" id="fecha" name="fecha"
                           value="<?= htmlspecialchars($fecha) ?>" required>
                </div>

                <div class="campo campo-completo">
                    <label>Tipo de Cliente</label>
                    <div class="tipo-cliente">
                        <label>
                            <input type="radio" name="tipo_cliente" value="registrado"
                                   id="clienteRegistrado"
                                   <?= $tipo_cliente === "registrado" ? "checked" : "" ?>>
                            Cliente registrado
                        </label>
                        <label>
                            <input type="radio" name="tipo_cliente" value="ocasional"
                                   id="clienteOcasional"
                                   <?= $tipo_cliente === "ocasional" ? "checked" : "" ?>>
                            Cliente ocasional
                        </label>
                    </div>
                </div>

                <div class="campo campo-completo" id="seccionRegistrado">
                    <label for="id_cliente">Cliente</label>
                    <select id="id_cliente" name="id_cliente">
                        <option value="">Seleccione un cliente</option>
                        <?php if ($clientes): ?>
                            <?php while ($cliente = $clientes->fetch_assoc()): ?>
                                <option value="<?= (int)$cliente["id_cliente"] ?>"
                                    <?= (string)$id_cliente === (string)$cliente["id_cliente"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($cliente["codigo"] . " - " . $cliente["nombre"]) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div id="seccionOcasional" class="campo-completo" style="display:none;">
                    <div class="grupo-formulario">
                        <div class="campo">
                            <label for="nombre_cliente">Nombre</label>
                            <input type="text" id="nombre_cliente" name="nombre_cliente"
                                   value="<?= htmlspecialchars($nombre_cliente ?? "") ?>"
                                   placeholder="Nombre del cliente">
                        </div>
                        <div class="campo">
                            <label for="documento_cliente">Documento</label>
                            <input type="text" id="documento_cliente" name="documento_cliente"
                                   value="<?= htmlspecialchars($documento_cliente ?? "") ?>"
                                   placeholder="Documento">
                        </div>
                        <div class="campo">
                            <label for="telefono_cliente">Teléfono</label>
                            <input type="text" id="telefono_cliente" name="telefono_cliente"
                                   value="<?= htmlspecialchars($telefono_cliente ?? "") ?>"
                                   placeholder="Teléfono">
                        </div>
                        <div class="campo">
                            <label for="correo_cliente">Correo</label>
                            <input type="email" id="correo_cliente" name="correo_cliente"
                                   value="<?= htmlspecialchars($correo_cliente ?? "") ?>"
                                   placeholder="Correo electrónico">
                        </div>
                    </div>
                </div>

                <div class="campo">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <?php foreach (["Pendiente", "En Producción", "Entregado"] as $opcionEstado): ?>
                            <option value="<?= htmlspecialchars($opcionEstado) ?>"
                                <?= $estado === $opcionEstado ? "selected" : "" ?>>
                                <?= htmlspecialchars($opcionEstado) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Productos</h2>
                        <p>Selecciona el producto y su presentación.</p>
                    </div>
                    <button type="button" class="btn btn-principal" id="agregarProducto">
                        + Agregar Producto
                    </button>
                </div>

                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="productosPedido"></tbody>
                </table>
            </div>

            <div class="resumen-pedido">
                <div><span>Subtotal</span><strong id="subtotal">$0</strong></div>
                <div><span>IVA (19%)</span><strong id="iva">$0</strong></div>
                <div><span>Total</span><strong id="total">$0</strong></div>
            </div>

            <div class="grupo-formulario">
                <div class="campo">
                    <label for="fecha_entrega">Fecha de Entrega</label>
                    <input type="date" id="fecha_entrega" name="fecha_entrega"
                           value="<?= htmlspecialchars($fecha_entrega) ?>">
                </div>

                <div class="campo campo-completo">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="4"><?= htmlspecialchars($observaciones) ?></textarea>
                </div>
            </div>

            <div class="barra-acciones botones-formulario">
                <a href="Pedidos.php" class="btn btn-eliminar">Cancelar</a>
                <button type="submit" class="btn btn-principal">Guardar Pedido</button>
            </div>
        </form>
    </section>
</main>

<script>
const productos = <?= json_encode(
    (function () use ($productos) {
        $lista = [];
        if ($productos) {
            $productos->data_seek(0);
            while ($producto = $productos->fetch_assoc()) {
                $lista[] = [
                    "id" => (int)$producto["id_producto"],
                    "nombre" => $producto["nombre"],
                    "presentacion" => trim($producto["presentacion"] ?? ""),
                    "precio" => (float)$producto["precio"]
                ];
            }
        }
        return $lista;
    })(),
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

const tabla = document.getElementById("productosPedido");
const boton = document.getElementById("agregarProducto");
const formulario = document.getElementById("formPedido");
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

function moneda(valor) {
    return "$" + Math.round(valor).toLocaleString("es-CO");
}

function calcular() {
    let subtotal = 0;

    document.querySelectorAll("#productosPedido tr").forEach(function(fila) {
        const cantidad = Number(fila.querySelector(".cantidad").value) || 0;
        const precio = Number(fila.querySelector(".precio").value) || 0;
        const valor = cantidad * precio;
        fila.querySelector(".subtotal-producto").textContent = moneda(valor);
        subtotal += valor;
    });

    const iva = subtotal * 0.19;
    document.getElementById("subtotal").textContent = moneda(subtotal);
    document.getElementById("iva").textContent = moneda(iva);
    document.getElementById("total").textContent = moneda(subtotal + iva);
}

function agregarProducto() {
    if (productos.length === 0) {
        alert("No hay productos activos registrados.");
        return;
    }

    const fila = document.createElement("tr");
    const celdaProducto = document.createElement("td");
    const selector = document.createElement("select");
    selector.name = "producto[]";
    selector.className = "producto";
    selector.required = true;

    const opcionInicial = document.createElement("option");
    opcionInicial.value = "";
    opcionInicial.textContent = "Seleccione un producto";
    selector.appendChild(opcionInicial);

    productos.forEach(function(producto) {
        const opcion = document.createElement("option");
        opcion.value = producto.id;
        opcion.dataset.precio = producto.precio;
        opcion.textContent = producto.nombre + " — " + (producto.presentacion || "Única");
        selector.appendChild(opcion);
    });

    celdaProducto.appendChild(selector);
    fila.appendChild(celdaProducto);

    const celdaCantidad = document.createElement("td");
    const cantidad = document.createElement("input");
    cantidad.type = "number";
    cantidad.name = "cantidad[]";
    cantidad.className = "cantidad";
    cantidad.value = "1";
    cantidad.min = "1";
    cantidad.step = "1";
    cantidad.required = true;
    celdaCantidad.appendChild(cantidad);
    fila.appendChild(celdaCantidad);

    const celdaPrecio = document.createElement("td");
    const precio = document.createElement("input");
    precio.type = "number";
    precio.className = "precio";
    precio.value = "0";
    precio.min = "0";
    precio.step = "0.01";
    precio.readOnly = true;
    precio.setAttribute("aria-label", "Precio del producto");
    celdaPrecio.appendChild(precio);
    fila.appendChild(celdaPrecio);

    const celdaSubtotal = document.createElement("td");
    const textoSubtotal = document.createElement("span");
    textoSubtotal.className = "subtotal-producto";
    textoSubtotal.textContent = "$0";
    celdaSubtotal.appendChild(textoSubtotal);
    fila.appendChild(celdaSubtotal);

    const celdaAccion = document.createElement("td");
    const eliminar = document.createElement("button");
    eliminar.type = "button";
    eliminar.className = "btn btn-eliminar eliminar";
    eliminar.textContent = "Eliminar";
    celdaAccion.appendChild(eliminar);
    fila.appendChild(celdaAccion);

    tabla.appendChild(fila);

    selector.addEventListener("change", function() {
        precio.value = this.options[this.selectedIndex].dataset.precio || "0";
        calcular();
    });

    cantidad.addEventListener("input", calcular);
    eliminar.addEventListener("click", function() {
        fila.remove();
        calcular();
    });

    calcular();
}

boton.addEventListener("click", agregarProducto);
agregarProducto();

formulario.addEventListener("submit", function(evento) {
    const filas = document.querySelectorAll("#productosPedido tr");

    if (filas.length === 0) {
        evento.preventDefault();
        alert("Debe agregar al menos un producto.");
        return;
    }

    for (const fila of filas) {
        const producto = fila.querySelector(".producto").value;
        const cantidad = Number(fila.querySelector(".cantidad").value);

        if (!producto || !Number.isInteger(cantidad) || cantidad < 1) {
            evento.preventDefault();
            alert("Verifique que cada producto esté seleccionado y tenga una cantidad válida.");
            return;
        }
    }
});
</script>

<?php include("../includes/footer.php"); ?>