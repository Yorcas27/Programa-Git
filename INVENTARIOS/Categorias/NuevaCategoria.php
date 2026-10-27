<?php

$titulo="NUEVA CATEGORÍA";
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
            Nueva Categoría
        </h1>

        <p>
            Registra una nueva categoría para clasificar los insumos del inventario.
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
                    Complete los siguientes datos.
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
                        value="CAT004"
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
                        placeholder="Ingrese el nombre de la categoría"
                        required
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
                        placeholder="Ingrese una descripción"
                    ></textarea>

                </div>


                <!-- ESTADO -->

                <div class="campo">

                    <label for="estado">
                        Estado
                    </label>

                    <select id="estado">

                        <option value="Activa">
                            Activa
                        </option>

                        <option value="Inactiva">
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
                    Guardar
                </button>


                <button
                    type="reset"
                    class="btn-limpiar"
                >
                    Limpiar
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