
<?php
$titulo = "VER ORDEN";
$css = "OrdenesProduccion.css";

require_once("../../includes/conexion.php");

function formatoCantidad($cantidad) {
    if ($cantidad === null || $cantidad === "") {
        return "";
    }

    return rtrim(
        rtrim(number_format((float)$cantidad, 2, ".", ""), "0"),
        "."
    );
}

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

$stmt = $conexion->prepare("
    SELECT
        o.id_orden,
        o.codigo,
        o.destino,
        o.id_pedido,
        o.fecha,
        o.cantidad,
        o.cantidad_fabricada,
        o.responsable,
        o.fecha_entrega,
        o.fecha_finalizacion,
        o.fecha_entrega_real,
        o.estado,
        o.observaciones,
        c.nombre AS cliente,
        pe.codigo_pedido,
        p.nombre AS producto,
        p.presentacion
    FROM ordenes_produccion o
    LEFT JOIN clientes c
        ON o.id_cliente = c.id_cliente
    LEFT JOIN pedidos pe
        ON o.id_pedido = pe.id_pedido
    INNER JOIN productos p
        ON o.id_producto = p.id_producto
    WHERE o.id_orden = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();
$orden = $stmt->get_result()->fetch_assoc();
$stmt->close();

require_once("../../includes/header.php");

$destinoTexto = "Sin clasificar";

if ($orden) {
    if ($orden["destino"] === "PARA_STOCK") {
        $destinoTexto = "Para inventario";
    } elseif ($orden["destino"] === "POR_PEDIDO") {
        $destinoTexto = "Por pedido";
    }
}

$cantidadFabricada = "Pendiente";

if ($orden && $orden["cantidad_fabricada"] !== null) {
    $cantidadFabricada = formatoCantidad($orden["cantidad_fabricada"]);
}
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
        <p>Consulte la información registrada de la orden de producción.</p>
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
                    <p>Datos generales de la orden.</p>
                </div>
            </div>

            <form class="formulario">

                <div class="grupo-formulario">

                    <div class="campo">
                        <label for="codigo">Código</label>
                        <input type="text" id="codigo"
                            value="<?= htmlspecialchars($orden["codigo"]) ?>" readonly>
                    </div>

                    <div class="campo">
                        <label for="destino">Destino</label>
                        <input type="text" id="destino"
                            value="<?= htmlspecialchars($destinoTexto) ?>" readonly>
                    </div>

                    <div class="campo">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha"
                            value="<?= htmlspecialchars($orden["fecha"]) ?>" readonly>
                    </div>

                    <?php if ($orden["destino"] === "POR_PEDIDO"): ?>

                        <div class="campo">
                            <label for="pedido">Pedido relacionado</label>
                            <input type="text" id="pedido"
                                value="<?= htmlspecialchars($orden["codigo_pedido"] ?? "Sin pedido asociado") ?>" readonly>
                        </div>

                        <div class="campo">
                            <label for="cliente">Cliente</label>
                            <input type="text" id="cliente"
                                value="<?= htmlspecialchars($orden["cliente"] ?? "Sin cliente asociado") ?>" readonly>
                        </div>

                    <?php endif; ?>

                    <div class="campo">
                        <label for="producto">Producto</label>
                        <input type="text" id="producto"
                            value="<?= htmlspecialchars($orden["producto"] . " — " . $orden["presentacion"]) ?>" readonly>
                    </div>

                    <div class="campo">
                        <label for="cantidad">Cantidad solicitada</label>
                        <input type="text" id="cantidad"
                            value="<?= htmlspecialchars(formatoCantidad($orden["cantidad"])) ?>" readonly>
                    </div>

                    <div class="campo">
                        <label for="cantidad_fabricada">Cantidad fabricada</label>
                        <input type="text" id="cantidad_fabricada"
                            value="<?= htmlspecialchars($cantidadFabricada) ?>" readonly>
                    </div>

                    <div class="campo">
                        <label for="responsable">Responsable</label>
                        <input type="text" id="responsable"
                            value="<?= htmlspecialchars($orden["responsable"]) ?>" readonly>
                    </div>

                    <div class="campo">
                        <label for="entrega">Fecha de Entrega</label>
                        <input type="date" id="entrega"
                            value="<?= htmlspecialchars($orden["fecha_entrega"] ?? "") ?>" readonly>
                    </div>

                    <div class="campo">
                        <label for="finalizacion">Fecha de Finalización</label>
                        <input type="date" id="finalizacion"
                            value="<?= htmlspecialchars($orden["fecha_finalizacion"] ?? "") ?>" readonly>
                    </div>

                    <div class="campo">
                        <label for="entrega_real">Fecha de Entrega Real</label>
                        <input type="date" id="entrega_real"
                            value="<?= htmlspecialchars($orden["fecha_entrega_real"] ?? "") ?>" readonly>
                    </div>

                    <div class="campo">
                        <label for="estado">Estado</label>
                        <input type="text" id="estado"
                            value="<?= htmlspecialchars($orden["estado"]) ?>" readonly>
                    </div>

                    <div class="campo campo-completo">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" rows="5" readonly><?= htmlspecialchars($orden["observaciones"] ?? "") ?></textarea>
                    </div>

                </div>

                <div class="botones-formulario">
                    <a href="../../REPORTES/PRODUCCION/ReporteProduccion.php"
                        class="btn btn-verde">
                        ← Volver a Reporte de Producción
                    </a>
                </div>

            </form>

        <?php endif; ?>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>
