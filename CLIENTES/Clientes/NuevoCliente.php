<?php
$titulo = "NUEVO CLIENTE";
$css = "Clientes.css";

require_once("../../includes/conexion.php");

/* VARIABLES DEL FORMULARIO */

$nombre = "";
$documento = "";
$telefono = "";
$correo = "";
$ciudad = "";
$estado = "Activo";
$direccion = "";
$observaciones = "";
$mensaje = "";
$tipoMensaje = "";

/* GENERAR CÓDIGO DEL CLIENTE */

function obtenerCodigoCliente($conexion) {
    $sql = "
        SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) AS ultimo_codigo
        FROM clientes
        WHERE codigo REGEXP '^CLI-[0-9]+$'
    ";

    $resultado = $conexion->query($sql);
    $ultimoCodigo = 0;

    if ($resultado && $fila = $resultado->fetch_assoc()) {
        $ultimoCodigo = (int)($fila["ultimo_codigo"] ?? 0);
    }

    $siguienteCodigo = $ultimoCodigo + 1;

    return "CLI-" . str_pad($siguienteCodigo, 4, "0", STR_PAD_LEFT);
}

$codigo_cliente = obtenerCodigoCliente($conexion);

/* PROCESAR FORMULARIO */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre"] ?? "");
    $documento = trim($_POST["documento"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $ciudad = trim($_POST["ciudad"] ?? "");
    $estado = $_POST["estado"] ?? "Activo";
    $direccion = trim($_POST["direccion"] ?? "");
    $observaciones = trim($_POST["observaciones"] ?? "");

    /* VALIDAR CAMPOS OBLIGATORIOS */

    if ($nombre === "" || $documento === "") {

        $mensaje = "El nombre y el documento son obligatorios.";
        $tipoMensaje = "error";

    } elseif (!in_array($estado, ["Activo", "Inactivo"], true)) {

        $mensaje = "El estado seleccionado no es válido.";
        $tipoMensaje = "error";

    } else {

        /* COMPROBAR DOCUMENTO DUPLICADO */

        $consulta = $conexion->prepare("
            SELECT codigo, nombre
            FROM clientes
            WHERE documento = ?
            LIMIT 1
        ");

        if (!$consulta) {

            $mensaje = "No fue posible validar el documento. Intenta nuevamente.";
            $tipoMensaje = "error";

        } else {

            $consulta->bind_param("s", $documento);
            $consulta->execute();

            $resultadoDocumento = $consulta->get_result();
            $clienteExistente = $resultadoDocumento->fetch_assoc();

            $consulta->close();

            if ($clienteExistente) {

                $mensaje = "No se puede registrar el cliente porque el documento "
                    . $documento . " ya pertenece a "
                    . $clienteExistente["nombre"] . " ("
                    . $clienteExistente["codigo"] . "). "
                    . "Verifica el registro existente antes de continuar.";

                $tipoMensaje = "advertencia";

            } else {

                /* GENERAR CÓDIGO ACTUALIZADO */

                $codigo_cliente = obtenerCodigoCliente($conexion);

                /* INSERTAR CLIENTE */

                try {

                    $sql = "
                        INSERT INTO clientes (
                            codigo,
                            nombre,
                            documento,
                            telefono,
                            correo,
                            ciudad,
                            estado,
                            direccion,
                            observaciones
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ";

                    $stmt = $conexion->prepare($sql);

                    if (!$stmt) {
                        throw new Exception("No fue posible preparar el registro.");
                    }

                    $stmt->bind_param(
                        "sssssssss",
                        $codigo_cliente,
                        $nombre,
                        $documento,
                        $telefono,
                        $correo,
                        $ciudad,
                        $estado,
                        $direccion,
                        $observaciones
                    );

                    $stmt->execute();
                    $stmt->close();

                    header("Location: Clientes.php");
                    exit;

                } catch (mysqli_sql_exception $e) {

                    if ((int)$e->getCode() === 1062) {

                        $mensaje = "El documento o el código del cliente ya existe. "
                            . "Verifica los datos e inténtalo nuevamente.";

                        $tipoMensaje = "advertencia";

                        $codigo_cliente = obtenerCodigoCliente($conexion);

                    } else {

                        error_log("NuevoCliente.php: " . $e->getMessage());

                        $mensaje = "Ocurrió un error al guardar el cliente. "
                            . "Intenta nuevamente.";

                        $tipoMensaje = "error";
                    }

                } catch (Throwable $e) {

                    error_log("NuevoCliente.php: " . $e->getMessage());

                    $mensaje = "No fue posible guardar el cliente. "
                        . "Intenta nuevamente.";

                    $tipoMensaje = "error";
                }
            }
        }
    }
}

include("../../includes/header.php");
?>

<main class="contenido">

    <section class="barra-superior">

        <a href="Clientes.php" class="btn btn-principal">
            ← Volver a Clientes
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </section>

    <header class="encabezado">

        <h1>Nuevo Cliente</h1>

        <p>
            Registre la información de un nuevo cliente del sistema.
        </p>

    </header>

    <section class="panel">

        <div class="panel-header">

            <div>
                <h2>Información del Cliente</h2>

                <p>
                    Complete los siguientes datos para registrar el cliente.
                </p>
            </div>

        </div>

        <?php if ($mensaje !== ""): ?>

            <div
                role="alert"
                style="
                    margin: 15px 0;
                    padding: 14px 16px;
                    border-radius: 6px;
                    border: 1px solid <?= $tipoMensaje === "advertencia" ? "#f0c36d" : "#ef9a9a" ?>;
                    background-color: <?= $tipoMensaje === "advertencia" ? "#fff8e1" : "#fdecea" ?>;
                    color: <?= $tipoMensaje === "advertencia" ? "#795500" : "#b71c1c" ?>;
                    line-height: 1.5;
                "
            >
                <strong>
                    <?= $tipoMensaje === "advertencia" ? "⚠ Atención:" : "Error:" ?>
                </strong>

                <?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?>
            </div>

        <?php endif; ?>

        <form class="formulario" method="POST">

            <div class="grupo-formulario">

                <div class="campo">

                    <label for="codigo">Código</label>

                    <input
                        type="text"
                        id="codigo"
                        name="codigo"
                        value="<?= htmlspecialchars($codigo_cliente, ENT_QUOTES, "UTF-8") ?>"
                        readonly
                    >

                </div>

                <div class="campo">

                    <label for="nombre">Nombre Completo</label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        placeholder="Ingrese el nombre del cliente"
                        value="<?= htmlspecialchars($nombre, ENT_QUOTES, "UTF-8") ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="documento">Documento</label>

                    <input
                        type="text"
                        id="documento"
                        name="documento"
                        placeholder="Número de identificación"
                        value="<?= htmlspecialchars($documento, ENT_QUOTES, "UTF-8") ?>"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="telefono">Teléfono</label>

                    <input
                        type="text"
                        id="telefono"
                        name="telefono"
                        placeholder="Número de contacto"
                        value="<?= htmlspecialchars($telefono, ENT_QUOTES, "UTF-8") ?>"
                    >

                </div>

                <div class="campo">

                    <label for="correo">Correo Electrónico</label>

                    <input
                        type="email"
                        id="correo"
                        name="correo"
                        placeholder="correo@ejemplo.com"
                        value="<?= htmlspecialchars($correo, ENT_QUOTES, "UTF-8") ?>"
                    >

                </div>

                <div class="campo">

                    <label for="ciudad">Ciudad</label>

                    <input
                        type="text"
                        id="ciudad"
                        name="ciudad"
                        placeholder="Ciudad de residencia"
                        value="<?= htmlspecialchars($ciudad, ENT_QUOTES, "UTF-8") ?>"
                    >

                </div>

                <div class="campo">

                    <label for="estado">Estado</label>

                    <select id="estado" name="estado">

                        <option value="Activo" <?= $estado === "Activo" ? "selected" : "" ?>>
                            Activo
                        </option>

                        <option value="Inactivo" <?= $estado === "Inactivo" ? "selected" : "" ?>>
                            Inactivo
                        </option>

                    </select>

                </div>

                <div class="campo campo-completo">

                    <label for="direccion">Dirección</label>

                    <textarea
                        id="direccion"
                        name="direccion"
                        rows="3"
                        placeholder="Dirección del cliente"
                    ><?= htmlspecialchars($direccion, ENT_QUOTES, "UTF-8") ?></textarea>

                </div>

                <div class="campo campo-completo">

                    <label for="observaciones">Observaciones</label>

                    <textarea
                        id="observaciones"
                        name="observaciones"
                        rows="4"
                        placeholder="Información adicional..."
                    ><?= htmlspecialchars($observaciones, ENT_QUOTES, "UTF-8") ?></textarea>

                </div>

            </div>

            <div class="botones-formulario">

                <button type="submit" class="btn btn-principal">
                    Guardar
                </button>

                <button type="reset" class="btn btn-limpiar">
                    Limpiar
                </button>

                <a href="Clientes.php" class="btn btn-cancelar">
                    Cancelar
                </a>

            </div>

        </form>

    </section>

</main>

<?php include("../../includes/footer.php"); ?>