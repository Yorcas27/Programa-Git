<?php
$titulo = "Facturación";
$css = "Facturacion.css";

require_once("../includes/config.php");
require_once("../includes/conexion.php");

$mensaje = "";
$tipoMensaje = "";

if (isset($_GET["eliminada"]) && $_GET["eliminada"] === "1") {
    $mensaje = "La factura y sus detalles se eliminaron correctamente.";
    $tipoMensaje = "exito";
} elseif (isset($_GET["error"])) {
    $mensaje = $_GET["error"] === "eliminar"
        ? "No fue posible eliminar la factura. Verifica la solicitud e inténtalo nuevamente."
        : "No se pudo procesar la solicitud.";
    $tipoMensaje = "error";
}

/* KPI DE FACTURACIÓN */
$resultadoTotal = $conexion->query("SELECT COUNT(*) AS total FROM facturas");
$totalFacturas = (int)($resultadoTotal->fetch_assoc()["total"] ?? 0);

$resultadoMes = $conexion->query("
    SELECT COUNT(*) AS total
    FROM facturas
    WHERE fecha >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
      AND fecha < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')
");
$facturasMes = (int)($resultadoMes->fetch_assoc()["total"] ?? 0);

$resultadoPagadas = $conexion->query("
    SELECT COUNT(*) AS total FROM facturas WHERE estado = 'Pagada'
");
$facturasPagadas = (int)($resultadoPagadas->fetch_assoc()["total"] ?? 0);

$resultadoPendientes = $conexion->query("
    SELECT COUNT(*) AS total FROM facturas WHERE estado = 'Pendiente'
");
$facturasPendientes = (int)($resultadoPendientes->fetch_assoc()["total"] ?? 0);

/* PEDIDOS PENDIENTES DE FACTURAR */
$sqlPedidos = "
    SELECT
        p.id_pedido,
        p.codigo_pedido,
        COALESCE(
            NULLIF(c.nombre, ''),
            NULLIF(p.nombre_cliente, ''),
            'Cliente ocasional'
        ) AS cliente,
        p.total
    FROM pedidos p
    LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
    WHERE p.estado <> 'Cancelado'
      AND NOT EXISTS (
          SELECT 1
          FROM facturas f
          WHERE f.id_pedido = p.id_pedido
      )
    ORDER BY p.id_pedido DESC
";
$resultadoPedidos = $conexion->query($sqlPedidos);

/* FACTURAS GENERADAS: PEDIDOS Y VENTAS DIRECTAS */
$sqlFacturas = "
    SELECT
        f.id_factura,
        f.numero_factura,
        COALESCE(
            NULLIF(f.nombre_cliente, ''),
            NULLIF(c.nombre, ''),
            NULLIF(p.nombre_cliente, ''),
            'Cliente ocasional'
        ) AS cliente,
        COALESCE(p.codigo_pedido, 'Venta directa') AS codigo_pedido,
        f.fecha,
        f.metodo_pago,
        f.total,
        f.estado,
        DATEDIFF(CURDATE(), f.fecha) AS dias_transcurridos
    FROM facturas f
    LEFT JOIN clientes c ON c.id_cliente = f.id_cliente
    LEFT JOIN pedidos p ON p.id_pedido = f.id_pedido
    ORDER BY f.id_factura DESC
";
$resultadoFacturas = $conexion->query($sqlFacturas);

include("../includes/header.php");
?>

<div class="container">
    <main class="contenido">

        <div class="barra-superior">
            <a href="../DASHBOARD/Dashboard.php" class="btn-volver">
                ← Volver al Dashboard
            </a>
        </div>

        <header class="encabezado">
            <h1>Facturación</h1>
            <p>Gestión y control de facturas generadas desde pedidos y ventas directas.</p>
        </header>

        <section class="acciones">
            <a href="GenerarFactura.php" class="btn-accion">
                Facturar Pedido
            </a>
            <a href="../CATALOGOS/Catalogos.php" class="btn-accion">
                Ir a Catálogos
            </a>
            <a href="ConsultarFacturas.php" class="btn-accion">
                Consultar Facturas
            </a>
        </section>

        <?php if ($mensaje !== ""): ?>
            <div id="mensajeFacturacion"
                 class="mensaje-facturacion <?= htmlspecialchars($tipoMensaje, ENT_QUOTES, "UTF-8") ?>"
                 role="<?= $tipoMensaje === "exito" ? "status" : "alert" ?>"
                 aria-live="<?= $tipoMensaje === "exito" ? "polite" : "assertive" ?>">
                <div class="mensaje-icono" aria-hidden="true">
                    <?= $tipoMensaje === "exito" ? "✓" : "!" ?>
                </div>
                <div class="mensaje-contenido">
                    <strong>
                        <?= $tipoMensaje === "exito"
                            ? "Operación completada"
                            : "No se pudo completar la operación" ?>
                    </strong>
                    <p><?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?></p>
                </div>
                <button type="button" class="mensaje-cerrar"
                        aria-label="Cerrar mensaje"
                        onclick="document.getElementById('mensajeFacturacion')?.remove()">
                    &times;
                </button>
            </div>
        <?php endif; ?>

        <section class="cards">
            <div class="card">
                <h2><?= $totalFacturas ?></h2>
                <span>Facturas Totales</span>
            </div>
            <div class="card">
                <h2><?= $facturasMes ?></h2>
                <span>Facturas del Mes</span>
            </div>
            <div class="card">
                <h2><?= $facturasPagadas ?></h2>
                <span>Pagadas</span>
            </div>
            <div class="card">
                <h2><?= $facturasPendientes ?></h2>
                <span>Pendientes</span>
            </div>
        </section>

        <section class="panel">
            <h2>Pedidos Pendientes de Facturar</h2>

            <?php if ($resultadoPedidos && $resultadoPedidos->num_rows > 0): ?>
                <?php while ($pedido = $resultadoPedidos->fetch_assoc()): ?>
                    <div class="pedido">
                        <div>
                            <h3><?= htmlspecialchars($pedido["codigo_pedido"], ENT_QUOTES, "UTF-8") ?></h3>
                            <p>
                                <?= htmlspecialchars($pedido["cliente"], ENT_QUOTES, "UTF-8") ?>
                                |
                                $<?= number_format((float)$pedido["total"], 0, ",", ".") ?>
                            </p>
                        </div>
                        <a href="GenerarFactura.php?id_pedido=<?= (int)$pedido["id_pedido"] ?>"
                           class="btn-accion">
                            Generar Factura
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No hay pedidos pendientes de facturar.</p>
            <?php endif; ?>
        </section>

        <section class="panel">
            <h2>Facturas Generadas</h2>

            <?php if ($resultadoFacturas && $resultadoFacturas->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Cliente</th>
                            <th>Pedido / Origen</th>
                            <th>Fecha</th>
                            <th>Método Pago</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($factura = $resultadoFacturas->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($factura["numero_factura"], ENT_QUOTES, "UTF-8") ?></td>
                                <td><?= htmlspecialchars($factura["cliente"], ENT_QUOTES, "UTF-8") ?></td>
                                <td><?= htmlspecialchars($factura["codigo_pedido"], ENT_QUOTES, "UTF-8") ?></td>
                                <td><?= date("d/m/Y", strtotime($factura["fecha"])) ?></td>
                                <td><?= htmlspecialchars($factura["metodo_pago"], ENT_QUOTES, "UTF-8") ?></td>
                                <td>$<?= number_format((float)$factura["total"], 0, ",", ".") ?></td>
                                <td>
                                    <?php if ($factura["estado"] === "Pagada"): ?>
                                        <span class="pagada">Pagada</span>
                                    <?php elseif ($factura["estado"] === "Pendiente"): ?>
                                        <span class="pendiente">Pendiente</span>
                                    <?php else: ?>
                                        <span><?= htmlspecialchars($factura["estado"], ENT_QUOTES, "UTF-8") ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="VerFactura.php?id_factura=<?= (int)$factura["id_factura"] ?>"
                                       class="ver">
                                        Ver
                                    </a>

                                    <?php if ((int)$factura["dias_transcurridos"] >= 30): ?>
                                        <form action="EliminarFactura.php"
                                              method="POST"
                                              class="form-eliminar-factura"
                                              onsubmit="return confirm('¿Estás seguro de eliminar la factura <?= htmlspecialchars($factura["numero_factura"], ENT_QUOTES, "UTF-8") ?> y sus detalles? Esta acción no se puede deshacer.');">
                                            <input type="hidden" name="id_factura"
                                                   value="<?= (int)$factura["id_factura"] ?>">
                                            <button type="submit" class="eliminar">Eliminar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No hay facturas generadas.</p>
            <?php endif; ?>
        </section>
    </main>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const mensaje = document.getElementById("mensajeFacturacion");
    if (!mensaje) return;

    setTimeout(function() {
        if (!mensaje.isConnected) return;
        mensaje.classList.add("ocultar");
        setTimeout(function() {
            if (mensaje.isConnected) mensaje.remove();
        }, 600);
    }, 5000);
});
</script>

<?php include("../includes/footer.php"); ?>