<?php

$titulo = "Cambiar Contraseña";
$css = "Seguridad.css";

include("../../includes/config.php");
include("../../includes/header.php");

?>

<main class="contenido">

    <!-- BARRA SUPERIOR -->

    <div class="barra-superior">

        <a href="Seguridad.php" class="btn-volver">
            ← Volver a Seguridad
        </a>

    </div>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>Cambiar Contraseña</h1>

        <p>
            Actualice la contraseña utilizada para acceder al sistema.
        </p>

    </header>


    <!-- FORMULARIO -->

    <section class="formulario-seguridad">

        <h2>Seguridad de la cuenta</h2>

        <p>
            Ingrese su contraseña actual y registre una nueva.
        </p>


        <form>

            <div class="grupo-formulario">

                <div class="campo">

                    <label for="actual">
                        Contraseña actual
                    </label>

                    <input
                        type="password"
                        id="actual"
                        name="actual"
                        placeholder="Ingrese su contraseña actual"
                    >

                </div>


                <div class="campo">

                    <label for="nueva">
                        Nueva contraseña
                    </label>

                    <input
                        type="password"
                        id="nueva"
                        name="nueva"
                        placeholder="Ingrese la nueva contraseña"
                    >

                </div>


                <div class="campo">

                    <label for="confirmar">
                        Confirmar nueva contraseña
                    </label>

                    <input
                        type="password"
                        id="confirmar"
                        name="confirmar"
                        placeholder="Confirme la nueva contraseña"
                    >

                </div>

            </div>


            <!-- BOTONES -->

            <div class="acciones-formulario">

                <a href="Seguridad.php" class="btn-cancelar">
                    Cancelar
                </a>

                <button type="submit" class="btn-guardar">
                    Guardar Contraseña
                </button>

            </div>

        </form>

    </section>

</main>


<?php

include("../../includes/footer.php");

?>