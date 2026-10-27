<?php

$titulo = "EDITAR PRODUCTO";
$css = "ProductosTerminados.css";

require_once("../../includes/conexion.php");
require_once("../../includes/header.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: ProductosTerminados.php");
    exit;
}

$error = "";

// Actualizar producto
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $producto = trim($_POST["producto"] ?? "");
    $categoria = trim($_POST["categoria"] ?? "");
    $unidad = trim($_POST["unidad"] ?? "");
    $cantidad = (float)($_POST["cantidad"] ?? 0);
    $precio = (float)($_POST["precio"] ?? 0);
    $fecha = $_POST["fecha"] ?? "";
    $estado = $_POST["estado"] ?? "Disponible";
    $descripcion = trim($_POST["descripcion"] ?? "");
    $observaciones = trim($_POST["observaciones"] ?? "");

    if ($producto === "" || $categoria === "" || $unidad === "" || $fecha === "") {

        $error = "Complete los campos obligatorios.";

    } else {

        $sqlUpdate = "UPDATE productos_terminados
                      SET producto = ?,
                          categoria = ?,
                          unidad = ?,
                          cantidad = ?,
                          precio = ?,
                          fecha = ?,
                          estado = ?,
                          descripcion = ?,
                          observaciones = ?
                      WHERE id_producto_terminado = ?";

        $stmtUpdate = $conexion->prepare($sqlUpdate);

        if ($stmtUpdate) {

            $stmtUpdate->bind_param(
                "sssddssssi",
                $producto,
                $categoria,
                $unidad,
                $cantidad,
                $precio,
                $fecha,
                $estado,
                $descripcion,
                $observaciones,
                $id
            );

            if ($stmtUpdate->execute()) {

                header("Location: ProductosTerminados.php");
                exit;

            } else {

                $error = "No fue posible actualizar el producto terminado.";
            }

            $stmtUpdate->close();

        } else {

            $error = "Error al preparar la actualización.";
        }
    }
}


// Consultar producto
$sql = "SELECT
            id_producto_terminado,
            codigo,
            producto,
            categoria,
            unidad,
            cantidad,
            precio,
            fecha,
            estado,
            descripcion,
            observaciones
        FROM productos_terminados
        WHERE id_producto_terminado = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();
$productoDB = $resultado->fetch_assoc();

$stmt->close();

if (!$productoDB) {
    header("Location: ProductosTerminados.php");
    exit;
}


// Cargar categorías activas del Catálogo
$sqlCategorias = "SELECT nombre
                  FROM categorias_producto
                  WHERE estado = 'Activa'
                  ORDER BY nombre ASC";

$resultadoCategorias = $conexion->query($sqlCategorias);

?>

<main class="contenido">

    <section class="barra-superior">

        <a href="ProductosTerminados.php" class="btn btn-principal">
            ← Volver a Productos Terminados
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </section>


    <header class="encabezado">

        <h1>Editar Producto Terminado</h1>

        <p>
            Modifique la información registrada del producto terminado.
        </p>

    </header>


    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Información del Producto Terminado
                </h2>

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


        <form class="formulario" method="POST">

            <div class="grupo-formulario">

                <div class="campo">

                    <label for="codigo">
                        Código
                    </label>

                    <input
                        type="text"
                        id="codigo"
                        value="<?= htmlspecialchars($productoDB['codigo']) ?>"
                        readonly
                    >

                </div>


                <div class="campo">

                    <label for="producto">
                        Nombre del Producto
                    </label>

                    <input
                        type="text"
                        id="producto"
                        name="producto"
                        value="<?= htmlspecialchars($productoDB['producto']) ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="categoria">
                        Categoría
                    </label>

                    <select
                        id="categoria"
                        name="categoria"
                        required
                    >

                        <option value="">
                            Seleccione...
                        </option>

                        <?php if ($resultadoCategorias && $resultadoCategorias->num_rows > 0): ?>

                            <?php while ($categoria = $resultadoCategorias->fetch_assoc()): ?>

                                <option
                                    value="<?= htmlspecialchars($categoria['nombre']) ?>"
                                    <?= $productoDB['categoria'] === $categoria['nombre'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>

                </div>


                <div class="campo">

                    <label for="unidad">
                        Unidad
                    </label>

                    <select
                        id="unidad"
                        name="unidad"
                        required
                    >

                        <option value="">
                            Seleccione...
                        </option>

                        <option
                            value="Unidad"
                            <?= $productoDB['unidad'] === 'Unidad' ? 'selected' : '' ?>
                        >
                            Unidad
                        </option>

                        <option
                            value="Kilogramo"
                            <?= $productoDB['unidad'] === 'Kilogramo' ? 'selected' : '' ?>
                        >
                            Kilogramo
                        </option>

                        <option
                            value="Caja"
                            <?= $productoDB['unidad'] === 'Caja' ? 'selected' : '' ?>
                            >
                            Caja
                        </option>

                    </select>

                </div>


                <div class="campo">

                    <label for="cantidad">
                        Cantidad Disponible
                    </label>

                    <input
                        type="number"
                        id="cantidad"
                        name="cantidad"
                        value="<?= htmlspecialchars($productoDB['cantidad']) ?>"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="precio">
                        Precio Venta
                    </label>

                    <input
                        type="number"
                        id="precio"
                        name="precio"
                        value="<?= htmlspecialchars($productoDB['precio']) ?>"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="fecha">
                        Fecha Elaboración
                    </label>

                    <input
                        type="date"
                        id="fecha"
                        name="fecha"
                        value="<?= htmlspecialchars($productoDB['fecha']) ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="estado">
                        Estado
                    </label>

                    <select
                        id="estado"
                        name="estado"
                    >

                        <option
                            value="Disponible"
                            <?= $productoDB['estado'] === 'Disponible' ? 'selected' : '' ?>
                        >
                            Disponible
                        </option>

                        <option
                            value="Agotado"
                            <?= $productoDB['estado'] === 'Agotado' ? 'selected' : '' ?>
                        >
                            Agotado
                        </option>

                        <option
                            value="Inactivo"
                            <?= $productoDB['estado'] === 'Inactivo' ? 'selected' : '' ?>
                        >
                            Inactivo
                        </option>

                    </select>

                </div>


                <div class="campo campo-completo">

                    <label for="descripcion">
                        Descripción
                    </label>

                    <textarea
                        id="descripcion"
                        name="descripcion"
                        rows="4"
                    ><?= htmlspecialchars($productoDB['descripcion'] ?? '') ?></textarea>

                </div>


                <div class="campo campo-completo">

                    <label for="observaciones">
                        Observaciones
                    </label>

                    <textarea
                        id="observaciones"
                        name="observaciones"
                        rows="4"
                    ><?= htmlspecialchars($productoDB['observaciones'] ?? '') ?></textarea>

                </div>

            </div>


            <!-- BOTONES -->
            <div class="botones-formulario">

                <button
                    type="submit"
                    class="btn btn-guardar"
                >
                    Actualizar
                </button>

                <a
                    href="ProductosTerminados.php"
                    class="btn btn-cancelar"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </section>

</main>


<?php include("../../includes/footer.php"); ?>