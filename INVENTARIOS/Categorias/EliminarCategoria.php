
<?php
require_once("../../includes/conexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: Categorias.php");
    exit;
}

$idCategoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);

if (!$idCategoria || $idCategoria <= 0) {
    header("Location: Categorias.php?mensaje=id_invalido");
    exit;
}

// Verificar que la categoría exista
$sql = "SELECT id_categoria FROM categorias WHERE id_categoria = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $idCategoria);
$stmt->execute();

$resultado = $stmt->get_result();
$categoria = $resultado->fetch_assoc();
$stmt->close();

if (!$categoria) {
    header("Location: Categorias.php?mensaje=no_encontrada");
    exit;
}

// Verificar insumos asociados
$sql = "SELECT COUNT(*) AS total
        FROM insumos
        WHERE id_categoria = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $idCategoria);
$stmt->execute();

$resultado = $stmt->get_result();
$fila = $resultado->fetch_assoc();
$totalInsumos = (int)$fila['total'];
$stmt->close();

if ($totalInsumos > 0) {
    header("Location: Categorias.php?mensaje=tiene_insumos");
    exit;
}

// Eliminar categoría sin insumos asociados
$sql = "DELETE FROM categorias WHERE id_categoria = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $idCategoria);

if ($stmt->execute() && $stmt->affected_rows === 1) {
    $stmt->close();
    header("Location: Categorias.php?mensaje=eliminada");
    exit;
}

$codigoError = $stmt->errno;
$stmt->close();

if ($codigoError === 1451) {
    header("Location: Categorias.php?mensaje=tiene_insumos");
} else {
    header("Location: Categorias.php?mensaje=error_eliminar");
}

exit;
