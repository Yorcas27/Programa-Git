
<?php

require_once("../../includes/conexion.php");

$titulo = "ELIMINAR PROVEEDOR";
$css = "Proveedores.css";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: Proveedores.php");
    exit;
}

$id_proveedor = (int) $_GET["id"];

$sql = "SELECT id_proveedor, codigo, nombre
        FROM proveedores
        WHERE id_proveedor = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_proveedor);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    header("Location: Proveedores.php");
    exit;
}

$proveedor = $resultado->fetch_assoc();

$stmt->close();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $sql = "DELETE FROM proveedores
            WHERE id_proveedor = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_proveedor);

    if ($stmt->execute()) {

        header("Location: Proveedores.php");
        exit;

    } else {

        if ($stmt->errno == 1451) {

            $error = "No se puede eliminar este proveedor porque tiene registros relacionados con otras operaciones del sistema.";

        } else {

            $error = "No fue posible eliminar el proveedor.";

        }
    }

    $stmt->close();
}

include("../../includes/header.php");

?>

<div class="contenedor">

    <div class="volver">

        <a href="Proveedores.php">
            ← Volver a Proveedores
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </div>

    <div class="encabezado">

        <h1>Eliminar Proveedor</h1>

        <p>
            Confirma la eliminación del proveedor seleccionado.
        </p>

    </div>

    <section class="panel">

        <?php if ($error !== ""): ?>

            <div class="mensaje-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <div class="campo">

            <label>Código</label>

            <input
                type="text"
                value="<?php echo htmlspecialchars($proveedor["codigo"]); ?>"
                readonly
            >

        </div>

        <div class="campo">

            <label>Proveedor</label>

            <input
                type="text"
                value="<?php echo htmlspecialchars($proveedor["nombre"]); ?>"
                readonly
            >

        </div>

        <form method="POST" action="EliminarProveedor.php?id=<?php echo $id_proveedor; ?>">

            <div class="acciones-formulario">

                <button
                    type="submit"
                    class="btn btn-eliminar"
                >
                    Eliminar
                </button>

                <a
                    href="Proveedores.php"
                    class="btn btn-cancelar"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </section>

</div>

<?php

include("../../includes/footer.php");

?>

