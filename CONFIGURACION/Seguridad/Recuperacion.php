<?php

/* SEGURIDAD */

include("../../includes/sesion.php");

/* CONFIGURACIÓN */

$titulo = "Recuperación de Acceso";
$css = "Seguridad.css";

include("../../includes/config.php");
include("../../includes/conexion.php");
include("../../includes/header.php");

/* DATOS DEL USUARIO */

$idUsuario = $_SESSION["id_usuario"] ?? 0;

$error = "";

/* GUARDAR DATOS */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $correo = trim($_POST["correo_recuperacion"] ?? "");
    $telefono = trim($_POST["telefono_recuperacion"] ?? "");

    /* VALIDAR DATOS */

    if ($correo !== "" && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $error = "Ingrese un correo electrónico válido.";

    } elseif ($correo === "" && $telefono === "") {

        $error = "Debe registrar al menos un medio de recuperación.";

    } else {

        $sql = "UPDATE usuarios
                SET correo_recuperacion = ?,
                    telefono_recuperacion = ?
                WHERE id_usuario = ?";

        $stmt = $conexion->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "ssi",
                $correo,
                $telefono,
                $idUsuario
            );

            if ($stmt->execute()) {

                $stmt->close();

                /* VOLVER A SEGURIDAD */

                header("Location: Seguridad.php?guardado=1");
                exit;

            } else {

                $error = "No fue posible actualizar los datos de recuperación.";
            }

            $stmt->close();

        } else {

            $error = "No fue posible preparar la actualización.";
        }
    }
}

/* CONSULTAR DATOS ACTUALES */

$sqlDatos = "SELECT correo_recuperacion, telefono_recuperacion
             FROM usuarios
             WHERE id_usuario = ?";

$stmtDatos = $conexion->prepare($sqlDatos);

$correoRecuperacion = "";
$telefonoRecuperacion = "";

if ($stmtDatos) {

    $stmtDatos->bind_param("i", $idUsuario);
    $stmtDatos->execute();

    $resultado = $stmtDatos->get_result();

    if ($fila = $resultado->fetch_assoc()) {

        $correoRecuperacion = $fila["correo_recuperacion"] ?? "";
        $telefonoRecuperacion = $fila["telefono_recuperacion"] ?? "";
    }

    $stmtDatos->close();
}

?>

<main class="contenido">

    <!-- BARRA SUPERIOR -->

    <div class="barra-superior">

        <a href="Seguridad.php" class="btn-volver">
            ← Volver a Seguridad
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </div>

    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>Recuperación de Acceso</h1>

        <p>
            Configure los medios que se utilizarán para recuperar el acceso al sistema.
        </p>

    </header>

    <!-- MENSAJE DE ERROR -->

    <?php if ($error !== ""): ?>

        <div
            style="
                width:100%;
                max-width:1100px;
                margin:0 auto 25px auto;
                padding:16px 20px;
                background:#fdecea;
                border:1px solid #f5c6cb;
                border-left:5px solid #e74c3c;
                border-radius:10px;
                color:#c0392b;
                font-size:14px;
                text-align:center;
                box-shadow:0 3px 10px rgba(0,0,0,.06);
            "
        >

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>

    <!-- INFORMACIÓN -->

    <section class="panel">

        <div class="panel-header">

            <h2>Datos de recuperación</h2>

        </div>

        <div class="formulario-seguridad">

            <p class="texto-ayuda">
                Registre al menos un medio de recuperación. Estos datos serán utilizados
                para verificar su identidad si olvida el usuario o la contraseña.
            </p>

            <form method="POST">

                <div class="grupo-formulario">

                    <div class="campo">

                        <label for="correo_recuperacion">
                            Correo de recuperación
                        </label>

                        <input
                            type="email"
                            id="correo_recuperacion"
                            name="correo_recuperacion"
                            value="<?= htmlspecialchars($correoRecuperacion) ?>"
                            placeholder="correo@ejemplo.com"
                            maxlength="150"
                        >

                    </div>

                    <div class="campo">

                        <label for="telefono_recuperacion">
                            Teléfono de recuperación
                        </label>

                        <input
                            type="tel"
                            id="telefono_recuperacion"
                            name="telefono_recuperacion"
                            value="<?= htmlspecialchars($telefonoRecuperacion) ?>"
                            placeholder="3001234567"
                            maxlength="30"
                        >

                    </div>

                </div>

                <div class="acciones-formulario">

                    <button
                        type="submit"
                        class="btn-guardar"
                    >
                        Guardar
                    </button>

                </div>

            </form>

        </div>

    </section>

</main>

<?php

include("../../includes/footer.php");

?>