
<?php
require_once("../../includes/conexion.php");

$id_cliente = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_cliente = filter_input(INPUT_POST, "id_cliente", FILTER_VALIDATE_INT);
}

if (!$id_cliente || $id_cliente < 1) {
    header("Location: Clientes.php?mensaje=" . urlencode("Cliente no válido.") . "&tipo=error");
    exit;
}

/* CONSULTAR CLIENTE */
$stmt = $conexion->prepare(
    "SELECT nombre, documento
     FROM clientes
     WHERE id_cliente = ?"
);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cliente) {
    header("Location: Clientes.php?mensaje=" . urlencode("El cliente no existe o ya fue eliminado.") . "&tipo=error");
    exit;
}

/* CONSULTAR CANTIDADES PARA CONFIRMACIÓN */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $consultas = [
        "pedidos" => "SELECT COUNT(*) AS total FROM pedidos WHERE id_cliente = ?",
        "ordenes" => "SELECT COUNT(*) AS total FROM ordenes_produccion WHERE id_cliente = ?",
        "facturas" => "SELECT COUNT(*) AS total
                       FROM facturas f
                       WHERE f.id_cliente = ?
                          OR f.id_pedido IN (
                              SELECT p.id_pedido
                              FROM pedidos p
                              WHERE p.id_cliente = ?
                          )",
        "detalles_pedido" => "SELECT COUNT(*) AS total
                              FROM detalle_pedido dp
                              INNER JOIN pedidos p ON p.id_pedido = dp.id_pedido
                              WHERE p.id_cliente = ?",
        "productos_terminados" => "SELECT COUNT(*) AS total
                                   FROM productos_terminados pt
                                   INNER JOIN ordenes_produccion op ON op.id_orden = pt.id_orden
                                   WHERE op.id_cliente = ?"
    ];

    $cantidades = [];

    foreach ($consultas as $clave => $sql) {
        $stmt = $conexion->prepare($sql);

        if ($clave === "facturas") {
            $stmt->bind_param("ii", $id_cliente, $id_cliente);
        } else {
            $stmt->bind_param("i", $id_cliente);
        }

        $stmt->execute();
        $cantidades[$clave] = (int) $stmt->get_result()->fetch_assoc()["total"];
        $stmt->close();
    }

    $stmt = $conexion->prepare(
        "SELECT COUNT(*) AS total
         FROM detalle_factura df
         INNER JOIN facturas f ON f.id_factura = df.id_factura
         WHERE f.id_cliente = ?
            OR f.id_pedido IN (
                SELECT p.id_pedido
                FROM pedidos p
                WHERE p.id_cliente = ?
            )"
    );
    $stmt->bind_param("ii", $id_cliente, $id_cliente);
    $stmt->execute();
    $cantidades["detalles_factura"] = (int) $stmt->get_result()->fetch_assoc()["total"];
    $stmt->close();

    $nombre = htmlspecialchars($cliente["nombre"], ENT_QUOTES, "UTF-8");
    $documento = htmlspecialchars($cliente["documento"] ?? "", ENT_QUOTES, "UTF-8");
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Confirmar eliminación | YCL Solutions</title>
        <style>
            *{box-sizing:border-box}
            body{margin:0;padding:30px;background:#f4f6f9;font-family:"Segoe UI",Arial,sans-serif;color:#333}
            .contenedor{max-width:680px;margin:40px auto;background:#fff;padding:30px;border-radius:10px;box-shadow:0 3px 12px #00000012}
            h2{margin-top:0;color:#1f3f6d}
            .aviso{padding:15px;background:#fff3cd;border-left:5px solid #f39c12;border-radius:5px;line-height:1.6}
            ul{line-height:1.9;padding-left:25px}
            .botones{display:flex;gap:10px;flex-wrap:wrap;margin-top:25px}
            .boton{display:inline-block;border:0;border-radius:5px;padding:11px 18px;color:#fff;text-decoration:none;font-size:15px;cursor:pointer}
            .eliminar{background:#e74c3c}
            .cancelar{background:#1f3f6d}
        </style>
    </head>
    <body>
        <main class="contenedor">
            <h2>Confirmar eliminación del cliente</h2>
            <p>Cliente: <strong><?= $nombre ?></strong></p>
            <p>Documento: <strong><?= $documento ?></strong></p>

            <div class="aviso">
                Esta acción es definitiva. También se eliminarán los registros dependientes que se muestran a continuación.
            </div>

            <ul>
                <li><?= $cantidades["pedidos"] ?> pedido(s) y sus detalles.</li>
                <li><?= $cantidades["facturas"] ?> factura(s) y <?= $cantidades["detalles_factura"] ?> detalle(s) de factura.</li>
                <li><?= $cantidades["ordenes"] ?> orden(es) de producción.</li>
                <li><?= $cantidades["productos_terminados"] ?> registro(s) de productos terminados asociados a esas órdenes.</li>
                <li>El registro del cliente.</li>
            </ul>

            <p>Los productos del catálogo y los insumos independientes se conservarán.</p>

            <div class="botones">
                <form method="POST" action="EliminarCliente.php">
                    <input type="hidden" name="id_cliente" value="<?= (int) $id_cliente ?>">
                    <button type="submit" class="boton eliminar"
                        onclick="return confirm('¿Confirma la eliminación definitiva del cliente y de todos los registros relacionados indicados?');">
                        Sí, eliminar todo
                    </button>
                </form>

                <a href="Clientes.php" class="boton cancelar">Cancelar</a>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}

/* ELIMINAR EN TRANSACCIÓN */
$conexion->begin_transaction();

try {
    /* ELIMINAR DETALLES DE FACTURAS DEL CLIENTE O DE SUS PEDIDOS */
    $stmt = $conexion->prepare(
        "DELETE df
         FROM detalle_factura df
         INNER JOIN facturas f ON f.id_factura = df.id_factura
         WHERE f.id_cliente = ?
            OR f.id_pedido IN (
                SELECT p.id_pedido
                FROM pedidos p
                WHERE p.id_cliente = ?
            )"
    );
    $stmt->bind_param("ii", $id_cliente, $id_cliente);

    if (!$stmt->execute()) {
        throw new Exception("No fue posible eliminar los detalles de las facturas.");
    }
    $stmt->close();

    /* ELIMINAR FACTURAS */
    $stmt = $conexion->prepare(
        "DELETE FROM facturas
         WHERE id_cliente = ?
            OR id_pedido IN (
                SELECT id_pedido
                FROM pedidos
                WHERE id_cliente = ?
            )"
    );
    $stmt->bind_param("ii", $id_cliente, $id_cliente);

    if (!$stmt->execute()) {
        throw new Exception("No fue posible eliminar las facturas.");
    }
    $stmt->close();

    /* ELIMINAR DETALLES DE PEDIDOS */
    $stmt = $conexion->prepare(
        "DELETE dp
         FROM detalle_pedido dp
         INNER JOIN pedidos p ON p.id_pedido = dp.id_pedido
         WHERE p.id_cliente = ?"
    );
    $stmt->bind_param("i", $id_cliente);

    if (!$stmt->execute()) {
        throw new Exception("No fue posible eliminar los detalles de los pedidos.");
    }
    $stmt->close();

    /* ELIMINAR PEDIDOS */
    $stmt = $conexion->prepare(
        "DELETE FROM pedidos WHERE id_cliente = ?"
    );
    $stmt->bind_param("i", $id_cliente);

    if (!$stmt->execute()) {
        throw new Exception("No fue posible eliminar los pedidos.");
    }
    $stmt->close();

    /* ELIMINAR PRODUCTOS TERMINADOS DE LAS ÓRDENES DEL CLIENTE */
    $stmt = $conexion->prepare(
        "DELETE pt
         FROM productos_terminados pt
         INNER JOIN ordenes_produccion op ON op.id_orden = pt.id_orden
         WHERE op.id_cliente = ?"
    );
    $stmt->bind_param("i", $id_cliente);

    if (!$stmt->execute()) {
        throw new Exception("No fue posible eliminar los productos terminados asociados.");
    }
    $stmt->close();

    /* ELIMINAR ÓRDENES DE PRODUCCIÓN */
    $stmt = $conexion->prepare(
        "DELETE FROM ordenes_produccion WHERE id_cliente = ?"
    );
    $stmt->bind_param("i", $id_cliente);

    if (!$stmt->execute()) {
        throw new Exception("No fue posible eliminar las órdenes de producción.");
    }
    $stmt->close();

    /* ELIMINAR CLIENTE */
    $stmt = $conexion->prepare(
        "DELETE FROM clientes WHERE id_cliente = ?"
    );
    $stmt->bind_param("i", $id_cliente);

    if (!$stmt->execute()) {
        throw new Exception("No fue posible eliminar el cliente.");
    }

    if ($stmt->affected_rows !== 1) {
        throw new Exception("No se encontró el cliente para completar la eliminación.");
    }
    $stmt->close();

    $conexion->commit();

    header(
        "Location: Clientes.php?mensaje="
        . urlencode("Cliente y sus registros dependientes eliminados correctamente.")
        . "&tipo=success"
    );
    exit;

} catch (Throwable $e) {
    $conexion->rollback();

    error_log("Error al eliminar cliente {$id_cliente}: " . $e->getMessage());

    header(
        "Location: Clientes.php?mensaje="
        . urlencode("No se pudo eliminar el cliente. La operación fue revertida; revisa las relaciones pendientes.")
        . "&tipo=error"
    );
    exit;
}