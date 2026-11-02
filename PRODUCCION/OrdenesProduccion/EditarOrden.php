
<?php
$titulo = "EDITAR ORDEN";
$css = "OrdenesProduccion.css";

require_once("../../includes/conexion.php");

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$error = "";

$stmt = $conexion->prepare("
    SELECT *
    FROM ordenes_produccion
    WHERE id_orden = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$orden = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$orden) {
    header("Location: OrdenesProduccion.php");
    exit;
}

$editable = (
    $orden["destino"] === "PARA_STOCK" &&
    $orden["estado"] === "Pendiente"
);

$fecha = $orden["fecha"];
$id_producto = (int)$orden["id_producto"];
$cantidad = $orden["cantidad"];
$responsable = $orden["responsable"];
$fecha_entrega = $orden["fecha_entrega"] ?? "";
$observaciones = $orden["observaciones"] ?? "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && $editable) {

    $fecha = trim($_POST["fecha"] ?? "");
    $id_producto = (int)($_POST["id_producto"] ?? 0);
    $cantidad = filter_var(
        $_POST["cantidad"] ?? null,
        FILTER_VALIDATE_INT
    );
    $responsable = trim($_POST["responsable"] ?? "");
    $fecha_entrega = trim($_POST["fecha_entrega"] ?? "");
    $observaciones = trim($_POST["observaciones"] ?? "");

    if ($fecha === "") {
        $error = "Debe seleccionar la fecha de la orden.";

    } elseif ($id_producto <= 0) {
        $error = "Debe seleccionar un producto.";

    } elseif ($cantidad === false || $cantidad === null || $cantidad <= 0) {
        $error = "La cantidad debe ser un número entero mayor que cero.";

    } elseif ($responsable === "") {
        $error = "Debe ingresar el responsable.";

    } elseif (strlen($responsable) > 100) {
        $error = "El responsable no puede superar los 100 caracteres.";

    } else {

        $fechaValida = DateTime::createFromFormat("Y-m-d", $fecha);

        if (!$fechaValida || $fechaValida->format("Y-m-d") !== $fecha) {
            $error = "La fecha de la orden no es válida.";
        } elseif ($fecha_entrega !== "") {
            $fechaEntregaValida = DateTime::createFromFormat("Y-m-d", $fecha_entrega);

            if (!$fechaEntregaValida || $fechaEntregaValida->format("Y-m-d") !== $fecha_entrega) {
                $error = "La fecha de entrega no es válida.";
            } elseif ($fecha_entrega < $fecha) {
                $error = "La fecha de entrega no puede ser anterior a la fecha de la orden.";
            }
        }

        if ($error === "") {

            $conexion->begin_transaction();

            try {
                $bloqueo = $conexion->prepare("
                    SELECT destino, estado
                    FROM ordenes_produccion
                    WHERE id_orden = ?
                    FOR UPDATE
                ");
                $bloqueo->bind_param("i", $id);
                $bloqueo->execute();
                $actual = $bloqueo->get_result()->fetch_assoc();
                $bloqueo->close();

                if (
                    !$actual ||
                    $actual["destino"] !== "PARA_STOCK" ||
                    $actual["estado"] !== "Pendiente"
                ) {
                    throw new Exception(
                        "La orden ya no se puede editar porque su destino o estado cambió."
                    );
                }

                $stmtProducto = $conexion->prepare("
                    SELECT id_producto
                    FROM productos
                    WHERE id_producto = ?
                    AND (estado = 'activo' OR id_producto = ?)
                ");
                $stmtProducto->bind_param(
                    "ii",
                    $id_producto,
                    $orden["id_producto"]
                );
                $stmtProducto->execute();
                $productoValido = $stmtProducto->get_result()->fetch_assoc();
                $stmtProducto->close();

                if (!$productoValido) {
                    throw new Exception(
                        "El producto seleccionado no existe o está inactivo."
                    );
                }

                $fechaEntregaDB = $fecha_entrega !== ""
                    ? $fecha_entrega
                    : null;

                $actualizar = $conexion->prepare("
                    UPDATE ordenes_produccion
                    SET fecha = ?,
                        id_producto = ?,
                        cantidad = ?,
                        responsable = ?,
                        fecha_entrega = ?,
                        observaciones = ?
                    WHERE id_orden = ?
                    AND destino = 'PARA_STOCK'
                    AND estado = 'Pendiente'
                ");

                $actualizar->bind_param(
                    "siisssi",
                    $fecha,
                    $id_producto,
                    $cantidad,
                    $responsable,
                    $fechaEntregaDB,
                    $observaciones,
                    $id
                );

                if (!$actualizar->execute()) {
                    throw new Exception(
                        "No fue posible actualizar la orden: " . $actualizar->error
                    );
                }

                if ($actualizar->affected_rows === 0) {
                    $actualizar->close();
                    throw new Exception(
                        "No se realizaron cambios en la orden."
                    );
                }

                $actualizar->close();
                $conexion->commit();

                header("Location: VerOrden.php?id=" . $id);
                exit;

            } catch (Throwable $e) {
                $conexion->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

$productos = $conexion->query("
    SELECT id_producto, nombre, presentacion
    FROM productos
    WHERE estado = 'activo'
       OR id_producto = " . (int)$orden["id_producto"] . "
    ORDER BY nombre ASC, presentacion ASC
");

require_once("../../includes/header.php");
?>

<main class="contenido">

    <section class="barra-superior">

        <a href="OrdenesProduccion.php" class="btn btn-verde">
            ← Volver a Órdenes de Producción
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </section>

    <header class="encabezado">
        <h1>Editar Orden de Producción</h1>
        <p>Modifique la información de la orden de producción.</p>
    </header>

    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Información de la Orden</h2>
                <p>Orden <?= htmlspecialchars($orden["codigo"]) ?></p>
            </div>
        </div>

        <?php if (!$editable): ?>

            <div class="mensaje-error">
                Esta orden no se puede editar desde este módulo.
                <br><br>
                Las órdenes por pedido se modifican desde el módulo Pedidos.
                Las órdenes en producción, finalizadas, entregadas o anuladas
                conservan sus datos de fabricación.
            </div>

            <div class="botones-formulario">
                <a href="VerOrden.php?id=<?= $id ?>" class="btn btn-cancelar">
                    Volver al Detalle
                </a>
            </div>

        <?php else: ?>

            <?php if ($error !== ""): ?>
                <div class="mensaje-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form class="formulario" method="POST"
                action="EditarOrden.php?id=<?= $id ?>">

                <div class="grupo-formulario">

                    <div class="campo">
                        <label for="codigo">Código</label>
                        <input type="text" id="codigo"
                            value="<?= htmlspecialchars($orden["codigo"]) ?>"
                            readonly>
                    </div>

                    <div class="campo">
                        <label for="destino">Destino</label>
                        <input type="text" id="destino"
                            value="Para inventario" readonly>
                    </div>

                    <div class="campo">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha"
                            value="<?= htmlspecialchars($fecha) ?>" required>
                    </div>

                    <div class="campo">
                        <label for="producto">Producto</label>
                        <select id="producto" name="id_producto" required>
                            <option value="">Seleccione...</option>

                            <?php while ($producto = $productos->fetch_assoc()): ?>
                                <option
                                    value="<?= (int)$producto["id_producto"] ?>"
                                    <?= (int)$producto["id_producto"] === (int)$id_producto ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars(
                                        $producto["nombre"] . " — " . $producto["presentacion"]
                                    ) ?>
                                </option>
                            <?php endwhile; ?>

                        </select>
                    </div>

                    <div class="campo">
                        <label for="cantidad">Cantidad</label>
                        <input type="number" id="cantidad" name="cantidad"
                            value="<?= htmlspecialchars((string)$cantidad) ?>"
                            min="1" step="1" required>
                    </div>

                    <div class="campo">
                        <label for="responsable">Responsable</label>
                        <input type="text" id="responsable" name="responsable"
                            maxlength="100"
                            value="<?= htmlspecialchars($responsable) ?>"
                            required>
                    </div>

                    <div class="campo">
                        <label for="fecha_entrega">Fecha de Entrega</label>
                        <input type="date" id="fecha_entrega"
                            name="fecha_entrega"
                            min="<?= htmlspecialchars($fecha) ?>"
                            value="<?= htmlspecialchars($fecha_entrega) ?>">
                    </div>

                    <div class="campo campo-completo">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" name="observaciones"
                            rows="5"><?= htmlspecialchars($observaciones) ?></textarea>
                    </div>

                </div>

                <div class="botones-formulario">

                    <button type="submit" class="btn btn-guardar">
                        Actualizar
                    </button>

                    <a href="OrdenesProduccion.php" class="btn btn-cancelar">
                        Cancelar
                    </a>

                </div>

            </form>

        <?php endif; ?>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>
