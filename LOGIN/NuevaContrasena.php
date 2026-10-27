<?php

/* CONFIGURACIÓN */

session_start();

$titulo = "Nueva Contraseña";
$css = "RecuperarAcceso.css";

include("../includes/config.php");
include("../includes/conexion.php");

/* VERIFICAR RECUPERACIÓN */

if (
    !isset($_SESSION["recuperacion_id_usuario"]) ||
    !isset($_SESSION["recuperacion_verificada"]) ||
    $_SESSION["recuperacion_verificada"] !== true
) {
    header("Location: RecuperarAcceso.php");
    exit;
}

/* DATOS DE RECUPERACIÓN */

$idUsuario = (int) $_SESSION["recuperacion_id_usuario"];
$tipoRecuperacion = $_SESSION["recuperacion_tipo"] ?? "contrasena";

$error = "";
$exito = "";
$usuarioRecuperado = "";

/* OBTENER USUARIO */

$sqlUsuario = "SELECT usuario
               FROM usuarios
               WHERE id_usuario = ?
               LIMIT 1";

$stmtUsuario = $conexion->prepare($sqlUsuario);

if ($stmtUsuario) {

    $stmtUsuario->bind_param("i", $idUsuario);
    $stmtUsuario->execute();

    $resultadoUsuario = $stmtUsuario->get_result();
    $datosUsuario = $resultadoUsuario->fetch_assoc();

    $stmtUsuario->close();

    if ($datosUsuario) {
        $usuarioRecuperado = $datosUsuario["usuario"];
    }
}

/* PROCESAR CAMBIO DE CONTRASEÑA */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    (
        $tipoRecuperacion === "contrasena" ||
        $tipoRecuperacion === "ambos"
    )
) {

    $nuevaContrasena = $_POST["nueva_contrasena"] ?? "";
    $confirmarContrasena = $_POST["confirmar_contrasena"] ?? "";

    /* VALIDAR CONTRASEÑA */

    if ($nuevaContrasena === "" || $confirmarContrasena === "") {

        $error = "Complete todos los campos.";

    } elseif (strlen($nuevaContrasena) < 8) {

        $error = "La contraseña debe tener mínimo 8 caracteres.";

    } elseif ($nuevaContrasena !== $confirmarContrasena) {

        $error = "Las contraseñas no coinciden.";

    } else {

        /* ACTUALIZAR CONTRASEÑA */

        $hashContrasena = password_hash(
            $nuevaContrasena,
            PASSWORD_DEFAULT
        );

        $sqlActualizar = "UPDATE usuarios
                          SET contrasena = ?,
                              intentos_fallidos = 0,
                              bloqueado_hasta = NULL
                          WHERE id_usuario = ?";

        $stmtActualizar = $conexion->prepare($sqlActualizar);

        if ($stmtActualizar) {

            $stmtActualizar->bind_param(
                "si",
                $hashContrasena,
                $idUsuario
            );

            if ($stmtActualizar->execute()) {

                /* CONFIRMACIÓN */

                $exito = "Contraseña actualizada correctamente.";

                /* GUARDAR DATOS ANTES DE LIMPIAR SESIÓN */

                $usuarioFinal = $usuarioRecuperado;

                /* LIMPIAR RECUPERACIÓN */

                unset($_SESSION["recuperacion_id_usuario"]);
                unset($_SESSION["recuperacion_tipo"]);
                unset($_SESSION["recuperacion_medio"]);
                unset($_SESSION["recuperacion_destino"]);
                unset($_SESSION["recuperacion_codigo_prueba"]);
                unset($_SESSION["recuperacion_verificada"]);
                unset($_SESSION["recuperacion_id"]);

            } else {

                $error = "No fue posible actualizar la contraseña.";
            }

            $stmtActualizar->close();

        } else {

            $error = "No fue posible procesar la solicitud.";
        }
    }
}

/* RECUPERACIÓN SOLAMENTE DE USUARIO */

if (
    $tipoRecuperacion === "usuario" &&
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    if ($usuarioRecuperado !== "") {

        $exito = "Usuario recuperado correctamente.";

        /* LIMPIAR RECUPERACIÓN */

        unset($_SESSION["recuperacion_id_usuario"]);
        unset($_SESSION["recuperacion_tipo"]);
        unset($_SESSION["recuperacion_medio"]);
        unset($_SESSION["recuperacion_destino"]);
        unset($_SESSION["recuperacion_codigo_prueba"]);
        unset($_SESSION["recuperacion_verificada"]);
        unset($_SESSION["recuperacion_id"]);

    } else {

        $error = "No fue posible recuperar el usuario.";
    }
}

/* MOSTRAR INTERFAZ */

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
            Recuperación de acceso
        </h2>

        <p class="subtitulo">
            YCL Solutions
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

        <!-- RECUPERACIÓN DE USUARIO -->

        <?php if (
            $tipoRecuperacion === "usuario" &&
            $exito !== ""
        ): ?>

            <p class="bienvenida">
                Su usuario de acceso es:
            </p>

            <div
                style="
                    margin-bottom:20px;
                    padding:14px;
                    background:#eaf7ee;
                    border:1px solid #b7dfc2;
                    border-radius:8px;
                    color:#1e7e34;
                    font-size:16px;
                    font-weight:600;
                    text-align:center;
                "
            >
                <?= htmlspecialchars($usuarioRecuperado) ?>
            </div>

            <p class="bienvenida">
                Ahora puede utilizar este usuario para iniciar sesión.
            </p>

            <a
                href="Login.php"
                class="btn-recuperar"
                style="
                    display:block;
                    text-decoration:none;
                    text-align:center;
                "
            >
                Ir al inicio de sesión
            </a>

        <!-- RECUPERACIÓN DE CONTRASEÑA -->

        <?php elseif (
            $tipoRecuperacion === "contrasena"
        ): ?>

            <?php if ($exito === ""): ?>

                <p class="bienvenida">
                    Ingrese una nueva contraseña para recuperar
                    el acceso a su cuenta.
                </p>

                <form
                    method="POST"
                    action="NuevaContrasena.php"
                >

                    <label for="nueva_contrasena">
                        Nueva contraseña
                    </label>

                    <input
                        type="password"
                        id="nueva_contrasena"
                        name="nueva_contrasena"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <label for="confirmar_contrasena">
                        Confirmar contraseña
                    </label>

                    <input
                        type="password"
                        id="confirmar_contrasena"
                        name="confirmar_contrasena"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="submit"
                        class="btn-recuperar"
                    >
                        Actualizar contraseña
                    </button>

                </form>

            <?php else: ?>

                <p class="bienvenida">
                    <?= htmlspecialchars($exito) ?>
                </p>

                <div
                    style="
                        margin-bottom:20px;
                        padding:14px;
                        background:#eaf7ee;
                        border:1px solid #b7dfc2;
                        border-radius:8px;
                        color:#1e7e34;
                        font-size:14px;
                        text-align:center;
                    "
                >
                    Ya puede iniciar sesión con su nueva contraseña.
                </div>

                <a
                    href="Login.php"
                    class="btn-recuperar"
                    style="
                        display:block;
                        text-decoration:none;
                        text-align:center;
                    "
                >
                    Ir al inicio de sesión
                </a>

            <?php endif; ?>

        <!-- RECUPERACIÓN DE USUARIO Y CONTRASEÑA -->

        <?php elseif (
            $tipoRecuperacion === "ambos"
        ): ?>

            <?php if ($exito === ""): ?>

                <p class="bienvenida">
                    Su usuario de acceso es:
                </p>

                <div
                    style="
                        margin-bottom:20px;
                        padding:14px;
                        background:#eaf7ee;
                        border:1px solid #b7dfc2;
                        border-radius:8px;
                        color:#1e7e34;
                        font-size:16px;
                        font-weight:600;
                        text-align:center;
                    "
                >
                    <?= htmlspecialchars($usuarioRecuperado) ?>
                </div>

                <p class="bienvenida">
                    Ahora establezca una nueva contraseña para
                    completar la recuperación de acceso.
                </p>

                <form
                    method="POST"
                    action="NuevaContrasena.php"
                >

                    <label for="nueva_contrasena">
                        Nueva contraseña
                    </label>

                    <input
                        type="password"
                        id="nueva_contrasena"
                        name="nueva_contrasena"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <label for="confirmar_contrasena">
                        Confirmar contraseña
                    </label>

                    <input
                        type="password"
                        id="confirmar_contrasena"
                        name="confirmar_contrasena"
                        minlength="8"
                        autocomplete="new-password"
                        required
                    >

                    <button
                        type="submit"
                        class="btn-recuperar"
                    >
                        Actualizar contraseña
                    </button>

                </form>

            <?php else: ?>

                <p class="bienvenida">
                    Recuperación de acceso completada correctamente.
                </p>

                <div
                    style="
                        margin-bottom:12px;
                        padding:14px;
                        background:#eaf7ee;
                        border:1px solid #b7dfc2;
                        border-radius:8px;
                        color:#1e7e34;
                        font-size:14px;
                        text-align:center;
                    "
                >
                    Usuario recuperado:
                    <strong>
                        <?= htmlspecialchars($usuarioFinal ?? $usuarioRecuperado) ?>
                    </strong>
                </div>

                <div
                    style="
                        margin-bottom:20px;
                        padding:14px;
                        background:#eaf7ee;
                        border:1px solid #b7dfc2;
                        border-radius:8px;
                        color:#1e7e34;
                        font-size:14px;
                        text-align:center;
                    "
                >
                    Contraseña actualizada correctamente.
                </div>

                <a
                    href="Login.php"
                    class="btn-recuperar"
                    style="
                        display:block;
                        text-decoration:none;
                        text-align:center;
                    "
                >
                    Ir al inicio de sesión
                </a>

            <?php endif; ?>

        <?php endif; ?>

        <!-- VOLVER -->

        <?php if ($exito === "" && $tipoRecuperacion !== "ambos"): ?>

            <a
                href="RecuperarAcceso.php"
                class="enlace-login"
            >
                ← Volver a recuperación
            </a>

        <?php endif; ?>

        <p class="nota">
            La recuperación es temporal y de un solo uso.
            <br>
            YCL Solutions © 2026
        </p>

    </div>

</div>

</body>
</html>