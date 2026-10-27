<?php

$titulo = "Recetas";
$css = "Recetas.css";

include("../../includes/config.php");
include("../../includes/conexion.php");
include("../../includes/header.php");

/* CONSULTAR RECETAS */

$sql = "SELECT
            id_receta,
            codigo,
            nombre,
            producto,
            categoria,
            rendimiento,
            unidad,
            tiempo,
            estado
        FROM recetas
        ORDER BY id_receta DESC";

$resultado = $conexion->query($sql);
$errorConsulta = $resultado === false ? $conexion->error : "";

?>

<main class="contenido">

    <!-- BARRA SUPERIOR -->

    <section class="barra-superior">

        <div>

            <a href="../Produccion/Produccion.php" class="btn btn-verde">
                ← Volver a Producción
            </a>

            <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
                ⌂ Inicio
            </a>

        </div>

    </section>

    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1><?= htmlspecialchars($titulo) ?></h1>

        <p>
            Gestione las recetas de elaboración, controle los ingredientes
            utilizados y administre la información de cada producto.
        </p>

    </header>

    <!-- PANEL -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>Listado de Recetas</h2>

                <p>
                    Consulte y administre todas las recetas registradas.
                </p>

            </div>

            <!-- ACCIONES -->

            <div class="barra-acciones">

                <input
                    type="text"
                    id="buscarReceta"
                    placeholder="Buscar receta..."
                >

                <button
                    type="button"
                    class="btn btn-principal"
                    onclick="buscarReceta()"
                >
                    Buscar
                </button>

                <button
                    type="button"
                    class="btn btn-principal"
                    onclick="refrescarRecetas()"
                >
                    ↻ Refrescar
                </button>

                <a
                    href="NuevaReceta.php"
                    class="btn btn-principal"
                >
                    + Nueva Receta
                </a>

            </div>

        </div>

        <!-- TABLA -->

        <table class="tabla" id="tablaRecetas">

            <thead>

                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Rendimiento</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>

            </thead>

            <tbody>

                <?php if ($resultado === false): ?>

                    <tr>
                        <td colspan="6" style="text-align:center;">
                            No fue posible consultar las recetas.
                            Error de base de datos:
                            <?= htmlspecialchars($errorConsulta) ?>
                        </td>
                    </tr>

                <?php elseif ($resultado->num_rows > 0): ?>

                    <?php while ($receta = $resultado->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($receta['codigo'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($receta['producto'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($receta['categoria'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($receta['rendimiento'] ?? '') ?>
                                <?= htmlspecialchars($receta['unidad'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($receta['estado'] ?? '') ?>
                            </td>

                            <td class="acciones">

                                <a
                                    href="VerReceta.php?id=<?= (int) $receta['id_receta'] ?>"
                                    class="btn-ver"
                                >
                                    Ver
                                </a>

                                <a
                                    href="EditarReceta.php?id=<?= (int) $receta['id_receta'] ?>"
                                    class="btn-editar"
                                >
                                    Editar
                                </a>

                                <a
                                    href="EliminarReceta.php?id=<?= (int) $receta['id_receta'] ?>"
                                    class="btn-eliminar"
                                    onclick="return confirm('¿Está seguro de eliminar esta receta?');"
                                >
                                    Eliminar
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" style="text-align:center;">
                            No hay recetas registradas.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </section>

</main>

<script>

/* BUSCAR RECETA */

function buscarReceta() {

    const texto = document
        .getElementById("buscarReceta")
        .value
        .toLowerCase()
        .trim();

    const filas = document.querySelectorAll(
        "#tablaRecetas tbody tr"
    );

    filas.forEach(function(fila) {

        const contenido = fila.textContent.toLowerCase();

        fila.style.display =
            contenido.includes(texto) ? "" : "none";

    });

}

/* REFRESCAR */

function refrescarRecetas() {

    document.getElementById("buscarReceta").value = "";

    const filas = document.querySelectorAll(
        "#tablaRecetas tbody tr"
    );

    filas.forEach(function(fila) {
        fila.style.display = "";
    });

}

/* BUSCAR AUTOMÁTICAMENTE AL ESCRIBIR */

document
    .getElementById("buscarReceta")
    .addEventListener("input", buscarReceta);

/* BUSCAR CON ENTER */

document
    .getElementById("buscarReceta")
    .addEventListener("keydown", function(event) {

        if (event.key === "Enter") {
            buscarReceta();
        }

    });

</script>

<?php include("../../includes/footer.php"); ?>