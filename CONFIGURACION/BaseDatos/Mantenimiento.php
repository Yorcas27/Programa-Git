<?php

/* SEGURIDAD */

include("../../includes/sesion.php");

/* CONFIGURACIÓN */

$titulo = "Mantenimiento";
$css = "BaseDatos.css";

include("../../includes/config.php");
include("../../includes/conexion.php");
include("../../includes/header.php");

/* RESULTADO DEL MANTENIMIENTO */

$resultados = [];
$mensaje = "";
$tipo_mensaje = "";

/* ACCIÓN SOLICITADA */

$accion = isset($_POST["accion"]) ? $_POST["accion"] : "";

/* EJECUTAR MANTENIMIENTO */

if ($accion != "" && isset($conexion) && $conexion && !$conexion->connect_error) {

    $tablas = $conexion->query("SHOW TABLES");

    if ($tablas) {

        while ($fila = $tablas->fetch_array()) {

            $nombre_tabla = $fila[0];

            /* REVISAR */

            if ($accion == "revisar") {

                $consulta = $conexion->query("CHECK TABLE `$nombre_tabla`");

                if ($consulta) {

                    $resultado = $consulta->fetch_assoc();

                    $resultados[] = [
                        "tabla" => $nombre_tabla,
                        "resultado" => $resultado["Msg_text"]
                    ];
                }
            }

            /* ANALIZAR */

            if ($accion == "analizar") {

                $consulta = $conexion->query("ANALYZE TABLE `$nombre_tabla`");

                if ($consulta) {

                    $resultado = $consulta->fetch_assoc();

                    $resultados[] = [
                        "tabla" => $nombre_tabla,
                        "resultado" => $resultado["Msg_text"]
                    ];
                }
            }

            /* OPTIMIZAR */

            if ($accion == "optimizar") {

                $consulta = $conexion->query("OPTIMIZE TABLE `$nombre_tabla`");

                if ($consulta) {

                    $resultado = $consulta->fetch_assoc();

                    $resultados[] = [
                        "tabla" => $nombre_tabla,
                        "resultado" => $resultado["Msg_text"]
                    ];
                }
            }
        }

        /* MENSAJES */

        if ($accion == "revisar") {

            $mensaje = "Revisión de las tablas completada.";
            $tipo_mensaje = "mensaje-exito";
        }

        if ($accion == "analizar") {

            $mensaje = "Análisis de las tablas completado.";
            $tipo_mensaje = "mensaje-exito";
        }

        if ($accion == "optimizar") {

            $mensaje = "Optimización de las tablas completada.";
            $tipo_mensaje = "mensaje-exito";
        }

    } else {

        $mensaje = "No fue posible consultar las tablas.";
        $tipo_mensaje = "mensaje-error";
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

        <h1>Mantenimiento</h1>

        <p>
            Realice tareas de revisión y mantenimiento sobre las tablas de la base de datos.
        </p>

    </header>

    <!-- ACCIONES DE MANTENIMIENTO -->

    <section class="mantenimiento">

        <!-- REVISAR -->

        <div class="accion-mantenimiento">

            <div>

                <h2>Revisar tablas</h2>

                <p>
                    Comprueba el estado de las tablas y detecta posibles problemas.
                </p>

            </div>

            <form method="POST">

                <input type="hidden" name="accion" value="revisar">

                <button type="submit" class="btn-mantenimiento">
                    Revisar
                </button>

            </form>

        </div>

        <!-- ANALIZAR -->

        <div class="accion-mantenimiento">

            <div>

                <h2>Analizar tablas</h2>

                <p>
                    Actualiza las estadísticas utilizadas por MySQL para mejorar las consultas.
                </p>

            </div>

            <form method="POST">

                <input type="hidden" name="accion" value="analizar">

                <button type="submit" class="btn-mantenimiento">
                    Analizar
                </button>

            </form>

        </div>

        <!-- OPTIMIZAR -->

        <div class="accion-mantenimiento">

            <div>

                <h2>Optimizar tablas</h2>

                <p>
                    Ejecuta tareas de optimización sobre las tablas disponibles.
                </p>

            </div>

            <form method="POST" onsubmit="return confirm('¿Está seguro de que desea optimizar las tablas de la base de datos?');">

                <input type="hidden" name="accion" value="optimizar">

                <button type="submit" class="btn-mantenimiento">
                    Optimizar
                </button>

            </form>

        </div>

    </section>

    <!-- REFRESCAR -->

    <div class="refrescar-mantenimiento">

        <a href="Mantenimiento.php" class="btn-mantenimiento">
            🔄 Refrescar
        </a>

    </div>

    <!-- MENSAJE -->

    <?php if ($mensaje != ""): ?>

        <div class="<?= htmlspecialchars($tipo_mensaje) ?>">

            <?= htmlspecialchars($mensaje) ?>

        </div>

    <?php endif; ?>

    <!-- RESULTADOS -->

    <?php if (!empty($resultados)): ?>

        <section class="resultados-mantenimiento">

            <h2>Resultado</h2>

            <table>

                <thead>

                    <tr>
                        <th>Tabla</th>
                        <th>Resultado</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($resultados as $resultado): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($resultado["tabla"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($resultado["resultado"]) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </section>

    <?php endif; ?>

</main>

<?php include("../../includes/footer.php"); ?>