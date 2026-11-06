
<?php

$titulo = "NUEVA CATEGORÍA";
$css = "Categorias.css";

require_once("../../includes/conexion.php");

$sqlCodigo = "SELECT codigo
              FROM categorias
              WHERE codigo REGEXP '^CAT[0-9]+$'
              ORDER BY CAST(SUBSTRING(codigo, 4) AS UNSIGNED) DESC
              LIMIT 1";

$resultadoCodigo = $conexion->query($sqlCodigo);

$numero = 1;

if ($resultadoCodigo && $resultadoCodigo->num_rows > 0) {
    $ultimo = $resultadoCodigo->fetch_assoc()['codigo'];
    $numero = (int) substr($ultimo, 3) + 1;
}

$codigoSiguiente = "CAT" . str_pad($numero, 3, "0", STR_PAD_LEFT);

include("../../includes/header.php");

?>

<main class="contenedor">

    <div class="volver">
        <a href="Categorias.php">← Volver a Categorías</a>
        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">⌂ Inicio</a>
    </div>

    <header class="encabezado">
        <h1>Nueva Categoría</h1>
        <p>Registra una nueva categoría para clasificar los insumos del inventario.</p>
    </header>

    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Información de la Categoría</h2>
                <p>Complete los siguientes datos.</p>
            </div>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <p class="mensaje-error">
                <?php
                $errores = [
                    'datos' => 'Verifique la información ingresada.',
                    'duplicado' => 'No se pudo generar un código único. Intente nuevamente.',
                    'guardar' => 'No fue posible registrar la categoría.',
                ];

                echo htmlspecialchars(
                    $errores[$_GET['error']] ?? 'Ocurrió un error.'
                );
                ?>
            </p>
        <?php endif; ?>

        <form class="formulario" action="GuardarCategoria.php" method="POST">

            <div class="grupo-formulario">

                <div class="campo">
                    <label for="codigo">Código</label>
                    <input
                        type="text"
                        id="codigo"
                        value="<?= htmlspecialchars($codigoSiguiente) ?>"
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
                        placeholder="Ingrese el nombre de la categoría"
                        required
                    >
                </div>

                <div class="campo campo-completo">
                    <label for="descripcion">Descripción</label>
                    <textarea
                        id="descripcion"
                        name="descripcion"
                        rows="4"
                        placeholder="Ingrese una descripción"
                    ></textarea>
                </div>

                <div class="campo">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" required>
                        <option value="Activa">Activa</option>
                        <option value="Inactiva">Inactiva</option>
                    </select>
                </div>

            </div>

            <div class="botones-formulario">

                <button type="submit" class="btn-guardar">
                    Guardar
                </button>

                <button type="reset" class="btn-limpiar">
                    Limpiar
                </button>

                <a href="Categorias.php" class="btn-cancelar">
                    Cancelar
                </a>

            </div>

        </form>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>
