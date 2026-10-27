<?php

/* SEGURIDAD */

include("../../includes/sesion.php");

/* CONFIGURACIÓN */

$titulo = "Datos de la Empresa";
$css = "Empresa.css";

include("../../includes/config.php");
require_once("../../includes/conexion.php");

/* DATOS INICIALES */

$empresa = [
    'id_empresa' => 0,
    'nombre' => '',
    'nit' => '',
    'descripcion' => '',
    'direccion' => '',
    'ciudad' => '',
    'telefono' => '',
    'correo' => '',
    'logo' => 'IMG/pastelera.jpg'
];

$mensaje = '';
$tipo_mensaje = '';

$modo_edicion = false;
$verificacion_seguridad = false;

/* CARGAR DATOS DE LA EMPRESA */

$sqlEmpresa = "SELECT
                    id_empresa,
                    nombre,
                    nit,
                    descripcion,
                    direccion,
                    ciudad,
                    telefono,
                    correo,
                    logo
               FROM empresa
               ORDER BY id_empresa ASC
               LIMIT 1";

$resultadoEmpresa = $conexion->query($sqlEmpresa);

if ($resultadoEmpresa && $resultadoEmpresa->num_rows > 0) {

    $empresaBD = $resultadoEmpresa->fetch_assoc();

    foreach ($empresaBD as $campo => $valor) {

        if ($valor !== null) {
            $empresa[$campo] = $valor;
        }
    }

    if (empty($empresa['logo'])) {
        $empresa['logo'] = 'IMG/pastelera.jpg';
    }
}

/* SOLICITAR EDICIÓN */

if (isset($_POST['editar'])) {

    $verificacion_seguridad = true;
}

/* VERIFICAR CONTRASEÑA */

if (isset($_POST['verificar'])) {

    $contrasena = $_POST['contrasena_seguridad'] ?? '';

    if ($contrasena === '') {

        $mensaje = "Ingrese su contraseña para continuar.";
        $tipo_mensaje = "error";
        $verificacion_seguridad = true;

    } else {

        $idUsuario = $_SESSION["id_usuario"];

        $sqlUsuario = "SELECT contrasena
                       FROM usuarios
                       WHERE id_usuario = ?
                       LIMIT 1";

        $stmtUsuario = $conexion->prepare($sqlUsuario);

        if ($stmtUsuario) {

            $stmtUsuario->bind_param("i", $idUsuario);
            $stmtUsuario->execute();

            $resultadoUsuario = $stmtUsuario->get_result();

            if ($resultadoUsuario->num_rows === 1) {

                $usuarioBD = $resultadoUsuario->fetch_assoc();

                if (password_verify($contrasena, $usuarioBD['contrasena'])) {

                    $modo_edicion = true;
                    $verificacion_seguridad = false;

                } else {

                    $mensaje = "Contraseña incorrecta. No se permite modificar los datos.";
                    $tipo_mensaje = "error";
                    $verificacion_seguridad = true;
                }

            } else {

                $mensaje = "No fue posible verificar el usuario.";
                $tipo_mensaje = "error";
                $verificacion_seguridad = true;
            }

            $stmtUsuario->close();

        } else {

            $mensaje = "No fue posible realizar la verificación de seguridad.";
            $tipo_mensaje = "error";
            $verificacion_seguridad = true;
        }
    }
}

/* CANCELAR EDICIÓN */

if (isset($_POST['cancelar'])) {

    $modo_edicion = false;
    $verificacion_seguridad = false;

    $resultadoEmpresa = $conexion->query($sqlEmpresa);

    if ($resultadoEmpresa && $resultadoEmpresa->num_rows > 0) {

        $empresa = $resultadoEmpresa->fetch_assoc();

        if (empty($empresa['logo'])) {
            $empresa['logo'] = 'IMG/pastelera.jpg';
        }
    }
}

/* GUARDAR INFORMACIÓN */

if (isset($_POST['guardar'])) {

    $nombre = trim($_POST['nombre'] ?? '');
    $nit = trim($_POST['nit'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    /* VALIDACIÓN */

    if ($nombre === '') {

        $mensaje = "El nombre de la empresa es obligatorio.";
        $tipo_mensaje = "error";
        $modo_edicion = true;

    } elseif ($nit === '') {

        $mensaje = "El NIT es obligatorio.";
        $tipo_mensaje = "error";
        $modo_edicion = true;

    } elseif ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $mensaje = "El correo electrónico no es válido.";
        $tipo_mensaje = "error";
        $modo_edicion = true;

    } else {

        /* ACTUALIZAR EMPRESA */

        if ((int)$empresa['id_empresa'] > 0) {

            $sql = "UPDATE empresa SET
                        nombre = ?,
                        nit = ?,
                        descripcion = ?,
                        direccion = ?,
                        ciudad = ?,
                        telefono = ?,
                        correo = ?
                    WHERE id_empresa = ?";

            $stmt = $conexion->prepare($sql);

            $stmt->bind_param(
                "sssssssi",
                $nombre,
                $nit,
                $descripcion,
                $direccion,
                $ciudad,
                $telefono,
                $correo,
                $empresa['id_empresa']
            );

        } else {

            /* CREAR REGISTRO */

            $logo = 'IMG/pastelera.jpg';

            $sql = "INSERT INTO empresa
                        (
                            nombre,
                            nit,
                            descripcion,
                            direccion,
                            ciudad,
                            telefono,
                            correo,
                            logo
                        )
                    VALUES
                        (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conexion->prepare($sql);

            $stmt->bind_param(
                "ssssssss",
                $nombre,
                $nit,
                $descripcion,
                $direccion,
                $ciudad,
                $telefono,
                $correo,
                $logo
            );
        }

        if ($stmt->execute()) {

            $mensaje = "Los datos de la empresa se guardaron correctamente.";
            $tipo_mensaje = "exito";
            $modo_edicion = false;
            $verificacion_seguridad = false;

            /* RECARGAR DATOS */

            $resultadoEmpresa = $conexion->query($sqlEmpresa);

            if ($resultadoEmpresa && $resultadoEmpresa->num_rows > 0) {

                $empresa = $resultadoEmpresa->fetch_assoc();

                if (empty($empresa['logo'])) {
                    $empresa['logo'] = 'IMG/pastelera.jpg';
                }
            }

        } else {

            $mensaje = "No fue posible guardar los datos de la empresa.";
            $tipo_mensaje = "error";
            $modo_edicion = true;
        }

        $stmt->close();
    }
}

include("../../includes/header.php");

?>

<main class="contenido">

    <!-- BARRA SUPERIOR -->

    <section class="barra-superior">

        <a href="../Configuracion/Configuracion.php" class="btn-volver">
            ← Volver a Configuración
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </section>

    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>Datos de la Empresa</h1>

        <p>
            Administre la información general de la empresa
            utilizada en facturas, reportes y documentos.
        </p>

    </header>

    <!-- MENSAJES -->

    <?php if ($mensaje !== ''): ?>

        <div class="mensaje <?= htmlspecialchars($tipo_mensaje) ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>

    <?php endif; ?>

    <?php if ($verificacion_seguridad): ?>

        <!-- VERIFICACIÓN DE SEGURIDAD -->

        <section class="formulario-configuracion">

            <div class="seccion-formulario">

                <h2>Verificación de Seguridad</h2>

                <p>
                    Para modificar los datos de la empresa,
                    confirme su contraseña actual.
                </p>

                <form action="" method="POST">

                    <div class="campo">

                        <label for="contrasena_seguridad">
                            Contraseña actual
                        </label>

                        <input
                            type="password"
                            id="contrasena_seguridad"
                            name="contrasena_seguridad"
                            placeholder="Ingrese su contraseña"
                            autocomplete="current-password"
                            required
                        >

                        <a
                            href="/YCL_Solutions/LOGIN/RecuperarAcceso.php"
                            style="
                                display:block;
                                margin-top:8px;
                                color:#1f3f6d;
                                font-size:13px;
                                text-decoration:none;
                            "
                        >
                            ¿Olvidó su contraseña? Recuperar contraseña
                        </a>

                    </div>

                    <div class="acciones-formulario">

                        <button
                            type="submit"
                            name="verificar"
                            class="btn-guardar"
                        >
                            Verificar
                        </button>

                        <button
                            type="submit"
                            name="cancelar"
                            class="btn-cancelar"
                        >
                            Cancelar
                        </button>

                    </div>

                </form>

            </div>

        </section>

    <?php else: ?>

        <!-- FORMULARIO -->

        <section class="formulario-configuracion">

            <form action="" method="POST">

                <!-- INFORMACIÓN GENERAL -->

                <div class="seccion-formulario">

                    <h2>Información General</h2>

                    <div class="grupo-formulario">

                        <div class="campo">

                            <label for="nombre">
                                Nombre de la Empresa
                            </label>

                            <input
                                type="text"
                                id="nombre"
                                name="nombre"
                                value="<?= htmlspecialchars($empresa['nombre']) ?>"
                                placeholder="Nombre de la empresa"
                                <?= !$modo_edicion ? 'readonly' : '' ?>
                                required
                            >

                        </div>

                        <div class="campo">

                            <label for="nit">
                                NIT
                            </label>

                            <input
                                type="text"
                                id="nit"
                                name="nit"
                                value="<?= htmlspecialchars($empresa['nit']) ?>"
                                placeholder="Número de identificación tributaria"
                                <?= !$modo_edicion ? 'readonly' : '' ?>
                                required
                            >

                        </div>

                    </div>

                    <div class="campo">

                        <label for="descripcion">
                            Descripción
                        </label>

                        <textarea
                            id="descripcion"
                            name="descripcion"
                            rows="3"
                            placeholder="Descripción de la empresa"
                            <?= !$modo_edicion ? 'readonly' : '' ?>
                        ><?= htmlspecialchars($empresa['descripcion']) ?></textarea>

                    </div>

                </div>

                <!-- INFORMACIÓN DE CONTACTO -->

                <div class="seccion-formulario">

                    <h2>Información de Contacto</h2>

                    <div class="grupo-formulario">

                        <div class="campo">

                            <label for="direccion">
                                Dirección
                            </label>

                            <input
                                type="text"
                                id="direccion"
                                name="direccion"
                                value="<?= htmlspecialchars($empresa['direccion']) ?>"
                                placeholder="Dirección"
                                <?= !$modo_edicion ? 'readonly' : '' ?>
                            >

                        </div>

                        <div class="campo">

                            <label for="ciudad">
                                Ciudad
                            </label>

                            <input
                                type="text"
                                id="ciudad"
                                name="ciudad"
                                value="<?= htmlspecialchars($empresa['ciudad']) ?>"
                                placeholder="Ciudad"
                                <?= !$modo_edicion ? 'readonly' : '' ?>
                            >

                        </div>

                    </div>

                    <div class="grupo-formulario">

                        <div class="campo">

                            <label for="telefono">
                                Teléfono
                            </label>

                            <input
                                type="tel"
                                id="telefono"
                                name="telefono"
                                value="<?= htmlspecialchars($empresa['telefono']) ?>"
                                placeholder="Teléfono"
                                <?= !$modo_edicion ? 'readonly' : '' ?>
                            >

                        </div>

                        <div class="campo">

                            <label for="correo">
                                Correo Electrónico
                            </label>

                            <input
                                type="email"
                                id="correo"
                                name="correo"
                                value="<?= htmlspecialchars($empresa['correo']) ?>"
                                placeholder="correo@empresa.com"
                                <?= !$modo_edicion ? 'readonly' : '' ?>
                            >

                        </div>

                    </div>

                </div>

                <!-- LOGO -->

                <div class="seccion-formulario">

                    <h2>Logo de la Empresa</h2>

                    <div class="logo-empresa">

                        <div class="logo-vista">

                            <?php
                            $rutaLogo = "../../" . ltrim($empresa['logo'], "/");
                            ?>

                            <?php if (!empty($empresa['logo']) && file_exists($rutaLogo)): ?>

                                <img
                                    src="<?= htmlspecialchars($rutaLogo) ?>"
                                    alt="Logo de la empresa"
                                >

                            <?php else: ?>

                                <div class="sin-logo">
                                    Sin logo
                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="logo-info">

                            <strong>Logo oficial de la empresa</strong>

                            <span>
                                Este logo será utilizado en facturas,
                                reportes y documentos del sistema.
                            </span>

                        </div>

                    </div>

                </div>

                <!-- ACCIONES -->

                <div class="acciones-formulario">

                    <?php if (!$modo_edicion): ?>

                        <button
                            type="submit"
                            name="editar"
                            class="btn-editar"
                        >
                            Editar información
                        </button>

                    <?php else: ?>

                        <button
                            type="submit"
                            name="guardar"
                            class="btn-guardar"
                            onclick="return confirm('¿Está seguro de que desea actualizar los datos de la empresa?');"
                        >
                            Guardar Cambios
                        </button>

                        <button
                            type="submit"
                            name="cancelar"
                            class="btn-cancelar"
                        >
                            Cancelar
                        </button>

                    <?php endif; ?>

                </div>

            </form>

        </section>

    <?php endif; ?>

</main>

<?php include("../../includes/footer.php"); ?>