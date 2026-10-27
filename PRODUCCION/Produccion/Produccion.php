<?php

$titulo = "PRODUCCION";
$css = "Produccion.css";

include("../../includes/config.php");
include("../../includes/header.php");

?>

<div class="container">

<main class="contenido">

    <!-- BARRA SUPERIOR -->

    <div class="barra-superior">

        <a href="../../DASHBOARD/Dashboard.php" class="btn btn-volver">
            ← Volver al Dashboard
        </a>

    </div>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1><?= $titulo ?></h1>

        <p>
            Gestión y seguimiento del proceso productivo de YCL_Solutions.
            Seleccione una opción para administrar las órdenes, productos
            terminados, recetas o consultar el historial de producción.
        </p>

    </header>


    <!-- MÓDULOS DE PRODUCCIÓN -->

    <section class="menu-produccion">


        <!-- 01 - ÓRDENES DE PRODUCCIÓN -->

        <a href="../OrdenesProduccion/OrdenesProduccion.php"
           class="tarjeta-menu ordenes">

            <div class="numero-modulo">
                01
            </div>

            <h3>
                Órdenes de Producción
            </h3>

            <p>
                Registre y administre las órdenes de fabricación de productos.
            </p>

        </a>


        <!-- 02 - PRODUCTOS TERMINADOS -->

        <a href="../ProductosTerminados/ProductosTerminados.php"
           class="tarjeta-menu productos">

            <div class="numero-modulo">
                02
            </div>

            <h3>
                Productos Terminados
            </h3>

            <p>
                Consulte y administre los productos terminados disponibles.
            </p>

        </a>


        <!-- 03 - RECETAS -->

        <a href="../Recetas/Recetas.php"
           class="tarjeta-menu recetas">

            <div class="numero-modulo">
                03
            </div>

            <h3>
                Recetas
            </h3>

            <p>
                Configure las recetas y el consumo de insumos por producto.
            </p>

        </a>


        <!-- 04 - HISTORIAL DE PRODUCCIÓN -->

        <a href="../HistorialProduccion/Historial.php"
           class="tarjeta-menu historial">

            <div class="numero-modulo">
                04
            </div>

            <h3>
                Historial de Producción
            </h3>

            <p>
                Consulte el historial y seguimiento de las órdenes ejecutadas.
            </p>

        </a>


    </section>

</main>
```

</div>

<?php

include("../../includes/footer.php");

?>
