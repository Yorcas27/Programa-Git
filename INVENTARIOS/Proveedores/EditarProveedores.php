<?php

require_once("../../includes/conexion.php");

$titulo = "EDITAR PROVEEDOR";
$css = "Proveedores.css";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: Proveedores.php");
    exit;
}

$id_proveedor = (int) $_GET["id"];

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"]);
    $nit = trim($_POST["nit"]);
    $contacto = trim($_POST["contacto"]);
    $telefono = trim($_POST["telefono"]);
    $correo = trim($_POST["correo"]);
    $direccion = trim($_POST["direccion"]);
    $ciudad = trim($_POST["ciudad"]);
    $estado = $_POST["estado"];
    $observaciones = trim($_POST["observaciones"]);

    if ($nombre === "") {

        $error = "El nombre del proveedor es obligatorio.";

    } else {

        $sql = "UPDATE proveedores
                SET nombre = ?,
                    nit = ?,
                    contacto = ?,
                    telefono = ?,
                    correo = ?,
                    direccion = ?,
                    ciudad = ?,
                    estado = ?,
                    observaciones = ?
                WHERE id_proveedor = ?";

        $stmt = $conexion->prepare($sql);

        $stmt->bind_param(
            "sssssssssi",
            $nombre,
            $nit,
            $contacto,
            $telefono,
            $correo,
            $direccion,
            $ciudad,
            $estado,
            $observaciones,
            $id_proveedor
        );

        if ($stmt->execute()) {

            header("Location: VerProveedor.php?id=" . $id_proveedor);
            exit;

        } else {

            $error = "No fue posible actualizar el proveedor.";

        }

        $stmt->close();
    }
}

$sql = "SELECT
            id_proveedor,
            codigo,
            nombre,
            nit,
            contacto,
            telefono,
            correo,
            direccion,
            ciudad,
            estado,
            observaciones
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

include("../../includes/header.php");

?>

<div class="contenedor proveedores-modulo">

    <div class="volver">

        <a href="Proveedores.php">
            ← Volver a Proveedores
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </div>

    <div class="encabezado">

        <h1>Editar Proveedor</h1>

        <p>
            Actualiza la información registrada del proveedor.
        </p>

    </div>

    <?php if ($error !== ""): ?>

        <div class="mensaje-error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>

    <section class="panel">

        <form method="POST" action="EditarProveedor.php?id=<?php echo $id_proveedor; ?>">

            <div class="grupo-formulario">

                <div class="campo">

                    <label for="codigo">Código</label>

                    <input
                        type="text"
                        id="codigo"
                        value="<?php echo htmlspecialchars($proveedor["codigo"]); ?>"
                        readonly
                    >

                </div>

                <div class="campo">

                    <label for="nombre">Nombre del Proveedor</label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        value="<?php echo htmlspecialchars($proveedor["nombre"]); ?>"
                        required
                    >

                </div>

            </div>

            <div class="grupo-formulario">

                <div class="campo">

                    <label for="nit">NIT</label>

                    <input
                        type="text"
                        id="nit"
                        name="nit"
                        value="<?php echo htmlspecialchars($proveedor["nit"] ?? ""); ?>"
                    >

                </div>

                <div class="campo">

                    <label for="contacto">Contacto</label>

                    <input
                        type="text"
                        id="contacto"
                        name="contacto"
                        value="<?php echo htmlspecialchars($proveedor["contacto"] ?? ""); ?>"
                    >

                </div>

            </div>

            <div class="grupo-formulario">

                <div class="campo">

                    <label for="telefono">Teléfono</label>

                    <input
                        type="text"
                        id="telefono"
                        name="telefono"
                        value="<?php echo htmlspecialchars($proveedor["telefono"] ?? ""); ?>"
                    >

                </div>

                <div class="campo">

                    <label for="correo">Correo electrónico</label>

                    <input
                        type="email"
                        id="correo"
                        name="correo"
                        value="<?php echo htmlspecialchars($proveedor["correo"] ?? ""); ?>"
                    >

                </div>

            </div>

            <div class="grupo-formulario">

                <div class="campo">

                    <label for="ciudad">Ciudad</label>

                    <input
                        type="text"
                        id="ciudad"
                        name="ciudad"
                        value="<?php echo htmlspecialchars($proveedor["ciudad"] ?? ""); ?>"
                    >

                </div>

                <div class="campo">

                    <label for="estado">Estado</label>

                    <select id="estado" name="estado">

                        <option value="Activo" <?php echo $proveedor["estado"] === "Activo" ? "selected" : ""; ?>>
                            Activo
                        </option>

                        <option value="Inactivo" <?php echo $proveedor["estado"] === "Inactivo" ? "selected" : ""; ?>>
                            Inactivo
                        </option>

                    </select>

                </div>

            </div>

            <div class="campo">

                <label for="direccion">Dirección</label>

                <textarea
                    id="direccion"
                    name="direccion"
                    rows="3"
                ><?php echo htmlspecialchars($proveedor["direccion"] ?? ""); ?></textarea>

            </div>

            <div class="campo">

                <label for="observaciones">Observaciones</label>

                <textarea
                    id="observaciones"
                    name="observaciones"
                    rows="3"
                ><?php echo htmlspecialchars($proveedor["observaciones"] ?? ""); ?></textarea>

            </div>

            <div class="acciones-formulario">

                <button type="submit" class="btn btn-guardar">
                    Actualizar
                </button>

                <button type="reset" class="btn btn-limpiar">
                    Limpiar
                </button>

                <a href="Proveedores.php" class="btn btn-cancelar">
                    Cancelar
                </a>

            </div>

        </form>

    </section>

</div>

<?php

include("../../includes/footer.php");

?>