<?php
$titulo = "NUEVO MOVIMIENTO";
$css = "Movimientos.css";

include("../../includes/header.php");
require_once("../../includes/conexion.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tipo = $_POST["tipo"] ?? "";
    $fecha = $_POST["fecha"] ?? "";
    $id_insumo = intval($_POST["id_insumo"] ?? 0);
    $cantidad = floatval($_POST["cantidad"] ?? 0);
    $responsable = trim($_POST["responsable"] ?? "");
    $estado = $_POST["estado"] ?? "Pendiente";
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

            $sql = "SELECT producto, stock, unidad
                    FROM insumos
                    WHERE id_insumo = ?
                    FOR UPDATE";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id_insumo);
            $stmt->execute();
            $insumo = $stmt->get_result()->fetch_assoc();

            if (!$insumo) {
                throw new Exception("El insumo seleccionado no existe.");
            }

            $stockActual = (float) $insumo["stock"];
            $unidad = $insumo["unidad"];

            if ($estado === "Completado" && $tipo === "Salida" && $cantidad > $stockActual) {
                throw new Exception(
                    "No hay suficiente stock para realizar esta salida. Stock disponible: "
                    . $stockActual . " " . $unidad
                );
            }

            $resultado = $conexion->query(
                "SELECT codigo FROM movimientos ORDER BY id_movimiento DESC LIMIT 1"
            );

            $numero = 1;

            if ($resultado && $resultado->num_rows > 0) {
                $ultimo = $resultado->fetch_assoc()["codigo"];
                preg_match("/(\d+)$/", $ultimo, $coincidencia);
                $numero = isset($coincidencia[1]) ? intval($coincidencia[1]) + 1 : 1;
            }

            $codigo = "MOV-" . str_pad($numero, 4, "0", STR_PAD_LEFT);

            $sql = "INSERT INTO movimientos
                    (codigo, fecha, tipo, id_insumo, cantidad, unidad, responsable, estado, observaciones)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param(
                "sssidssss",
                $codigo,
                $fecha,
                $tipo,
                $id_insumo,
                $cantidad,
                $unidad,
                $responsable,
                $estado,
                $observaciones
            );
            $stmt->execute();

            // Solo los movimientos completados modifican el stock.
            if ($estado === "Completado") {
                if ($tipo === "Entrada") {
                    $nuevoStock = $stockActual + $cantidad;
                } else {
                    $nuevoStock = $stockActual - $cantidad;
                }

                $estadoInsumo = $nuevoStock > 0 ? "Disponible" : "Bajo Stock";

                $sql = "UPDATE insumos
                        SET stock = ?, estado = ?
                        WHERE id_insumo = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("dsi", $nuevoStock, $estadoInsumo, $id_insumo);
                $stmt->execute();
            }

            $conexion->commit();

            header("Location: Movimientos.php");
            exit;
        } catch (Throwable $e) {
            $conexion->rollback();
            $error = $e->getMessage();
        }
    }
}

$sql = "SELECT id_insumo, codigo, producto, stock, unidad
        FROM insumos
        ORDER BY producto ASC";
$insumos = $conexion->query($sql);
?>

<main class="contenido">
    <section class="barra-superior">
        <a href="Movimientos.php" class="btn btn-azul">← Volver a Movimientos</a>
        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">⌂ Inicio</a>
    </section>

    <header class="encabezado">
        <h1>Nuevo Movimiento</h1>
        <p>Registre una nueva entrada o salida de inventario.</p>
    </header>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Registro del Movimiento</h2>
                <p>Complete la siguiente información.</p>
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
                        <option value="">Seleccione...</option>
                        <option value="Entrada" <?= (($_POST["tipo"] ?? "") === "Entrada") ? "selected" : "" ?>>Entrada</option>
                        <option value="Salida" <?= (($_POST["tipo"] ?? "") === "Salida") ? "selected" : "" ?>>Salida</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="fecha">Fecha</label>
                    <input type="date" id="fecha" name="fecha"
                           value="<?= htmlspecialchars($_POST["fecha"] ?? date("Y-m-d")) ?>" required>
                </div>

                <div class="campo">
                    <label for="insumo">Insumo</label>
                    <select id="insumo" name="id_insumo" required>
                        <option value="">Seleccione...</option>
                        <?php if ($insumos && $insumos->num_rows > 0): ?>
                            <?php while ($insumo = $insumos->fetch_assoc()): ?>
                                <option value="<?= $insumo["id_insumo"] ?>"
                                    <?= (intval($_POST["id_insumo"] ?? 0) === intval($insumo["id_insumo"])) ? "selected" : "" ?>>
                                    <?= htmlspecialchars($insumo["producto"]) ?> -
                                    Stock: <?= htmlspecialchars($insumo["stock"]) ?>
                                    <?= htmlspecialchars($insumo["unidad"]) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="campo">
                    <label for="cantidad">Cantidad</label>
                    <input type="number" id="cantidad" name="cantidad"
                           value="<?= htmlspecialchars($_POST["cantidad"] ?? "") ?>"
                           min="0.01" step="0.01" required>
                </div>

                <div class="campo">
                    <label for="unidad">Unidad</label>
                    <input type="text" id="unidad" readonly placeholder="Se obtiene del insumo">
                </div>

                <div class="campo">
                    <label for="responsable">Responsable</label>
                    <input type="text" id="responsable" name="responsable"
                           value="<?= htmlspecialchars($_POST["responsable"] ?? "") ?>"
                           placeholder="Administrador" required>
                </div>

                <div class="campo">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="Completado" <?= (($_POST["estado"] ?? "Completado") === "Completado") ? "selected" : "" ?>>Completado</option>
                        <option value="Pendiente" <?= (($_POST["estado"] ?? "") === "Pendiente") ? "selected" : "" ?>>Pendiente</option>
                    </select>
                </div>

                <div class="campo campo-completo">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" rows="4"
                              placeholder="Ingrese una observación..."><?= htmlspecialchars($_POST["observaciones"] ?? "") ?></textarea>
                </div>
            </div>

            <div class="botones-formulario">
                <button type="submit" class="btn btn-verde">Guardar</button>
                <button type="reset" class="btn">Limpiar</button>
                <a href="Movimientos.php" class="btn">Cancelar</a>
            </div>
        </form>
    </section>
</main>

<script>
const insumos = <?= json_encode((function() use ($conexion) {
    $datos = [];
    $resultado = $conexion->query("SELECT id_insumo, unidad FROM insumos");
    while ($fila = $resultado->fetch_assoc()) {
        $datos[$fila["id_insumo"]] = $fila["unidad"];
    }
    return $datos;
})()) ?>;

document.getElementById("insumo").addEventListener("change", function() {
    document.getElementById("unidad").value = insumos[this.value] || "";
});

document.getElementById("insumo").dispatchEvent(new Event("change"));
</script>

<?php include("../../includes/footer.php"); ?>