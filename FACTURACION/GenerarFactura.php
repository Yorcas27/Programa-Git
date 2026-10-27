<?php

$titulo = "Generar Factura";
$css = "Facturacion.css";

include("../includes/config.php");
require_once("../includes/conexion.php");

/* VARIABLES */

$id_pedido = isset($_GET["id_pedido"])
    ? (int) $_GET["id_pedido"]
    : (isset($_POST["id_pedido"]) ? (int) $_POST["id_pedido"] : 0);

$error = "";
$pedido = null;
$detalles = [];

/* OBTENER PEDIDO Y DATOS DEL CLIENTE */

function obtenerPedidoFactura($conexion, $id_pedido)
{
    $sql = "
        SELECT
            p.id_pedido,
            p.codigo_pedido,
            p.id_cliente,
            COALESCE(NULLIF(c.nombre, ''), NULLIF(p.nombre_cliente, ''), 'Cliente ocasional') AS cliente,
            COALESCE(NULLIF(c.documento, ''), NULLIF(p.documento_cliente, ''), '') AS documento,
            COALESCE(NULLIF(c.telefono, ''), NULLIF(p.telefono_cliente, ''), '') AS telefono,
            COALESCE(NULLIF(c.correo, ''), NULLIF(p.correo_cliente, ''), '') AS correo,
            p.fecha,
            p.subtotal,
            p.iva,
            p.total,
            p.estado
        FROM pedidos p
        LEFT JOIN clientes c ON p.id_cliente = c.id_cliente
        WHERE p.id_pedido = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

/* PROCESAR GUARDADO */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $metodo_pago = isset($_POST["metodo_pago"])
        ? trim($_POST["metodo_pago"])
        : "";

    $estado = isset($_POST["estado"])
        ? trim($_POST["estado"])
        : "Pendiente";

    if ($id_pedido <= 0) {

        $error = "Debe seleccionar un pedido para generar la factura.";

    } elseif (!in_array($metodo_pago, ["Efectivo", "Transferencia", "Tarjeta"], true)) {

        $error = "Debe seleccionar un método de pago válido.";

    } elseif (!in_array($estado, ["Pendiente", "Pagada"], true)) {

        $error = "El estado seleccionado no es válido.";

    } else {

        /* VERIFICAR SI YA EXISTE FACTURA */

        $sql = "
            SELECT id_factura, numero_factura
            FROM facturas
            WHERE id_pedido = ?
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {

            $facturaExistente = $resultado->fetch_assoc();

            $error = "El pedido ya tiene la factura "
                . $facturaExistente["numero_factura"] . ".";

        } else {

            /* OBTENER PEDIDO */

            $pedidoGuardar = obtenerPedidoFactura($conexion, $id_pedido);

            if (!$pedidoGuardar) {

                $error = "No se encontró el pedido seleccionado.";

            } elseif ($pedidoGuardar["estado"] === "Cancelado") {

                $error = "No se puede facturar un pedido cancelado.";

            } else {

                /* OBTENER PRODUCTOS */

                $sql = "
                    SELECT
                        dp.id_producto,
                        pr.nombre AS producto,
                        dp.cantidad,
                        dp.precio,
                        dp.subtotal
                    FROM detalle_pedido dp
                    INNER JOIN productos pr ON dp.id_producto = pr.id_producto
                    WHERE dp.id_pedido = ?
                    ORDER BY dp.id_detalle_pedido ASC
                ";

                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("i", $id_pedido);
                $stmt->execute();

                $resultadoDetalles = $stmt->get_result();

                if ($resultadoDetalles->num_rows === 0) {

                    $error = "El pedido no tiene productos registrados.";

                } else {

                    $detallesGuardar = [];

                    while ($detalle = $resultadoDetalles->fetch_assoc()) {
                        $detallesGuardar[] = $detalle;
                    }

                    /* GENERAR NÚMERO DE FACTURA */

                    $resultadoNumero = $conexion->query("
                        SELECT numero_factura
                        FROM facturas
                        ORDER BY id_factura DESC
                        LIMIT 1
                    ");

                    $numero_factura = "FAC001";

                    if ($resultadoNumero && $resultadoNumero->num_rows > 0) {

                        $ultima = $resultadoNumero->fetch_assoc();
                        $ultimoNumero = (int) substr($ultima["numero_factura"], 3);

                        $numero_factura = "FAC" . str_pad(
                            $ultimoNumero + 1,
                            3,
                            "0",
                            STR_PAD_LEFT
                        );
                    }

                    $fecha = date("Y-m-d");
                    $total = (float) $pedidoGuardar["total"];

                    /* INICIAR TRANSACCIÓN */

                    $conexion->begin_transaction();

                    try {

                        /* INSERTAR FACTURA */

                        if ($pedidoGuardar["id_cliente"] === null) {

                            // Cliente ocasional: insertar NULL real.

                            $sqlFactura = "
                                INSERT INTO facturas
                                (
                                    numero_factura,
                                    id_pedido,
                                    id_cliente,
                                    fecha,
                                    metodo_pago,
                                    estado,
                                    total
                                )
                                VALUES (?, ?, NULL, ?, ?, ?, ?)
                            ";

                            $stmtFactura = $conexion->prepare($sqlFactura);

                            $stmtFactura->bind_param(
                                "sisssd",
                                $numero_factura,
                                $id_pedido,
                                $fecha,
                                $metodo_pago,
                                $estado,
                                $total
                            );

                        } else {

                            // Cliente registrado.

                            $id_cliente = (int) $pedidoGuardar["id_cliente"];

                            $sqlFactura = "
                                INSERT INTO facturas
                                (
                                    numero_factura,
                                    id_pedido,
                                    id_cliente,
                                    fecha,
                                    metodo_pago,
                                    estado,
                                    total
                                )
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ";

                            $stmtFactura = $conexion->prepare($sqlFactura);

                            $stmtFactura->bind_param(
                                "siisssd",
                                $numero_factura,
                                $id_pedido,
                                $id_cliente,
                                $fecha,
                                $metodo_pago,
                                $estado,
                                $total
                            );
                        }

                        if (!$stmtFactura->execute()) {
                            throw new Exception("No se pudo guardar la factura.");
                        }

                        $id_factura = $conexion->insert_id;

                        /* INSERTAR DETALLE DE FACTURA */

                        $sqlDetalle = "
                            INSERT INTO detalle_factura
                            (
                                id_factura,
                                id_producto,
                                cantidad,
                                valor,
                                subtotal
                            )
                            VALUES (?, ?, ?, ?, ?)
                        ";

                        $stmtDetalle = $conexion->prepare($sqlDetalle);

                        foreach ($detallesGuardar as $detalle) {

                            $id_producto = (int) $detalle["id_producto"];
                            $cantidad = (int) $detalle["cantidad"];
                            $valor = (float) $detalle["precio"];
                            $subtotal = (float) $detalle["subtotal"];

                            $stmtDetalle->bind_param(
                                "iiidd",
                                $id_factura,
                                $id_producto,
                                $cantidad,
                                $valor,
                                $subtotal
                            );

                            if (!$stmtDetalle->execute()) {
                                throw new Exception(
                                    "No se pudo guardar el detalle de la factura."
                                );
                            }
                        }

                        $conexion->commit();

                        header(
                            "Location: VerFactura.php?id_factura=" . $id_factura
                        );
                        exit;

                    } catch (Throwable $e) {

                        $conexion->rollback();

                        error_log("Error al generar factura: " . $e->getMessage());

                        $error = "No fue posible guardar la factura. Verifica los datos e inténtalo nuevamente.";
                    }
                }
            }
        }
    }
}

/* CARGAR PEDIDO PARA MOSTRAR EL FORMULARIO */

if ($id_pedido > 0) {

    $pedido = obtenerPedidoFactura($conexion, $id_pedido);

    if ($pedido) {

        $sql = "
            SELECT
                dp.id_producto,
                pr.nombre AS producto,
                dp.cantidad,
                dp.precio,
                dp.subtotal
            FROM detalle_pedido dp
            INNER JOIN productos pr ON dp.id_producto = pr.id_producto
            WHERE dp.id_pedido = ?
            ORDER BY dp.id_detalle_pedido ASC
        ";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();

        $resultadoDetalles = $stmt->get_result();

        while ($detalle = $resultadoDetalles->fetch_assoc()) {
            $detalles[] = $detalle;
        }

    } elseif ($error === "") {

        $error = "No se encontró el pedido seleccionado.";
    }
}

/* PRÓXIMO NÚMERO DE FACTURA */

$numero_factura = "FAC001";

$resultadoNumero = $conexion->query("
    SELECT numero_factura
    FROM facturas
    ORDER BY id_factura DESC
    LIMIT 1
");

if ($resultadoNumero && $resultadoNumero->num_rows > 0) {

    $ultima = $resultadoNumero->fetch_assoc();
    $ultimoNumero = (int) substr($ultima["numero_factura"], 3);

    $numero_factura = "FAC" . str_pad(
        $ultimoNumero + 1,
        3,
        "0",
        STR_PAD_LEFT
    );
}

include("../includes/header.php");

?>

<main class="contenido-form">

    <a href="Facturacion.php" class="btn-regresar">
        ← Volver a Facturación
    </a>

    <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
        ⌂ Inicio
    </a>

    <header class="encabezado">
        <h1>Generar Factura</h1>
        <p>Creación de factura a partir de un pedido registrado.</p>
    </header>

    <?php if ($error !== ""): ?>
        <div class="mensaje-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!$pedido): ?>

        <section class="panel">
            <h2>Seleccionar Pedido</h2>
            <p>
                Para generar una factura debe seleccionar un pedido desde
                la sección "Pedidos Pendientes de Facturar".
            </p>
            <a href="Facturacion.php" class="btn-regresar">
                ← Volver a Facturación
            </a>
        </section>

    <?php else: ?>

        <form method="POST" class="factura-generar">

            <input
                type="hidden"
                name="id_pedido"
                value="<?= (int) $pedido["id_pedido"] ?>"
            >

            <div class="factura-contenedores">

                <section class="panel factura-panel">
                    <h2>Información de Factura</h2>

                    <div class="campo">
                        <label>Número Factura</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($numero_factura) ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label>Pedido Asociado</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($pedido["codigo_pedido"]) ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label>Tipo de Cliente</label>
                        <input
                            type="text"
                            value="<?= $pedido["id_cliente"] !== null ? "Registrado" : "Ocasional" ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label>Cliente</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($pedido["cliente"]) ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label>Documento</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($pedido["documento"] ?? "") ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label>Teléfono</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($pedido["telefono"] ?? "") ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label>Correo</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($pedido["correo"] ?? "") ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label>Fecha</label>
                        <input
                            type="date"
                            value="<?= date("Y-m-d") ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label>Método de Pago</label>
                        <select name="metodo_pago" required>
                            <option value="">Seleccione método</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Tarjeta">Tarjeta</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label>Estado</label>
                        <select name="estado" required>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Pagada">Pagada</option>
                        </select>
                    </div>
                </section>

                <section class="panel factura-panel">
                    <h2>Detalle del Pedido</h2>

                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Valor</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($detalles as $detalle): ?>
                                <tr>
                                    <td><?= htmlspecialchars($detalle["producto"]) ?></td>
                                    <td><?= (int) $detalle["cantidad"] ?></td>
                                    <td>
                                        $<?= number_format(
                                            $detalle["precio"],
                                            0,
                                            ",",
                                            "."
                                        ) ?>
                                    </td>
                                    <td>
                                        $<?= number_format(
                                            $detalle["subtotal"],
                                            0,
                                            ",",
                                            "."
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <h2 class="total-factura">
                        Total: $<?= number_format($pedido["total"], 0, ",", ".") ?>
                    </h2>
                </section>

            </div>

            <div class="acciones-form factura-acciones">
                <button type="submit">Guardar Factura</button>
                <a href="Facturacion.php" class="btn-regresar">Cancelar</a>
            </div>

        </form>

    <?php endif; ?>

</main>

<?php include("../includes/footer.php"); ?>