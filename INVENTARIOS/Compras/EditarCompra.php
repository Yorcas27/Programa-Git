<?php
$titulo = "Editar Compra";
$css = "Compras.css";

require_once "../../includes/conexion.php";

$id_compra = (int)($_GET['id'] ?? $_POST['id_compra'] ?? 0);

if ($id_compra <= 0) {
    header("Location: Compras.php");
    exit;
}

$stmt = $conexion->prepare("SELECT * FROM compras WHERE id_compra = ?");
$stmt->bind_param("i", $id_compra);
$stmt->execute();
$compra = $stmt->get_result()->fetch_assoc();

if (!$compra) {
    header("Location: Compras.php");
    exit;
}

$proveedores = $conexion->query(
    "SELECT id_proveedor, nombre
     FROM proveedores
     WHERE estado = 'Activo'
     ORDER BY nombre ASC"
);

$insumos = $conexion->query(
    "SELECT id_insumo, producto, unidad
     FROM insumos
     WHERE estado = 'Disponible'
     ORDER BY producto ASC"
);

$mensaje = "";

/* PROCESAR ACTUALIZACIÓN */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_proveedor = $_POST['tipo_proveedor'] ?? 'Registrado';
    $id_proveedor = (int)($_POST['id_proveedor'] ?? 0);
    $nombre_proveedor_casual = trim($_POST['nombre_proveedor_casual'] ?? '');
    $documento_proveedor_casual = trim($_POST['documento_proveedor_casual'] ?? '');
    $tipo_compra = $_POST['tipo_compra'] ?? 'Insumos';
    $fecha = $_POST['fecha'] ?? '';
    $estado = $_POST['estado'] ?? 'Pendiente';
    $observaciones = trim($_POST['observaciones'] ?? '');

    $detallesNuevos = [];
    $subtotal = 0;

    if (!in_array($tipo_proveedor, ['Registrado', 'Casual'], true)) {
        $mensaje = "El tipo de proveedor no es válido.";
    } elseif ($tipo_proveedor === 'Registrado' && $id_proveedor <= 0) {
        $mensaje = "Seleccione un proveedor registrado.";
    } elseif ($tipo_proveedor === 'Casual' && $nombre_proveedor_casual === '') {
        $mensaje = "Ingrese el nombre del proveedor casual.";
    } elseif (!in_array($tipo_compra, ['Insumos', 'Otros'], true)) {
        $mensaje = "El tipo de compra no es válido.";
    } elseif (!in_array($estado, ['Pendiente', 'Recibida'], true)) {
        $mensaje = "El estado de la compra no es válido.";
    } elseif ($fecha === '') {
        $mensaje = "Seleccione una fecha.";
    }

    /* VALIDAR PROVEEDOR REGISTRADO */

    if ($mensaje === '' && $tipo_proveedor === 'Registrado') {
        $stmtProveedor = $conexion->prepare(
            "SELECT id_proveedor
             FROM proveedores
             WHERE id_proveedor = ? AND estado = 'Activo'"
        );
        $stmtProveedor->bind_param("i", $id_proveedor);
        $stmtProveedor->execute();

        if (!$stmtProveedor->get_result()->fetch_assoc()) {
            $mensaje = "El proveedor seleccionado no existe o está inactivo.";
        }
    }

    /* DETALLE DE INSUMOS */

    if ($mensaje === '' && $tipo_compra === 'Insumos') {
        $ids_insumo = $_POST['id_insumo'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $precios = $_POST['precio'] ?? [];

        if (!is_array($ids_insumo) || !is_array($cantidades) || !is_array($precios)) {
            $mensaje = "Los detalles de la compra no son válidos.";
        } else {
            for ($i = 0; $i < count($ids_insumo); $i++) {
                $id_insumo = (int)($ids_insumo[$i] ?? 0);
                $cantidad = (float)($cantidades[$i] ?? 0);
                $precioTexto = (string)($precios[$i] ?? '0');
                $precioTexto = str_replace('.', '', $precioTexto);
                $precio = (float)$precioTexto;

                if ($id_insumo > 0 && $cantidad > 0 && $precio >= 0) {
                    $sub = $cantidad * $precio;
                    $subtotal += $sub;

                    $detallesNuevos[] = [
                        'id_insumo' => $id_insumo,
                        'cantidad' => $cantidad,
                        'precio' => $precio,
                        'subtotal' => $sub
                    ];
                }
            }

            if (empty($detallesNuevos)) {
                $mensaje = "Agregue al menos un insumo válido.";
            }
        }
    }

    /* DETALLE DE OTROS CONCEPTOS */

    if ($mensaje === '' && $tipo_compra === 'Otros') {
        $conceptos = $_POST['concepto'] ?? [];
        $descripciones = $_POST['descripcion'] ?? [];
        $cantidades = $_POST['cantidad_otro'] ?? [];
        $precios = $_POST['precio_otro'] ?? [];

        if (!is_array($conceptos) || !is_array($descripciones) ||
            !is_array($cantidades) || !is_array($precios)) {
            $mensaje = "Los detalles de la compra no son válidos.";
        } else {
            for ($i = 0; $i < count($conceptos); $i++) {
                $concepto = trim($conceptos[$i] ?? '');
                $descripcion = trim($descripciones[$i] ?? '');
                $cantidad = (float)($cantidades[$i] ?? 0);
                $precioTexto = (string)($precios[$i] ?? '0');
                $precioTexto = str_replace('.', '', $precioTexto);
                $precio = (float)$precioTexto;

                if ($concepto !== '' && $cantidad > 0 && $precio >= 0) {
                    $sub = $cantidad * $precio;
                    $subtotal += $sub;

                    $detallesNuevos[] = [
                        'concepto' => $concepto,
                        'descripcion' => $descripcion,
                        'cantidad' => $cantidad,
                        'precio' => $precio,
                        'subtotal' => $sub
                    ];
                }
            }

            if (empty($detallesNuevos)) {
                $mensaje = "Agregue al menos un concepto válido.";
            }
        }
    }

    /* ACTUALIZAR COMPRA, DETALLES, MOVIMIENTOS Y STOCK */

    if ($mensaje === '') {
        $iva = $subtotal * 0.19;
        $total = $subtotal + $iva;

        if ($tipo_proveedor === 'Registrado') {
            $id_proveedor_actualizar = $id_proveedor;
            $nombre_casual_actualizar = null;
            $documento_casual_actualizar = null;
        } else {
            $id_proveedor_actualizar = null;
            $nombre_casual_actualizar = $nombre_proveedor_casual;
            $documento_casual_actualizar =
                $documento_proveedor_casual !== ''
                ? $documento_proveedor_casual
                : null;
        }

        $conexion->begin_transaction();

        try {
            /* BLOQUEAR COMPRA */

            $stmtBloqueo = $conexion->prepare(
                "SELECT id_compra
                 FROM compras
                 WHERE id_compra = ?
                 FOR UPDATE"
            );
            $stmtBloqueo->bind_param("i", $id_compra);
            $stmtBloqueo->execute();

            if (!$stmtBloqueo->get_result()->fetch_assoc()) {
                throw new Exception("La compra ya no existe.");
            }

            /* LEER MOVIMIENTOS ANTERIORES VINCULADOS */

            $stmtMovimientos = $conexion->prepare(
                "SELECT id_movimiento, id_insumo, tipo, cantidad, estado
                 FROM movimientos
                 WHERE id_compra = ?
                 ORDER BY id_movimiento
                 FOR UPDATE"
            );
            $stmtMovimientos->bind_param("i", $id_compra);
            $stmtMovimientos->execute();
            $resultadoMovimientos = $stmtMovimientos->get_result();

            $movimientosAnteriores = [];
            $efectoAnterior = [];

            while ($mov = $resultadoMovimientos->fetch_assoc()) {
                $movimientosAnteriores[] = $mov;

                if ($mov['estado'] === 'Completado') {
                    $idAnterior = (int)$mov['id_insumo'];
                    $cantidadAnterior = (float)$mov['cantidad'];

                    if (!isset($efectoAnterior[$idAnterior])) {
                        $efectoAnterior[$idAnterior] = 0;
                    }

                    if ($mov['tipo'] === 'Entrada') {
                        $efectoAnterior[$idAnterior] += $cantidadAnterior;
                    } elseif ($mov['tipo'] === 'Salida') {
                        $efectoAnterior[$idAnterior] -= $cantidadAnterior;
                    }
                }
            }

            /*
             * CALCULAR EL EFECTO NUEVO.
             * Una compra recibida genera entradas completadas.
             * Una pendiente genera entradas pendientes sin efecto en stock.
             */

            $efectoNuevo = [];

            if ($tipo_compra === 'Insumos' && $estado === 'Recibida') {
                foreach ($detallesNuevos as $detalle) {
                    $idNuevo = (int)$detalle['id_insumo'];

                    if (!isset($efectoNuevo[$idNuevo])) {
                        $efectoNuevo[$idNuevo] = 0;
                    }

                    $efectoNuevo[$idNuevo] += (float)$detalle['cantidad'];
                }
            }

            /* CALCULAR DIFERENCIAS POR INSUMO */

            $idsAfectados = array_unique(array_merge(
                array_keys($efectoAnterior),
                array_keys($efectoNuevo)
            ));
            sort($idsAfectados);

            foreach ($idsAfectados as $idAfectado) {
                $idAfectado = (int)$idAfectado;
                $anterior = (float)($efectoAnterior[$idAfectado] ?? 0);
                $nuevo = (float)($efectoNuevo[$idAfectado] ?? 0);
                $diferencia = $nuevo - $anterior;

                if (abs($diferencia) < 0.00001) {
                    continue;
                }

                $stmtStock = $conexion->prepare(
                    "SELECT stock
                     FROM insumos
                     WHERE id_insumo = ?
                     FOR UPDATE"
                );
                $stmtStock->bind_param("i", $idAfectado);
                $stmtStock->execute();
                $filaStock = $stmtStock->get_result()->fetch_assoc();

                if (!$filaStock) {
                    throw new Exception(
                        "No se encontró el insumo ID " . $idAfectado . "."
                    );
                }

                $stockActual = (float)$filaStock['stock'];
                $stockNuevo = $stockActual + $diferencia;

                if ($stockNuevo < -0.00001) {
                    throw new Exception(
                        "No se puede guardar la compra porque el ajuste dejaría negativo el stock del insumo ID "
                        . $idAfectado . ". Revise los movimientos y el consumo previo."
                    );
                }

                if ($stockNuevo < 0) {
                    $stockNuevo = 0;
                }

                $stmtActualizarStock = $conexion->prepare(
                    "UPDATE insumos SET stock = ? WHERE id_insumo = ?"
                );
                $stmtActualizarStock->bind_param(
                    "di",
                    $stockNuevo,
                    $idAfectado
                );
                $stmtActualizarStock->execute();
            }

            /* ACTUALIZAR CABECERA DE COMPRA */

            $sqlUpdate = "UPDATE compras
                          SET tipo_proveedor = ?,
                              id_proveedor = ?,
                              nombre_proveedor_casual = ?,
                              documento_proveedor_casual = ?,
                              tipo_compra = ?,
                              fecha = ?,
                              subtotal = ?,
                              iva = ?,
                              total = ?,
                              estado = ?,
                              observaciones = ?
                          WHERE id_compra = ?";

            $stmtUpdate = $conexion->prepare($sqlUpdate);
            $stmtUpdate->bind_param(
                "sissssdddssi",
                $tipo_proveedor,
                $id_proveedor_actualizar,
                $nombre_casual_actualizar,
                $documento_casual_actualizar,
                $tipo_compra,
                $fecha,
                $subtotal,
                $iva,
                $total,
                $estado,
                $observaciones,
                $id_compra
            );
            $stmtUpdate->execute();

            /* REEMPLAZAR DETALLES ANTERIORES */

            $stmtDelete = $conexion->prepare(
                "DELETE FROM detalle_compra WHERE id_compra = ?"
            );
            $stmtDelete->bind_param("i", $id_compra);
            $stmtDelete->execute();

            $stmtDeleteOtro = $conexion->prepare(
                "DELETE FROM detalle_compra_otro WHERE id_compra = ?"
            );
            $stmtDeleteOtro->bind_param("i", $id_compra);
            $stmtDeleteOtro->execute();

            /* ELIMINAR SOLO LOS MOVIMIENTOS VINCULADOS A ESTA COMPRA */

            $stmtEliminarMov = $conexion->prepare(
                "DELETE FROM movimientos WHERE id_compra = ?"
            );
            $stmtEliminarMov->bind_param("i", $id_compra);
            $stmtEliminarMov->execute();

            /* INSERTAR LOS NUEVOS DETALLES Y MOVIMIENTOS */

            if ($tipo_compra === 'Insumos') {
                $stmtDetalle = $conexion->prepare(
                    "INSERT INTO detalle_compra
                    (id_compra, id_insumo, cantidad, precio, subtotal)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmtInfoInsumo = $conexion->prepare(
                    "SELECT producto, unidad
                     FROM insumos
                     WHERE id_insumo = ?
                     FOR UPDATE"
                );

                $stmtDetalle->bind_param(
                    "iiddd",
                    $id_compra,
                    $idDetalleInsumo,
                    $cantidadDetalle,
                    $precioDetalle,
                    $subtotalDetalle
                );

                foreach ($detallesNuevos as $detalle) {
                    $idDetalleInsumo = (int)$detalle['id_insumo'];
                    $cantidadDetalle = (float)$detalle['cantidad'];
                    $precioDetalle = (float)$detalle['precio'];
                    $subtotalDetalle = (float)$detalle['subtotal'];

                    $stmtInfoInsumo->bind_param("i", $idDetalleInsumo);
                    $stmtInfoInsumo->execute();
                    $infoInsumo = $stmtInfoInsumo->get_result()->fetch_assoc();

                    if (!$infoInsumo) {
                        throw new Exception(
                            "Uno de los insumos seleccionados ya no existe."
                        );
                    }

                    $unidad = $infoInsumo['unidad'];

                    $stmtDetalle->execute();

                    /* CÓDIGO ÚNICO PARA EL MOVIMIENTO */

                    $siguienteCodigo = (int)$conexion->query(
                        "SELECT COALESCE(MAX(id_movimiento), 0) + 1 AS siguiente
                         FROM movimientos"
                    )->fetch_assoc()['siguiente'];

                    do {
                        $codigoMovimiento = 'MOV-' . str_pad(
                            (string)$siguienteCodigo,
                            4,
                            '0',
                            STR_PAD_LEFT
                        );

                        $stmtCodigo = $conexion->prepare(
                            "SELECT id_movimiento
                             FROM movimientos
                             WHERE codigo = ?"
                        );
                        $stmtCodigo->bind_param("s", $codigoMovimiento);
                        $stmtCodigo->execute();
                        $codigoExiste = $stmtCodigo->get_result()->num_rows > 0;
                        $siguienteCodigo++;
                    } while ($codigoExiste);

                    $estadoMovimiento =
                        $estado === 'Recibida' ? 'Completado' : 'Pendiente';

                    $responsable = 'Compra ' . $id_compra;
                    $observacionesMovimiento =
                        'Movimiento generado automáticamente desde la compra '
                        . $id_compra;

                    $stmtMovimiento = $conexion->prepare(
                        "INSERT INTO movimientos
                        (id_compra, codigo, fecha, tipo, id_insumo, cantidad,
                         unidad, responsable, estado, observaciones)
                        VALUES (?, ?, ?, 'Entrada', ?, ?, ?, ?, ?, ?)"
                    );

                    $stmtMovimiento->bind_param(
                        "issidssss",
                        $id_compra,
                        $codigoMovimiento,
                        $fecha,
                        $idDetalleInsumo,
                        $cantidadDetalle,
                        $unidad,
                        $responsable,
                        $estadoMovimiento,
                        $observacionesMovimiento
                    );
                    $stmtMovimiento->execute();
                }
            } else {
                $stmtDetalleOtro = $conexion->prepare(
                    "INSERT INTO detalle_compra_otro
                    (id_compra, concepto, descripcion, cantidad, precio, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?)"
                );

                foreach ($detallesNuevos as $detalle) {
                    $concepto = $detalle['concepto'];
                    $descripcion = $detalle['descripcion'];
                    $cantidadOtro = (float)$detalle['cantidad'];
                    $precioOtro = (float)$detalle['precio'];
                    $subtotalOtro = (float)$detalle['subtotal'];

                    $stmtDetalleOtro->bind_param(
                        "issddd",
                        $id_compra,
                        $concepto,
                        $descripcion,
                        $cantidadOtro,
                        $precioOtro,
                        $subtotalOtro
                    );
                    $stmtDetalleOtro->execute();
                }
            }

            $conexion->commit();

            header("Location: VerCompra.php?id=" . $id_compra);
            exit;

        } catch (Throwable $e) {
            $conexion->rollback();
            $mensaje = $e->getMessage();

            if ($mensaje === '') {
                $mensaje = "No fue posible actualizar la compra.";
            }
        }
    }

    /* ACTUALIZAR DATOS MOSTRADOS SI HAY ERROR */

    if ($mensaje !== '') {
        $compra['tipo_proveedor'] = $tipo_proveedor;
        $compra['id_proveedor'] = $id_proveedor;
        $compra['nombre_proveedor_casual'] = $nombre_proveedor_casual;
        $compra['documento_proveedor_casual'] = $documento_proveedor_casual;
        $compra['tipo_compra'] = $tipo_compra;
        $compra['fecha'] = $fecha;
        $compra['estado'] = $estado;
        $compra['observaciones'] = $observaciones;
    }
}

/* CARGAR DETALLES ACTUALES */

$detalleCompra = [];
$detalleOtros = [];

if ($compra['tipo_compra'] === 'Insumos') {
    $stmtDetalle = $conexion->prepare(
        "SELECT *
         FROM detalle_compra
         WHERE id_compra = ?
         ORDER BY id_detalle_compra ASC"
    );
    $stmtDetalle->bind_param("i", $id_compra);
    $stmtDetalle->execute();
    $resultadoDetalle = $stmtDetalle->get_result();

    while ($fila = $resultadoDetalle->fetch_assoc()) {
        $detalleCompra[] = $fila;
    }
} else {
    $stmtDetalleOtro = $conexion->prepare(
        "SELECT *
         FROM detalle_compra_otro
         WHERE id_compra = ?
         ORDER BY id_detalle_otro ASC"
    );
    $stmtDetalleOtro->bind_param("i", $id_compra);
    $stmtDetalleOtro->execute();
    $resultadoDetalleOtro = $stmtDetalleOtro->get_result();

    while ($fila = $resultadoDetalleOtro->fetch_assoc()) {
        $detalleOtros[] = $fila;
    }
}

require_once "../../includes/header.php";
?>

<div class="contenedor compras-modulo">
    <div class="barra-superior">
        <a href="Compras.php" class="btn-volver">← Volver a Compras</a>
        <a href="../../index.php" class="btn-inicio">⌂ Dashboard</a>
    </div>

    <div class="encabezado">
        <h1>EDITAR COMPRA</h1>
        <p>Modifica la información de la compra registrada.</p>
    </div>

    <?php if ($mensaje): ?>
        <div class="mensaje-error">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="panel">
        <form method="POST">
            <input type="hidden" name="id_compra" value="<?= $id_compra ?>">

            <div class="compra-datos">
                <div class="campo">
                    <label>Tipo de proveedor</label>
                    <select name="tipo_proveedor" id="tipoProveedor"
                            onchange="cambiarTipoProveedor()" required>
                        <option value="Registrado"
                            <?= $compra['tipo_proveedor'] === 'Registrado' ? 'selected' : '' ?>>
                            Proveedor registrado
                        </option>
                        <option value="Casual"
                            <?= $compra['tipo_proveedor'] === 'Casual' ? 'selected' : '' ?>>
                            Proveedor casual
                        </option>
                    </select>
                </div>

                <div class="campo">
                    <label>Tipo de compra</label>
                    <select name="tipo_compra" id="tipoCompra"
                            onchange="cambiarTipoCompra()" required>
                        <option value="Insumos"
                            <?= $compra['tipo_compra'] === 'Insumos' ? 'selected' : '' ?>>
                            Compra de insumos
                        </option>
                        <option value="Otros"
                            <?= $compra['tipo_compra'] === 'Otros' ? 'selected' : '' ?>>
                            Otros conceptos
                        </option>
                    </select>
                </div>

                <div class="campo" id="campoProveedorRegistrado">
                    <label>Proveedor</label>
                    <select name="id_proveedor" id="idProveedor">
                        <option value="">Seleccione un proveedor</option>
                        <?php while ($proveedor = $proveedores->fetch_assoc()): ?>
                            <option value="<?= $proveedor['id_proveedor'] ?>"
                                <?= $proveedor['id_proveedor'] == $compra['id_proveedor'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($proveedor['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="campo" id="campoProveedorCasual">
                    <label>Nombre del proveedor</label>
                    <input type="text" name="nombre_proveedor_casual"
                           id="nombreProveedorCasual"
                           value="<?= htmlspecialchars($compra['nombre_proveedor_casual'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Nombre del proveedor">
                </div>

                <div class="campo" id="campoDocumentoCasual">
                    <label>Documento</label>
                    <input type="text" name="documento_proveedor_casual"
                           id="documentoProveedorCasual"
                           value="<?= htmlspecialchars($compra['documento_proveedor_casual'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Documento opcional">
                </div>

                <div class="campo">
                    <label>Fecha</label>
                    <input type="date" name="fecha"
                           value="<?= htmlspecialchars($compra['fecha'], ENT_QUOTES, 'UTF-8') ?>"
                           required>
                </div>

                <div class="campo">
                    <label>Estado</label>
                    <select name="estado" required>
                        <option value="Pendiente"
                            <?= $compra['estado'] === 'Pendiente' ? 'selected' : '' ?>>
                            Pendiente
                        </option>
                        <option value="Recibida"
                            <?= $compra['estado'] === 'Recibida' ? 'selected' : '' ?>>
                            Recibida
                        </option>
                    </select>
                </div>
            </div>

            <div class="campo campo-observaciones">
                <label>Observaciones</label>
                <textarea name="observaciones"><?= htmlspecialchars($compra['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div id="panelInsumos">
                <h2 class="titulo-insumos">Insumos</h2>
                <div id="detalle-compra">
                    <?php if (!empty($detalleCompra)): ?>
                        <?php foreach ($detalleCompra as $detalle): ?>
                            <div class="fila-insumo">
                                <select name="id_insumo[]" required>
                                    <option value="">Seleccione un insumo</option>
                                    <?php
                                    $insumos->data_seek(0);
                                    while ($insumo = $insumos->fetch_assoc()):
                                    ?>
                                        <option value="<?= $insumo['id_insumo'] ?>"
                                            <?= $insumo['id_insumo'] == $detalle['id_insumo'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($insumo['producto'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>

                                <input type="number" name="cantidad[]" step="0.01" min="0.01"
                                       value="<?= $detalle['cantidad'] ?>" required>

                                <input type="text" name="precio[]" class="precio-colombiano"
                                       inputmode="numeric"
                                       value="<?= number_format($detalle['precio'], 0, ',', '.') ?>"
                                       required>

                                <button type="button" class="btn-quitar"
                                        onclick="eliminarFila(this)">×</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="fila-insumo">
                            <select name="id_insumo[]" required>
                                <option value="">Seleccione un insumo</option>
                                <?php
                                $insumos->data_seek(0);
                                while ($insumo = $insumos->fetch_assoc()):
                                ?>
                                    <option value="<?= $insumo['id_insumo'] ?>">
                                        <?= htmlspecialchars($insumo['producto'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <input type="number" name="cantidad[]" step="0.01" min="0.01"
                                   value="1" required>
                            <input type="text" name="precio[]" class="precio-colombiano"
                                   inputmode="numeric" value="0" required>
                            <button type="button" class="btn-quitar"
                                    onclick="eliminarFila(this)">×</button>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="button" class="btn btn-nuevo" onclick="agregarFilaInsumo()">
                    + Agregar Insumo
                </button>
            </div>

            <div id="panelOtros">
                <h2 class="titulo-insumos">Otros conceptos</h2>
                <div id="detalle-otros">
                    <?php if (!empty($detalleOtros)): ?>
                        <?php foreach ($detalleOtros as $detalle): ?>
                            <div class="fila-insumo fila-otro">
                                <input type="text" name="concepto[]" placeholder="Concepto"
                                       value="<?= htmlspecialchars($detalle['concepto'], ENT_QUOTES, 'UTF-8') ?>"
                                       required>
                                <input type="text" name="descripcion[]" placeholder="Descripción"
                                       value="<?= htmlspecialchars($detalle['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="number" name="cantidad_otro[]" step="0.01" min="0.01"
                                       value="<?= $detalle['cantidad'] ?>" required>
                                <input type="text" name="precio_otro[]" class="precio-colombiano"
                                       inputmode="numeric"
                                       value="<?= number_format($detalle['precio'], 0, ',', '.') ?>"
                                       required>
                                <button type="button" class="btn-quitar"
                                        onclick="eliminarFila(this)">×</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="fila-insumo fila-otro">
                            <input type="text" name="concepto[]" placeholder="Concepto" required>
                            <input type="text" name="descripcion[]" placeholder="Descripción">
                            <input type="number" name="cantidad_otro[]" step="0.01" min="0.01"
                                   value="1" required>
                            <input type="text" name="precio_otro[]" class="precio-colombiano"
                                   inputmode="numeric" value="0" required>
                            <button type="button" class="btn-quitar"
                                    onclick="eliminarFila(this)">×</button>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="button" class="btn btn-nuevo" onclick="agregarFilaOtro()">
                    + Agregar Concepto
                </button>
            </div>

            <div class="acciones-formulario acciones-compra">
                <button type="submit" class="btn btn-nuevo">Guardar cambios</button>
                <a href="Compras.php" class="btn btn-cancelar">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
function formatearPrecio(campo) {
    let valor = campo.value.replace(/\D/g, '');
    campo.value = valor === '' ? '' : Number(valor).toLocaleString('es-CO');
}

function manejarPrecio() {
    formatearPrecio(this);
}

function activarPrecios() {
    document.querySelectorAll('.precio-colombiano').forEach(function(campo) {
        campo.removeEventListener('input', manejarPrecio);
        campo.addEventListener('input', manejarPrecio);
    });
}

function cambiarTipoProveedor() {
    const tipo = document.getElementById('tipoProveedor').value;
    const campoRegistrado = document.getElementById('campoProveedorRegistrado');
    const campoCasual = document.getElementById('campoProveedorCasual');
    const campoDocumento = document.getElementById('campoDocumentoCasual');
    const proveedor = document.getElementById('idProveedor');
    const nombre = document.getElementById('nombreProveedorCasual');
    const documento = document.getElementById('documentoProveedorCasual');

    if (tipo === 'Registrado') {
        campoRegistrado.style.display = '';
        campoCasual.style.display = 'none';
        campoDocumento.style.display = 'none';
        proveedor.disabled = false;
        proveedor.required = true;
        nombre.disabled = true;
        nombre.required = false;
        documento.disabled = true;
    } else {
        campoRegistrado.style.display = 'none';
        campoCasual.style.display = '';
        campoDocumento.style.display = '';
        proveedor.disabled = true;
        proveedor.required = false;
        proveedor.value = '';
        nombre.disabled = false;
        nombre.required = true;
        documento.disabled = false;
    }
}

function cambiarTipoCompra() {
    const tipo = document.getElementById('tipoCompra').value;
    const panelInsumos = document.getElementById('panelInsumos');
    const panelOtros = document.getElementById('panelOtros');
    const insumos = document.querySelectorAll('#detalle-compra input, #detalle-compra select');
    const otros = document.querySelectorAll('#detalle-otros input');

    if (tipo === 'Insumos') {
        panelInsumos.style.display = '';
        panelOtros.style.display = 'none';
        insumos.forEach(campo => campo.disabled = false);
        otros.forEach(campo => campo.disabled = true);
    } else {
        panelInsumos.style.display = 'none';
        panelOtros.style.display = '';
        insumos.forEach(campo => campo.disabled = true);
        otros.forEach(campo => campo.disabled = false);
    }
}

function agregarFilaInsumo() {
    const contenedor = document.getElementById('detalle-compra');
    const filaOriginal = contenedor.querySelector('.fila-insumo');
    const nuevaFila = filaOriginal.cloneNode(true);

    nuevaFila.querySelector('select').selectedIndex = 0;
    nuevaFila.querySelector('[name="cantidad[]"]').value = '1';
    nuevaFila.querySelector('[name="precio[]"]').value = '0';

    nuevaFila.querySelectorAll('input, select').forEach(campo => campo.disabled = false);
    contenedor.appendChild(nuevaFila);
    activarPrecios();
    cambiarTipoCompra();
}

function agregarFilaOtro() {
    const contenedor = document.getElementById('detalle-otros');
    const filaOriginal = contenedor.querySelector('.fila-otro');
    const nuevaFila = filaOriginal.cloneNode(true);

    nuevaFila.querySelector('[name="concepto[]"]').value = '';
    nuevaFila.querySelector('[name="descripcion[]"]').value = '';
    nuevaFila.querySelector('[name="cantidad_otro[]"]').value = '1';
    nuevaFila.querySelector('[name="precio_otro[]"]').value = '0';

    nuevaFila.querySelectorAll('input').forEach(campo => campo.disabled = false);
    contenedor.appendChild(nuevaFila);
    activarPrecios();
    cambiarTipoCompra();
}

function eliminarFila(boton) {
    const fila = boton.closest('.fila-insumo');
    const contenedor = fila.parentElement;

    if (contenedor.querySelectorAll('.fila-insumo').length > 1) {
        fila.remove();
    }
}

document.querySelector('form').addEventListener('submit', function() {
    document.querySelectorAll('.precio-colombiano').forEach(function(campo) {
        if (!campo.disabled) {
            campo.value = campo.value.replace(/\./g, '');
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    activarPrecios();
    cambiarTipoProveedor();
    cambiarTipoCompra();
});
</script>

<?php require_once "../../includes/footer.php"; ?>