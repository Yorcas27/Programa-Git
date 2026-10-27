<?php
$titulo = "NUEVA RECETA";
$css = "Recetas.css";

include("../../includes/config.php");
include("../../includes/conexion.php");
include("../../includes/header.php");

$error = "";

/* GENERAR CÓDIGO */
$sqlCodigo = "SELECT MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)) AS ultimo
              FROM recetas
              WHERE codigo LIKE 'RC-%'";

$resultadoCodigo = $conexion->query($sqlCodigo);
$ultimo = 0;

if ($resultadoCodigo && $filaCodigo = $resultadoCodigo->fetch_assoc()) {
    $ultimo = (int)$filaCodigo['ultimo'];
}

$nuevoCodigo = "RC-" . str_pad($ultimo + 1, 4, "0", STR_PAD_LEFT);

/* PRODUCTOS */
$sqlProductos = "SELECT p.id_producto, p.nombre, cp.nombre AS categoria
                 FROM productos p
                 INNER JOIN categorias_producto cp
                    ON p.id_categoria_producto = cp.id_categoria_producto
                 WHERE p.estado = 'activo'
                 ORDER BY p.nombre ASC";

$resultadoProductos = $conexion->query($sqlProductos);

/* INSUMOS DISPONIBLES */
$sqlInsumos = "SELECT id_insumo, codigo, producto, unidad
               FROM insumos
               WHERE estado = 'Disponible'
               ORDER BY producto ASC";

$resultadoInsumos = $conexion->query($sqlInsumos);
$insumos = [];

if ($resultadoInsumos) {
    while ($insumo = $resultadoInsumos->fetch_assoc()) {
        $insumos[] = $insumo;
    }
}

/* RECUPERAR INSUMOS DEL FORMULARIO */
$filasFormulario = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idsFormulario = $_POST['id_insumo'] ?? [];
    $cantidadesFormulario = $_POST['cantidad'] ?? [];
    $unidadesFormulario = $_POST['unidad_medida'] ?? [];
    $observacionesFormulario = $_POST['observaciones_insumo'] ?? [];

    foreach ($idsFormulario as $indice => $idFormulario) {
        $filasFormulario[] = [
            'id_insumo' => (int)$idFormulario,
            'cantidad' => $cantidadesFormulario[$indice] ?? '',
            'unidad_medida' => $unidadesFormulario[$indice] ?? 'g',
            'observaciones' => $observacionesFormulario[$indice] ?? ''
        ];
    }
}

if (empty($filasFormulario)) {
    $filasFormulario[] = [
        'id_insumo' => 0,
        'cantidad' => '',
        'unidad_medida' => 'g',
        'observaciones' => ''
    ];
}

/* GUARDAR */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $id_producto = (int)($_POST['id_producto'] ?? 0);
    $rendimiento = (int)($_POST['rendimiento'] ?? 0);
    $peso_batido = (float)($_POST['peso_batido'] ?? 0);
    $unidad_peso = $_POST['unidad_peso'] ?? 'g';
    $molde = trim($_POST['molde'] ?? '');

    if ($molde === 'Pequeño') {
        $molde = 'Pequeno';
    }

    $tiempo_preparacion = (int)($_POST['tiempo_preparacion'] ?? 0);
    $temperatura_horno = (int)($_POST['temperatura_horno'] ?? 0);
    $tiempo_horneado = (int)($_POST['tiempo_horneado'] ?? 0);
    $estado = $_POST['estado'] ?? 'Activa';
    $descripcion = trim($_POST['descripcion'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');

    if (!in_array($unidad_peso, ['g', 'kg'], true)) {
        $unidad_peso = 'g';
    }

    /* OBTENER DATOS DEL PRODUCTO */
    $producto = '';
    $categoria = '';

    if ($id_producto > 0) {
        $stmtProducto = $conexion->prepare(
            "SELECT p.nombre, cp.nombre AS categoria
             FROM productos p
             INNER JOIN categorias_producto cp
                ON p.id_categoria_producto = cp.id_categoria_producto
             WHERE p.id_producto = ? AND p.estado = 'activo'"
        );

        if ($stmtProducto) {
            $stmtProducto->bind_param("i", $id_producto);
            $stmtProducto->execute();
            $resultadoProducto = $stmtProducto->get_result();

            if ($datosProducto = $resultadoProducto->fetch_assoc()) {
                $producto = $datosProducto['nombre'];
                $categoria = $datosProducto['categoria'];
            }

            $stmtProducto->close();
        }
    }

    /* VALIDACIONES GENERALES */
    if (
        $nombre === '' ||
        $id_producto <= 0 ||
        $producto === '' ||
        $rendimiento <= 0 ||
        $peso_batido <= 0 ||
        !in_array($molde, ['Pequeno', 'Mediano', 'Grande'], true) ||
        $tiempo_preparacion <= 0 ||
        $temperatura_horno <= 0 ||
        $tiempo_horneado <= 0 ||
        !in_array($estado, ['Activa', 'Inactiva'], true)
    ) {
        $error = "Verifique los campos obligatorios e ingrese valores válidos.";
    } else {
        /* VALIDAR INSUMOS */
        $idsInsumos = $_POST['id_insumo'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $unidades = $_POST['unidad_medida'] ?? [];
        $observacionesInsumos = $_POST['observaciones_insumo'] ?? [];

        $unidadesPermitidas = ['g', 'kg', 'ml', 'L', 'unidad'];
        $detallesInsumos = [];
        $hayInsumoIncompleto = false;
        $hayInsumoNoDisponible = false;

        foreach ($idsInsumos as $indice => $idInsumoFormulario) {
            $idInsumo = (int)$idInsumoFormulario;
            $cantidadTexto = trim((string)($cantidades[$indice] ?? ''));
            $unidadMedida = trim((string)($unidades[$indice] ?? ''));
            $observacionInsumo = trim((string)($observacionesInsumos[$indice] ?? ''));

            /* FILA COMPLETAMENTE VACÍA */
            if (
                $idInsumo <= 0 &&
                $cantidadTexto === '' &&
                $observacionInsumo === ''
            ) {
                continue;
            }

            /* VALIDAR CANTIDAD */
            if (
                $idInsumo <= 0 ||
                $cantidadTexto === '' ||
                !is_numeric($cantidadTexto) ||
                (float)$cantidadTexto <= 0 ||
                !in_array($unidadMedida, $unidadesPermitidas, true)
            ) {
                $hayInsumoIncompleto = true;
                break;
            }

            $detallesInsumos[] = [
                'id_insumo' => $idInsumo,
                'cantidad' => (float)$cantidadTexto,
                'unidad_medida' => $unidadMedida,
                'observaciones' => $observacionInsumo
            ];
        }

        if ($hayInsumoIncompleto) {
            $error = "Hay un insumo incompleto. Seleccione el insumo, indique una cantidad válida y confirme la unidad.";
        } elseif (empty($detallesInsumos)) {
            $error = "Debe agregar al menos un insumo con una cantidad válida.";
        } else {
            /* VALIDAR QUE LOS INSUMOS EXISTAN Y ESTÉN DISPONIBLES */
            $stmtValidar = $conexion->prepare(
                "SELECT id_insumo
                 FROM insumos
                 WHERE id_insumo = ? AND estado = 'Disponible'"
            );

            if (!$stmtValidar) {
                $error = "No fue posible validar los insumos seleccionados.";
            } else {
                foreach ($detallesInsumos as $detalle) {
                    $idValidar = $detalle['id_insumo'];

                    $stmtValidar->bind_param("i", $idValidar);
                    $stmtValidar->execute();
                    $resultadoValidacion = $stmtValidar->get_result();

                    if ($resultadoValidacion->num_rows === 0) {
                        $hayInsumoNoDisponible = true;
                        break;
                    }
                }

                $stmtValidar->close();

                if ($hayInsumoNoDisponible) {
                    $error = "Uno de los insumos seleccionados ya no está disponible. Revise la lista e inténtelo nuevamente.";
                }
            }
        }

        if ($error === '') {
            /* TRANSACCIÓN */
            $conexion->begin_transaction();

            try {
                /* GUARDAR RECETA */
                $stmt = $conexion->prepare(
                    "INSERT INTO recetas
                    (
                        codigo,
                        id_producto,
                        nombre,
                        producto,
                        categoria,
                        rendimiento,
                        peso_batido,
                        unidad_peso,
                        molde,
                        tiempo_preparacion,
                        temperatura_horno,
                        tiempo_horneado,
                        unidad,
                        tiempo,
                        estado,
                        descripcion,
                        observaciones
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                if (!$stmt) {
                    throw new Exception("No fue posible preparar el registro de la receta.");
                }

                $unidad = "Unidad";
                $tiempo = $tiempo_preparacion . " min preparación / " .
                          $tiempo_horneado . " min horneado";

                $stmt->bind_param(
                    "sisssidssiiisssss",
                    $nuevoCodigo,
                    $id_producto,
                    $nombre,
                    $producto,
                    $categoria,
                    $rendimiento,
                    $peso_batido,
                    $unidad_peso,
                    $molde,
                    $tiempo_preparacion,
                    $temperatura_horno,
                    $tiempo_horneado,
                    $unidad,
                    $tiempo,
                    $estado,
                    $descripcion,
                    $observaciones
                );

                if (!$stmt->execute()) {
                    throw new Exception("No fue posible guardar la receta: " . $stmt->error);
                }

                $id_receta = $conexion->insert_id;
                $stmt->close();

                /* GUARDAR INSUMOS */
                $stmtDetalle = $conexion->prepare(
                    "INSERT INTO detalle_receta
                    (
                        id_receta,
                        id_insumo,
                        cantidad,
                        unidad_medida,
                        observaciones
                    )
                    VALUES (?, ?, ?, ?, ?)"
                );

                if (!$stmtDetalle) {
                    throw new Exception("No fue posible preparar los insumos de la receta.");
                }

                foreach ($detallesInsumos as $detalle) {
                    $idInsumo = $detalle['id_insumo'];
                    $cantidad = $detalle['cantidad'];
                    $unidadMedida = $detalle['unidad_medida'];
                    $observacionInsumo = $detalle['observaciones'];

                    $stmtDetalle->bind_param(
                        "iidss",
                        $id_receta,
                        $idInsumo,
                        $cantidad,
                        $unidadMedida,
                        $observacionInsumo
                    );

                    if (!$stmtDetalle->execute()) {
                        throw new Exception(
                            "No fue posible guardar los insumos de la receta: " .
                            $stmtDetalle->error
                        );
                    }
                }

                $stmtDetalle->close();
                $conexion->commit();

                header("Location: Recetas.php");
                exit;
            } catch (Throwable $e) {
                $conexion->rollback();
                $error = $e->getMessage();
            }
        }
    }
}
?>

<main class="contenido">
    <section class="barra-superior">
        <div>
            <a href="Recetas.php" class="btn btn-verde">← Volver a Recetas</a>
            <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">⌂ Inicio</a>
        </div>
    </section>

    <header class="encabezado">
        <h1>Nueva Receta</h1>
        <p>Registre la información de la receta utilizada para la elaboración del producto.</p>
    </header>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Información de la Receta</h2>
                <p>Complete los datos de elaboración y los insumos utilizados.</p>
            </div>
        </div>

        <?php if ($error !== ''): ?>
            <div class="mensaje-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form class="formulario" method="POST" id="formularioReceta">
            <div class="grupo-formulario">
                <div class="campo">
                    <label for="codigo">Código</label>
                    <input type="text" id="codigo" name="codigo"
                           value="<?= htmlspecialchars($nuevoCodigo, ENT_QUOTES, 'UTF-8') ?>"
                           readonly>
                </div>

                <div class="campo">
                    <label for="nombre">Nombre de la Receta *</label>
                    <input type="text" id="nombre" name="nombre"
                           placeholder="Ej: Receta Torta de Chocolate"
                           required
                           value="<?= htmlspecialchars($_POST['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="campo">
                    <label for="id_producto">Producto Asociado *</label>
                    <select id="id_producto" name="id_producto" required>
                        <option value="">Seleccione un producto</option>
                        <?php if ($resultadoProductos): ?>
                            <?php while ($productoItem = $resultadoProductos->fetch_assoc()): ?>
                                <option value="<?= (int)$productoItem['id_producto'] ?>"
                                    <?= ((int)($_POST['id_producto'] ?? 0) === (int)$productoItem['id_producto']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($productoItem['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="rendimiento">Rendimiento de la Receta (unidades) *</label>
                    <input type="number" id="rendimiento" name="rendimiento"
                           min="1" step="1" placeholder="Ej: 3" required
                           value="<?= htmlspecialchars($_POST['rendimiento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="campo">
                    <label for="peso_batido">Peso Total del Batido *</label>
                    <div class="peso-con-unidad">
                        <input type="number" id="peso_batido" name="peso_batido"
                               min="0.001" step="0.001" placeholder="Ej: 2400" required
                               value="<?= htmlspecialchars($_POST['peso_batido'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <select name="unidad_peso">
                            <option value="g" <?= (($_POST['unidad_peso'] ?? 'g') === 'g') ? 'selected' : '' ?>>g</option>
                            <option value="kg" <?= (($_POST['unidad_peso'] ?? '') === 'kg') ? 'selected' : '' ?>>kg</option>
                        </select>
                    </div>
                </div>

                <div class="campo">
                    <label for="molde">Tamaño del Molde *</label>
                    <select id="molde" name="molde" required>
                        <option value="">Seleccione...</option>
                        <option value="Pequeño" <?= (($_POST['molde'] ?? '') === 'Pequeño') ? 'selected' : '' ?>>Pequeño</option>
                        <option value="Mediano" <?= (($_POST['molde'] ?? '') === 'Mediano') ? 'selected' : '' ?>>Mediano</option>
                        <option value="Grande" <?= (($_POST['molde'] ?? '') === 'Grande') ? 'selected' : '' ?>>Grande</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="tiempo_preparacion">Tiempo de Preparación del Batido *</label>
                    <div class="campo-con-unidad">
                        <input type="number" id="tiempo_preparacion" name="tiempo_preparacion"
                               min="1" step="1" placeholder="Minutos" required
                               value="<?= htmlspecialchars($_POST['tiempo_preparacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <span>min</span>
                    </div>
                </div>

                <div class="campo">
                    <label for="temperatura_horno">Temperatura del Horno *</label>
                    <div class="campo-con-unidad">
                        <input type="number" id="temperatura_horno" name="temperatura_horno"
                               min="1" step="1" placeholder="°C" required
                               value="<?= htmlspecialchars($_POST['temperatura_horno'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <span>°C</span>
                    </div>
                </div>

                <div class="campo">
                    <label for="tiempo_horneado">Tiempo de Horneado *</label>
                    <div class="campo-con-unidad">
                        <input type="number" id="tiempo_horneado" name="tiempo_horneado"
                               min="1" step="1" placeholder="Minutos" required
                               value="<?= htmlspecialchars($_POST['tiempo_horneado'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <span>min</span>
                    </div>
                </div>

                <div class="campo">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="Activa" <?= (($_POST['estado'] ?? 'Activa') === 'Activa') ? 'selected' : '' ?>>Activa</option>
                        <option value="Inactiva" <?= (($_POST['estado'] ?? '') === 'Inactiva') ? 'selected' : '' ?>>Inactiva</option>
                    </select>
                </div>

                <div class="campo campo-completo">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="4"
                              placeholder="Descripción de la receta..."><?= htmlspecialchars($_POST['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="campo campo-completo">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="4"
                              placeholder="Ingrese observaciones..."><?= htmlspecialchars($_POST['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <!-- INSUMOS -->
            <section class="panel-insumos">
                <div class="panel-header">
                    <div>
                        <h2>Insumos del Batido</h2>
                        <p>Agregue los insumos utilizados en la elaboración.</p>
                    </div>
                    <button type="button" class="btn btn-principal" onclick="agregarInsumo()">
                        + Agregar Insumo
                    </button>
                </div>

                <div class="tabla-insumos-contenedor">
                    <table class="tabla tabla-insumos" id="tablaInsumos">
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th>Cantidad</th>
                                <th>Unidad</th>
                                <th>Observaciones</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="insumosBody">
                            <?php foreach ($filasFormulario as $filaFormulario): ?>
                                <tr class="fila-insumo">
                                    <td>
                                        <select name="id_insumo[]">
                                            <option value="">Seleccione un insumo</option>
                                            <?php foreach ($insumos as $insumo): ?>
                                                <option value="<?= (int)$insumo['id_insumo'] ?>"
                                                    <?= ((int)$filaFormulario['id_insumo'] === (int)$insumo['id_insumo']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($insumo['producto'], ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="cantidad[]" min="0.001" step="0.001"
                                               placeholder="Cantidad"
                                               value="<?= htmlspecialchars((string)$filaFormulario['cantidad'], ENT_QUOTES, 'UTF-8') ?>">
                                    </td>
                                    <td>
                                        <select name="unidad_medida[]">
                                            <?php
                                            $unidadFila = $filaFormulario['unidad_medida'];
                                            $unidadesDisponibles = ['g', 'kg', 'ml', 'L', 'unidad'];
                                            ?>
                                            <?php foreach ($unidadesDisponibles as $opcionUnidad): ?>
                                                <option value="<?= $opcionUnidad ?>"
                                                    <?= ($unidadFila === $opcionUnidad) ? 'selected' : '' ?>>
                                                    <?= $opcionUnidad ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="observaciones_insumo[]"
                                               placeholder="Opcional"
                                               value="<?= htmlspecialchars($filaFormulario['observaciones'], ENT_QUOTES, 'UTF-8') ?>">
                                    </td>
                                    <td>
                                        <button type="button" class="btn-eliminar-insumo" onclick="eliminarInsumo(this)">
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- BOTONES -->
            <div class="botones-formulario">
                <button type="submit" class="btn btn-principal">Guardar</button>
                <button type="reset" class="btn btn-limpiar" id="btnLimpiar">Limpiar</button>
                <a href="Recetas.php" class="btn btn-cancelar" id="btnCancelar">Cancelar</a>
            </div>
        </form>
    </section>
</main>

<script>
/* AGREGAR INSUMO */
function agregarInsumo() {
    const tbody = document.getElementById("insumosBody");
    const filaOriginal = tbody.querySelector(".fila-insumo");
    const nuevaFila = filaOriginal.cloneNode(true);

    nuevaFila.querySelectorAll("input").forEach(function(input) {
        input.value = "";
    });

    nuevaFila.querySelectorAll("select").forEach(function(select) {
        select.selectedIndex = 0;
    });

    tbody.appendChild(nuevaFila);
}

/* ELIMINAR INSUMO */
function eliminarInsumo(boton) {
    const filas = document.querySelectorAll("#insumosBody .fila-insumo");
    const fila = boton.closest(".fila-insumo");

    if (filas.length <= 1) {
        fila.querySelector('select[name="id_insumo[]"]').selectedIndex = 0;
        fila.querySelector('select[name="unidad_medida[]"]').selectedIndex = 0;
        fila.querySelectorAll("input").forEach(function(input) {
            input.value = "";
        });
        return;
    }

    fila.remove();
}

/* VALIDAR INSUMOS ANTES DE GUARDAR */
document.getElementById("formularioReceta").addEventListener("submit", function(event) {
    const filas = document.querySelectorAll("#insumosBody .fila-insumo");
    let hayInsumo = false;

    for (const fila of filas) {
        const insumo = fila.querySelector('[name="id_insumo[]"]').value;
        const cantidad = fila.querySelector('[name="cantidad[]"]').value.trim();
        const observacion = fila.querySelector('[name="observaciones_insumo[]"]').value.trim();

        if (insumo === "" && cantidad === "" && observacion === "") {
            continue;
        }

        if (insumo === "" || cantidad === "" || !Number.isFinite(Number(cantidad)) || Number(cantidad) <= 0) {
            event.preventDefault();
            alert("Hay un insumo incompleto.\n\nSeleccione el insumo e indique una cantidad válida.");
            return;
        }

        hayInsumo = true;
    }

    if (!hayInsumo) {
        event.preventDefault();
        alert("Debe agregar al menos un insumo con una cantidad válida.");
    }
});

/* CONFIRMAR CANCELACIÓN */
document.getElementById("btnCancelar").addEventListener("click", function(event) {
    const formulario = document.getElementById("formularioReceta");
    const campos = formulario.querySelectorAll("input:not([readonly]), select, textarea");
    let hayCambios = false;

    campos.forEach(function(campo) {
        if (campo.value !== campo.defaultValue) {
            hayCambios = true;
        }
    });

    const filas = document.querySelectorAll("#insumosBody .fila-insumo");

    if (filas.length > 1) {
        hayCambios = true;
    }

    if (hayCambios) {
        const confirmar = confirm("¿Desea salir sin guardar la receta?");

        if (!confirmar) {
            event.preventDefault();
        }
    }
});

/* LIMPIAR FORMULARIO */
document.getElementById("btnLimpiar").addEventListener("click", function(event) {
    const confirmar = confirm("¿Desea limpiar todos los campos de la receta?");

    if (!confirmar) {
        event.preventDefault();
        return;
    }

    const tbody = document.getElementById("insumosBody");
    const filas = tbody.querySelectorAll(".fila-insumo");

    filas.forEach(function(fila, indice) {
        if (indice > 0) {
            fila.remove();
        }
    });

    const primeraFila = tbody.querySelector(".fila-insumo");

    if (primeraFila) {
        primeraFila.querySelectorAll("input").forEach(function(input) {
            input.value = "";
        });

        primeraFila.querySelectorAll("select").forEach(function(select) {
            select.selectedIndex = 0;
        });
    }
});
</script>

<?php
include("../../includes/footer.php");
?>