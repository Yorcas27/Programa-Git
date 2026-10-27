
<?php
require_once("../includes/conexion.php");

$id_pedido = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_pedido = filter_input(
        INPUT_POST,
        "id_pedido",
        FILTER_VALIDATE_INT
    );
}

if (!$id_pedido || $id_pedido < 1) {
    header("Location: Pedidos.php?mensaje=" . urlencode("Pedido no válido.") . "&tipo=error");
    exit;
}

/* CONSULTAR PEDIDO */
$stmt = $conexion->prepare(
    "SELECT codigo_pedido
     FROM pedidos
     WHERE id_pedido = ?"
);
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    header("Location: Pedidos.php?mensaje=" . urlencode("El pedido no existe o ya fue eliminado.") . "&tipo=error");
    exit;
}

/* CONFIRMAR ELIMINACIÓN */
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $stmt = $conexion->prepare(
        "SELECT COUNT(*) AS total
         FROM facturas
         WHERE id_pedido = ?"
    );
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
    $total_facturas = (int) $stmt->get_result()->fetch_assoc()["total"];
    $stmt->close();

    $stmt = $conexion->prepare(
        "SELECT COUNT(*) AS total
         FROM detalle_pedido
         WHERE id_pedido = ?"
    );
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
    $total_detalles = (int) $stmt->get_result()->fetch_assoc()["total"];
    $stmt->close();

    $stmt = $conexion->prepare(
        "SELECT COUNT(*) AS total
         FROM detalle_factura df
         INNER JOIN facturas f ON f.id_factura = df.id_factura
         WHERE f.id_pedido = ?"
    );
    $stmt->bind_param("i", $id_pedido);
    $stmt->execute();
    $total_detalles_factura = (int) $stmt->get_result()->fetch_assoc()["total"];
    $stmt->close();

    $codigo = htmlspecialchars($pedido["codigo_pedido"], ENT_QUOTES, "UTF-8");
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
            .contenedor{max-width:650px;margin:50px auto;background:#fff;padding:30px;border-radius:10px;box-shadow:0 3px 12px #00000012}
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
            <h2>Confirmar eliminación del pedido</h2>
            <p>Pedido: <strong><?= $codigo ?></strong></p>

            <div class="aviso">
                Esta acción es definitiva. Se eliminarán el pedido y los registros que dependan directamente de él, incluidas sus facturas asociadas.
            </div>

            <ul>
                <li><?= $total_detalles ?> detalle(s) del pedido.</li>
                <li><?= $total_facturas ?> factura(s) asociada(s).</li>
                <li><?= $total_detalles_factura ?> detalle(s) de factura.</li>
            </ul>

            <p>Los clientes, productos y demás registros independientes no se eliminarán.</p>

            <div class="botones">
                <form method="POST" action="EliminarPedido.php">
                    <input type="hidden" name="id_pedido" value="<?= (int) $id_pedido ?>">
                    <button type="submit" class="boton eliminar"
                        onclick="return confirm('¿Confirma la eliminación definitiva del pedido y todos los registros relacionados indicados?');">
                        Sí, eliminar todo
                    </button>
                </form>

                <a class="boton cancelar" href="Pedidos.php">Cancelar</a>
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
    /* ELIMINAR DETALLES DE LAS FACTURAS DEL PEDIDO */
    $stmt = $conexion->prepare(
        "DELETE df
         FROM detalle_factura df
         INNER JOIN facturas f ON f.id_factura = df.id_factura
         WHERE f.id_pedido = ?"
    );
    $stmt->bind_param("i", $id_pedido);

    if (!$stmt->execute()) {
        throw new Exception("No fue posible eliminar los detalles de las facturas.");
    }
    $stmt->close();

    /* ELIMINAR FACTURAS ASOCIADAS */
    $stmt = $conexion->prepare(
        "DELETE FROM facturas WHERE id_pedido = ?"
    );
    $stmt->bind_param("i", $id_pedido);

    if (!$stmt->execute()) {
        throw new Exception("No fue posible eliminar las facturas asociadas.");
    }
    $stmt->close();

    /* ELIMINAR DETALLES DEL PEDIDO */
    $stmt = $conexion->prepare(
        "DELETE FROM detalle_pedido WHERE id_pedido = ?"
    );
    $stmt->bind_param("i", $id_pedido);

    if (!$stmt->execute()) {
        throw new Exception("No fue posible eliminar los detalles del pedido.");
    }
    $stmt->close();

    /* ELIMINAR PEDIDO */
    $stmt = $conexion->prepare(
        "DELETE FROM pedidos WHERE id_pedido = ?"
    );
    $stmt->bind_param("i", $id_pedido);

    if (!$stmt->execute()) {
        throw new Exception("No fue posible eliminar el pedido.");
    }

    if ($stmt->affected_rows !== 1) {
        throw new Exception("No se encontró el pedido para completar la eliminación.");
    }
    $stmt->close();

    $conexion->commit();

    header(
        "Location: Pedidos.php?mensaje="
        . urlencode("Pedido {$pedido['codigo_pedido']} y todos sus registros dependientes eliminados correctamente.")
        . "&tipo=success"
    );
    exit;

} catch (Throwable $e) {
    $conexion->rollback();

    error_log("Error al eliminar pedido {$id_pedido}: " . $e->getMessage());

    header(
        "Location: Pedidos.php?mensaje="
        . urlencode("No se pudo eliminar el pedido. La operación fue revertida; revisa las relaciones pendientes.")
        . "&tipo=error"
    );
    exit;
}