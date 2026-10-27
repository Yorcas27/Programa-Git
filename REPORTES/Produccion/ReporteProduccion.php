<?php

$titulo = "Reporte de Producción";
$css = "../Reportes.css";

include("../../includes/config.php");
include("../../includes/conexion.php");
include("../../includes/header.php");

$desde = $_GET["desde"] ?? "";
$hasta = $_GET["hasta"] ?? "";
$estado = $_GET["estado"] ?? "Todos";

$error = "";


/* VALIDAR FECHAS */

if ($desde !== "" && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $desde)) {
    $desde = "";
}

if ($hasta !== "" && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $hasta)) {
    $hasta = "";
}


/* VALIDAR ESTADO */

$estadosPermitidos = [
    "Todos",
    "Pendiente",
    "En Producción",
    "Finalizada"
];

if (!in_array($estado, $estadosPermitidos, true)) {
    $estado = "Todos";
}


/* CONSULTA */

$sql = "
    SELECT
        o.id_orden,
        o.codigo,
        o.fecha,
        o.cantidad,
        o.estado,
        p.nombre AS producto
    FROM ordenes_produccion o
    INNER JOIN productos p
        ON o.id_producto = p.id_producto
    WHERE 1=1
";

$tipos = "";
$parametros = [];


/* FILTRO DESDE */

if ($desde !== "") {

    $sql .= " AND o.fecha >= ?";
    $tipos .= "s";
    $parametros[] = $desde;
}


/* FILTRO HASTA */

if ($hasta !== "") {

    $sql .= " AND o.fecha < DATE_ADD(?, INTERVAL 1 DAY)";
    $tipos .= "s";
    $parametros[] = $hasta;
}


/* FILTRO ESTADO */

if ($estado !== "Todos") {

    $sql .= " AND o.estado = ?";
    $tipos .= "s";
    $parametros[] = $estado;
}


$sql .= "
    ORDER BY o.fecha DESC, o.id_orden DESC
";


/* EJECUTAR CONSULTA */

$stmt = $conexion->prepare($sql);

if (!$stmt) {

    $error = "No fue posible preparar la consulta del reporte.";

} else {

    if (!empty($parametros)) {

        $stmt->bind_param(
            $tipos,
            ...$parametros
        );
    }

    if (!$stmt->execute()) {

        $error = "No fue posible consultar las órdenes de producción.";

    } else {

        $resultado = $stmt->get_result();
    }
}

?>

<main class="contenido-interno">

    <!-- BOTONES SUPERIORES -->

    <div class="barra-superior">

        <a href="../Reportes.php" class="btn-regresar">
            ← Volver a Reportes
        </a>

        <a href="../../DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </div>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>Reporte de Producción</h1>

        <p>
            Consulta y seguimiento de las órdenes de producción registradas.
        </p>

    </header>


    <!-- FILTROS -->

    <section class="panel">

        <div class="panel-header">

            <h2>Filtros de Consulta</h2>

        </div>


        <form
            class="formulario"
            method="GET"
            action="ReporteProduccion.php"
        >

            <div class="grupo-formulario">

                <!-- DESDE -->

                <div class="campo">

                    <label for="desde">
                        Desde
                    </label>

                    <input
                        type="date"
                        id="desde"
                        name="desde"
                        value="<?= htmlspecialchars($desde) ?>"
                    >

                </div>


                <!-- HASTA -->

                <div class="campo">

                    <label for="hasta">
                        Hasta
                    </label>

                    <input
                        type="date"
                        id="hasta"
                        name="hasta"
                        value="<?= htmlspecialchars($hasta) ?>"
                    >

                </div>


                <!-- ESTADO -->

                <div class="campo">

                    <label for="estado">
                        Estado
                    </label>

                    <select
                        id="estado"
                        name="estado"
                    >

                        <option
                            value="Todos"
                            <?= $estado === "Todos" ? "selected" : "" ?>
                        >
                            Todos
                        </option>

                        <option
                            value="Pendiente"
                            <?= $estado === "Pendiente" ? "selected" : "" ?>
                        >
                            Pendiente
                        </option>

                        <option
                            value="En Producción"
                            <?= $estado === "En Producción" ? "selected" : "" ?>
                        >
                            En Producción
                        </option>

                        <option
                            value="Finalizada"
                            <?= $estado === "Finalizada" ? "selected" : "" ?>
                        >
                            Finalizada
                        </option>

                    </select>

                </div>

            </div>


            <div class="acciones-formulario">

                <button
                    type="submit"
                    class="btn-consultar"
                >
                    Consultar
                </button>

                <a
                    href="ReporteProduccion.php"
                    class="btn-consultar"
                >
                    Refrescar
                </a>

            </div>

        </form>

    </section>


    <!-- RESULTADOS -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>Resultados</h2>

                <p>
                    Órdenes de producción encontradas.
                </p>

            </div>

        </div>


        <?php if ($error !== ""): ?>

            <div class="mensaje-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php elseif ($resultado->num_rows === 0): ?>

            <div class="mensaje-error">
                No se encontraron órdenes de producción con los filtros seleccionados.
            </div>

        <?php else: ?>

            <table>

                <thead>

                    <tr>

                        <th>Orden</th>
                        <th>Producto</th>
                        <th>Fecha</th>
                        <th>Cantidad</th>
                        <th>Estado</th>
                        <th>Acción</th>

                    </tr>

                </thead>


                <tbody>

                    <?php while ($orden = $resultado->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($orden["codigo"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($orden["producto"]) ?>
                            </td>

                            <td>
                                <?= date(
                                    "d/m/Y",
                                    strtotime($orden["fecha"])
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($orden["cantidad"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($orden["estado"]) ?>
                            </td>

                            <td>

                                <a
                                    href="../../PRODUCCION/OrdenesProduccion/VerOrden.php?id=<?= (int)$orden["id_orden"] ?>"
                                    class="btn-ver"
                                >
                                    Ver
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        <?php endif; ?>


        <!-- EXPORTACIONES -->

        <div class="acciones-formulario">

            <a
                href="ExportarProduccionPDF.php?desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&estado=<?= urlencode($estado) ?>"
                class="btn-exportar"
                onclick="return confirmarExportacion('PDF');"
            >
                Exportar PDF
            </a>

            <a
                href="ExportarProduccionExcel.php?desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&estado=<?= urlencode($estado) ?>"
                class="btn-exportar"
                onclick="return confirmarExportacion('Excel');"
            >
                Exportar Excel
            </a>

        </div>

    </section>

</main>


<script>

function confirmarExportacion(formato){

    return confirm(
        "¿Deseas exportar el reporte de producción en " + formato + "?"
    );

}

</script>


<?php include("../../includes/footer.php"); ?>