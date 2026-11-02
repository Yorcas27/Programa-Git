
<?php
require_once("../../includes/conexion.php");

header("Content-Type: application/json; charset=UTF-8");

function responder($exito, $mensaje, $codigo = 200) {
    http_response_code($codigo);

    echo json_encode([
        "exito" => $exito,
        "mensaje" => $mensaje
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método de solicitud no permitido.", 405);
}

$idOrden = filter_input(INPUT_POST, "id_orden", FILTER_VALIDATE_INT);

if (!$idOrden || $idOrden < 1) {
    responder(false, "El identificador de la orden no es válido.", 400);
}

try {
    $conexion->begin_transaction();

    $consulta = $conexion->prepare("
        SELECT
            codigo,
            estado,
            destino,
            fecha_entrega_real,
            fecha_entrega_real <= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AS elegible
        FROM ordenes_produccion
        WHERE id_orden = ?
        FOR UPDATE
    ");

    if (!$consulta) {
        throw new Exception("No fue posible preparar la consulta.");
    }

    $consulta->bind_param("i", $idOrden);
    $consulta->execute();

    $resultado = $consulta->get_result();
    $orden = $resultado->fetch_assoc();

    $consulta->close();

    if (!$orden) {
        throw new Exception("La orden no existe o ya fue eliminada.");
    }

    if ($orden["destino"] !== "POR_PEDIDO") {
        throw new Exception("Solo se pueden depurar órdenes generadas desde Pedidos.");
    }

    if (
        $orden["estado"] !== "Entregada" ||
        empty($orden["fecha_entrega_real"])
    ) {
        throw new Exception("Solo se pueden eliminar órdenes entregadas con fecha real registrada.");
    }

    if (!(int)$orden["elegible"]) {
        throw new Exception("La orden todavía no cumple los 30 días desde su entrega real.");
    }

    // Verificar registros asociados en productos terminados.
    $validarProductos = $conexion->prepare("
        SELECT COUNT(*) AS total
        FROM productos_terminados
        WHERE id_orden = ?
    ");

    if (!$validarProductos) {
        throw new Exception("No fue posible verificar los productos terminados relacionados.");
    }

    $validarProductos->bind_param("i", $idOrden);
    $validarProductos->execute();

    $resultadoProductos = $validarProductos->get_result();
    $productosRelacionados = $resultadoProductos->fetch_assoc();

    $validarProductos->close();

    if ((int)$productosRelacionados["total"] > 0) {
        throw new Exception(
            "No se puede eliminar la orden porque tiene productos terminados relacionados. Se debe conservar su historial de inventario."
        );
    }

    // Eliminar únicamente la orden que cumple todas las condiciones.
    $eliminar = $conexion->prepare("
        DELETE FROM ordenes_produccion
        WHERE id_orden = ?
          AND destino = 'POR_PEDIDO'
          AND estado = 'Entregada'
          AND fecha_entrega_real IS NOT NULL
          AND fecha_entrega_real <= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");

    if (!$eliminar) {
        throw new Exception("No fue posible preparar la eliminación.");
    }

    $eliminar->bind_param("i", $idOrden);

    if (!$eliminar->execute()) {
        throw new Exception("No fue posible eliminar la orden.");
    }

    if ($eliminar->affected_rows !== 1) {
        $eliminar->close();
        throw new Exception("La orden cambió o dejó de cumplir las condiciones para eliminarse.");
    }

    $eliminar->close();

    $conexion->commit();

    responder(
        true,
        "La orden " . $orden["codigo"] . " fue eliminada correctamente."
    );

} catch (Throwable $e) {

    try {
        $conexion->rollback();
    } catch (Throwable $ignorado) {
    }

    error_log("EliminarOrden.php: " . $e->getMessage());

    responder(
        false,
        $e->getMessage(),
        409
    );
}
