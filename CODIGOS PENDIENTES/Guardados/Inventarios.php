<?php

$titulo = "INVENTARIOS";
$css = "Inventarios.css";

include("../../includes/config.php");
include("../../includes/header.php");

?>

<div class="container">

    <main class="contenido">

        <!-- BARRA SUPERIOR -->

        <div class="barra-superior">

            <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-regresar">
                ← Volver al Dashboard
            </a>

        </div>

        <!-- ENCABEZADO -->

        <header class="encabezado">

            <h1>
                Gestión de Inventarios
            </h1>

            <p>
                Control y administración de los insumos utilizados
                durante el proceso productivo de YCL_Solutions.
            </p>

        </header>

        <!-- MÓDULOS DEL INVENTARIO -->

        <section class="menu-inventarios">

            <!-- INVENTARIO GENERAL -->

            <a href="../InventarioGeneral/InventarioGeneral.php"
               class="tarjeta-menu inventario-general">

                <div class="numero-modulo">
                    01
                </div>

                <h3>
                    Inventario General
                </h3>

                <p>
                    Consulta el inventario completo y el estado actual
                    de todos los insumos registrados.
                </p>

            </a>

            <!-- INSUMOS -->

            <a href="../INSUMOS/Insumos.php"
               class="tarjeta-menu insumos">

                <div class="numero-modulo">
                    02
                </div>

                <h3>
                    Insumos
                </h3>

                <p>
                    Administra el registro, edición y control
                    de cada insumo utilizado en producción.
                </p>

            </a>

            <!-- MOVIMIENTOS -->

            <a href="../MOVIMIENTOS/Movimientos.php"
               class="tarjeta-menu movimientos">

                <div class="numero-modulo">
                    03
                </div>

                <h3>
                    Movimientos
                </h3>

                <p>
                    Consulta todas las entradas y salidas
                    realizadas dentro del inventario.
                </p>

            </a>

            <!-- CATEGORÍAS -->

            <a href="../CATEGORIAS/Categorias.php"
               class="tarjeta-menu categorias">

                <div class="numero-modulo">
                    04
                </div>

                <h3>
                    Categorías
                </h3>

                <p>
                    Organiza los insumos por categorías
                    para facilitar su administración.
                </p>

            </a>

            <!-- PROVEEDORES -->

            <a href="../PROVEEDORES/Proveedores.php"
               class="tarjeta-menu proveedores">

                <div class="numero-modulo">
                    05
                </div>

                <h3>
                    Proveedores
                </h3>

                <p>
                    Gestiona la información de los proveedores
                    que abastecen el inventario.
                </p>

            </a>

            <!-- COMPRAS -->

            <a href="../COMPRAS/Compras.php"
               class="tarjeta-menu compras">

                <div class="numero-modulo">
                    06
                </div>

                <h3>
                    Compras
                </h3>

                <p>
                    Gestiona las compras de insumos realizadas
                    a los diferentes proveedores.
                </p>

            </a>

            <!-- ALERTAS DE STOCK -->

            <a href="../ALERTAS/Alertas.php"
               class="tarjeta-menu alertas">

                <div class="numero-modulo">
                    07
                </div>

                <h3>
                    Alertas de Stock
                </h3>

                <p>
                    Visualiza los productos con existencias
                    bajas para evitar desabastecimientos.
                </p>

            </a>

        </section>

    </main>

</div>