<?php

$titulo = "Nuevo Insumo";
$css = "Insumos.css";

require_once "../../includes/conexion.php";

$mensaje = "";
$error = "";

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $codigo = trim($_POST['codigo'] ?? '');
    $producto = trim($_POST['producto'] ?? '');
    $id_categoria = $_POST['id_categoria'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $unidad = $_POST['unidad'] ?? '';
    $estado = $_POST['estado'] ?? '';

    if(
        $codigo === '' ||
        $producto === '' ||
        $id_categoria === '' ||
        $stock === '' ||
        $unidad === '' ||
        $estado === ''
    ){

        $error = "Complete todos los campos.";

    }else{

        $stmt = $conexion->prepare(
            "INSERT INTO insumos
            (codigo, producto, id_categoria, stock, unidad, estado)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssidss",
            $codigo,
            $producto,
            $id_categoria,
            $stock,
            $unidad,
            $estado
        );

        if($stmt->execute()){

            header("Location: Insumos.php");
            exit;

        }else{

            if($conexion->errno == 1062){

                $error = "El código del insumo ya existe.";

            }else{

                $error = "No fue posible registrar el insumo.";

            }

        }

        $stmt->close();
    }
}


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
            Nuevo Insumo
        </h1>

        <p>
            Registro de nuevas materias primas utilizadas en YCL_Solutions.
        </p>

    </header>


    <!-- FORMULARIO -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Registro de Insumo
                </h2>

                <p>
                    Complete la información requerida para registrar un nuevo insumo.
                </p>

            </div>

        </div>


        <?php if($error != ''): ?>

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
                        name="codigo"
                        placeholder="Ej: INS004"
                        value="<?= htmlspecialchars($_POST['codigo'] ?? '') ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label>
                        Nombre del Producto
                    </label>

                    <input
                        type="text"
                        name="producto"
                        placeholder="Ej: Harina de Trigo"
                        value="<?= htmlspecialchars($_POST['producto'] ?? '') ?>"
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
                                    <?= ($_POST['id_categoria'] ?? '') == $categoria['id_categoria'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                </option>

                            <?php endwhile; ?>

                        <?php endif; ?>

                    </select>

                </div>

            </div>


            <!-- DATOS DE INVENTARIO -->

            <div class="grupo-formulario">

                <div class="campo">

                    <label>
                        Cantidad Inicial
                    </label>

                    <input
                        type="number"
                        name="stock"
                        step="0.01"
                        min="0"
                        placeholder="Ej: 50"
                        value="<?= htmlspecialchars($_POST['stock'] ?? '') ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label>
                        Unidad de Medida
                    </label>

                    <select name="unidad" required>

                        <option value="">
                            Seleccione unidad
                        </option>

                        <option value="Kg" <?= ($_POST['unidad'] ?? '') == 'Kg' ? 'selected' : '' ?>>
                            Kg
                        </option>

                        <option value="Unidad" <?= ($_POST['unidad'] ?? '') == 'Unidad' ? 'selected' : '' ?>>
                            Unidad
                        </option>

                        <option value="Litro" <?= ($_POST['unidad'] ?? '') == 'Litro' ? 'selected' : '' ?>>
                            Litro
                        </option>

                    </select>

                </div>


                <div class="campo">

                    <label>
                        Estado
                    </label>

                    <select name="estado" required>

                        <option value="Disponible" <?= ($_POST['estado'] ?? 'Disponible') == 'Disponible' ? 'selected' : '' ?>>
                            Disponible
                        </option>

                        <option value="Bajo Stock" <?= ($_POST['estado'] ?? '') == 'Bajo Stock' ? 'selected' : '' ?>>
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
                    Guardar
                </button>


                <button
                    type="reset"
                    class="btn-limpiar">
                    Limpiar
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