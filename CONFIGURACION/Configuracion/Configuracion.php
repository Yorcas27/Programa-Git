<?php

$titulo = "Configuración";
$css = "Configuracion.css";

include("../../includes/config.php");
include("../../includes/header.php");

?>

<main class="contenido">

    <!-- BOTÓN VOLVER -->

    <section class="barra-superior">

        <a href="../../Dashboard/Dashboard.php" class="btn-volver">
            ← Volver al Dashboard
        </a>

    </section>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>Configuración</h1>

        <p>
            Administre los parámetros generales, la información de la empresa
            y las opciones de funcionamiento de YCL_Solutions.
        </p>

    </header>


    <!-- OPCIONES -->

    <section class="menu-configuracion">

        <a href="../Empresa/Empresa.php" class="tarjeta-menu empresa">

            <div class="numero-modulo">01</div>

            <h3>Datos de la Empresa</h3>

            <p>
                Administra la información de la empresa utilizada
                en facturas, reportes y documentos.
            </p>

        </a>


        <a href="../Catalogo/Catalogo.php" class="tarjeta-menu catalogo">

            <div class="numero-modulo">02</div>

            <h3>Catálogo</h3>

            <p>
                Administra productos, precios y categorías
                que posteriormente se muestran en el catálogo de venta.
            </p>

        </a>


        <a href="../Seguridad/Seguridad.php" class="tarjeta-menu seguridad">

            <div class="numero-modulo">03</div>

            <h3>Seguridad</h3>

            <p>
                Administra la contraseña y las opciones
                relacionadas con la seguridad del sistema.
            </p>

        </a>


        <a href="../BaseDatos/BaseDatos.php" class="tarjeta-menu base-datos">

            <div class="numero-modulo">04</div>

            <h3>Base de Datos</h3>

            <p>
                Realiza copias de seguridad y administra
                la restauración de la base de datos.
            </p>

        </a>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>