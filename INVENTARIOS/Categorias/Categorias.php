<?php

$titulo = "CATEGORÍAS";
$css = "Categorias.css";

include("../../includes/header.php");

?>

<main class="contenedor">

<!-- VOLVER -->

<div class="volver">

    <a href="../Inventarios/Inventarios.php">
        ← Volver a Inventarios
    </a>

    <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
        ⌂ Inicio
    </a>

</div>


<!-- ENCABEZADO -->

<header class="encabezado">

    <h1>
        Gestión de Categorías
    </h1>

    <p>
        Administra las categorías utilizadas para clasificar los insumos del inventario.
    </p>

</header>


<!-- INDICADORES -->

<section class="cards">

    <div class="card categorias">

        <span class="titulo-card">
            Categorías
        </span>

        <h2>
            8
        </h2>

        <p>
            Total registradas
        </p>

    </div>


    <div class="card insumos">

        <span class="titulo-card">
            Insumos
        </span>

        <h2>
            120
        </h2>

        <p>
            Productos asociados
        </p>

    </div>


    <div class="card utilizada">

        <span class="titulo-card">
            Más utilizada
        </span>

        <h2>
            Materia Prima
        </h2>

        <p>
            Categoría con más insumos
        </p>

    </div>


    <div class="card activas">

        <span class="titulo-card">
            Activas
        </span>

        <h2>
            8
        </h2>

        <p>
            Categorías habilitadas
        </p>

    </div>

</section>


<!-- PANEL PRINCIPAL -->

<section class="panel">

    <div class="panel-header">

        <div>

            <h2>
                Listado de Categorías
            </h2>

            <p>
                Consulta y administra las categorías registradas en el sistema.
            </p>

        </div>


        <div class="acciones">

            <input
                type="text"
                id="buscarCategoria"
                class="buscar"
                placeholder="Buscar categoría..."
            >

            <button
                type="button"
                class="btn-buscar"
                onclick="buscarCategoria()"
            >
                Buscar
            </button>

            <button
                type="button"
                class="btn-buscar"
                onclick="refrescarCategorias()"
            >
                Refrescar
            </button>

            <a
                href="NuevaCategoria.php"
                class="btn-nuevo"
            >
                + Nueva Categoría
            </a>

        </div>

    </div>


    <!-- TABLA -->

    <table>

        <thead>

            <tr>

                <th>
                    Código
                </th>

                <th>
                    Categoría
                </th>

                <th>
                    Descripción
                </th>

                <th>
                    Insumos
                </th>

                <th>
                    Estado
                </th>

                <th>
                    Acciones
                </th>

            </tr>

        </thead>


        <tbody id="tablaCategorias">

            <tr>

                <td>
                    CAT001
                </td>

                <td>
                    Materia Prima
                </td>

                <td>
                    Ingredientes principales para la producción.
                </td>

                <td>
                    45
                </td>

                <td>

                    <span class="badge badge-activo">
                        Activa
                    </span>

                </td>

                <td>

                    <div class="acciones-tabla">

                        <a
                            href="EditarCategoria.php"
                            class="btn-editar"
                        >
                            Editar Categoría
                        </a>

                        <a
                            href="#"
                            class="btn-eliminar"
                        >
                            Eliminar
                        </a>

                    </div>

                </td>

            </tr>


            <tr>

                <td>
                    CAT002
                </td>

                <td>
                    Decoración
                </td>

                <td>
                    Colorantes, fondant y elementos decorativos.
                </td>

                <td>
                    30
                </td>

                <td>

                    <span class="badge badge-activo">
                        Activa
                    </span>

                </td>

                <td>

                    <div class="acciones-tabla">

                        <a
                            href="EditarCategoria.php"
                            class="btn-editar"
                        >
                            Editar Categoría
                        </a>

                        <a
                            href="#"
                            class="btn-eliminar"
                        >
                            Eliminar
                        </a>

                    </div>

                </td>

            </tr>


            <tr>

                <td>
                    CAT003
                </td>

                <td>
                    Empaques
                </td>

                <td>
                    Cajas, cintas, bolsas y materiales de empaque.
                </td>

                <td>
                    18
                </td>

                <td>

                    <span class="badge badge-inactivo">
                        Inactiva
                    </span>

                </td>

                <td>

                    <div class="acciones-tabla">

                        <a
                            href="EditarCategoria.php"
                            class="btn-editar"
                        >
                            Editar Categoría
                        </a>

                        <a
                            href="#"
                            class="btn-eliminar"
                        >
                            Eliminar
                        </a>

                    </div>

                </td>

            </tr>

        </tbody>

    </table>

</section>

</main>


<!-- BUSCADOR EN TIEMPO REAL -->

<script>

function buscarCategoria() {

    const texto = document
        .getElementById("buscarCategoria")
        .value
        .toLowerCase()
        .trim();

    const filas = document.querySelectorAll("#tablaCategorias tr");

    filas.forEach(function(fila) {

        const contenido = fila.textContent.toLowerCase();

        fila.style.display = contenido.includes(texto) ? "" : "none";

    });

}


function refrescarCategorias() {

    const buscador = document.getElementById("buscarCategoria");

    buscador.value = "";

    const filas = document.querySelectorAll("#tablaCategorias tr");

    filas.forEach(function(fila) {

        fila.style.display = "";

    });

}


const buscadorCategoria = document.getElementById("buscarCategoria");


buscadorCategoria.addEventListener("input", function() {

    buscarCategoria();

});


buscadorCategoria.addEventListener("keyup", function(event) {

    if (event.key === "Enter") {

        buscarCategoria();

    }

});

</script>


<?php

include("../../includes/footer.php");

?>