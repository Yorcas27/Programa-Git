
<?php

require_once("../../includes/conexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: NuevaCategoria.php");
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$estado = $_POST['estado'] ?? '';

if (
    $nombre === '' ||
    mb_strlen($nombre) > 100 ||
    !in_array($estado, ['Activa', 'Inactiva'], true)
) {
    header("Location: NuevaCategoria.php?error=datos");
    exit;
}

$sqlCodigo = "SELECT codigo
              FROM categorias
              WHERE codigo REGEXP '^CAT[0-9]+$'
              ORDER BY CAST(SUBSTRING(codigo, 4) AS UNSIGNED) DESC
              LIMIT 1";

$resultadoCodigo = $conexion->query($sqlCodigo);

if (!$resultadoCodigo) {
    header("Location: NuevaCategoria.php?error=guardar");
    exit;
}

$numero = 1;

if ($resultadoCodigo->num_rows > 0) {
    $ultimo = $resultadoCodigo->fetch_assoc()['codigo'];
    $numero = (int) substr($ultimo, 3) + 1;
}

$codigo = "CAT" . str_pad($numero, 3, "0", STR_PAD_LEFT);

$sql = "INSERT INTO categorias
        (codigo, nombre, descripcion, estado)
        VALUES (?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    header("Location: NuevaCategoria.php?error=guardar");
    exit;
}

$stmt->bind_param(
    "ssss",
    $codigo,
    $nombre,
    $descripcion,
    $estado
);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: Categorias.php?mensaje=creada");
    exit;
}

$error = $stmt->errno;
$stmt->close();

if ($error === 1062) {
    header("Location: NuevaCategoria.php?error=duplicado");
    exit;
}

header("Location: NuevaCategoria.php?error=guardar");
exit;

?>
