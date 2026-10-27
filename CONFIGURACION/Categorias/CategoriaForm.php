
<?php
$titulo = "Categoría";
$css = "Categorias.css";

include("../../includes/config.php");
include("../../includes/header.php");
require_once("../../includes/conexion.php");

/* DATOS INICIALES */
$id = (int)($_GET["id"] ?? 0);
$error = "";
$nombre = "";
$descripcion = "";
$estado = "Activa";
$editar = $id > 0;

/* CARGAR CATEGORÍA */
if ($editar) {
    $stmt = $conexion->prepare(
        "SELECT nombre, descripcion, estado
         FROM categorias_producto
         WHERE id_categoria_producto = ?"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $categoria = $resultado->fetch_assoc();
    $stmt->close();

    if (!$categoria) {
        header("Location: Categorias.php");
        exit;
    }

    $nombre = $categoria["nombre"];
    $descripcion = $categoria["descripcion"] ?? "";
    $estado = $categoria["estado"];
}

/* GUARDAR O ACTUALIZAR */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $estado = $_POST["estado"] ?? "Activa";

    if ($nombre === "") {
        $error = "Debe ingresar el nombre de la categoría.";
    } elseif (mb_strtolower($nombre, "UTF-8") === "insumo") {
        $error = "El nombre 'insumo' está reservado para las categorías de materias primas y no puede utilizarse aquí.";
    } elseif (!in_array($estado, ["Activa", "Inactiva"], true)) {
        $error = "El estado seleccionado no es válido.";
    } else {
        /* VALIDAR NOMBRE DUPLICADO */
        if ($editar) {
            $stmt = $conexion->prepare(
                "SELECT id_categoria_producto
                 FROM categorias_producto
                 WHERE LOWER(nombre) = LOWER(?)
                   AND id_categoria_producto <> ?"
            );
            $stmt->bind_param("si", $nombre, $id);
        } else {
            $stmt = $conexion->prepare(
                "SELECT id_categoria_producto
                 FROM categorias_producto
                 WHERE LOWER(nombre) = LOWER(?)"
            );
            $stmt->bind_param("s", $nombre);
        }

        $stmt->execute();
        $resultado = $stmt->get_result();
        $existe = $resultado->num_rows > 0;
        $stmt->close();

        if ($existe) {
            $error = "Ya existe una categoría con ese nombre.";
        } else {
            try {
                if ($editar) {
                    $stmt = $conexion->prepare(
                        "UPDATE categorias_producto
                         SET nombre = ?, descripcion = ?, estado = ?
                         WHERE id_categoria_producto = ?"
                    );
                    $stmt->bind_param(
                        "sssi",
                        $nombre,
                        $descripcion,
                        $estado,
                        $id
                    );
                    $mensaje = "Categoría actualizada correctamente.";
                } else {
                    $stmt = $conexion->prepare(
                        "INSERT INTO categorias_producto
                         (nombre, descripcion, estado)
                         VALUES (?, ?, ?)"
                    );
                    $stmt->bind_param(
                        "sss",
                        $nombre,
                        $descripcion,
                        $estado
                    );
                    $mensaje = "Categoría creada correctamente.";
                }

                if ($stmt->execute()) {
                    $stmt->close();
                    header(
                        "Location: Categorias.php?mensaje=" .
                        urlencode($mensaje)
                    );
                    exit;
                }

                $stmt->close();
                $error = "No fue posible guardar la categoría.";
            } catch (mysqli_sql_exception $e) {
                if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                    $stmt->close();
                }
                $error = "Ocurrió un error al guardar la categoría.";
            }
        }
    }
}
?>

<main class="contenido">

    <!-- BARRA SUPERIOR -->
    <section class="barra-superior">
        <a href="Categorias.php" class="btn-volver">
            ← Volver a Categorías
        </a>
    </section>

    <!-- ENCABEZADO -->
    <header class="encabezado">
        <h1><?= $editar ? "Editar Categoría" : "Nueva Categoría" ?></h1>
        <p>
            <?= $editar
                ? "Modifique la información de la categoría seleccionada."
                : "Registre una categoría para organizar los productos terminados del catálogo."
            ?>
        </p>
    </header>

    <?php if ($error !== ""): ?>
        <script>
            alert(<?= json_encode($error, JSON_UNESCAPED_UNICODE) ?>);
        </script>
    <?php endif; ?>

    <!-- FORMULARIO -->
    <section class="formulario-contenedor">
        <form
            action="<?= htmlspecialchars(
                $editar
                    ? "CategoriaForm.php?id=" . $id
                    : "CategoriaForm.php",
                ENT_QUOTES,
                "UTF-8"
            ) ?>"
            method="POST"
        >

            <!-- INFORMACIÓN DE LA CATEGORÍA -->
            <div class="seccion-formulario">
                <h2>Información de la Categoría</h2>

                <div class="campo-formulario">
                    <label for="nombre">Nombre de la categoría</label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        value="<?= htmlspecialchars($nombre, ENT_QUOTES, "UTF-8") ?>"
                        placeholder="Ej. Tortas"
                        maxlength="100"
                        required
                    >
                </div>

                <div class="campo-formulario">
                    <label for="descripcion">Descripción</label>
                    <textarea
                        id="descripcion"
                        name="descripcion"
                        rows="4"
                        placeholder="Descripción de la categoría..."
                    ><?= htmlspecialchars($descripcion, ENT_QUOTES, "UTF-8") ?></textarea>
                </div>
            </div>

            <!-- ESTADO -->
            <div class="seccion-formulario">
                <h2>Estado</h2>

                <div class="campo-formulario">
                    <label for="estado">Estado de la categoría</label>
                    <select id="estado" name="estado">
                        <option
                            value="Activa"
                            <?= $estado === "Activa" ? "selected" : "" ?>
                        >Activa</option>

                        <option
                            value="Inactiva"
                            <?= $estado === "Inactiva" ? "selected" : "" ?>
                        >Inactiva</option>
                    </select>
                </div>
            </div>

            <!-- ACCIONES -->
            <div class="acciones-formulario">
                <a href="Categorias.php" class="btn-cancelar">
                    Cancelar
                </a>

                <button type="submit" class="btn-principal">
                    <?= $editar ? "Actualizar Categoría" : "Guardar Categoría" ?>
                </button>
            </div>

        </form>
    </section>
</main>

<?php include("../../includes/footer.php"); ?>