
<?php

require_once("../../includes/config.php");
require_once("../../includes/conexion.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id > 0){

    $stmt = $conexion->prepare(
        "DELETE FROM recetas
         WHERE id_receta = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt->close();
}

header("Location: Recetas.php");
exit;

?>

