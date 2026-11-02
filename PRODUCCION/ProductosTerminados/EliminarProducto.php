
<?php
require_once("../../includes/conexion.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ProductosTerminados.php");
    exit;
}

$id = isset($_POST["id_producto_terminado"])
    ? (int)$_POST["id_producto_terminado"]
    : 0;

if ($id <= 0) {
    header("Location: ProductosTerminados.php?eliminacion=error");
    exit;
}

$stmt = $conexion->prepare("
    DELETE FROM productos_terminados
    WHERE id_producto_terminado = ?
      AND fecha < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
      AND id_orden IS NULL
");

if (!$stmt) {
    error_log("Error al preparar eliminación de producto: " . $conexion->error);
    header("Location: ProductosTerminados.php?eliminacion=error");
    exit;
}

$stmt->bind_param("i", $id);

if (!$stmt->execute()) {
    error_log("Error al eliminar producto terminado: " . $stmt->error);
    $stmt->close();
    header("Location: ProductosTerminados.php?eliminacion=error");
    exit;
}

$eliminados = $stmt->affected_rows;
$stmt->close();

if ($eliminados === 1) {
    header("Location: ProductosTerminados.php?eliminacion=correcta");
} else {
    header("Location: ProductosTerminados.php?eliminacion=no_permitida");
}

exit;
