<?php
$titulo = "VER ORDEN";
$css = "OrdenesProduccion.css";

require_once("../../includes/conexion.php");
require_once("../../includes/header.php");

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

$stmt = $conexion->prepare("
    SELECT
        o.id_orden,
        o.codigo,
        o.fecha,
        o.cantidad,
        o.responsable,
        o.fecha_entrega,
        o.estado,
        o.observaciones,
        c.nombre AS cliente,
        p.nombre AS producto,
        p.presentacion AS presentacion
    FROM ordenes_produccion o
    INNER JOIN clientes c
        ON o.id_cliente = c.id_cliente
    INNER JOIN productos p
        ON o.id_producto = p.id_producto
    WHERE o.id_orden = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$orden = $resultado->fetch_assoc();
$stmt->close();
?>

<main class="contenido">

    <section class="barra-superior">

        <a href="OrdenesProduccion.php" class="btn btn-verde">
            ← Volver a Órdenes de Producción
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </section>

    <header class="encabezado">

        <h1>Detalle de la Orden de Producción</h1>

        <p>
            Consulte la información registrada de la orden de producción.
        </p>

    </header>

    <section class="panel">

        <?php if (!$orden): ?>

            <div class="mensaje-error">
                La orden de producción no fue encontrada.
            </div>

        <?php else: ?>

            <div class="panel-header">

                <div>

                    <h2>Información de la Orden</h2>

                    <p>
                        Datos generales de la orden.
                    </p>

                </div>

            </div>

            <form class="formulario">

                <div class="grupo-formulario">

                    <div class="campo">

                        <label for="codigo">Código</label>

                        <input
                            type="text"
                            id="codigo"
                            value="<?= htmlspecialchars($orden["codigo"]) ?>"
                            readonly
                        >

                    </div>

                    <div class="campo">

                        <label for="fecha">Fecha</label>

                        <input
                            type="date"
                            id="fecha"
                            value="<?= htmlspecialchars($orden["fecha"]) ?>"
                            readonly
                        >

                    </div>

                    <div class="campo">

                        <label for="cliente">Cliente</label>

                        <input
                            type="text"
                            id="cliente"
                            value="<?= htmlspecialchars($orden["cliente"]) ?>"
                            readonly
                        >

                    </div>

                    <div class="campo">

                        <label for="producto">Producto</label>

                        <input
                            type="text"
                            id="producto"
                            value="<?= htmlspecialchars(
                                $orden["producto"] . " — " . $orden["presentacion"]
                            ) ?>"
                            readonly
                        >

                    </div>

                    <div class="campo">

                        <label for="cantidad">Cantidad</label>

                        <input
                            type="number"
                            id="cantidad"
                            value="<?= htmlspecialchars((string)$orden["cantidad"]) ?>"
                            readonly
                        >

                    </div>

                    <div class="campo">

                        <label for="responsable">Responsable</label>

                        <input
                            type="text"
                            id="responsable"
                            value="<?= htmlspecialchars($orden["responsable"]) ?>"
                            readonly
                        >

                    </div>

                    <div class="campo">

                        <label for="entrega">Fecha de Entrega</label>

                        <input
                            type="date"
                            id="entrega"
                            value="<?= htmlspecialchars($orden["fecha_entrega"] ?? "") ?>"
                            readonly
                        >

                    </div>

                    <div class="campo">

                        <label for="estado">Estado</label>

                        <input
                            type="text"
                            id="estado"
                            value="<?= htmlspecialchars($orden["estado"]) ?>"
                            readonly
                        >

                    </div>

                    <div class="campo campo-completo">

                        <label for="observaciones">Observaciones</label>

                        <textarea
                            id="observaciones"
                            rows="5"
                            readonly
                        ><?= htmlspecialchars($orden["observaciones"] ?? "") ?></textarea>

                    </div>

                </div>

                <div class="botones-formulario">

                    <a
                        href="../../REPORTES/PRODUCCION/ReporteProduccion.php"
                        class="btn btn-verde"
                    >
                        ← Volver a Reporte de Producción
                    </a>

                </div>

            </form>

        <?php endif; ?>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>