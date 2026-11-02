
<?php
$titulo = "Ver Factura";
$css = "Facturacion.css";

require_once("../includes/config.php");
require_once("../includes/conexion.php");

$id_factura = filter_input(INPUT_GET, "id_factura", FILTER_VALIDATE_INT);

if (!$id_factura || $id_factura < 1) {
    header("Location: Facturacion.php?error=consulta");
    exit;
}

/* DATOS DE LA FACTURA */
$sqlFactura = "
    SELECT
        f.id_factura,
        f.numero_factura,
        f.id_pedido,
        f.id_cliente,
        f.nombre_cliente,
        f.documento_cliente,
        f.telefono_cliente,
        f.correo_cliente,
        f.fecha,
        f.metodo_pago,
        f.estado,
        f.total,
        p.codigo_pedido,
        COALESCE(
            NULLIF(f.nombre_cliente, ''),
            NULLIF(c.nombre, ''),
            NULLIF(p.nombre_cliente, ''),
            'Cliente ocasional'
        ) AS cliente,
        COALESCE(
            NULLIF(f.documento_cliente, ''),
            NULLIF(c.documento, ''),
            NULLIF(p.documento_cliente, ''),
            ''
        ) AS documento,
        COALESCE(
            NULLIF(f.telefono_cliente, ''),
            NULLIF(c.telefono, ''),
            NULLIF(p.telefono_cliente, ''),
            ''
        ) AS telefono,
        COALESCE(
            NULLIF(f.correo_cliente, ''),
            NULLIF(c.correo, ''),
            NULLIF(p.correo_cliente, ''),
            ''
        ) AS correo
    FROM facturas f
    LEFT JOIN pedidos p ON p.id_pedido = f.id_pedido
    LEFT JOIN clientes c ON c.id_cliente = f.id_cliente
    WHERE f.id_factura = ?
    LIMIT 1
";

$stmt = $conexion->prepare($sqlFactura);
$stmt->bind_param("i", $id_factura);
$stmt->execute();
$factura = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$factura) {
    header("Location: Facturacion.php?error=consulta");
    exit;
}

/* DETALLE DE LA FACTURA */
$sqlDetalles = "
    SELECT
        df.id_producto,
        pr.nombre AS producto,
        pr.presentacion,
        df.cantidad,
        df.valor,
        df.subtotal
    FROM detalle_factura df
    INNER JOIN productos pr ON pr.id_producto = df.id_producto
    WHERE df.id_factura = ?
    ORDER BY df.id_detalle_factura ASC
";

$stmtDetalles = $conexion->prepare($sqlDetalles);
$stmtDetalles->bind_param("i", $id_factura);
$stmtDetalles->execute();
$resultadoDetalles = $stmtDetalles->get_result();

$detalles = [];
$subtotalDetalle = 0;

while ($detalle = $resultadoDetalles->fetch_assoc()) {
    $detalles[] = $detalle;
    $subtotalDetalle += (float)$detalle["subtotal"];
}

$stmtDetalles->close();

$origen = !empty($factura["id_pedido"])
    ? ($factura["codigo_pedido"] ?? "Pedido asociado")
    : "Venta directa";

function monedaFactura($valor) {
    return "$" . number_format((float)$valor, 0, ",", ".");
}

include("../includes/header.php");
?>

<main class="contenido-form">

    <section class="factura-superior">
        <a href="Facturacion.php" class="btn-regresar">
            ← Volver a Facturación
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>
    </section>

    <header class="encabezado">
        <h1>Factura <?= htmlspecialchars($factura["numero_factura"], ENT_QUOTES, "UTF-8") ?></h1>
        <p>Consulta la información y el detalle de la factura generada.</p>
    </header>

    <?php if (isset($_GET["anulada"])): ?>
        <div class="mensaje-facturacion exito notificacion-temporal" role="status" aria-live="polite">
            <span class="mensaje-icono">✓</span>
            <div class="mensaje-contenido">
                <strong>¡Factura anulada correctamente!</strong>
                <p>Los movimientos de salida asociados fueron reversados y las existencias correspondientes se restituyeron al inventario.</p>
            </div>
            <button type="button" class="mensaje-cerrar" aria-label="Cerrar notificación">&times;</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET["error_anulacion"])): ?>
        <div class="mensaje-facturacion error notificacion-temporal" role="alert" aria-live="assertive">
            <span class="mensaje-icono">!</span>
            <div class="mensaje-contenido">
                <strong>No fue posible anular la factura</strong>
                <p><?= htmlspecialchars($_GET["error_anulacion"], ENT_QUOTES, "UTF-8") ?></p>
            </div>
            <button type="button" class="mensaje-cerrar" aria-label="Cerrar notificación">&times;</button>
        </div>
    <?php endif; ?>

    <div class="ver-factura-contenedores">

        <section class="panel ver-factura-panel">
            <h2>Información General</h2>

            <div class="grupo-formulario">
                <div class="campo">
                    <label>Número de factura</label>
                    <input type="text" value="<?= htmlspecialchars($factura["numero_factura"], ENT_QUOTES, "UTF-8") ?>" readonly>
                </div>

                <div class="campo">
                    <label>Origen</label>
                    <input type="text" value="<?= htmlspecialchars($origen, ENT_QUOTES, "UTF-8") ?>" readonly>
                </div>

                <div class="campo">
                    <label>Cliente</label>
                    <input type="text" value="<?= htmlspecialchars($factura["cliente"], ENT_QUOTES, "UTF-8") ?>" readonly>
                </div>

                <div class="campo">
                    <label>Documento</label>
                    <input type="text" value="<?= htmlspecialchars($factura["documento"] ?: "No registrado", ENT_QUOTES, "UTF-8") ?>" readonly>
                </div>

                <div class="campo">
                    <label>Teléfono</label>
                    <input type="text" value="<?= htmlspecialchars($factura["telefono"] ?: "No registrado", ENT_QUOTES, "UTF-8") ?>" readonly>
                </div>

                <div class="campo">
                    <label>Correo</label>
                    <input type="text" value="<?= htmlspecialchars($factura["correo"] ?: "No registrado", ENT_QUOTES, "UTF-8") ?>" readonly>
                </div>

                <div class="campo">
                    <label>Fecha</label>
                    <input type="text" value="<?= date("d/m/Y", strtotime($factura["fecha"])) ?>" readonly>
                </div>

                <div class="campo">
                    <label>Método de pago</label>
                    <input type="text" value="<?= htmlspecialchars($factura["metodo_pago"], ENT_QUOTES, "UTF-8") ?>" readonly>
                </div>

                <div class="campo">
                    <label>Estado</label>
                    <input type="text" value="<?= htmlspecialchars($factura["estado"], ENT_QUOTES, "UTF-8") ?>" readonly>
                </div>
            </div>
        </section>

        <section class="panel ver-factura-panel">
            <h2>Detalle de la Factura</h2>

            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Presentación</th>
                            <th>Cantidad</th>
                            <th>Valor</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($detalles)): ?>
                            <?php foreach ($detalles as $detalle): ?>
                                <tr>
                                    <td><?= htmlspecialchars($detalle["producto"], ENT_QUOTES, "UTF-8") ?></td>
                                    <td><?= htmlspecialchars($detalle["presentacion"] ?: "Única", ENT_QUOTES, "UTF-8") ?></td>
                                    <td><?= (int)$detalle["cantidad"] ?></td>
                                    <td><?= monedaFactura($detalle["valor"]) ?></td>
                                    <td><?= monedaFactura($detalle["subtotal"]) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No hay productos registrados para esta factura.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div style="text-align:right;margin-top:20px;">
                <p>
                    Subtotal de los productos:
                    <strong><?= monedaFactura($subtotalDetalle) ?></strong>
                </p>

                <h2 class="total-factura">
                    Total de la factura: <?= monedaFactura($factura["total"]) ?>
                </h2>
            </div>
        </section>

    </div>

    <section class="acciones-form ver-factura-acciones">

        <a href="ImprimirFactura.php?id_factura=<?= (int)$factura["id_factura"] ?>"
           class="btn-regresar" target="_blank">
            Imprimir Factura
        </a>

        <a href="ExportarFacturaPDF.php?id_factura=<?= (int)$factura["id_factura"] ?>"
           class="btn-regresar" target="_blank">
            Exportar PDF
        </a>

        <a href="../REPORTES/FACTURACION/ReporteFacturacion.php"
           class="btn-regresar">
            Volver al Reporte de Facturación
        </a>

        <?php if (in_array($factura["estado"], ["Pendiente", "Pagada"], true)): ?>
            <form method="POST"
                  action="AnularFactura.php"
                  onsubmit="return confirm('¿Estás seguro de anular esta factura? Esta operación restaurará las existencias que se hayan descontado y no se puede repetir.');">

                <input type="hidden"
                       name="id_factura"
                       value="<?= (int)$factura["id_factura"] ?>">

                <button type="submit" class="btn-anular-factura">
                    Anular Factura
                </button>
            </form>
        <?php endif; ?>

    </section>

</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".notificacion-temporal").forEach(function (mensaje) {
        let temporizador;

        function ocultarMensaje() {
            clearTimeout(temporizador);
            mensaje.classList.add("ocultar");

            setTimeout(function () {
                mensaje.remove();
            }, 650);
        }

        mensaje.querySelector(".mensaje-cerrar").addEventListener("click", ocultarMensaje);

        temporizador = setTimeout(ocultarMensaje, 4000);
    });
});
</script>

<?php include("../includes/footer.php"); ?>
