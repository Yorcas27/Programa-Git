<?php

$titulo = "Nombre del módulo";
$estilo = "nombre_css";

include("../includes/config.php");
include("../includes/header.php");

?>

<div class="container">

    <?php include("../includes/sidebar.php"); ?>

    <main class="contenido">

        <!-- ENCABEZADO -->

        <header class="encabezado">

            <h1><?= $titulo ?></h1>

            <p>
                Descripción del módulo.
            </p>

        </header>

        <!-- ACCIONES -->

        <section class="acciones">

            <!-- Botones del módulo -->

        </section>

        <!-- INDICADORES -->

        <section class="cards">

            <!-- KPIs del módulo -->

        </section>

        <!-- PANEL PRINCIPAL -->

        <section class="panel">

            <h2>Contenido</h2>

            <p>
                Aquí se desarrollará el módulo.
            </p>

        </section>

    </main>

</div>

<?php include("../includes/footer.php"); ?>