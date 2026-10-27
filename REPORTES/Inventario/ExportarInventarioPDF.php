
<?php
require_once("../../includes/config.php");
require_once("../../includes/conexion.php");
require_once("../../vendor/autoload.php");

use Dompdf\Dompdf;
use Dompdf\Options;

/* FORMATO DE CANTIDADES */
function formatoCantidad($valor) {
    return rtrim(
        rtrim(number_format((float)$valor, 2, ',', '.'), '0'),
        ','
    );
}

/* FILTROS */
$buscar = trim($_GET["buscar"] ?? "");
$estado = $_GET["estado"] ?? "Todos";
$desde = $_GET["desde"] ?? "";
$hasta = $_GET["hasta"] ?? "";
$tipo = $_GET["tipo"] ?? "Todos";
$estadoMovimiento = $_GET["estado_movimiento"] ?? "Todos";

/* VALIDAR ESTADO */
$estadosInsumo = ["Todos", "Disponible", "Bajo Stock"];

if (!in_array($estado, $estadosInsumo, true)) {
    $estado = "Todos";
}

/* VALIDAR TIPO */
$tiposMovimiento = ["Todos", "Entrada", "Salida"];

if (!in_array($tipo, $tiposMovimiento, true)) {
    $tipo = "Todos";
}

/* VALIDAR ESTADO MOVIMIENTO */
$estadosMovimiento = ["Todos", "Completado", "Pendiente"];

if (!in_array($estadoMovimiento, $estadosMovimiento, true)) {
    $estadoMovimiento = "Todos";
}

/* VALIDAR FECHAS */
if ($desde !== "" && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $desde)) {
    $desde = "";
}

if ($hasta !== "" && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $hasta)) {
    $hasta = "";
}

/* EXISTENCIAS */
$sqlInventario = "
    SELECT
        i.stock_actual,
        i.fecha_actualizacion,
        s.codigo,
        s.producto,
        s.unidad,
        s.estado
    FROM inventario i
    INNER JOIN insumos s
        ON i.id_insumo = s.id_insumo
    WHERE 1=1
";

$tiposInventario = "";
$parametrosInventario = [];

if ($buscar !== "") {
    $sqlInventario .= "
        AND (
            s.codigo LIKE ?
            OR s.producto LIKE ?
        )
    ";

    $tiposInventario .= "ss";
    $busqueda = "%" . $buscar . "%";
    $parametrosInventario[] = $busqueda;
    $parametrosInventario[] = $busqueda;
}

if ($estado !== "Todos") {
    $sqlInventario .= " AND s.estado = ?";
    $tiposInventario .= "s";
    $parametrosInventario[] = $estado;
}

$sqlInventario .= " ORDER BY s.producto ASC";

$stmtInventario = $conexion->prepare($sqlInventario);
$resultadoInventario = false;

if ($stmtInventario) {
    if (!empty($parametrosInventario)) {
        $stmtInventario->bind_param(
            $tiposInventario,
            ...$parametrosInventario
        );
    }

    if ($stmtInventario->execute()) {
        $resultadoInventario = $stmtInventario->get_result();
    }
}

/* MOVIMIENTOS */
$sqlMovimientos = "
    SELECT
        m.codigo,
        m.fecha,
        m.tipo,
        m.cantidad,
        m.unidad,
        m.responsable,
        m.estado,
        s.producto
    FROM movimientos m
    INNER JOIN insumos s
        ON m.id_insumo = s.id_insumo
    WHERE 1=1
";

$tiposMovimientos = "";
$parametrosMovimientos = [];

if ($desde !== "") {
    $sqlMovimientos .= " AND m.fecha >= ?";
    $tiposMovimientos .= "s";
    $parametrosMovimientos[] = $desde;
}

if ($hasta !== "") {
    $sqlMovimientos .= "
        AND m.fecha < DATE_ADD(?, INTERVAL 1 DAY)
    ";

    $tiposMovimientos .= "s";
    $parametrosMovimientos[] = $hasta;
}

if ($tipo !== "Todos") {
    $sqlMovimientos .= " AND m.tipo = ?";
    $tiposMovimientos .= "s";
    $parametrosMovimientos[] = $tipo;
}

if ($estadoMovimiento !== "Todos") {
    $sqlMovimientos .= " AND m.estado = ?";
    $tiposMovimientos .= "s";
    $parametrosMovimientos[] = $estadoMovimiento;
}

$sqlMovimientos .= " ORDER BY m.fecha DESC, m.id_movimiento DESC";

$stmtMovimientos = $conexion->prepare($sqlMovimientos);
$resultadoMovimientos = false;

if ($stmtMovimientos) {
    if (!empty($parametrosMovimientos)) {
        $stmtMovimientos->bind_param(
            $tiposMovimientos,
            ...$parametrosMovimientos
        );
    }

    if ($stmtMovimientos->execute()) {
        $resultadoMovimientos = $stmtMovimientos->get_result();
    }
}

/* HTML PDF */
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page {
    margin: 35px 30px;
}
body {
    font-family: DejaVu Sans, sans-serif;
    color: #333;
    font-size: 10px;
}
h1 {
    color: #1f3f6d;
    text-align: center;
    font-size: 20px;
    margin-bottom: 5px;
}
.subtitulo {
    text-align: center;
    color: #666;
    margin-bottom: 20px;
}
h2 {
    color: #1f3f6d;
    font-size: 14px;
    margin-top: 20px;
    margin-bottom: 5px;
}
.descripcion {
    color: #666;
    font-size: 9px;
    margin-bottom: 8px;
}
.filtros {
    background: #f4f6f9;
    border: 1px solid #ddd;
    padding: 8px;
    margin-bottom: 15px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
}
th {
    background: #1f3f6d;
    color: white;
    padding: 7px;
    font-size: 9px;
}
td {
    border-bottom: 1px solid #ddd;
    padding: 6px;
    font-size: 9px;
}
tr:nth-child(even) {
    background: #fafbfd;
}
.centro {
    text-align: center;
}
.footer {
    margin-top: 20px;
    text-align: center;
    color: #777;
    font-size: 8px;
}
</style>
</head>
<body>

<h1>Reporte de Inventario</h1>

<div class="subtitulo">
Consulta de existencias y movimientos de inventario.
</div>

<div class="filtros">
<strong>Filtros aplicados</strong><br><br>
Existencias: ' . htmlspecialchars($buscar !== "" ? $buscar : "Todos", ENT_QUOTES, "UTF-8") . '
&nbsp;&nbsp; | &nbsp;&nbsp;
Estado: ' . htmlspecialchars($estado, ENT_QUOTES, "UTF-8") . '
<br><br>
Movimientos: ' . htmlspecialchars($desde !== "" ? $desde : "Desde cualquier fecha", ENT_QUOTES, "UTF-8") . '
&nbsp;&nbsp; - &nbsp;&nbsp;
' . htmlspecialchars($hasta !== "" ? $hasta : "Hasta cualquier fecha", ENT_QUOTES, "UTF-8") . '
&nbsp;&nbsp; | &nbsp;&nbsp;
Tipo: ' . htmlspecialchars($tipo, ENT_QUOTES, "UTF-8") . '
&nbsp;&nbsp; | &nbsp;&nbsp;
Estado: ' . htmlspecialchars($estadoMovimiento, ENT_QUOTES, "UTF-8") . '
</div>

<h2>Existencias Actuales</h2>

<div class="descripcion">
Consulta del inventario disponible.
</div>

<table>
<thead>
<tr>
<th>Código</th>
<th>Insumo</th>
<th>Stock Actual</th>
<th>Unidad</th>
<th>Estado</th>
<th>Actualización</th>
</tr>
</thead>
<tbody>';

/* FILAS EXISTENCIAS */
if ($resultadoInventario && $resultadoInventario->num_rows > 0) {
    while ($insumo = $resultadoInventario->fetch_assoc()) {
        $html .= '
        <tr>
            <td class="centro">' . htmlspecialchars($insumo["codigo"], ENT_QUOTES, "UTF-8") . '</td>
            <td>' . htmlspecialchars($insumo["producto"], ENT_QUOTES, "UTF-8") . '</td>
            <td class="centro">' . formatoCantidad($insumo["stock_actual"]) . '</td>
            <td class="centro">' . htmlspecialchars($insumo["unidad"], ENT_QUOTES, "UTF-8") . '</td>
            <td class="centro">' . htmlspecialchars($insumo["estado"], ENT_QUOTES, "UTF-8") . '</td>
            <td class="centro">' .
                (!empty($insumo["fecha_actualizacion"])
                    ? date("d/m/Y H:i", strtotime($insumo["fecha_actualizacion"]))
                    : "") .
            '</td>
        </tr>';
    }
} else {
    $html .= '
    <tr>
        <td colspan="6" class="centro">
            No se encontraron insumos con los filtros seleccionados.
        </td>
    </tr>';
}

$html .= '
</tbody>
</table>

<h2>Movimientos de Inventario</h2>

<div class="descripcion">
Historial de entradas y salidas registradas.
</div>

<table>
<thead>
<tr>
<th>Código</th>
<th>Fecha</th>
<th>Insumo</th>
<th>Tipo</th>
<th>Cantidad</th>
<th>Unidad</th>
<th>Responsable</th>
<th>Estado</th>
</tr>
</thead>
<tbody>';

/* FILAS MOVIMIENTOS */
if ($resultadoMovimientos && $resultadoMovimientos->num_rows > 0) {
    while ($movimiento = $resultadoMovimientos->fetch_assoc()) {
        $html .= '
        <tr>
            <td class="centro">' . htmlspecialchars($movimiento["codigo"], ENT_QUOTES, "UTF-8") . '</td>
            <td class="centro">' .
                (!empty($movimiento["fecha"])
                    ? date("d/m/Y", strtotime($movimiento["fecha"]))
                    : "") .
            '</td>
            <td>' . htmlspecialchars($movimiento["producto"], ENT_QUOTES, "UTF-8") . '</td>
            <td class="centro">' . htmlspecialchars($movimiento["tipo"], ENT_QUOTES, "UTF-8") . '</td>
            <td class="centro">' . formatoCantidad($movimiento["cantidad"]) . '</td>
            <td class="centro">' . htmlspecialchars($movimiento["unidad"], ENT_QUOTES, "UTF-8") . '</td>
            <td>' . htmlspecialchars($movimiento["responsable"], ENT_QUOTES, "UTF-8") . '</td>
            <td class="centro">' . htmlspecialchars($movimiento["estado"], ENT_QUOTES, "UTF-8") . '</td>
        </tr>';
    }
} else {
    $html .= '
    <tr>
        <td colspan="8" class="centro">
            No se encontraron movimientos con los filtros seleccionados.
        </td>
    </tr>';
}

$html .= '
</tbody>
</table>

<div class="footer">
YCL Solutions - Reporte de Inventario
</div>

</body>
</html>';

/* GENERAR PDF */
$options = new Options();
$options->set("isRemoteEnabled", true);
$options->set("defaultFont", "DejaVu Sans");

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, "UTF-8");
$dompdf->setPaper("A4", "landscape");
$dompdf->render();

$dompdf->stream("Reporte_Inventario.pdf", [
    "Attachment" => true
]);

exit;
