<?php

require_once("../../includes/config.php");
require_once("../../includes/conexion.php");
require_once("../../vendor/autoload.php");

use Dompdf\Dompdf;
use Dompdf\Options;


/* FILTROS */

$desde = $_GET["desde"] ?? "";
$hasta = $_GET["hasta"] ?? "";
$estado = $_GET["estado"] ?? "Todos";


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


/* EJECUTAR */

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("No fue posible preparar la consulta.");
}

if (!empty($parametros)) {

    $stmt->bind_param(
        $tipos,
        ...$parametros
    );
}

if (!$stmt->execute()) {
    die("No fue posible consultar las órdenes de producción.");
}

$resultado = $stmt->get_result();


/* DOMPDF */

$options = new Options();
$options->set("isRemoteEnabled", true);
$options->set("defaultFont", "DejaVu Sans");

$dompdf = new Dompdf($options);


/* HTML */

$html = '
<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<style>

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11px;
    color: #333;
    margin: 30px;
}

.encabezado {
    text-align: center;
    margin-bottom: 25px;
}

.encabezado h1 {
    color: #1f3f6d;
    font-size: 22px;
    margin: 0 0 6px 0;
}

.encabezado p {
    margin: 0;
    color: #666;
    font-size: 11px;
}

.informacion {
    margin-bottom: 20px;
    padding: 12px;
    border: 1px solid #ddd;
    background-color: #f4f6f9;
}

.informacion table {
    width: 100%;
    border-collapse: collapse;
}

.informacion td {
    padding: 4px;
}

.etiqueta {
    font-weight: bold;
    color: #1f3f6d;
}

.resultados {
    margin-top: 10px;
}

.resultados table {
    width: 100%;
    border-collapse: collapse;
}

.resultados th {
    background-color: #1f3f6d;
    color: white;
    padding: 8px;
    text-align: left;
    font-size: 10px;
}

.resultados td {
    border: 1px solid #ddd;
    padding: 7px;
    font-size: 10px;
}

.resultados tr:nth-child(even) {
    background-color: #f8f9fa;
}

.total {
    margin-top: 12px;
    text-align: right;
    font-weight: bold;
    color: #1f3f6d;
}

.pie {
    margin-top: 25px;
    text-align: center;
    color: #777;
    font-size: 9px;
}

</style>

</head>

<body>

<div class="encabezado">

    <h1>YCL SOLUTIONS</h1>

    <p>Reporte de Producción</p>

</div>

<div class="informacion">

    <table>

        <tr>

            <td class="etiqueta">Desde:</td>

            <td>
                ' . ($desde !== "" ? date("d/m/Y", strtotime($desde)) : "Todas") . '
            </td>

            <td class="etiqueta">Hasta:</td>

            <td>
                ' . ($hasta !== "" ? date("d/m/Y", strtotime($hasta)) : "Todas") . '
            </td>

        </tr>

        <tr>

            <td class="etiqueta">Estado:</td>

            <td colspan="3">
                ' . htmlspecialchars($estado) . '
            </td>

        </tr>

    </table>

</div>

<div class="resultados">

<table>

<thead>

<tr>

    <th>Orden</th>
    <th>Producto</th>
    <th>Fecha</th>
    <th>Cantidad</th>
    <th>Estado</th>

</tr>

</thead>

<tbody>
';


$totalRegistros = 0;

while ($orden = $resultado->fetch_assoc()) {

    $totalRegistros++;

    $html .= '
    <tr>

        <td>
            ' . htmlspecialchars($orden["codigo"]) . '
        </td>

        <td>
            ' . htmlspecialchars($orden["producto"]) . '
        </td>

        <td>
            ' . date("d/m/Y", strtotime($orden["fecha"])) . '
        </td>

        <td>
            ' . htmlspecialchars($orden["cantidad"]) . '
        </td>

        <td>
            ' . htmlspecialchars($orden["estado"]) . '
        </td>

    </tr>
    ';
}


if ($totalRegistros === 0) {

    $html .= '
    <tr>

        <td colspan="5" style="text-align:center;">
            No se encontraron órdenes de producción.
        </td>

    </tr>
    ';
}


$html .= '

</tbody>

</table>

</div>

<div class="total">
    Total de órdenes: ' . $totalRegistros . '
</div>

<div class="pie">
    YCL Solutions - Reporte generado automáticamente
</div>

</body>

</html>
';


/* GENERAR PDF */

$dompdf->loadHtml($html);

$dompdf->setPaper("A4", "landscape");

$dompdf->render();

$dompdf->stream(
    "Reporte_Produccion.pdf",
    [
        "Attachment" => true
    ]
);

exit;