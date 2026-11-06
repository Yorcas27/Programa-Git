
<?php
require_once("../../includes/conexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: Categorias.php");
    exit;
}

$idCategoria = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$estado = $_POST['estado'] ?? '';

if (!$idCategoria || $idCategoria <= 0) {
    header("Location: Categorias.php?mensaje=id_invalido");
    exit;
}

$longitudNombre = function_exists('mb_strlen')
    ? mb_strlen($nombre, 'UTF-8')
    : strlen($nombre);

if (
    $nombre === '' ||
    $longitudNombre > 100 ||
    !in_array($estado, ['Activa', 'Inactiva'], true)
) {
    header("Location: EditarCategoria.php?id=$idCategoria&error=datos");
    exit;
}

$sql = "UPDATE categorias
        SET nombre = ?, descripcion = ?, estado = ?
        WHERE id_categoria = ?";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    header("Location: EditarCategoria.php?id=$idCategoria&error=actualizar");
    exit;
}

$stmt->bind_param("sssi", $nombre, $descripcion, $estado, $idCategoria);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: Categorias.php?mensaje=actualizada");
    exit;
}

$codigoError = $stmt->errno;
$stmt->close();

if ($codigoError === 1062) {
    header("Location: EditarCategoria.php?id=$idCategoria&error=duplicado");
} else {
    header("Location: EditarCategoria.php?id=$idCategoria&error=actualizar");
}

exit;
