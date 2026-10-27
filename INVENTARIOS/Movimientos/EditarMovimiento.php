
<?php
$titulo = "EDITAR MOVIMIENTO";
$css = "Movimientos.css";

require_once("../../includes/conexion.php");

$id_movimiento = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id_movimiento || $id_movimiento < 1) {
    header("Location: Movimientos.php");
    exit;
}

$error = "";

$sql = "SELECT id_movimiento, codigo, fecha, tipo, id_insumo, cantidad, unidad, responsable, estado, observaciones, id_compra
        FROM movimientos
        WHERE id_movimiento = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_movimiento);
$stmt->execute();
$movimiento = $stmt->get_result()->fetch_assoc();

if (!$movimiento) {
    header("Location: Movimientos.php");
    exit;
}

// Los movimientos automáticos se administran desde Compras.
if ($movimiento["id_compra"] !== null) {
    echo "<script>
        alert(" . json_encode("Este movimiento pertenece a una compra. Edite la compra desde el módulo Compras para mantener el inventario sincronizado.") . ");
        window.location.href = 'Movimientos.php';
    </script>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tipo = $_POST["tipo"] ?? "";
    $fecha = $_POST["fecha"] ?? "";
    $id_insumo = (int) ($_POST["id_insumo"] ?? 0);
    $cantidad = (float) ($_POST["cantidad"] ?? 0);
    $responsable = trim($_POST["responsable"] ?? "");
    $estado = $_POST["estado"] ?? "";
    $observaciones = trim($_POST["observaciones"] ?? "");

    if ($tipo === "" || $fecha === "" || $id_insumo <= 0 || $cantidad <= 0 || $responsable === "") {
        $error = "Complete todos los campos obligatorios.";
    } elseif (!in_array($tipo, ["Entrada", "Salida"], true)) {
        $error = "El tipo de movimiento no es válido.";
    } elseif (!in_array($estado, ["Completado", "Pendiente"], true)) {
        $error = "El estado seleccionado no es válido.";
    } else {
        try {
            $conexion->begin_transaction();

            $sql = "SELECT id_insumo, tipo, cantidad, estado, id_compra
                    FROM movimientos
                    WHERE id_movimiento = ?
                    FOR UPDATE";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id_movimiento);
            $stmt->execute();
            $actual = $stmt->get_result()->fetch_assoc();

            if (!$actual) {
                throw new Exception("El movimiento no existe.");
            }

            if ($actual["id_compra"] !== null) {
                throw new Exception("Este movimiento pertenece a una compra. Debe modificarlo desde el módulo Compras.");
            }

            $id_insumo_anterior = (int) $actual["id_insumo"];
            $id_insumos = array_unique([$id_insumo_anterior, $id_insumo]);
            sort($id_insumos);

            $stocks = [];

            foreach ($id_insumos as $id) {
                $sql = "SELECT stock, unidad FROM insumos WHERE id_insumo = ? FOR UPDATE";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $fila = $stmt->get_result()->fetch_assoc();

                if (!$fila) {
                    throw new Exception("Uno de los insumos seleccionados no existe.");
                }

                $stocks[$id] = [
                    "stock" => (float) $fila["stock"],
                    "unidad" => $fila["unidad"]
                ];
            }

            // Revertir el movimiento anterior solo si estaba completado.
            if ($actual["estado"] === "Completado") {
                $cantidadAnterior = (float) $actual["cantidad"];

                if ($actual["tipo"] === "Entrada") {
                    $stocks[$id_insumo_anterior]["stock"] -= $cantidadAnterior;
                } else {
                    $stocks[$id_insumo_anterior]["stock"] += $cantidadAnterior;
                }

                if ($stocks[$id_insumo_anterior]["stock"] < -0.00001) {
                    throw new Exception("No se puede revertir el movimiento porque el stock resultante sería negativo.");
                }

                if ($stocks[$id_insumo_anterior]["stock"] < 0) {
                    $stocks[$id_insumo_anterior]["stock"] = 0;
                }
            }

            $stockNuevo = $stocks[$id_insumo]["stock"];
            $unidad = $stocks[$id_insumo]["unidad"];

            // Validar y aplicar el nuevo efecto solo si queda completado.
            if ($estado === "Completado") {
                if ($tipo === "Salida" && $cantidad > $stockNuevo) {
                    throw new Exception(
                        "No hay suficiente stock para realizar esta salida. Stock disponible: "
                        . $stockNuevo . " " . $unidad
                    );
                }

                if ($tipo === "Entrada") {
                    $stockNuevo += $cantidad;
                } else {
                    $stockNuevo -= $cantidad;
                }

                if ($stockNuevo < -0.00001) {
                    throw new Exception("El stock no puede quedar negativo.");
                }

                if ($stockNuevo < 0) {
                    $stockNuevo = 0;
                }
            }

            // Guardar los stocks finales afectados.
            foreach ($id_insumos as $id) {
                $stockFinal = $id === $id_insumo ? $stockNuevo : $stocks[$id]["stock"];
                $estadoInsumo = $stockFinal > 0 ? "Disponible" : "Bajo Stock";

                $sql = "UPDATE insumos SET stock = ?, estado = ? WHERE id_insumo = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("dsi", $stockFinal, $estadoInsumo, $id);
                $stmt->execute();
            }

            $sql = "UPDATE movimientos
                    SET fecha = ?, tipo = ?, id_insumo = ?, cantidad = ?, unidad = ?,
                        responsable = ?, estado = ?, observaciones = ?
                    WHERE id_movimiento = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param(
                "ssidssssi",
                $fecha,
                $tipo,
                $id_insumo,
                $cantidad,
                $unidad,
                $responsable,
                $estado,
                $observaciones,
                $id_movimiento
            );
            $stmt->execute();

            $conexion->commit();
            header("Location: Movimientos.php");
            exit;
        } catch (Throwable $e) {
            $conexion->rollback();
            $error = $e->getMessage();
        }
    }

    $movimiento["fecha"] = $fecha;
    $movimiento["tipo"] = $tipo;
    $movimiento["id_insumo"] = $id_insumo;
    $movimiento["cantidad"] = $cantidad;
    $movimiento["responsable"] = $responsable;
    $movimiento["estado"] = $estado;
    $movimiento["observaciones"] = $observaciones;
}

$sql = "SELECT id_insumo, producto, stock, unidad FROM insumos ORDER BY producto ASC";
$insumos = $conexion->query($sql);
?>

<?php include("../../includes/header.php"); ?>

<main class="contenido">
    <section class="barra-superior">
        <a href="Movimientos.php" class="btn btn-azul">← Volver a Movimientos</a>
        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">⌂ Inicio</a>
    </section>

    <header class="encabezado">
        <h1>Editar Movimiento</h1>
        <p>Modifique la información registrada del movimiento de inventario.</p>
    </header>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Información del Movimiento</h2>
                <p>Actualice los datos necesarios.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div style="margin:15px 0;padding:12px;background:#fdecea;color:#c0392b;border-radius:6px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form class="formulario" method="POST">
            <div class="grupo-formulario">
                <div class="campo">
                    <label for="tipo">Tipo de Movimiento</label>
                    <select id="tipo" name="tipo" required>
                        <option value="Entrada" <?= $movimiento["tipo"] === "Entrada" ? "selected" : "" ?>>Entrada</option>
                        <option value="Salida" <?= $movimiento["tipo"] === "Salida" ? "selected" : "" ?>>Salida</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="fecha">Fecha</label>
                    <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($movimiento["fecha"]) ?>" required>
                </div>

                <div class="campo">
                    <label for="insumo">Insumo</label>
                    <select id="insumo" name="id_insumo" required>
                        <option value="">Seleccione...</option>
                        <?php while ($insumo = $insumos->fetch_assoc()): ?>
                            <option value="<?= $insumo["id_insumo"] ?>" <?= (int) $movimiento["id_insumo"] === (int) $insumo["id_insumo"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($insumo["producto"]) ?> - Stock: <?= htmlspecialchars($insumo["stock"]) ?> <?= htmlspecialchars($insumo["unidad"]) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="cantidad">Cantidad</label>
                    <input type="number" id="cantidad" name="cantidad" value="<?= htmlspecialchars((string) $movimiento["cantidad"]) ?>" min="0.01" step="0.01" required>
                </div>

                <div class="campo">
                    <label for="unidad">Unidad</label>
                    <input type="text" id="unidad" value="<?= htmlspecialchars($movimiento["unidad"]) ?>" readonly>
                </div>

                <div class="campo">
                    <label for="responsable">Responsable</label>
                    <input type="text" id="responsable" name="responsable" value="<?= htmlspecialchars($movimiento["responsable"]) ?>" required>
                </div>

                <div class="campo">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" required>
                        <option value="Completado" <?= $movimiento["estado"] === "Completado" ? "selected" : "" ?>>Completado</option>
                        <option value="Pendiente" <?= $movimiento["estado"] === "Pendiente" ? "selected" : "" ?>>Pendiente</option>
                    </select>
                </div>

                <div class="campo campo-completo">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="4"><?= htmlspecialchars($movimiento["observaciones"] ?? "") ?></textarea>
                </div>
            </div>

            <div class="botones-formulario">
                <button type="submit" class="btn btn-verde">Actualizar</button>
                <button type="reset" class="btn">Limpiar</button>
                <a href="Movimientos.php" class="btn">Cancelar</a>
            </div>
        </form>
    </section>
</main>

<script>
const unidadesInsumos = <?= json_encode((function () use ($conexion) {
    $datos = [];
    $resultado = $conexion->query("SELECT id_insumo, unidad FROM insumos");
    while ($fila = $resultado->fetch_assoc()) {
        $datos[$fila["id_insumo"]] = $fila["unidad"];
    }
    return $datos;
})()) ?>;

document.getElementById("insumo").addEventListener("change", function () {
    document.getElementById("unidad").value = unidadesInsumos[this.value] || "";
});

document.getElementById("insumo").dispatchEvent(new Event("change"));
</script>

<?php include("../../includes/footer.php"); ?>

