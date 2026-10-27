<?php
$titulo = "Ver Producción";
$css = "HistorialProduccion.css";

require_once("../../includes/conexion.php");
require_once("../../includes/header.php");

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$produccion = null;

if ($id > 0) {
    $stmt = $conexion->prepare("
        SELECT
            op.id_orden,
            op.codigo,
            op.fecha,
            op.id_cliente,
            op.id_producto,
            op.cantidad,
            op.responsable,
            op.fecha_entrega,
            op.estado,
            op.observaciones,
            p.nombre AS nombre_producto,
            c.nombre AS nombre_cliente
        FROM ordenes_produccion op
        LEFT JOIN productos p
            ON op.id_producto = p.id_producto
        LEFT JOIN clientes c
            ON op.id_cliente = c.id_cliente
        WHERE op.id_orden = ?
          AND op.estado = 'Finalizada'
    ");

    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $produccion = $resultado->fetch_assoc();
        $stmt->close();
    }
}
?>

<main class="contenido">

    <section class="barra-superior">
        <a href="Historial.php" class="btn btn-principal">
            ← Volver a Historial de Producción
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>
    </section>

    <header class="encabezado">
        <h1>Detalle de Producción</h1>
        <p>Consulte la información registrada de la producción finalizada.</p>
    </header>

    <section class="panel">

        <?php if ($produccion): ?>

            <div class="panel-header">
                <div>
                    <h2>Información de Producción</h2>
                    <p>Datos generales de la orden finalizada.</p>
                </div>
            </div>

            <form class="formulario">

                <div class="grupo-formulario">

                    <div class="campo">
                        <label for="codigo">Código de Orden</label>
                        <input
                            type="text"
                            id="codigo"
                            value="<?= htmlspecialchars($produccion["codigo"] ?? "") ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label for="fecha">Fecha de Producción</label>
                        <input
                            type="text"
                            id="fecha"
                            value="<?= !empty($produccion["fecha"]) ? date("d/m/Y", strtotime($produccion["fecha"])) : "—" ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label for="cliente">Cliente</label>
                        <input
                            type="text"
                            id="cliente"
                            value="<?= htmlspecialchars($produccion["nombre_cliente"] ?? "No registrado") ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label for="producto">Producto</label>
                        <input
                            type="text"
                            id="producto"
                            value="<?= htmlspecialchars($produccion["nombre_producto"] ?? "No disponible") ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label for="cantidad">Cantidad Producida</label>
                        <input
                            type="text"
                            id="cantidad"
                            value="<?= number_format((float)$produccion["cantidad"], 2, ",", ".") ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label for="responsable">Responsable</label>
                        <input
                            type="text"
                            id="responsable"
                            value="<?= htmlspecialchars($produccion["responsable"] ?? "—") ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label for="fecha_entrega">Fecha de Entrega</label>
                        <input
                            type="text"
                            id="fecha_entrega"
                            value="<?= !empty($produccion["fecha_entrega"]) ? date("d/m/Y", strtotime($produccion["fecha_entrega"])) : "—" ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label for="estado">Estado</label>
                        <input
                            type="text"
                            id="estado"
                            value="<?= htmlspecialchars($produccion["estado"]) ?>"
                            readonly
                        >
                    </div>

                    <div class="campo campo-completo">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" rows="4" readonly><?= htmlspecialchars($produccion["observaciones"] ?? "") ?></textarea>
                    </div>

                </div>

                <div class="botones-formulario">
                    <a href="Historial.php" class="btn btn-principal">
                        Volver al Historial
                    </a>
                </div>

            </form>

        <?php else: ?>

            <div class="panel-header">
                <div>
                    <h2>Producción no encontrada</h2>
                    <p>La orden no existe o no está finalizada.</p>
                </div>
            </div>

            <div class="botones-formulario">
                <a href="Historial.php" class="btn btn-principal">
                    Volver al Historial
                </a>
            </div>

        <?php endif; ?>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>