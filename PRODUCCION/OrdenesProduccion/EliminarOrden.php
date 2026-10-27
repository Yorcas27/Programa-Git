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
        SELECT codigo, estado, fecha_entrega_real
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
        $conexion->rollback();
        responder(false, "La orden no existe o ya fue eliminada.", 404);
    }

    if (
        $orden["estado"] !== "Entregada" ||
        empty($orden["fecha_entrega_real"])
    ) {
        $conexion->rollback();

        responder(
            false,
            "Solo se pueden eliminar órdenes entregadas con fecha real registrada.",
            409
        );
    }

    $validarFecha = $conexion->prepare("
        SELECT
            fecha_entrega_real <= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AS elegible
        FROM ordenes_produccion
        WHERE id_orden = ?
    ");

    if (!$validarFecha) {
        throw new Exception("No fue posible validar la antigüedad.");
    }

    $validarFecha->bind_param("i", $idOrden);
    $validarFecha->execute();

    $fechaResultado = $validarFecha->get_result();
    $fechaRegistro = $fechaResultado->fetch_assoc();

    $validarFecha->close();

    if (!$fechaRegistro || !(int)$fechaRegistro["elegible"]) {
        $conexion->rollback();

        responder(
            false,
            "La orden todavía no cumple los 30 días desde su entrega real.",
            409
        );
    }

    $eliminar = $conexion->prepare("
        DELETE FROM ordenes_produccion
        WHERE id_orden = ?
          AND estado = 'Entregada'
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
        $conexion->rollback();

        responder(
            false,
            "La orden cambió o dejó de cumplir las condiciones para eliminarse.",
            409
        );
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
        "No se pudo eliminar la orden. Puede existir información relacionada que deba conservarse.",
        500
    );
}