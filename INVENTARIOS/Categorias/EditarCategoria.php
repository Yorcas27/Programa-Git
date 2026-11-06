
<?php
require_once("../../includes/conexion.php");

$titulo = "EDITAR CATEGORÍA";
$css = "Categorias.css";

$idCategoria = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idCategoria || $idCategoria <= 0) {
    header("Location: Categorias.php?mensaje=id_invalido");
    exit;
}

$sql = "SELECT id_categoria, codigo, nombre, descripcion, estado
        FROM categorias
        WHERE id_categoria = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $idCategoria);
$stmt->execute();
$resultado = $stmt->get_result();
$categoria = $resultado->fetch_assoc();
$stmt->close();

if (!$categoria) {
    header("Location: Categorias.php?mensaje=no_encontrada");
    exit;
}

$errores = [
    'datos' => 'Verifica el nombre y el estado de la categoría.',
    'duplicado' => 'Ya existe una categoría con esa información.',
    'actualizar' => 'No fue posible actualizar la categoría.'
];

$error = $_GET['error'] ?? '';
$mensajeError = $errores[$error] ?? '';

include("../../includes/header.php");
?>

<main class="contenedor">

    <div class="volver">
        <a href="Categorias.php">
            ← Volver a Categorías
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>
    </div>

    <header class="encabezado">
        <h1>Editar Categoría</h1>
        <p>Modifica la información de una categoría existente.</p>
    </header>

    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Información de la Categoría</h2>
                <p>Actualiza los datos registrados.</p>
            </div>
        </div>

        <?php if ($mensajeError): ?>
            <p class="mensaje-error">
                <?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form class="formulario" action="ActualizarCategoria.php" method="POST">

            <input
                type="hidden"
                name="id_categoria"
                value="<?= (int)$categoria['id_categoria'] ?>"
            >

            <div class="grupo-formulario">

                <div class="campo">
                    <label for="codigo">Código</label>
                    <input
                        type="text"
                        id="codigo"
                        value="<?= htmlspecialchars($categoria['codigo'], ENT_QUOTES, 'UTF-8') ?>"
                        readonly
                    >
                </div>

                <div class="campo">
                    <label for="nombre">Nombre</label>
                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        maxlength="100"
                        value="<?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="campo campo-completo">
                    <label for="descripcion">Descripción</label>
                    <textarea
                        id="descripcion"
                        name="descripcion"
                        rows="4"
                    ><?= htmlspecialchars($categoria['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="campo">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" required>
                        <option value="Activa" <?= $categoria['estado'] === 'Activa' ? 'selected' : '' ?>>
                            Activa
                        </option>
                        <option value="Inactiva" <?= $categoria['estado'] === 'Inactiva' ? 'selected' : '' ?>>
                            Inactiva
                        </option>
                    </select>
                </div>

            </div>

            <div class="botones-formulario">
                <button type="submit" class="btn-guardar">
                    Guardar Cambios
                </button>

                <a href="Categorias.php" class="btn-cancelar">
                    Cancelar
                </a>
            </div>

        </form>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>
