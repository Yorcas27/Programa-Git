
<?php
$titulo = "Reporte de Inventario";
$css = "../Reportes.css";

include("../../includes/config.php");
include("../../includes/conexion.php");
include("../../includes/header.php");

/* FORMATO DE CANTIDADES */
function formatoCantidad($valor) {
    return rtrim(
        rtrim(number_format((float)$valor, 2, ',', '.'), '0'),
        ','
    );
}

/* FILTROS EXISTENCIAS */
$buscar = trim($_GET["buscar"] ?? "");
$estado = $_GET["estado"] ?? "Todos";

/* FILTROS MOVIMIENTOS */
$desde = $_GET["desde"] ?? "";
$hasta = $_GET["hasta"] ?? "";
$tipo = $_GET["tipo"] ?? "Todos";
$estadoMovimiento = $_GET["estado_movimiento"] ?? "Todos";

/* VALIDAR ESTADO */
$estadosInsumo = ["Todos", "Disponible", "Bajo Stock"];

if (!in_array($estado, $estadosInsumo, true)) {
    $estado = "Todos";
}

/* VALIDAR TIPO */
$tiposMovimiento = ["Todos", "Entrada", "Salida"];

if (!in_array($tipo, $tiposMovimiento, true)) {
    $tipo = "Todos";
}

/* VALIDAR ESTADO MOVIMIENTO */
$estadosMovimiento = ["Todos", "Completado", "Pendiente"];

if (!in_array($estadoMovimiento, $estadosMovimiento, true)) {
    $estadoMovimiento = "Todos";
}

/* VALIDAR FECHAS */
if ($desde !== "" && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $desde)) {
    $desde = "";
}

if ($hasta !== "" && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $hasta)) {
    $hasta = "";
}

/* RESUMEN */
$totalInsumos = 0;
$totalDisponibles = 0;
$totalBajoStock = 0;
$totalMovimientos = 0;

$sqlResumen = "
    SELECT
        COUNT(*) AS total,
        SUM(estado = 'Disponible') AS disponibles,
        SUM(estado = 'Bajo Stock') AS bajo_stock
    FROM insumos
";

$resultadoResumen = $conexion->query($sqlResumen);

if ($resultadoResumen) {
    $resumen = $resultadoResumen->fetch_assoc();
    $totalInsumos = (int)($resumen["total"] ?? 0);
    $totalDisponibles = (int)($resumen["disponibles"] ?? 0);
    $totalBajoStock = (int)($resumen["bajo_stock"] ?? 0);
}

$sqlTotalMovimientos = "
    SELECT COUNT(*) AS total
    FROM movimientos
";

$resultadoTotalMovimientos = $conexion->query($sqlTotalMovimientos);

if ($resultadoTotalMovimientos) {
    $filaMovimientos = $resultadoTotalMovimientos->fetch_assoc();
    $totalMovimientos = (int)($filaMovimientos["total"] ?? 0);
}

/* EXISTENCIAS */
$sqlInventario = "
    SELECT
        i.id_inventario,
        i.id_insumo,
        i.stock_actual,
        i.fecha_actualizacion,
        s.codigo,
        s.producto,
        s.unidad,
        s.estado
    FROM inventario i
    INNER JOIN insumos s
        ON i.id_insumo = s.id_insumo
    WHERE 1=1
";

$tiposInventario = "";
$parametrosInventario = [];

if ($buscar !== "") {
    $sqlInventario .= "
        AND (
            s.codigo LIKE ?
            OR s.producto LIKE ?
        )
    ";

    $tiposInventario .= "ss";
    $busqueda = "%" . $buscar . "%";
    $parametrosInventario[] = $busqueda;
    $parametrosInventario[] = $busqueda;
}

if ($estado !== "Todos") {
    $sqlInventario .= " AND s.estado = ?";
    $tiposInventario .= "s";
    $parametrosInventario[] = $estado;
}

$sqlInventario .= " ORDER BY s.producto ASC";

$stmtInventario = $conexion->prepare($sqlInventario);
$resultadoInventario = false;

if ($stmtInventario) {
    if (!empty($parametrosInventario)) {
        $stmtInventario->bind_param(
            $tiposInventario,
            ...$parametrosInventario
        );
    }

    if ($stmtInventario->execute()) {
        $resultadoInventario = $stmtInventario->get_result();
    }
}

/* MOVIMIENTOS */
$sqlMovimientos = "
    SELECT
        m.id_movimiento,
        m.codigo,
        m.fecha,
        m.tipo,
        m.cantidad,
        m.unidad,
        m.responsable,
        m.estado,
        m.observaciones,
        s.producto
    FROM movimientos m
    INNER JOIN insumos s
        ON m.id_insumo = s.id_insumo
    WHERE 1=1
";

$tiposMovimientos = "";
$parametrosMovimientos = [];

if ($desde !== "") {
    $sqlMovimientos .= " AND m.fecha >= ?";
    $tiposMovimientos .= "s";
    $parametrosMovimientos[] = $desde;
}

if ($hasta !== "") {
    $sqlMovimientos .= "
        AND m.fecha < DATE_ADD(?, INTERVAL 1 DAY)
    ";

    $tiposMovimientos .= "s";
    $parametrosMovimientos[] = $hasta;
}

if ($tipo !== "Todos") {
    $sqlMovimientos .= " AND m.tipo = ?";
    $tiposMovimientos .= "s";
    $parametrosMovimientos[] = $tipo;
}

if ($estadoMovimiento !== "Todos") {
    $sqlMovimientos .= " AND m.estado = ?";
    $tiposMovimientos .= "s";
    $parametrosMovimientos[] = $estadoMovimiento;
}

$sqlMovimientos .= " ORDER BY m.fecha DESC, m.id_movimiento DESC";

$stmtMovimientos = $conexion->prepare($sqlMovimientos);
$resultadoMovimientos = false;

if ($stmtMovimientos) {
    if (!empty($parametrosMovimientos)) {
        $stmtMovimientos->bind_param(
            $tiposMovimientos,
            ...$parametrosMovimientos
        );
    }

    if ($stmtMovimientos->execute()) {
        $resultadoMovimientos = $stmtMovimientos->get_result();
    }
}
?>

<main class="contenido-interno">

    <!-- BOTONES SUPERIORES -->
    <div class="barra-superior">
        <a href="../Reportes.php" class="btn-regresar">
            ← Volver a Reportes
        </a>

        <a href="../../DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>
    </div>

    <!-- ENCABEZADO -->
    <header class="encabezado">
        <h1>Reporte de Inventario</h1>
        <p>Consulta de existencias y movimientos de inventario.</p>
    </header>

    <!-- RESUMEN -->
    <section class="cards">
        <div class="card">
            <h2><?= $totalInsumos ?></h2>
            <span>Total de Insumos</span>
        </div>

        <div class="card">
            <h2><?= $totalDisponibles ?></h2>
            <span>Disponibles</span>
        </div>

        <div class="card">
            <h2><?= $totalBajoStock ?></h2>
            <span>Bajo Stock</span>
        </div>

        <div class="card">
            <h2><?= $totalMovimientos ?></h2>
            <span>Movimientos</span>
        </div>
    </section>

    <!-- EXISTENCIAS -->
    <section class="panel">
        <div class="panel-header-inventario">
            <h2>Existencias Actuales</h2>
            <p>Consulta del inventario disponible.</p>
        </div>

        <!-- FILTROS EXISTENCIAS -->
        <form class="formulario" method="GET" action="ReporteInventario.php">
            <div class="grupo-formulario">
                <div class="campo">
                    <label for="buscar">Buscar</label>
                    <input
                        type="text"
                        id="buscar"
                        name="buscar"
                        value="<?= htmlspecialchars($buscar, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Código o nombre del insumo"
                    >
                </div>

                <div class="campo">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="Todos" <?= $estado === "Todos" ? "selected" : "" ?>>Todos</option>
                        <option value="Disponible" <?= $estado === "Disponible" ? "selected" : "" ?>>Disponible</option>
                        <option value="Bajo Stock" <?= $estado === "Bajo Stock" ? "selected" : "" ?>>Bajo Stock</option>
                    </select>
                </div>
            </div>

            <div class="acciones-formulario">
                <button type="submit" class="btn-consultar">Buscar</button>
                <a href="ReporteInventario.php" class="btn-consultar">Refrescar</a>
            </div>
        </form>

        <!-- RESULTADOS EXISTENCIAS -->
        <?php if ($resultadoInventario): ?>
            <?php if ($resultadoInventario->num_rows === 0): ?>
                <div class="mensaje-error">
                    No se encontraron insumos con los filtros seleccionados.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Insumo</th>
                            <th>Stock Actual</th>
                            <th>Unidad</th>
                            <th>Estado</th>
                            <th>Actualización</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($insumo = $resultadoInventario->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($insumo["codigo"], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($insumo["producto"], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= formatoCantidad($insumo["stock_actual"]) ?></td>
                                <td><?= htmlspecialchars($insumo["unidad"], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($insumo["estado"], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?= !empty($insumo["fecha_actualizacion"])
                                        ? date("d/m/Y H:i", strtotime($insumo["fecha_actualizacion"]))
                                        : "" ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php else: ?>
            <div class="mensaje-error">
                No fue posible consultar el inventario.
            </div>
        <?php endif; ?>
    </section>

    <!-- MOVIMIENTOS -->
    <section class="panel">
        <div class="panel-header-inventario">
            <h2>Movimientos de Inventario</h2>
            <p>Historial de entradas y salidas registradas.</p>
        </div>

        <!-- FILTROS MOVIMIENTOS -->
        <form class="formulario" method="GET" action="ReporteInventario.php">
            <div class="grupo-formulario">
                <div class="campo">
                    <label for="desde">Desde</label>
                    <input
                        type="date"
                        id="desde"
                        name="desde"
                        value="<?= htmlspecialchars($desde, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="campo">
                    <label for="hasta">Hasta</label>
                    <input
                        type="date"
                        id="hasta"
                        name="hasta"
                        value="<?= htmlspecialchars($hasta, ENT_QUOTES, 'UTF-8') ?>"
                    >
                </div>

                <div class="campo">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo">
                        <option value="Todos" <?= $tipo === "Todos" ? "selected" : "" ?>>Todos</option>
                        <option value="Entrada" <?= $tipo === "Entrada" ? "selected" : "" ?>>Entrada</option>
                        <option value="Salida" <?= $tipo === "Salida" ? "selected" : "" ?>>Salida</option>
                    </select>
                </div>

                <div class="campo">
                    <label for="estado_movimiento">Estado</label>
                    <select id="estado_movimiento" name="estado_movimiento">
                        <option value="Todos" <?= $estadoMovimiento === "Todos" ? "selected" : "" ?>>Todos</option>
                        <option value="Completado" <?= $estadoMovimiento === "Completado" ? "selected" : "" ?>>Completado</option>
                        <option value="Pendiente" <?= $estadoMovimiento === "Pendiente" ? "selected" : "" ?>>Pendiente</option>
                    </select>
                </div>
            </div>

            <div class="acciones-formulario">
                <button type="submit" class="btn-consultar">Buscar</button>
                <a href="ReporteInventario.php" class="btn-consultar">Refrescar</a>
            </div>
        </form>

        <!-- RESULTADOS MOVIMIENTOS -->
        <?php if ($resultadoMovimientos): ?>
            <?php if ($resultadoMovimientos->num_rows === 0): ?>
                <div class="mensaje-error">
                    No se encontraron movimientos con los filtros seleccionados.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Insumo</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Unidad</th>
                            <th>Responsable</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($movimiento = $resultadoMovimientos->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($movimiento["codigo"], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?= !empty($movimiento["fecha"])
                                        ? date("d/m/Y", strtotime($movimiento["fecha"]))
                                        : "" ?>
                                </td>
                                <td><?= htmlspecialchars($movimiento["producto"], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($movimiento["tipo"], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= formatoCantidad($movimiento["cantidad"]) ?></td>
                                <td><?= htmlspecialchars($movimiento["unidad"], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($movimiento["responsable"], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($movimiento["estado"], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php else: ?>
            <div class="mensaje-error">
                No fue posible consultar los movimientos.
            </div>
        <?php endif; ?>

        <!-- EXPORTACIONES -->
        <div class="acciones-formulario">
            <a
                href="ExportarInventarioPDF.php?buscar=<?= urlencode($buscar) ?>&estado=<?= urlencode($estado) ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&tipo=<?= urlencode($tipo) ?>&estado_movimiento=<?= urlencode($estadoMovimiento) ?>"
                class="btn-exportar"
                onclick="return confirmarExportacion('PDF');"
            >
                Exportar PDF
            </a>

            <a
                href="ExportarInventarioExcel.php?buscar=<?= urlencode($buscar) ?>&estado=<?= urlencode($estado) ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&tipo=<?= urlencode($tipo) ?>&estado_movimiento=<?= urlencode($estadoMovimiento) ?>"
                class="btn-exportar"
                onclick="return confirmarExportacion('Excel');"
            >
                Exportar Excel
            </a>
        </div>
    </section>
</main>

<script>
function confirmarExportacion(formato) {
    return confirm(
        "¿Deseas exportar el reporte de inventario en " + formato + "?"
    );
}
</script>

<?php include("../../includes/footer.php"); ?>
