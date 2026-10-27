<?php

/* SEGURIDAD */

include("../../includes/sesion.php");

/* CONFIGURACIÓN */

$titulo = "Base de Datos";
$css = "BaseDatos.css";

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

        <h1>Base de Datos</h1>

        <p>
            Administración y consulta de la información almacenada
            en la base de datos de YCL_Solutions.
        </p>

    </header>

    <!-- OPCIONES -->

    <section class="menu-base-datos">

        <!-- ESTADO -->

        <a href="EstadoBaseDatos.php" class="boton-base-datos">

            <span class="icono">🗄️</span>

            <div>

                <h2>Estado de la Base de Datos</h2>

                <p>
                    Consulte el estado de la conexión y la base de datos utilizada.
                </p>

            </div>

        </a>

        <!-- TABLAS -->

        <a href="Tablas.php" class="boton-base-datos">

            <span class="icono">📋</span>

            <div>

                <h2>Tablas</h2>

                <p>
                    Consulte las tablas y la información almacenada en el sistema.
                </p>

            </div>

        </a>

        <!-- MANTENIMIENTO -->

        <a href="Mantenimiento.php" class="boton-base-datos">

            <span class="icono">🔧</span>

            <div>

                <h2>Mantenimiento</h2>

                <p>
                    Realice tareas básicas de revisión y mantenimiento.
                </p>

            </div>

        </a>

    </section>

</main>

<?php

include("../../includes/footer.php");

?>