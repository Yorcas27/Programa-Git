<?php

/* CONFIGURACIÓN */

session_start();

$titulo = "Recuperar Acceso";
$css = "RecuperarAcceso.css";

include("../includes/config.php");
include("../includes/conexion.php");

$error = "";
$codigoPrueba = "";

/* PROCESAR SOLICITUD */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario = trim($_POST["usuario"] ?? "");
    $tipoRecuperacion = $_POST["tipo_recuperacion"] ?? "";
    $medio = $_POST["medio"] ?? "";

    /* VALIDAR OPCIONES */

    $tiposPermitidos = [
        "contrasena",
        "usuario",
        "ambos"
    ];

    $mediosPermitidos = [
        "correo",
        "telefono"
    ];

    if (!in_array($tipoRecuperacion, $tiposPermitidos, true)) {

        $error = "Seleccione qué desea recuperar.";

    } elseif (!in_array($medio, $mediosPermitidos, true)) {

        $error = "Seleccione un medio de recuperación.";

    } elseif (
        ($tipoRecuperacion === "contrasena" ||
         $tipoRecuperacion === "usuario") &&
        $usuario === ""
    ) {

        $error = "Ingrese su usuario.";

    } else {

        /* BUSCAR USUARIO */

        $datosUsuario = null;

        if (
            $tipoRecuperacion === "ambos" &&
            $usuario === ""
        ) {

            /* BUSCAR POR MEDIO DE RECUPERACIÓN */

            if ($medio === "telefono") {

                $sql = "SELECT
                            id_usuario,
                            usuario,
                            correo_recuperacion,
                            telefono_recuperacion,
                            estado
                        FROM usuarios
                        WHERE telefono_recuperacion IS NOT NULL
                        AND telefono_recuperacion <> ''
                        AND estado = 'Activo'
                        LIMIT 1";

            } else {

                $sql = "SELECT
                            id_usuario,
                            usuario,
                            correo_recuperacion,
                            telefono_recuperacion,
                            estado
                        FROM usuarios
                        WHERE correo_recuperacion IS NOT NULL
                        AND correo_recuperacion <> ''
                        AND estado = 'Activo'
                        LIMIT 1";
            }

            $stmt = $conexion->prepare($sql);

            if ($stmt) {

                $stmt->execute();

                $resultado = $stmt->get_result();
                $datosUsuario = $resultado->fetch_assoc();

                $stmt->close();

            }

        } else {

            /* BUSCAR POR USUARIO */

            $stmt = $conexion->prepare(
                "SELECT
                    id_usuario,
                    usuario,
                    correo_recuperacion,
                    telefono_recuperacion,
                    estado
                 FROM usuarios
                 WHERE usuario = ?
                 LIMIT 1"
            );

            if ($stmt) {

                $stmt->bind_param(
                    "s",
                    $usuario
                );

                $stmt->execute();

                $resultado = $stmt->get_result();
                $datosUsuario = $resultado->fetch_assoc();

                $stmt->close();

            }
        }

        /* COMPROBAR USUARIO */

        if (
            !$datosUsuario ||
            $datosUsuario["estado"] !== "Activo"
        ) {

            $error =
                "No fue posible procesar la solicitud. "
                . "Verifique los datos e inténtelo nuevamente.";

        } else {

            /* DETERMINAR DESTINO */

            if ($medio === "correo") {

                $destino = trim(
                    $datosUsuario["correo_recuperacion"] ?? ""
                );

                $medioBD = "Correo";

            } else {

                $destino = trim(
                    $datosUsuario["telefono_recuperacion"] ?? ""
                );

                $medioBD = "Telefono";
            }

            /* COMPROBAR MEDIO CONFIGURADO */

            if ($destino === "") {

                $error =
                    "El medio de recuperación seleccionado "
                    . "no está configurado.";

            } else {

                /* INVALIDAR CÓDIGOS ANTERIORES */

                $stmt = $conexion->prepare(
                    "UPDATE recuperacion_acceso
                     SET utilizado = 1
                     WHERE id_usuario = ?
                     AND utilizado = 0"
                );

                if ($stmt) {

                    $idUsuario = (int) $datosUsuario["id_usuario"];

                    $stmt->bind_param(
                        "i",
                        $idUsuario
                    );

                    $stmt->execute();
                    $stmt->close();
                }

                /* GENERAR CÓDIGO */

                $codigo = (string) random_int(
                    100000,
                    999999
                );

                $codigoHash = password_hash(
                    $codigo,
                    PASSWORD_DEFAULT
                );

                $fechaExpiracion = date(
                    "Y-m-d H:i:s",
                    time() + 600
                );

                /* GUARDAR CÓDIGO */

                $stmt = $conexion->prepare(
                    "INSERT INTO recuperacion_acceso
                    (
                        id_usuario,
                        codigo_hash,
                        medio,
                        destino,
                        fecha_expiracion
                    )
                    VALUES (?, ?, ?, ?, ?)"
                );

                if ($stmt) {

                    $stmt->bind_param(
                        "issss",
                        $idUsuario,
                        $codigoHash,
                        $medioBD,
                        $destino,
                        $fechaExpiracion
                    );

                    if ($stmt->execute()) {

                        $stmt->close();

                        /* GUARDAR DATOS DE RECUPERACIÓN */

                        $_SESSION["recuperacion_id_usuario"] =
                            $idUsuario;

                        $_SESSION["recuperacion_tipo"] =
                            $tipoRecuperacion;

                        $_SESSION["recuperacion_medio"] =
                            $medio;

                        $_SESSION["recuperacion_destino"] =
                            $destino;

                        /* CÓDIGO SOLO PARA PRUEBAS */

                        $_SESSION["recuperacion_codigo_prueba"] =
                            $codigo;

                        $codigoPrueba = $codigo;

                    } else {

                        $stmt->close();

                        $error =
                            "No fue posible generar el código.";
                    }

                } else {

                    $error =
                        "No fue posible procesar la solicitud.";
                }
            }
        }
    }
}

/* INTERFAZ */

include("../includes/header.php");

?>

<div class="recuperacion-container">

    <div class="recuperacion-card">

        <!-- LOGO -->

        <div class="logo-placeholder">

            <img
                src="../IMG/pastelera.jpg"
                alt="Pastelera"
            >

        </div>

        <!-- TÍTULO -->

        <h2>
            Recuperar acceso
        </h2>

        <p class="subtitulo">
            Recupere el acceso a YCL Solutions
        </p>

        <p class="bienvenida">
            Seleccione qué desea recuperar y el medio que desea utilizar.
        </p>

        <!-- ERROR -->

        <?php if ($error !== ""): ?>

            <div
                style="
                    margin-bottom:20px;
                    padding:12px;
                    background:#fdecea;
                    border:1px solid #f5c6cb;
                    border-radius:8px;
                    color:#c0392b;
                    font-size:13px;
                    text-align:center;
                "
            >
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <?php if ($codigoPrueba !== ""): ?>

            <!-- CÓDIGO DE PRUEBA -->

            <div
                style="
                    margin-bottom:20px;
                    padding:18px;
                    background:#eaf8ef;
                    border:1px solid #ccebd7;
                    border-radius:8px;
                    color:#218838;
                    text-align:center;
                "
            >

                <div
                    style="
                        font-size:13px;
                        margin-bottom:8px;
                    "
                >
                    Código de recuperación
                </div>

                <div
                    style="
                        font-size:28px;
                        font-weight:700;
                        letter-spacing:4px;
                    "
                >
                    <?= htmlspecialchars($codigoPrueba) ?>
                </div>

                <div
                    style="
                        margin-top:8px;
                        font-size:12px;
                    "
                >
                    Código válido durante 10 minutos.
                </div>

            </div>

            <a
                href="VerificarCodigo.php"
                class="btn-recuperar"
                style="
                    display:block;
                    text-align:center;
                    text-decoration:none;
                    margin-top:10px;
                "
            >
                Ir a verificar código
            </a>

        <?php else: ?>

            <!-- FORMULARIO -->

            <form
                method="POST"
                action="RecuperarAcceso.php"
            >

                <!-- USUARIO -->

                <label for="usuario">
                    Usuario
                </label>

                <input
                    type="text"
                    id="usuario"
                    name="usuario"
                    placeholder="Ingrese su usuario"
                    autocomplete="username"
                >

                <!-- RECUPERAR -->

                <label for="tipo_recuperacion">
                    ¿Qué desea recuperar?
                </label>

                <select
                    id="tipo_recuperacion"
                    name="tipo_recuperacion"
                    required
                >

                    <option value="">
                        Seleccione una opción
                    </option>

                    <option value="contrasena">
                        Contraseña
                    </option>

                    <option value="usuario">
                        Usuario
                    </option>

                    <option value="ambos">
                        Usuario y contraseña
                    </option>

                </select>

                <!-- MEDIO -->

                <label for="medio">
                    Medio de recuperación
                </label>

                <select
                    id="medio"
                    name="medio"
                    required
                >

                    <option value="">
                        Seleccione un medio
                    </option>

                    <option value="correo">
                        Correo electrónico
                    </option>

                    <option value="telefono">
                        Teléfono
                    </option>

                </select>

                <!-- BOTÓN -->

                <button
                    type="submit"
                    class="btn-recuperar"
                >
                    Continuar
                </button>

            </form>

        <?php endif; ?>

        <!-- VOLVER -->

        <?php if ($codigoPrueba === ""): ?>

            <a
                href="Login.php"
                class="enlace-login"
            >
                ← Volver al inicio de sesión
            </a>

        <?php endif; ?>

        <p class="nota">
            Acceso exclusivo para personal autorizado
            <br>
            YCL Solutions © 2026
        </p>

    </div>

</div>

<script>

/* CONTROL DEL CAMPO USUARIO */

const tipoRecuperacion =
    document.getElementById("tipo_recuperacion");

const campoUsuario =
    document.getElementById("usuario");

if (tipoRecuperacion && campoUsuario) {

    function actualizarUsuario() {

        if (tipoRecuperacion.value === "ambos") {

            campoUsuario.value = "";

            campoUsuario.disabled = true;

            campoUsuario.removeAttribute("required");

            campoUsuario.placeholder =
                "No es necesario para esta opción";

        } else {

            campoUsuario.disabled = false;

            campoUsuario.setAttribute(
                "required",
                "required"
            );

            campoUsuario.placeholder =
                "Ingrese su usuario";
        }
    }

    tipoRecuperacion.addEventListener(
        "change",
        actualizarUsuario
    );

    actualizarUsuario();
}

</script>

</body>
</html>