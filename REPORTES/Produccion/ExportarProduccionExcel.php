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


/* CREAR EXCEL */

$spreadsheet = new Spreadsheet();

$hoja = $spreadsheet->getActiveSheet();

$hoja->setTitle("Reporte Producción");


/* ENCABEZADO */

$hoja->mergeCells("A1:E1");

$hoja->setCellValue(
    "A1",
    "YCL SOLUTIONS"
);

$hoja->mergeCells("A2:E2");

$hoja->setCellValue(
    "A2",
    "Reporte de Producción"
);


/* INFORMACIÓN DE FILTROS */

$hoja->setCellValue("A4", "Desde:");

$hoja->setCellValue(
    "B4",
    $desde !== "" ? date("d/m/Y", strtotime($desde)) : "Todas"
);

$hoja->setCellValue("C4", "Hasta:");

$hoja->setCellValue(
    "D4",
    $hasta !== "" ? date("d/m/Y", strtotime($hasta)) : "Todas"
);

$hoja->setCellValue("A5", "Estado:");

$hoja->mergeCells("B5:E5");

$hoja->setCellValue(
    "B5",
    $estado
);


/* ENCABEZADOS */

$fila = 7;

$hoja->setCellValue("A{$fila}", "Orden");
$hoja->setCellValue("B{$fila}", "Producto");
$hoja->setCellValue("C{$fila}", "Fecha");
$hoja->setCellValue("D{$fila}", "Cantidad");
$hoja->setCellValue("E{$fila}", "Estado");


/* ESTILO ENCABEZADOS */

$hoja->getStyle("A7:E7")->applyFromArray([
    "font" => [
        "bold" => true,
        "color" => [
            "rgb" => "FFFFFF"
        ]
    ],
    "fill" => [
        "fillType" => Fill::FILL_SOLID,
        "startColor" => [
            "rgb" => "1F3F6D"
        ]
    ],
    "alignment" => [
        "horizontal" => Alignment::HORIZONTAL_CENTER,
        "vertical" => Alignment::VERTICAL_CENTER
    ],
    "borders" => [
        "allBorders" => [
            "borderStyle" => Border::BORDER_THIN
        ]
    ]
]);


/* REGISTROS */

$fila++;

$totalRegistros = 0;

while ($orden = $resultado->fetch_assoc()) {

    $totalRegistros++;

    $hoja->setCellValue(
        "A{$fila}",
        $orden["codigo"]
    );

    $hoja->setCellValue(
        "B{$fila}",
        $orden["producto"]
    );

    $hoja->setCellValue(
        "C{$fila}",
        date("d/m/Y", strtotime($orden["fecha"]))
    );

    $hoja->setCellValue(
        "D{$fila}",
        $orden["cantidad"]
    );

    $hoja->setCellValue(
        "E{$fila}",
        $orden["estado"]
    );

    $fila++;
}


/* SI NO HAY REGISTROS */

if ($totalRegistros === 0) {

    $hoja->mergeCells("A{$fila}:E{$fila}");

    $hoja->setCellValue(
        "A{$fila}",
        "No se encontraron órdenes de producción."
    );

    $hoja->getStyle("A{$fila}:E{$fila}")->applyFromArray([
        "alignment" => [
            "horizontal" => Alignment::HORIZONTAL_CENTER
        ]
    ]);

    $fila++;
}


/* ESTILO DE DATOS */

if ($totalRegistros > 0) {

    $ultimaFila = $fila - 1;

    $hoja->getStyle("A8:E{$ultimaFila}")->applyFromArray([
        "borders" => [
            "allBorders" => [
                "borderStyle" => Border::BORDER_THIN
            ]
        ],
        "alignment" => [
            "vertical" => Alignment::VERTICAL_CENTER
        ]
    ]);

    $hoja->getStyle("D8:D{$ultimaFila}")
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
}


/* TOTAL */

$fila++;

$hoja->setCellValue(
    "D{$fila}",
    "Total de órdenes:"
);

$hoja->setCellValue(
    "E{$fila}",
    $totalRegistros
);

$hoja->getStyle("D{$fila}:E{$fila}")->applyFromArray([
    "font" => [
        "bold" => true
    ],
    "alignment" => [
        "horizontal" => Alignment::HORIZONTAL_RIGHT
    ]
]);


/* TÍTULOS */

$hoja->getStyle("A1:E1")->applyFromArray([
    "font" => [
        "bold" => true,
        "size" => 18,
        "color" => [
            "rgb" => "1F3F6D"
        ]
    ],
    "alignment" => [
        "horizontal" => Alignment::HORIZONTAL_CENTER
    ]
]);

$hoja->getStyle("A2:E2")->applyFromArray([
    "font" => [
        "bold" => true,
        "size" => 14
    ],
    "alignment" => [
        "horizontal" => Alignment::HORIZONTAL_CENTER
    ]
]);


/* FILTROS */

$hoja->getStyle("A4:A5")->getFont()->setBold(true);
$hoja->getStyle("C4")->getFont()->setBold(true);


/* ANCHO DE COLUMNAS */

$hoja->getColumnDimension("A")->setWidth(18);
$hoja->getColumnDimension("B")->setWidth(32);
$hoja->getColumnDimension("C")->setWidth(15);
$hoja->getColumnDimension("D")->setWidth(15);
$hoja->getColumnDimension("E")->setWidth(20);


/* CONGELAR ENCABEZADOS */

$hoja->freezePane("A8");


/* CONFIGURACIÓN DE IMPRESIÓN */

$hoja->getPageSetup()->setOrientation(
    \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
);

$hoja->getPageSetup()->setPaperSize(
    \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
);


/* DESCARGAR */

$nombreArchivo = "Reporte_Produccion.xlsx";

header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);

header(
    "Content-Disposition: attachment; filename=\"" . $nombreArchivo . "\""
);

header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);

$writer->save("php://output");

exit;