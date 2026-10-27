<?php
$titulo = "VER RECETA";
$css = "Recetas.css";

include("../../includes/config.php");
include("../../includes/conexion.php");
include("../../includes/header.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$receta = null;
$insumos_receta = [];

if ($id > 0) {
    /* CONSULTAR RECETA */
    $stmt = $conexion->prepare(
        "SELECT
            r.id_receta,
            r.codigo,
            r.nombre,
            r.id_producto,
            COALESCE(p.nombre, r.producto) AS producto,
            COALESCE(cp.nombre, r.categoria) AS categoria,
            r.rendimiento,
            r.peso_batido,
            r.unidad_peso,
            r.molde,
            r.tiempo_preparacion,
            r.temperatura_horno,
            r.tiempo_horneado,
            r.unidad,
            r.tiempo,
            r.estado,
            r.descripcion,
            r.observaciones
        FROM recetas r
        LEFT JOIN productos p
            ON r.id_producto = p.id_producto
        LEFT JOIN categorias_producto cp
            ON p.id_categoria_producto = cp.id_categoria_producto
        WHERE r.id_receta = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $receta = $resultado->fetch_assoc();
    $stmt->close();

    /* CONSULTAR INSUMOS */
    if ($receta) {
        $stmt = $conexion->prepare(
            "SELECT
                dr.id_detalle_receta,
                dr.id_insumo,
                dr.cantidad,
                dr.unidad_medida,
                dr.observaciones,
                i.codigo,
                i.producto
            FROM detalle_receta dr
            INNER JOIN insumos i
                ON dr.id_insumo = i.id_insumo
            WHERE dr.id_receta = ?
            ORDER BY dr.id_detalle_receta ASC"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        while ($fila = $resultado->fetch_assoc()) {
            $insumos_receta[] = $fila;
        }

        $stmt->close();
    }
}

/* PESO POR UNIDAD */
$peso_por_unidad = null;
$unidad_peso = $receta['unidad_peso'] ?? 'g';

if (
    $receta &&
    $receta['peso_batido'] !== null &&
    $receta['peso_batido'] !== '' &&
    (int)$receta['rendimiento'] > 0
) {
    $peso_por_unidad =
        (float)$receta['peso_batido'] / (int)$receta['rendimiento'];
}

/* FORMATO NUMÉRICO SIN CEROS INNECESARIOS */
function formatoNumero($valor, $decimales = 3) {
    $numero = number_format(
        (float)$valor,
        $decimales,
        ',',
        '.'
    );

    if (strpos($numero, ',') !== false) {
        $numero = rtrim($numero, '0');
        $numero = rtrim($numero, ',');
    }

    return $numero;
}
?>

<main class="contenido">

    <!-- BARRA SUPERIOR -->
    <section class="barra-superior">
        <div>
            <a href="Recetas.php" class="btn btn-verde">
                ← Volver a Recetas
            </a>

            <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
                ⌂ Inicio
            </a>
        </div>
    </section>

    <!-- ENCABEZADO -->
    <header class="encabezado">
        <h1>Detalle de la Receta</h1>
        <p>
            Consulte la información registrada y calcule los ingredientes
            necesarios para preparar diferentes cantidades.
        </p>
    </header>

    <?php if ($receta): ?>

        <!-- INFORMACIÓN GENERAL -->
        <section class="panel">

            <div class="panel-header">
                <div>
                    <h2>Información de la Receta</h2>
                    <p>Datos generales de la receta registrada.</p>
                </div>
            </div>

            <div class="grupo-formulario">

                <div class="campo">
                    <label for="codigo">Código</label>
                    <input type="text" id="codigo"
                        value="<?= htmlspecialchars($receta['codigo'] ?? '') ?>"
                        readonly>
                </div>

                <div class="campo">
                    <label for="nombre">Nombre de la Receta</label>
                    <input type="text" id="nombre"
                        value="<?= htmlspecialchars($receta['nombre'] ?? '') ?>"
                        readonly>
                </div>

                <div class="campo">
                    <label for="producto">Producto Asociado</label>
                    <input type="text" id="producto"
                        value="<?= htmlspecialchars($receta['producto'] ?? '') ?>"
                        readonly>
                </div>

                <div class="campo">
                    <label for="categoria">Categoría</label>
                    <input type="text" id="categoria"
                        value="<?= htmlspecialchars($receta['categoria'] ?? '') ?>"
                        readonly>
                </div>

                <div class="campo">
                    <label for="rendimiento">Rendimiento original</label>
                    <input type="text" id="rendimiento"
                        value="<?= (int)$receta['rendimiento'] ?> unidades"
                        readonly>
                </div>

                <div class="campo">
                    <label for="peso_batido">Peso Total del Batido</label>
                    <input type="text" id="peso_batido"
                        value="<?= $receta['peso_batido'] !== null
                            ? formatoNumero($receta['peso_batido']) . ' ' . htmlspecialchars($unidad_peso)
                            : '' ?>"
                        readonly>
                </div>

                <div class="campo">
                    <label for="peso_por_unidad">Peso por Unidad</label>
                    <input type="text" id="peso_por_unidad"
                        value="<?= $peso_por_unidad !== null
                            ? formatoNumero($peso_por_unidad) . ' ' . htmlspecialchars($unidad_peso)
                            : '' ?>"
                        readonly>
                </div>

                <div class="campo">
                    <label for="molde">Tamaño del Molde</label>
                    <input type="text" id="molde"
                        value="<?= htmlspecialchars($receta['molde'] ?? '') ?>"
                        readonly>
                </div>

                <div class="campo">
                    <label for="tiempo_preparacion">Tiempo de Preparación del Batido</label>
                    <input type="text" id="tiempo_preparacion"
                        value="<?= $receta['tiempo_preparacion'] !== null
                            ? htmlspecialchars((string)$receta['tiempo_preparacion']) . ' min'
                            : '' ?>"
                        readonly>
                </div>

                <div class="campo">
                    <label for="temperatura_horno">Temperatura del Horno</label>
                    <input type="text" id="temperatura_horno"
                        value="<?= $receta['temperatura_horno'] !== null
                            ? htmlspecialchars((string)$receta['temperatura_horno']) . ' °C'
                            : '' ?>"
                        readonly>
                </div>

                <div class="campo">
                    <label for="tiempo_horneado">Tiempo de Horneado</label>
                    <input type="text" id="tiempo_horneado"
                        value="<?= $receta['tiempo_horneado'] !== null
                            ? htmlspecialchars((string)$receta['tiempo_horneado']) . ' min'
                            : '' ?>"
                        readonly>
                </div>

                <div class="campo">
                    <label for="estado">Estado</label>
                    <input type="text" id="estado"
                        value="<?= htmlspecialchars($receta['estado'] ?? '') ?>"
                        readonly>
                </div>

                <div class="campo campo-completo">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" rows="4" readonly><?= htmlspecialchars($receta['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="campo campo-completo">
                    <label for="observaciones">Observaciones</label>
                    <textarea id="observaciones" rows="4" readonly><?= htmlspecialchars($receta['observaciones'] ?? '') ?></textarea>
                </div>

            </div>
        </section>

        <!-- CALCULADORA DE INGREDIENTES -->
        <section class="panel panel-calculadora">

            <div class="panel-header">
                <div>
                    <h2>Calcular Ingredientes</h2>
                    <p>
                        Indique cuántas unidades desea preparar para calcular
                        las cantidades necesarias de cada insumo.
                    </p>
                </div>
            </div>

            <div class="grupo-formulario calculadora-campos">

                <div class="campo">
                    <label for="cantidadDeseada">
                        Cantidad de unidades a preparar
                    </label>
                    <input
                        type="number"
                        id="cantidadDeseada"
                        min="1"
                        step="1"
                        value="<?= max(1, (int)$receta['rendimiento']) ?>"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="rendimientoGuardado">
                        Rendimiento de la receta guardada
                    </label>
                    <input
                        type="text"
                        id="rendimientoGuardado"
                        value="<?= (int)$receta['rendimiento'] ?> unidades"
                        readonly
                    >
                </div>

            </div>

            <div id="mensajeCalculo"
                role="status"
                aria-live="polite"
                style="display:none;">
            </div>

            <div class="botones-formulario botones-calculo">
                <button type="button" class="btn btn-calcular" id="btnCalcular">
                    Calcular ingredientes
                </button>

                <button type="button" class="btn btn-restablecer" id="btnRestablecer">
                    Restablecer
                </button>
            </div>

            <p class="nota-calculadora">
                Este cálculo es informativo. No modifica la receta guardada
                ni descuenta cantidades del inventario.
            </p>

        </section>

        <!-- INSUMOS DE LA RECETA -->
        <section class="panel panel-insumos" id="seccionInsumos">

            <div class="panel-header">
                <div>
                    <h2>Insumos de la Receta</h2>
                    <p>
                        Consulte las cantidades originales y las cantidades
                        calculadas para la preparación seleccionada.
                    </p>
                </div>
            </div>

            <?php if (!empty($insumos_receta)): ?>

                <div class="tabla-insumos-contenedor">
                    <table class="tabla tabla-insumos">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Insumo</th>
                                <th>Cantidad original</th>
                                <th>Unidad</th>
                                <th>Cantidad necesaria</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($insumos_receta as $insumo): ?>
                                <tr data-cantidad="<?= htmlspecialchars((string)$insumo['cantidad']) ?>">
                                    <td><?= htmlspecialchars($insumo['codigo'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($insumo['producto'] ?? '') ?></td>
                                    <td><?= formatoNumero($insumo['cantidad']) ?></td>
                                    <td><?= htmlspecialchars($insumo['unidad_medida'] ?? '') ?></td>
                                    <td class="cantidad-calculada">
                                        <?= formatoNumero($insumo['cantidad']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($insumo['observaciones'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <p>No hay insumos registrados para esta receta.</p>
            <?php endif; ?>

            <div class="botones-formulario">
                <a href="Recetas.php" class="btn btn-verde">
                    Volver a Recetas
                </a>
            </div>

        </section>

    <?php else: ?>

        <!-- RECETA NO ENCONTRADA -->
        <section class="panel">
            <p>La receta solicitada no existe o no fue encontrada.</p>

            <div class="botones-formulario">
                <a href="Recetas.php" class="btn btn-verde">
                    Volver a Recetas
                </a>
            </div>
        </section>

    <?php endif; ?>

</main>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const campoCantidad = document.getElementById('cantidadDeseada');
    const botonCalcular = document.getElementById('btnCalcular');
    const botonRestablecer = document.getElementById('btnRestablecer');
    const mensaje = document.getElementById('mensajeCalculo');

    if (!campoCantidad || !botonCalcular || !botonRestablecer || !mensaje) {
        return;
    }

    const rendimientoOriginal = <?= $receta ? max(0, (int)$receta['rendimiento']) : 0 ?>;
    const filas = document.querySelectorAll(
        '.tabla-insumos tbody tr[data-cantidad]'
    );

    function formatoCantidad(valor) {
        return valor.toLocaleString('es-CO', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3
        });
    }

    function calcularIngredientes() {
        const cantidadDeseada = Number(campoCantidad.value);

        if (!Number.isInteger(cantidadDeseada) || cantidadDeseada < 1) {
            mensaje.style.display = 'block';
            mensaje.style.color = '#e74c3c';
            mensaje.textContent = 'Ingrese una cantidad entera mayor que cero.';
            campoCantidad.focus();
            return;
        }

        if (rendimientoOriginal < 1) {
            mensaje.style.display = 'block';
            mensaje.style.color = '#e74c3c';
            mensaje.textContent =
                'No se puede calcular porque el rendimiento original de la receta no es válido.';
            return;
        }

        const factor = cantidadDeseada / rendimientoOriginal;

        filas.forEach(function (fila) {
            const cantidadOriginal = Number(fila.dataset.cantidad);
            const celda = fila.querySelector('.cantidad-calculada');

            if (celda) {
                celda.textContent = formatoCantidad(cantidadOriginal * factor);
            }
        });

        mensaje.style.display = 'block';
        mensaje.style.color = '#1f3f6d';
        mensaje.style.backgroundColor = '#eef5ff';
        mensaje.style.border = '1px solid #4a8be0';
        mensaje.style.borderRadius = '8px';
        mensaje.style.padding = '16px';
        mensaje.style.margin = '15px 0';
        mensaje.style.lineHeight = '1.7';

        mensaje.innerHTML =
            '<strong>¡Cálculo realizado correctamente!</strong><br>' +
            'Se calcularon las cantidades necesarias de ingredientes ' +
            'para preparar <strong>' + cantidadDeseada + ' unidades</strong>.<br>' +
            'Factor de preparación: <strong>× ' +
            formatoCantidad(factor) + '</strong> respecto a la receta original.<br><br>' +
            '<strong>¿Dónde consultar los resultados?</strong><br>' +
            'En la sección <strong>Insumos de la Receta</strong>, ubicada debajo ' +
            'de esta calculadora, encontrará la cantidad original de cada ingrediente ' +
            'y la cantidad necesaria para preparar las unidades seleccionadas.';
    }

    botonCalcular.addEventListener('click', calcularIngredientes);

    campoCantidad.addEventListener('keydown', function (evento) {
        if (evento.key === 'Enter') {
            evento.preventDefault();
            calcularIngredientes();
        }
    });

    botonRestablecer.addEventListener('click', function () {
        campoCantidad.value = Math.max(1, rendimientoOriginal);

        filas.forEach(function (fila) {
            const cantidadOriginal = Number(fila.dataset.cantidad);
            const celda = fila.querySelector('.cantidad-calculada');

            if (celda) {
                celda.textContent = formatoCantidad(cantidadOriginal);
            }
        });

        mensaje.textContent = '';
        mensaje.style.display = 'none';
        campoCantidad.focus();
    });

});
</script>

<?php
include("../../includes/footer.php");
?>