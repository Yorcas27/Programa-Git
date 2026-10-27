<?php

/* SEGURIDAD */

include("../../includes/sesion.php");

/* CONFIGURACIÓN */

$titulo = "Tablas";
$css = "BaseDatos.css";

include("../../includes/config.php");
include("../../includes/conexion.php");
include("../../includes/header.php");

/* OBTENER TABLAS */

$tablas = [];

if (isset($conexion) && $conexion && !$conexion->connect_error) {

    $resultado = $conexion->query("SHOW TABLES");

    if ($resultado) {

        while ($fila = $resultado->fetch_array()) {

            $nombre_tabla = $fila[0];

            $consulta = $conexion->query(
                "SELECT COUNT(*) AS total FROM `$nombre_tabla`"
            );

            $total = 0;

            if ($consulta) {

                $datos = $consulta->fetch_assoc();
                $total = $datos["total"];

            }

            $tablas[] = [
                "nombre" => $nombre_tabla,
                "total" => $total
            ];

        }

        $resultado->free();

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

        <h1>Tablas</h1>

        <p>
            Consulte las tablas y la cantidad de registros almacenados
            en la base de datos del sistema.
        </p>

    </header>

    <!-- TABLA DE INFORMACIÓN -->

    <section class="tabla-base-datos">

        <table>

            <thead>

                <tr>
                    <th>Tabla</th>
                    <th>Registros</th>
                    <th>Estado</th>
                </tr>

            </thead>

            <tbody>

                <?php if (!empty($tablas)): ?>

                    <?php foreach ($tablas as $tabla): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($tabla["nombre"]) ?>
                            </td>

                            <td>
                                <?= number_format($tabla["total"]) ?>
                            </td>

                            <td>

                                <span class="estado-tabla">
                                    Activa
                                </span>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="3" class="sin-tablas">
                            No se encontraron tablas o no existe conexión con la base de datos.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>