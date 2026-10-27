
<?php

/* Seguridad */
include("../includes/sesion.php");

$titulo = "Dashboard";
$css = "Dashboard.css";

require_once("../includes/config.php");
require_once("../includes/conexion.php");

include("../includes/header.php");


/* Pedidos pendientes */
$sqlPedidos = "SELECT COUNT(*) AS total
               FROM pedidos
               WHERE estado = 'Pendiente'";

$resultadoPedidos = $conexion->query($sqlPedidos);
$pedidosPendientes = (int) $resultadoPedidos->fetch_assoc()['total'];


/* Órdenes de producción activas */
$sqlProduccion = "SELECT COUNT(*) AS total
                  FROM ordenes_produccion
                  WHERE estado IN ('Pendiente', 'En Producción')";

$resultadoProduccion = $conexion->query($sqlProduccion);
$produccionProceso = (int) $resultadoProduccion->fetch_assoc()['total'];


/* Entregas para hoy */
$sqlEntregas = "SELECT COUNT(*) AS total
                FROM pedidos
                WHERE fecha_entrega = CURDATE()
                AND estado NOT IN ('Entregado', 'Cancelado')";

$resultadoEntregas = $conexion->query($sqlEntregas);
$entregasHoy = (int) $resultadoEntregas->fetch_assoc()['total'];


/* Insumos con stock bajo */
$sqlInventario = "SELECT COUNT(*) AS total
                  FROM insumos
                  WHERE estado = 'Bajo Stock'";

$resultadoInventario = $conexion->query($sqlInventario);
$stockBajo = (int) $resultadoInventario->fetch_assoc()['total'];


/* Actividad del mes: pedidos */
$sqlActividadPedidos = "SELECT COUNT(*) AS total
                        FROM pedidos
                        WHERE fecha >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                        AND fecha < DATE_ADD(
                            DATE_FORMAT(CURDATE(), '%Y-%m-01'),
                            INTERVAL 1 MONTH
                        )";

$resultadoActividadPedidos = $conexion->query($sqlActividadPedidos);
$actividadPedidos = (int) $resultadoActividadPedidos->fetch_assoc()['total'];


/* Actividad del mes: órdenes de producción */
$sqlActividadProduccion = "SELECT COUNT(*) AS total
                           FROM ordenes_produccion
                           WHERE fecha >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                           AND fecha < DATE_ADD(
                               DATE_FORMAT(CURDATE(), '%Y-%m-01'),
                               INTERVAL 1 MONTH
                           )
                           AND estado <> 'Cancelado'";

$resultadoActividadProduccion = $conexion->query($sqlActividadProduccion);
$actividadProduccion = (int) $resultadoActividadProduccion->fetch_assoc()['total'];


/* Actividad del mes: entregas */
$sqlActividadEntregas = "SELECT COUNT(*) AS total
                         FROM pedidos
                         WHERE estado = 'Entregado'
                         AND fecha_entrega >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                         AND fecha_entrega < DATE_ADD(
                             DATE_FORMAT(CURDATE(), '%Y-%m-01'),
                             INTERVAL 1 MONTH
                         )";

$resultadoActividadEntregas = $conexion->query($sqlActividadEntregas);
$actividadEntregas = (int) $resultadoActividadEntregas->fetch_assoc()['total'];


/* Ventas facturadas de la semana: lunes a domingo */
$sqlVentasSemana = "SELECT COALESCE(SUM(total), 0) AS total
                    FROM facturas
                    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
                    AND fecha < DATE_ADD(
                        DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY),
                        INTERVAL 7 DAY
                    )
                    AND estado <> 'Anulada'";

$resultadoVentasSemana = $conexion->query($sqlVentasSemana);
$ventasSemana = (float) $resultadoVentasSemana->fetch_assoc()['total'];


/* Próximos pedidos */
$sqlProximos = "SELECT
                    p.codigo_pedido,
                    COALESCE(c.nombre, p.nombre_cliente, 'Cliente ocasional') AS cliente,
                    p.fecha_entrega,
                    p.estado
                FROM pedidos p
                LEFT JOIN clientes c
                    ON p.id_cliente = c.id_cliente
                WHERE p.fecha_entrega IS NOT NULL
                AND p.estado NOT IN ('Entregado', 'Cancelado')
                ORDER BY p.fecha_entrega ASC
                LIMIT 5";

$resultadoProximos = $conexion->query($sqlProximos);


/* Producción programada para hoy */
$sqlProduccionHoy = "SELECT
                         op.codigo,
                         pr.nombre AS producto,
                         op.cantidad
                     FROM ordenes_produccion op
                     INNER JOIN productos pr
                         ON op.id_producto = pr.id_producto
                     WHERE op.fecha_entrega = CURDATE()
                     AND op.estado IN ('Pendiente', 'En Producción')
                     ORDER BY op.fecha ASC
                     LIMIT 10";

$resultadoProduccionHoy = $conexion->query($sqlProduccionHoy);

?>

<div class="container">

    <?php include("../includes/sidebar.php"); ?>

    <main class="contenido">

        <header class="encabezado">
            <div class="titulo-dashboard">
                <h1>Dashboard</h1>
                <p>Resumen general del negocio</p>
            </div>
        </header>

        <!-- Indicadores -->

        <section class="cards">

            <div class="card pendientes">
                <p class="titulo-card">Pedidos</p>
                <span>Pendientes</span>
                <h2><?= str_pad($pedidosPendientes, 2, '0', STR_PAD_LEFT) ?></h2>
            </div>

            <div class="card produccion">
                <p class="titulo-card">Producción</p>
                <span>Órdenes activas</span>
                <h2><?= str_pad($produccionProceso, 2, '0', STR_PAD_LEFT) ?></h2>
            </div>

            <div class="card entregas">
                <p class="titulo-card">Entregas</p>
                <span>Para hoy</span>
                <h2><?= str_pad($entregasHoy, 2, '0', STR_PAD_LEFT) ?></h2>
            </div>

            <div class="card inventario">
                <p class="titulo-card">Inventario</p>
                <span>Stock bajo</span>
                <h2><?= str_pad($stockBajo, 2, '0', STR_PAD_LEFT) ?></h2>
            </div>

        </section>

        <!-- Paneles -->

        <section class="paneles">

            <!-- Próximos pedidos -->

            <section class="panel">

                <div class="panel-header">
                    <h2>Próximos Pedidos</h2>
                </div>

                <div class="tabla-contenedor">

                    <table>
                        <thead>
                            <tr>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Entrega</th>
                                <th>Estado</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if ($resultadoProximos && $resultadoProximos->num_rows > 0): ?>

                                <?php while ($pedido = $resultadoProximos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pedido['codigo_pedido']) ?></td>
                                        <td><?= htmlspecialchars($pedido['cliente']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($pedido['fecha_entrega'])) ?></td>
                                        <td>
                                            <?php if ($pedido['estado'] === 'Pendiente'): ?>
                                                <span class="estado pendiente">Pendiente</span>
                                            <?php elseif ($pedido['estado'] === 'En Producción'): ?>
                                                <span class="estado en-produccion">En Producción</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="4" class="sin-registros">
                                        No hay próximos pedidos registrados.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>
                    </table>

                </div>

            </section>


            <!-- Actividad del mes y ventas semanales -->

            <section class="panel panel-grafica">

                <div class="panel-header">
                    <h2>Actividad del Mes</h2>
                </div>

                <div class="grafica-vertical">

                    <div class="barra-item">
                        <div class="barra-contenedor">
                            <div
                                class="barra barra-pedidos"
                                style="height: <?= min(max($actividadPedidos * 8, 15), 180) ?>px;"
                            >
                                <span><?= $actividadPedidos ?></span>
                            </div>
                        </div>
                        <p>Pedidos</p>
                    </div>

                    <div class="barra-item">
                        <div class="barra-contenedor">
                            <div
                                class="barra barra-produccion"
                                style="height: <?= min(max($actividadProduccion * 8, 15), 180) ?>px;"
                            >
                                <span><?= $actividadProduccion ?></span>
                            </div>
                        </div>
                        <p>Producción</p>
                    </div>

                    <div class="barra-item">
                        <div class="barra-contenedor">
                            <div
                                class="barra barra-entregas"
                                style="height: <?= min(max($actividadEntregas * 8, 15), 180) ?>px;"
                            >
                                <span><?= $actividadEntregas ?></span>
                            </div>
                        </div>
                        <p>Entregas</p>
                    </div>

                    <div class="barra-item">
                        <div class="barra-contenedor">
                            <div
                                class="barra barra-facturacion"
                                style="height: <?= min(max(($ventasSemana / 10000), 15), 180) ?>px;"
                            >
                                <span>$<?= number_format($ventasSemana, 0, ',', '.') ?></span>
                            </div>
                        </div>
                        <p>Ventas semana</p>
                    </div>

                </div>

                <p class="texto-grafica">
                    Pedidos y órdenes de producción del mes actual, entregas del mes y ventas facturadas de lunes a domingo.
                </p>

            </section>


            <!-- Producción de hoy -->

            <section class="panel">

                <div class="panel-header">
                    <h2>Producción de Hoy</h2>
                </div>

                <ul class="lista-produccion">

                    <?php if ($resultadoProduccionHoy && $resultadoProduccionHoy->num_rows > 0): ?>

                        <?php while ($produccion = $resultadoProduccionHoy->fetch_assoc()): ?>
                            <li>
                                <span class="producto-produccion">
                                    <?= htmlspecialchars($produccion['producto']) ?>
                                </span>
                                <span class="cantidad-produccion">
                                    x<?= (int) $produccion['cantidad'] ?>
                                </span>
                            </li>
                        <?php endwhile; ?>

                    <?php else: ?>

                        <li class="sin-produccion">
                            No hay producción programada para hoy.
                        </li>

                    <?php endif; ?>

                </ul>

            </section>

        </section>

    </main>

</div>

<?php include("../includes/footer.php"); ?>
