<?php

$titulo="EDITAR CATEGORÍA";
$css="Categorias.css";

include("../../includes/header.php");

?>

<main class="contenedor">

    <!-- VOLVER -->

    <div class="volver">

        <a href="Categorias.php">
            ← Volver a Categorías
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </div>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>
            Editar Categoría
        </h1>

        <p>
            Modifica la información de una categoría existente.
        </p>

    </header>


    <!-- PANEL FORMULARIO -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Información de la Categoría
                </h2>

                <p>
                    Actualiza los datos registrados.
                </p>

            </div>

        </div>


        <!-- FORMULARIO -->

        <form class="formulario">

            <div class="grupo-formulario">


                <!-- CÓDIGO -->

                <div class="campo">

                    <label for="codigo">
                        Código
                    </label>

                    <input
                        type="text"
                        id="codigo"
                        value="CAT001"
                        readonly
                    >

                </div>


                <!-- NOMBRE -->

                <div class="campo">

                    <label for="nombre">
                        Nombre
                    </label>

                    <input
                        type="text"
                        id="nombre"
                        value="Materia Prima"
                    >

                </div>


                <!-- DESCRIPCIÓN -->

                <div class="campo campo-completo">

                    <label for="descripcion">
                        Descripción
                    </label>

                    <textarea
                        id="descripcion"
                        rows="4"
                    >Ingredientes principales para la producción.</textarea>

                </div>


                <!-- ESTADO -->

                <div class="campo">

                    <label for="estado">
                        Estado
                    </label>

                    <select id="estado">

                        <option selected>
                            Activa
                        </option>

                        <option>
                            Inactiva
                        </option>

                    </select>

                </div>


            </div>


            <!-- BOTONES -->

            <div class="botones-formulario">

                <button
                    type="submit"
                    class="btn-guardar"
                >
                    Guardar Cambios
                </button>

                <a
                    href="Categorias.php"
                    class="btn-cancelar"
                >
                    Cancelar
                </a>

            </div>


        </form>

    </section>


</main>


<?php

include("../../includes/footer.php");

?>