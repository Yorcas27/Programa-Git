<?php

/* SEGURIDAD */

include("../../includes/sesion.php");

/* CONFIGURACIÓN */

$titulo = "Sesión";
$css = "Seguridad.css";

include("../../includes/config.php");
include("../../includes/header.php");

/* DATOS DE LA SESIÓN */

$usuario = $_SESSION["usuario"] ?? "";
$nombre = $_SESSION["nombre"] ?? "";
$idUsuario = $_SESSION["id_usuario"] ?? "";

?>

<main class="contenido">

    <!-- BARRA SUPERIOR -->

    <div class="barra-superior">

        <a href="Seguridad.php" class="btn-volver">
            ← Volver a Seguridad
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </div>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>Sesión</h1>

        <p>
            Consulte la información de la sesión actual y gestione el acceso al sistema.
        </p>

    </header>


    <!-- INFORMACIÓN DE SESIÓN -->

    <section class="panel">

        <div class="panel-header">

            <h2>Sesión actual</h2>

        </div>


        <div class="formulario-seguridad">

            <div class="grupo-formulario">

                <div class="campo">

                    <label>
                        Usuario
                    </label>

                    <input
                        type="text"
                        value="<?= htmlspecialchars($usuario) ?>"
                        readonly
                    >

                </div>


                <div class="campo">

                    <label>
                        Nombre
                    </label>

                    <input
                        type="text"
                        value="<?= htmlspecialchars($nombre) ?>"
                        readonly
                    >

                </div>

            </div>


            <div class="grupo-formulario">

                <div class="campo">

                    <label>
                        ID de usuario
                    </label>

                    <input
                        type="text"
                        value="<?= htmlspecialchars($idUsuario) ?>"
                        readonly
                    >

                </div>


                <div class="campo">

                    <label>
                        Estado de sesión
                    </label>

                    <div class="estado-sesion estado-activo">
                        Sesión activa
                    </div>

                </div>

            </div>


            <div class="acciones-formulario">

                <a
                    href="/YCL_Solutions/includes/cerrar_sesion.php"
                    class="btn-cancelar"
                    onclick="return confirm('¿Está seguro de que desea cerrar la sesión?');"
                >
                    Cerrar sesión
                </a>

            </div>

        </div>

    </section>

</main>


<?php

include("../../includes/footer.php");

?>