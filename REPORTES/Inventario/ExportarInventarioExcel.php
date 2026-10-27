<?php

require_once("../../includes/config.php");
require_once("../../includes/conexion.php");
require_once("../../vendor/autoload.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;


/* FILTROS */

$buscar = trim($_GET["buscar"] ?? "");
$estado = $_GET["estado"] ?? "Todos";

$desde = $_GET["desde"] ?? "";
$hasta = $_GET["hasta"] ?? "";

$tipo = $_GET["tipo"] ?? "Todos";
$estadoMovimiento = $_GET["estado_movimiento"] ?? "Todos";


/* VALIDAR ESTADO */

$estadosInsumo = [
    "Todos",
    "Disponible",
    "Bajo Stock"
];

if (!in_array($estado, $estadosInsumo, true)) {
    $estado = "Todos";
}


/* VALIDAR TIPO */

$tiposMovimiento = [
    "Todos",
    "Entrada",
    "Salida"
];

if (!in_array($tipo, $tiposMovimiento, true)) {
    $tipo = "Todos";
}


/* VALIDAR ESTADO MOVIMIENTO */

$estadosMovimiento = [
    "Todos",
    "Completado",
    "Pendiente"
];

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


$sqlInventario .= "
    ORDER BY s.producto ASC
";


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


$sqlMovimientos .= "
    ORDER BY m.fecha DESC, m.id_movimiento DESC
";


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


/* CREAR EXCEL */

$spreadsheet = new Spreadsheet();


/* HOJA EXISTENCIAS */

$hojaExistencias = $spreadsheet->getActiveSheet();

$hojaExistencias->setTitle("Existencias");


$hojaExistencias->mergeCells("A1:F1");

$hojaExistencias->setCellValue(
    "A1",
    "REPORTE DE INVENTARIO - EXISTENCIAS ACTUALES"
);

$hojaExistencias->mergeCells("A2:F2");

$hojaExistencias->setCellValue(
    "A2",
    "Consulta del inventario disponible."
);


$hojaExistencias->fromArray(
    [
        [
            "Código",
            "Insumo",
            "Stock Actual",
            "Unidad",
            "Estado",
            "Actualización"
        ]
    ],
    null,
    "A4"
);


if ($resultadoInventario) {

    $fila = 5;

    while ($insumo = $resultadoInventario->fetch_assoc()) {

        $hojaExistencias->fromArray(
            [
                [
                    $insumo["codigo"],
                    $insumo["producto"],
                    (float)$insumo["stock_actual"],
                    $insumo["unidad"],
                    $insumo["estado"],
                    date(
                        "d/m/Y H:i",
                        strtotime($insumo["fecha_actualizacion"])
                    )
                ]
            ],
            null,
            "A" . $fila
        );

        $fila++;
    }
}


/* ESTILOS EXISTENCIAS */

$hojaExistencias->getStyle("A1:F1")->applyFromArray([
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
    ]
]);


$hojaExistencias->getStyle("A2:F2")->applyFromArray([
    "font" => [
        "italic" => true,
        "color" => [
            "rgb" => "666666"
        ]
    ],
    "alignment" => [
        "horizontal" => Alignment::HORIZONTAL_CENTER
    ]
]);


$hojaExistencias->getStyle("A4:F4")->applyFromArray([
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
        "horizontal" => Alignment::HORIZONTAL_CENTER
    ]
]);


$hojaExistencias->getStyle(
    "A4:F" . max(4, $hojaExistencias->getHighestRow())
)->applyFromArray([
    "borders" => [
        "allBorders" => [
            "borderStyle" => Border::BORDER_THIN,
            "color" => [
                "rgb" => "D9D9D9"
            ]
        ]
    ]
]);


$hojaExistencias->getColumnDimension("A")->setWidth(16);
$hojaExistencias->getColumnDimension("B")->setWidth(30);
$hojaExistencias->getColumnDimension("C")->setWidth(18);
$hojaExistencias->getColumnDimension("D")->setWidth(15);
$hojaExistencias->getColumnDimension("E")->setWidth(18);
$hojaExistencias->getColumnDimension("F")->setWidth(22);


/* HOJA MOVIMIENTOS */

$hojaMovimientos = $spreadsheet->createSheet();

$hojaMovimientos->setTitle("Movimientos");


$hojaMovimientos->mergeCells("A1:H1");

$hojaMovimientos->setCellValue(
    "A1",
    "REPORTE DE INVENTARIO - MOVIMIENTOS"
);

$hojaMovimientos->mergeCells("A2:H2");

$hojaMovimientos->setCellValue(
    "A2",
    "Historial de entradas y salidas registradas."
);


$hojaMovimientos->fromArray(
    [
        [
            "Código",
            "Fecha",
            "Insumo",
            "Tipo",
            "Cantidad",
            "Unidad",
            "Responsable",
            "Estado"
        ]
    ],
    null,
    "A4"
);


if ($resultadoMovimientos) {

    $fila = 5;

    while ($movimiento = $resultadoMovimientos->fetch_assoc()) {

        $hojaMovimientos->fromArray(
            [
                [
                    $movimiento["codigo"],
                    date(
                        "d/m/Y",
                        strtotime($movimiento["fecha"])
                    ),
                    $movimiento["producto"],
                    $movimiento["tipo"],
                    (float)$movimiento["cantidad"],
                    $movimiento["unidad"],
                    $movimiento["responsable"],
                    $movimiento["estado"]
                ]
            ],
            null,
            "A" . $fila
        );

        $fila++;
    }
}


/* ESTILOS MOVIMIENTOS */

$hojaMovimientos->getStyle("A1:H1")->applyFromArray([
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
    ]
]);


$hojaMovimientos->getStyle("A2:H2")->applyFromArray([
    "font" => [
        "italic" => true,
        "color" => [
            "rgb" => "666666"
        ]
    ],
    "alignment" => [
        "horizontal" => Alignment::HORIZONTAL_CENTER
    ]
]);


$hojaMovimientos->getStyle("A4:H4")->applyFromArray([
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
        "horizontal" => Alignment::HORIZONTAL_CENTER
    ]
]);


$hojaMovimientos->getStyle(
    "A4:H" . max(4, $hojaMovimientos->getHighestRow())
)->applyFromArray([
    "borders" => [
        "allBorders" => [
            "borderStyle" => Border::BORDER_THIN,
            "color" => [
                "rgb" => "D9D9D9"
            ]
        ]
    ]
]);


$hojaMovimientos->getColumnDimension("A")->setWidth(16);
$hojaMovimientos->getColumnDimension("B")->setWidth(15);
$hojaMovimientos->getColumnDimension("C")->setWidth(30);
$hojaMovimientos->getColumnDimension("D")->setWidth(15);
$hojaMovimientos->getColumnDimension("E")->setWidth(16);
$hojaMovimientos->getColumnDimension("F")->setWidth(15);
$hojaMovimientos->getColumnDimension("G")->setWidth(25);
$hojaMovimientos->getColumnDimension("H")->setWidth(18);


/* DESCARGA */

$nombreArchivo = "Reporte_Inventario.xlsx";

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