<?php

$titulo = "Catálogo";
$css = "Catalogo.css";

include("../../includes/config.php");
include("../../includes/header.php");

?>

<main class="contenido">

    <!-- BOTÓN VOLVER -->

    <section class="barra-superior">

        <a href="../Configuracion/Configuracion.php" class="btn-volver">
            ← Volver a Configuración
        </a>
        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
        ⌂ Inicio
    </a>

    </section>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>Catálogo</h1>

        <p>
            Administre los productos y categorías disponibles
            en el catálogo de venta.
        </p>

    </header>


    <!-- OPCIONES DEL CATÁLOGO -->

    <section class="menu-catalogo">


        <!-- PRODUCTOS -->

        <a href="../Productos/Productos.php" class="tarjeta-menu productos">

            <div class="numero-modulo">
                01
            </div>

            <h3>
                Productos
            </h3>

            <p>
                Administre productos, precios, imágenes,
                descripciones y estado.
            </p>

        </a>


        <!-- CATEGORÍAS -->

        <a href="../Categorias/Categorias.php" class="tarjeta-menu categorias">

            <div class="numero-modulo">
                02
            </div>

            <h3>
                Categorías
            </h3>

            <p>
                Administre las categorías utilizadas
                para organizar los productos.
            </p>

        </a>


    </section>

</main>

<?php include("../../includes/footer.php"); ?>