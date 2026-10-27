
<?php
$titulo = "Productos";
$css = "Productos.css";

include("../../includes/config.php");
include("../../includes/header.php");
require_once("../../includes/conexion.php");

/* PRODUCTOS PRINCIPALES Y PRODUCTOS ANTIGUOS */
$sql = "SELECT *
        FROM (
            SELECT
                MIN(p.id_producto) AS id_producto,
                pp.id_producto_principal,
                pp.nombre,
                GROUP_CONCAT(
                    DISTINCT p.presentacion
                    ORDER BY p.id_producto
                    SEPARATOR ', '
                ) AS presentacion,
                pp.descripcion,
                MIN(p.precio) AS precio_min,
                MAX(p.precio) AS precio_max,
                CASE
                    WHEN SUM(p.estado = 'activo') > 0 THEN 'activo'
                    ELSE 'inactivo'
                END AS estado,
                COALESCE(pp.imagen, MAX(p.imagen)) AS imagen,
                cp.nombre AS categoria,
                COUNT(p.id_producto) AS cantidad_presentaciones
            FROM productos_principales pp
            INNER JOIN categorias_producto cp
                ON pp.id_categoria_producto = cp.id_categoria_producto
            INNER JOIN productos p
                ON p.id_producto_principal = pp.id_producto_principal
            GROUP BY
                pp.id_producto_principal,
                pp.nombre,
                pp.descripcion,
                pp.imagen,
                cp.nombre

            UNION ALL

            SELECT
                p.id_producto,
                NULL AS id_producto_principal,
                p.nombre,
                p.presentacion,
                p.descripcion,
                p.precio AS precio_min,
                p.precio AS precio_max,
                p.estado,
                p.imagen,
                cp.nombre AS categoria,
                1 AS cantidad_presentaciones
            FROM productos p
            INNER JOIN categorias_producto cp
                ON p.id_categoria_producto = cp.id_categoria_producto
            WHERE p.id_producto_principal IS NULL
        ) AS lista_productos
        ORDER BY id_producto DESC";

$resultado = $conexion->query($sql);

/* CATEGORÍAS */
$sql_categorias = "SELECT
                        id_categoria_producto,
                        nombre
                    FROM categorias_producto
                    WHERE estado = 'Activa'
                    ORDER BY nombre";

$categorias = $conexion->query($sql_categorias);
?>

<main class="contenido">
    <section class="barra-superior">
        <a href="../Catalogo/Catalogo.php" class="btn-volver">
            ← Volver a Catálogo
        </a>
    </section>

    <header class="encabezado">
        <h1>Productos</h1>
        <p>Administre los productos disponibles en el catálogo de venta.</p>
    </header>

    <section class="filtros">
        <div class="campo-filtro campo-categoria">
            <label for="categoria">Categoría</label>
            <select id="categoria">
                <option value="">Todas las categorías</option>
                <?php if ($categorias): ?>
                    <?php while ($categoria = $categorias->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($categoria['nombre']) ?>">
                            <?= htmlspecialchars($categoria['nombre']) ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="campo-filtro campo-busqueda">
            <label for="buscar">Buscar producto</label>
            <input
                type="text"
                id="buscar"
                placeholder="Nombre o presentación..."
            >
        </div>

        <div class="acciones-filtros">
            <button
                type="button"
                class="btn-buscar"
                onclick="filtrarProductos()"
            >Buscar</button>

            <a href="Productos.php" class="btn-refrescar">Refrescar</a>

            <a href="ProductoForm.php" class="btn-nuevo-producto">
                + Nuevo Producto
            </a>
        </div>
    </section>

    <section class="tabla-contenedor">
        <table id="tablaProductos">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Producto</th>
                    <th>Presentación</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <?php while ($producto = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if (!empty($producto['imagen'])): ?>
                                    <img
                                        src="/YCL_Solutions/<?= htmlspecialchars($producto['imagen']) ?>"
                                        alt="<?= htmlspecialchars($producto['nombre']) ?>"
                                        class="imagen-producto"
                                    >
                                <?php else: ?>
                                    <div class="imagen-producto">Sin imagen</div>
                                <?php endif; ?>
                            </td>

                            <td class="celda-producto">
                                <div class="nombre-producto">
                                    <?= htmlspecialchars($producto['nombre']) ?>
                                </div>
                            </td>

                            <td class="celda-presentacion">
                                <?= htmlspecialchars($producto['presentacion'] ?? 'Única') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($producto['categoria']) ?>
                            </td>

                            <td>
                                <?php if ((float)$producto['precio_min'] === (float)$producto['precio_max']): ?>
                                    $<?= number_format((float)$producto['precio_min'], 0, ',', '.') ?>
                                <?php else: ?>
                                    $<?= number_format((float)$producto['precio_min'], 0, ',', '.') ?>
                                    -
                                    $<?= number_format((float)$producto['precio_max'], 0, ',', '.') ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($producto['estado'] === 'activo'): ?>
                                    <span class="estado activo">Activo</span>
                                <?php else: ?>
                                    <span class="estado">Inactivo</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <a
                                    href="VerProducto.php?id=<?= (int)$producto['id_producto'] ?>"
                                    class="btn-ver"
                                >Ver</a>

                                <a
                                    href="ProductoForm.php?id=<?= (int)$producto['id_producto'] ?>"
                                    class="btn-editar"
                                >Editar</a>

                                <?php if (empty($producto['id_producto_principal'])): ?>
                                    <a
                                        href="EliminarProducto.php?id=<?= (int)$producto['id_producto'] ?>"
                                        class="btn-eliminar"
                                        onclick="return confirm('¿Está seguro de eliminar este producto?');"
                                    >Eliminar</a>
                                <?php else: ?>
                                    <span
                                        class="btn-eliminar"
                                        title="La eliminación conjunta se habilitará al adaptar el proceso de eliminación."
                                        style="opacity:.55;cursor:not-allowed;"
                                    >Eliminar</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">No hay productos registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>

<script>
const buscar = document.getElementById("buscar");
const categoria = document.getElementById("categoria");
const filas = document.querySelectorAll("#tablaProductos tbody tr");

function filtrarProductos() {
    const texto = buscar.value.toLowerCase().trim();
    const categoriaSeleccionada = categoria.value.toLowerCase().trim();

    filas.forEach(function(fila) {
        if (fila.cells.length < 7) return;

        const nombre = fila.cells[1].textContent.toLowerCase().trim();
        const presentacion = fila.cells[2].textContent.toLowerCase().trim();
        const categoriaProducto = fila.cells[3].textContent.toLowerCase().trim();

        const coincideTexto =
            nombre.includes(texto) || presentacion.includes(texto);

        const coincideCategoria =
            categoriaSeleccionada === "" ||
            categoriaProducto === categoriaSeleccionada;

        fila.style.display =
            coincideTexto && coincideCategoria ? "" : "none";
    });
}

buscar.addEventListener("input", filtrarProductos);
categoria.addEventListener("change", filtrarProductos);
</script>

<?php include("../../includes/footer.php"); ?>

