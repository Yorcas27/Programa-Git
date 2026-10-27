
<?php
require_once("../../includes/conexion.php");

/* OBTENER ID */
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$producto = null;
$presentaciones = [];
$idPrincipal = null;

/* BUSCAR PRODUCTO */
if ($id && $id > 0) {
    $stmt = $conexion->prepare(
        "SELECT
            p.id_producto,
            p.id_producto_principal,
            p.nombre,
            p.presentacion,
            p.descripcion,
            p.precio,
            p.estado,
            p.imagen,
            c.nombre AS categoria,
            pp.nombre AS nombre_principal,
            pp.descripcion AS descripcion_principal,
            pp.imagen AS imagen_principal
        FROM productos p
        INNER JOIN categorias_producto c
            ON p.id_categoria_producto = c.id_categoria_producto
        LEFT JOIN productos_principales pp
            ON p.id_producto_principal = pp.id_producto_principal
        WHERE p.id_producto = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $producto = $resultado->fetch_assoc();
        $idPrincipal = $producto['id_producto_principal'];
    }

    $stmt->close();

    /* CARGAR TODAS LAS PRESENTACIONES */
    if ($producto) {
        if ($idPrincipal !== null) {
            $stmt = $conexion->prepare(
                "SELECT id_producto, presentacion, precio, estado
                 FROM productos
                 WHERE id_producto_principal = ?
                 ORDER BY id_producto ASC"
            );
            $stmt->bind_param("i", $idPrincipal);
        } else {
            $stmt = $conexion->prepare(
                "SELECT id_producto, presentacion, precio, estado
                 FROM productos
                 WHERE id_producto = ?"
            );
            $stmt->bind_param("i", $id);
        }

        $stmt->execute();
        $resultadoPresentaciones = $stmt->get_result();

        while ($fila = $resultadoPresentaciones->fetch_assoc()) {
            $presentaciones[] = $fila;
        }

        $stmt->close();
    }
}

/* TÍTULO */
$titulo = "Ver Producto";
$css = "Productos.css";

include("../../includes/header.php");
?>

<main class="contenido">
    <?php if ($producto): ?>
        <header class="encabezado">
            <h1>Ver Producto</h1>
            <p>Consulte la información del producto y sus presentaciones.</p>
        </header>

        <section class="formulario-contenedor">
            <div class="seccion-formulario">
                <h2>Información del Producto</h2>

                <div class="fila-formulario">
                    <div class="campo-formulario">
                        <label>Nombre del producto</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($producto['nombre_principal'] ?? $producto['nombre']) ?>"
                            readonly
                        >
                    </div>

                    <div class="campo-formulario">
                        <label>Categoría</label>
                        <input
                            type="text"
                            value="<?= htmlspecialchars($producto['categoria']) ?>"
                            readonly
                        >
                    </div>
                </div>

                <div class="campo-formulario">
                    <label>Descripción</label>
                    <textarea rows="4" readonly><?= htmlspecialchars($producto['descripcion_principal'] ?? $producto['descripcion'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="seccion-formulario">
                <h2>Presentaciones y Precios</h2>

                <div class="tabla-contenedor">
                    <table>
                        <thead>
                            <tr>
                                <th>Presentación</th>
                                <th>Precio (COP)</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($presentaciones as $presentacion): ?>
                                <tr>
                                    <td><?= htmlspecialchars($presentacion['presentacion'] ?: 'Única') ?></td>
                                    <td>$ <?= number_format((float)$presentacion['precio'], 0, ',', '.') ?></td>
                                    <td>
                                        <?php if ($presentacion['estado'] === 'activo'): ?>
                                            <span class="estado activo">Activo</span>
                                        <?php else: ?>
                                            <span class="estado">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="seccion-formulario">
                <h2>Imagen del Producto</h2>

                <div class="campo-formulario">
                    <?php
                    $imagen = $producto['imagen_principal'] ?? null;

                    if (empty($imagen)) {
                        $imagen = $producto['imagen'];
                    }
                    ?>

                    <?php if (!empty($imagen)): ?>
                        <img
                            src="/YCL_Solutions/<?= htmlspecialchars($imagen) ?>"
                            alt="<?= htmlspecialchars($producto['nombre_principal'] ?? $producto['nombre']) ?>"
                            class="imagen-producto"
                        >
                    <?php else: ?>
                        <div class="imagen-producto">Sin imagen</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="acciones-formulario">
                <a href="Productos.php" class="btn-volver">
                    ← Volver a Productos
                </a>

                <a
                    href="ProductoForm.php?id=<?= (int)$producto['id_producto'] ?>"
                    class="btn-principal"
                >
                    Editar Producto
                </a>
            </div>
        </section>

    <?php else: ?>
        <header class="encabezado">
            <h1>Producto no encontrado</h1>
            <p>El producto solicitado no existe o ya fue eliminado.</p>
        </header>

        <section class="formulario-contenedor">
            <div class="acciones-formulario">
                <a href="Productos.php" class="btn-volver">
                    ← Volver a Productos
                </a>

                <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-principal">
                    ⌂ Inicio
                </a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include("../../includes/footer.php"); ?>

