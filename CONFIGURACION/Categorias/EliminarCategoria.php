<?php

require_once("../../includes/conexion.php");


// Obtener ID

$id_categoria = (int)($_GET["id"] ?? 0);


if ($id_categoria <= 0) {

    header("Location: Categorias.php");
    exit;

}


// Obtener categoría

$stmt = $conexion->prepare(
    "SELECT nombre
     FROM categorias_producto
     WHERE id_categoria_producto = ?"
);

$stmt->bind_param(
    "i",
    $id_categoria
);

$stmt->execute();

$resultado = $stmt->get_result();

$categoria = $resultado->fetch_assoc();

$stmt->close();


if (!$categoria) {

    header(
        "Location: Categorias.php?mensaje="
        . urlencode("La categoría no existe.")
    );

    exit;

}


// Verificar productos relacionados

$stmt = $conexion->prepare(
    "SELECT COUNT(*) AS total
     FROM productos
     WHERE id_categoria_producto = ?"
);

$stmt->bind_param(
    "i",
    $id_categoria
);

$stmt->execute();

$resultado = $stmt->get_result();

$datos = $resultado->fetch_assoc();

$stmt->close();


if ((int)$datos["total"] > 0) {

    header(
        "Location: Categorias.php?mensaje="
        . urlencode(
            "No se puede eliminar la categoría porque tiene productos asociados."
        )
    );

    exit;

}


// Eliminar categoría

try {

    $stmt = $conexion->prepare(
        "DELETE FROM categorias_producto
         WHERE id_categoria_producto = ?"
    );

    $stmt->bind_param(
        "i",
        $id_categoria
    );

    $stmt->execute();

    $stmt->close();


    header(
        "Location: Categorias.php?mensaje="
        . urlencode(
            "La categoría fue eliminada correctamente."
        )
    );

    exit;


} catch (mysqli_sql_exception $e) {

    header(
        "Location: Categorias.php?mensaje="
        . urlencode(
            "No fue posible eliminar la categoría."
        )
    );

    exit;

}

?>