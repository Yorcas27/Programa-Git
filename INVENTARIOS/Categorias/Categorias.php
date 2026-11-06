
<?php
$titulo = "CATEGORÍAS";
$css = "Categorias.css";

require_once("../../includes/conexion.php");

// INDICADORES
$totalCategorias = 0;
$totalInsumos = 0;
$totalActivas = 0;
$categoriaMasUtilizada = "Sin registros";

$resultado = $conexion->query("SELECT COUNT(*) AS total FROM categorias");
if ($resultado) {
    $totalCategorias = (int)$resultado->fetch_assoc()['total'];
}

$resultado = $conexion->query("SELECT COUNT(*) AS total FROM insumos");
if ($resultado) {
    $totalInsumos = (int)$resultado->fetch_assoc()['total'];
}

$resultado = $conexion->query("SELECT COUNT(*) AS total FROM categorias WHERE estado = 'Activa'");
if ($resultado) {
    $totalActivas = (int)$resultado->fetch_assoc()['total'];
}

$sqlMasUtilizada = "
    SELECT c.nombre, COUNT(i.id_insumo) AS total_insumos
    FROM categorias c
    LEFT JOIN insumos i ON c.id_categoria = i.id_categoria
    GROUP BY c.id_categoria, c.nombre
    ORDER BY total_insumos DESC, c.nombre ASC
    LIMIT 1
";

$resultado = $conexion->query($sqlMasUtilizada);

if ($resultado && $resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();

    if ((int)$fila['total_insumos'] > 0) {
        $categoriaMasUtilizada = $fila['nombre'];
    }
}

// LISTADO DE CATEGORÍAS
$sql = "
    SELECT
        c.id_categoria,
        c.codigo,
        c.nombre,
        c.descripcion,
        c.estado,
        COUNT(i.id_insumo) AS total_insumos
    FROM categorias c
    LEFT JOIN insumos i ON c.id_categoria = i.id_categoria
    GROUP BY
        c.id_categoria,
        c.codigo,
        c.nombre,
        c.descripcion,
        c.estado
    ORDER BY c.id_categoria DESC
";

$resultadoCategorias = $conexion->query($sql);

include("../../includes/header.php");
?>

<main class="contenedor categorias-modulo">

    <div class="volver">
        <a href="../Inventarios/Inventarios.php">← Volver a Inventarios</a>
        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn">Inicio</a>
    </div>

    <div class="encabezado">
        <h1>Gestión de Categorías</h1>
        <p>Administra las categorías utilizadas para clasificar los insumos.</p>
    </div>

    <!-- INDICADORES -->
    <section class="cards">

        <div class="card categorias">
            <span class="titulo-card">Categorías registradas</span>
            <h2 class="valor-categoria"><?= $totalCategorias ?></h2>
        </div>

        <div class="card insumos">
            <span class="titulo-card">Insumos registrados</span>
            <h2 class="valor-categoria"><?= $totalInsumos ?></h2>
        </div>

        <div class="card utilizada">
            <span class="titulo-card">Más utilizada</span>
            <h2 class="valor-categoria">
                <?= htmlspecialchars($categoriaMasUtilizada) ?>
            </h2>
        </div>

        <div class="card activas">
            <span class="titulo-card">Categorías activas</span>
            <h2 class="valor-categoria"><?= $totalActivas ?></h2>
        </div>

    </section>

    <!-- PANEL PRINCIPAL -->
    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Listado de Categorías</h2>
                <p>Consulta y administra las categorías registradas.</p>
            </div>
        </div>

        <!-- MENSAJES -->
        <?php
        $mensajes = [
            'creada' => ['success', 'La categoría se registró correctamente.'],
            'actualizada' => ['success', 'La categoría se actualizó correctamente.'],
            'eliminada' => ['success', 'La categoría se eliminó correctamente.'],
            'tiene_insumos' => ['warning', 'No se puede eliminar esta categoría porque tiene insumos asociados. Puedes cambiar su estado a Inactiva.'],
            'no_encontrada' => ['warning', 'La categoría solicitada no existe.'],
            'id_invalido' => ['warning', 'El identificador de la categoría no es válido.'],
            'error_eliminar' => ['error', 'No fue posible eliminar la categoría. Inténtalo nuevamente.']
        ];

        $mensaje = $_GET['mensaje'] ?? '';

        if (isset($mensajes[$mensaje])):
        ?>
            <div class="mensaje-categorias <?= $mensajes[$mensaje][0] ?>">
                <?= htmlspecialchars($mensajes[$mensaje][1]) ?>
            </div>
        <?php endif; ?>

        <!-- BUSCADOR Y BOTONES -->
        <div class="acciones">

            <input
                type="text"
                id="buscarCategoria"
                class="buscar"
                placeholder="Buscar por código o nombre..."
                autocomplete="off"
            >

            <div class="acciones-derecha">

                <button type="button" class="btn-buscar" id="btnBuscar">
                    Buscar
                </button>

                <button type="button" class="btn-buscar" id="btnRefrescar">
                    Refrescar
                </button>

                <a href="NuevaCategoria.php" class="btn-nuevo">
                    + Nueva Categoría
                </a>

            </div>

        </div>

        <!-- TABLA -->
        <div class="tabla-categorias-contenedor">

            <table id="tablaCategorias">

                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th>Insumos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if ($resultadoCategorias && $resultadoCategorias->num_rows > 0): ?>

                        <?php while ($categoria = $resultadoCategorias->fetch_assoc()): ?>

                            <tr>
                                <td>
                                    <?= htmlspecialchars($categoria['codigo']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($categoria['descripcion'] ?? '') ?>
                                </td>

                                <td>
                                    <?= (int)$categoria['total_insumos'] ?>
                                </td>

                                <td>
                                    <?php if ($categoria['estado'] === 'Activa'): ?>
                                        <span class="badge badge-activo">Activa</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactivo">Inactiva</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="acciones-tabla">

                                        <a
                                            href="EditarCategoria.php?id=<?= (int)$categoria['id_categoria'] ?>"
                                            class="btn-editar"
                                        >
                                            Editar
                                        </a>

                                        <?php if ((int)$categoria['total_insumos'] === 0): ?>

                                            <form
                                                action="EliminarCategoria.php"
                                                method="POST"
                                                onsubmit="return confirm('¿Estás seguro de eliminar esta categoría?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="id_categoria"
                                                    value="<?= (int)$categoria['id_categoria'] ?>"
                                                >

                                                <button type="submit" class="btn-eliminar">
                                                    Eliminar
                                                </button>
                                            </form>

                                        <?php else: ?>

                                            <button
                                                type="button"
                                                class="btn-eliminar"
                                                disabled
                                                title="Esta categoría tiene insumos asociados"
                                            >
                                                Eliminar
                                            </button>

                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="sin-datos">
                                No hay categorías registradas.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

            <p id="sinResultados" class="sin-datos" style="display:none;">
                No se encontraron categorías con ese criterio.
            </p>

        </div>

    </section>

</main>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const buscador = document.getElementById("buscarCategoria");
    const btnBuscar = document.getElementById("btnBuscar");
    const btnRefrescar = document.getElementById("btnRefrescar");
    const tabla = document.getElementById("tablaCategorias");
    const sinResultados = document.getElementById("sinResultados");

    function filtrarCategorias() {

        const texto = buscador.value.trim().toLowerCase();
        const filas = tabla.querySelectorAll("tbody tr");
        let visibles = 0;

        filas.forEach(function (fila) {

            if (fila.querySelector(".sin-datos")) {
                fila.style.display = texto === "" ? "" : "none";
                return;
            }

            const codigo = fila.cells[0].textContent.toLowerCase();
            const nombre = fila.cells[1].textContent.toLowerCase();

            const coincide = codigo.includes(texto) || nombre.includes(texto);

            fila.style.display = coincide ? "" : "none";

            if (coincide) {
                visibles++;
            }
        });

        sinResultados.style.display =
            texto !== "" && visibles === 0 ? "block" : "none";
    }

    btnBuscar.addEventListener("click", filtrarCategorias);

    buscador.addEventListener("keyup", filtrarCategorias);

    btnRefrescar.addEventListener("click", function () {
        window.location.href = "Categorias.php";
    });

});
</script>

<?php include("../../includes/footer.php"); ?>
