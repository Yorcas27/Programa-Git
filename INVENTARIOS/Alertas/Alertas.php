<?php
$titulo = "Alertas de Stock";
$css = "Alertas.css";

require_once("../../includes/conexion.php");
require_once("../../includes/header.php");

/* FORMATO DE CANTIDADES */
function formatoCantidadAlerta($cantidad) {
    return rtrim(
        rtrim(number_format((float)$cantidad, 2, ',', '.'), '0'),
        ','
    );
}

/* BÚSQUEDA */
$buscar = trim($_GET['buscar'] ?? '');

/* INDICADORES DE INSUMOS */
$sqlInsumos = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) AS criticos,
        SUM(CASE WHEN stock > 0 AND stock <= stock_minimo THEN 1 ELSE 0 END) AS bajos
    FROM insumos
";

$datosInsumos = $conexion->query($sqlInsumos)->fetch_assoc();

$totalInsumos = (int)($datosInsumos['total'] ?? 0);
$criticosInsumos = (int)($datosInsumos['criticos'] ?? 0);
$bajosInsumos = (int)($datosInsumos['bajos'] ?? 0);

/* INDICADORES DE PRODUCTOS TERMINADOS */
$sqlProductos = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN COALESCE(i.stock, 0) <= 0 THEN 1 ELSE 0 END) AS criticos,
        SUM(
            CASE
                WHEN COALESCE(i.stock, 0) > 0
                 AND COALESCE(i.stock, 0) <= COALESCE(i.stock_minimo, 0)
                THEN 1 ELSE 0
            END
        ) AS bajos
    FROM productos p
    LEFT JOIN inventario_productos_terminados i
        ON p.id_producto = i.id_producto
    WHERE LOWER(p.estado) = 'activo'
";

$datosProductos = $conexion->query($sqlProductos)->fetch_assoc();

$totalProductos = (int)($datosProductos['total'] ?? 0);
$criticosProductos = (int)($datosProductos['criticos'] ?? 0);
$bajosProductos = (int)($datosProductos['bajos'] ?? 0);

/* CONSOLIDADO */
$totalCriticos = $criticosInsumos + $criticosProductos;
$totalBajos = $bajosInsumos + $bajosProductos;
$totalAlertas = $totalCriticos + $totalBajos;

/* LISTADO COMPLETO DE INSUMOS */
$sqlListaInsumos = "
    SELECT
        i.id_insumo AS id,
        i.codigo,
        i.producto AS nombre,
        c.nombre AS categoria,
        i.stock,
        i.stock_minimo,
        i.unidad,
        CASE
            WHEN i.stock <= 0 THEN 'Crítico'
            WHEN i.stock <= i.stock_minimo THEN 'Stock bajo'
            ELSE 'Disponible'
        END AS estado
    FROM insumos i
    LEFT JOIN categorias c
        ON i.id_categoria = c.id_categoria
    ORDER BY
        CASE
            WHEN i.stock <= 0 THEN 1
            WHEN i.stock <= i.stock_minimo THEN 2
            ELSE 3
        END,
        i.stock ASC,
        i.producto ASC
";

$resultadoInsumos = $conexion->query($sqlListaInsumos);

/* LISTADO COMPLETO DE PRODUCTOS TERMINADOS */
$sqlListaProductos = "
    SELECT
        p.id_producto AS id,
        p.nombre,
        p.presentacion,
        cp.nombre AS categoria,
        COALESCE(i.stock, 0) AS stock,
        COALESCE(i.stock_minimo, 0) AS stock_minimo,
        CASE
            WHEN COALESCE(i.stock, 0) <= 0 THEN 'Crítico'
            WHEN COALESCE(i.stock, 0) <= COALESCE(i.stock_minimo, 0)
                THEN 'Stock bajo'
            ELSE 'Disponible'
        END AS estado
    FROM productos p
    LEFT JOIN categorias_producto cp
        ON p.id_categoria_producto = cp.id_categoria_producto
    LEFT JOIN inventario_productos_terminados i
        ON p.id_producto = i.id_producto
    WHERE LOWER(p.estado) = 'activo'
    ORDER BY
        CASE
            WHEN COALESCE(i.stock, 0) <= 0 THEN 1
            WHEN COALESCE(i.stock, 0) <= COALESCE(i.stock_minimo, 0) THEN 2
            ELSE 3
        END,
        stock ASC,
        p.nombre ASC
";

$resultadoProductos = $conexion->query($sqlListaProductos);
?>

<main class="contenido">

    <div class="barra-superior">
        <a href="../Inventarios/Inventarios.php" class="btn-regresar">
            ← Volver a Inventarios
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>
    </div>

    <header class="encabezado">
        <h1>Alertas de Stock</h1>
        <p>
            Seguimiento centralizado de las existencias de insumos
            y productos terminados.
        </p>
    </header>

    <!-- INDICADORES CONSOLIDADOS -->
    <section class="cards">

        <div class="card total">
            <h2><?= number_format($totalAlertas, 0, ',', '.') ?></h2>
            <span>Total de Alertas</span>
        </div>

        <div class="card critico">
            <h2><?= number_format($totalCriticos, 0, ',', '.') ?></h2>
            <span>Stock Crítico</span>
        </div>

        <div class="card bajo">
            <h2><?= number_format($totalBajos, 0, ',', '.') ?></h2>
            <span>Stock Bajo</span>
        </div>

    </section>

    <!-- LISTADO DE INSUMOS -->
    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Seguimiento de Insumos</h2>
                <p>
                    <?= $criticosInsumos ?> críticos,
                    <?= $bajosInsumos ?> con stock bajo y
                    <?= $totalInsumos - $criticosInsumos - $bajosInsumos ?> disponibles.
                </p>
            </div>

            <form method="GET" class="acciones">
                <input
                    type="text"
                    name="buscar"
                    class="buscar"
                    placeholder="Buscar insumos o productos..."
                    value="<?= htmlspecialchars($buscar, ENT_QUOTES, 'UTF-8') ?>"
                >

                <button type="submit" class="btn-buscar">Buscar</button>

                <a href="Alertas.php" class="btn-actualizar">↻ Refrescar</a>
            </form>
        </div>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Insumo</th>
                        <th>Categoría</th>
                        <th>Stock actual</th>
                        <th>Stock mínimo</th>
                        <th>Unidad</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $encontrados = 0;

                    if ($resultadoInsumos):
                        while ($fila = $resultadoInsumos->fetch_assoc()):

                            $contenido = mb_strtolower(
                                $fila['codigo'] . ' ' .
                                $fila['nombre'] . ' ' .
                                ($fila['categoria'] ?? '') . ' ' .
                                $fila['estado'],
                                'UTF-8'
                            );

                            if (
                                $buscar !== '' &&
                                mb_strpos(
                                    $contenido,
                                    mb_strtolower($buscar, 'UTF-8')
                                ) === false
                            ) {
                                continue;
                            }

                            $encontrados++;

                            $clase = 'disponible';

                            if ($fila['estado'] === 'Crítico') {
                                $clase = 'critico';
                            } elseif ($fila['estado'] === 'Stock bajo') {
                                $clase = 'bajo';
                            }
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($fila['codigo']) ?></td>

                            <td><?= htmlspecialchars($fila['nombre']) ?></td>

                            <td>
                                <?= htmlspecialchars($fila['categoria'] ?? 'Sin categoría') ?>
                            </td>

                            <td>
                                <span class="stock-actual">
                                    <?= htmlspecialchars(formatoCantidadAlerta($fila['stock'])) ?>
                                </span>
                            </td>

                            <td>
                                <span class="stock-minimo">
                                    <?= htmlspecialchars(formatoCantidadAlerta($fila['stock_minimo'])) ?>
                                </span>
                            </td>

                            <td><?= htmlspecialchars($fila['unidad']) ?></td>

                            <td>
                                <span class="<?= $clase ?>">
                                    <?= htmlspecialchars($fila['estado']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    endif;

                    if ($encontrados === 0):
                    ?>
                        <tr>
                            <td colspan="7">
                                No hay registros de insumos para mostrar.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

    <!-- LISTADO DE PRODUCTOS TERMINADOS -->
    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Seguimiento de Productos Terminados</h2>
                <p>
                    <?= $criticosProductos ?> críticos,
                    <?= $bajosProductos ?> con stock bajo y
                    <?= $totalProductos - $criticosProductos - $bajosProductos ?> disponibles.
                </p>
            </div>
        </div>

        <div class="tabla-contenedor">
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Presentación</th>
                        <th>Categoría</th>
                        <th>Stock actual</th>
                        <th>Stock mínimo</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $encontrados = 0;

                    if ($resultadoProductos):
                        while ($fila = $resultadoProductos->fetch_assoc()):

                            $contenido = mb_strtolower(
                                $fila['nombre'] . ' ' .
                                ($fila['presentacion'] ?? '') . ' ' .
                                ($fila['categoria'] ?? '') . ' ' .
                                $fila['estado'],
                                'UTF-8'
                            );

                            if (
                                $buscar !== '' &&
                                mb_strpos(
                                    $contenido,
                                    mb_strtolower($buscar, 'UTF-8')
                                ) === false
                            ) {
                                continue;
                            }

                            $encontrados++;

                            $clase = 'disponible';

                            if ($fila['estado'] === 'Crítico') {
                                $clase = 'critico';
                            } elseif ($fila['estado'] === 'Stock bajo') {
                                $clase = 'bajo';
                            }
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($fila['nombre']) ?></td>

                            <td>
                                <?= htmlspecialchars($fila['presentacion'] ?? 'No especificada') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($fila['categoria'] ?? 'Sin categoría') ?>
                            </td>

                            <td>
                                <span class="stock-actual">
                                    <?= htmlspecialchars(formatoCantidadAlerta($fila['stock'])) ?>
                                </span>
                            </td>

                            <td>
                                <span class="stock-minimo">
                                    <?= htmlspecialchars(formatoCantidadAlerta($fila['stock_minimo'])) ?>
                                </span>
                            </td>

                            <td>
                                <span class="<?= $clase ?>">
                                    <?= htmlspecialchars($fila['estado']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php
                        endwhile;
                    endif;

                    if ($encontrados === 0):
                    ?>
                        <tr>
                            <td colspan="6">
                                No hay registros de productos terminados para mostrar.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>