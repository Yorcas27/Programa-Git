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

$metodo_pago = $_GET["metodo_pago"] ?? "Todos";
$estado = $_GET["estado"] ?? "Todos";


/* VALIDAR MÉTODO DE PAGO */

$metodos_validos = [
    "Todos",
    "Efectivo",
    "Transferencia",
    "Tarjeta"
];

if (!in_array($metodo_pago, $metodos_validos, true)) {
    $metodo_pago = "Todos";
}


/* VALIDAR ESTADO */

$estados_validos = [
    "Todos",
    "Pendiente",
    "Pagada",
    "Anulada"
];

if (!in_array($estado, $estados_validos, true)) {
    $estado = "Todos";
}


/* VALIDAR FECHAS */

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
    $desde = "";
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
    $hasta = "";
}


/* CONSTRUIR CONSULTA */

$where = [];
$params = [];
$types = "";


/* BÚSQUEDA */

if ($buscar !== "") {

    $where[] = "(
        f.numero_factura LIKE ?
        OR p.codigo_pedido LIKE ?
        OR c.nombre LIKE ?
        OR p.nombre_cliente LIKE ?
    )";

    $valor_busqueda = "%" . $buscar . "%";

    $params[] = $valor_busqueda;
    $params[] = $valor_busqueda;
    $params[] = $valor_busqueda;
    $params[] = $valor_busqueda;

    $types .= "ssss";
}


/* FECHA DESDE */

if ($desde !== "") {

    $where[] = "f.fecha >= ?";

    $params[] = $desde;

    $types .= "s";
}


/* FECHA HASTA */

if ($hasta !== "") {

    $where[] = "f.fecha <= ?";

    $params[] = $hasta;

    $types .= "s";
}


/* MÉTODO DE PAGO */

if ($metodo_pago !== "Todos") {

    $where[] = "f.metodo_pago = ?";

    $params[] = $metodo_pago;

    $types .= "s";
}


/* ESTADO */

if ($estado !== "Todos") {

    $where[] = "f.estado = ?";

    $params[] = $estado;

    $types .= "s";
}


/* WHERE FINAL */

$where_sql = "";

if (!empty($where)) {
    $where_sql = "WHERE " . implode(" AND ", $where);
}


/* CONSULTA PRINCIPAL */

$sql = "
    SELECT
        f.id_factura,
        f.numero_factura,
        f.fecha,
        f.metodo_pago,
        f.total,
        f.estado,
        p.codigo_pedido,
        COALESCE(
            NULLIF(c.nombre, ''),
            NULLIF(p.nombre_cliente, ''),
            'Cliente ocasional'
        ) AS cliente
    FROM facturas f
    INNER JOIN pedidos p
        ON f.id_pedido = p.id_pedido
    LEFT JOIN clientes c
        ON f.id_cliente = c.id_cliente
    $where_sql
    ORDER BY f.fecha DESC, f.id_factura DESC
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$resultado = $stmt->get_result();


/* RESUMEN */

$sql_resumen = "
    SELECT
        COUNT(*) AS total_facturas,
        SUM(CASE WHEN f.estado = 'Pendiente' THEN 1 ELSE 0 END) AS pendientes,
        SUM(CASE WHEN f.estado = 'Pagada' THEN 1 ELSE 0 END) AS pagadas,
        SUM(CASE WHEN f.estado = 'Anulada' THEN 1 ELSE 0 END) AS anuladas
    FROM facturas f
    INNER JOIN pedidos p
        ON f.id_pedido = p.id_pedido
    LEFT JOIN clientes c
        ON f.id_cliente = c.id_cliente
    $where_sql
";

$stmt_resumen = $conexion->prepare($sql_resumen);

if (!$stmt_resumen) {
    die("Error al preparar el resumen: " . $conexion->error);
}

if (!empty($params)) {
    $stmt_resumen->bind_param($types, ...$params);
}

$stmt_resumen->execute();

$resumen = $stmt_resumen->get_result()->fetch_assoc();

$total_facturas = (int)($resumen["total_facturas"] ?? 0);
$pendientes = (int)($resumen["pendientes"] ?? 0);
$pagadas = (int)($resumen["pagadas"] ?? 0);
$anuladas = (int)($resumen["anuladas"] ?? 0);


/* HTML DEL PDF */

$html = '

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<style>

body{
    font-family: DejaVu Sans, sans-serif;
    font-size:10px;
    color:#333;
}

.encabezado{
    text-align:center;
    margin-bottom:18px;
}

.encabezado h1{
    color:#1f3f6d;
    font-size:20px;
    margin:0 0 5px 0;
}

.encabezado p{
    margin:3px 0;
    color:#666;
    font-size:10px;
}

.resumen{
    width:100%;
    margin-bottom:15px;
}

.resumen td{
    width:25%;
    text-align:center;
    border:1px solid #ddd;
    padding:8px;
}

.resumen strong{
    display:block;
    color:#1f3f6d;
    font-size:14px;
}

.filtros{
    margin-bottom:15px;
    padding:8px;
    border:1px solid #ddd;
    background:#f4f6f9;
}

.filtros strong{
    color:#1f3f6d;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#1f3f6d;
    color:white;
    padding:7px;
    text-align:center;
    font-size:9px;
}

td{
    padding:7px;
    border:1px solid #ddd;
    text-align:center;
    font-size:9px;
}

tr:nth-child(even){
    background:#f8f8f8;
}

.total{
    text-align:right;
    margin-top:12px;
    font-size:11px;
    font-weight:bold;
}

</style>

</head>

<body>

<div class="encabezado">

<h1>YCL SOLUTIONS</h1>

<p>Reporte de Facturación</p>

</div>


<table class="resumen">

<tr>

<td>
<strong>' . $total_facturas . '</strong>
Facturas Totales
</td>

<td>
<strong>' . $pendientes . '</strong>
Pendientes
</td>

<td>
<strong>' . $pagadas . '</strong>
Pagadas
</td>

<td>
<strong>' . $anuladas . '</strong>
Anuladas
</td>

</tr>

</table>


<div class="filtros">

<strong>Filtros aplicados:</strong><br>';

if ($buscar !== "") {
    $html .= "Búsqueda: " . htmlspecialchars($buscar, ENT_QUOTES, "UTF-8") . " | ";
}

if ($desde !== "") {
    $html .= "Desde: " . htmlspecialchars($desde, ENT_QUOTES, "UTF-8") . " | ";
}

if ($hasta !== "") {
    $html .= "Hasta: " . htmlspecialchars($hasta, ENT_QUOTES, "UTF-8") . " | ";
}

$html .= "Método de pago: " . htmlspecialchars($metodo_pago, ENT_QUOTES, "UTF-8") . " | ";
$html .= "Estado: " . htmlspecialchars($estado, ENT_QUOTES, "UTF-8");

$html .= '

</div>


<table>

<thead>

<tr>

<th>Factura</th>
<th>Cliente</th>
<th>Pedido</th>
<th>Fecha</th>
<th>Método Pago</th>
<th>Total</th>
<th>Estado</th>

</tr>

</thead>

<tbody>
';


$total_general = 0;

if ($resultado->num_rows > 0) {

    while ($factura = $resultado->fetch_assoc()) {

        $total_general += (float)$factura["total"];

        $html .= '

        <tr>

        <td>' . htmlspecialchars($factura["numero_factura"], ENT_QUOTES, "UTF-8") . '</td>

        <td>' . htmlspecialchars($factura["cliente"], ENT_QUOTES, "UTF-8") . '</td>

        <td>' . htmlspecialchars($factura["codigo_pedido"], ENT_QUOTES, "UTF-8") . '</td>

        <td>' . htmlspecialchars($factura["fecha"], ENT_QUOTES, "UTF-8") . '</td>

        <td>' . htmlspecialchars($factura["metodo_pago"], ENT_QUOTES, "UTF-8") . '</td>

        <td>$' . number_format((float)$factura["total"], 0, ",", ".") . '</td>

        <td>' . htmlspecialchars($factura["estado"], ENT_QUOTES, "UTF-8") . '</td>

        </tr>

        ';

    }

} else {

    $html .= '

    <tr>

    <td colspan="7">
        No se encontraron facturas con los filtros seleccionados.
    </td>

    </tr>

    ';

}


$html .= '

</tbody>

</table>


<div class="total">

Total facturado:
$' . number_format($total_general, 0, ",", ".") . '

</div>


</body>

</html>
';


/* GENERAR PDF */

$options = new Options();

$options->set("isHtml5ParserEnabled", true);
$options->set("isRemoteEnabled", true);
$options->set("defaultFont", "DejaVu Sans");

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

$dompdf->setPaper("A4", "landscape");

$dompdf->render();

$dompdf->stream(
    "Reporte_Facturacion.pdf",
    [
        "Attachment" => true
    ]
);