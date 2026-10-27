
<?php
include("../../includes/sesion.php");
require_once("../../includes/conexion.php");

$id = (int)($_GET["id"] ?? 0);
if ($id < 0) {
    header("Location: Productos.php");
    exit;
}

$operacion = $id > 0 ? "editar:" . $id : "crear";
$errores = [];
$mensaje_seguridad = "";

if (empty($_SESSION["csrf_producto"])) {
    $_SESSION["csrf_producto"] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION["csrf_producto"];

/* VERIFICAR CONTRASEÑA */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "verificar") {
    if (!hash_equals($csrf, $_POST["csrf"] ?? "")) {
        $mensaje_seguridad = "La solicitud no es válida. Inténtelo nuevamente.";
    } else {
        $contrasena = $_POST["contrasena"] ?? "";
        $idUsuario = (int)($_SESSION["id_usuario"] ?? 0);
        $stmt = $conexion->prepare("SELECT contrasena FROM usuarios WHERE id_usuario = ? LIMIT 1");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($usuario && password_verify($contrasena, $usuario["contrasena"])) {
            $_SESSION["autorizacion_producto"] = [
                "operacion" => $operacion,
                "token" => bin2hex(random_bytes(32)),
                "vence" => time() + 900
            ];
            header("Location: ProductoForm.php" . ($id ? "?id=" . $id : ""));
            exit;
        }

        $mensaje_seguridad = "La contraseña ingresada es incorrecta. Inténtelo nuevamente.";
    }
}

/* AUTORIZACIÓN */
$autorizacion = $_SESSION["autorizacion_producto"] ?? null;
$autorizado = is_array($autorizacion)
    && ($autorizacion["operacion"] ?? "") === $operacion
    && ($autorizacion["vence"] ?? 0) >= time()
    && !empty($autorizacion["token"]);

if (!$autorizado) {
    unset($_SESSION["autorizacion_producto"]);
    $titulo = "Verificación de seguridad";
    $css = "Productos.css";
    include("../../includes/header.php");
    ?>
    <main class="contenido">
        <section class="barra-superior">
            <a href="Productos.php" class="btn-volver">← Volver a Productos</a>
        </section>
        <header class="encabezado">
            <h1>Verificación de seguridad</h1>
            <p><?= $id ? "Ingrese su contraseña actual para autorizar la edición del producto." : "Ingrese su contraseña actual para autorizar el registro del producto." ?></p>
        </header>

        <?php if ($mensaje_seguridad !== ""): ?>
            <div class="mensaje-error"><?= htmlspecialchars($mensaje_seguridad) ?></div>
        <?php endif; ?>

        <section class="formulario-contenedor">
            <form method="POST" action="<?= $id ? "?id=" . $id : "" ?>">
                <input type="hidden" name="accion" value="verificar">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

                <div class="seccion-formulario">
                    <h2>Confirmar identidad</h2>
                    <div class="campo-formulario">
                        <label for="contrasena">Contraseña actual</label>
                        <input type="password" id="contrasena" name="contrasena" autocomplete="current-password" required autofocus>
                    </div>
                </div>

                <div class="acciones-formulario">
                    <a href="Productos.php" class="btn-cancelar">Cancelar</a>
                    <button type="submit" class="btn-principal">Verificar contraseña</button>
                </div>
            </form>
        </section>
    </main>
    <?php
    include("../../includes/footer.php");
    exit;
}

/* DATOS INICIALES */
$editar = false;
$idPrincipal = 0;
$nombre = "";
$categoria = "";
$descripcion = "";
$imagen_actual = "";
$variantes = [];
$idsOriginales = [];

if ($id > 0) {
    $stmt = $conexion->prepare("
        SELECT p.id_producto, p.nombre, p.id_categoria_producto,
               p.descripcion, p.imagen, p.id_producto_principal,
               pp.nombre AS nombre_principal,
               pp.id_categoria_producto AS categoria_principal,
               pp.descripcion AS descripcion_principal,
               pp.imagen AS imagen_principal
        FROM productos p
        LEFT JOIN productos_principales pp
            ON pp.id_producto_principal = p.id_producto_principal
        WHERE p.id_producto = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$producto) {
        unset($_SESSION["autorizacion_producto"]);
        header("Location: Productos.php?mensaje=" . urlencode("No se encontró el producto solicitado."));
        exit;
    }

    $editar = true;
    $idPrincipal = (int)($producto["id_producto_principal"] ?? 0);
    $nombre = $producto["nombre_principal"] ?? $producto["nombre"];
    $categoria = $producto["categoria_principal"] ?? $producto["id_categoria_producto"];
    $descripcion = $producto["descripcion_principal"] ?? ($producto["descripcion"] ?? "");
    $imagen_actual = $producto["imagen_principal"] ?? ($producto["imagen"] ?? "");

    if ($idPrincipal > 0) {
        $stmt = $conexion->prepare("
            SELECT id_producto, presentacion, precio, estado
            FROM productos
            WHERE id_producto_principal = ?
            ORDER BY id_producto
        ");
        $stmt->bind_param("i", $idPrincipal);
    } else {
        $stmt = $conexion->prepare("
            SELECT id_producto, presentacion, precio, estado
            FROM productos
            WHERE id_producto = ?
        ");
        $stmt->bind_param("i", $id);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {
        $variantes[] = $fila;
        $idsOriginales[] = (int)$fila["id_producto"];
    }

    $stmt->close();
}

if (!$variantes) {
    $variantes = [[
        "id_producto" => 0,
        "presentacion" => "Única",
        "precio" => "",
        "estado" => "activo"
    ]];
}

/* GUARDAR */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["accion"] ?? "") === "guardar") {
    $tokenFormulario = $_POST["token_operacion"] ?? "";
    $autorizacion = $_SESSION["autorizacion_producto"] ?? null;

    $autorizadoGuardar = is_array($autorizacion)
        && ($autorizacion["operacion"] ?? "") === $operacion
        && ($autorizacion["vence"] ?? 0) >= time()
        && !empty($autorizacion["token"])
        && hash_equals($autorizacion["token"], $tokenFormulario);

    if (!$autorizadoGuardar) {
        unset($_SESSION["autorizacion_producto"]);
        header("Location: ProductoForm.php" . ($id ? "?id=" . $id : ""));
        exit;
    }

    $nombre = trim($_POST["nombre"] ?? "");
    $categoria = (int)($_POST["categoria"] ?? 0);
    $descripcion = trim($_POST["descripcion"] ?? "");
    $imagen = $imagen_actual;
    $variantes = [];
    $presentaciones = $_POST["presentaciones"] ?? [];

    if (!is_array($presentaciones)) {
        $presentaciones = [];
        $errores[] = "Las presentaciones enviadas no son válidas.";
    }

    foreach ($presentaciones as $fila) {
        if (!is_array($fila)) {
            $errores[] = "Se recibió una presentación con datos inválidos.";
            continue;
        }

        $precioTexto = trim((string)($fila["precio"] ?? ""));
        $precioTexto = str_replace(".", "", $precioTexto);
        $precioTexto = str_replace(",", ".", $precioTexto);

        $variantes[] = [
            "id_producto" => (int)($fila["id_producto"] ?? 0),
            "presentacion" => trim($fila["presentacion"] ?? ""),
            "precio" => $precioTexto !== "" && is_numeric($precioTexto) ? (float)$precioTexto : -1,
            "estado" => $fila["estado"] ?? "activo"
        ];
    }

    if ($nombre === "") {
        $errores[] = "El nombre del producto es obligatorio.";
    } elseif (mb_strlen($nombre) > 150) {
        $errores[] = "El nombre no puede superar 150 caracteres.";
    }

    if ($categoria <= 0) {
        $errores[] = "Debe seleccionar una categoría.";
    } else {
        $stmt = $conexion->prepare("
            SELECT id_categoria_producto
            FROM categorias_producto
            WHERE id_categoria_producto = ? AND estado = 'Activa'
        ");
        $stmt->bind_param("i", $categoria);
        $stmt->execute();
        $valida = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (!$valida) {
            $errores[] = "La categoría seleccionada no está disponible.";
        }
    }

    if (!$variantes) {
        $errores[] = "Agregue al menos una presentación.";
    }

    /* VALIDAR PRESENTACIONES */
    $presentacionesUnicas = [];

    foreach ($variantes as $i => $variante) {
        $numero = $i + 1;
        $presentacion = $variante["presentacion"];

        if ($presentacion === "") {
            $errores[] = "La presentación {$numero} es obligatoria.";
        } elseif (mb_strlen($presentacion) > 50) {
            $errores[] = "La presentación {$numero} supera los 50 caracteres.";
        }

        $clave = mb_strtolower($presentacion);

        if ($clave !== "" && in_array($clave, $presentacionesUnicas, true)) {
            $errores[] = "No repita el nombre de una presentación.";
        }

        if ($clave !== "") {
            $presentacionesUnicas[] = $clave;
        }

        if ($variante["precio"] < 0) {
            $errores[] = "Ingrese un precio válido para la presentación {$numero}.";
        }

        if (!in_array($variante["estado"], ["activo", "inactivo"], true)) {
            $errores[] = "El estado de la presentación {$numero} no es válido.";
        }

        if ($variante["id_producto"] < 0) {
            $errores[] = "Se encontró un identificador de presentación inválido.";
        }
    }

    /* PROTEGER TODAS LAS PRESENTACIONES EXISTENTES */
    if ($editar) {
        $idsEnviados = [];

        foreach ($variantes as $variante) {
            $idVariante = (int)$variante["id_producto"];

            if ($idVariante > 0) {
                if (in_array($idVariante, $idsEnviados, true)) {
                    $errores[] = "Una presentación existente fue enviada más de una vez.";
                    break;
                }

                $idsEnviados[] = $idVariante;
            }
        }

        foreach ($idsOriginales as $idOriginal) {
            if (!in_array($idOriginal, $idsEnviados, true)) {
                $errores[] = "No se pueden omitir presentaciones existentes. Verifique todas las presentaciones antes de guardar.";
                break;
            }
        }

        foreach ($idsEnviados as $idEnviado) {
            if (!in_array($idEnviado, $idsOriginales, true)) {
                $errores[] = "Una presentación enviada no pertenece al producto que está editando.";
                break;
            }
        }
    }

    /* VALIDAR IMAGEN */
    if (empty($errores) && isset($_FILES["imagen"]) && $_FILES["imagen"]["error"] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES["imagen"]["error"] !== UPLOAD_ERR_OK) {
            $errores[] = "No se pudo cargar la imagen.";
        } else {
            $extension = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
            $permitidas = ["jpg", "jpeg", "png", "webp"];

            if (!in_array($extension, $permitidas, true)) {
                $errores[] = "La imagen debe estar en formato JPG, JPEG, PNG o WEBP.";
            } elseif (@getimagesize($_FILES["imagen"]["tmp_name"]) === false) {
                $errores[] = "El archivo seleccionado no es una imagen válida.";
            } else {
                $carpeta = "../../uploads/productos/";

                if (!is_dir($carpeta) && !mkdir($carpeta, 0775, true) && !is_dir($carpeta)) {
                    $errores[] = "No se pudo preparar la carpeta de imágenes.";
                } else {
                    $nombreImagen = bin2hex(random_bytes(16)) . "." . $extension;

                    if (move_uploaded_file($_FILES["imagen"]["tmp_name"], $carpeta . $nombreImagen)) {
                        $imagen = "uploads/productos/" . $nombreImagen;
                    } else {
                        $errores[] = "No se pudo guardar la imagen en el servidor.";
                    }
                }
            }
        }
    }

    /* GUARDAR PRODUCTO PRINCIPAL Y PRESENTACIONES */
    if (empty($errores)) {
        $conexion->begin_transaction();

        try {
            $estadoPrincipal = "inactivo";

            foreach ($variantes as $variante) {
                if ($variante["estado"] === "activo") {
                    $estadoPrincipal = "activo";
                    break;
                }
            }

            if ($idPrincipal > 0) {
                $stmt = $conexion->prepare("
                    UPDATE productos_principales
                    SET nombre = ?, id_categoria_producto = ?,
                        descripcion = ?, imagen = ?, estado = ?
                    WHERE id_producto_principal = ?
                ");
                $stmt->bind_param(
                    "sisssi",
                    $nombre,
                    $categoria,
                    $descripcion,
                    $imagen,
                    $estadoPrincipal,
                    $idPrincipal
                );
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conexion->prepare("
                    INSERT INTO productos_principales
                        (nombre, id_categoria_producto, descripcion, imagen, estado)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "sisss",
                    $nombre,
                    $categoria,
                    $descripcion,
                    $imagen,
                    $estadoPrincipal
                );
                $stmt->execute();
                $idPrincipal = $conexion->insert_id;
                $stmt->close();
            }

            foreach ($variantes as $variante) {
                $idVariante = (int)$variante["id_producto"];
                $presentacion = $variante["presentacion"];
                $precio = $variante["precio"];
                $estadoVariante = $variante["estado"];

                if ($idVariante > 0) {
                    $stmt = $conexion->prepare("
                        UPDATE productos
                        SET nombre = ?, presentacion = ?,
                            id_categoria_producto = ?, descripcion = ?,
                            precio = ?, estado = ?, imagen = ?,
                            id_producto_principal = ?
                        WHERE id_producto = ?
                    ");
                    $stmt->bind_param(
                        "ssisdssii",
                        $nombre,
                        $presentacion,
                        $categoria,
                        $descripcion,
                        $precio,
                        $estadoVariante,
                        $imagen,
                        $idPrincipal,
                        $idVariante
                    );
                } else {
                    $stmt = $conexion->prepare("
                        INSERT INTO productos
                            (nombre, presentacion, id_categoria_producto,
                             descripcion, precio, estado, imagen,
                             id_producto_principal)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->bind_param(
                        "ssisdssi",
                        $nombre,
                        $presentacion,
                        $categoria,
                        $descripcion,
                        $precio,
                        $estadoVariante,
                        $imagen,
                        $idPrincipal
                    );
                }

                $stmt->execute();
                $stmt->close();
            }

            $conexion->commit();
            unset($_SESSION["autorizacion_producto"]);

            header(
                "Location: Productos.php?mensaje=" .
                urlencode($editar
                    ? "Producto y presentaciones actualizados correctamente."
                    : "Producto y presentaciones registrados correctamente.")
            );
            exit;
        } catch (Throwable $e) {
            $conexion->rollback();
            $errores[] = "No se pudo guardar el producto. Verifique los datos e inténtelo nuevamente.";
        }
    }
}

/* CATEGORÍAS */
$categorias = $conexion->query("
    SELECT id_categoria_producto, nombre
    FROM categorias_producto
    WHERE estado = 'Activa'
    ORDER BY nombre
");

$titulo = $editar ? "Editar Producto" : "Nuevo Producto";
$css = "Productos.css";
include("../../includes/header.php");
?>

<main class="contenido">
    <section class="barra-superior">
        <a href="Productos.php" class="btn-volver">← Volver a Productos</a>
    </section>

    <header class="encabezado">
        <h1><?= $editar ? "Editar Producto" : "Nuevo Producto" ?></h1>
        <p><?= $editar ? "Modifique los datos y las presentaciones del producto." : "Registre un producto con una o varias presentaciones y precios." ?></p>
    </header>

    <?php if ($errores): ?>
        <div class="mensaje-error">
            <?php foreach (array_unique($errores) as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="formulario-contenedor">
        <form action="<?= $editar ? "?id=" . $id : "" ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="token_operacion" value="<?= htmlspecialchars($_SESSION["autorizacion_producto"]["token"]) ?>">

            <div class="seccion-formulario">
                <h2>Información del Producto</h2>

                <div class="campo-formulario">
                    <label for="nombre">Nombre del producto</label>
                    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre) ?>" placeholder="Ej. Torta de chocolate" maxlength="150" required>
                </div>

                <div class="campo-formulario">
                    <label for="categoria">Categoría</label>
                    <select id="categoria" name="categoria" required>
                        <option value="">Seleccione una categoría</option>
                        <?php if ($categorias): ?>
                            <?php while ($cat = $categorias->fetch_assoc()): ?>
                                <option value="<?= (int)$cat["id_categoria_producto"] ?>" <?= (string)$categoria === (string)$cat["id_categoria_producto"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($cat["nombre"]) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="campo-formulario">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="4" placeholder="Descripción del producto..."><?= htmlspecialchars($descripcion) ?></textarea>
                </div>
            </div>

            <div class="seccion-formulario">
                <h2>Presentaciones y Precios</h2>
                <p>Agregue las presentaciones disponibles. Cada una tendrá su propio precio y estado.</p>

                <div id="listaPresentaciones">
                    <?php foreach ($variantes as $i => $variante): ?>
                        <div class="fila-formulario fila-presentacion">
                            <input type="hidden" name="presentaciones[<?= $i ?>][id_producto]" value="<?= (int)$variante["id_producto"] ?>">

                            <div class="campo-formulario">
                                <label>Presentación</label>
                                <input type="text" name="presentaciones[<?= $i ?>][presentacion]" value="<?= htmlspecialchars($variante["presentacion"]) ?>" maxlength="50" placeholder="Ej. Pequeña" list="presentacionesSugeridas" required>
                            </div>

                            <div class="campo-formulario">
                                <label>Precio (COP)</label>
                                <input type="text" class="precio-presentacion" name="presentaciones[<?= $i ?>][precio]" value="<?= $variante["precio"] !== "" ? number_format((float)$variante["precio"], 0, ',', '.') : "" ?>" placeholder="Ej. 28.000" inputmode="numeric" required>
                            </div>

                            <div class="campo-formulario">
                                <label>Estado</label>
                                <select name="presentaciones[<?= $i ?>][estado]" required>
                                    <option value="activo" <?= $variante["estado"] === "activo" ? "selected" : "" ?>>Activo</option>
                                    <option value="inactivo" <?= $variante["estado"] === "inactivo" ? "selected" : "" ?>>Inactivo</option>
                                </select>
                            </div>

                            <?php if ((int)$variante["id_producto"] === 0): ?>
                                <button type="button" class="btn-cancelar quitar-presentacion">Quitar</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <datalist id="presentacionesSugeridas">
                    <option value="Pequeña">
                    <option value="Mediana">
                    <option value="Grande">
                    <option value="Individual">
                    <option value="Media">
                    <option value="Familiar">
                    <option value="Única">
                </datalist>

                <button type="button" id="agregarPresentacion" class="btn-principal">+ Agregar presentación</button>
            </div>

            <div class="seccion-formulario">
                <h2>Imagen del Producto</h2>
                <div class="campo-formulario">
                    <label for="imagen">Imagen compartida</label>
                    <input type="file" id="imagen" name="imagen" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <?php if ($imagen_actual !== ""): ?>
                        <p>Imagen actual: <?= htmlspecialchars($imagen_actual) ?></p>
                    <?php endif; ?>
                    <small>Formatos permitidos: JPG, JPEG, PNG o WEBP. Todas las presentaciones compartirán esta imagen.</small>
                </div>
            </div>

            <div class="acciones-formulario">
                <a href="Productos.php" class="btn-cancelar">Cancelar</a>
                <button type="submit" class="btn-principal"><?= $editar ? "Actualizar Producto" : "Guardar Producto" ?></button>
            </div>
        </form>
    </section>
</main>

<script>
const lista = document.getElementById("listaPresentaciones");
let indicePresentacion = <?= count($variantes) ?>;

function prepararPrecio(campo) {
    campo.addEventListener("input", function () {
        const valor = this.value.replace(/\D/g, "");
        this.value = valor ? Number(valor).toLocaleString("es-CO") : "";
    });
}

document.querySelectorAll(".precio-presentacion").forEach(prepararPrecio);

document.getElementById("agregarPresentacion").addEventListener("click", function () {
    const fila = document.createElement("div");
    fila.className = "fila-formulario fila-presentacion";
    fila.innerHTML = `
        <input type="hidden" name="presentaciones[${indicePresentacion}][id_producto]" value="0">
        <div class="campo-formulario">
            <label>Presentación</label>
            <input type="text" name="presentaciones[${indicePresentacion}][presentacion]" maxlength="50" placeholder="Ej. Grande" list="presentacionesSugeridas" required>
        </div>
        <div class="campo-formulario">
            <label>Precio (COP)</label>
            <input type="text" class="precio-presentacion" name="presentaciones[${indicePresentacion}][precio]" placeholder="Ej. 28.000" inputmode="numeric" required>
        </div>
        <div class="campo-formulario">
            <label>Estado</label>
            <select name="presentaciones[${indicePresentacion}][estado]" required>
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
        </div>
        <button type="button" class="btn-cancelar quitar-presentacion">Quitar</button>
    `;
    lista.appendChild(fila);
    prepararPrecio(fila.querySelector(".precio-presentacion"));
    indicePresentacion++;
});

lista.addEventListener("click", function (e) {
    if (e.target.classList.contains("quitar-presentacion")) {
        e.target.closest(".fila-presentacion").remove();
    }
});
</script>

<?php include("../../includes/footer.php"); ?>

