<?php

/* CONFIGURACIÓN */

session_start();

$titulo = "Verificar Código";
$css = "RecuperarAcceso.css";

include("../includes/config.php");
include("../includes/conexion.php");

/* VERIFICAR PROCESO DE RECUPERACIÓN */

if (
    !isset($_SESSION["recuperacion_id_usuario"]) ||
    !isset($_SESSION["recuperacion_tipo"]) ||
    !isset($_SESSION["recuperacion_medio"])
) {
    header("Location: RecuperarAcceso.php");
    exit;
}

/* DATOS DE RECUPERACIÓN */

$idUsuario = (int) $_SESSION["recuperacion_id_usuario"];
$tipoRecuperacion = $_SESSION["recuperacion_tipo"];
$medio = $_SESSION["recuperacion_medio"];

$error = "";

/* PROCESAR CÓDIGO */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $codigo = trim($_POST["codigo"] ?? "");

    /* VALIDAR FORMATO */

    if (!preg_match('/^\d{6}$/', $codigo)) {

        $error = "Ingrese un código válido de 6 dígitos.";

    } else {

        /* BUSCAR CÓDIGO ACTIVO */

        $sql = "SELECT
                    id_recuperacion,
                    codigo_hash,
                    fecha_expiracion,
                    intentos,
                    utilizado
                FROM recuperacion_acceso
                WHERE id_usuario = ?
                AND medio = ?
                AND utilizado = 0
                ORDER BY id_recuperacion DESC
                LIMIT 1";

        $stmt = $conexion->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "is",
                $idUsuario,
                ucfirst($medio)
            );

            $stmt->execute();

            $resultado = $stmt->get_result();

            $recuperacion = $resultado->fetch_assoc();

            $stmt->close();

            /* COMPROBAR EXISTENCIA */

            if (!$recuperacion) {

                $error = "El código no es válido o ya fue utilizado.";

            } else {

                /* COMPROBAR INTENTOS */

                if ((int) $recuperacion["intentos"] >= 5) {

                    $error = "Se alcanzó el máximo de intentos. Solicite un nuevo código.";

                    $idRecuperacion = (int) $recuperacion["id_recuperacion"];

                    $stmt = $conexion->prepare(
                        "UPDATE recuperacion_acceso
                         SET utilizado = 1
                         WHERE id_recuperacion = ?"
                    );

                    $stmt->bind_param("i", $idRecuperacion);
                    $stmt->execute();
                    $stmt->close();

                } else {

                    /* COMPROBAR EXPIRACIÓN */

                    if (
                        strtotime($recuperacion["fecha_expiracion"]) < time()
                    ) {

                        $error = "El código ha expirado. Solicite uno nuevo.";

                        $idRecuperacion = (int) $recuperacion["id_recuperacion"];

                        $stmt = $conexion->prepare(
                            "UPDATE recuperacion_acceso
                             SET utilizado = 1
                             WHERE id_recuperacion = ?"
                        );

                        $stmt->bind_param("i", $idRecuperacion);
                        $stmt->execute();
                        $stmt->close();

                    } else {

                        /* VERIFICAR CÓDIGO */

                        if (
                            password_verify(
                                $codigo,
                                $recuperacion["codigo_hash"]
                            )
                        ) {

                            /* MARCAR CÓDIGO COMO UTILIZADO */

                            $idRecuperacion = (int) $recuperacion["id_recuperacion"];

                            $stmt = $conexion->prepare(
                                "UPDATE recuperacion_acceso
                                 SET utilizado = 1
                                 WHERE id_recuperacion = ?"
                            );

                            $stmt->bind_param("i", $idRecuperacion);
                            $stmt->execute();
                            $stmt->close();

                            /* GUARDAR ID VERIFICADO */

                            $_SESSION["recuperacion_verificada"] = true;
                            $_SESSION["recuperacion_id"] = $idRecuperacion;

                            /* CONTINUAR */

                            header("Location: NuevaContrasena.php");
                            exit;

                        } else {

                            /* AUMENTAR INTENTOS */

                            $idRecuperacion = (int) $recuperacion["id_recuperacion"];

                            $stmt = $conexion->prepare(
                                "UPDATE recuperacion_acceso
                                 SET intentos = intentos + 1
                                 WHERE id_recuperacion = ?"
                            );

                            $stmt->bind_param("i", $idRecuperacion);
                            $stmt->execute();
                            $stmt->close();

                            $intentosRestantes =
                                4 - (int) $recuperacion["intentos"];

                            if ($intentosRestantes <= 0) {

                                $stmt = $conexion->prepare(
                                    "UPDATE recuperacion_acceso
                                     SET utilizado = 1
                                     WHERE id_recuperacion = ?"
                                );

                                $stmt->bind_param(
                                    "i",
                                    $idRecuperacion
                                );

                                $stmt->execute();
                                $stmt->close();

                                $error =
                                    "Se alcanzó el máximo de intentos. Solicite un nuevo código.";

                            } else {

                                $error =
                                    "El código ingresado es incorrecto. "
                                    . "Intentos restantes: "
                                    . $intentosRestantes
                                    . ".";
                            }
                        }
                    }
                }
            }

        } else {

            $error = "No fue posible verificar el código.";
        }
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
            Verificar código
        </h2>

        <p class="subtitulo">
            Confirme el código de recuperación
        </p>

        <p class="bienvenida">
            Ingrese el código de 6 dígitos que recibió
            mediante el medio seleccionado.
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

        <!-- FORMULARIO -->

        <form
            method="POST"
            action="VerificarCodigo.php"
        >

            <label for="codigo">
                Código de recuperación
            </label>

            <input
                type="text"
                id="codigo"
                name="codigo"
                placeholder="Ingrese los 6 dígitos"
                maxlength="6"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
                autofocus
            >

            <button
                type="submit"
                class="btn-recuperar"
            >
                Verificar código
            </button>

        </form>

        <!-- VOLVER -->

        <a
            href="RecuperarAcceso.php"
            class="enlace-login"
        >
            ← Solicitar un nuevo código
        </a>

        <p class="nota">
            El código es temporal y de un solo uso.
            <br>
            YCL Solutions © 2026
        </p>

    </div>

</div>

</body>
</html>