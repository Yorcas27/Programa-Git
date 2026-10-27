
<?php
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function limpiar($valor) {
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function pesos($valor) {
    return '$ ' . number_format((float)$valor, 0, ',', '.');
}

$id_factura = filter_input(INPUT_GET, 'id_factura', FILTER_VALIDATE_INT);

if (!$id_factura || $id_factura <= 0) {
    http_response_code(400);
    exit('El número de factura no es válido.');
}

/* DATOS DE LA EMPRESA */

$sqlEmpresa = "SELECT nombre, nit, descripcion, direccion, ciudad, telefono, correo, logo
               FROM empresa LIMIT 1";

$resultadoEmpresa = $conexion->query($sqlEmpresa);
$empresa = $resultadoEmpresa ? $resultadoEmpresa->fetch_assoc() : [];

/* CONSULTAR FACTURA Y CLIENTE */

$sqlFactura = "SELECT
                    f.id_factura,
                    f.numero_factura,
                    f.fecha,
                    f.metodo_pago,
                    f.estado,
                    f.total,
                    f.nombre_cliente AS nombre_cliente_factura,
                    f.documento_cliente AS documento_factura,
                    f.telefono_cliente AS telefono_factura,
                    f.correo_cliente AS correo_factura,
                    p.id_pedido,
                    p.codigo_pedido,
                    p.subtotal AS subtotal_pedido,
                    p.iva AS iva_pedido,
                    COALESCE(
                        NULLIF(c.nombre, ''),
                        NULLIF(f.nombre_cliente, ''),
                        NULLIF(p.nombre_cliente, ''),
                        'Cliente ocasional'
                    ) AS cliente,
                    COALESCE(
                        NULLIF(c.documento, ''),
                        NULLIF(f.documento_cliente, ''),
                        NULLIF(p.documento_cliente, ''),
                        ''
                    ) AS documento,
                    COALESCE(
                        NULLIF(c.telefono, ''),
                        NULLIF(f.telefono_cliente, ''),
                        NULLIF(p.telefono_cliente, ''),
                        ''
                    ) AS telefono_cliente,
                    COALESCE(
                        NULLIF(c.correo, ''),
                        NULLIF(f.correo_cliente, ''),
                        NULLIF(p.correo_cliente, ''),
                        ''
                    ) AS correo_cliente,
                    COALESCE(NULLIF(c.direccion, ''), '') AS direccion_cliente,
                    COALESCE(NULLIF(c.ciudad, ''), '') AS ciudad_cliente
                FROM facturas f
                LEFT JOIN pedidos p
                    ON f.id_pedido = p.id_pedido
                LEFT JOIN clientes c
                    ON f.id_cliente = c.id_cliente
                WHERE f.id_factura = ?";

$stmt = $conexion->prepare($sqlFactura);

if (!$stmt) {
    http_response_code(500);
    exit('No fue posible preparar la consulta de la factura.');
}

$stmt->bind_param('i', $id_factura);
$stmt->execute();

$resultadoFactura = $stmt->get_result();
$factura = $resultadoFactura->fetch_assoc();

$stmt->close();

if (!$factura) {
    http_response_code(404);
    exit('La factura solicitada no existe.');
}

/* DETALLES DE LA FACTURA */

$sqlDetalles = "SELECT
                    df.id_detalle_factura,
                    p.nombre AS producto,
                    df.cantidad,
                    df.valor,
                    df.subtotal
                FROM detalle_factura df
                INNER JOIN productos p
                    ON df.id_producto = p.id_producto
                WHERE df.id_factura = ?
                ORDER BY df.id_detalle_factura ASC";

$stmt = $conexion->prepare($sqlDetalles);

if (!$stmt) {
    http_response_code(500);
    exit('No fue posible consultar los productos de la factura.');
}

$stmt->bind_param('i', $id_factura);
$stmt->execute();

$resultadoDetalles = $stmt->get_result();
$detalles = [];
$subtotalDetalles = 0;

while ($fila = $resultadoDetalles->fetch_assoc()) {
    $detalles[] = $fila;
    $subtotalDetalles += (float)$fila['subtotal'];
}

$stmt->close();

/* DATOS GENERALES */

$nombreEmpresa = $empresa['nombre'] ?? 'YCL Solutions';
$numeroFactura = $factura['numero_factura'] ?? $factura['id_factura'];

$fechaFactura = !empty($factura['fecha'])
    ? date('d/m/Y', strtotime($factura['fecha']))
    : '';

$subtotal = $factura['subtotal_pedido'] !== null
    ? (float)$factura['subtotal_pedido']
    : $subtotalDetalles;

$total = (float)$factura['total'];

$iva = $factura['iva_pedido'] !== null
    ? (float)$factura['iva_pedido']
    : max(0, $total - $subtotal);

$origen = !empty($factura['codigo_pedido'])
    ? $factura['codigo_pedido']
    : 'Venta directa';

/* LOGO */
$logoHTML = '';
$logo = trim((string)($empresa['logo'] ?? ''));

if ($logo !== '') {
    if (filter_var($logo, FILTER_VALIDATE_URL)) {
        $logoHTML = '<img class="logo" src="' . limpiar($logo) . '" alt="Logo">';
    } else {
        $rutaRelativa = str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            ltrim($logo, '/\\')
        );

        $rutaLogo = realpath(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . $rutaRelativa
        );

        if ($rutaLogo && is_file($rutaLogo) && is_readable($rutaLogo)) {
            $contenidoLogo = file_get_contents($rutaLogo);

            if ($contenidoLogo !== false) {
                $extension = strtolower(pathinfo($rutaLogo, PATHINFO_EXTENSION));

                $tiposImagen = [
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png'  => 'image/png',
                    'gif'  => 'image/gif',
                    'webp' => 'image/webp'
                ];

                $tipoImagen = $tiposImagen[$extension] ?? 'image/jpeg';

                $logoHTML = '<img class="logo" src="data:' .
                    $tipoImagen . ';base64,' .
                    base64_encode($contenidoLogo) .
                    '" alt="Logo">';
            }
        }
    }
}

/* CONSTRUIR PDF */

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page {
    size: letter portrait;
    margin: 18mm;
}
body {
    font-family: DejaVu Sans, sans-serif;
    color: #333333;
    font-size: 10px;
}
.encabezado {
    width: 100%;
    border-bottom: 3px solid #1f3f6d;
    padding-bottom: 14px;
    margin-bottom: 20px;
}
.encabezado td {
    vertical-align: middle;
}
.logo {
    max-width: 100px;
    max-height: 65px;
}
.empresa {
    font-size: 19px;
    font-weight: bold;
    color: #1f3f6d;
}
.datos-empresa {
    font-size: 9px;
    line-height: 1.6;
    color: #555555;
}
.titulo {
    text-align: right;
    font-size: 22px;
    font-weight: bold;
    color: #1f3f6d;
}
.numero {
    text-align: right;
    font-size: 12px;
    margin-top: 5px;
}
.seccion {
    background-color: #1f3f6d;
    color: #ffffff;
    font-size: 11px;
    font-weight: bold;
    padding: 9px;
    margin-top: 16px;
    margin-bottom: 8px;
}
.info {
    width: 100%;
    border-collapse: collapse;
}
.info td {
    padding: 5px 4px;
    vertical-align: top;
}
.etiqueta {
    font-weight: bold;
    color: #1f3f6d;
}
.productos {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
.productos th {
    background-color: #1f3f6d;
    color: #ffffff;
    padding: 9px 6px;
    text-align: left;
    font-size: 9px;
}
.productos td {
    padding: 8px 6px;
    border-bottom: 1px solid #dddddd;
    vertical-align: top;
}
.derecha {
    text-align: right;
}
.centro {
    text-align: center;
}
.totales {
    width: 48%;
    margin-left: auto;
    margin-top: 18px;
    border-collapse: collapse;
}
.totales td {
    padding: 7px;
    border-bottom: 1px solid #dddddd;
}
.total-final td {
    background-color: #eaf0f8;
    color: #1f3f6d;
    font-weight: bold;
    font-size: 13px;
    border-top: 2px solid #1f3f6d;
}
.pie {
    margin-top: 35px;
    padding-top: 10px;
    border-top: 1px solid #cccccc;
    text-align: center;
    color: #666666;
    font-size: 9px;
}
</style>
</head>
<body>

<table class="encabezado">
<tr>
<td width="20%">' . $logoHTML . '</td>
<td width="45%">
    <div class="empresa">' . limpiar($nombreEmpresa) . '</div>
    <div class="datos-empresa">
        ' . (!empty($empresa['nit']) ? 'NIT: ' . limpiar($empresa['nit']) . '<br>' : '') . '
        ' . (!empty($empresa['direccion']) ? limpiar($empresa['direccion']) . '<br>' : '') . '
        ' . (!empty($empresa['ciudad']) ? limpiar($empresa['ciudad']) . '<br>' : '') . '
        ' . (!empty($empresa['telefono']) ? 'Teléfono: ' . limpiar($empresa['telefono']) . '<br>' : '') . '
        ' . (!empty($empresa['correo']) ? 'Correo: ' . limpiar($empresa['correo']) : '') . '
    </div>
</td>
<td width="35%">
    <div class="titulo">FACTURA</div>
    <div class="numero">N.º ' . limpiar($numeroFactura) . '</div>
    <div class="numero">Fecha: ' . limpiar($fechaFactura) . '</div>
</td>
</tr>
</table>

<div class="seccion">INFORMACIÓN DEL CLIENTE</div>

<table class="info">
<tr>
<td width="50%">
    <span class="etiqueta">Cliente:</span> ' . limpiar($factura['cliente']) . '
</td>
<td width="50%">
    <span class="etiqueta">Documento:</span> ' . limpiar($factura['documento']) . '
</td>
</tr>
<tr>
<td>
    <span class="etiqueta">Teléfono:</span> ' . limpiar($factura['telefono_cliente']) . '
</td>
<td>
    <span class="etiqueta">Correo:</span> ' . limpiar($factura['correo_cliente']) . '
</td>
</tr>
<tr>
<td>
    <span class="etiqueta">Ciudad:</span> ' . limpiar($factura['ciudad_cliente']) . '
</td>
<td>
    <span class="etiqueta">Origen:</span> ' . limpiar($origen) . '
</td>
</tr>
<tr>
<td colspan="2">
    <span class="etiqueta">Dirección:</span> ' . limpiar($factura['direccion_cliente']) . '
</td>
</tr>
</table>

<div class="seccion">DETALLE DE PRODUCTOS</div>

<table class="productos">
<thead>
<tr>
<th width="8%" class="centro">N.º</th>
<th width="38%">Producto</th>
<th width="14%" class="centro">Cantidad</th>
<th width="20%" class="derecha">Valor unitario</th>
<th width="20%" class="derecha">Subtotal</th>
</tr>
</thead>
<tbody>';

if (count($detalles) > 0) {
    $contador = 1;

    foreach ($detalles as $detalle) {
        $html .= '
        <tr>
            <td class="centro">' . $contador . '</td>
            <td>' . limpiar($detalle['producto']) . '</td>
            <td class="centro">' . limpiar($detalle['cantidad']) . '</td>
            <td class="derecha">' . pesos($detalle['valor']) . '</td>
            <td class="derecha">' . pesos($detalle['subtotal']) . '</td>
        </tr>';

        $contador++;
    }
} else {
    $html .= '
    <tr>
        <td colspan="5" class="centro">
            No hay productos registrados en esta factura.
        </td>
    </tr>';
}

$html .= '
</tbody>
</table>

<table class="totales">
<tr>
    <td>Subtotal</td>
    <td class="derecha">' . pesos($subtotal) . '</td>
</tr>
<tr>
    <td>IVA</td>
    <td class="derecha">' . pesos($iva) . '</td>
</tr>
<tr class="total-final">
    <td>TOTAL</td>
    <td class="derecha">' . pesos($total) . '</td>
</tr>
</table>

<div class="seccion">INFORMACIÓN DE PAGO</div>

<table class="info">
<tr>
<td width="50%">
    <span class="etiqueta">Método de pago:</span> ' . limpiar($factura['metodo_pago']) . '
</td>
<td width="50%">
    <span class="etiqueta">Estado:</span> ' . limpiar($factura['estado']) . '
</td>
</tr>
</table>

<div class="pie">
    ' . limpiar($nombreEmpresa) . '<br>
    Documento generado electrónicamente por el sistema YCL Solutions.
</div>

</body>
</html>';

/* GENERAR Y DESCARGAR PDF */

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

$nombreArchivo = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '_',
    (string)$numeroFactura
);

$dompdf->stream('Factura_' . $nombreArchivo . '.pdf', [
    'Attachment' => true
]);

exit;