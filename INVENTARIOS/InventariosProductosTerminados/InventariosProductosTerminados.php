<?php
$titulo = "Inventario de Productos Terminados";
$css = "InventariosProductosTerminados.css";

require_once("../../includes/conexion.php");
require_once("../../includes/header.php");

/* FORMATO DE CANTIDADES */
function formatoCantidadPT($cantidad) {
    return rtrim(
        rtrim(number_format((float)$cantidad, 2, ',', '.'), '0'),
        ','
    );
}

/* INDICADORES */
$resultado = $conexion->query("
    SELECT
        COUNT(*) AS total_productos,
        COALESCE(SUM(i.stock), 0) AS total_stock,
        SUM(
            CASE
                WHEN i.stock > 0 AND i.stock <= i.stock_minimo
                THEN 1 ELSE 0
            END
        ) AS stock_bajo,
        SUM(
            CASE
                WHEN i.stock = 0
                THEN 1 ELSE 0
            END
        ) AS agotados
    FROM productos p
    LEFT JOIN inventario_productos_terminados i
        ON p.id_producto = i.id_producto
    WHERE LOWER(p.estado) = 'activo'
");

$indicadores = $resultado->fetch_assoc();

$total_productos = $indicadores['total_productos'] ?? 0;
$total_stock = $indicadores['total_stock'] ?? 0;
$stock_bajo = $indicadores['stock_bajo'] ?? 0;
$agotados = $indicadores['agotados'] ?? 0;

/* ÚLTIMO MOVIMIENTO */
$resultado = $conexion->query("
    SELECT MAX(fecha) AS ultima_fecha
    FROM movimientos_productos_terminados
");

$ultima_fecha = $resultado->fetch_assoc()['ultima_fecha'] ?? null;

$ultima_actualizacion = $ultima_fecha
    ? date('d/m/Y', strtotime($ultima_fecha))
    : 'No registrada';

/* PRODUCTOS Y EXISTENCIAS */
$resultado = $conexion->query("
    SELECT
        p.id_producto,
        p.nombre,
        p.presentacion,
        cp.nombre AS categoria,
        COALESCE(i.stock, 0) AS stock,
        COALESCE(i.stock_minimo, 0) AS stock_minimo
    FROM productos p
    LEFT JOIN categorias_producto cp
        ON p.id_categoria_producto = cp.id_categoria_producto
    LEFT JOIN inventario_productos_terminados i
        ON p.id_producto = i.id_producto
    WHERE LOWER(p.estado) = 'activo'
    ORDER BY p.nombre ASC, p.presentacion ASC
");
?>

<main class="contenido">

    <div class="volver">
        <a href="../Inventarios/Inventarios.php">
            ← Volver a Inventarios
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>
    </div>

    <header class="encabezado">
        <h1>Inventario de Productos Terminados</h1>
        <p>
            Consulta y controla las existencias de los productos
            terminados disponibles para la venta.
        </p>
    </header>

    <!-- INDICADORES -->

    <section class="cards">

        <div class="card productos">
            <h2><?= number_format($total_productos, 0, ',', '.') ?></h2>
            <span>Productos Registrados</span>
        </div>

        <div class="card stock">
            <h2><?= htmlspecialchars(formatoCantidadPT($total_stock)) ?></h2>
            <span>Unidades Disponibles</span>
        </div>

        <div class="card bajo-stock">
            <h2><?= number_format($stock_bajo, 0, ',', '.') ?></h2>
            <span>Productos con Stock Bajo</span>
        </div>

        <div class="card agotados">
            <h2><?= number_format($agotados, 0, ',', '.') ?></h2>
            <span>Productos Agotados</span>
        </div>

    </section>

    <!-- LISTADO -->

    <section class="panel">

        <div class="panel-header">

            <div>
                <h2>Existencias por Producto</h2>
                <p>
                    Cada presentación conserva su propio saldo de inventario.
                </p>
            </div>

            <div class="acciones">

                <input
                    type="text"
                    class="buscar"
                    id="buscarProductos"
                    placeholder="Buscar producto..."
                >

                <button
                    type="button"
                    class="btn-buscar"
                    onclick="buscarProductos()"
                >
                    Buscar
                </button>

                <button
                    type="button"
                    class="btn-actualizar"
                    onclick="actualizarInventarioPT()"
                >
                    ↻ Actualizar
                </button>

            </div>

        </div>

        <div class="tabla-contenedor">

            <table id="tablaProductos">

                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Presentación</th>
                        <th>Categoría</th>
                        <th>Stock Actual</th>
                        <th>Stock Mínimo</th>
                        <th>Disponibilidad</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($resultado && $resultado->num_rows > 0): ?>

                        <?php while ($producto = $resultado->fetch_assoc()): ?>

                            <?php
                            $stock = (float)$producto['stock'];
                            $minimo = (float)$producto['stock_minimo'];

                            if ($stock <= 0) {
                                $estado = 'Agotado';
                                $clase = 'agotado';
                            } elseif ($stock <= $minimo) {
                                $estado = 'Stock Bajo';
                                $clase = 'bajo';
                            } else {
                                $estado = 'Disponible';
                                $clase = 'disponible';
                            }
                            ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars($producto['nombre']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($producto['presentacion'] ?? 'No especificada') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($producto['categoria'] ?? 'Sin categoría') ?>
                                </td>

                                <td class="stock-actual">
                                    <?= htmlspecialchars(formatoCantidadPT($stock)) ?>
                                </td>

                                <td class="stock-minimo">
                                    <?= htmlspecialchars(formatoCantidadPT($minimo)) ?>
                                </td>

                                <td>
                                    <span class="<?= $clase ?>">
                                        <?= $estado ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6">
                                No hay productos activos registrados.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="ultima-actualizacion">
            Último movimiento registrado:
            <strong><?= htmlspecialchars($ultima_actualizacion) ?></strong>
        </div>

    </section>

</main>

<script>
/* BUSCAR PRODUCTOS */
function buscarProductos() {
    const texto = document
        .getElementById("buscarProductos")
        .value
        .toLowerCase()
        .trim();

    const filas = document.querySelectorAll(
        "#tablaProductos tbody tr"
    );

    filas.forEach(function(fila) {
        const contenido = fila.textContent.toLowerCase();
        fila.style.display = contenido.includes(texto) ? "" : "none";
    });
}

/* ACTUALIZAR LISTADO */
function actualizarInventarioPT() {
    window.location.reload();
}

/* BÚSQUEDA AUTOMÁTICA */
document
    .getElementById("buscarProductos")
    .addEventListener("input", buscarProductos);

/* BUSCAR CON ENTER */
document
    .getElementById("buscarProductos")
    .addEventListener("keyup", function(event) {
        if (event.key === "Enter") {
            buscarProductos();
        }
    });
</script>

<?php
include("../../includes/footer.php");
?>