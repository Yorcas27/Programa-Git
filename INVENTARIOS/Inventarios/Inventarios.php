
<?php

$titulo = "INVENTARIOS";
$css = "Inventarios.css";

include("../../includes/config.php");
include("../../includes/header.php");

?>

<div class="container">

    <main class="contenido">

        <div class="barra-superior">
            <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-regresar">
                ← Volver al Dashboard
            </a>
        </div>

        <header class="encabezado">
            <h1>Gestión de Inventarios</h1>
            <p>
                Control y administración de los insumos utilizados
                en producción y de los productos terminados disponibles para la venta.
            </p>
        </header>

        <section class="menu-inventarios">

            <!-- INVENTARIO GENERAL -->
            <a href="../InventarioGeneral/InventarioGeneral.php"
               class="tarjeta-menu inventario-general">
                <div class="numero-modulo">01</div>
                <h3>Inventario General</h3>
                <p>
                    Consulta el resumen consolidado de insumos
                    y productos terminados.
                </p>
            </a>

            <!-- PRODUCTOS TERMINADOS -->
            <a href="../InventariosProductosTerminados/InventariosProductosTerminados.php"
               class="tarjeta-menu productos-terminados">
                <div class="numero-modulo">02</div>
                <h3>Productos Terminados</h3>
                <p>
                    Controla las existencias comerciales por producto
                    y presentación, con stock mínimo y disponibilidad.
                </p>
            </a>

            <!-- INSUMOS -->
            <a href="../INSUMOS/Insumos.php"
               class="tarjeta-menu insumos">
                <div class="numero-modulo">03</div>
                <h3>Insumos</h3>
                <p>
                    Administra el registro, edición y control
                    de cada insumo utilizado en producción.
                </p>
            </a>

            <!-- MOVIMIENTOS DE INSUMOS -->
            <a href="../MOVIMIENTOS/Movimientos.php"
               class="tarjeta-menu movimientos">
                <div class="numero-modulo">04</div>
                <h3>Movimientos de Insumos</h3>
                <p>
                    Consulta las entradas y salidas realizadas
                    dentro del inventario de insumos.
                </p>
            </a>

            <!-- CATEGORÍAS -->
            <a href="../CATEGORIAS/Categorias.php"
               class="tarjeta-menu categorias">
                <div class="numero-modulo">05</div>
                <h3>Categorías</h3>
                <p>
                    Organiza los insumos por categorías
                    para facilitar su administración.
                </p>
            </a>

            <!-- PROVEEDORES -->
            <a href="../PROVEEDORES/Proveedores.php"
               class="tarjeta-menu proveedores">
                <div class="numero-modulo">06</div>
                <h3>Proveedores</h3>
                <p>
                    Gestiona la información de los proveedores
                    que abastecen el inventario.
                </p>
            </a>

            <!-- COMPRAS -->
            <a href="../COMPRAS/Compras.php"
               class="tarjeta-menu compras">
                <div class="numero-modulo">07</div>
                <h3>Compras</h3>
                <p>
                    Gestiona las compras de insumos realizadas
                    a los diferentes proveedores.
                </p>
            </a>

            <!-- ALERTAS DE INSUMOS -->
            <a href="../ALERTAS/Alertas.php"
               class="tarjeta-menu alertas">
                <div class="numero-modulo">08</div>
                <h3>Alertas de Stock</h3>
                <p>
                    Visualiza los insumos con existencias bajas
                    para evitar desabastecimientos.
                </p>
            </a>

        </section>

    </main>

</div>

<?php
include("../../includes/footer.php");
?>
