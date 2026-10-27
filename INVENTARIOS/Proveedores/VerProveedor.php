<?php

require_once("../../includes/conexion.php");

$titulo = "VER PROVEEDOR";
$css = "Proveedores.css";

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: Proveedores.php");
    exit;
}

$id_proveedor = (int) $_GET["id"];

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

        <h1>Información del Proveedor</h1>

        <p>
            Consulta la información registrada del proveedor.
        </p>

    </div>

    <section class="panel">

        <div class="grupo-formulario">

            <div class="campo">

                <label>Código</label>

                <input
                    type="text"
                    value="<?php echo htmlspecialchars($proveedor["codigo"]); ?>"
                    readonly
                >

            </div>

            <div class="campo">

                <label>Estado</label>

                <input
                    type="text"
                    value="<?php echo htmlspecialchars($proveedor["estado"]); ?>"
                    readonly
                >

            </div>

        </div>

        <div class="grupo-formulario">

            <div class="campo">

                <label>Nombre del Proveedor</label>

                <input
                    type="text"
                    value="<?php echo htmlspecialchars($proveedor["nombre"]); ?>"
                    readonly
                >

            </div>

            <div class="campo">

                <label>NIT</label>

                <input
                    type="text"
                    value="<?php echo htmlspecialchars($proveedor["nit"] ?? ""); ?>"
                    readonly
                >

            </div>

        </div>

        <div class="grupo-formulario">

            <div class="campo">

                <label>Contacto</label>

                <input
                    type="text"
                    value="<?php echo htmlspecialchars($proveedor["contacto"] ?? ""); ?>"
                    readonly
                >

            </div>

            <div class="campo">

                <label>Ciudad</label>

                <input
                    type="text"
                    value="<?php echo htmlspecialchars($proveedor["ciudad"] ?? ""); ?>"
                    readonly
                >

            </div>

        </div>

        <div class="grupo-formulario">

            <div class="campo">

                <label>Teléfono</label>

                <input
                    type="text"
                    value="<?php echo htmlspecialchars($proveedor["telefono"] ?? ""); ?>"
                    readonly
                >

            </div>

            <div class="campo">

                <label>Correo electrónico</label>

                <input
                    type="text"
                    value="<?php echo htmlspecialchars($proveedor["correo"] ?? ""); ?>"
                    readonly
                >

            </div>

        </div>

        <div class="campo">

            <label>Dirección</label>

            <textarea rows="3" readonly><?php echo htmlspecialchars($proveedor["direccion"] ?? ""); ?></textarea>

        </div>

        <div class="campo">

            <label>Observaciones</label>

            <textarea rows="3" readonly><?php echo htmlspecialchars($proveedor["observaciones"] ?? ""); ?></textarea>

        </div>

        <div class="acciones-formulario">

            <a
                href="EditarProveedores.php?id=<?php echo $proveedor["id_proveedor"]; ?>"
                class="btn btn-editar"
            >
                Editar
            </a>

            <a
                href="Proveedores.php"
                class="btn btn-cancelar"
            >
                Volver
            </a>

        </div>

    </section>

</div>

<?php

include("../../includes/footer.php");

?>