<?php

session_start();

include("../includes/conexion.php");

$error = "";

/* CONFIGURACIÓN DE SEGURIDAD */

$maxIntentos = 5;
$minutosBloqueo = 15;

/* PROCESAR LOGIN */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario = trim($_POST["usuario"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";

    if ($usuario === "" || $contrasena === "") {

        $error = "Ingrese el usuario y la contraseña.";

    } else {

        /* BUSCAR USUARIO */

        $sql = "SELECT
                    id_usuario,
                    usuario,
                    contrasena,
                    nombre,
                    estado,
                    intentos_fallidos,
                    bloqueado_hasta
                FROM usuarios
                WHERE usuario = ?
                LIMIT 1";

        $stmt = $conexion->prepare($sql);

        if ($stmt) {

            $stmt->bind_param("s", $usuario);
            $stmt->execute();

            $resultado = $stmt->get_result();

            if ($resultado->num_rows === 1) {

                $usuarioDB = $resultado->fetch_assoc();

                $idUsuario = (int)$usuarioDB["id_usuario"];
                $intentosFallidos = (int)$usuarioDB["intentos_fallidos"];
                $bloqueadoHasta = $usuarioDB["bloqueado_hasta"];

                /* COMPROBAR BLOQUEO */

                if ($bloqueadoHasta !== null) {

                    $momentoActual = time();
                    $momentoBloqueo = strtotime($bloqueadoHasta);

                    if ($momentoActual < $momentoBloqueo) {

                        $minutosRestantes = ceil(
                            ($momentoBloqueo - $momentoActual) / 60
                        );

                        $error = "El acceso está bloqueado temporalmente. Intente nuevamente en aproximadamente " . $minutosRestantes . " minuto(s).";

                    } else {

                        /* DESBLOQUEAR */

                        $sqlDesbloquear = "UPDATE usuarios
                                           SET intentos_fallidos = 0,
                                               bloqueado_hasta = NULL
                                           WHERE id_usuario = ?";

                        $stmtDesbloquear = $conexion->prepare($sqlDesbloquear);

                        if ($stmtDesbloquear) {

                            $stmtDesbloquear->bind_param("i", $idUsuario);
                            $stmtDesbloquear->execute();
                            $stmtDesbloquear->close();
                        }

                        $intentosFallidos = 0;
                        $bloqueadoHasta = null;
                    }
                }

                /* CONTINUAR SI NO ESTÁ BLOQUEADO */

                if ($error === "") {

                    if ($usuarioDB["estado"] !== "Activo") {

                        $error = "Usuario o contraseña incorrectos.";

                    } elseif (password_verify($contrasena, $usuarioDB["contrasena"])) {

                        /* LOGIN CORRECTO */

                        $sqlReset = "UPDATE usuarios
                                     SET intentos_fallidos = 0,
                                         bloqueado_hasta = NULL
                                     WHERE id_usuario = ?";

                        $stmtReset = $conexion->prepare($sqlReset);

                        if ($stmtReset) {

                            $stmtReset->bind_param("i", $idUsuario);
                            $stmtReset->execute();
                            $stmtReset->close();
                        }

                        session_regenerate_id(true);

                        $_SESSION["id_usuario"] = $usuarioDB["id_usuario"];
                        $_SESSION["usuario"] = $usuarioDB["usuario"];
                        $_SESSION["nombre"] = $usuarioDB["nombre"];
                        $_SESSION["ultima_actividad"] = time();

                        header("Location: /YCL_Solutions/DASHBOARD/Dashboard.php");
                        exit;

                    } else {

                        /* LOGIN INCORRECTO */

                        $intentosFallidos++;

                        if ($intentosFallidos >= $maxIntentos) {

                            $bloqueadoHastaNuevo = date(
                                "Y-m-d H:i:s",
                                time() + ($minutosBloqueo * 60)
                            );

                            $sqlBloqueo = "UPDATE usuarios
                                           SET intentos_fallidos = ?,
                                               bloqueado_hasta = ?
                                           WHERE id_usuario = ?";

                            $stmtBloqueo = $conexion->prepare($sqlBloqueo);

                            if ($stmtBloqueo) {

                                $stmtBloqueo->bind_param(
                                    "isi",
                                    $intentosFallidos,
                                    $bloqueadoHastaNuevo,
                                    $idUsuario
                                );

                                $stmtBloqueo->execute();
                                $stmtBloqueo->close();
                            }

                            $error = "Ha superado el número máximo de intentos permitidos. El acceso ha sido bloqueado temporalmente durante " . $minutosBloqueo . " minutos.";

                        } else {

                            $sqlIntento = "UPDATE usuarios
                                           SET intentos_fallidos = ?
                                           WHERE id_usuario = ?";

                            $stmtIntento = $conexion->prepare($sqlIntento);

                            if ($stmtIntento) {

                                $stmtIntento->bind_param(
                                    "ii",
                                    $intentosFallidos,
                                    $idUsuario
                                );

                                $stmtIntento->execute();
                                $stmtIntento->close();
                            }

                            $intentosRestantes = $maxIntentos - $intentosFallidos;

                            $error = "Usuario o contraseña incorrectos.";

                            if ($intentosRestantes === 1) {

                                $error .= " Le queda 1 intento antes del bloqueo temporal.";

                            } else {

                                $error .= " Le quedan " . $intentosRestantes . " intentos.";
                            }
                        }
                    }
                }

            } else {

                /* USUARIO NO ENCONTRADO */

                $error = "Usuario o contraseña incorrectos.";
            }

            $stmt->close();

        } else {

            $error = "No fue posible procesar el inicio de sesión.";
        }
    }
}

/* TÍTULO Y ESTILO */

$titulo = "Login";
$estilo = "Login";
$css = "Login.css";

include("../includes/header.php");

?>

<div class="login-container">

    <div class="login-card">

        <div class="logo-placeholder">

            <img src="../IMG/pastelera.jpg" alt="Pastelera">

        </div>

        <h2>YCL Solutions</h2>

        <p class="subtitulo">
            Plataforma interna de gestión
        </p>

        <p class="bienvenida">
            Bienvenido al sistema
        </p>

        <?php if ($error !== ""): ?>

            <div class="mensaje-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="Login.php">

            <label for="usuario">
                Usuario
            </label>

            <input
                type="text"
                id="usuario"
                name="usuario"
                placeholder="Ingrese usuario"
                value="<?= htmlspecialchars($_POST["usuario"] ?? "") ?>"
                autocomplete="username"
                required
            >

            <label for="contrasena">
                Contraseña
            </label>

            <input
                type="password"
                id="contrasena"
                name="contrasena"
                placeholder="Ingrese contraseña"
                autocomplete="current-password"
                required
            >

            <button
                type="submit"
                class="btn-login"
            >
                Ingresar
            </button>

        </form>

        <!-- RECUPERACIÓN DE ACCESO -->

        <a
            href="RecuperarAcceso.php"
            class="enlace-recuperacion"
        >
            ¿Olvidó su usuario o contraseña?
        </a>

        <p class="nota">
            Acceso exclusivo para personal autorizado
            <br>
            YCL Solutions © 2026
        </p>

    </div>

</div>

</body>
</html>