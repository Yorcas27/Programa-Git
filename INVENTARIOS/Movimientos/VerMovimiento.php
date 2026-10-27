<?php
$titulo = "VER MOVIMIENTO";
$css = "Movimientos.css";

include("../../includes/header.php");
require_once("../../includes/conexion.php");

$id_movimiento = intval($_GET["id"] ?? 0);

if ($id_movimiento <= 0) {
    header("Location: Movimientos.php");
    exit;
}

// CONSULTAR MOVIMIENTO
$sql = "SELECT
            m.codigo,
            m.fecha,
            m.tipo,
            m.cantidad,
            m.unidad,
            m.responsable,
            m.estado,
            m.observaciones,
            i.producto AS insumo,
            c.nombre AS categoria
        FROM movimientos m
        INNER JOIN insumos i
            ON m.id_insumo = i.id_insumo
        INNER JOIN categorias c
            ON i.id_categoria = c.id_categoria
        WHERE m.id_movimiento = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_movimiento);
$stmt->execute();

$resultado = $stmt->get_result();
$movimiento = $resultado->fetch_assoc();

if (!$movimiento) {
    header("Location: Movimientos.php");
    exit;
}

// FORMATO DE CANTIDAD
$cantidad = rtrim(
    rtrim(number_format((float)$movimiento['cantidad'], 2, ',', '.'), '0'),
    ','
);
?>

<main class="contenido">

    <!-- BOTON VOLVER -->
    <section class="barra-superior">
        <a href="Movimientos.php" class="btn btn-azul">
            ← Volver a Movimientos
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>
    </section>

    <!-- ENCABEZADO -->
    <header class="encabezado">
        <h1>Detalle del Movimiento</h1>
        <p>
            Consulte la información registrada del movimiento de inventario.
        </p>
    </header>

    <!-- PANEL -->
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Información del Movimiento</h2>
                <p>Datos generales del movimiento registrado.</p>
            </div>
        </div>

        <!-- FORMULARIO -->
        <form class="formulario">
            <div class="grupo-formulario">

                <!-- Código -->
                <div class="campo">
                    <label for="codigo">Código</label>
                    <input
                        type="text"
                        id="codigo"
                        value="<?= htmlspecialchars($movimiento['codigo']) ?>"
                        readonly
                    >
                </div>

                <!-- Tipo -->
                <div class="campo">
                    <label for="tipo">Tipo de Movimiento</label>
                    <input
                        type="text"
                        id="tipo"
                        value="<?= htmlspecialchars($movimiento['tipo']) ?>"
                        readonly
                    >
                </div>

                <!-- Fecha -->
                <div class="campo">
                    <label for="fecha">Fecha</label>
                    <input
                        type="text"
                        id="fecha"
                        value="<?= date('d/m/Y', strtotime($movimiento['fecha'])) ?>"
                        readonly
                    >
                </div>

                <!-- Responsable -->
                <div class="campo">
                    <label for="responsable">Responsable</label>
                    <input
                        type="text"
                        id="responsable"
                        value="<?= htmlspecialchars($movimiento['responsable']) ?>"
                        readonly
                    >
                </div>

                <!-- Insumo -->
                <div class="campo">
                    <label for="insumo">Insumo</label>
                    <input
                        type="text"
                        id="insumo"
                        value="<?= htmlspecialchars($movimiento['insumo']) ?>"
                        readonly
                    >
                </div>

                <!-- Categoría -->
                <div class="campo">
                    <label for="categoria">Categoría</label>
                    <input
                        type="text"
                        id="categoria"
                        value="<?= htmlspecialchars($movimiento['categoria']) ?>"
                        readonly
                    >
                </div>

                <!-- Cantidad -->
                <div class="campo">
                    <label for="cantidad">Cantidad</label>
                    <input
                        type="text"
                        id="cantidad"
                        value="<?= htmlspecialchars($cantidad) ?>"
                        readonly
                    >
                </div>

                <!-- Unidad -->
                <div class="campo">
                    <label for="unidad">Unidad</label>
                    <input
                        type="text"
                        id="unidad"
                        value="<?= htmlspecialchars($movimiento['unidad']) ?>"
                        readonly
                    >
                </div>

                <!-- Estado -->
                <div class="campo">
                    <label for="estado">Estado</label>
                    <input
                        type="text"
                        id="estado"
                        value="<?= htmlspecialchars($movimiento['estado']) ?>"
                        readonly
                    >
                </div>

                <!-- Observaciones -->
                <div class="campo campo-completo">
                    <label for="observaciones">Observaciones</label>
                    <textarea
                        id="observaciones"
                        rows="4"
                        readonly
                    ><?= htmlspecialchars($movimiento['observaciones'] ?? '') ?></textarea>
                </div>

            </div>

            <!-- BOTÓN -->
            <div class="botones-formulario">
                <a href="Movimientos.php" class="btn btn-azul">
                    Volver
                </a>
            </div>
        </form>
    </section>

</main>

<?php
include("../../includes/footer.php");
?>