
<?php
session_start();
require_once("../../includes/conexion.php");

header("Content-Type: application/json; charset=UTF-8");

function responder($exito, $mensaje, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode(
        ["exito" => $exito, "mensaje" => $mensaje],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Método de solicitud no permitido.", 405);
}

$idOrden = filter_input(INPUT_POST, "id_orden", FILTER_VALIDATE_INT);
$nuevoEstado = trim($_POST["estado"] ?? "");
$cantidadFabricada = filter_input(INPUT_POST, "cantidad_fabricada", FILTER_VALIDATE_FLOAT);

$estadosPermitidos = [
    "Pendiente",
    "En Producción",
    "Finalizada",
    "Entregada",
    "Anulada"
];

if (!$idOrden || !in_array($nuevoEstado, $estadosPermitidos, true)) {
    responder(false, "Los datos enviados no son válidos.", 400);
}

if (
    $nuevoEstado === "Finalizada" &&
    (
        $cantidadFabricada === false ||
        $cantidadFabricada === null ||
        !is_finite((float)$cantidadFabricada) ||
        $cantidadFabricada <= 0
    )
) {
    responder(false, "Ingresa una cantidad fabricada válida mayor que cero.", 400);
}

try {
    $conexion->begin_transaction();

    $consulta = $conexion->prepare("
        SELECT o.id_orden, o.codigo, o.estado, o.destino,
               o.fecha_finalizacion, o.fecha_entrega_real,
               o.cantidad, o.id_producto,
               p.nombre AS producto, p.presentacion, p.precio,
               c.nombre AS categoria
        FROM ordenes_produccion o
        INNER JOIN productos p
            ON p.id_producto = o.id_producto
        INNER JOIN categorias_producto c
            ON c.id_categoria_producto = p.id_categoria_producto
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

    $destino = $orden["destino"];
    $estadoActual = $orden["estado"];

    if (!in_array($destino, ["PARA_STOCK", "POR_PEDIDO"], true)) {
        throw new Exception("La orden no tiene un destino de producción válido.");
    }

    if ($estadoActual === $nuevoEstado) {
        throw new Exception("La orden ya tiene ese estado.");
    }

    $transiciones = [
        "PARA_STOCK" => [
            "Pendiente" => ["En Producción", "Anulada"],
            "En Producción" => ["Finalizada", "Anulada"],
            "Finalizada" => [],
            "Entregada" => [],
            "Anulada" => []
        ],
        "POR_PEDIDO" => [
            "Pendiente" => ["En Producción", "Anulada"],
            "En Producción" => ["Finalizada", "Anulada"],
            "Finalizada" => ["Entregada"],
            "Entregada" => [],
            "Anulada" => []
        ]
    ];

    if (!in_array($nuevoEstado, $transiciones[$destino][$estadoActual] ?? [], true)) {
        throw new Exception(
            "No se permite cambiar de " . $estadoActual .
            " a " . $nuevoEstado .
            " para una orden con destino " . $destino . "."
        );
    }

    $fechaFinalizacion = $orden["fecha_finalizacion"];
    $fechaEntregaReal = $orden["fecha_entrega_real"];
    $cantidadFabricadaDB = null;

    if ($nuevoEstado === "Finalizada") {
        $cantidadFabricada = (float)$cantidadFabricada;

        if ($cantidadFabricada > (float)$orden["cantidad"]) {
            throw new Exception(
                "La cantidad fabricada no puede superar la cantidad solicitada."
            );
        }

        $cantidadFabricadaDB = $cantidadFabricada;
        $fechaFinalizacion = date("Y-m-d");

        if ($destino === "PARA_STOCK") {

            // Evitar registrar dos productos terminados para la misma orden.
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
                throw new Exception(
                    "Esta orden ya tiene un producto terminado registrado."
                );
            }

            // Generar código del producto terminado.
            $codigoQuery = $conexion->query("
                SELECT COALESCE(
                    MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)),
                    0
                ) AS ultimo
                FROM productos_terminados
                WHERE codigo REGEXP '^PT-[0-9]+$'
            ");

            if (!$codigoQuery) {
                throw new Exception(
                    "No fue posible generar el código del producto terminado."
                );
            }

            $ultimo = (int)$codigoQuery->fetch_assoc()["ultimo"];
            $codigoPT = "PT-" . str_pad($ultimo + 1, 4, "0", STR_PAD_LEFT);

            $estadoProducto = "Disponible";
            $descripcion = "Producto elaborado según la orden " . $orden["codigo"] . ".";
            $observaciones = "Cantidad realmente fabricada: " . $cantidadFabricada . ".";
            $fecha = date("Y-m-d");

            // Registrar el producto terminado.
            $insertar = $conexion->prepare("
                INSERT INTO productos_terminados
                (
                    id_orden, codigo, producto, categoria, unidad,
                    cantidad, precio, fecha, estado, descripcion, observaciones
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$insertar) {
                throw new Exception(
                    "No fue posible preparar el registro del producto terminado."
                );
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
                throw new Exception(
                    "No fue posible guardar el producto terminado."
                );
            }

            $idProductoTerminado = $conexion->insert_id;
            $insertar->close();

            // Crear el registro de inventario si todavía no existe.
            $crearInventario = $conexion->prepare("
                INSERT IGNORE INTO inventario_productos_terminados
                    (id_producto, stock, stock_minimo)
                VALUES (?, 0, 0)
            ");

            if (!$crearInventario) {
                throw new Exception(
                    "No fue posible preparar el registro de inventario."
                );
            }

            $idProducto = (int)$orden["id_producto"];
            $crearInventario->bind_param("i", $idProducto);
            $crearInventario->execute();
            $crearInventario->close();

            // Bloquear la existencia para evitar inconsistencias simultáneas.
            $consultarStock = $conexion->prepare("
                SELECT stock
                FROM inventario_productos_terminados
                WHERE id_producto = ?
                FOR UPDATE
            ");

            if (!$consultarStock) {
                throw new Exception("No fue posible consultar las existencias.");
            }

            $consultarStock->bind_param("i", $idProducto);
            $consultarStock->execute();
            $registroStock = $consultarStock->get_result()->fetch_assoc();
            $consultarStock->close();

            if (!$registroStock) {
                throw new Exception("No se encontró el registro de inventario.");
            }

            $stockAnterior = (float)$registroStock["stock"];
            $stockResultante = $stockAnterior + $cantidadFabricada;

            // Actualizar el inventario de productos terminados.
            $actualizarStock = $conexion->prepare("
                UPDATE inventario_productos_terminados
                SET stock = ?
                WHERE id_producto = ?
            ");

            if (!$actualizarStock) {
                throw new Exception("No fue posible preparar la actualización del inventario.");
            }

            $actualizarStock->bind_param(
                "di",
                $stockResultante,
                $idProducto
            );

            if (!$actualizarStock->execute()) {
                throw new Exception("No fue posible actualizar las existencias.");
            }

            $actualizarStock->close();

            // Registrar la entrada de inventario asociada al producto terminado.
            $tipoMovimiento = "Entrada";
            $observacionMovimiento =
                "Entrada por finalización de la orden " .
                $orden["codigo"] . ".";

            $movimiento = $conexion->prepare("
                INSERT INTO movimientos_productos_terminados
                (
                    id_producto,
                    tipo,
                    cantidad,
                    stock_anterior,
                    stock_resultante,
                    id_producto_terminado,
                    observaciones
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$movimiento) {
                throw new Exception("No fue posible preparar el movimiento de inventario.");
            }

            $movimiento->bind_param(
                "isdddis",
                $idProducto,
                $tipoMovimiento,
                $cantidadFabricada,
                $stockAnterior,
                $stockResultante,
                $idProductoTerminado,
                $observacionMovimiento
            );

            if (!$movimiento->execute()) {
                throw new Exception("No fue posible registrar la entrada de inventario.");
            }

            $movimiento->close();
        }
    }

    if ($nuevoEstado === "Entregada") {
        $fechaEntregaReal = date("Y-m-d");
    }

    // Actualizar el estado de la orden.
    $actualizar = $conexion->prepare("
        UPDATE ordenes_produccion
        SET estado = ?,
            fecha_finalizacion = ?,
            fecha_entrega_real = ?,
            cantidad_fabricada = COALESCE(?, cantidad_fabricada)
        WHERE id_orden = ?
    ");

    if (!$actualizar) {
        throw new Exception("No fue posible preparar la actualización de la orden.");
    }

    $actualizar->bind_param(
        "sssdi",
        $nuevoEstado,
        $fechaFinalizacion,
        $fechaEntregaReal,
        $cantidadFabricadaDB,
        $idOrden
    );

    if (!$actualizar->execute()) {
        throw new Exception("No fue posible actualizar el estado de la orden.");
    }

    $actualizar->close();

    $conexion->commit();

    if ($nuevoEstado === "Finalizada" && $destino === "PARA_STOCK") {
        $mensaje =
            "Orden finalizada. Producto terminado registrado y existencias actualizadas correctamente.";
    } elseif ($nuevoEstado === "Finalizada" && $destino === "POR_PEDIDO") {
        $mensaje =
            "Orden finalizada. Cantidad fabricada registrada para el pedido, sin ingresar al inventario disponible.";
    } else {
        $mensaje = "Estado actualizado correctamente.";
    }

    responder(true, $mensaje);

} catch (Throwable $e) {
    try {
        $conexion->rollback();
    } catch (Throwable $ignorado) {
    }

    error_log("CambiarEstadoOrden.php: " . $e->getMessage());
    responder(false, $e->getMessage(), 400);
}
