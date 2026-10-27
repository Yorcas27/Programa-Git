<?php

require_once("../../includes/conexion.php");

$titulo = "NUEVO PROVEEDOR";
$css = "Proveedores.css";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $codigo = trim($_POST["codigo"]);
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

        $sql = "INSERT INTO proveedores
                (codigo, nombre, nit, contacto, telefono, correo, direccion, ciudad, estado, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "ssssssssss",
                $codigo,
                $nombre,
                $nit,
                $contacto,
                $telefono,
                $correo,
                $direccion,
                $ciudad,
                $estado,
                $observaciones
            );

            if ($stmt->execute()) {
                header("Location: Proveedores.php");
                exit;
            }

            if ($stmt->errno == 1062) {
                $error = "El código del proveedor ya existe.";
            } else {
                $error = "No fue posible registrar el proveedor.";
            }

            $stmt->close();

        } else {
            $error = "Error al preparar el registro.";
        }
    }
}

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

        <h1>Nuevo Proveedor</h1>

        <p>
            Registra un nuevo proveedor para el suministro de insumos.
        </p>

    </div>

    <?php if ($error !== ""): ?>

        <div class="mensaje-error">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>

    <section class="panel">

        <form method="POST" action="">

            <div class="grupo-formulario">

                <div class="campo">

                    <label for="codigo">Código</label>

                    <input
                        type="text"
                        id="codigo"
                        name="codigo"
                        value="PRV001"
                        readonly
                    >

                </div>

                <div class="campo">

                    <label for="nombre">Nombre del Proveedor</label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
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
                    >

                </div>

                <div class="campo">

                    <label for="contacto">Contacto</label>

                    <input
                        type="text"
                        id="contacto"
                        name="contacto"
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
                    >

                </div>

                <div class="campo">

                    <label for="correo">Correo electrónico</label>

                    <input
                        type="email"
                        id="correo"
                        name="correo"
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
                    >

                </div>

                <div class="campo">

                    <label for="estado">Estado</label>

                    <select id="estado" name="estado">

                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>

                    </select>

                </div>

            </div>

            <div class="campo">

                <label for="direccion">Dirección</label>

                <textarea
                    id="direccion"
                    name="direccion"
                    rows="3"
                ></textarea>

            </div>

            <div class="campo">

                <label for="observaciones">Observaciones</label>

                <textarea
                    id="observaciones"
                    name="observaciones"
                    rows="3"
                ></textarea>

            </div>

            <div class="acciones-formulario">

                <button type="submit" class="btn btn-guardar">
                    Guardar
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