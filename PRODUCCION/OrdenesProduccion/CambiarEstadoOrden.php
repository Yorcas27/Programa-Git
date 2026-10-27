<?php
session_start();
require_once("../../includes/conexion.php");
header("Content-Type: application/json; charset=UTF-8");

function responder($exito, $mensaje, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode(["exito" => $exito, "mensaje" => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método de solicitud no permitido.", 405);
}

$idOrden = filter_input(INPUT_POST, "id_orden", FILTER_VALIDATE_INT);
$nuevoEstado = trim($_POST["estado"] ?? "");
$cantidadFabricada = filter_input(INPUT_POST, "cantidad_fabricada", FILTER_VALIDATE_FLOAT);

$estadosPermitidos = ["Pendiente", "En Producción", "Finalizada", "Entregada", "Anulada"];

if (!$idOrden || !in_array($nuevoEstado, $estadosPermitidos, true)) {
    responder(false, "Los datos enviados no son válidos.", 400);
}

if ($nuevoEstado === "Finalizada" &&
    ($cantidadFabricada === false || $cantidadFabricada === null ||
    !is_finite((float)$cantidadFabricada) || $cantidadFabricada <= 0)) {
    responder(false, "Ingresa una cantidad fabricada válida mayor que cero.", 400);
}

try {
    $conexion->begin_transaction();

    $consulta = $conexion->prepare("
        SELECT o.codigo, o.estado, o.fecha_finalizacion, o.fecha_entrega_real,
               o.cantidad, o.id_producto, p.nombre AS producto,
               p.presentacion, p.precio, c.nombre AS categoria
        FROM ordenes_produccion o
        INNER JOIN productos p ON p.id_producto = o.id_producto
        INNER JOIN categorias_producto c ON c.id_categoria_producto = p.id_categoria_producto
        WHERE o.id_orden = ?
        FOR UPDATE
    ");

    if (!$consulta) {
        throw new Exception("No fue posible consultar la orden.");
    }

    $consulta->bind_param("i", $idOrden);
    $consulta->execute();
    $orden = $consulta->get_result()->fetch_assoc();
    $consulta->close();

    if (!$orden) {
        throw new Exception("La orden no existe o sus datos están incompletos.");
    }

    $estadoActual = $orden["estado"];

    if ($estadoActual === $nuevoEstado) {
        throw new Exception("La orden ya tiene ese estado.");
    }

    $transiciones = [
        "Pendiente" => ["En Producción", "Anulada"],
        "En Producción" => ["Finalizada", "Anulada"],
        "Finalizada" => ["Entregada"],
        "Entregada" => [],
        "Anulada" => []
    ];

    if (!in_array($nuevoEstado, $transiciones[$estadoActual] ?? [], true)) {
        throw new Exception("No se permite cambiar de " . $estadoActual . " a " . $nuevoEstado . ".");
    }

    $fechaFinalizacion = $orden["fecha_finalizacion"];
    $fechaEntregaReal = $orden["fecha_entrega_real"];

    if ($nuevoEstado === "Finalizada") {
        $cantidadFabricada = (float)$cantidadFabricada;

        if ($cantidadFabricada > (float)$orden["cantidad"]) {
            throw new Exception("La cantidad fabricada no puede superar la cantidad solicitada.");
        }

        $verificar = $conexion->prepare("
            SELECT id_producto_terminado
            FROM productos_terminados
            WHERE id_orden = ?
            LIMIT 1
            FOR UPDATE
        ");

        if (!$verificar) {
            throw new Exception("No fue posible comprobar el producto terminado.");
        }

        $verificar->bind_param("i", $idOrden);
        $verificar->execute();
        $existe = $verificar->get_result()->fetch_assoc();
        $verificar->close();

        if ($existe) {
            throw new Exception("Esta orden ya tiene un producto terminado registrado.");
        }

        $codigoQuery = $conexion->query("
            SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)), 0) AS ultimo
            FROM productos_terminados
            WHERE codigo REGEXP '^PT-[0-9]+$'
        ");

        if (!$codigoQuery) {
            throw new Exception("No fue posible generar el código del producto terminado.");
        }

        $ultimo = (int)$codigoQuery->fetch_assoc()["ultimo"];
        $codigoPT = "PT-" . str_pad($ultimo + 1, 4, "0", STR_PAD_LEFT);
        $fecha = date("Y-m-d");
        $estadoProducto = "Disponible";
        $descripcion = "Producto elaborado según la orden " . $orden["codigo"] . ".";
        $observaciones = "Cantidad realmente fabricada: " . $cantidadFabricada . ".";

        $insertar = $conexion->prepare("
            INSERT INTO productos_terminados
            (id_orden, codigo, producto, categoria, unidad, cantidad, precio,
             fecha, estado, descripcion, observaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$insertar) {
            throw new Exception("No fue posible preparar el registro del producto terminado.");
        }

        $insertar->bind_param(
            "issssddssss",
            $idOrden,
            $codigoPT,
            $orden["producto"],
            $orden["categoria"],
            $orden["presentacion"],
            $cantidadFabricada,
            $orden["precio"],
            $fecha,
            $estadoProducto,
            $descripcion,
            $observaciones
        );

        if (!$insertar->execute()) {
            if ($insertar->errno === 1062) {
                throw new Exception("Esta orden ya tiene un producto terminado registrado.");
            }

            throw new Exception("No fue posible guardar el producto terminado.");
        }

        $insertar->close();
        $fechaFinalizacion = $fecha;
    }

    if ($nuevoEstado === "Entregada") {
        $fechaEntregaReal = date("Y-m-d");
    }

    $actualizar = $conexion->prepare("
        UPDATE ordenes_produccion
        SET estado = ?, fecha_finalizacion = ?, fecha_entrega_real = ?
        WHERE id_orden = ?
    ");

    if (!$actualizar) {
        throw new Exception("No fue posible preparar la actualización de la orden.");
    }

    $actualizar->bind_param("sssi", $nuevoEstado, $fechaFinalizacion, $fechaEntregaReal, $idOrden);

    if (!$actualizar->execute()) {
        throw new Exception("No fue posible actualizar el estado de la orden.");
    }

    $actualizar->close();
    $conexion->commit();

    $mensaje = $nuevoEstado === "Finalizada"
        ? "Orden finalizada y producto terminado registrado correctamente."
        : "Estado actualizado correctamente.";

    responder(true, $mensaje);

} catch (Throwable $e) {
    try {
        $conexion->rollback();
    } catch (Throwable $ignorado) {
    }

    error_log("CambiarEstadoOrden.php: " . $e->getMessage());
    responder(false, $e->getMessage(), 400);
}