<?php

/* SEGURIDAD */

include("../../includes/sesion.php");

/* CONFIGURACIÓN */

$titulo = "Información";
$css = "BaseDatos.css";

include("../../includes/config.php");
include("../../includes/conexion.php");
include("../../includes/header.php");

/* DATOS DE LA BASE DE DATOS */

$version_mysql = "No disponible";
$servidor = "localhost";
$puerto = "No disponible";
$nombre_bd = "No disponible";
$total_tablas = 0;
$estadoConexion = false;

/* CONSULTAR INFORMACIÓN */

if (isset($conexion) && $conexion && !$conexion->connect_error) {

    $estadoConexion = true;

    /* NOMBRE DE LA BASE DE DATOS */

    $consulta_bd = $conexion->query(
        "SELECT DATABASE() AS nombre_bd"
    );

    if ($consulta_bd) {

        $datos_bd = $consulta_bd->fetch_assoc();

        $nombre_bd = $datos_bd["nombre_bd"] ?? "No disponible";
    }

    /* VERSIÓN DE MYSQL */

    $consulta_version = $conexion->query(
        "SELECT VERSION() AS version"
    );

    if ($consulta_version) {

        $datos_version = $consulta_version->fetch_assoc();

        $version_mysql = $datos_version["version"] ?? "No disponible";
    }

    /* PUERTO */

    $consulta_puerto = $conexion->query(
        "SELECT @@port AS puerto"
    );

    if ($consulta_puerto) {

        $datos_puerto = $consulta_puerto->fetch_assoc();

        $puerto = $datos_puerto["puerto"] ?? "No disponible";
    }

    /* CANTIDAD DE TABLAS */

    $consulta_tablas = $conexion->query(
        "SHOW TABLES"
    );

    if ($consulta_tablas) {

        $total_tablas = $consulta_tablas->num_rows;
    }

}

?>

<main class="contenido">

    <!-- BARRA SUPERIOR -->

    <div class="barra-superior">

        <a href="BaseDatos.php" class="btn-volver">
            ← Volver a Base de Datos
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </div>

    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>Información</h1>

        <p>
            Consulte la información técnica de la base de datos
            utilizada por YCL_Solutions.
        </p>

    </header>

    <!-- INFORMACIÓN GENERAL -->

    <section class="informacion-general">

        <div class="dato-informacion">

            <span>Base de datos</span>

            <strong>
                <?= htmlspecialchars($nombre_bd) ?>
            </strong>

        </div>

        <div class="dato-informacion">

            <span>Motor</span>

            <strong>
                MySQL
            </strong>

        </div>

        <div class="dato-informacion">

            <span>Servidor</span>

            <strong>
                <?= htmlspecialchars($servidor) ?>
            </strong>

        </div>

        <div class="dato-informacion">

            <span>Puerto</span>

            <strong>
                <?= htmlspecialchars($puerto) ?>
            </strong>

        </div>

        <div class="dato-informacion">

            <span>Versión de MySQL</span>

            <strong>
                <?= htmlspecialchars($version_mysql) ?>
            </strong>

        </div>

        <div class="dato-informacion">

            <span>Total de tablas</span>

            <strong>
                <?= $total_tablas ?>
            </strong>

        </div>

        <div class="dato-informacion">

            <span>Estado</span>

            <strong class="<?= $estadoConexion ? 'texto-activo' : 'texto-inactivo' ?>">

                <?= $estadoConexion ? "Operativa" : "Sin conexión" ?>

            </strong>

        </div>

        <div class="dato-informacion">

            <span>Sistema</span>

            <strong>
                YCL_Solutions
            </strong>

        </div>

    </section>

</main>

<?php

include("../../includes/footer.php");

?>