
<?php
require_once("../includes/config.php");
require_once("../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Facturacion.php");
    exit;
}

$idFactura = filter_input(INPUT_POST, "id_factura", FILTER_VALIDATE_INT);

if (!$idFactura || $idFactura <= 0) {
    header("Location: Facturacion.php?error=id");
    exit;
}

try {
    $conexion->begin_transaction();

    $sql = "SELECT fecha
            FROM facturas
            WHERE id_factura = ?
            FOR UPDATE";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idFactura);
    $stmt->execute();
    $factura = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$factura) {
        throw new Exception("La factura no existe.");
    }

    $fechaFactura = new DateTime($factura["fecha"]);
    $hoy = new DateTime("today");
    $diasTranscurridos = (int) $fechaFactura->diff($hoy)->format("%r%a");

    if ($diasTranscurridos < 30) {
        throw new Exception("La factura todavía no cumple 30 días.");
    }

    $sql = "DELETE FROM detalle_factura WHERE id_factura = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idFactura);
    $stmt->execute();
    $stmt->close();

    $sql = "DELETE FROM facturas WHERE id_factura = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idFactura);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new Exception("No se pudo eliminar la factura.");
    }

    $stmt->close();
    $conexion->commit();

    header("Location: Facturacion.php?eliminada=1");
    exit;

} catch (Throwable $e) {
    $conexion->rollback();
    error_log("Error al eliminar factura: " . $e->getMessage());

    header("Location: Facturacion.php?error=eliminar");
    exit;
}