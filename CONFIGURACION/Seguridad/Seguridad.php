<?php

$titulo = "Seguridad";
$css = "Seguridad.css";

include("../../includes/config.php");
include("../../includes/header.php");

?>

<main class="contenido">

    <!-- BARRA SUPERIOR -->

    <div class="barra-superior">

        <a href="../Configuracion/Configuracion.php" class="btn-volver">
            ← Volver a Configuración
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </div>

    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>Seguridad</h1>

        <p>
            Administre la seguridad, las sesiones y el control de acceso
            del sistema.
        </p>

    </header>

    <!-- MENSAJE DE CONFIRMACIÓN -->

    <?php if (isset($_GET["guardado"]) && $_GET["guardado"] === "1"): ?>

        <div
            id="mensaje-confirmacion"
            style="
                width:100%;
                max-width:1100px;
                margin:0 auto 25px auto;
                padding:16px 20px;
                background:#eaf8ef;
                border:1px solid #ccebd7;
                border-left:5px solid #27ae60;
                border-radius:10px;
                color:#218838;
                font-size:14px;
                text-align:center;
                box-shadow:0 3px 10px rgba(0,0,0,.06);
            "
        >

            <div
                style="
                    font-size:18px;
                    font-weight:600;
                    margin-bottom:5px;
                "
            >
                ✓ Datos actualizados
            </div>

            <div>
                Los datos de recuperación fueron actualizados correctamente.
            </div>

        </div>

        <script>
            setTimeout(function() {

                const mensaje = document.getElementById("mensaje-confirmacion");

                if (mensaje) {
                    mensaje.remove();
                }

                window.history.replaceState(
                    null,
                    "",
                    "Seguridad.php"
                );

            }, 4000);
        </script>

    <?php endif; ?>

    <!-- MENU DE SEGURIDAD -->

    <section class="menu-seguridad">

        <!-- 01 - CAMBIAR CONTRASEÑA -->

        <a href="CambiarContraseña.php"
           class="tarjeta-menu">

            <span class="numero-modulo">
                01
            </span>

            <h2>
                Cambiar Contraseña
            </h2>

            <p>
                Actualice la contraseña utilizada para acceder al sistema.
            </p>

        </a>

        <!-- 02 - SESION -->

        <a href="Sesion.php"
           class="tarjeta-menu">

            <span class="numero-modulo">
                02
            </span>

            <h2>
                Sesión
            </h2>

            <p>
                Consulte el estado de la sesión y gestione el acceso actual.
            </p>

        </a>

        <!-- 03 - COPIAS DE SEGURIDAD -->

        <a href="CopiasSeguridad.php"
           class="tarjeta-menu">

            <span class="numero-modulo">
                03
            </span>

            <h2>
                Copias de Seguridad
            </h2>

            <p>
                Administre las copias de seguridad de la información
                del sistema.
            </p>

        </a>

        <!-- 04 - ACTIVIDAD Y ACCESO -->

        <a href="ActividadAcceso.php"
           class="tarjeta-menu">

            <span class="numero-modulo">
                04
            </span>

            <h2>
                Actividad y Acceso
            </h2>

            <p>
                Consulte el registro de actividades y accesos realizados
                en el sistema.
            </p>

        </a>

        <!-- 05 - RECUPERACION DE ACCESO -->

        <a href="Recuperacion.php"
           class="tarjeta-menu">

            <span class="numero-modulo">
                05
            </span>

            <h2>
                Recuperación de Acceso
            </h2>

            <p>
                Configure el correo y teléfono utilizados para recuperar
                el acceso al sistema.
            </p>

        </a>

    </section>

</main>

<?php

include("../../includes/footer.php");

?>