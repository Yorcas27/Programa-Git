<?php

/* SEGURIDAD */

include("../../includes/sesion.php");

/* CONFIGURACIÓN */

$titulo = "Copias de Seguridad";
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

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </div>

    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>Copias de Seguridad</h1>

        <p>
            Consulte y gestione el proceso de respaldo
            de la base de datos del sistema.
        </p>

    </header>

    <!-- INFORMACIÓN DEL RESPALDO -->

    <section class="panel">

        <div class="panel-header">

            <h2>Información del respaldo</h2>

            <p>
                La copia de seguridad de la base de datos
                se realiza mediante MySQL Workbench.
            </p>

        </div>

        <div class="grupo-formulario">

            <div class="campo">

                <label>
                    Base de datos
                </label>

                <div class="campo-info">
                    ycl_solutions_db
                </div>

            </div>

            <div class="campo">

                <label>
                    Método de respaldo
                </label>

                <div class="campo-info">
                    MySQL Workbench
                </div>

            </div>

            <div class="campo">

                <label>
                    Tipo de copia
                </label>

                <div class="campo-info">
                    Base de datos completa
                </div>

            </div>

            <div class="campo">

                <label>
                    Estado
                </label>

                <div class="campo-info estado-sesion">
                    Respaldo manual
                </div>

            </div>

        </div>

    </section>

    <!-- INSTRUCCIONES -->

    <section class="panel">

        <div class="panel-header">

            <h2>Cómo realizar una copia de seguridad</h2>

            <p>
                Utilice MySQL Workbench para exportar la base
                de datos completa.
            </p>

        </div>

        <div class="formulario-seguridad">

            <div class="campo">

                <label>
                    1. Abrir MySQL Workbench
                </label>

                <div class="campo-info">
                    Inicie MySQL Workbench y conéctese al servidor
                    MySQL utilizado por YCL Solutions.
                </div>

            </div>

            <div class="campo">

                <label>
                    2. Seleccionar la base de datos
                </label>

                <div class="campo-info">
                    Seleccione la base de datos
                    <strong>ycl_solutions_db</strong>.
                </div>

            </div>

            <div class="campo">

                <label>
                    3. Abrir la exportación
                </label>

                <div class="campo-info">
                    Diríjase a Server → Data Export.
                </div>

            </div>

            <div class="campo">

                <label>
                    4. Seleccionar las tablas
                </label>

                <div class="campo-info">
                    Seleccione las tablas que desea respaldar
                    o utilice la opción para seleccionar toda
                    la base de datos.
                </div>

            </div>

            <div class="campo">

                <label>
                    5. Elegir ubicación
                </label>

                <div class="campo-info">
                    Seleccione una carpeta segura para guardar
                    el archivo de respaldo.
                </div>

            </div>

            <div class="campo">

                <label>
                    6. Ejecutar la exportación
                </label>

                <div class="campo-info">
                    Inicie la exportación y espere a que
                    MySQL Workbench finalice el proceso.
                </div>

            </div>

        </div>

    </section>

    <!-- RECOMENDACIÓN -->

    <section class="panel">

        <div class="panel-header">

            <h2>Recomendación</h2>

        </div>

        <div class="campo-info">

            Realice copias de seguridad periódicamente y
            conserve los archivos en una ubicación segura
            diferente al equipo donde se encuentra instalada
            la aplicación.

        </div>

    </section>

</main>

<?php

include("../../includes/footer.php");

?>