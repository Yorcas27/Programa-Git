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


    /* CONSULTAR INSUMOS DE LA RECETA */

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


/* PESO POR TORTA */

$peso_por_torta = null;

if (
    $receta &&
    !empty($receta['peso_batido']) &&
    !empty($receta['rendimiento']) &&
    $receta['rendimiento'] > 0
) {
    $peso_por_torta = $receta['peso_batido'] / $receta['rendimiento'];
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

        <h1>
            Detalle de la Receta
        </h1>

        <p>
            Consulte la información registrada de la receta.
        </p>

    </header>


    <?php if ($receta): ?>

        <!-- PANEL INFORMACIÓN GENERAL -->

        <section class="panel">

            <div class="panel-header">

                <div>

                    <h2>
                        Información de la Receta
                    </h2>

                    <p>
                        Datos generales de la receta registrada.
                    </p>

                </div>

            </div>


            <div class="grupo-formulario">

                <!-- Código -->

                <div class="campo">

                    <label for="codigo">
                        Código
                    </label>

                    <input
                        type="text"
                        id="codigo"
                        value="<?= htmlspecialchars($receta['codigo']) ?>"
                        readonly
                    >

                </div>


                <!-- Nombre -->

                <div class="campo">

                    <label for="nombre">
                        Nombre de la Receta
                    </label>

                    <input
                        type="text"
                        id="nombre"
                        value="<?= htmlspecialchars($receta['nombre']) ?>"
                        readonly
                    >

                </div>


                <!-- Producto -->

                <div class="campo">

                    <label for="producto">
                        Producto Asociado
                    </label>

                    <input
                        type="text"
                        id="producto"
                        value="<?= htmlspecialchars($receta['producto'] ?? '') ?>"
                        readonly
                    >

                </div>


                <!-- Categoría -->

                <div class="campo">

                    <label for="categoria">
                        Categoría
                    </label>

                    <input
                        type="text"
                        id="categoria"
                        value="<?= htmlspecialchars($receta['categoria'] ?? '') ?>"
                        readonly
                    >

                </div>


                <!-- Rendimiento -->

                <div class="campo">

                    <label for="rendimiento">
                        Rendimiento
                    </label>

                    <input
                        type="text"
                        id="rendimiento"
                        value="<?= htmlspecialchars($receta['rendimiento']) ?> tortas"
                        readonly
                    >

                </div>


                <!-- Peso total -->

                <div class="campo">

                    <label for="peso_batido">
                        Peso Total del Batido
                    </label>

                    <input
                        type="text"
                        id="peso_batido"
                        value="<?= number_format((float)$receta['peso_batido'], 0, ',', '.') ?> g"
                        readonly
                    >

                </div>


                <!-- Peso por torta -->

                <div class="campo">

                    <label for="peso_por_torta">
                        Peso por Torta
                    </label>

                    <input
                        type="text"
                        id="peso_por_torta"
                        value="<?= $peso_por_torta !== null ? number_format($peso_por_torta, 0, ',', '.') . ' g' : '' ?>"
                        readonly
                    >

                </div>


                <!-- Molde -->

                <div class="campo">

                    <label for="molde">
                        Tamaño del Molde
                    </label>

                    <input
                        type="text"
                        id="molde"
                        value="<?= htmlspecialchars($receta['molde'] ?? '') ?>"
                        readonly
                    >

                </div>


                <!-- Tiempo preparación -->

                <div class="campo">

                    <label for="tiempo_preparacion">
                        Tiempo de Preparación del Batido
                    </label>

                    <input
                        type="text"
                        id="tiempo_preparacion"
                        value="<?= $receta['tiempo_preparacion'] !== null ? htmlspecialchars($receta['tiempo_preparacion']) . ' min' : '' ?>"
                        readonly
                    >

                </div>


                <!-- Temperatura -->

                <div class="campo">

                    <label for="temperatura_horno">
                        Temperatura del Horno
                    </label>

                    <input
                        type="text"
                        id="temperatura_horno"
                        value="<?= $receta['temperatura_horno'] !== null ? htmlspecialchars($receta['temperatura_horno']) . ' °C' : '' ?>"
                        readonly
                    >

                </div>


                <!-- Tiempo horneado -->

                <div class="campo">

                    <label for="tiempo_horneado">
                        Tiempo de Horneado
                    </label>

                    <input
                        type="text"
                        id="tiempo_horneado"
                        value="<?= $receta['tiempo_horneado'] !== null ? htmlspecialchars($receta['tiempo_horneado']) . ' min' : '' ?>"
                        readonly
                    >

                </div>


                <!-- Estado -->

                <div class="campo">

                    <label for="estado">
                        Estado
                    </label>

                    <input
                        type="text"
                        id="estado"
                        value="<?= htmlspecialchars($receta['estado']) ?>"
                        readonly
                    >

                </div>


                <!-- Descripción -->

                <div class="campo campo-completo">

                    <label for="descripcion">
                        Descripción
                    </label>

                    <textarea
                        id="descripcion"
                        rows="4"
                        readonly
                    ><?= htmlspecialchars($receta['descripcion'] ?? '') ?></textarea>

                </div>


                <!-- Observaciones -->

                <div class="campo campo-completo">

                    <label for="observaciones">
                        Observaciones
                    </label>

                    <textarea
                        id="observaciones"
                        rows="4"
                        readonly
                    ><?= htmlspecialchars($receta['observaciones'] ?? '') ?></textarea>

                </div>

            </div>

        </section>


        <!-- PANEL INSUMOS -->

        <section class="panel panel-insumos">

            <div class="panel-header">

                <div>

                    <h2>
                        Insumos de la Receta
                    </h2>

                    <p>
                        Ingredientes utilizados para elaborar el batido.
                    </p>

                </div>

            </div>


            <?php if (!empty($insumos_receta)): ?>

                <div class="tabla-insumos-contenedor">

                    <table class="tabla tabla-insumos">

                        <thead>

                            <tr>

                                <th>
                                    Código
                                </th>

                                <th>
                                    Insumo
                                </th>

                                <th>
                                    Cantidad
                                </th>

                                <th>
                                    Unidad
                                </th>

                                <th>
                                    Observaciones
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($insumos_receta as $insumo): ?>

                                <tr>

                                    <td>
                                        <?= htmlspecialchars($insumo['codigo']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($insumo['producto']) ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float)$insumo['cantidad'],
                                            3,
                                            ',',
                                            '.'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($insumo['unidad_medida']) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($insumo['observaciones'] ?? '') ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <p>
                    No hay insumos registrados para esta receta.
                </p>

            <?php endif; ?>


            <!-- BOTÓN -->

            <div class="botones-formulario">

                <a
                    href="Recetas.php"
                    class="btn btn-verde"
                >
                    Volver
                </a>

            </div>

        </section>


    <?php else: ?>

        <!-- RECETA NO ENCONTRADA -->

        <section class="panel">

            <p>
                La receta solicitada no existe o no fue encontrada.
            </p>

            <div class="botones-formulario">

                <a
                    href="Recetas.php"
                    class="btn btn-verde"
                >
                    Volver a Recetas
                </a>

            </div>

        </section>

    <?php endif; ?>

</main>


<?php

include("../../includes/footer.php");

?>