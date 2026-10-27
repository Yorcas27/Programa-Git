<?php

$titulo = "Reportes";
$css = "Reportes.css";

include("../includes/config.php");
include("../includes/header.php");

?>

<main class="contenido">
    <div class="barra-superior">

    <a href="../Dashboard/Dashboard.php" class="btn-regresar">
        ← Volver al Dashboard
    </a>

</div>

    <header class="encabezado">

        <h1>Reportes</h1>

        <p>
            Consulta y generación de reportes del sistema.
        </p>

    </header>


    <!-- TARJETAS -->

    <section class="cards">

        <div class="card">

            <h2>Producción</h2>

            <span>
                Reporte de producción registrada.
            </span>

        </div>


        <div class="card">

            <h2>Inventarios</h2>

            <span>
                Reporte de existencias y movimientos.
            </span>

        </div>


        <div class="card">

            <h2>Pedidos</h2>

            <span>
                Reporte de pedidos gestionados.
            </span>

        </div>


        <div class="card">

            <h2>Facturación</h2>

            <span>
                Reporte de facturas emitidas.
            </span>

        </div>

    </section>


    <!-- PANEL -->

    <section class="panel">

        <div class="panel-header">

            <h2>Reportes Disponibles</h2>

        </div>


        <table>

            <thead>

                <tr>

                    <th>Reporte</th>
                    <th>Descripción</th>
                    <th>Acción</th>

                </tr>

            </thead>


            <tbody>

                <tr>

                    <td>Producción</td>

                    <td>
                        Producción registrada en el sistema.
                    </td>

                    <td>

                        <a href="Produccion/ReporteProduccion.php" class="btn-ver">
                            Ver
                        </a>

                    </td>

                </tr>


                <tr>

                    <td>Inventarios</td>

                    <td>
                        Control de existencias y movimientos.
                    </td>

                    <td>

                        <a href="Inventario/ReporteInventario.php" class="btn-ver">
                            Ver
                        </a>

                    </td>

                </tr>


                <tr>

                    <td>Pedidos</td>

                    <td>
                        Pedidos registrados y estados.
                    </td>

                    <td>

                        <a href="Pedidos/ReportePedidos.php" class="btn-ver">
                            Ver
                        </a>

                    </td>

                </tr>


                <tr>

                    <td>Facturación</td>

                    <td>
                        Facturas generadas por el sistema.
                    </td>

                    <td>

                        <a href="Facturacion/ReporteFacturacion.php" class="btn-ver">
                            Ver
                        </a>

                    </td>

                </tr>

            </tbody>

        </table>

    </section>

</main>


<?php include("../includes/footer.php"); ?>