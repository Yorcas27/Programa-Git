<?php

require_once("../../includes/config.php");
require_once("../../includes/conexion.php");
require_once("../../vendor/autoload.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;


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


/* CREAR EXCEL */

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle("Facturación");


/* TÍTULO */

$sheet->mergeCells("A1:G1");

$sheet->setCellValue(
    "A1",
    "YCL SOLUTIONS - REPORTE DE FACTURACIÓN"
);

$sheet->getStyle("A1")->getFont()->setBold(true)->setSize(16);

$sheet->getStyle("A1")->getAlignment()->setHorizontal(
    Alignment::HORIZONTAL_CENTER
);


/* FECHA DE GENERACIÓN */

$sheet->mergeCells("A2:G2");

$sheet->setCellValue(
    "A2",
    "Fecha de generación: " . date("Y-m-d H:i:s")
);

$sheet->getStyle("A2")->getAlignment()->setHorizontal(
    Alignment::HORIZONTAL_CENTER
);


/* RESUMEN */

$sheet->setCellValue("A4", "Facturas Totales");
$sheet->setCellValue("B4", "Pendientes");
$sheet->setCellValue("C4", "Pagadas");
$sheet->setCellValue("D4", "Anuladas");

$sheet->setCellValue("A5", $total_facturas);
$sheet->setCellValue("B5", $pendientes);
$sheet->setCellValue("C5", $pagadas);
$sheet->setCellValue("D5", $anuladas);

$sheet->getStyle("A4:D4")->getFont()->setBold(true);

$sheet->getStyle("A4:D4")->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()
    ->setARGB("1F3F6D");

$sheet->getStyle("A4:D4")->getFont()->getColor()
    ->setARGB("FFFFFF");

$sheet->getStyle("A4:D5")->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);


/* FILTROS */

$sheet->setCellValue("F4", "Método de Pago");
$sheet->setCellValue("G4", $metodo_pago);

$sheet->setCellValue("F5", "Estado");
$sheet->setCellValue("G5", $estado);

$sheet->setCellValue("F6", "Desde");
$sheet->setCellValue("G6", $desde !== "" ? $desde : "Todos");

$sheet->setCellValue("F7", "Hasta");
$sheet->setCellValue("G7", $hasta !== "" ? $hasta : "Todos");

if ($buscar !== "") {
    $sheet->setCellValue("F8", "Búsqueda");
    $sheet->setCellValue("G8", $buscar);
}


/* ENCABEZADOS */

$fila = 10;

$encabezados = [
    "Factura",
    "Cliente",
    "Pedido",
    "Fecha",
    "Método Pago",
    "Total",
    "Estado"
];

$columna = "A";

foreach ($encabezados as $encabezado) {

    $sheet->setCellValue(
        $columna . $fila,
        $encabezado
    );

    $columna++;
}


/* ESTILO ENCABEZADOS */

$sheet->getStyle("A10:G10")->getFont()->setBold(true);

$sheet->getStyle("A10:G10")->getFont()->getColor()
    ->setARGB("FFFFFF");

$sheet->getStyle("A10:G10")->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()
    ->setARGB("1F3F6D");

$sheet->getStyle("A10:G10")->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);


/* DATOS */

$fila = 11;

$total_general = 0;

while ($factura = $resultado->fetch_assoc()) {

    $total_general += (float)$factura["total"];

    $sheet->setCellValue(
        "A" . $fila,
        $factura["numero_factura"]
    );

    $sheet->setCellValue(
        "B" . $fila,
        $factura["cliente"]
    );

    $sheet->setCellValue(
        "C" . $fila,
        $factura["codigo_pedido"]
    );

    $sheet->setCellValue(
        "D" . $fila,
        $factura["fecha"]
    );

    $sheet->setCellValue(
        "E" . $fila,
        $factura["metodo_pago"]
    );

    $sheet->setCellValue(
        "F" . $fila,
        (float)$factura["total"]
    );

    $sheet->setCellValue(
        "G" . $fila,
        $factura["estado"]
    );

    $fila++;
}


/* TOTAL */

$fila_total = $fila + 1;

$sheet->setCellValue(
    "E" . $fila_total,
    "TOTAL FACTURADO"
);

$sheet->setCellValue(
    "F" . $fila_total,
    $total_general
);

$sheet->getStyle("E" . $fila_total . ":F" . $fila_total)
    ->getFont()
    ->setBold(true);


/* FORMATO MONEDA */

if ($fila > 11) {

    $sheet->getStyle("F11:F" . ($fila - 1))
        ->getNumberFormat()
        ->setFormatCode('$ #,##0');

}

$sheet->getStyle("F" . $fila_total)
    ->getNumberFormat()
    ->setFormatCode('$ #,##0');


/* BORDES */

$ultima_fila = max(10, $fila - 1);

$sheet->getStyle("A10:G" . $ultima_fila)
    ->getBorders()
    ->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN)
    ->getColor()
    ->setARGB("D9D9D9");


/* ANCHO DE COLUMNAS */

$anchos = [
    "A" => 16,
    "B" => 30,
    "C" => 16,
    "D" => 15,
    "E" => 20,
    "F" => 18,
    "G" => 15
];

foreach ($anchos as $col => $ancho) {
    $sheet->getColumnDimension($col)->setWidth($ancho);
}


/* ALINEACIÓN */

$sheet->getStyle("A10:G" . $ultima_fila)
    ->getAlignment()
    ->setVertical(Alignment::VERTICAL_CENTER);

$sheet->getStyle("A10:G" . $ultima_fila)
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);


/* ALINEAR CLIENTE A LA IZQUIERDA */

if ($ultima_fila >= 11) {

    $sheet->getStyle("B11:B" . $ultima_fila)
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_LEFT);

}


/* EXPORTAR */

$writer = new Xlsx($spreadsheet);

$filename = "Reporte_Facturacion.xlsx";

header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);

header(
    "Content-Disposition: attachment; filename=\"" . $filename . "\""
);

header("Cache-Control: max-age=0");

$writer->save("php://output");

exit;