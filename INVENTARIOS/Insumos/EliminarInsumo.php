
<?php

require_once "../../includes/conexion.php";

$id = $_GET['id'] ?? '';

if(!is_numeric($id)){

    header("Location: Insumos.php");
    exit;

}


/* VERIFICAR QUE EXISTA */

$stmt = $conexion->prepare(
    "SELECT id_insumo
     FROM insumos
     WHERE id_insumo = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();

if($resultado->num_rows === 0){

    $stmt->close();

    header("Location: Insumos.php");
    exit;

}

$stmt->close();


/* VERIFICAR COMPRAS */

$stmt = $conexion->prepare(
    "SELECT COUNT(*) AS total
     FROM detalle_compra
     WHERE id_insumo = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$compras = $resultado->fetch_assoc()['total'];

$stmt->close();


/* ELIMINAR */

if($compras > 0){

    echo "<script>
        alert('No se puede eliminar este insumo porque tiene compras relacionadas.');
        window.location.href='Insumos.php';
    </script>";

    exit;
}


$stmt = $conexion->prepare(
    "DELETE FROM insumos
     WHERE id_insumo = ?"
);

$stmt->bind_param("i", $id);

if($stmt->execute()){

    $stmt->close();

    header("Location: Insumos.php");
    exit;

}

$stmt->close();

echo "<script>
    alert('No fue posible eliminar el insumo.');
    window.location.href='Insumos.php';
</script>";

exit;

?>

