<?php

$titulo = "Editar Insumo";
$css = "Insumos.css";

require_once "../../includes/conexion.php";

$id = $_GET['id'] ?? '';

if(!is_numeric($id)){

    header("Location: Insumos.php");
    exit;

}


/* GUARDAR CAMBIOS */

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $producto = trim($_POST['producto'] ?? '');
    $id_categoria = $_POST['id_categoria'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $unidad = $_POST['unidad'] ?? '';
    $estado = $_POST['estado'] ?? '';

    if(
        $producto === '' ||
        $id_categoria === '' ||
        $stock === '' ||
        $unidad === '' ||
        $estado === ''
    ){

        $error = "Complete todos los campos.";

    }else{

        $stmt = $conexion->prepare(
            "UPDATE insumos
             SET producto = ?,
                 id_categoria = ?,
                 stock = ?,
                 unidad = ?,
                 estado = ?
             WHERE id_insumo = ?"
        );

        $stmt->bind_param(
            "sidssi",
            $producto,
            $id_categoria,
            $stock,
            $unidad,
            $estado,
            $id
        );

        if($stmt->execute()){

            header("Location: VerInsumo.php?id=".$id);
            exit;

        }else{

            $error = "No fue posible actualizar el insumo.";

        }

        $stmt->close();
    }
}


/* DATOS DEL INSUMO */

$stmt = $conexion->prepare(
    "SELECT
        id_insumo,
        codigo,
        producto,
        id_categoria,
        stock,
        unidad,
        estado
     FROM insumos
     WHERE id_insumo = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();

if($resultado->num_rows === 0){

    header("Location: Insumos.php");
    exit;

}

$insumo = $resultado->fetch_assoc();

$stmt->close();


/* CATEGORÍAS */

$categorias = $conexion->query(
    "SELECT id_categoria, nombre
     FROM categorias
     WHERE estado = 'Activa'
     ORDER BY nombre ASC"
);

require_once "../../includes/header.php";

?>

<main class="contenedor">

    <!-- BOTON VOLVER -->

    <div class="volver">

        <a href="Insumos.php">
            ← Volver a Insumos
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </div>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>
            Editar Insumo
        </h1>

        <p>
            Actualización de información del insumo registrado en YCL_Solutions.
        </p>

    </header>


    <!-- FORMULARIO -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Actualizar Insumo
                </h2>

                <p>
                    Modifique la información del registro seleccionado.
                </p>

            </div>

        </div>


        <?php if(isset($error)): ?>

            <p class="mensaje-error">
                <?= htmlspecialchars($error) ?>
            </p>

        <?php endif; ?>


        <form class="formulario" method="POST">

            <!-- DATOS GENERALES -->

            <div class="grupo-formulario">

                <div class="campo">

                    <label>
                        Código del Insumo
                    </label>

                    <input
                        type="text"
                        value="<?= htmlspecialchars($insumo['codigo']) ?>"
                        readonly
                    >

                </div>


                <div class="campo">

                    <label>
                        Nombre del Producto
                    </label>

                    <input
                        type="text"
                        name="producto"
                        value="<?= htmlspecialchars($insumo['producto']) ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label>
                        Categoría
                    </label>

                    <select name="id_categoria" required>

                        <option value="">
                            Seleccione categoría
                        </option>

                        <?php if($categorias): ?>

                            <?php while($categoria = $categorias->fetch_assoc()): ?>

                                <option
                                    value="<?= $categoria['id_categoria'] ?>"
                                    <?= $insumo['id_categoria'] == $categoria['id_categoria'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>

                </div>

            </div>


            <!-- DATOS INVENTARIO -->

            <div class="grupo-formulario">

                <div class="campo">

                    <label>
                        Cantidad Disponible
                    </label>

                    <input
                        type="number"
                        name="stock"
                        step="0.01"
                        min="0"
                        value="<?= htmlspecialchars($insumo['stock']) ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label>
                        Unidad de Medida
                    </label>

                    <select name="unidad" required>

                        <option value="Kg" <?= $insumo['unidad'] == 'Kg' ? 'selected' : '' ?>>
                            Kg
                        </option>

                        <option value="Unidad" <?= $insumo['unidad'] == 'Unidad' ? 'selected' : '' ?>>
                            Unidad
                        </option>

                        <option value="Litro" <?= $insumo['unidad'] == 'Litro' ? 'selected' : '' ?>>
                            Litro
                        </option>

                    </select>

                </div>


                <div class="campo">

                    <label>
                        Estado
                    </label>

                    <select name="estado" required>

                        <option value="Disponible" <?= $insumo['estado'] == 'Disponible' ? 'selected' : '' ?>>
                            Disponible
                        </option>

                        <option value="Bajo Stock" <?= $insumo['estado'] == 'Bajo Stock' ? 'selected' : '' ?>>
                            Bajo Stock
                        </option>

                    </select>

                </div>

            </div>


            <!-- BOTONES -->

            <div class="botones-formulario">

                <button
                    type="submit"
                    class="btn-guardar">
                    Guardar Cambios
                </button>


                <a
                    href="Insumos.php"
                    class="btn-cancelar">
                    Cancelar
                </a>

            </div>

        </form>

    </section>

</main>


<?php

require_once "../../includes/footer.php";

?>