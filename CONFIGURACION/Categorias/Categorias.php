<?php

$titulo = "Categorías";
$css = "Categorias.css";

include("../../includes/config.php");
include("../../includes/header.php");

require_once("../../includes/conexion.php");


/* CATEGORÍAS */

$sql = "SELECT
            id_categoria_producto,
            nombre,
            descripcion,
            estado
        FROM categorias_producto
        ORDER BY id_categoria_producto DESC";

$resultado = $conexion->query($sql);

?>

<main class="contenido">

    <!-- BARRA SUPERIOR -->

    <section class="barra-superior">

        <a
            href="../Catalogo/Catalogo.php"
            class="btn-volver"
        >
            ← Volver a Catálogo
        </a>

    </section>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>
            Categorías
        </h1>

        <p>
            Administre las categorías utilizadas para organizar
            los productos del catálogo.
        </p>

    </header>


    <!-- FILTROS -->

    <section class="filtros">

        <div class="campo-filtro campo-busqueda">

            <label for="buscar">
                Buscar categoría
            </label>

            <input
                type="text"
                id="buscar"
                placeholder="Nombre o descripción..."
            >

        </div>


        <!-- ACCIONES -->

        <div class="acciones-filtros">

            <button
                type="button"
                class="btn-buscar"
                onclick="filtrarCategorias()"
            >
                Buscar
            </button>

            <a
                href="Categorias.php"
                class="btn-refrescar"
            >
                Refrescar
            </a>

            <a
                href="CategoriaForm.php"
                class="btn-nueva-categoria"
            >
                + Nueva Categoría
            </a>

        </div>

    </section>


    <!-- TABLA -->

    <section class="tabla-contenedor">

        <table id="tablaCategorias">

            <thead>

                <tr>

                    <th>
                        Categoría
                    </th>

                    <th>
                        Descripción
                    </th>

                    <th>
                        Estado
                    </th>

                    <th>
                        Acciones
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php if ($resultado && $resultado->num_rows > 0): ?>

                    <?php while ($categoria = $resultado->fetch_assoc()): ?>

                        <tr>

                            <!-- CATEGORÍA -->

                            <td>
                                <?= htmlspecialchars($categoria['nombre']) ?>
                            </td>


                            <!-- DESCRIPCIÓN -->

                            <td>
                                <?= htmlspecialchars(
                                    $categoria['descripcion'] ?? ''
                                ) ?>
                            </td>


                            <!-- ESTADO -->

                            <td>

                                <?php if ($categoria['estado'] === 'Activa'): ?>

                                    <span class="estado activo">
                                        Activa
                                    </span>

                                <?php else: ?>

                                    <span class="estado">
                                        Inactiva
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- ACCIONES -->

                            <td>

                                <a
                                    href="CategoriaForm.php?id=<?= $categoria['id_categoria_producto'] ?>"
                                    class="btn-editar"
                                >
                                    Editar
                                </a>

                                <a
                                    href="EliminarCategoria.php?id=<?= $categoria['id_categoria_producto'] ?>"
                                    class="btn-eliminar"
                                    onclick="return confirm('¿Está seguro de eliminar esta categoría?');"
                                >
                                    Eliminar
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="4">
                            No hay categorías registradas.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </section>

</main>


<!-- BÚSQUEDA -->

<script>

const buscar = document.getElementById("buscar");
const filas = document.querySelectorAll("#tablaCategorias tbody tr");


function filtrarCategorias(){

    const texto = buscar.value.toLowerCase().trim();

    filas.forEach(function(fila){

        const contenido =
            fila.textContent.toLowerCase();

        fila.style.display =
            contenido.includes(texto)
                ? ""
                : "none";

    });

}


/* BÚSQUEDA AUTOMÁTICA */

buscar.addEventListener(
    "input",
    filtrarCategorias
);

</script>


<?php include("../../includes/footer.php"); ?>