<?php

/* SEGURIDAD */

include("../../includes/sesion.php");

/* CONFIGURACIÓN */

$titulo = "Estado de la Base de Datos";
$css = "BaseDatos.css";

include("../../includes/config.php");
include("../../includes/conexion.php");
include("../../includes/header.php");

/* DATOS DE LA CONEXIÓN */

$conexion_activa = false;
$mensaje = "Sin conexión";

if (isset($conexion) && $conexion && !$conexion->connect_error) {

    $conexion_activa = true;
    $mensaje = "Conectada";

}

/* DATOS REALES */

$baseDatos = $conexion->query("SELECT DATABASE() AS base_datos");
$datosBase = $baseDatos ? $baseDatos->fetch_assoc() : null;

$nombreBaseDatos = $datosBase["base_datos"] ?? "No disponible";

$version = $conexion->query("SELECT VERSION() AS version");
$datosVersion = $version ? $version->fetch_assoc() : null;

$versionMySQL = $datosVersion["version"] ?? "No disponible";

$puerto = $conexion->query("SELECT @@port AS puerto");
$datosPuerto = $puerto ? $puerto->fetch_assoc() : null;

$puertoMySQL = $datosPuerto["puerto"] ?? "No disponible";

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

        <h1>Estado de la Base de Datos</h1>

        <p>
            Consulte el estado actual de la conexión con el servidor
            y la base de datos del sistema.
        </p>

    </header>

    <!-- ESTADO DE CONEXIÓN -->

    <section class="estado-conexion">

        <h2>Estado de conexión</h2>

        <div class="<?= $conexion_activa ? 'estado-conectado' : 'estado-desconectado' ?>">

            <span class="indicador"></span>

            <span>
                <?= htmlspecialchars($mensaje) ?>
            </span>

        </div>

        <p>
            <?= $conexion_activa
                ? "La aplicación se encuentra conectada correctamente a la base de datos."
                : "No fue posible establecer conexión con la base de datos."
            ?>
        </p>

    </section>

    <!-- INFORMACIÓN -->

    <section class="informacion-db">

        <div class="dato-db">

            <span>Base de datos</span>

            <strong>
                <?= htmlspecialchars($nombreBaseDatos) ?>
            </strong>

        </div>

        <div class="dato-db">

            <span>Servidor</span>

            <strong>
                MySQL
            </strong>

        </div>

        <div class="dato-db">

            <span>Host</span>

            <strong>
                localhost
            </strong>

        </div>

        <div class="dato-db">

            <span>Puerto</span>

            <strong>
                <?= htmlspecialchars($puertoMySQL) ?>
            </strong>

        </div>

        <div class="dato-db">

            <span>Versión</span>

            <strong>
                <?= htmlspecialchars($versionMySQL) ?>
            </strong>

        </div>

        <div class="dato-db">

            <span>Estado</span>

            <strong class="<?= $conexion_activa ? 'texto-activo' : 'texto-inactivo' ?>">

                <?= $conexion_activa ? "Operativa" : "No disponible" ?>

            </strong>

        </div>

        <div class="dato-db">

            <span>Conexión</span>

            <strong>
                <?= $conexion_activa ? "Activa" : "Inactiva" ?>
            </strong>

        </div>

    </section>

</main>

<?php

include("../../includes/footer.php");

?>