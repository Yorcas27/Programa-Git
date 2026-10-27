
<?php
$titulo = "Imprimir Factura";
$css = "ImprimirFactura.css";

include("../includes/config.php");
require_once("../includes/conexion.php");
include("../includes/header.php");

/* ID DE FACTURA */
$id_factura = filter_input(INPUT_GET, "id_factura", FILTER_VALIDATE_INT);

if (!$id_factura || $id_factura < 1) {
    die("Factura no válida.");
}

/* DATOS DE LA EMPRESA */
$sqlEmpresa = "SELECT
                    id_empresa,
                    nombre,
                    nit,
                    descripcion,
                    direccion,
                    ciudad,
                    telefono,
                    correo,
                    logo
               FROM empresa
               ORDER BY id_empresa ASC
               LIMIT 1";

$resultadoEmpresa = $conexion->query($sqlEmpresa);
$empresa = $resultadoEmpresa ? $resultadoEmpresa->fetch_assoc() : null;

if (!$empresa) {
    $empresa = [
        "nombre" => "YCL Solutions",
        "nit" => "",
        "descripcion" => "",
        "direccion" => "",
        "ciudad" => "",
        "telefono" => "",
        "correo" => "",
        "logo" => ""
    ];
}

/* DATOS DE LA FACTURA Y DEL CLIENTE */
$sqlFactura = "SELECT
                    f.id_factura,
                    f.numero_factura,
                    f.fecha,
                    f.metodo_pago,
                    f.estado,
                    f.total,
                    f.id_pedido,
                    f.id_cliente,
                    f.nombre_cliente,
                    f.documento_cliente,
                    f.telefono_cliente AS telefono_factura,
                    f.correo_cliente AS correo_factura,
                    p.codigo_pedido,
                    p.subtotal,
                    p.iva,
                    COALESCE(
                        NULLIF(f.nombre_cliente, ''),
                        NULLIF(c.nombre, ''),
                        NULLIF(p.nombre_cliente, ''),
                        'Cliente ocasional'
                    ) AS cliente,
                    COALESCE(
                        NULLIF(f.documento_cliente, ''),
                        NULLIF(c.documento, ''),
                        NULLIF(p.documento_cliente, ''),
                        ''
                    ) AS documento,
                    COALESCE(
                        NULLIF(f.telefono_cliente, ''),
                        NULLIF(c.telefono, ''),
                        NULLIF(p.telefono_cliente, ''),
                        ''
                    ) AS telefono_cliente,
                    COALESCE(
                        NULLIF(f.correo_cliente, ''),
                        NULLIF(c.correo, ''),
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
               WHERE f.id_factura = ?
               LIMIT 1";

$stmtFactura = $conexion->prepare($sqlFactura);

if (!$stmtFactura) {
    die("Error al consultar la factura.");
}

$stmtFactura->bind_param("i", $id_factura);
$stmtFactura->execute();

$resultadoFactura = $stmtFactura->get_result();
$factura = $resultadoFactura->fetch_assoc();

$stmtFactura->close();

if (!$factura) {
    die("La factura no existe.");
}

/* DETALLE DE LA FACTURA */
$sqlDetalle = "SELECT
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

$stmtDetalle = $conexion->prepare($sqlDetalle);

if (!$stmtDetalle) {
    die("Error al consultar el detalle de la factura.");
}

$stmtDetalle->bind_param("i", $id_factura);
$stmtDetalle->execute();

$resultadoDetalle = $stmtDetalle->get_result();

/* SUBTOTAL, IVA Y TOTAL */
$detalles = [];
$subtotalDetalle = 0;

while ($detalle = $resultadoDetalle->fetch_assoc()) {
    $detalles[] = $detalle;
    $subtotalDetalle += (float)$detalle["subtotal"];
}

$stmtDetalle->close();

$total = (float)$factura["total"];

if ($factura["subtotal"] !== null) {
    $subtotal = (float)$factura["subtotal"];
} else {
    $subtotal = $subtotalDetalle;
}

if ($factura["iva"] !== null) {
    $iva = (float)$factura["iva"];
} else {
    $iva = $total - $subtotal;
}

if (abs($iva) < 0.01) {
    $iva = 0;
}

/* ORIGEN DE LA FACTURA */
$origen = !empty($factura["id_pedido"])
    ? ($factura["codigo_pedido"] ?: "Pedido asociado")
    : "Venta directa";

/* LOGO DE LA EMPRESA */
$logo = trim($empresa["logo"] ?? "");

$logoRuta = "";
$logoValido = false;

if ($logo !== "") {
    if (filter_var($logo, FILTER_VALIDATE_URL)) {
        $logoRuta = $logo;
        $logoValido = true;
    } else {
        $logoRuta = "../" . ltrim($logo, "/");

        if (file_exists(__DIR__ . "/" . $logoRuta)) {
            $logoValido = true;
        }
    }
}
?>

<main class="pagina-impresion">

    <!-- BOTONES DE VISTA PREVIA -->
    <div class="barra-impresion">

        <a
            href="VerFactura.php?id_factura=<?= (int)$id_factura ?>"
            class="btn-volver"
        >
            ← Volver
        </a>

        <button
            type="button"
            class="btn-imprimir"
            onclick="window.print()"
        >
            🖨 Imprimir
        </button>

    </div>

    <!-- FACTURA -->
    <section class="factura-ticket">

        <!-- ENCABEZADO EMPRESA -->
        <header class="factura-encabezado">

            <?php if ($logoValido): ?>
                <img
                    src="<?= htmlspecialchars($logoRuta, ENT_QUOTES, "UTF-8") ?>"
                    alt="Logo de <?= htmlspecialchars($empresa["nombre"], ENT_QUOTES, "UTF-8") ?>"
                    class="logo-empresa"
                >
            <?php endif; ?>

            <h1>
                <?= htmlspecialchars($empresa["nombre"], ENT_QUOTES, "UTF-8") ?>
            </h1>

            <?php if (!empty($empresa["nit"])): ?>
                <p>NIT: <?= htmlspecialchars($empresa["nit"], ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <?php if (!empty($empresa["direccion"])): ?>
                <p><?= htmlspecialchars($empresa["direccion"], ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <?php if (!empty($empresa["ciudad"])): ?>
                <p><?= htmlspecialchars($empresa["ciudad"], ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <?php if (!empty($empresa["telefono"])): ?>
                <p>Tel: <?= htmlspecialchars($empresa["telefono"], ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

            <?php if (!empty($empresa["correo"])): ?>
                <p><?= htmlspecialchars($empresa["correo"], ENT_QUOTES, "UTF-8") ?></p>
            <?php endif; ?>

        </header>

        <div class="separador"></div>

        <!-- INFORMACIÓN DE FACTURA -->
        <section class="bloque-factura">

            <h2>FACTURA DE VENTA</h2>

            <p>
                <strong>No.:</strong>
                <?= htmlspecialchars($factura["numero_factura"], ENT_QUOTES, "UTF-8") ?>
            </p>

            <p>
                <strong>Fecha:</strong>
                <?= date("d/m/Y", strtotime($factura["fecha"])) ?>
            </p>

            <p>
                <strong>Origen:</strong>
                <?= htmlspecialchars($origen, ENT_QUOTES, "UTF-8") ?>
            </p>

        </section>

        <div class="separador"></div>

        <!-- DATOS DEL CLIENTE -->
        <section class="datos-cliente">

            <h3>CLIENTE</h3>

            <p>
                <strong>Nombre:</strong>
                <?= htmlspecialchars($factura["cliente"], ENT_QUOTES, "UTF-8") ?>
            </p>

            <?php if (!empty($factura["documento"])): ?>
                <p>
                    <strong>Documento:</strong>
                    <?= htmlspecialchars($factura["documento"], ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($factura["telefono_cliente"])): ?>
                <p>
                    <strong>Teléfono:</strong>
                    <?= htmlspecialchars($factura["telefono_cliente"], ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($factura["correo_cliente"])): ?>
                <p>
                    <strong>Correo:</strong>
                    <?= htmlspecialchars($factura["correo_cliente"], ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($factura["direccion_cliente"])): ?>
                <p>
                    <strong>Dirección:</strong>
                    <?= htmlspecialchars($factura["direccion_cliente"], ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

        </section>

        <div class="separador"></div>

        <!-- PRODUCTOS -->
        <section class="detalle-factura">

            <h3>DETALLE</h3>

            <?php if (!empty($detalles)): ?>

                <?php foreach ($detalles as $detalle): ?>

                    <article class="producto-ticket">

                        <div class="producto-nombre">
                            <?= htmlspecialchars($detalle["producto"], ENT_QUOTES, "UTF-8") ?>
                        </div>

                        <div class="producto-info">

                            <span>
                                <?= (int)$detalle["cantidad"] ?> x
                                $<?= number_format((float)$detalle["valor"], 0, ",", ".") ?>
                            </span>

                            <strong>
                                $<?= number_format((float)$detalle["subtotal"], 0, ",", ".") ?>
                            </strong>

                        </div>

                    </article>

                <?php endforeach; ?>

            <?php else: ?>

                <p class="sin-productos">
                    No hay productos registrados.
                </p>

            <?php endif; ?>

        </section>

        <div class="separador"></div>

        <!-- TOTALES -->
        <section class="totales">

            <div>
                <span>Subtotal</span>
                <strong>
                    $<?= number_format($subtotal, 0, ",", ".") ?>
                </strong>
            </div>

            <div>
                <span>IVA</span>
                <strong>
                    $<?= number_format($iva, 0, ",", ".") ?>
                </strong>
            </div>

            <div class="total-final">
                <span>TOTAL</span>
                <strong>
                    $<?= number_format($total, 0, ",", ".") ?>
                </strong>
            </div>

        </section>

        <div class="separador"></div>

        <!-- PAGO -->
        <section class="pago">

            <p>
                <strong>Método de pago:</strong>
                <?= htmlspecialchars($factura["metodo_pago"], ENT_QUOTES, "UTF-8") ?>
            </p>

            <p>
                <strong>Estado:</strong>
                <?= htmlspecialchars($factura["estado"], ENT_QUOTES, "UTF-8") ?>
            </p>

        </section>

        <!-- PIE -->
        <footer class="factura-pie">

            <p>Gracias por su compra</p>

            <p><?= htmlspecialchars($empresa["nombre"], ENT_QUOTES, "UTF-8") ?></p>

        </footer>

    </section>

</main>

<?php include("../includes/footer.php"); ?>