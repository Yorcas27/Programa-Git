<?php

require_once("../../includes/config.php");
require_once("../../includes/conexion.php");
require_once("../../vendor/autoload.php");

use Dompdf\Dompdf;
use Dompdf\Options;


/* FILTROS */

$buscar = trim($_GET["buscar"] ?? "");
$desde = $_GET["desde"] ?? "";
$hasta = $_GET["hasta"] ?? "";
$estado = $_GET["estado"] ?? "";


/* VALIDAR FECHAS */

if ($desde !== "" && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $desde)) {
    $desde = "";
}

if ($hasta !== "" && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $hasta)) {
    $hasta = "";
}


/* VALIDAR ESTADO */

$estadosPermitidos = [
    "",
    "Pendiente",
    "En Producción",
    "Entregado",
    "Cancelado"
];

if (!in_array($estado, $estadosPermitidos, true)) {
    $estado = "";
}


/* CONSULTA */

$sql = "
    SELECT
        p.id_pedido,
        p.codigo_pedido,
        p.fecha,
        p.fecha_entrega,
        p.subtotal,
        p.iva,
        p.total,
        p.estado,
        COALESCE(c.nombre, p.nombre_cliente) AS cliente
    FROM pedidos p
    LEFT JOIN clientes c
        ON p.id_cliente = c.id_cliente
    WHERE 1=1
";

$tipos = "";
$parametros = [];


/* BUSQUEDA */

if ($buscar !== "") {

    $sql .= "
        AND (
            p.codigo_pedido LIKE ?
            OR c.nombre LIKE ?
            OR p.nombre_cliente LIKE ?
        )
    ";

    $buscarLike = "%{$buscar}%";

    $parametros[] = $buscarLike;
    $parametros[] = $buscarLike;
    $parametros[] = $buscarLike;

    $tipos .= "sss";
}


/* FECHA DESDE */

if ($desde !== "") {

    $sql .= " AND DATE(p.fecha) >= ?";

    $tipos .= "s";

    $parametros[] = $desde;
}


/* FECHA HASTA */

if ($hasta !== "") {

    $sql .= " AND DATE(p.fecha) <= ?";

    $tipos .= "s";

    $parametros[] = $hasta;
}


/* ESTADO */

if ($estado !== "") {

    $sql .= " AND p.estado = ?";

    $tipos .= "s";

    $parametros[] = $estado;
}


/* ORDEN */

$sql .= "
    ORDER BY p.fecha DESC, p.id_pedido DESC
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
    die("No fue posible consultar los pedidos.");
}

$resultado = $stmt->get_result();


/* DOMPDF */

$options = new Options();

$options->set(
    "isRemoteEnabled",
    true
);

$options->set(
    "defaultFont",
    "DejaVu Sans"
);

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
    font-size: 10px;
    color: #333;
    margin: 25px;
}

.encabezado {
    text-align: center;
    margin-bottom: 20px;
}

.encabezado h1 {
    color: #1f3f6d;
    font-size: 22px;
    margin: 0 0 5px 0;
}

.encabezado p {
    margin: 0;
    color: #666;
    font-size: 11px;
}

.informacion {
    margin-bottom: 18px;
    padding: 10px;
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
    padding: 7px;
    text-align: center;
    font-size: 9px;
}

.resultados td {
    border: 1px solid #ddd;
    padding: 6px;
    font-size: 9px;
    text-align: center;
}

.resultados tr:nth-child(even) {
    background-color: #f8f9fa;
}

.izquierda {
    text-align: left !important;
}

.dinero {
    text-align: right !important;
}

.resumen {
    margin-top: 15px;
    width: 100%;
    border-collapse: collapse;
}

.resumen td {
    padding: 5px;
    font-size: 10px;
}

.resumen .etiqueta-resumen {
    font-weight: bold;
    color: #1f3f6d;
    text-align: right;
}

.resumen .valor-resumen {
    font-weight: bold;
    text-align: right;
}

.pie {
    margin-top: 20px;
    text-align: center;
    color: #777;
    font-size: 9px;
}

</style>

</head>

<body>


<div class="encabezado">

    <h1>YCL SOLUTIONS</h1>

    <p>Reporte de Pedidos</p>

</div>


<div class="informacion">

    <table>

        <tr>

            <td class="etiqueta">
                Buscar:
            </td>

            <td>
                ' . ($buscar !== "" ? htmlspecialchars($buscar) : "Todos") . '
            </td>

            <td class="etiqueta">
                Estado:
            </td>

            <td>
                ' . ($estado !== "" ? htmlspecialchars($estado) : "Todos") . '
            </td>

        </tr>

        <tr>

            <td class="etiqueta">
                Desde:
            </td>

            <td>
                ' . ($desde !== "" ? date("d/m/Y", strtotime($desde)) : "Todas") . '
            </td>

            <td class="etiqueta">
                Hasta:
            </td>

            <td>
                ' . ($hasta !== "" ? date("d/m/Y", strtotime($hasta)) : "Todas") . '
            </td>

        </tr>

    </table>

</div>


<div class="resultados">

<table>

<thead>

<tr>

    <th>Código</th>
    <th>Cliente</th>
    <th>Fecha</th>
    <th>Fecha Entrega</th>
    <th>Subtotal</th>
    <th>IVA</th>
    <th>Total</th>
    <th>Estado</th>

</tr>

</thead>

<tbody>

';


$totalRegistros = 0;

$totalSubtotal = 0;
$totalIva = 0;
$totalGeneral = 0;


while ($pedido = $resultado->fetch_assoc()) {

    $totalRegistros++;

    $subtotal = (float)$pedido["subtotal"];
    $iva = (float)$pedido["iva"];
    $total = (float)$pedido["total"];

    $totalSubtotal += $subtotal;
    $totalIva += $iva;
    $totalGeneral += $total;


    $html .= '

    <tr>

        <td>
            ' . htmlspecialchars($pedido["codigo_pedido"]) . '
        </td>

        <td class="izquierda">
            ' . htmlspecialchars($pedido["cliente"] ?? "") . '
        </td>

        <td>
            ' . (
                !empty($pedido["fecha"])
                ? date("d/m/Y", strtotime($pedido["fecha"]))
                : ""
            ) . '
        </td>

        <td>
            ' . (
                !empty($pedido["fecha_entrega"])
                ? date("d/m/Y", strtotime($pedido["fecha_entrega"]))
                : ""
            ) . '
        </td>

        <td class="dinero">
            $' . number_format(
                $subtotal,
                0,
                ",",
                "."
            ) . '
        </td>

        <td class="dinero">
            $' . number_format(
                $iva,
                0,
                ",",
                "."
            ) . '
        </td>

        <td class="dinero">
            $' . number_format(
                $total,
                0,
                ",",
                "."
            ) . '
        </td>

        <td>
            ' . htmlspecialchars($pedido["estado"]) . '
        </td>

    </tr>

    ';
}


if ($totalRegistros === 0) {

    $html .= '

    <tr>

        <td colspan="8" style="text-align:center;">

            No se encontraron pedidos con los filtros seleccionados.

        </td>

    </tr>

    ';
}


$html .= '

</tbody>

</table>

</div>


<table class="resumen">

<tr>

    <td class="etiqueta-resumen">
        Pedidos encontrados:
    </td>

    <td class="valor-resumen">
        ' . $totalRegistros . '
    </td>

</tr>

<tr>

    <td class="etiqueta-resumen">
        Subtotal:
    </td>

    <td class="valor-resumen">
        $' . number_format(
            $totalSubtotal,
            0,
            ",",
            "."
        ) . '
    </td>

</tr>

<tr>

    <td class="etiqueta-resumen">
        IVA:
    </td>

    <td class="valor-resumen">
        $' . number_format(
            $totalIva,
            0,
            ",",
            "."
        ) . '
    </td>

</tr>

<tr>

    <td class="etiqueta-resumen">
        Total:
    </td>

    <td class="valor-resumen">
        $' . number_format(
            $totalGeneral,
            0,
            ",",
            "."
        ) . '
    </td>

</tr>

</table>


<div class="pie">

    YCL Solutions - Reporte generado automáticamente

</div>


</body>

</html>

';


/* GENERAR PDF */

$dompdf->loadHtml($html);

$dompdf->setPaper(
    "A4",
    "landscape"
);

$dompdf->render();


/* DESCARGAR */

$dompdf->stream(
    "Reporte_Pedidos.pdf",
    [
        "Attachment" => true
    ]
);

exit;