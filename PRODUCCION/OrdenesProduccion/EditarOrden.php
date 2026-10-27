<?php
$titulo = "EDITAR ORDEN";
$css = "OrdenesProduccion.css";

require_once("../../includes/conexion.php");
require_once("../../includes/header.php");

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$error = "";

/* CONSULTAR ORDEN */

$stmt = $conexion->prepare("
    SELECT *
    FROM ordenes_produccion
    WHERE id_orden = ?
");

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$orden = $resultado->fetch_assoc();
$stmt->close();

if (!$orden) {
    header("Location: OrdenesProduccion.php");
    exit;
}

/* VALORES DEL FORMULARIO */

$fecha = $orden["fecha"];
$id_cliente = (int)$orden["id_cliente"];
$id_producto = (int)$orden["id_producto"];
$cantidad = $orden["cantidad"];
$responsable = $orden["responsable"];
$fecha_entrega = $orden["fecha_entrega"] ?? "";
$estado = $orden["estado"];
$observaciones = $orden["observaciones"] ?? "";

/* ACTUALIZAR ORDEN */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fecha = trim($_POST["fecha"] ?? "");
    $id_cliente = (int)($_POST["id_cliente"] ?? 0);
    $id_producto = (int)($_POST["id_producto"] ?? 0);
    $cantidad = filter_var(
        $_POST["cantidad"] ?? null,
        FILTER_VALIDATE_INT
    );
    $responsable = trim($_POST["responsable"] ?? "");
    $fecha_entrega = trim($_POST["fecha_entrega"] ?? "");
    $estado = $_POST["estado"] ?? "";
    $observaciones = trim($_POST["observaciones"] ?? "");

    if ($fecha === "") {

        $error = "Debe seleccionar la fecha de la orden.";

    } elseif ($id_cliente <= 0) {

        $error = "Debe seleccionar un cliente.";

    } elseif ($id_producto <= 0) {

        $error = "Debe seleccionar un producto.";

    } elseif ($cantidad === false || $cantidad === null || $cantidad <= 0) {

        $error = "La cantidad debe ser un número entero mayor que cero.";

    } elseif ($responsable === "") {

        $error = "Debe ingresar el responsable.";

    } elseif (
        !in_array(
            $estado,
            ["Pendiente", "En Producción", "Finalizada"],
            true
        )
    ) {

        $error = "El estado seleccionado no es válido.";

    } else {

        /* VALIDAR CLIENTE */

        $stmtCliente = $conexion->prepare("
            SELECT id_cliente
            FROM clientes
            WHERE id_cliente = ?
            AND (estado = 'Activo' OR id_cliente = ?)
        ");

        $stmtCliente->bind_param("ii", $id_cliente, $orden["id_cliente"]);
        $stmtCliente->execute();

        $clienteValido = $stmtCliente->get_result()->fetch_assoc();
        $stmtCliente->close();

        if (!$clienteValido) {

            $error = "El cliente seleccionado no existe o está inactivo.";

        } else {

            /* VALIDAR PRODUCTO */

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

                $error = "El producto seleccionado no existe o está inactivo.";

            } else {

                /* ACTUALIZAR */

                $fecha_entrega_db = $fecha_entrega !== ""
                    ? $fecha_entrega
                    : null;

                $actualizar = $conexion->prepare("
                    UPDATE ordenes_produccion
                    SET fecha = ?,
                        id_cliente = ?,
                        id_producto = ?,
                        cantidad = ?,
                        responsable = ?,
                        fecha_entrega = ?,
                        estado = ?,
                        observaciones = ?
                    WHERE id_orden = ?
                ");

                $actualizar->bind_param(
                    "siiissssi",
                    $fecha,
                    $id_cliente,
                    $id_producto,
                    $cantidad,
                    $responsable,
                    $fecha_entrega_db,
                    $estado,
                    $observaciones,
                    $id
                );

                if ($actualizar->execute()) {

                    $actualizar->close();

                    header("Location: VerOrden.php?id=" . $id);
                    exit;

                } else {

                    $error = "No fue posible actualizar la orden: "
                        . $actualizar->error;

                    $actualizar->close();
                }
            }
        }
    }
}

/* CLIENTES ACTIVOS Y CLIENTE ACTUAL */

$clientes = $conexion->query("
    SELECT id_cliente, nombre, documento
    FROM clientes
    WHERE estado = 'Activo'
       OR id_cliente = " . (int)$orden["id_cliente"] . "
    ORDER BY nombre ASC
");

/* PRODUCTOS ACTIVOS Y PRODUCTO ACTUAL */

$productos = $conexion->query("
    SELECT id_producto, nombre, presentacion
    FROM productos
    WHERE estado = 'activo'
       OR id_producto = " . (int)$orden["id_producto"] . "
    ORDER BY nombre ASC, presentacion ASC
");
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

        <p>
            Modifique la información registrada de la orden de producción.
        </p>

    </header>

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>Información de la Orden</h2>

                <p>
                    Actualice los datos necesarios.
                </p>

            </div>

        </div>

        <?php if ($error !== ""): ?>

            <div class="mensaje-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form class="formulario" method="POST" action="EditarOrden.php?id=<?= $id ?>">

            <div class="grupo-formulario">

                <div class="campo">

                    <label for="codigo">Código</label>

                    <input
                        type="text"
                        id="codigo"
                        value="<?= htmlspecialchars($orden["codigo"]) ?>"
                        readonly
                    >

                </div>

                <div class="campo">

                    <label for="fecha">Fecha</label>

                    <input
                        type="date"
                        id="fecha"
                        name="fecha"
                        value="<?= htmlspecialchars($fecha) ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="cliente">Cliente</label>

                    <select id="cliente" name="id_cliente" required>

                        <option value="">Seleccione...</option>

                        <?php while ($cliente = $clientes->fetch_assoc()): ?>

                            <option
                                value="<?= $cliente["id_cliente"] ?>"
                                <?= (int)$cliente["id_cliente"] === (int)$id_cliente ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars(
                                    $cliente["nombre"]
                                    . (
                                        !empty($cliente["documento"])
                                            ? " - " . $cliente["documento"]
                                            : ""
                                    )
                                ) ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>

                <div class="campo">

                    <label for="producto">Producto</label>

                    <select id="producto" name="id_producto" required>

                        <option value="">Seleccione...</option>

                        <?php while ($producto = $productos->fetch_assoc()): ?>

                            <option
                                value="<?= $producto["id_producto"] ?>"
                                <?= (int)$producto["id_producto"] === (int)$id_producto ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars(
                                    $producto["nombre"]
                                    . " — "
                                    . $producto["presentacion"]
                                ) ?>
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>

                <div class="campo">

                    <label for="cantidad">Cantidad</label>

                    <input
                        type="number"
                        id="cantidad"
                        name="cantidad"
                        value="<?= htmlspecialchars((string)$cantidad) ?>"
                        min="1"
                        step="1"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="responsable">Responsable</label>

                    <input
                        type="text"
                        id="responsable"
                        name="responsable"
                        value="<?= htmlspecialchars($responsable) ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="entrega">Fecha de Entrega</label>

                    <input
                        type="date"
                        id="entrega"
                        name="fecha_entrega"
                        value="<?= htmlspecialchars($fecha_entrega) ?>"
                    >

                </div>

                <div class="campo">

                    <label for="estado">Estado</label>

                    <select id="estado" name="estado" required>

                        <option
                            value="Pendiente"
                            <?= $estado === "Pendiente" ? "selected" : "" ?>
                        >
                            Pendiente
                        </option>

                        <option
                            value="En Producción"
                            <?= $estado === "En Producción" ? "selected" : "" ?>
                        >
                            En Producción
                        </option>

                        <option
                            value="Finalizada"
                            <?= $estado === "Finalizada" ? "selected" : "" ?>
                        >
                            Finalizada
                        </option>

                    </select>

                </div>

                <div class="campo campo-completo">

                    <label for="observaciones">Observaciones</label>

                    <textarea
                        id="observaciones"
                        name="observaciones"
                        rows="5"
                    ><?= htmlspecialchars($observaciones) ?></textarea>

                </div>

            </div>

            <div class="botones-formulario">

                <button
                    type="submit"
                    class="btn btn-guardar"
                >
                    Actualizar
                </button>

                <a
                    href="OrdenesProduccion.php"
                    class="btn btn-cancelar"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>