<?php

$titulo = "EDITAR CLIENTE";
$css = "Clientes.css";

/* CONEXION A LA BASE DE DATOS */

require_once("../../includes/conexion.php");


/* OBTENER ID DEL CLIENTE */

$id_cliente = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id_cliente <= 0) {

    die("Error: cliente no válido.");

}


/* ACTUALIZAR CLIENTE */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $documento = trim($_POST["documento"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $ciudad = trim($_POST["ciudad"] ?? "");
    $estado = $_POST["estado"] ?? "Activo";
    $direccion = trim($_POST["direccion"] ?? "");
    $observaciones = trim($_POST["observaciones"] ?? "");


    /* VALIDACIONES */

    if ($nombre === "" || $documento === "") {

        die("Error: el nombre y el documento son obligatorios.");

    }


    /* ACTUALIZAR */

    $sql = "UPDATE clientes
            SET nombre = ?,
                documento = ?,
                telefono = ?,
                correo = ?,
                ciudad = ?,
                estado = ?,
                direccion = ?,
                observaciones = ?
            WHERE id_cliente = ?";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {

        die("Error al preparar la actualización: " . $conexion->error);

    }

    $stmt->bind_param(
        "ssssssssi",
        $nombre,
        $documento,
        $telefono,
        $correo,
        $ciudad,
        $estado,
        $direccion,
        $observaciones,
        $id_cliente
    );


    /* GUARDAR CAMBIOS */

    if ($stmt->execute()) {

        $stmt->close();

        header("Location: Clientes.php");
        exit;

    } else {

        if ($conexion->errno === 1062) {

            die("Error: el documento ingresado ya está registrado.");

        }

        die("Error al actualizar el cliente: " . $stmt->error);

    }

}


/* CONSULTAR CLIENTE */

$sql_cliente = "SELECT
                    codigo,
                    nombre,
                    documento,
                    telefono,
                    correo,
                    ciudad,
                    estado,
                    direccion,
                    observaciones
                FROM clientes
                WHERE id_cliente = ?";

$stmt_cliente = $conexion->prepare($sql_cliente);

if (!$stmt_cliente) {

    die("Error al consultar el cliente: " . $conexion->error);

}

$stmt_cliente->bind_param("i", $id_cliente);

$stmt_cliente->execute();

$resultado_cliente = $stmt_cliente->get_result();

if ($resultado_cliente->num_rows === 0) {

    $stmt_cliente->close();

    die("Error: el cliente no existe.");

}

$cliente = $resultado_cliente->fetch_assoc();

$stmt_cliente->close();


include("../../includes/header.php");

?>

<main class="contenido">

    <!-- BARRA SUPERIOR -->

    <section class="barra-superior">

        <a href="Clientes.php" class="btn btn-principal">

            ← Volver a Clientes

        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">

            ⌂ Inicio

        </a>

    </section>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>
            Editar Cliente
        </h1>

        <p>
            Modifique la información registrada del cliente.
        </p>

    </header>


    <!-- PANEL -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Actualización del Cliente
                </h2>

                <p>
                    Edite la información necesaria y guarde los cambios.
                </p>

            </div>

        </div>


        <!-- FORMULARIO -->

        <form class="formulario" method="POST">

            <div class="grupo-formulario">

                <!-- CODIGO -->

                <div class="campo">

                    <label>
                        Código
                    </label>

                    <input
                        type="text"
                        value="<?= htmlspecialchars($cliente["codigo"]) ?>"
                        readonly
                    >

                </div>


                <!-- NOMBRE -->

                <div class="campo">

                    <label for="nombre">
                        Nombre Completo
                    </label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        value="<?= htmlspecialchars($cliente["nombre"]) ?>"
                        required
                    >

                </div>


                <!-- DOCUMENTO -->

                <div class="campo">

                    <label for="documento">
                        Documento
                    </label>

                    <input
                        type="text"
                        id="documento"
                        name="documento"
                        value="<?= htmlspecialchars($cliente["documento"]) ?>"
                        required
                    >

                </div>


                <!-- TELEFONO -->

                <div class="campo">

                    <label for="telefono">
                        Teléfono
                    </label>

                    <input
                        type="text"
                        id="telefono"
                        name="telefono"
                        value="<?= htmlspecialchars($cliente["telefono"] ?? "") ?>"
                    >

                </div>


                <!-- CORREO -->

                <div class="campo">

                    <label for="correo">
                        Correo Electrónico
                    </label>

                    <input
                        type="email"
                        id="correo"
                        name="correo"
                        value="<?= htmlspecialchars($cliente["correo"] ?? "") ?>"
                    >

                </div>


                <!-- CIUDAD -->

                <div class="campo">

                    <label for="ciudad">
                        Ciudad
                    </label>

                    <input
                        type="text"
                        id="ciudad"
                        name="ciudad"
                        value="<?= htmlspecialchars($cliente["ciudad"] ?? "") ?>"
                    >

                </div>


                <!-- ESTADO -->

                <div class="campo">

                    <label for="estado">
                        Estado
                    </label>

                    <select id="estado" name="estado">

                        <option
                            value="Activo"
                            <?= $cliente["estado"] === "Activo" ? "selected" : "" ?>
                        >
                            Activo
                        </option>

                        <option
                            value="Inactivo"
                            <?= $cliente["estado"] === "Inactivo" ? "selected" : "" ?>
                        >
                            Inactivo
                        </option>

                    </select>

                </div>


                <!-- DIRECCION -->

                <div class="campo campo-completo">

                    <label for="direccion">
                        Dirección
                    </label>

                    <textarea
                        id="direccion"
                        name="direccion"
                        rows="3"
                    ><?= htmlspecialchars($cliente["direccion"] ?? "") ?></textarea>

                </div>


                <!-- OBSERVACIONES -->

                <div class="campo campo-completo">

                    <label for="observaciones">
                        Observaciones
                    </label>

                    <textarea
                        id="observaciones"
                        name="observaciones"
                        rows="4"
                    ><?= htmlspecialchars($cliente["observaciones"] ?? "") ?></textarea>

                </div>

            </div>


            <!-- BOTONES -->

            <div class="botones-formulario">

                <button
                    type="submit"
                    class="btn btn-principal"
                >
                    Actualizar
                </button>

                <a
                    href="Clientes.php"
                    class="btn btn-cancelar"
                >
                    Cancelar
                </a>

            </div>

        </form>

    </section>

</main>

<?php

include("../../includes/footer.php");

?>