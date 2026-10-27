<?php
$titulo = "Nueva Compra";
$css = "Compras.css";

require_once "../../includes/conexion.php";

$error = "";

$proveedores = $conexion->query(
    "SELECT id_proveedor, codigo, nombre
     FROM proveedores
     WHERE estado = 'Activo'
     ORDER BY nombre"
);

$insumos = $conexion->query(
    "SELECT id_insumo, codigo, producto, unidad
     FROM insumos
     WHERE estado = 'Disponible'
     ORDER BY producto"
);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tipo_proveedor = $_POST["tipo_proveedor"] ?? "Registrado";
    $id_proveedor = (int) ($_POST["id_proveedor"] ?? 0);
    $nombre_proveedor_casual = trim($_POST["nombre_proveedor_casual"] ?? "");
    $documento_proveedor_casual = trim($_POST["documento_proveedor_casual"] ?? "");
    $tipo_compra = $_POST["tipo_compra"] ?? "Insumos";
    $fecha = $_POST["fecha"] ?? date("Y-m-d");
    $estado = $_POST["estado"] ?? "Pendiente";
    $observaciones = trim($_POST["observaciones"] ?? "");

    if (!in_array($tipo_proveedor, ["Registrado", "Casual"], true)) {
        $error = "El tipo de proveedor no es válido.";
    } elseif ($tipo_proveedor === "Registrado" && $id_proveedor <= 0) {
        $error = "Selecciona un proveedor registrado.";
    } elseif ($tipo_proveedor === "Casual" && $nombre_proveedor_casual === "") {
        $error = "Ingresa el nombre del proveedor casual.";
    } elseif (!in_array($tipo_compra, ["Insumos", "Otros"], true)) {
        $error = "El tipo de compra no es válido.";
    } elseif (!in_array($estado, ["Pendiente", "Recibida"], true)) {
        $error = "El estado de la compra no es válido.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $error = "La fecha de la compra no es válida.";
    } else {
        $detalles = [];
        $subtotal = 0;

        if ($tipo_compra === "Insumos") {
            $ids_insumo = $_POST["id_insumo"] ?? [];
            $cantidades = $_POST["cantidad"] ?? [];
            $precios = $_POST["precio"] ?? [];

            for ($i = 0; $i < count($ids_insumo); $i++) {
                $id_insumo = (int) ($ids_insumo[$i] ?? 0);
                $cantidad = (float) ($cantidades[$i] ?? 0);
                $precioTexto = str_replace(".", "", (string) ($precios[$i] ?? "0"));
                $precio = (float) $precioTexto;

                if ($id_insumo <= 0 || $cantidad <= 0 || $precio < 0) {
                    continue;
                }

                $valor = $cantidad * $precio;
                $subtotal += $valor;

                $detalles[] = [
                    "id_insumo" => $id_insumo,
                    "cantidad" => $cantidad,
                    "precio" => $precio,
                    "subtotal" => $valor
                ];
            }

            if (empty($detalles)) {
                $error = "Los datos de los insumos no son válidos.";
            }
        } else {
            $conceptos = $_POST["concepto"] ?? [];
            $descripciones = $_POST["descripcion"] ?? [];
            $cantidades = $_POST["cantidad_otro"] ?? [];
            $precios = $_POST["precio_otro"] ?? [];

            for ($i = 0; $i < count($conceptos); $i++) {
                $concepto = trim($conceptos[$i] ?? "");
                $descripcion = trim($descripciones[$i] ?? "");
                $cantidad = (float) ($cantidades[$i] ?? 0);
                $precioTexto = str_replace(".", "", (string) ($precios[$i] ?? "0"));
                $precio = (float) $precioTexto;

                if ($concepto === "" || $cantidad <= 0 || $precio < 0) {
                    continue;
                }

                $valor = $cantidad * $precio;
                $subtotal += $valor;

                $detalles[] = [
                    "concepto" => $concepto,
                    "descripcion" => $descripcion,
                    "cantidad" => $cantidad,
                    "precio" => $precio,
                    "subtotal" => $valor
                ];
            }

            if (empty($detalles)) {
                $error = "Los datos de los otros conceptos no son válidos.";
            }
        }

        if ($error === "") {
            $iva = $subtotal * 0.19;
            $total = $subtotal + $iva;

            try {
                $conexion->begin_transaction();

                if ($tipo_proveedor === "Registrado") {
                    $stmtProveedor = $conexion->prepare(
                        "SELECT id_proveedor
                         FROM proveedores
                         WHERE id_proveedor = ? AND estado = 'Activo'
                         FOR UPDATE"
                    );
                    $stmtProveedor->bind_param("i", $id_proveedor);
                    $stmtProveedor->execute();

                    if (!$stmtProveedor->get_result()->fetch_assoc()) {
                        throw new Exception("El proveedor seleccionado no existe o está inactivo.");
                    }

                    $id_proveedor_insertar = $id_proveedor;
                    $nombre_casual_insertar = null;
                    $documento_casual_insertar = null;
                } else {
                    $id_proveedor_insertar = null;
                    $nombre_casual_insertar = $nombre_proveedor_casual;
                    $documento_casual_insertar =
                        $documento_proveedor_casual !== ""
                        ? $documento_proveedor_casual
                        : null;
                }

                // Validar y bloquear los insumos antes de registrar la compra.
                if ($tipo_compra === "Insumos") {
                    $idsValidar = array_unique(
                        array_column($detalles, "id_insumo")
                    );
                    sort($idsValidar);

                    foreach ($idsValidar as $idValidar) {
                        $stmtValidar = $conexion->prepare(
                            "SELECT id_insumo
                             FROM insumos
                             WHERE id_insumo = ?
                             FOR UPDATE"
                        );
                        $stmtValidar->bind_param("i", $idValidar);
                        $stmtValidar->execute();

                        if (!$stmtValidar->get_result()->fetch_assoc()) {
                            throw new Exception("Uno de los insumos seleccionados no existe.");
                        }
                    }
                }

                // Generar código de compra.
                $resultado = $conexion->query(
                    "SELECT COUNT(*) AS total FROM compras"
                );
                $numero = (int) $resultado->fetch_assoc()["total"] + 1;
                $codigo = "COM" . str_pad($numero, 3, "0", STR_PAD_LEFT);

                // Registrar la compra.
                $stmt = $conexion->prepare(
                    "INSERT INTO compras
                    (
                        codigo_compra, tipo_proveedor, id_proveedor,
                        nombre_proveedor_casual, documento_proveedor_casual,
                        tipo_compra, fecha, subtotal, iva, total, estado, observaciones
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "ssissssdddss",
                    $codigo,
                    $tipo_proveedor,
                    $id_proveedor_insertar,
                    $nombre_casual_insertar,
                    $documento_casual_insertar,
                    $tipo_compra,
                    $fecha,
                    $subtotal,
                    $iva,
                    $total,
                    $estado,
                    $observaciones
                );
                $stmt->execute();

                $id_compra = $conexion->insert_id;

                if ($tipo_compra === "Insumos") {
                    $stmtDetalle = $conexion->prepare(
                        "INSERT INTO detalle_compra
                        (id_compra, id_insumo, cantidad, precio, subtotal)
                        VALUES (?, ?, ?, ?, ?)"
                    );

                    // Código de responsable identificable para la compra automática.
                    $responsable = "Compra " . $codigo;
                    $observacionesMovimiento = "Generado automáticamente desde la compra " . $codigo;

                    if ($observaciones !== "") {
                        $observacionesMovimiento .= ". " . $observaciones;
                    }

                    // El código de movimiento se incrementa por cada detalle.
                    $resultadoMov = $conexion->query(
                        "SELECT COALESCE(MAX(id_movimiento), 0) AS ultimo
                         FROM movimientos"
                    );
                    $numeroMovimiento = (int) $resultadoMov->fetch_assoc()["ultimo"] + 1;

                    foreach ($detalles as $detalle) {
                        $id_detalle_insumo = (int) $detalle["id_insumo"];
                        $cantidadDetalle = (float) $detalle["cantidad"];
                        $precioDetalle = (float) $detalle["precio"];
                        $subtotalDetalle = (float) $detalle["subtotal"];

                        $stmtDetalle->bind_param(
                            "iiddd",
                            $id_compra,
                            $id_detalle_insumo,
                            $cantidadDetalle,
                            $precioDetalle,
                            $subtotalDetalle
                        );
                        $stmtDetalle->execute();

                        // Pendiente no afecta el stock; Recibida sí lo aumenta.
                        $estadoMovimiento = $estado === "Recibida"
                            ? "Completado"
                            : "Pendiente";

                        $stmtInsumo = $conexion->prepare(
                            "SELECT unidad, stock
                             FROM insumos
                             WHERE id_insumo = ?
                             FOR UPDATE"
                        );
                        $stmtInsumo->bind_param("i", $id_detalle_insumo);
                        $stmtInsumo->execute();
                        $datosInsumo = $stmtInsumo->get_result()->fetch_assoc();

                        if (!$datosInsumo) {
                            throw new Exception("No se encontró uno de los insumos de la compra.");
                        }

                        $unidad = $datosInsumo["unidad"];

                        // Crear movimiento vinculado a la compra.
                        $codigoMovimiento = "MOV-" . str_pad(
                            $numeroMovimiento,
                            4,
                            "0",
                            STR_PAD_LEFT
                        );
                        $numeroMovimiento++;

                        $tipoMovimiento = "Entrada";

                        $stmtMovimiento = $conexion->prepare(
                            "INSERT INTO movimientos
                            (
                                id_compra, codigo, fecha, tipo, id_insumo,
                                cantidad, unidad, responsable, estado, observaciones
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                        );

                        $stmtMovimiento->bind_param(
                            "isssidssss",
                            $id_compra,
                            $codigoMovimiento,
                            $fecha,
                            $tipoMovimiento,
                            $id_detalle_insumo,
                            $cantidadDetalle,
                            $unidad,
                            $responsable,
                            $estadoMovimiento,
                            $observacionesMovimiento
                        );
                        $stmtMovimiento->execute();

                        // Solo una compra recibida aumenta el inventario.
                        if ($estado === "Recibida") {
                            $stockActual = (float) $datosInsumo["stock"];
                            $nuevoStock = $stockActual + $cantidadDetalle;
                            $estadoInsumo = $nuevoStock > 0
                                ? "Disponible"
                                : "Bajo Stock";

                            $stmtStock = $conexion->prepare(
                                "UPDATE insumos
                                 SET stock = ?, estado = ?
                                 WHERE id_insumo = ?"
                            );
                            $stmtStock->bind_param(
                                "dsi",
                                $nuevoStock,
                                $estadoInsumo,
                                $id_detalle_insumo
                            );
                            $stmtStock->execute();
                        }
                    }
                } else {
                    // Las compras de Otros no generan movimientos de inventario.
                    $stmtDetalleOtro = $conexion->prepare(
                        "INSERT INTO detalle_compra_otro
                        (id_compra, concepto, descripcion, cantidad, precio, subtotal)
                        VALUES (?, ?, ?, ?, ?, ?)"
                    );

                    foreach ($detalles as $detalle) {
                        $stmtDetalleOtro->bind_param(
                            "issddd",
                            $id_compra,
                            $detalle["concepto"],
                            $detalle["descripcion"],
                            $detalle["cantidad"],
                            $detalle["precio"],
                            $detalle["subtotal"]
                        );
                        $stmtDetalleOtro->execute();
                    }
                }

                $conexion->commit();

                header("Location: Compras.php");
                exit;
            } catch (Throwable $e) {
                $conexion->rollback();
                $error = "No fue posible guardar la compra: " . $e->getMessage();
            }
        }
    }
}

require_once "../../includes/header.php";
?>

<div class="contenedor">
    <div class="navegacion">
        <a href="../INVENTARIOS/Inventarios.php" class="btn-volver">← Volver a Inventarios</a>
        <a href="../../index.php" class="btn-inicio">⌂ Inicio</a>
    </div>

    <div class="encabezado">
        <h1>Nueva Compra</h1>
        <p>Registra una nueva compra de insumos u otros conceptos.</p>
    </div>

    <?php if ($error !== ""): ?>
        <div class="mensaje-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="formulario">
        <div class="panel">
            <h2>Datos de la Compra</h2>

            <div class="grupo-formulario">
                <div class="campo">
                    <label>Tipo de proveedor</label>
                    <select name="tipo_proveedor" id="tipoProveedor" onchange="cambiarTipoProveedor()" required>
                        <option value="Registrado" <?= ($_POST["tipo_proveedor"] ?? "Registrado") === "Registrado" ? "selected" : "" ?>>Proveedor registrado</option>
                        <option value="Casual" <?= ($_POST["tipo_proveedor"] ?? "") === "Casual" ? "selected" : "" ?>>Proveedor casual</option>
                    </select>
                </div>

                <div class="campo">
                    <label>Tipo de compra</label>
                    <select name="tipo_compra" id="tipoCompra" onchange="cambiarTipoCompra()" required>
                        <option value="Insumos" <?= ($_POST["tipo_compra"] ?? "Insumos") === "Insumos" ? "selected" : "" ?>>Compra de insumos</option>
                        <option value="Otros" <?= ($_POST["tipo_compra"] ?? "") === "Otros" ? "selected" : "" ?>>Otros conceptos</option>
                    </select>
                </div>

                <div class="campo" id="campoProveedorRegistrado">
                    <label>Proveedor</label>
                    <select name="id_proveedor" id="idProveedor">
                        <option value="">Seleccionar proveedor</option>
                        <?php
                        $proveedores->data_seek(0);
                        while ($proveedor = $proveedores->fetch_assoc()):
                        ?>
                            <option value="<?= $proveedor["id_proveedor"] ?>" <?= (int) ($_POST["id_proveedor"] ?? 0) === (int) $proveedor["id_proveedor"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($proveedor["codigo"]) ?> - <?= htmlspecialchars($proveedor["nombre"]) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="campo" id="campoProveedorCasual" style="display:none;">
                    <label>Nombre del proveedor</label>
                    <input type="text" name="nombre_proveedor_casual" id="nombreProveedorCasual" placeholder="Nombre del proveedor" value="<?= htmlspecialchars($_POST["nombre_proveedor_casual"] ?? "") ?>" disabled>
                </div>

                <div class="campo" id="campoDocumentoCasual" style="display:none;">
                    <label>Documento</label>
                    <input type="text" name="documento_proveedor_casual" id="documentoProveedorCasual" placeholder="Documento opcional" value="<?= htmlspecialchars($_POST["documento_proveedor_casual"] ?? "") ?>" disabled>
                </div>

                <div class="campo">
                    <label>Fecha</label>
                    <input type="date" name="fecha" value="<?= htmlspecialchars($_POST["fecha"] ?? date("Y-m-d")) ?>" required>
                </div>

                <div class="campo">
                    <label>Estado</label>
                    <select name="estado" required>
                        <option value="Pendiente" <?= ($_POST["estado"] ?? "Pendiente") === "Pendiente" ? "selected" : "" ?>>Pendiente</option>
                        <option value="Recibida" <?= ($_POST["estado"] ?? "") === "Recibida" ? "selected" : "" ?>>Recibida</option>
                    </select>
                </div>

                <div class="campo campo-completo">
                    <label>Observaciones</label>
                    <textarea name="observaciones" placeholder="Observaciones de la compra..."><?= htmlspecialchars($_POST["observaciones"] ?? "") ?></textarea>
                </div>
            </div>
        </div>

        <div class="panel" id="panelInsumos">
            <div class="panel-header">
                <div>
                    <h2>Detalle de Compra</h2>
                    <p>Agrega los insumos adquiridos.</p>
                </div>
                <button type="button" class="btn btn-nuevo" onclick="agregarFilaInsumo()">+ Agregar Insumo</button>
            </div>

            <div class="tabla-contenedor">
                <table id="tablaDetalle">
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="detalleBody">
                        <tr>
                            <td>
                                <select name="id_insumo[]" required>
                                    <option value="">Seleccionar</option>
                                    <?php
                                    $insumos->data_seek(0);
                                    while ($insumo = $insumos->fetch_assoc()):
                                    ?>
                                        <option value="<?= $insumo["id_insumo"] ?>" <?= (string) ($_POST["id_insumo"][0] ?? "") === (string) $insumo["id_insumo"] ? "selected" : "" ?>>
                                            <?= htmlspecialchars($insumo["producto"]) ?> (<?= htmlspecialchars($insumo["unidad"]) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="cantidad[]" min="0.01" step="0.01" value="<?= htmlspecialchars($_POST["cantidad"][0] ?? "1") ?>" oninput="calcularInsumos()" required>
                            </td>
                            <td>
                                <input type="text" name="precio[]" class="precio-colombiano" inputmode="numeric" value="<?= htmlspecialchars($_POST["precio"][0] ?? "0") ?>" oninput="formatearPrecio(this); calcularInsumos()" required>
                            </td>
                            <td class="subtotal">$0</td>
                            <td>
                                <button type="button" class="btn-quitar" onclick="eliminarFila(this)">×</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel" id="panelOtros" style="display:none;">
            <div class="panel-header">
                <div>
                    <h2>Detalle de Compra</h2>
                    <p>Registra gastos o adquisiciones que no pertenecen al inventario.</p>
                </div>
                <button type="button" class="btn btn-nuevo" onclick="agregarFilaOtro()">+ Agregar Concepto</button>
            </div>

            <div class="tabla-contenedor">
                <table id="tablaOtros">
                    <thead>
                        <tr>
                            <th>Concepto</th>
                            <th>Descripción</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="detalleOtrosBody">
                        <tr>
                            <td><input type="text" name="concepto[]" placeholder="Ej. Mantenimiento" disabled></td>
                            <td><input type="text" name="descripcion[]" placeholder="Descripción" disabled></td>
                            <td><input type="number" name="cantidad_otro[]" min="0.01" step="0.01" value="1" oninput="calcularOtros()" disabled></td>
                            <td><input type="text" name="precio_otro[]" class="precio-colombiano" inputmode="numeric" value="0" oninput="formatearPrecio(this); calcularOtros()" disabled></td>
                            <td class="subtotal-otro">$0</td>
                            <td><button type="button" class="btn-quitar" onclick="eliminarFila(this)">×</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="totales">
                <div><span>Subtotal</span><strong id="subtotalGeneral">$0</strong></div>
                <div><span>IVA 19%</span><strong id="ivaGeneral">$0</strong></div>
                <div class="total"><span>TOTAL</span><strong id="totalGeneral">$0</strong></div>
            </div>
        </div>

        <div class="botones-formulario">
            <a href="Compras.php" class="btn btn-cancelar">Cancelar</a>
            <button type="submit" class="btn btn-guardar">Guardar Compra</button>
        </div>
    </form>
</div>

<script>
function obtenerNumero(valor) {
    return parseFloat(String(valor).replace(/\./g, "")) || 0;
}

function formatearPrecio(campo) {
    const valor = campo.value.replace(/\D/g, "");
    campo.value = valor === "" ? "" : Number(valor).toLocaleString("es-CO");
}

function formato(valor) {
    return "$" + Math.round(valor).toLocaleString("es-CO");
}

function cambiarTipoProveedor() {
    const tipo = document.getElementById("tipoProveedor").value;
    const registrado = document.getElementById("campoProveedorRegistrado");
    const casual = document.getElementById("campoProveedorCasual");
    const documento = document.getElementById("campoDocumentoCasual");
    const selectProveedor = document.getElementById("idProveedor");
    const nombreCasual = document.getElementById("nombreProveedorCasual");
    const documentoCasual = document.getElementById("documentoProveedorCasual");

    if (tipo === "Registrado") {
        registrado.style.display = "";
        casual.style.display = "none";
        documento.style.display = "none";
        selectProveedor.disabled = false;
        selectProveedor.required = true;
        nombreCasual.disabled = true;
        nombreCasual.required = false;
        documentoCasual.disabled = true;
    } else {
        registrado.style.display = "none";
        casual.style.display = "";
        documento.style.display = "";
        selectProveedor.disabled = true;
        selectProveedor.required = false;
        selectProveedor.value = "";
        nombreCasual.disabled = false;
        nombreCasual.required = true;
        documentoCasual.disabled = false;
    }
}

function cambiarTipoCompra() {
    const tipo = document.getElementById("tipoCompra").value;
    const panelInsumos = document.getElementById("panelInsumos");
    const panelOtros = document.getElementById("panelOtros");
    const camposInsumos = document.querySelectorAll("#detalleBody input, #detalleBody select");
    const camposOtros = document.querySelectorAll("#detalleOtrosBody input");

    if (tipo === "Insumos") {
        panelInsumos.style.display = "";
        panelOtros.style.display = "none";
        camposInsumos.forEach(campo => campo.disabled = false);
        camposOtros.forEach(campo => campo.disabled = true);
        calcularInsumos();
    } else {
        panelInsumos.style.display = "none";
        panelOtros.style.display = "";
        camposInsumos.forEach(campo => campo.disabled = true);
        camposOtros.forEach(campo => campo.disabled = false);
        calcularOtros();
    }
}

function calcularInsumos() {
    if (document.getElementById("tipoCompra").value !== "Insumos") return;

    let subtotalGeneral = 0;

    document.querySelectorAll("#detalleBody tr").forEach(fila => {
        const cantidadCampo = fila.querySelector('[name="cantidad[]"]');
        const precioCampo = fila.querySelector('[name="precio[]"]');
        if (!cantidadCampo || !precioCampo) return;

        const subtotal = (parseFloat(cantidadCampo.value) || 0) * obtenerNumero(precioCampo.value);
        subtotalGeneral += subtotal;
        fila.querySelector(".subtotal").textContent = formato(subtotal);
    });

    actualizarTotales(subtotalGeneral);
}

function calcularOtros() {
    if (document.getElementById("tipoCompra").value !== "Otros") return;

    let subtotalGeneral = 0;

    document.querySelectorAll("#detalleOtrosBody tr").forEach(fila => {
        const cantidadCampo = fila.querySelector('[name="cantidad_otro[]"]');
        const precioCampo = fila.querySelector('[name="precio_otro[]"]');
        if (!cantidadCampo || !precioCampo) return;

        const subtotal = (parseFloat(cantidadCampo.value) || 0) * obtenerNumero(precioCampo.value);
        subtotalGeneral += subtotal;
        fila.querySelector(".subtotal-otro").textContent = formato(subtotal);
    });

    actualizarTotales(subtotalGeneral);
}

function actualizarTotales(subtotal) {
    const iva = subtotal * 0.19;
    document.getElementById("subtotalGeneral").textContent = formato(subtotal);
    document.getElementById("ivaGeneral").textContent = formato(iva);
    document.getElementById("totalGeneral").textContent = formato(subtotal + iva);
}

function agregarFilaInsumo() {
    const tbody = document.getElementById("detalleBody");
    const nuevaFila = tbody.querySelector("tr").cloneNode(true);
    nuevaFila.querySelector("select").selectedIndex = 0;
    nuevaFila.querySelector('[name="cantidad[]"]').value = "1";
    nuevaFila.querySelector('[name="precio[]"]').value = "0";
    nuevaFila.querySelector(".subtotal").textContent = "$0";
    nuevaFila.querySelectorAll("input, select").forEach(campo => campo.disabled = false);
    tbody.appendChild(nuevaFila);
}

function agregarFilaOtro() {
    const tbody = document.getElementById("detalleOtrosBody");
    const nuevaFila = tbody.querySelector("tr").cloneNode(true);
    nuevaFila.querySelector('[name="concepto[]"]').value = "";
    nuevaFila.querySelector('[name="descripcion[]"]').value = "";
    nuevaFila.querySelector('[name="cantidad_otro[]"]').value = "1";
    nuevaFila.querySelector('[name="precio_otro[]"]').value = "0";
    nuevaFila.querySelector(".subtotal-otro").textContent = "$0";
    nuevaFila.querySelectorAll("input").forEach(campo => campo.disabled = false);
    tbody.appendChild(nuevaFila);
}

function eliminarFila(boton) {
    const fila = boton.closest("tr");
    const tbody = fila.closest("tbody");

    if (tbody.querySelectorAll("tr").length > 1) {
        fila.remove();
        tbody.id === "detalleBody" ? calcularInsumos() : calcularOtros();
    }
}

document.querySelector(".formulario").addEventListener("submit", function() {
    document.querySelectorAll(".precio-colombiano").forEach(campo => {
        if (!campo.disabled) campo.value = campo.value.replace(/\./g, "");
    });
});

document.addEventListener("DOMContentLoaded", function() {
    cambiarTipoProveedor();
    cambiarTipoCompra();
    calcularInsumos();
});
</script>

<?php require_once "../../includes/footer.php"; ?>