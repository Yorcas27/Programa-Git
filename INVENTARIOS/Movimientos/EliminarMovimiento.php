
<?php
require_once("../../includes/conexion.php");

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: Movimientos.php");
    exit;
}

try {
    $conexion->begin_transaction();

    $sql = "SELECT id_insumo, tipo, cantidad, estado, id_compra
            FROM movimientos
            WHERE id_movimiento = ?
            FOR UPDATE";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $movimiento = $stmt->get_result()->fetch_assoc();

    if (!$movimiento) {
        throw new Exception("El movimiento no existe.");
    }

    // Los movimientos vinculados a compras se gestionan desde Compras.
    if ($movimiento["id_compra"] !== null) {
        throw new Exception(
            "Este movimiento está vinculado a una compra. 
            Para evitar diferencias en el inventario, gestione la compra desde el módulo Compras."
        );
    }

    // Solo se revierte el stock si el movimiento estaba completado.
    if ($movimiento["estado"] === "Completado") {
        $id_insumo = (int) $movimiento["id_insumo"];
        $cantidad = (float) $movimiento["cantidad"];

        $sql = "SELECT stock
                FROM insumos
                WHERE id_insumo = ?
                FOR UPDATE";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_insumo);
        $stmt->execute();
        $insumo = $stmt->get_result()->fetch_assoc();

        if (!$insumo) {
            throw new Exception("El insumo asociado no existe.");
        }

        $stock_actual = (float) $insumo["stock"];

        if ($movimiento["tipo"] === "Entrada") {
            $nuevo_stock = $stock_actual - $cantidad;
        } else {
            $nuevo_stock = $stock_actual + $cantidad;
        }

        if ($nuevo_stock < 0) {
            throw new Exception(
                "No se puede eliminar el movimiento porque el stock resultante sería negativo."
            );
        }

        $estadoInsumo = $nuevo_stock > 0 ? "Disponible" : "Bajo Stock";

        $sql = "UPDATE insumos
                SET stock = ?, estado = ?
                WHERE id_insumo = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("dsi", $nuevo_stock, $estadoInsumo, $id_insumo);
        $stmt->execute();
    }

    $sql = "DELETE FROM movimientos WHERE id_movimiento = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("No se pudo eliminar el movimiento.");
    }

    $conexion->commit();

    header("Location: Movimientos.php");
    exit;

} catch (Throwable $e) {
    $conexion->rollback();

    echo "<script>
        alert(" . json_encode($e->getMessage()) . ");
        window.location.href = 'Movimientos.php';
    </script>";
    exit;
}
?>

