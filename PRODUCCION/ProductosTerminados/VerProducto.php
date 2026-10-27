<?php

$titulo = "VER PRODUCTO";
$css = "ProductosTerminados.css";

require_once("../../includes/conexion.php");
require_once("../../includes/header.php");

/* FORMATO COLOMBIANO SIN DECIMALES INNECESARIOS */
function formatoCantidad($valor) {
    $valor = number_format((float)$valor, 2, ',', '.');
    $valor = rtrim(rtrim($valor, '0'), ',');

    return $valor;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT
            id_producto_terminado,
            codigo,
            producto,
            categoria,
            unidad,
            cantidad,
            precio,
            fecha,
            estado,
            descripcion,
            observaciones
        FROM productos_terminados
        WHERE id_producto_terminado = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$producto = $resultado->fetch_assoc();

$stmt->close();

if (!$producto) {
    header("Location: ProductosTerminados.php");
    exit;
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

                <p>Datos generales del producto registrado.</p>

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

                    <label for="cantidad">Cantidad Disponible</label>

                    <input
                        type="text"
                        id="cantidad"
                        value="<?= htmlspecialchars(formatoCantidad($producto['cantidad'])) ?>"
                        readonly
                    >

                </div>

                <div class="campo">

                    <label for="precio">Precio Venta</label>

                    <input
                        type="text"
                        id="precio"
                        value="<?= htmlspecialchars(formatoCantidad($producto['precio'])) ?>"
                        readonly
                    >

                </div>

                <div class="campo">

                    <label for="fecha">Fecha Elaboración</label>

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

                    <textarea
                        id="descripcion"
                        rows="4"
                        readonly
                    ><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>

                </div>

                <div class="campo campo-completo">

                    <label for="observaciones">Observaciones</label>

                    <textarea
                        id="observaciones"
                        rows="4"
                        readonly
                    ><?= htmlspecialchars($producto['observaciones'] ?? '') ?></textarea>

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