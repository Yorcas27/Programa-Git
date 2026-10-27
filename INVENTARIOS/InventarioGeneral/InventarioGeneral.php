<?php
$titulo = "Inventario General";
$css = "InventarioGeneral.css";

require_once("../../includes/conexion.php");
require_once("../../includes/header.php");

/* INDICADORES */
$resultado = $conexion->query("
    SELECT COUNT(*) AS total
    FROM insumos
");
$total_insumos = $resultado->fetch_assoc()['total'] ?? 0;

$resultado = $conexion->query("
    SELECT COUNT(*) AS total
    FROM categorias
");
$total_categorias = $resultado->fetch_assoc()['total'] ?? 0;

$resultado = $conexion->query("
    SELECT COALESCE(SUM(stock), 0) AS total
    FROM insumos
");
$total_stock = $resultado->fetch_assoc()['total'] ?? 0;

$resultado = $conexion->query("
    SELECT MAX(fecha) AS ultima_fecha
    FROM movimientos
");
$ultima_fecha = $resultado->fetch_assoc()['ultima_fecha'] ?? null;

/* FORMATO FECHA */
$ultima_actualizacion = $ultima_fecha
    ? date('d/m/Y', strtotime($ultima_fecha))
    : 'No registrada';

/* FORMATO DE CANTIDAD */
function formatoCantidad($cantidad) {
    return rtrim(
        rtrim(number_format((float)$cantidad, 2, ',', '.'), '0'),
        ','
    );
}

/* INVENTARIO */
$resultado = $conexion->query("
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
            Consulta el resumen general del inventario registrado en YCL_Solutions.
        </p>
    </header>

    <section class="cards">

        <div class="card insumos">
            <h2>
                <?= number_format($total_insumos, 0, ',', '.') ?>
            </h2>
            <span>Total de Insumos</span>
        </div>

        <div class="card categorias">
            <h2>
                <?= number_format($total_categorias, 0, ',', '.') ?>
            </h2>
            <span>Categorías Registradas</span>
        </div>

        <div class="card stock">
            <h2>
                <?= htmlspecialchars(formatoCantidad($total_stock)) ?>
            </h2>
            <span>Unidades Disponibles</span>
        </div>

        <div class="card actualizados">
            <h2>
                <?= htmlspecialchars($ultima_actualizacion) ?>
            </h2>
            <span>Última Actualización</span>
        </div>

    </section>

    <section class="panel">

        <div class="panel-header">

            <div>
                <h2>Resumen del Inventario</h2>
                <p>
                    Visualice el estado actual de los insumos registrados.
                </p>
            </div>

            <div class="acciones">

                <input
                    type="text"
                    class="buscar"
                    id="buscarInventario"
                    placeholder="Buscar insumo..."
                >

                <button
                    type="button"
                    class="btn-buscar"
                    onclick="buscarInventario()"
                >
                    Buscar
                </button>

                <button
                    type="button"
                    class="btn-buscar"
                    onclick="actualizarInventario()"
                >
                    ↻ Actualizar
                </button>

            </div>

        </div>

        <table id="tablaInventario">

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

                <?php if ($resultado && $resultado->num_rows > 0): ?>

                    <?php while ($insumo = $resultado->fetch_assoc()): ?>

                        <tr>
                            <td>
                                <?= htmlspecialchars($insumo['codigo']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($insumo['producto']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($insumo['categoria']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(formatoCantidad($insumo['stock'])) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($insumo['unidad']) ?>
                            </td>

                            <td>
                                <?php if ($insumo['estado'] === 'Disponible'): ?>
                                    <span class="disponible">
                                        Disponible
                                    </span>
                                <?php else: ?>
                                    <span class="bajo">
                                        Bajo Stock
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6">
                            No hay insumos registrados.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </section>

</main>

<script>
/* BUSCAR INVENTARIO */
function buscarInventario() {
    const texto = document
        .getElementById("buscarInventario")
        .value
        .toLowerCase()
        .trim();

    const filas = document.querySelectorAll(
        "#tablaInventario tbody tr"
    );

    filas.forEach(function(fila) {
        const contenido = fila.textContent.toLowerCase();
        fila.style.display = contenido.includes(texto) ? "" : "none";
    });
}

/* ACTUALIZAR */
function actualizarInventario() {
    document.getElementById("buscarInventario").value = "";

    const filas = document.querySelectorAll(
        "#tablaInventario tbody tr"
    );

    filas.forEach(function(fila) {
        fila.style.display = "";
    });
}

/* BUSCAR AUTOMÁTICAMENTE AL ESCRIBIR */
document
    .getElementById("buscarInventario")
    .addEventListener("input", function() {
        buscarInventario();
    });

/* BUSCAR CON ENTER */
document
    .getElementById("buscarInventario")
    .addEventListener("keyup", function(event) {
        if (event.key === "Enter") {
            buscarInventario();
        }
    });
</script>

<?php
include("../../includes/footer.php");
?>