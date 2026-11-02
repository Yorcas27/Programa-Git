
<?php
$titulo = "Inventario General";
$css = "InventarioGeneral.css";

require_once("../../includes/conexion.php");
require_once("../../includes/header.php");

/* FORMATO DE CANTIDADES */
function formatoCantidad($cantidad) {
    return rtrim(
        rtrim(number_format((float)$cantidad, 2, ',', '.'), '0'),
        ','
    );
}

/* INDICADORES DE INSUMOS */
$resultado = $conexion->query("
    SELECT
        COUNT(*) AS total_insumos,
        COALESCE(SUM(stock), 0) AS total_stock
    FROM insumos
");
$datos_insumos = $resultado->fetch_assoc();

$total_insumos = $datos_insumos['total_insumos'] ?? 0;
$total_stock_insumos = $datos_insumos['total_stock'] ?? 0;

$resultado = $conexion->query("
    SELECT COUNT(*) AS total
    FROM categorias
");
$total_categorias = $resultado->fetch_assoc()['total'] ?? 0;

/* INDICADORES DE PRODUCTOS TERMINADOS */
$resultado = $conexion->query("
    SELECT
        COUNT(p.id_producto) AS total_productos,
        COALESCE(SUM(i.stock), 0) AS total_stock
    FROM productos p
    LEFT JOIN inventario_productos_terminados i
        ON p.id_producto = i.id_producto
    WHERE LOWER(p.estado) = 'activo'
");
$datos_productos = $resultado->fetch_assoc();

$total_productos = $datos_productos['total_productos'] ?? 0;
$total_stock_productos = $datos_productos['total_stock'] ?? 0;

/* ÚLTIMA ACTUALIZACIÓN */
$resultado = $conexion->query("
    SELECT MAX(fecha) AS ultima_fecha
    FROM movimientos
");
$fecha_insumos = $resultado->fetch_assoc()['ultima_fecha'] ?? null;

$resultado = $conexion->query("
    SELECT MAX(fecha) AS ultima_fecha
    FROM movimientos_productos_terminados
");
$fecha_productos = $resultado->fetch_assoc()['ultima_fecha'] ?? null;

$fechas = array_filter([$fecha_insumos, $fecha_productos]);

$ultima_fecha = !empty($fechas) ? max($fechas) : null;

$ultima_actualizacion = $ultima_fecha
    ? date('d/m/Y', strtotime($ultima_fecha))
    : 'No registrada';

/* INVENTARIO DE INSUMOS */
$resultadoInsumos = $conexion->query("
    SELECT
        i.codigo,
        i.producto,
        c.nombre AS categoria,
        i.stock,
        i.unidad,
        i.estado
    FROM insumos i
    INNER JOIN categorias c
        ON i.id_categoria = c.id_categoria
    ORDER BY i.id_insumo DESC
");

/* INVENTARIO DE PRODUCTOS TERMINADOS */
$resultadoProductos = $conexion->query("
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

<main class="contenedor">

    <div class="volver">
        <a href="../Inventarios/Inventarios.php">
            ← Volver a Inventarios
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>
    </div>

    <header class="encabezado">
        <h1>Inventario General</h1>
        <p>
            Consulta consolidada de las existencias de insumos
            y productos terminados de YCL_Solutions.
        </p>
    </header>

    <!-- INDICADORES GENERALES -->
    <section class="cards">

        <div class="card insumos">
            <h2><?= number_format($total_insumos, 0, ',', '.') ?></h2>
            <span>Total de Insumos</span>
        </div>

        <div class="card categorias">
            <h2><?= number_format($total_categorias, 0, ',', '.') ?></h2>
            <span>Categorías de Insumos</span>
        </div>

        <div class="card stock">
            <h2><?= htmlspecialchars(formatoCantidad($total_stock_insumos)) ?></h2>
            <span>Existencias de Insumos</span>
        </div>

        <div class="card productos">
            <h2><?= number_format($total_productos, 0, ',', '.') ?></h2>
            <span>Productos Terminados</span>
        </div>

        <div class="card stock-productos">
            <h2><?= htmlspecialchars(formatoCantidad($total_stock_productos)) ?></h2>
            <span>Existencias de Productos Terminados</span>
        </div>

        <div class="card actualizados">
            <h2><?= htmlspecialchars($ultima_actualizacion) ?></h2>
            <span>Último Movimiento Registrado</span>
        </div>

    </section>

    <!-- INVENTARIO DE INSUMOS -->
    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Inventario de Insumos</h2>
                <p>
                    Consulta las existencias de los materiales utilizados
                    en los procesos de producción.
                </p>
            </div>

            <div class="acciones">
                <input
                    type="text"
                    class="buscar"
                    id="buscarInsumos"
                    placeholder="Buscar insumo..."
                >

                <button type="button" class="btn-buscar"
                    onclick="buscarTabla('buscarInsumos', 'tablaInsumos')">
                    Buscar
                </button>

                <button type="button" class="btn-actualizar"
                    onclick="actualizarTabla('buscarInsumos', 'tablaInsumos')">
                    ↻ Actualizar
                </button>
            </div>
        </div>

        <div class="tabla-contenedor">
            <table id="tablaInsumos">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Stock</th>
                        <th>Unidad</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($resultadoInsumos && $resultadoInsumos->num_rows > 0): ?>

                        <?php while ($insumo = $resultadoInsumos->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($insumo['codigo']) ?></td>
                                <td><?= htmlspecialchars($insumo['producto']) ?></td>
                                <td><?= htmlspecialchars($insumo['categoria']) ?></td>
                                <td><?= htmlspecialchars(formatoCantidad($insumo['stock'])) ?></td>
                                <td><?= htmlspecialchars($insumo['unidad']) ?></td>
                                <td>
                                    <?php
                                    $stockInsumo = (float)$insumo['stock'];
                                    $estadoInsumo = strtolower(trim($insumo['estado']));

                                    if ($stockInsumo <= 0 || $estadoInsumo === 'agotado'):
                                    ?>
                                        <span class="agotado">Agotado</span>
                                    <?php elseif ($estadoInsumo === 'disponible'): ?>
                                        <span class="disponible">Disponible</span>
                                    <?php else: ?>
                                        <span class="bajo">Bajo Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="6">No hay insumos registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

    <!-- INVENTARIO DE PRODUCTOS TERMINADOS -->
    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Inventario de Productos Terminados</h2>
                <p>
                    Consulta las existencias comerciales disponibles
                    por producto y presentación.
                </p>
            </div>

            <div class="acciones">
                <input
                    type="text"
                    class="buscar"
                    id="buscarProductos"
                    placeholder="Buscar producto..."
                >

                <button type="button" class="btn-buscar"
                    onclick="buscarTabla('buscarProductos', 'tablaProductos')">
                    Buscar
                </button>

                <button type="button" class="btn-actualizar"
                    onclick="actualizarTabla('buscarProductos', 'tablaProductos')">
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
                        <th>Stock Disponible</th>
                        <th>Stock Mínimo</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($resultadoProductos && $resultadoProductos->num_rows > 0): ?>

                        <?php while ($producto = $resultadoProductos->fetch_assoc()): ?>
                            <?php
                            $stock = (float)$producto['stock'];
                            $minimo = (float)$producto['stock_minimo'];

                            if ($stock <= 0) {
                                $estadoProducto = 'Agotado';
                                $claseEstado = 'agotado';
                            } elseif ($stock <= $minimo) {
                                $estadoProducto = 'Bajo Stock';
                                $claseEstado = 'bajo';
                            } else {
                                $estadoProducto = 'Disponible';
                                $claseEstado = 'disponible';
                            }
                            ?>

                            <tr>
                                <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                <td><?= htmlspecialchars($producto['presentacion']) ?></td>
                                <td><?= htmlspecialchars($producto['categoria'] ?? 'Sin categoría') ?></td>
                                <td><?= htmlspecialchars(formatoCantidad($stock)) ?></td>
                                <td><?= htmlspecialchars(formatoCantidad($minimo)) ?></td>
                                <td>
                                    <span class="<?= $claseEstado ?>">
                                        <?= htmlspecialchars($estadoProducto) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="6">No hay productos terminados activos registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

</main>

<script>
/* BÚSQUEDA GENERAL POR TABLA */
function buscarTabla(idBuscar, idTabla) {
    const texto = document.getElementById(idBuscar).value.toLowerCase().trim();
    const filas = document.querySelectorAll(`#${idTabla} tbody tr`);

    filas.forEach(function(fila) {
        const contenido = fila.textContent.toLowerCase();
        fila.style.display = contenido.includes(texto) ? "" : "none";
    });
}

/* ACTUALIZAR TABLA */
function actualizarTabla(idBuscar, idTabla) {
    document.getElementById(idBuscar).value = "";

    document.querySelectorAll(`#${idTabla} tbody tr`).forEach(function(fila) {
        fila.style.display = "";
    });
}

/* BÚSQUEDA AUTOMÁTICA Y ENTER */
[
    ['buscarInsumos', 'tablaInsumos'],
    ['buscarProductos', 'tablaProductos']
].forEach(function(elementos) {
    const buscador = document.getElementById(elementos[0]);

    buscador.addEventListener('input', function() {
        buscarTabla(elementos[0], elementos[1]);
    });

    buscador.addEventListener('keyup', function(event) {
        if (event.key === 'Enter') {
            buscarTabla(elementos[0], elementos[1]);
        }
    });
});
</script>

<?php
include("../../includes/footer.php");
?>
