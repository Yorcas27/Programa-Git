
<?php

$titulo = "Ver Compra";
$css = "Compras.css";

require_once "../../includes/conexion.php";
require_once "../../includes/header.php";

$id_compra = (int)($_GET['id'] ?? 0);

if($id_compra <= 0){
    header("Location: Compras.php");
    exit;
}

/* INFORMACIÓN DE LA COMPRA */

$sql = "SELECT
            c.*,
            CASE
                WHEN c.tipo_proveedor = 'Casual'
                    THEN c.nombre_proveedor_casual
                ELSE p.nombre
            END AS proveedor
        FROM compras c
        LEFT JOIN proveedores p
            ON c.id_proveedor = p.id_proveedor
        WHERE c.id_compra = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_compra);
$stmt->execute();

$compra = $stmt->get_result()->fetch_assoc();

if(!$compra){
    header("Location: Compras.php");
    exit;
}

/* DETALLE DE LA COMPRA */

$detalles = null;

if($compra['tipo_compra'] === 'Insumos'){

    $sqlDetalle = "SELECT
                        d.*,
                        i.producto AS insumo
                   FROM detalle_compra d
                   INNER JOIN insumos i
                        ON d.id_insumo = i.id_insumo
                   WHERE d.id_compra = ?
                   ORDER BY d.id_detalle_compra ASC";

    $stmtDetalle = $conexion->prepare($sqlDetalle);
    $stmtDetalle->bind_param("i", $id_compra);
    $stmtDetalle->execute();

    $detalles = $stmtDetalle->get_result();

}else{

    $sqlDetalle = "SELECT
                        *
                   FROM detalle_compra_otro
                   WHERE id_compra = ?
                   ORDER BY id_detalle_otro ASC";

    $stmtDetalle = $conexion->prepare($sqlDetalle);
    $stmtDetalle->bind_param("i", $id_compra);
    $stmtDetalle->execute();

    $detalles = $stmtDetalle->get_result();
}

/* FORMATO DE CANTIDADES */

function formatoCantidad($cantidad){
    return rtrim(rtrim(number_format((float)$cantidad, 2, ',', '.'), '0'), ',');
}

?>

<div class="contenedor compras-modulo">

    <div class="barra-superior">
        <a href="Compras.php" class="btn-volver">← Volver a Compras</a>
        <a href="../../index.php" class="btn-inicio">⌂ Dashboard</a>
    </div>

    <div class="encabezado">
        <h1>VER COMPRA</h1>
        <p>Consulta la información y el detalle de la compra.</p>
    </div>

    <div class="panel">

        <div class="panel-header">
            <div>
                <h2>Información de la Compra</h2>
                <p>Datos generales de la compra registrada.</p>
            </div>
        </div>

        <div class="datos-compra">

            <div>
                <strong>Código</strong>
                <span><?= htmlspecialchars($compra['codigo_compra']) ?></span>
            </div>

            <div>
                <strong>Fecha</strong>
                <span><?= date('d/m/Y', strtotime($compra['fecha'])) ?></span>
            </div>

            <div>
                <strong>Proveedor</strong>
                <span><?= htmlspecialchars($compra['proveedor'] ?? 'Sin proveedor') ?></span>
            </div>

            <div>
                <strong>Tipo de proveedor</strong>
                <span><?= htmlspecialchars($compra['tipo_proveedor']) ?></span>
            </div>

            <div>
                <strong>Tipo de compra</strong>
                <span><?= htmlspecialchars($compra['tipo_compra']) ?></span>
            </div>

            <?php if($compra['tipo_proveedor'] === 'Casual' && !empty($compra['documento_proveedor_casual'])): ?>

                <div>
                    <strong>Documento</strong>
                    <span><?= htmlspecialchars($compra['documento_proveedor_casual']) ?></span>
                </div>

            <?php endif; ?>

            <div>
                <strong>Estado</strong>
                <span class="estado <?= strtolower($compra['estado']) ?>">
                    <?= htmlspecialchars($compra['estado']) ?>
                </span>
            </div>

        </div>

        <?php if(!empty($compra['observaciones'])): ?>

            <div class="observaciones">
                <strong>Observaciones</strong>
                <p><?= nl2br(htmlspecialchars($compra['observaciones'])) ?></p>
            </div>

        <?php endif; ?>

    </div>

    <div class="panel">

        <div class="panel-header">
            <div>

                <h2>Detalle de la Compra</h2>

                <?php if($compra['tipo_compra'] === 'Insumos'): ?>

                    <p>Insumos incluidos en esta compra.</p>

                <?php else: ?>

                    <p>Conceptos incluidos en esta compra.</p>

                <?php endif; ?>

            </div>
        </div>

        <div class="tabla-contenedor">

            <table>

                <thead>

                    <?php if($compra['tipo_compra'] === 'Insumos'): ?>

                        <tr>
                            <th>Insumo</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                        </tr>

                    <?php else: ?>

                        <tr>
                            <th>Concepto</th>
                            <th>Descripción</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                        </tr>

                    <?php endif; ?>

                </thead>

                <tbody>

                    <?php if($detalles && $detalles->num_rows > 0): ?>

                        <?php while($detalle = $detalles->fetch_assoc()): ?>

                            <?php if($compra['tipo_compra'] === 'Insumos'): ?>

                                <tr>

                                    <td>
                                        <?= htmlspecialchars($detalle['insumo']) ?>
                                    </td>

                                    <td>
                                        <?= formatoCantidad($detalle['cantidad']) ?>
                                    </td>

                                    <td>
                                        $<?= number_format($detalle['precio'], 0, ',', '.') ?>
                                    </td>

                                    <td>
                                        $<?= number_format($detalle['subtotal'], 0, ',', '.') ?>
                                    </td>

                                </tr>

                            <?php else: ?>

                                <tr>

                                    <td>
                                        <?= htmlspecialchars($detalle['concepto']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($detalle['descripcion'] ?? '') ?>
                                    </td>

                                    <td>
                                        <?= formatoCantidad($detalle['cantidad']) ?>
                                    </td>

                                    <td>
                                        $<?= number_format($detalle['precio'], 0, ',', '.') ?>
                                    </td>

                                    <td>
                                        $<?= number_format($detalle['subtotal'], 0, ',', '.') ?>
                                    </td>

                                </tr>

                            <?php endif; ?>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td
                                colspan="<?= $compra['tipo_compra'] === 'Insumos' ? '4' : '5' ?>"
                                class="sin-datos"
                            >
                                No hay detalles registrados.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="resumen-compra">

            <p>
                <strong>Subtotal:</strong>
                $<?= number_format($compra['subtotal'], 0, ',', '.') ?>
            </p>

            <p>
                <strong>IVA:</strong>
                $<?= number_format($compra['iva'], 0, ',', '.') ?>
            </p>

            <p>
                <strong>Total:</strong>
                $<?= number_format($compra['total'], 0, ',', '.') ?>
            </p>

        </div>

    </div>

    <div class="acciones-finales">

        <a href="Compras.php" class="btn-volver">
            ← Volver a Compras
        </a>

    </div>

</div>

<?php require_once "../../includes/footer.php"; ?>

