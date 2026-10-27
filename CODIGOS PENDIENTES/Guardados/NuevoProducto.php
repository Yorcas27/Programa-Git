<?php

$titulo = "NUEVO PRODUCTO";
$css = "ProductosTerminados.css";

require_once("../../includes/conexion.php");
require_once("../../includes/header.php");

$mensaje = "";
$error = "";

// Cargar categorías activas del Catálogo
$sqlCategorias = "SELECT nombre
                  FROM categorias_producto
                  WHERE estado = 'Activa'
                  ORDER BY nombre ASC";

$resultadoCategorias = $conexion->query($sqlCategorias);

// Guardar producto terminado
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

        // Generar código PT-0001, PT-0002, etc.
        $sqlCodigo = "SELECT MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)) AS ultimo
                      FROM productos_terminados
                      WHERE codigo LIKE 'PT-%'";

        $resultadoCodigo = $conexion->query($sqlCodigo);
        $filaCodigo = $resultadoCodigo ? $resultadoCodigo->fetch_assoc() : null;

        $ultimo = (int)($filaCodigo["ultimo"] ?? 0);
        $codigo = "PT-" . str_pad($ultimo + 1, 4, "0", STR_PAD_LEFT);

        $sql = "INSERT INTO productos_terminados
                (codigo, producto, categoria, unidad, cantidad, precio, fecha, estado, descripcion, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "ssssddssss",
                $codigo,
                $producto,
                $categoria,
                $unidad,
                $cantidad,
                $precio,
                $fecha,
                $estado,
                $descripcion,
                $observaciones
            );

            if ($stmt->execute()) {

                header("Location: ProductosTerminados.php");
                exit;

            } else {

                $error = "No fue posible guardar el producto terminado.";
            }

            $stmt->close();

        } else {

            $error = "Error al preparar el registro.";
        }
    }
}

?>

<main class="contenido">

    <!-- BOTON VOLVER -->
    <section class="barra-superior">

        <a href="ProductosTerminados.php" class="btn btn-principal">
            ← Volver a Productos Terminados
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </section>


    <!-- ENCABEZADO -->
    <header class="encabezado">

        <h1>Nuevo Producto Terminado</h1>

        <p>
            Registre la información del producto elaborado y disponible.
        </p>

    </header>


    <!-- PANEL -->
    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>Información del Producto Terminado</h2>

                <p>
                    Complete la siguiente información.
                </p>

            </div>

        </div>


        <?php if ($error !== ""): ?>

            <div class="mensaje-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <!-- FORMULARIO -->
        <form class="formulario" method="POST">

            <div class="grupo-formulario">

                <div class="campo">

                    <label for="codigo">
                        Código
                    </label>

                    <input
                        type="text"
                        id="codigo"
                        value="Automático"
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
                        placeholder="Nombre del producto"
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

                                <option value="<?= htmlspecialchars($categoria['nombre']) ?>">
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

                        <option value="Unidad">
                            Unidad
                        </option>

                        <option value="Kilogramo">
                            Kilogramo
                        </option>

                        <option value="Caja">
                            Caja
                        </option>

                    </select>

                </div>


                <div class="campo">

                    <label for="cantidad">
                        Cantidad Inicial
                    </label>

                    <input
                        type="number"
                        id="cantidad"
                        name="cantidad"
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

                        <option value="Disponible">
                            Disponible
                        </option>

                        <option value="Agotado">
                            Agotado
                        </option>

                        <option value="Inactivo">
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
                        placeholder="Descripción del producto..."
                    ></textarea>

                </div>


                <div class="campo campo-completo">

                    <label for="observaciones">
                        Observaciones
                    </label>

                    <textarea
                        id="observaciones"
                        name="observaciones"
                        rows="4"
                        placeholder="Ingrese observaciones..."
                    ></textarea>

                </div>

            </div>


            <!-- BOTONES -->
            <div class="botones-formulario">

                <button
                    type="submit"
                    class="btn btn-guardar"
                >
                    Guardar
                </button>

                <button
                    type="reset"
                    class="btn btn-limpiar"
                >
                    Limpiar
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