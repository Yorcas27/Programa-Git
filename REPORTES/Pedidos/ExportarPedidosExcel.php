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


/* CREAR EXCEL */

$spreadsheet = new Spreadsheet();

$hoja = $spreadsheet->getActiveSheet();

$hoja->setTitle("Reporte Pedidos");


/* ENCABEZADO */

$hoja->mergeCells("A1:H1");

$hoja->setCellValue(
    "A1",
    "YCL SOLUTIONS"
);

$hoja->mergeCells("A2:H2");

$hoja->setCellValue(
    "A2",
    "Reporte de Pedidos"
);


/* INFORMACIÓN DE FILTROS */

$hoja->setCellValue(
    "A4",
    "Buscar:"
);

$hoja->setCellValue(
    "B4",
    $buscar !== "" ? $buscar : "Todos"
);

$hoja->setCellValue(
    "C4",
    "Estado:"
);

$hoja->setCellValue(
    "D4",
    $estado !== "" ? $estado : "Todos"
);

$hoja->setCellValue(
    "A5",
    "Desde:"
);

$hoja->setCellValue(
    "B5",
    $desde !== ""
        ? date("d/m/Y", strtotime($desde))
        : "Todas"
);

$hoja->setCellValue(
    "C5",
    "Hasta:"
);

$hoja->setCellValue(
    "D5",
    $hasta !== ""
        ? date("d/m/Y", strtotime($hasta))
        : "Todas"
);

$hoja->mergeCells("E4:H5");


/* ENCABEZADOS */

$fila = 7;

$hoja->setCellValue(
    "A{$fila}",
    "Código"
);

$hoja->setCellValue(
    "B{$fila}",
    "Cliente"
);

$hoja->setCellValue(
    "C{$fila}",
    "Fecha"
);

$hoja->setCellValue(
    "D{$fila}",
    "Fecha Entrega"
);

$hoja->setCellValue(
    "E{$fila}",
    "Subtotal"
);

$hoja->setCellValue(
    "F{$fila}",
    "IVA"
);

$hoja->setCellValue(
    "G{$fila}",
    "Total"
);

$hoja->setCellValue(
    "H{$fila}",
    "Estado"
);


/* ESTILO ENCABEZADOS */

$hoja->getStyle("A7:H7")->applyFromArray([

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


    $hoja->setCellValue(
        "A{$fila}",
        $pedido["codigo_pedido"]
    );

    $hoja->setCellValue(
        "B{$fila}",
        $pedido["cliente"] ?? ""
    );

    $hoja->setCellValue(
        "C{$fila}",
        !empty($pedido["fecha"])
            ? date("d/m/Y", strtotime($pedido["fecha"]))
            : ""
    );

    $hoja->setCellValue(
        "D{$fila}",
        !empty($pedido["fecha_entrega"])
            ? date("d/m/Y", strtotime($pedido["fecha_entrega"]))
            : ""
    );

    $hoja->setCellValue(
        "E{$fila}",
        $subtotal
    );

    $hoja->setCellValue(
        "F{$fila}",
        $iva
    );

    $hoja->setCellValue(
        "G{$fila}",
        $total
    );

    $hoja->setCellValue(
        "H{$fila}",
        $pedido["estado"]
    );

    $fila++;
}


/* SI NO HAY REGISTROS */

if ($totalRegistros === 0) {

    $hoja->mergeCells(
        "A{$fila}:H{$fila}"
    );

    $hoja->setCellValue(
        "A{$fila}",
        "No se encontraron pedidos con los filtros seleccionados."
    );

    $hoja->getStyle(
        "A{$fila}:H{$fila}"
    )->applyFromArray([

        "alignment" => [
            "horizontal" => Alignment::HORIZONTAL_CENTER
        ]

    ]);

    $fila++;
}


/* ESTILO DE DATOS */

if ($totalRegistros > 0) {

    $ultimaFila = $fila - 1;

    $hoja->getStyle(
        "A8:H{$ultimaFila}"
    )->applyFromArray([

        "borders" => [
            "allBorders" => [
                "borderStyle" => Border::BORDER_THIN
            ]
        ],

        "alignment" => [
            "vertical" => Alignment::VERTICAL_CENTER
        ]

    ]);


    $hoja->getStyle(
        "A8:A{$ultimaFila}"
    )->getAlignment()->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    );


    $hoja->getStyle(
        "C8:D{$ultimaFila}"
    )->getAlignment()->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    );


    $hoja->getStyle(
        "H8:H{$ultimaFila}"
    )->getAlignment()->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    );


    $hoja->getStyle(
        "E8:G{$ultimaFila}"
    )->getNumberFormat()->setFormatCode(
        '$#,##0'
    );


    $hoja->getStyle(
        "E8:G{$ultimaFila}"
    )->getAlignment()->setHorizontal(
        Alignment::HORIZONTAL_RIGHT
    );
}


/* TOTAL */

$fila++;

$hoja->setCellValue(
    "F{$fila}",
    "Totales:"
);

$hoja->setCellValue(
    "G{$fila}",
    $totalGeneral
);

$hoja->setCellValue(
    "H{$fila}",
    $totalRegistros . " pedidos"
);


$hoja->getStyle(
    "F{$fila}:H{$fila}"
)->applyFromArray([

    "font" => [
        "bold" => true
    ],

    "alignment" => [
        "horizontal" => Alignment::HORIZONTAL_RIGHT
    ],

    "borders" => [
        "top" => [
            "borderStyle" => Border::BORDER_THIN
        ]
    ]

]);


$hoja->getStyle(
    "G{$fila}"
)->getNumberFormat()->setFormatCode(
    '$#,##0'
);


/* RESUMEN FINANCIERO */

$fila += 2;

$hoja->setCellValue(
    "F{$fila}",
    "Subtotal:"
);

$hoja->setCellValue(
    "G{$fila}",
    $totalSubtotal
);

$fila++;

$hoja->setCellValue(
    "F{$fila}",
    "IVA:"
);

$hoja->setCellValue(
    "G{$fila}",
    $totalIva
);

$fila++;

$hoja->setCellValue(
    "F{$fila}",
    "Total:"
);

$hoja->setCellValue(
    "G{$fila}",
    $totalGeneral
);


$hoja->getStyle(
    "F" . ($fila - 2) . ":G{$fila}"
)->applyFromArray([

    "font" => [
        "bold" => true
    ],

    "alignment" => [
        "horizontal" => Alignment::HORIZONTAL_RIGHT
    ]

]);


$hoja->getStyle(
    "G" . ($fila - 2) . ":G{$fila}"
)->getNumberFormat()->setFormatCode(
    '$#,##0'
);


/* TÍTULOS */

$hoja->getStyle(
    "A1:H1"
)->applyFromArray([

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


$hoja->getStyle(
    "A2:H2"
)->applyFromArray([

    "font" => [
        "bold" => true,
        "size" => 14
    ],

    "alignment" => [
        "horizontal" => Alignment::HORIZONTAL_CENTER
    ]

]);


/* FILTROS */

$hoja->getStyle(
    "A4:A5"
)->getFont()->setBold(true);

$hoja->getStyle(
    "C4:C5"
)->getFont()->setBold(true);


/* ANCHO DE COLUMNAS */

$hoja->getColumnDimension("A")->setWidth(18);
$hoja->getColumnDimension("B")->setWidth(35);
$hoja->getColumnDimension("C")->setWidth(15);
$hoja->getColumnDimension("D")->setWidth(17);
$hoja->getColumnDimension("E")->setWidth(17);
$hoja->getColumnDimension("F")->setWidth(17);
$hoja->getColumnDimension("G")->setWidth(17);
$hoja->getColumnDimension("H")->setWidth(20);


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

$nombreArchivo = "Reporte_Pedidos.xlsx";

header(
    "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
);

header(
    "Content-Disposition: attachment; filename=\"" . $nombreArchivo . "\""
);

header(
    "Cache-Control: max-age=0"
);


$writer = new Xlsx($spreadsheet);

$writer->save("php://output");

exit;