
<?php
$titulo = "NUEVA ORDEN";
$css = "OrdenesProduccion.css";

require_once("../../includes/conexion.php");

$error = "";
$fecha = date("Y-m-d");
$fecha_entrega = "";
$id_producto = "";
$cantidad = "";
$responsable = "";
$observaciones = "";

/* GENERAR CÓDIGO */

function generarCodigoOrden($conexion) {
    $resultado = $conexion->query(
        "SELECT MAX(id_orden) AS ultimo FROM ordenes_produccion"
    );

    if (!$resultado) {
        throw new Exception("No fue posible generar el código de la orden.");
    }

    $fila = $resultado->fetch_assoc();
    $ultimo = (int)($fila["ultimo"] ?? 0);

    return "OP-" . str_pad($ultimo + 1, 4, "0", STR_PAD_LEFT);
}

try {
    $codigo = generarCodigoOrden($conexion);
} catch (Throwable $e) {
    $codigo = "Pendiente";
    $error = $e->getMessage();
}

/* GUARDAR ORDEN */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_producto = (int)($_POST["id_producto"] ?? 0);
    $cantidad = (int)($_POST["cantidad"] ?? 0);
    $responsable = trim($_POST["responsable"] ?? "");
    $fecha_entrega = trim($_POST["fecha_entrega"] ?? "");
    $observaciones = trim($_POST["observaciones"] ?? "");

    if ($id_producto <= 0) {
        $error = "Debe seleccionar un producto.";

    } elseif ($cantidad <= 0) {
        $error = "La cantidad debe ser mayor que cero.";

    } elseif ($responsable === "") {
        $error = "Debe ingresar el responsable.";

    } else {

        $transaccion = false;

        try {

            $conexion->begin_transaction();
            $transaccion = true;

            /* VALIDAR PRODUCTO ACTIVO */

            $stmtProducto = $conexion->prepare(
                "SELECT id_producto
                 FROM productos
                 WHERE id_producto = ?
                 AND estado = 'activo'"
            );

            if (!$stmtProducto) {
                throw new Exception("No fue posible validar el producto.");
            }

            $stmtProducto->bind_param("i", $id_producto);
            $stmtProducto->execute();

            $productoValido = $stmtProducto->get_result()->fetch_assoc();
            $stmtProducto->close();

            if (!$productoValido) {
                throw new Exception(
                    "El producto seleccionado no existe o está inactivo."
                );
            }

            /* GENERAR CÓDIGO */

            $codigo = generarCodigoOrden($conexion);

            /* REGISTRAR ORDEN PARA INVENTARIO */

            $stmt = $conexion->prepare(
                "INSERT INTO ordenes_produccion
                (
                    id_pedido,
                    destino,
                    codigo,
                    fecha,
                    id_cliente,
                    id_producto,
                    cantidad,
                    responsable,
                    fecha_entrega,
                    estado,
                    observaciones
                )
                VALUES
                (
                    NULL,
                    'PARA_STOCK',
                    ?,
                    ?,
                    NULL,
                    ?,
                    ?,
                    ?,
                    NULLIF(?, ''),
                    'Pendiente',
                    ?
                )"
            );

            if (!$stmt) {
                throw new Exception(
                    "No fue posible preparar la orden: " . $conexion->error
                );
            }

            $stmt->bind_param(
                "ssiisss",
                $codigo,
                $fecha,
                $id_producto,
                $cantidad,
                $responsable,
                $fecha_entrega,
                $observaciones
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    "No fue posible guardar la orden: " . $stmt->error
                );
            }

            $stmt->close();
            $conexion->commit();
            $transaccion = false;

            header(
                "Location: OrdenesProduccion.php?mensaje="
                . urlencode("Orden de producción para inventario creada correctamente.")
            );
            exit;

        } catch (Throwable $e) {

            if ($transaccion) {
                $conexion->rollback();
            }

            $error = "No fue posible guardar la orden: " . $e->getMessage();

            try {
                $codigo = generarCodigoOrden($conexion);
            } catch (Throwable $ex) {
                $codigo = "No disponible";
            }
        }
    }
}

/* CONSULTAR PRODUCTOS ACTIVOS */

$productos = $conexion->query(
    "SELECT id_producto, nombre, presentacion
     FROM productos
     WHERE estado = 'activo'
     ORDER BY nombre, presentacion"
);

if (!$productos && $error === "") {
    $error = "No fue posible consultar los productos disponibles.";
}

include("../../includes/header.php");
?>

<main class="contenido">

    <section class="barra-superior">

        <a href="OrdenesProduccion.php" class="btn btn-principal">
            ← Volver a Órdenes de Producción
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </section>

    <header class="encabezado">

        <h1>Nueva Orden de Producción</h1>

        <p>
            Registra una orden de producción destinada al inventario de productos terminados.
        </p>

    </header>

    <?php if ($error !== ""): ?>

        <div class="mensaje-error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>

    <?php endif; ?>

    <section class="panel">

        <form method="POST" action="NuevaOrden.php" id="formOrden">

            <div class="grupo-formulario">

                <div class="campo">

                    <label for="codigo">Código</label>

                    <input
                        type="text"
                        id="codigo"
                        value="<?= htmlspecialchars($codigo, ENT_QUOTES, "UTF-8") ?>"
                        readonly
                    >

                </div>

                <div class="campo">

                    <label for="fecha">Fecha de Creación</label>

                    <input
                        type="date"
                        id="fecha"
                        value="<?= htmlspecialchars($fecha, ENT_QUOTES, "UTF-8") ?>"
                        readonly
                    >

                </div>

                <div class="campo">

                    <label for="id_producto">Producto</label>

                    <select id="id_producto" name="id_producto" required>

                        <option value="">Seleccione un producto</option>

                        <?php if ($productos): ?>

                            <?php while ($producto = $productos->fetch_assoc()): ?>

                                <option
                                    value="<?= (int)$producto["id_producto"] ?>"
                                    <?= (string)$id_producto === (string)$producto["id_producto"] ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars(
                                        $producto["nombre"] . " — " . $producto["presentacion"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>
                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>

                </div>

                <div class="campo">

                    <label for="cantidad">Cantidad</label>

                    <input
                        type="number"
                        id="cantidad"
                        name="cantidad"
                        min="1"
                        step="1"
                        value="<?= htmlspecialchars((string)$cantidad, ENT_QUOTES, "UTF-8") ?>"
                        placeholder="Cantidad a producir"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="responsable">Responsable</label>

                    <input
                        type="text"
                        id="responsable"
                        name="responsable"
                        maxlength="100"
                        value="<?= htmlspecialchars($responsable, ENT_QUOTES, "UTF-8") ?>"
                        placeholder="Nombre del responsable"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="fecha_entrega">Fecha Estimada de Finalización</label>

                    <input
                        type="date"
                        id="fecha_entrega"
                        name="fecha_entrega"
                        min="<?= date("Y-m-d") ?>"
                        value="<?= htmlspecialchars($fecha_entrega, ENT_QUOTES, "UTF-8") ?>"
                    >

                </div>

                <div class="campo campo-completo">

                    <label for="observaciones">Observaciones</label>

                    <textarea
                        id="observaciones"
                        name="observaciones"
                        rows="4"
                        placeholder="Ingrese observaciones de la orden..."
                    ><?= htmlspecialchars($observaciones, ENT_QUOTES, "UTF-8") ?></textarea>

                </div>

            </div>

            <div class="botones-formulario">

                <button
                    type="submit"
                    class="btn btn-guardar"
                    <?= !$productos ? "disabled" : "" ?>
                >
                    Guardar Orden
                </button>

                <a href="OrdenesProduccion.php" class="btn btn-cancelar">
                    Cancelar
                </a>

            </div>

        </form>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>
