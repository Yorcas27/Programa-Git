
<?php
require_once("../includes/config.php");
require_once("../includes/conexion.php");

$id_factura = filter_input(INPUT_POST, "id_factura", FILTER_VALIDATE_INT);

if (!$id_factura || $id_factura < 1) {
    header("Location: Facturacion.php?error=consulta");
    exit;
}

$error = "";

try {
    $conexion->begin_transaction();

    /* BLOQUEAR Y CONSULTAR FACTURA */
    $sqlFactura = "
        SELECT id_factura, numero_factura, id_pedido, estado
        FROM facturas
        WHERE id_factura = ?
        FOR UPDATE
    ";

    $stmt = $conexion->prepare($sqlFactura);
    $stmt->bind_param("i", $id_factura);
    $stmt->execute();
    $factura = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$factura) {
        throw new Exception("La factura no existe.");
    }

    if ($factura["estado"] === "Anulada") {
        throw new Exception("Esta factura ya se encuentra anulada.");
    }

    if (!in_array($factura["estado"], ["Pendiente", "Pagada"], true)) {
        throw new Exception("El estado de la factura no permite anularla.");
    }

    /*
     * DEVOLVER EXISTENCIAS ÚNICAMENTE PARA VENTAS
     * DIRECTAS CON MOVIMIENTOS DE SALIDA REGISTRADOS.
     */
    if ($factura["id_pedido"] === null) {

        $sqlSalidas = "
            SELECT
                m.id_producto,
                m.cantidad,
                m.id_detalle_factura
            FROM movimientos_productos_terminados m
            INNER JOIN detalle_factura df
                ON df.id_detalle_factura = m.id_detalle_factura
            WHERE df.id_factura = ?
              AND m.tipo = 'Salida'
            ORDER BY m.id_movimiento ASC
            FOR UPDATE
        ";

        $stmtSalidas = $conexion->prepare($sqlSalidas);
        $stmtSalidas->bind_param("i", $id_factura);
        $stmtSalidas->execute();
        $resultadoSalidas = $stmtSalidas->get_result();

        $sqlInventario = "
            SELECT stock
            FROM inventario_productos_terminados
            WHERE id_producto = ?
            FOR UPDATE
        ";

        $stmtInventario = $conexion->prepare($sqlInventario);

        $sqlActualizar = "
            UPDATE inventario_productos_terminados
            SET stock = stock + ?
            WHERE id_producto = ?
        ";

        $stmtActualizar = $conexion->prepare($sqlActualizar);

        $sqlEntrada = "
            INSERT INTO movimientos_productos_terminados
            (
                id_producto,
                tipo,
                cantidad,
                stock_anterior,
                stock_resultante,
                id_detalle_factura,
                observaciones
            )
            VALUES (?, 'Entrada', ?, ?, ?, ?, ?)
        ";

        $stmtEntrada = $conexion->prepare($sqlEntrada);

        while ($salida = $resultadoSalidas->fetch_assoc()) {

            $id_producto = (int)$salida["id_producto"];
            $cantidad = (float)$salida["cantidad"];
            $id_detalle = (int)$salida["id_detalle_factura"];

            if ($cantidad <= 0) {
                throw new Exception("Se encontró una cantidad inválida en un movimiento.");
            }

            /* EVITAR DEVOLVER DOS VECES EL MISMO DETALLE */
            $sqlVerificarEntrada = "
                SELECT id_movimiento
                FROM movimientos_productos_terminados
                WHERE id_detalle_factura = ?
                  AND tipo = 'Entrada'
                LIMIT 1
                FOR UPDATE
            ";

            $stmtVerificar = $conexion->prepare($sqlVerificarEntrada);
            $stmtVerificar->bind_param("i", $id_detalle);
            $stmtVerificar->execute();
            $entradaExistente = $stmtVerificar->get_result()->fetch_assoc();
            $stmtVerificar->close();

            if ($entradaExistente) {
                throw new Exception(
                    "Ya existe una devolución registrada para un producto de esta factura."
                );
            }

            /* CONSULTAR STOCK ACTUAL */
            $stmtInventario->bind_param("i", $id_producto);
            $stmtInventario->execute();
            $inventario = $stmtInventario->get_result()->fetch_assoc();

            if (!$inventario) {
                throw new Exception(
                    "No existe el registro de inventario del producto ID " . $id_producto . "."
                );
            }

            $stockAnterior = (float)$inventario["stock"];
            $stockResultante = $stockAnterior + $cantidad;

            /* ACTUALIZAR EXISTENCIAS */
            $stmtActualizar->bind_param(
                "di",
                $cantidad,
                $id_producto
            );

            if (!$stmtActualizar->execute() || $stmtActualizar->affected_rows !== 1) {
                throw new Exception("No se pudo restaurar el inventario.");
            }

            /* REGISTRAR DEVOLUCIÓN */
            $observaciones = "Devolución por anulación de factura "
                . $factura["numero_factura"];

            $stmtEntrada->bind_param(
                "idddis",
                $id_producto,
                $cantidad,
                $stockAnterior,
                $stockResultante,
                $id_detalle,
                $observaciones
            );

            if (!$stmtEntrada->execute()) {
                throw new Exception("No se pudo registrar el movimiento de entrada.");
            }
        }

        $stmtSalidas->close();
        $stmtInventario->close();
        $stmtActualizar->close();
        $stmtEntrada->close();
    }

    /* CAMBIAR ESTADO DE LA FACTURA */
    $sqlAnular = "
        UPDATE facturas
        SET estado = 'Anulada'
        WHERE id_factura = ?
          AND estado IN ('Pendiente', 'Pagada')
    ";

    $stmtAnular = $conexion->prepare($sqlAnular);
    $stmtAnular->bind_param("i", $id_factura);

    if (!$stmtAnular->execute() || $stmtAnular->affected_rows !== 1) {
        throw new Exception("No se pudo actualizar el estado de la factura.");
    }

    $stmtAnular->close();

    $conexion->commit();

    header(
        "Location: VerFactura.php?id_factura="
        . $id_factura
        . "&anulada=1"
    );
    exit;

} catch (Throwable $e) {

    $conexion->rollback();

    error_log("Error al anular factura: " . $e->getMessage());

    $error = $e->getMessage();
}

header(
    "Location: VerFactura.php?id_factura="
    . $id_factura
    . "&error_anulacion="
    . urlencode($error)
);
exit;
