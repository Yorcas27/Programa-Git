
<?php

$titulo = "VER PEDIDO";
$css = "Pedidos.css";

require_once("../includes/conexion.php");

/* ORIGEN */

$origen = $_GET["origen"] ?? "";
$desde_reporte = $origen === "reporte";

/* OBTENER PEDIDO */

$id_pedido = (int)($_GET["id"] ?? 0);

if ($id_pedido <= 0) {
    if ($desde_reporte) {
        header("Location: ../REPORTES/Pedidos/ReportePedidos.php");
    } else {
        header("Location: Pedidos.php");
    }
    exit;
}

$stmt_pedido = $conexion->prepare("
    SELECT
        p.id_pedido,
        p.codigo_pedido,
        p.id_cliente,
        p.nombre_cliente,
        p.documento_cliente,
        p.telefono_cliente,
        p.correo_cliente,
        p.fecha,
        p.fecha_entrega,
        p.subtotal,
        p.iva,
        p.total,
        p.estado,
        p.observaciones,
        c.nombre AS cliente_registrado
    FROM pedidos p
    LEFT JOIN clientes c
        ON c.id_cliente = p.id_cliente
    WHERE p.id_pedido = ?
");

$stmt_pedido->bind_param("i", $id_pedido);
$stmt_pedido->execute();

$resultado_pedido = $stmt_pedido->get_result();
$pedido = $resultado_pedido->fetch_assoc();

$stmt_pedido->close();

if (!$pedido) {
    if ($desde_reporte) {
        header("Location: ../REPORTES/Pedidos/ReportePedidos.php");
    } else {
        header("Location: Pedidos.php");
    }
    exit;
}

/* DETERMINAR CLIENTE */

$cliente_es_ocasional = empty($pedido["id_cliente"]);

if ($cliente_es_ocasional) {
    $nombre_cliente = $pedido["nombre_cliente"] ?? "";
    $documento_cliente = $pedido["documento_cliente"] ?? "";
    $telefono_cliente = $pedido["telefono_cliente"] ?? "";
    $correo_cliente = $pedido["correo_cliente"] ?? "";
} else {
    $nombre_cliente = $pedido["cliente_registrado"] ?? "";
    $documento_cliente = "";
    $telefono_cliente = "";
    $correo_cliente = "";
}

/* OBTENER DETALLES CON PRESENTACIÓN */

$stmt_detalles = $conexion->prepare("
    SELECT
        dp.cantidad,
        dp.precio,
        dp.subtotal,
        pr.nombre AS producto,
        pr.presentacion AS presentacion
    FROM detalle_pedido dp
    INNER JOIN productos pr
        ON pr.id_producto = dp.id_producto
    WHERE dp.id_pedido = ?
    ORDER BY dp.id_detalle_pedido ASC
");

$stmt_detalles->bind_param("i", $id_pedido);
$stmt_detalles->execute();

$detalles = $stmt_detalles->get_result();

$stmt_detalles->close();

/* FORMATO MONEDA */

function formatoMoneda($valor)
{
    return "$" . number_format(
        (float)$valor,
        0,
        ",",
        "."
    );
}

/* FORMATO FECHA */

function formatoFecha($fecha)
{
    if (empty($fecha)) {
        return "";
    }

    return date("d/m/Y", strtotime($fecha));
}

include("../includes/header.php");

?>

<main class="contenido">

    <!-- BARRA SUPERIOR -->

    <section class="barra-superior">

        <?php if ($desde_reporte): ?>

            <a
                href="../REPORTES/Pedidos/ReportePedidos.php"
                class="btn btn-principal btn-volver-reporte"
            >
                ← Volver a Reporte de Pedidos
            </a>

        <?php else: ?>

            <a
                href="Pedidos.php"
                class="btn btn-principal"
            >
                ← Volver a Pedidos
            </a>

        <?php endif; ?>

        <a
            href="/YCL_Solutions/DASHBOARD/Dashboard.php"
            class="btn-inicio"
        >
            ⌂ Inicio
        </a>

    </section>

    <!-- ENCABEZADO -->

    <header class="encabezado">
        <h1>Ver Pedido</h1>
        <p>Consulta la información detallada del pedido seleccionado.</p>
    </header>

    <!-- INFORMACIÓN GENERAL -->

    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Información General</h2>
                <p>Datos principales del pedido.</p>
            </div>
        </div>

        <div class="grupo-formulario">

            <div class="campo">
                <label for="codigo">Código del Pedido</label>
                <input
                    type="text"
                    id="codigo"
                    value="<?= htmlspecialchars($pedido["codigo_pedido"]) ?>"
                    readonly
                >
            </div>

            <div class="campo">
                <label for="fecha">Fecha</label>
                <input
                    type="text"
                    id="fecha"
                    value="<?= formatoFecha($pedido["fecha"]) ?>"
                    readonly
                >
            </div>

            <div class="campo">
                <label for="tipo_cliente">Tipo de Cliente</label>
                <input
                    type="text"
                    id="tipo_cliente"
                    value="<?= $cliente_es_ocasional ? "Cliente ocasional" : "Cliente registrado" ?>"
                    readonly
                >
            </div>

            <div class="campo">
                <label for="estado">Estado</label>
                <input
                    type="text"
                    id="estado"
                    value="<?= htmlspecialchars($pedido["estado"]) ?>"
                    readonly
                >
            </div>

            <div class="campo">
                <label for="clienteNombre">Cliente</label>
                <input
                    type="text"
                    id="clienteNombre"
                    value="<?= htmlspecialchars($nombre_cliente) ?>"
                    readonly
                >
            </div>

            <?php if ($cliente_es_ocasional): ?>

                <div class="campo">
                    <label for="documento">Documento</label>
                    <input
                        type="text"
                        id="documento"
                        value="<?= htmlspecialchars($documento_cliente) ?>"
                        readonly
                    >
                </div>

                <div class="campo">
                    <label for="telefono">Teléfono</label>
                    <input
                        type="text"
                        id="telefono"
                        value="<?= htmlspecialchars($telefono_cliente) ?>"
                        readonly
                    >
                </div>

                <div class="campo">
                    <label for="correo">Correo</label>
                    <input
                        type="text"
                        id="correo"
                        value="<?= htmlspecialchars($correo_cliente) ?>"
                        readonly
                    >
                </div>

            <?php endif; ?>

        </div>

    </section>

    <!-- PRODUCTOS -->

    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Productos del Pedido</h2>
                <p>Detalle de los productos y sus presentaciones.</p>
            </div>
        </div>

        <table class="tabla">

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

                <?php if ($detalles->num_rows > 0): ?>

                    <?php while ($detalle = $detalles->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($detalle["producto"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    trim($detalle["presentacion"] ?? "") !== ""
                                        ? $detalle["presentacion"]
                                        : "Única"
                                ) ?>
                            </td>

                            <td>
                                <?= (int)$detalle["cantidad"] ?>
                            </td>

                            <td>
                                <?= formatoMoneda($detalle["precio"]) ?>
                            </td>

                            <td>
                                <?= formatoMoneda($detalle["subtotal"]) ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">
                            No hay productos registrados para este pedido.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </section>

    <!-- RESUMEN -->

    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Resumen del Pedido</h2>
                <p>Valores registrados para el pedido.</p>
            </div>
        </div>

        <div class="grupo-formulario">

            <div class="campo">
                <label for="subtotal">Subtotal</label>
                <input
                    type="text"
                    id="subtotal"
                    value="<?= formatoMoneda($pedido["subtotal"]) ?>"
                    readonly
                >
            </div>

            <div class="campo">
                <label for="iva">IVA (19%)</label>
                <input
                    type="text"
                    id="iva"
                    value="<?= formatoMoneda($pedido["iva"]) ?>"
                    readonly
                >
            </div>

            <div class="campo">
                <label for="total">Total</label>
                <input
                    type="text"
                    id="total"
                    value="<?= formatoMoneda($pedido["total"]) ?>"
                    readonly
                >
            </div>

            <div class="campo">
                <label for="fecha_entrega">Fecha de Entrega</label>
                <input
                    type="text"
                    id="fecha_entrega"
                    value="<?= formatoFecha($pedido["fecha_entrega"]) ?>"
                    readonly
                >
            </div>

            <div class="campo campo-completo">
                <label for="observaciones">Observaciones</label>
                <textarea
                    id="observaciones"
                    rows="5"
                    readonly
                ><?= htmlspecialchars($pedido["observaciones"] ?? "") ?></textarea>
            </div>

        </div>

    </section>

    <!-- BOTONES -->

    <section class="panel">

        <div class="botones-formulario">

            <a
                href="EditarPedido.php?id=<?= (int)$pedido["id_pedido"] ?><?= $desde_reporte ? "&origen=reporte" : "" ?>"
                class="btn btn-principal"
            >
                Editar Pedido
            </a>

            <?php if ($desde_reporte): ?>

                <a
                    href="../REPORTES/Pedidos/ReportePedidos.php"
                    class="btn btn-principal btn-volver-reporte"
                >
                    ← Volver a Reporte de Pedidos
                </a>

            <?php else: ?>

                <a
                    href="Pedidos.php"
                    class="btn btn-principal"
                >
                    Volver
                </a>

            <?php endif; ?>

        </div>

    </section>

</main>

<?php include("../includes/footer.php"); ?>