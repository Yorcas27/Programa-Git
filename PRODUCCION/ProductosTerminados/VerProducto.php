<?php
$titulo = "VER PRODUCTO";
$css = "ProductosTerminados.css";

require_once("../../includes/conexion.php");

/* FORMATO COLOMBIANO SIN DECIMALES INNECESARIOS */
function formatoCantidad($valor) {
    return rtrim(
        rtrim(number_format((float)$valor, 2, ',', '.'), '0'),
        ','
    );
}

function formatoMoneda($valor) {
    return '$ ' . formatoCantidad($valor);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: ProductosTerminados.php");
    exit;
}

$stmt = $conexion->prepare("
    SELECT
        pt.id_producto_terminado,
        pt.id_orden,
        pt.codigo,
        pt.producto,
        pt.categoria,
        pt.unidad,
        pt.cantidad,
        pt.precio,
        pt.fecha,
        pt.estado,
        pt.descripcion,
        pt.observaciones,
        op.codigo AS codigo_orden,
        op.destino,
        pe.codigo_pedido,
        COALESCE(
            NULLIF(pe.nombre_cliente, ''),
            c.nombre,
            'Sin cliente asociado'
        ) AS cliente,
        COALESCE(
            NULLIF(pe.documento_cliente, ''),
            c.documento,
            ''
        ) AS documento_cliente,
        COALESCE(
            NULLIF(pe.telefono_cliente, ''),
            c.telefono,
            ''
        ) AS telefono_cliente,
        COALESCE(
            NULLIF(pe.correo_cliente, ''),
            c.correo,
            ''
        ) AS correo_cliente
    FROM productos_terminados pt
    LEFT JOIN ordenes_produccion op
        ON pt.id_orden = op.id_orden
    LEFT JOIN pedidos pe
        ON op.id_pedido = pe.id_pedido
    LEFT JOIN clientes c
        ON op.id_cliente = c.id_cliente
    WHERE pt.id_producto_terminado = ?
");

if (!$stmt) {
    error_log("Error al preparar consulta de producto terminado: " . $conexion->error);
    header("Location: ProductosTerminados.php");
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();

$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    header("Location: ProductosTerminados.php");
    exit;
}

require_once("../../includes/header.php");

$destinoTexto = "Registro independiente";
$cantidadTexto = "Cantidad registrada";

if ($producto["destino"] === "PARA_STOCK") {
    $destinoTexto = "Para inventario";
    $cantidadTexto = "Cantidad disponible";
} elseif ($producto["destino"] === "POR_PEDIDO") {
    $destinoTexto = "Por pedido";
    $cantidadTexto = "Cantidad fabricada";
}
?>

<main class="contenido">

    <section class="barra-superior">

        <a href="ProductosTerminados.php" class="btn btn-principal">
            ← Volver a Productos Terminados
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </section>

    <header class="encabezado">
        <h1>Detalle del Producto Terminado</h1>
        <p>Consulte la información registrada del producto terminado.</p>
    </header>

    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Información del Producto Terminado</h2>
                <p>Datos generales y origen del producto registrado.</p>
            </div>
        </div>

        <form class="formulario">

            <div class="grupo-formulario">

                <div class="campo">
                    <label for="codigo">Código</label>
                    <input
                        type="text"
                        id="codigo"
                        value="<?= htmlspecialchars($producto['codigo']) ?>"
                        readonly
                    >
                </div>

                <div class="campo">
                    <label for="destino">Destino de producción</label>
                    <input
                        type="text"
                        id="destino"
                        value="<?= htmlspecialchars($destinoTexto) ?>"
                        readonly
                    >
                </div>

                <?php if (!empty($producto["codigo_orden"])): ?>
                    <div class="campo">
                        <label for="orden">Orden de producción</label>
                        <input
                            type="text"
                            id="orden"
                            value="<?= htmlspecialchars($producto["codigo_orden"]) ?>"
                            readonly
                        >
                    </div>
                <?php endif; ?>

                <?php if ($producto["destino"] === "POR_PEDIDO"): ?>

                    <div class="campo">
                        <label for="pedido">Pedido relacionado</label>
                        <input
                            type="text"
                            id="pedido"
                            value="<?= htmlspecialchars($producto["codigo_pedido"] ?? "Sin pedido asociado") ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label for="cliente">Cliente</label>
                        <input
                            type="text"
                            id="cliente"
                            value="<?= htmlspecialchars($producto["cliente"]) ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label for="documento_cliente">Documento</label>
                        <input
                            type="text"
                            id="documento_cliente"
                            value="<?= htmlspecialchars($producto["documento_cliente"]) ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label for="telefono_cliente">Teléfono</label>
                        <input
                            type="text"
                            id="telefono_cliente"
                            value="<?= htmlspecialchars($producto["telefono_cliente"]) ?>"
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label for="correo_cliente">Correo electrónico</label>
                        <input
                            type="text"
                            id="correo_cliente"
                            value="<?= htmlspecialchars($producto["correo_cliente"]) ?>"
                            readonly
                        >
                    </div>

                <?php endif; ?>

                <div class="campo">
                    <label for="producto">Nombre del Producto</label>
                    <input
                        type="text"
                        id="producto"
                        value="<?= htmlspecialchars($producto['producto']) ?>"
                        readonly
                    >
                </div>

                <div class="campo">
                    <label for="categoria">Categoría</label>
                    <input
                        type="text"
                        id="categoria"
                        value="<?= htmlspecialchars($producto['categoria']) ?>"
                        readonly
                    >
                </div>

                <div class="campo">
                    <label for="unidad">Unidad</label>
                    <input
                        type="text"
                        id="unidad"
                        value="<?= htmlspecialchars($producto['unidad']) ?>"
                        readonly
                    >
                </div>

                <div class="campo">
                    <label for="cantidad">
                        <?= htmlspecialchars($cantidadTexto) ?>
                    </label>
                    <input
                        type="text"
                        id="cantidad"
                        value="<?= htmlspecialchars(formatoCantidad($producto['cantidad'])) ?>"
                        readonly
                    >
                </div>

                <div class="campo">
                    <label for="precio">Precio registrado</label>
                    <input
                        type="text"
                        id="precio"
                        value="<?= htmlspecialchars(formatoMoneda($producto['precio'])) ?>"
                        readonly
                    >
                </div>

                <div class="campo">
                    <label for="fecha">Fecha de Elaboración</label>
                    <input
                        type="date"
                        id="fecha"
                        value="<?= htmlspecialchars($producto['fecha']) ?>"
                        readonly
                    >
                </div>

                <div class="campo">
                    <label for="estado">Estado</label>
                    <input
                        type="text"
                        id="estado"
                        value="<?= htmlspecialchars($producto['estado']) ?>"
                        readonly
                    >
                </div>

                <div class="campo campo-completo">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" rows="4" readonly><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="campo campo-completo">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" rows="4" readonly><?= htmlspecialchars($producto['observaciones'] ?? '') ?></textarea>
                </div>

            </div>

            <div class="botones-formulario">
                <a href="ProductosTerminados.php" class="btn btn-principal">
                    Volver
                </a>
            </div>

        </form>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>