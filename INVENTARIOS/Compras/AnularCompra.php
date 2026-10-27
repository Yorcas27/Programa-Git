
<?php

$titulo = "Anular Compra";
$css = "Compras.css";

require_once "../../includes/conexion.php";

$id_compra = (int)($_GET['id'] ?? $_POST['id_compra'] ?? 0);

if($id_compra <= 0){
    header("Location: Compras.php");
    exit;
}

$sql = "SELECT
            c.*,
            p.nombre AS proveedor
        FROM compras c
        INNER JOIN proveedores p
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

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if($compra['estado'] === 'Anulada'){
        header("Location: Compras.php");
        exit;
    }

    $stmtAnular = $conexion->prepare(
        "UPDATE compras
         SET estado = 'Anulada'
         WHERE id_compra = ?"
    );

    $stmtAnular->bind_param("i", $id_compra);
    $stmtAnular->execute();

    header("Location: Compras.php");
    exit;
}

require_once "../../includes/header.php";
?>

<div class="contenedor">

    <div class="barra-superior">
        <a href="Compras.php" class="btn-volver">← Volver a Compras</a>
        <a href="../../index.php" class="btn-inicio">⌂ Dashboard</a>
    </div>

    <div class="encabezado">
        <h1>ANULAR COMPRA</h1>
        <p>Confirma la anulación de la compra seleccionada.</p>
    </div>

    <div class="panel">

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
                <span><?= htmlspecialchars($compra['proveedor']) ?></span>
            </div>

            <div>
                <strong>Total</strong>
                <span>
                    $<?= number_format($compra['total'], 0, ',', '.') ?>
                </span>
            </div>

            <div>
                <strong>Estado</strong>
                <span class="estado <?= strtolower($compra['estado']) ?>">
                    <?= htmlspecialchars($compra['estado']) ?>
                </span>
            </div>

        </div>

        <div class="confirmacion">

            <h2>¿Desea anular esta compra?</h2>

            <p>
                La compra no será eliminada. Su estado cambiará a
                <strong>Anulada</strong>.
            </p>

        </div>

        <form method="POST" class="acciones-formulario">

            <input
                type="hidden"
                name="id_compra"
                value="<?= $id_compra ?>"
            >

            <button type="submit" class="btn btn-anular">
                Anular compra
            </button>

            <a href="Compras.php" class="btn btn-limpiar">
                Cancelar
            </a>

        </form>

    </div>

</div>

<?php require_once "../../includes/footer.php"; ?>

