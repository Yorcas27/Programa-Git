<?php

/* SEGURIDAD */

include("../../includes/sesion.php");

/* CONFIGURACIÓN */

$titulo = "Actividad y Acceso";
$css = "Seguridad.css";

include("../../includes/config.php");
include("../../includes/conexion.php");
include("../../includes/header.php");

/* DATOS DEL USUARIO ACTUAL */

$idUsuario = (int) ($_SESSION["id_usuario"] ?? 0);

$usuario = "";
$nombre = "";
$estado = "";
$intentosFallidos = 0;
$bloqueadoHasta = null;

/* CONSULTAR USUARIO */

$sqlUsuario = "SELECT
                    usuario,
                    nombre,
                    estado,
                    intentos_fallidos,
                    bloqueado_hasta
               FROM usuarios
               WHERE id_usuario = ?
               LIMIT 1";

$stmtUsuario = $conexion->prepare($sqlUsuario);

if ($stmtUsuario) {

    $stmtUsuario->bind_param("i", $idUsuario);
    $stmtUsuario->execute();

    $resultadoUsuario = $stmtUsuario->get_result();

    if ($resultadoUsuario->num_rows === 1) {

        $datosUsuario = $resultadoUsuario->fetch_assoc();

        $usuario = $datosUsuario["usuario"];
        $nombre = $datosUsuario["nombre"];
        $estado = $datosUsuario["estado"];
        $intentosFallidos = (int) $datosUsuario["intentos_fallidos"];
        $bloqueadoHasta = $datosUsuario["bloqueado_hasta"];
    }

    $stmtUsuario->close();
}

/* ESTADO DE BLOQUEO */

$estaBloqueado = false;

if (!empty($bloqueadoHasta)) {

    $estaBloqueado = strtotime($bloqueadoHasta) > time();
}

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

        <h1>Actividad y Acceso</h1>

        <p>
            Consulte el estado de acceso y la información
            de seguridad de la sesión actual.
        </p>

    </header>

    <!-- RESUMEN -->

    <section class="cards">

        <div class="card">

            <h2>1</h2>

            <span>
                Sesión actual
            </span>

        </div>

        <div class="card">

            <h2>
                <?= $intentosFallidos ?>
            </h2>

            <span>
                Intentos fallidos
            </span>

        </div>

        <div class="card">

            <h2>
                <?= htmlspecialchars($estado) ?>
            </h2>

            <span>
                Estado del usuario
            </span>

        </div>

        <div class="card">

            <h2>
                <?= $estaBloqueado ? "Bloqueado" : "Disponible" ?>
            </h2>

            <span>
                Estado de acceso
            </span>

        </div>

    </section>

    <!-- INFORMACIÓN DE ACCESO -->

    <section class="panel">

        <div class="panel-header">

            <h2>Información de acceso</h2>

            <p>
                Información correspondiente al usuario
                actualmente autenticado.
            </p>

        </div>

        <div class="grupo-formulario">

            <div class="campo">

                <label>
                    Usuario
                </label>

                <div class="campo-info">
                    <?= htmlspecialchars($usuario) ?>
                </div>

            </div>

            <div class="campo">

                <label>
                    Nombre
                </label>

                <div class="campo-info">
                    <?= htmlspecialchars($nombre) ?>
                </div>

            </div>

            <div class="campo">

                <label>
                    Estado de la cuenta
                </label>

                <div class="campo-info estado-sesion">
                    <?= htmlspecialchars($estado) ?>
                </div>

            </div>

            <div class="campo">

                <label>
                    Estado de acceso
                </label>

                <div class="campo-info <?= $estaBloqueado ? '' : 'estado-sesion' ?>">

                    <?php if ($estaBloqueado): ?>

                        Bloqueado hasta
                        <?= date("d/m/Y H:i", strtotime($bloqueadoHasta)) ?>

                    <?php else: ?>

                        Acceso disponible

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </section>

    <!-- SESIÓN ACTUAL -->

    <section class="panel">

        <div class="panel-header">

            <h2>Sesión actual</h2>

            <p>
                Información de la sesión activa en este momento.
            </p>

        </div>

        <div class="grupo-formulario">

            <div class="campo">

                <label>
                    Estado
                </label>

                <div class="campo-info estado-sesion">
                    Sesión activa
                </div>

            </div>

            <div class="campo">

                <label>
                    Tiempo máximo de inactividad
                </label>

                <div class="campo-info">
                    30 minutos
                </div>

            </div>

            <div class="campo">

                <label>
                    Última actividad
                </label>

                <div class="campo-info">

                    <?php

                    if (isset($_SESSION["ultima_actividad"])) {

                        echo date(
                            "d/m/Y H:i:s",
                            $_SESSION["ultima_actividad"]
                        );

                    } else {

                        echo "No disponible";

                    }

                    ?>

                </div>

            </div>

        </div>

    </section>

    <!-- INTENTOS DE ACCESO -->

    <section class="panel">

        <div class="panel-header">

            <h2>Control de acceso</h2>

            <p>
                Información relacionada con los intentos
                de autenticación de la cuenta.
            </p>

        </div>

        <div class="grupo-formulario">

            <div class="campo">

                <label>
                    Intentos fallidos actuales
                </label>

                <div class="campo-info">
                    <?= $intentosFallidos ?>
                </div>

            </div>

            <div class="campo">

                <label>
                    Límite de intentos
                </label>

                <div class="campo-info">
                    5 intentos
                </div>

            </div>

            <div class="campo">

                <label>
                    Tiempo de bloqueo
                </label>

                <div class="campo-info">
                    15 minutos
                </div>

            </div>

            <div class="campo">

                <label>
                    Estado de protección
                </label>

                <div class="campo-info estado-sesion">
                    Protección contra intentos fallidos activa
                </div>

            </div>

        </div>

    </section>

    <!-- REGISTRO HISTÓRICO -->

    <section class="panel">

        <div class="panel-header">

            <h2>Registro histórico</h2>

            <p>
                El registro detallado de actividades se podrá
                habilitar mediante un historial de auditoría.
            </p>

        </div>

        <div class="campo-info">

            Actualmente el sistema muestra el estado de acceso
            y seguridad de la cuenta. El historial detallado
            de acciones se implementará posteriormente mediante
            un registro de auditoría.

        </div>

    </section>

</main>

<?php

include("../../includes/footer.php");

?>