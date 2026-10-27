
<?php
$titulo = "EDITAR PEDIDO";
$css = "Pedidos.css";

require_once("../includes/conexion.php");

$id_pedido = (int)($_GET['id'] ?? $_POST['id_pedido'] ?? 0);
$iva_porcentaje = 0.19;
$errores = [];
$detalles_nuevos = [];
$subtotal = 0;

if ($id_pedido <= 0) {
    header("Location: Pedidos.php");
    exit;
}

/* OBTENER PEDIDO */
$stmt = $conexion->prepare("SELECT * FROM pedidos WHERE id_pedido = ?");
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) {
    header("Location: Pedidos.php");
    exit;
}

/* CLIENTES ACTIVOS */
$clientes = [];
$resultado = $conexion->query("
    SELECT id_cliente, nombre
    FROM clientes
    WHERE LOWER(estado) = 'activo'
    ORDER BY nombre
");

if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $clientes[] = $fila;
    }
}

/* PRODUCTOS ACTIVOS */
$productos = [];
$resultado = $conexion->query("
    SELECT id_producto, nombre, presentacion, precio
    FROM productos
    WHERE LOWER(estado) = 'activo'
    ORDER BY nombre, presentacion
");

if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $productos[] = $fila;
    }
}

/* DETALLES DEL PEDIDO */
$detalles = [];
$stmt = $conexion->prepare("
    SELECT dp.id_producto, dp.cantidad, dp.precio, dp.subtotal,
           p.nombre AS producto, p.presentacion,
           p.estado AS estado_producto
    FROM detalle_pedido dp
    INNER JOIN productos p ON p.id_producto = dp.id_producto
    WHERE dp.id_pedido = ?
    ORDER BY dp.id_detalle_pedido
");
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$resultado = $stmt->get_result();

while ($fila = $resultado->fetch_assoc()) {
    $detalles[] = $fila;
}
$stmt->close();

if (empty($detalles)) {
    $detalles[] = [
        'id_producto' => '',
        'cantidad' => 1,
        'precio' => 0,
        'subtotal' => 0,
        'producto' => '',
        'presentacion' => '',
        'estado_producto' => ''
    ];
}

/* VALORES INICIALES */
$fecha_actual = $pedido['fecha'] ?? '';
$fecha_entrega_actual = $pedido['fecha_entrega'] ?? '';
$estado_actual = $pedido['estado'] ?? 'Pendiente';
$observaciones_actuales = $pedido['observaciones'] ?? '';
$id_cliente_actual = (int)($pedido['id_cliente'] ?? 0);
$tipo_cliente_actual = $id_cliente_actual > 0 ? 'registrado' : 'ocasional';
$nombre_cliente_actual = $pedido['nombre_cliente'] ?? '';
$documento_cliente_actual = $pedido['documento_cliente'] ?? '';
$telefono_cliente_actual = $pedido['telefono_cliente'] ?? '';
$correo_cliente_actual = $pedido['correo_cliente'] ?? '';

/* ACTUALIZAR PEDIDO */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha_actual = trim($_POST['fecha'] ?? '');
    $tipo_cliente_actual = $_POST['tipo_cliente'] ?? 'registrado';
    $id_cliente_actual = (int)($_POST['id_cliente'] ?? 0);

    $nombre_cliente_actual = trim($_POST['nombre_cliente'] ?? '');
    $documento_cliente_actual = trim($_POST['documento_cliente'] ?? '');
    $telefono_cliente_actual = trim($_POST['telefono_cliente'] ?? '');
    $correo_cliente_actual = trim($_POST['correo_cliente'] ?? '');

    $estado_actual = trim($_POST['estado'] ?? '');
    $fecha_entrega_actual = trim($_POST['fecha_entrega'] ?? '');
    $observaciones_actuales = trim($_POST['observaciones'] ?? '');

    $producto_ids = $_POST['producto_id'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];

    $id_cliente = null;
    $nombre_cliente = null;
    $documento_cliente = null;
    $telefono_cliente = null;
    $correo_cliente = null;

    /* VALIDAR CLIENTE */
    if ($tipo_cliente_actual === 'registrado') {
        if ($id_cliente_actual <= 0) {
            $errores[] = "Debe seleccionar un cliente.";
        } else {
            $stmt = $conexion->prepare("
                SELECT id_cliente
                FROM clientes
                WHERE id_cliente = ?
                  AND LOWER(estado) = 'activo'
            ");
            $stmt->bind_param("i", $id_cliente_actual);
            $stmt->execute();
            $cliente_valido = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$cliente_valido) {
                $errores[] = "El cliente seleccionado no está disponible.";
            } else {
                $id_cliente = $id_cliente_actual;
            }
        }
    } elseif ($tipo_cliente_actual === 'ocasional') {
        if ($nombre_cliente_actual === '') {
            $errores[] = "El nombre del cliente ocasional es obligatorio.";
        }

        $nombre_cliente = $nombre_cliente_actual;
        $documento_cliente = $documento_cliente_actual !== ''
            ? $documento_cliente_actual : null;
        $telefono_cliente = $telefono_cliente_actual !== ''
            ? $telefono_cliente_actual : null;
        $correo_cliente = $correo_cliente_actual !== ''
            ? $correo_cliente_actual : null;

        if ($correo_cliente_actual !== '' &&
            !filter_var($correo_cliente_actual, FILTER_VALIDATE_EMAIL)) {
            $errores[] = "El correo del cliente ocasional no es válido.";
        }
    } else {
        $errores[] = "Tipo de cliente no válido.";
    }

    /* VALIDAR FECHAS */
    if ($fecha_actual === '') {
        $errores[] = "La fecha del pedido es obligatoria.";
    } elseif (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_actual) ||
        !checkdate(
            (int)substr($fecha_actual, 5, 2),
            (int)substr($fecha_actual, 8, 2),
            (int)substr($fecha_actual, 0, 4)
        )
    ) {
        $errores[] = "La fecha del pedido no es válida.";
    }

    if ($fecha_entrega_actual !== '' && (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_entrega_actual) ||
        !checkdate(
            (int)substr($fecha_entrega_actual, 5, 2),
            (int)substr($fecha_entrega_actual, 8, 2),
            (int)substr($fecha_entrega_actual, 0, 4)
        )
    )) {
        $errores[] = "La fecha de entrega no es válida.";
    }

    $estados_permitidos = [
        'Pendiente', 'En Producción', 'Entregado', 'Cancelado'
    ];

    if (!in_array($estado_actual, $estados_permitidos, true)) {
        $errores[] = "El estado seleccionado no es válido.";
    }

    /* VALIDAR PRODUCTOS */
    if (
        !is_array($producto_ids) ||
        !is_array($cantidades) ||
        count($producto_ids) === 0 ||
        count($producto_ids) !== count($cantidades)
    ) {
        $errores[] = "Debe existir al menos un producto válido.";
    }

    /* VALIDAR PRODUCTOS Y CALCULAR PRECIOS */
    if (empty($errores)) {
        $productos_procesados = [];

        foreach ($producto_ids as $indice => $id_recibido) {
            $id_producto = filter_var($id_recibido, FILTER_VALIDATE_INT);
            $cantidad = filter_var(
                $cantidades[$indice] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$id_producto || $id_producto <= 0 ||
                !$cantidad || $cantidad <= 0) {
                $errores[] = "Revise los productos y las cantidades.";
                break;
            }

            if (isset($productos_procesados[$id_producto])) {
                $errores[] = "No repita el mismo producto en varias filas.";
                break;
            }

            $productos_procesados[$id_producto] = true;

            $stmt = $conexion->prepare("
                SELECT id_producto, precio, estado
                FROM productos
                WHERE id_producto = ?
                LIMIT 1
            ");
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            $producto_db = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$producto_db) {
                $errores[] = "Uno de los productos seleccionados ya no existe.";
                break;
            }

            $producto_activo =
                strtolower(trim($producto_db['estado'] ?? '')) === 'activo';

            $pertenecia_al_pedido = false;

            foreach ($detalles as $anterior) {
                if ((int)$anterior['id_producto'] === $id_producto) {
                    $pertenecia_al_pedido = true;
                    break;
                }
            }

            if (!$producto_activo && !$pertenecia_al_pedido) {
                $errores[] = "Uno de los productos seleccionados no está disponible.";
                break;
            }

            $precio = (float)$producto_db['precio'];
            $subtotal_detalle = $precio * $cantidad;
            $subtotal += $subtotal_detalle;

            $detalles_nuevos[] = [
                'id_producto' => $id_producto,
                'cantidad' => $cantidad,
                'precio' => $precio,
                'subtotal' => $subtotal_detalle
            ];
        }
    }

    /* GUARDAR PEDIDO Y SINCRONIZAR PRODUCCIÓN */
    if (empty($errores)) {
        $iva = round($subtotal * $iva_porcentaje, 2);
        $total = round($subtotal + $iva, 2);
        $fecha_entrega_db = $fecha_entrega_actual !== ''
            ? $fecha_entrega_actual : null;

        try {
            $conexion->begin_transaction();

            /*
             * Bloquear y consultar las órdenes del pedido.
             * Los códigos existentes se conservan.
             */
            $stmt = $conexion->prepare("
                SELECT id_orden, codigo, id_cliente, id_producto,
                       cantidad, estado
                FROM ordenes_produccion
                WHERE id_pedido = ?
                FOR UPDATE
            ");
            $stmt->bind_param("i", $id_pedido);
            $stmt->execute();
            $resultado_ordenes = $stmt->get_result();
            $ordenes_existentes = [];

            while ($orden = $resultado_ordenes->fetch_assoc()) {
                $ordenes_existentes[] = $orden;
            }
            $stmt->close();

            /*
             * Resolver el id_cliente que necesita producción.
             * Para clientes ocasionales se reutiliza el cliente
             * vinculado a una orden existente cuando lo haya.
             */
            $id_cliente_produccion = $id_cliente;

            if ($tipo_cliente_actual === 'ocasional') {
                foreach ($ordenes_existentes as $orden) {
                    if ((int)$orden['id_cliente'] > 0) {
                        $id_cliente_produccion = (int)$orden['id_cliente'];
                        break;
                    }
                }

                /*
                 * Si el pedido ocasional no tiene órdenes previas,
                 * buscar o registrar el cliente para producción.
                 */
                if (!$id_cliente_produccion) {
                    $cliente_encontrado = null;

                    if (!empty($documento_cliente)) {
                        $stmt = $conexion->prepare("
                            SELECT id_cliente
                            FROM clientes
                            WHERE documento = ?
                            LIMIT 1
                        ");
                        $stmt->bind_param("s", $documento_cliente);
                        $stmt->execute();
                        $cliente_encontrado =
                            $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                    }

                    if (!$cliente_encontrado) {
                        $stmt = $conexion->prepare("
                            SELECT id_cliente
                            FROM clientes
                            WHERE nombre = ?
                            LIMIT 1
                        ");
                        $stmt->bind_param("s", $nombre_cliente);
                        $stmt->execute();
                        $cliente_encontrado =
                            $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                    }

                    if ($cliente_encontrado) {
                        $id_cliente_produccion =
                            (int)$cliente_encontrado['id_cliente'];
                    } else {
                        $resultado_codigo = $conexion->query("
                            SELECT COALESCE(
                                MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)),
                                0
                            ) + 1 AS siguiente
                            FROM clientes
                            WHERE codigo REGEXP '^CLI-[0-9]+$'
                        ");

                        if (!$resultado_codigo) {
                            throw new Exception(
                                "No se pudo generar el código del cliente."
                            );
                        }

                        $fila_codigo = $resultado_codigo->fetch_assoc();
                        $codigo_cliente = 'CLI-' .
                            str_pad((string)$fila_codigo['siguiente'], 4, '0', STR_PAD_LEFT);

                        $stmt = $conexion->prepare("
                            INSERT INTO clientes
                                (codigo, nombre, documento, telefono, correo, estado)
                            VALUES (?, ?, ?, ?, ?, 'Activo')
                        ");
                        $stmt->bind_param(
                            "sssss",
                            $codigo_cliente,
                            $nombre_cliente,
                            $documento_cliente,
                            $telefono_cliente,
                            $correo_cliente
                        );

                        if (!$stmt->execute()) {
                            throw new Exception(
                                "No se pudo registrar el cliente para producción."
                            );
                        }

                        $id_cliente_produccion = (int)$conexion->insert_id;
                        $stmt->close();
                    }
                }
            }

            /*
             * Actualizar cabecera del pedido.
             */
            $stmt = $conexion->prepare("
                UPDATE pedidos
                SET
                    id_cliente = ?,
                    nombre_cliente = ?,
                    documento_cliente = ?,
                    telefono_cliente = ?,
                    correo_cliente = ?,
                    fecha = ?,
                    fecha_entrega = ?,
                    subtotal = ?,
                    iva = ?,
                    total = ?,
                    estado = ?,
                    observaciones = ?
                WHERE id_pedido = ?
            ");

            $stmt->bind_param(
                "issssssdddssi",
                $id_cliente,
                $nombre_cliente,
                $documento_cliente,
                $telefono_cliente,
                $correo_cliente,
                $fecha_actual,
                $fecha_entrega_db,
                $subtotal,
                $iva,
                $total,
                $estado_actual,
                $observaciones_actuales,
                $id_pedido
            );

            if (!$stmt->execute()) {
                throw new Exception("No se pudo actualizar el pedido.");
            }
            $stmt->close();

            /*
             * Reemplazar los detalles del pedido.
             */
            $stmt = $conexion->prepare("
                DELETE FROM detalle_pedido
                WHERE id_pedido = ?
            ");
            $stmt->bind_param("i", $id_pedido);

            if (!$stmt->execute()) {
                throw new Exception("No se pudieron actualizar los productos.");
            }
            $stmt->close();

            $stmt = $conexion->prepare("
                INSERT INTO detalle_pedido
                    (id_pedido, id_producto, cantidad, precio, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($detalles_nuevos as $detalle) {
                $stmt->bind_param(
                    "iiidd",
                    $id_pedido,
                    $detalle['id_producto'],
                    $detalle['cantidad'],
                    $detalle['precio'],
                    $detalle['subtotal']
                );

                if (!$stmt->execute()) {
                    throw new Exception("No se pudo guardar un producto.");
                }
            }
            $stmt->close();

            /*
             * Eliminar órdenes pendientes de productos retirados.
             * Las órdenes en producción o entregadas se conservan.
             */
            $productos_actuales = [];

            foreach ($detalles_nuevos as $detalle) {
                $productos_actuales[(int)$detalle['id_producto']] = true;
            }

            $stmt_eliminar_orden = $conexion->prepare("
                DELETE FROM ordenes_produccion
                WHERE id_orden = ?
                  AND id_pedido = ?
                  AND estado = 'Pendiente'
            ");

            foreach ($ordenes_existentes as $orden) {
                $id_producto_orden = (int)$orden['id_producto'];

                if (
                    strtolower(trim($orden['estado'])) === 'pendiente' &&
                    !isset($productos_actuales[$id_producto_orden])
                ) {
                    $id_orden_eliminar = (int)$orden['id_orden'];
                    $stmt_eliminar_orden->bind_param(
                        "ii",
                        $id_orden_eliminar,
                        $id_pedido
                    );

                    if (!$stmt_eliminar_orden->execute()) {
                        throw new Exception(
                            "No se pudo eliminar una orden pendiente retirada del pedido."
                        );
                    }
                }
            }
            $stmt_eliminar_orden->close();

            /*
             * Sincronizar cada producto:
             * - Si tiene orden pendiente, actualizarla.
             * - Si tiene orden en producción o entregada, no tocarla.
             * - Si no tiene ninguna orden, crear una nueva pendiente.
             */
            foreach ($detalles_nuevos as $detalle) {
                $id_producto_actual = (int)$detalle['id_producto'];
                $orden_pendiente = null;
                $tiene_orden_no_editable = false;

                foreach ($ordenes_existentes as $orden) {
                    if ((int)$orden['id_producto'] !== $id_producto_actual) {
                        continue;
                    }

                    if (strtolower(trim($orden['estado'])) === 'pendiente') {
                        $orden_pendiente = $orden;
                        break;
                    }

                    $tiene_orden_no_editable = true;
                }

                if ($orden_pendiente) {
                    $id_orden_actualizar = (int)$orden_pendiente['id_orden'];

                    $stmt = $conexion->prepare("
                        UPDATE ordenes_produccion
                        SET
                            fecha = ?,
                            id_cliente = ?,
                            cantidad = ?,
                            fecha_entrega = NULLIF(?, ''),
                            observaciones = ?
                        WHERE id_orden = ?
                          AND id_pedido = ?
                          AND estado = 'Pendiente'
                    ");

                    $stmt->bind_param(
                        "siissii",
                        $fecha_actual,
                        $id_cliente_produccion,
                        $detalle['cantidad'],
                        $fecha_entrega_actual,
                        $observaciones_actuales,
                        $id_orden_actualizar,
                        $id_pedido
                    );

                    if (!$stmt->execute()) {
                        throw new Exception(
                            "No se pudo sincronizar una orden de producción pendiente."
                        );
                    }
                    $stmt->close();
                } elseif (!$tiene_orden_no_editable) {
                    /*
                     * Crear código OP nuevo sin reutilizar códigos existentes.
                     */
                    $resultado_codigo = $conexion->query("
                        SELECT COALESCE(
                            MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)),
                            0
                        ) + 1 AS siguiente
                        FROM ordenes_produccion
                        WHERE codigo REGEXP '^OP-[0-9]+$'
                    ");

                    if (!$resultado_codigo) {
                        throw new Exception(
                            "No se pudo generar el código de la orden de producción."
                        );
                    }

                    $fila_codigo = $resultado_codigo->fetch_assoc();
                    $codigo_orden = 'OP-' .
                        str_pad((string)$fila_codigo['siguiente'], 4, '0', STR_PAD_LEFT);

                    $responsable = 'Por asignar';

                    $stmt = $conexion->prepare("
                        INSERT INTO ordenes_produccion
                            (
                                id_pedido,
                                codigo,
                                fecha,
                                id_cliente,
                                id_producto,
                                cantidad,
                                responsable,
                                fecha_entrega,
                                estado,
                                observaciones
                            )
                        VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), 'Pendiente', ?)
                    ");

                    $stmt->bind_param(
                        "issiiisss",
                        $id_pedido,
                        $codigo_orden,
                        $fecha_actual,
                        $id_cliente_produccion,
                        $id_producto_actual,
                        $detalle['cantidad'],
                        $responsable,
                        $fecha_entrega_actual,
                        $observaciones_actuales
                    );

                    if (!$stmt->execute()) {
                        throw new Exception(
                            "No se pudo crear la nueva orden de producción."
                        );
                    }
                    $stmt->close();
                }
            }

            $conexion->commit();

            header(
                "Location: Pedidos.php?mensaje=" .
                urlencode("Pedido y órdenes pendientes actualizados correctamente.") .
                "&tipo=success"
            );
            exit;

        } catch (Throwable $e) {
            $conexion->rollback();
            error_log("EditarPedido.php: " . $e->getMessage());
            $errores[] =
                "No se pudo actualizar el pedido y su producción. Verifique la estructura de la base de datos e inténtelo nuevamente.";
        }
    }

    /* CONSERVAR LOS DATOS EN CASO DE ERROR */
    if (!empty($errores) && is_array($producto_ids) && is_array($cantidades)) {
        $detalles = [];

        foreach ($producto_ids as $indice => $id_producto) {
            $id_producto = (int)$id_producto;
            $producto_encontrado = null;

            foreach ($productos as $producto) {
                if ((int)$producto['id_producto'] === $id_producto) {
                    $producto_encontrado = $producto;
                    break;
                }
            }

            if (!$producto_encontrado && $id_producto > 0) {
                $stmt = $conexion->prepare("
                    SELECT id_producto, nombre, presentacion, precio, estado
                    FROM productos
                    WHERE id_producto = ?
                ");
                $stmt->bind_param("i", $id_producto);
                $stmt->execute();
                $producto_encontrado = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($producto_encontrado) {
                    $productos[] = $producto_encontrado;
                }
            }

            $cantidad_recibida = max(1, (int)($cantidades[$indice] ?? 1));
            $precio_recibido = (float)($producto_encontrado['precio'] ?? 0);

            $detalles[] = [
                'id_producto' => $id_producto,
                'cantidad' => $cantidad_recibida,
                'precio' => $precio_recibido,
                'subtotal' => $precio_recibido * $cantidad_recibida,
                'producto' => $producto_encontrado['nombre'] ?? '',
                'presentacion' => $producto_encontrado['presentacion'] ?? '',
                'estado_producto' => $producto_encontrado['estado'] ?? ''
            ];
        }

        if (empty($detalles)) {
            $detalles[] = [
                'id_producto' => '',
                'cantidad' => 1,
                'precio' => 0,
                'subtotal' => 0,
                'producto' => '',
                'presentacion' => '',
                'estado_producto' => ''
            ];
        }
    }
}

/* ASEGURAR TIPO DE CLIENTE INICIAL */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $tipo_cliente_actual = (int)($pedido['id_cliente'] ?? 0) > 0
        ? 'registrado'
        : 'ocasional';
}

/* MOSTRAR PRODUCTOS ANTERIORES AUNQUE ESTÉN INACTIVOS */
foreach ($detalles as $detalle) {
    $id_producto_anterior = (int)($detalle['id_producto'] ?? 0);

    if ($id_producto_anterior <= 0) {
        continue;
    }

    $existe = false;

    foreach ($productos as $producto) {
        if ((int)$producto['id_producto'] === $id_producto_anterior) {
            $existe = true;
            break;
        }
    }

    if (!$existe) {
        $stmt = $conexion->prepare("
            SELECT id_producto, nombre, presentacion, precio, estado
            FROM productos
            WHERE id_producto = ?
        ");
        $stmt->bind_param("i", $id_producto_anterior);
        $stmt->execute();
        $producto_anterior = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($producto_anterior) {
            $productos[] = $producto_anterior;
        }
    }
}

include("../includes/header.php");
?>

<main class="contenido">
    <section class="barra-superior">
        <a href="Pedidos.php" class="btn btn-principal">← Volver a Pedidos</a>
        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">⌂ Inicio</a>
    </section>

    <header class="encabezado">
        <h1>Editar Pedido</h1>
        <p>Modifica la información del pedido seleccionado.</p>
    </header>

    <?php if (!empty($errores)): ?>
        <div class="mensaje-error">
            <?php foreach ($errores as $error): ?>
                <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form
        action="EditarPedido.php?id=<?= $id_pedido ?>"
        method="post"
        class="formulario"
        id="formEditarPedido"
    >
        <input type="hidden" name="id_pedido" value="<?= $id_pedido ?>">

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Información General</h2>
                    <p>Actualiza los datos principales del pedido.</p>
                </div>
            </div>

            <div class="grupo-formulario">
                <div class="campo">
                    <label for="codigo_pedido">Código del Pedido</label>
                    <input
                        type="text"
                        id="codigo_pedido"
                        value="<?= htmlspecialchars($pedido['codigo_pedido'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        readonly
                    >
                </div>

                <div class="campo">
                    <label for="fecha">Fecha</label>
                    <input
                        type="date"
                        name="fecha"
                        id="fecha"
                        value="<?= htmlspecialchars($fecha_actual, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >
                </div>

                <div class="campo campo-completo">
                    <label>Tipo de Cliente</label>
                    <div class="tipo-cliente">
                        <label>
                            <input
                                type="radio"
                                name="tipo_cliente"
                                value="registrado"
                                id="clienteRegistrado"
                                <?= $tipo_cliente_actual === 'registrado' ? 'checked' : '' ?>
                            >
                            Cliente registrado
                        </label>

                        <label>
                            <input
                                type="radio"
                                name="tipo_cliente"
                                value="ocasional"
                                id="clienteOcasional"
                                <?= $tipo_cliente_actual === 'ocasional' ? 'checked' : '' ?>
                            >
                            Cliente ocasional
                        </label>
                    </div>
                </div>

                <div
                    class="campo campo-completo"
                    id="seccionRegistrado"
                    style="<?= $tipo_cliente_actual === 'registrado' ? '' : 'display:none;' ?>"
                >
                    <label for="id_cliente">Cliente registrado</label>
                    <select
                        name="id_cliente"
                        id="id_cliente"
                        <?= $tipo_cliente_actual === 'registrado' ? 'required' : '' ?>
                    >
                        <option value="">Seleccione un cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option
                                value="<?= (int)$cliente['id_cliente'] ?>"
                                <?= $id_cliente_actual === (int)$cliente['id_cliente'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($cliente['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div
                    class="campo-completo"
                    id="seccionOcasional"
                    style="<?= $tipo_cliente_actual === 'ocasional' ? '' : 'display:none;' ?>"
                >
                    <div class="grupo-formulario">
                        <div class="campo">
                            <label for="nombre_cliente">Nombre</label>
                            <input
                                type="text"
                                name="nombre_cliente"
                                id="nombre_cliente"
                                value="<?= htmlspecialchars($nombre_cliente_actual, ENT_QUOTES, 'UTF-8') ?>"
                                <?= $tipo_cliente_actual === 'ocasional' ? 'required' : '' ?>
                            >
                        </div>

                        <div class="campo">
                            <label for="documento_cliente">Documento</label>
                            <input
                                type="text"
                                name="documento_cliente"
                                id="documento_cliente"
                                value="<?= htmlspecialchars($documento_cliente_actual, ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>

                        <div class="campo">
                            <label for="telefono_cliente">Teléfono</label>
                            <input
                                type="text"
                                name="telefono_cliente"
                                id="telefono_cliente"
                                value="<?= htmlspecialchars($telefono_cliente_actual, ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>

                        <div class="campo">
                            <label for="correo_cliente">Correo</label>
                            <input
                                type="email"
                                name="correo_cliente"
                                id="correo_cliente"
                                value="<?= htmlspecialchars($correo_cliente_actual, ENT_QUOTES, 'UTF-8') ?>"
                            >
                        </div>
                    </div>
                </div>

                <div class="campo">
                    <label for="estado">Estado</label>
                    <select name="estado" id="estado" required>
                        <?php foreach ([
                            'Pendiente',
                            'En Producción',
                            'Entregado',
                            'Cancelado'
                        ] as $estado): ?>
                            <option
                                value="<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>"
                                <?= $estado_actual === $estado ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Productos del Pedido</h2>
                    <p>Administra los productos y sus presentaciones.</p>
                </div>
                <button
                    type="button"
                    class="btn btn-principal"
                    onclick="agregarProducto()"
                >
                    + Agregar Producto
                </button>
            </div>

            <table class="tabla">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Subtotal</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="productosBody">
                    <?php foreach ($detalles as $detalle): ?>
                        <tr>
                            <td>
                                <select
                                    name="producto_id[]"
                                    class="producto-select"
                                    onchange="actualizarProducto(this)"
                                    required
                                >
                                    <option value="">Seleccione producto</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option
                                            value="<?= (int)$producto['id_producto'] ?>"
                                            data-precio="<?= htmlspecialchars((string)$producto['precio'], ENT_QUOTES, 'UTF-8') ?>"
                                            <?= (int)$detalle['id_producto'] === (int)$producto['id_producto'] ? 'selected' : '' ?>
                                        >
                                            <?= htmlspecialchars(
                                                $producto['nombre'] . ' — ' . ($producto['presentacion'] ?: 'Única'),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input
                                    type="number"
                                    name="cantidad[]"
                                    class="cantidad"
                                    min="1"
                                    step="1"
                                    value="<?= max(1, (int)$detalle['cantidad']) ?>"
                                    onchange="actualizarFila(this)"
                                    oninput="actualizarFila(this)"
                                    required
                                >
                            </td>
                            <td>
                                <input
                                    type="text"
                                    class="precio"
                                    value="$<?= number_format((float)$detalle['precio'], 0, ',', '.') ?>"
                                    readonly
                                >
                            </td>
                            <td>
                                <input
                                    type="text"
                                    class="subtotal"
                                    value="$<?= number_format((float)$detalle['subtotal'], 0, ',', '.') ?>"
                                    readonly
                                >
                            </td>
                            <td class="acciones">
                                <button
                                    type="button"
                                    class="btn btn-eliminar"
                                    onclick="eliminarProducto(this)"
                                >
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="panel">
            <div class="panel-header">
                <div>
                    <h2>Resumen del Pedido</h2>
                    <p>Consulta los valores calculados del pedido.</p>
                </div>
            </div>

            <div class="grupo-formulario">
                <div class="campo">
                    <label for="subtotalVista">Subtotal</label>
                    <input type="text" id="subtotalVista" value="$0" readonly>
                </div>

                <div class="campo">
                    <label for="ivaVista">IVA (19 %)</label>
                    <input type="text" id="ivaVista" value="$0" readonly>
                </div>

                <div class="campo">
                    <label for="totalVista">Total</label>
                    <input type="text" id="totalVista" value="$0" readonly>
                </div>

                <div class="campo">
                    <label for="fecha_entrega">Fecha de Entrega</label>
                    <input
                        type="date"
                        name="fecha_entrega"
                        id="fecha_entrega"
                        value="<?= htmlspecialchars($fecha_entrega_actual, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="campo campo-completo">
                    <label for="observaciones">Observaciones</label>
                    <textarea
                        name="observaciones"
                        id="observaciones"
                        rows="5"
                    ><?= htmlspecialchars($observaciones_actuales, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="botones-formulario">
                <button type="submit" class="btn btn-guardar">Guardar Cambios</button>
                <button
                    type="button"
                    class="btn btn-limpiar"
                    onclick="restablecerFormulario()"
                >
                    Restablecer
                </button>
                <a href="Pedidos.php" class="btn btn-cancelar">Cancelar</a>
            </div>
        </section>
    </form>
</main>

<script>
const productos = <?= json_encode(
    $productos,
    JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_QUOT |
    JSON_HEX_AMP
) ?>;

const tbody = document.getElementById("productosBody");
const formulario = document.getElementById("formEditarPedido");
const productosOriginales = tbody.innerHTML;

const clienteRegistrado = document.getElementById("clienteRegistrado");
const clienteOcasional = document.getElementById("clienteOcasional");
const seccionRegistrado = document.getElementById("seccionRegistrado");
const seccionOcasional = document.getElementById("seccionOcasional");
const selectCliente = document.getElementById("id_cliente");
const nombreCliente = document.getElementById("nombre_cliente");

function cambiarTipoCliente() {
    const registrado = clienteRegistrado.checked;

    seccionRegistrado.style.display = registrado ? "" : "none";
    seccionOcasional.style.display = registrado ? "none" : "";

    selectCliente.required = registrado;
    nombreCliente.required = !registrado;

    selectCliente.disabled = !registrado;
    nombreCliente.disabled = registrado;
    document.getElementById("documento_cliente").disabled = registrado;
    document.getElementById("telefono_cliente").disabled = registrado;
    document.getElementById("correo_cliente").disabled = registrado;
}

clienteRegistrado.addEventListener("change", cambiarTipoCliente);
clienteOcasional.addEventListener("change", cambiarTipoCliente);

function formatoMoneda(valor) {
    return "$" + Math.round(valor).toLocaleString("es-CO");
}

function cargarOpciones(select, idSeleccionado = "") {
    select.replaceChildren();
    select.add(new Option("Seleccione producto", ""));

    productos.forEach(producto => {
        const presentacion = producto.presentacion || "Única";
        const opcion = new Option(
            `${producto.nombre} — ${presentacion}`,
            producto.id_producto
        );

        opcion.dataset.precio = producto.precio;

        if (String(producto.id_producto) === String(idSeleccionado)) {
            opcion.selected = true;
        }

        select.add(opcion);
    });
}

function agregarProducto() {
    const fila = document.createElement("tr");
    const celdaProducto = document.createElement("td");
    const select = document.createElement("select");

    select.name = "producto_id[]";
    select.className = "producto-select";
    select.required = true;
    cargarOpciones(select);
    select.addEventListener("change", () => actualizarProducto(select));
    celdaProducto.appendChild(select);

    const celdaCantidad = document.createElement("td");
    const cantidad = document.createElement("input");
    cantidad.type = "number";
    cantidad.name = "cantidad[]";
    cantidad.className = "cantidad";
    cantidad.min = "1";
    cantidad.step = "1";
    cantidad.value = "1";
    cantidad.required = true;
    cantidad.addEventListener("input", () => actualizarFila(cantidad));
    celdaCantidad.appendChild(cantidad);

    const celdaPrecio = document.createElement("td");
    const precio = document.createElement("input");
    precio.type = "text";
    precio.className = "precio";
    precio.value = "$0";
    precio.readOnly = true;
    celdaPrecio.appendChild(precio);

    const celdaSubtotal = document.createElement("td");
    const subtotal = document.createElement("input");
    subtotal.type = "text";
    subtotal.className = "subtotal";
    subtotal.value = "$0";
    subtotal.readOnly = true;
    celdaSubtotal.appendChild(subtotal);

    const celdaAcciones = document.createElement("td");
    celdaAcciones.className = "acciones";

    const boton = document.createElement("button");
    boton.type = "button";
    boton.className = "btn btn-eliminar";
    boton.textContent = "Eliminar";
    boton.addEventListener("click", () => eliminarProducto(boton));
    celdaAcciones.appendChild(boton);

    fila.append(celdaProducto, celdaCantidad, celdaPrecio, celdaSubtotal, celdaAcciones);
    tbody.appendChild(fila);
    calcularTotales();
}

function actualizarProducto(select) {
    actualizarFila(select);
}

function actualizarFila(elemento) {
    const fila = elemento.closest("tr");
    const select = fila.querySelector(".producto-select");
    const cantidadCampo = fila.querySelector(".cantidad");
    const opcion = select.options[select.selectedIndex];
    const precio = Number(opcion?.dataset.precio || 0);
    const cantidad = Math.max(0, Number(cantidadCampo.value || 0));

    fila.querySelector(".precio").value = formatoMoneda(precio);
    fila.querySelector(".subtotal").value = formatoMoneda(precio * cantidad);

    calcularTotales();
}

function eliminarProducto(boton) {
    if (tbody.querySelectorAll("tr").length <= 1) {
        alert("El pedido debe tener al menos un producto.");
        return;
    }

    if (confirm("¿Desea eliminar este producto?")) {
        boton.closest("tr").remove();
        calcularTotales();
    }
}

function calcularTotales() {
    let subtotalGeneral = 0;

    tbody.querySelectorAll("tr").forEach(fila => {
        const select = fila.querySelector(".producto-select");
        const cantidadCampo = fila.querySelector(".cantidad");
        const opcion = select.options[select.selectedIndex];
        const precio = Number(opcion?.dataset.precio || 0);
        const cantidad = Math.max(0, Number(cantidadCampo.value || 0));
        const subtotalFila = precio * cantidad;

        fila.querySelector(".precio").value = formatoMoneda(precio);
        fila.querySelector(".subtotal").value = formatoMoneda(subtotalFila);
        subtotalGeneral += subtotalFila;
    });

    const iva = subtotalGeneral * 0.19;

    document.getElementById("subtotalVista").value = formatoMoneda(subtotalGeneral);
    document.getElementById("ivaVista").value = formatoMoneda(iva);
    document.getElementById("totalVista").value = formatoMoneda(subtotalGeneral + iva);
}

function restablecerFormulario() {
    if (!confirm("¿Desea restablecer los cambios realizados?")) {
        return;
    }

    formulario.reset();
    tbody.innerHTML = productosOriginales;
    cambiarTipoCliente();

    tbody.querySelectorAll(".producto-select").forEach(select => {
        actualizarProducto(select);
    });

    calcularTotales();
}

formulario.addEventListener("submit", function(event) {
    const filas = tbody.querySelectorAll("tr");

    if (filas.length === 0) {
        event.preventDefault();
        alert("Debe existir al menos un producto.");
        return;
    }

    let valido = true;

    filas.forEach(fila => {
        const producto = fila.querySelector(".producto-select").value;
        const cantidad = Number(fila.querySelector(".cantidad").value);

        if (!producto || !Number.isInteger(cantidad) || cantidad <= 0) {
            valido = false;
        }
    });

    if (!valido) {
        event.preventDefault();
        alert("Seleccione los productos y verifique las cantidades.");
    }
});

document.addEventListener("DOMContentLoaded", function() {
    cambiarTipoCliente();

    tbody.querySelectorAll(".producto-select").forEach(select => {
        actualizarProducto(select);
    });

    calcularTotales();
});
</script>

<?php include("../includes/footer.php"); ?>
