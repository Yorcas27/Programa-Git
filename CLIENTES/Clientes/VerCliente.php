<?php

$titulo = "VER CLIENTE";
$css = "Clientes.css";

/*
 * Conexión a la base de datos
 */
require_once("../../includes/conexion.php");


/*
 * Obtener el ID del cliente desde la URL
 */
$id_cliente = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($id_cliente <= 0) {
    die("Error: cliente no válido.");
}


/*
 * Consultar el cliente
 */
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

$stmt = $conexion->prepare($sql_cliente);

if (!$stmt) {
    die("Error al consultar el cliente: " . $conexion->error);
}

$stmt->bind_param("i", $id_cliente);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    $stmt->close();
    die("Error: el cliente no existe.");
}

$cliente = $resultado->fetch_assoc();

$stmt->close();

include("../../includes/header.php");

?>

<main class="contenido">

    <!-- BOTÓN VOLVER -->

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
            Información del Cliente
        </h1>

        <p>
            Consulte la información registrada del cliente.
        </p>

    </header>


    <!-- PANEL -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Datos del Cliente
                </h2>

                <p>
                    Información general registrada en el sistema.
                </p>

            </div>

        </div>


        <form class="formulario">

            <div class="grupo-formulario">


                <div class="campo">

                    <label>Código</label>

                    <input
                        type="text"
                        value="<?= htmlspecialchars($cliente["codigo"]) ?>"
                        readonly
                    >

                </div>


                <div class="campo">

                    <label>Nombre Completo</label>

                    <input
                        type="text"
                        value="<?= htmlspecialchars($cliente["nombre"]) ?>"
                        readonly
                    >

                </div>


                <div class="campo">

                    <label>Documento</label>

                    <input
                        type="text"
                        value="<?= htmlspecialchars($cliente["documento"]) ?>"
                        readonly
                    >

                </div>


                <div class="campo">

                    <label>Teléfono</label>

                    <input
                        type="text"
                        value="<?= htmlspecialchars($cliente["telefono"] ?? "") ?>"
                        readonly
                    >

                </div>


                <div class="campo">

                    <label>Correo Electrónico</label>

                    <input
                        type="email"
                        value="<?= htmlspecialchars($cliente["correo"] ?? "") ?>"
                        readonly
                    >

                </div>


                <div class="campo">

                    <label>Ciudad</label>

                    <input
                        type="text"
                        value="<?= htmlspecialchars($cliente["ciudad"] ?? "") ?>"
                        readonly
                    >

                </div>


                <div class="campo">

                    <label>Estado</label>

                    <input
                        type="text"
                        value="<?= htmlspecialchars($cliente["estado"]) ?>"
                        readonly
                    >

                </div>


                <div class="campo campo-completo">

                    <label>Dirección</label>

                    <textarea rows="3" readonly><?= htmlspecialchars($cliente["direccion"] ?? "") ?></textarea>

                </div>


                <div class="campo campo-completo">

                    <label>Observaciones</label>

                    <textarea rows="4" readonly><?= htmlspecialchars($cliente["observaciones"] ?? "") ?></textarea>

                </div>

            </div>


            <div class="botones-formulario">

                <a
                    href="Clientes.php"
                    class="btn btn-principal"
                >

                    Volver

                </a>

            </div>

        </form>

    </section>

</main>

<?php

include("../../includes/footer.php");

?>