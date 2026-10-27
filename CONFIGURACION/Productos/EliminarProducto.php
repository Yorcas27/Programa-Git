
<?php
require_once("../../includes/conexion.php");

$id_producto = (int)($_GET["id"] ?? 0);

if ($id_producto <= 0) {
    header("Location: Productos.php");
    exit;
}

function volverProductos($mensaje) {
    header("Location: Productos.php?mensaje=" . urlencode($mensaje));
    exit;
}

try {
    $conexion->begin_transaction();

    // Identificar el producto y su grupo.
    $stmt = $conexion->prepare(
        "SELECT id_producto, nombre, id_producto_principal
         FROM productos
         WHERE id_producto = ?
         FOR UPDATE"
    );
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$producto) {
        $conexion->rollback();
        volverProductos("El producto no existe.");
    }

    $idPrincipal = (int)($producto["id_producto_principal"] ?? 0);

    // Obtener todos los productos del grupo.
    if ($idPrincipal > 0) {
        $stmt = $conexion->prepare(
            "SELECT id_producto
             FROM productos
             WHERE id_producto_principal = ?
             FOR UPDATE"
        );
        $stmt->bind_param("i", $idPrincipal);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $idsProductos = [];

        while ($fila = $resultado->fetch_assoc()) {
            $idsProductos[] = (int)$fila["id_producto"];
        }

        $stmt->close();
    } else {
        $idsProductos = [$id_producto];
    }

    if (empty($idsProductos)) {
        $conexion->rollback();
        volverProductos("No se encontraron presentaciones para eliminar.");
    }

    // Consultar relaciones de todas las presentaciones.
    $marcadores = implode(",", array_fill(0, count($idsProductos), "?"));
    $tipos = str_repeat("i", count($idsProductos));

    $relaciones = [
        "detalle_pedido" => "pedidos",
        "detalle_factura" => "facturas",
        "ordenes_produccion" => "órdenes de producción"
    ];

    foreach ($relaciones as $tabla => $descripcion) {
        $sql = "SELECT COUNT(*) AS total
                FROM `$tabla`
                WHERE id_producto IN ($marcadores)";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($tipos, ...$idsProductos);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ((int)$fila["total"] > 0) {
            $conexion->rollback();
            volverProductos(
                "No se puede eliminar el producto porque tiene registros relacionados en $descripcion. Se recomienda conservarlo o desactivarlo."
            );
        }
    }

    // Eliminar las presentaciones del grupo.
    $sql = "DELETE FROM productos
            WHERE id_producto IN ($marcadores)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($tipos, ...$idsProductos);
    $stmt->execute();
    $stmt->close();

    // Eliminar el producto principal solo si ya no tiene presentaciones.
    if ($idPrincipal > 0) {
        $stmt = $conexion->prepare(
            "DELETE FROM productos_principales
             WHERE id_producto_principal = ?
               AND NOT EXISTS (
                   SELECT 1
                   FROM productos
                   WHERE id_producto_principal = ?
               )"
        );
        $stmt->bind_param("ii", $idPrincipal, $idPrincipal);
        $stmt->execute();
        $stmt->close();
    }

    
    $conexion->commit();

    volverProductos("El producto y sus presentaciones fueron eliminados correctamente.");

} catch (mysqli_sql_exception $e) {
    if ($conexion->errno === 0) {
        // No se requiere acción adicional.
    }

    try {
        $conexion->rollback();
    } catch (Throwable $errorRollback) {
        // La transacción pudo haberse cerrado previamente.
    }

    volverProductos(
        "No se pudo eliminar el producto porque existen restricciones o registros relacionados en la base de datos."
    );
}
?>