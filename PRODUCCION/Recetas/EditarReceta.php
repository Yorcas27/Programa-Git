<?php

$titulo = "EDITAR RECETA";
$css = "Recetas.css";

include("../../includes/config.php");
include("../../includes/conexion.php");
include("../../includes/header.php");

$error = "";


/* OBTENER ID */

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id <= 0){

    echo "<main class='contenido'>
            <section class='panel'>
                <p>La receta solicitada no existe o no fue encontrada.</p>
                <div class='botones-formulario'>
                    <a href='Recetas.php' class='btn btn-verde'>
                        Volver a Recetas
                    </a>
                </div>
            </section>
          </main>";

    include("../../includes/footer.php");
    exit;

}


/* CARGAR RECETA */

$stmt = $conexion->prepare(
    "SELECT
        id_receta,
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
    FROM recetas
    WHERE id_receta = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();

$receta = $resultado->fetch_assoc();

$stmt->close();


if(!$receta){

    echo "<main class='contenido'>
            <section class='panel'>
                <p>La receta solicitada no existe o no fue encontrada.</p>
                <div class='botones-formulario'>
                    <a href='Recetas.php' class='btn btn-verde'>
                        Volver a Recetas
                    </a>
                </div>
            </section>
          </main>";

    include("../../includes/footer.php");
    exit;

}


/* PRODUCTOS */

$productos = [];

$sqlProductos = "
    SELECT
        id_producto,
        nombre
    FROM productos
    WHERE estado = 'activo'
    ORDER BY nombre ASC
";

$resultadoProductos = $conexion->query($sqlProductos);

if($resultadoProductos){

    while($producto = $resultadoProductos->fetch_assoc()){

        $productos[] = $producto;

    }

}


/* INSUMOS DISPONIBLES */

$insumos = [];

$sqlInsumos = "
    SELECT
        id_insumo,
        codigo,
        producto,
        unidad
    FROM insumos
    WHERE estado = 'Disponible'
    ORDER BY producto ASC
";

$resultadoInsumos = $conexion->query($sqlInsumos);

if($resultadoInsumos){

    while($insumo = $resultadoInsumos->fetch_assoc()){

        $insumos[] = $insumo;

    }

}


/* CARGAR INSUMOS DE LA RECETA */

$detallesReceta = [];

$stmtDetalles = $conexion->prepare(
    "SELECT
        id_detalle_receta,
        id_insumo,
        cantidad,
        unidad_medida,
        observaciones
    FROM detalle_receta
    WHERE id_receta = ?
    ORDER BY id_detalle_receta ASC"
);

$stmtDetalles->bind_param("i", $id);

$stmtDetalles->execute();

$resultadoDetalles = $stmtDetalles->get_result();

while($detalle = $resultadoDetalles->fetch_assoc()){

    $detallesReceta[] = $detalle;

}

$stmtDetalles->close();


/* ACTUALIZAR */

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $nombre = trim($_POST['nombre'] ?? '');

    $id_producto = (int)($_POST['id_producto'] ?? 0);

    $rendimiento = (int)($_POST['rendimiento'] ?? 0);

    $peso_batido = (float)($_POST['peso_batido'] ?? 0);

    $unidad_peso = $_POST['unidad_peso'] ?? 'g';

    $molde = trim($_POST['molde'] ?? '');

    $tiempo_preparacion = (int)($_POST['tiempo_preparacion'] ?? 0);

    $temperatura_horno = (int)($_POST['temperatura_horno'] ?? 0);

    $tiempo_horneado = (int)($_POST['tiempo_horneado'] ?? 0);

    $estado = trim($_POST['estado'] ?? 'Activa');

    $descripcion = trim($_POST['descripcion'] ?? '');

    $observaciones = trim($_POST['observaciones'] ?? '');


    /* OBTENER PRODUCTO Y CATEGORÍA */

    $producto = '';
    $categoria = '';

    if($id_producto > 0){

        $stmtProducto = $conexion->prepare(
            "SELECT
                p.nombre,
                cp.nombre AS categoria
            FROM productos p
            INNER JOIN categorias_producto cp
                ON p.id_categoria_producto = cp.id_categoria_producto
            WHERE p.id_producto = ?"
        );

        $stmtProducto->bind_param("i", $id_producto);

        $stmtProducto->execute();

        $resultadoProducto = $stmtProducto->get_result();

        if($datosProducto = $resultadoProducto->fetch_assoc()){

            $producto = $datosProducto['nombre'];
            $categoria = $datosProducto['categoria'];

        }

        $stmtProducto->close();

    }


    /* VALIDAR UNIDAD */

    if(!in_array($unidad_peso, ['g', 'kg'], true)){

        $unidad_peso = 'g';

    }


    /* NORMALIZAR MOLDE */

    if($molde === 'Pequeño'){

        $moldeBD = 'Pequeno';

    }else{

        $moldeBD = $molde;

    }


    /* VALIDACIONES */

    if(
        $nombre === '' ||
        $id_producto <= 0 ||
        $rendimiento <= 0 ||
        $peso_batido <= 0 ||
        !in_array($moldeBD, ['Pequeno', 'Mediano', 'Grande'], true) ||
        $tiempo_preparacion <= 0 ||
        $temperatura_horno <= 0 ||
        $tiempo_horneado <= 0 ||
        $producto === ''
    ){

        $error = "Complete los campos obligatorios.";

    }else{


        /* INSUMOS */

        $idsInsumos = $_POST['id_insumo'] ?? [];

        $cantidades = $_POST['cantidad'] ?? [];

        $unidades = $_POST['unidad_medida'] ?? [];

        $observacionesInsumos = $_POST['observaciones_insumo'] ?? [];

        $detallesNuevos = [];

        $hayInsumoIncompleto = false;


        foreach($idsInsumos as $indice => $idInsumo){

            $idInsumo = (int)$idInsumo;

            $cantidad = (float)($cantidades[$indice] ?? 0);

            $unidadMedida = trim(
                $unidades[$indice] ?? ''
            );

            $observacionInsumo = trim(
                $observacionesInsumos[$indice] ?? ''
            );


            /* FILA VACÍA */

            if(
                $idInsumo <= 0 &&
                $cantidad <= 0 &&
                $observacionInsumo === ''
            ){

                continue;

            }


            /* FILA INCOMPLETA */

            if(
                $idInsumo <= 0 ||
                $cantidad <= 0 ||
                $unidadMedida === ''
            ){

                $hayInsumoIncompleto = true;

                break;

            }


            $detallesNuevos[] = [
                'id_insumo' => $idInsumo,
                'cantidad' => $cantidad,
                'unidad_medida' => $unidadMedida,
                'observaciones' => $observacionInsumo
            ];

        }


        if($hayInsumoIncompleto){

            $error = "Hay un insumo incompleto. Seleccione el insumo, indique la cantidad y la unidad.";

        }elseif(count($detallesNuevos) === 0){

            $error = "Debe agregar al menos un insumo con una cantidad válida.";

        }else{


            /* TRANSACCIÓN */

            $conexion->begin_transaction();

            try{


                /* DATOS DE COMPATIBILIDAD */

                $unidad = "Unidad";

                $tiempo = $tiempo_preparacion . " min preparación / " .
                          $tiempo_horneado . " min horneado";


                /* ACTUALIZAR RECETA */

                $stmtActualizar = $conexion->prepare(
                    "UPDATE recetas SET
                        id_producto = ?,
                        nombre = ?,
                        producto = ?,
                        categoria = ?,
                        rendimiento = ?,
                        peso_batido = ?,
                        unidad_peso = ?,
                        molde = ?,
                        tiempo_preparacion = ?,
                        temperatura_horno = ?,
                        tiempo_horneado = ?,
                        unidad = ?,
                        tiempo = ?,
                        estado = ?,
                        descripcion = ?,
                        observaciones = ?
                    WHERE id_receta = ?"
                );


                if(!$stmtActualizar){

                    throw new Exception(
                        "No fue posible preparar la actualización de la receta."
                    );

                }


                $stmtActualizar->bind_param(
                    "isssidssiiisssssi",
                    $id_producto,
                    $nombre,
                    $producto,
                    $categoria,
                    $rendimiento,
                    $peso_batido,
                    $unidad_peso,
                    $moldeBD,
                    $tiempo_preparacion,
                    $temperatura_horno,
                    $tiempo_horneado,
                    $unidad,
                    $tiempo,
                    $estado,
                    $descripcion,
                    $observaciones,
                    $id
                );


                if(!$stmtActualizar->execute()){

                    throw new Exception(
                        "No fue posible actualizar la receta: " .
                        $stmtActualizar->error
                    );

                }

                $stmtActualizar->close();


                /* ELIMINAR INSUMOS ANTERIORES */

                $stmtEliminar = $conexion->prepare(
                    "DELETE FROM detalle_receta
                     WHERE id_receta = ?"
                );


                if(!$stmtEliminar){

                    throw new Exception(
                        "No fue posible preparar la actualización de los insumos."
                    );

                }


                $stmtEliminar->bind_param(
                    "i",
                    $id
                );


                if(!$stmtEliminar->execute()){

                    throw new Exception(
                        "No fue posible eliminar los insumos anteriores."
                    );

                }

                $stmtEliminar->close();


                /* INSERTAR NUEVOS INSUMOS */

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


                if(!$stmtDetalle){

                    throw new Exception(
                        "No fue posible preparar los nuevos insumos."
                    );

                }


                foreach($detallesNuevos as $detalle){

                    $idInsumo = $detalle['id_insumo'];

                    $cantidad = $detalle['cantidad'];

                    $unidadMedida = $detalle['unidad_medida'];

                    $observacionInsumo = $detalle['observaciones'];


                    $stmtDetalle->bind_param(
                        "iidss",
                        $id,
                        $idInsumo,
                        $cantidad,
                        $unidadMedida,
                        $observacionInsumo
                    );


                    if(!$stmtDetalle->execute()){

                        throw new Exception(
                            "No fue posible guardar los nuevos insumos: " .
                            $stmtDetalle->error
                        );

                    }

                }


                $stmtDetalle->close();


                /* CONFIRMAR */

                $conexion->commit();


                header(
                    "Location: VerReceta.php?id=" . $id
                );

                exit;


            }catch(Exception $e){

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

            <a href="Recetas.php" class="btn btn-verde">
                ← Volver a Recetas
            </a>

            <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
                ⌂ Inicio
            </a>

        </div>

    </section>


    <header class="encabezado">

        <h1>
            Editar Receta
        </h1>

        <p>
            Modifique la información registrada de la receta.
        </p>

    </header>


    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Información de la Receta
                </h2>

                <p>
                    Actualice los datos necesarios y los insumos utilizados.
                </p>

            </div>

        </div>


        <?php if($error !== ''): ?>

            <div class="mensaje-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <form method="POST" class="formulario" id="formularioReceta">

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
                        Nombre de la Receta *
                    </label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        value="<?= htmlspecialchars($_POST['nombre'] ?? $receta['nombre']) ?>"
                        required
                    >

                </div>


                <!-- Producto -->

                <div class="campo">

                    <label for="id_producto">
                        Producto Asociado *
                    </label>

                    <select
                        id="id_producto"
                        name="id_producto"
                        required
                    >

                        <option value="">
                            Seleccione un producto
                        </option>

                        <?php foreach($productos as $productoItem): ?>

                            <option
                                value="<?= $productoItem['id_producto'] ?>"
                                <?=
                                    (
                                        (int)($_POST['id_producto'] ?? $receta['id_producto'])
                                        ===
                                        (int)$productoItem['id_producto']
                                    )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= htmlspecialchars($productoItem['nombre']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Rendimiento -->

                <div class="campo">

                    <label for="rendimiento">
                        Cantidad de Tortas *
                    </label>

                    <input
                        type="number"
                        id="rendimiento"
                        name="rendimiento"
                        min="1"
                        step="1"
                        value="<?= htmlspecialchars($_POST['rendimiento'] ?? $receta['rendimiento']) ?>"
                        required
                    >

                </div>


                <!-- Peso Batido -->

                <div class="campo">

                    <label for="peso_batido">
                        Peso Total del Batido *
                    </label>

                    <div class="peso-con-unidad">

                        <input
                            type="number"
                            id="peso_batido"
                            name="peso_batido"
                            min="0.001"
                            step="0.001"
                            value="<?= htmlspecialchars($_POST['peso_batido'] ?? $receta['peso_batido']) ?>"
                            required
                        >

                        <select name="unidad_peso">

                            <option
                                value="g"
                                <?=
                                    (
                                        ($_POST['unidad_peso'] ?? $receta['unidad_peso'])
                                        === 'g'
                                    )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                g
                            </option>

                            <option
                                value="kg"
                                <?=
                                    (
                                        ($_POST['unidad_peso'] ?? $receta['unidad_peso'])
                                        === 'kg'
                                    )
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                kg
                            </option>

                        </select>

                    </div>

                </div>


                <!-- Molde -->

                <div class="campo">

                    <label for="molde">
                        Tamaño del Molde *
                    </label>

                    <select
                        id="molde"
                        name="molde"
                        required
                    >

                        <option value="">
                            Seleccione...
                        </option>

                        <option
                            value="Pequeño"
                            <?=
                                (
                                    ($_POST['molde'] ?? $receta['molde']) === 'Pequeno' ||
                                    ($_POST['molde'] ?? $receta['molde']) === 'Pequeño'
                                )
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Pequeño
                        </option>

                        <option
                            value="Mediano"
                            <?=
                                (
                                    ($_POST['molde'] ?? $receta['molde']) === 'Mediano'
                                )
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Mediano
                        </option>

                        <option
                            value="Grande"
                            <?=
                                (
                                    ($_POST['molde'] ?? $receta['molde']) === 'Grande'
                                )
                                ? 'selected'
                                : ''
                            ?>
                        >
                            Grande
                        </option>

                    </select>

                </div>


                <!-- Tiempo Preparación -->

                <div class="campo">

                    <label for="tiempo_preparacion">
                        Tiempo de Preparación *
                    </label>

                    <input
                        type="number"
                        id="tiempo_preparacion"
                        name="tiempo_preparacion"
                        min="1"
                        step="1"
                        value="<?= htmlspecialchars($_POST['tiempo_preparacion'] ?? $receta['tiempo_preparacion']) ?>"
                        required
                    >

                </div>


                <!-- Temperatura -->

                <div class="campo">

                    <label for="temperatura_horno">
                        Temperatura del Horno *
                    </label>

                    <input
                        type="number"
                        id="temperatura_horno"
                        name="temperatura_horno"
                        min="1"
                        step="1"
                        value="<?= htmlspecialchars($_POST['temperatura_horno'] ?? $receta['temperatura_horno']) ?>"
                        required
                    >

                </div>


                <!-- Tiempo Horneado -->

                <div class="campo">

                    <label for="tiempo_horneado">
                        Tiempo de Horneado *
                    </label>

                    <input
                        type="number"
                        id="tiempo_horneado"
                        name="tiempo_horneado"
                        min="1"
                        step="1"
                        value="<?= htmlspecialchars($_POST['tiempo_horneado'] ?? $receta['tiempo_horneado']) ?>"
                        required
                    >

                </div>


                <!-- Estado -->

                <div class="campo">

                    <label for="estado">
                        Estado
                    </label>

                    <?php
                    $estadoActual = $_POST['estado'] ?? $receta['estado'];
                    ?>

                    <select
                        id="estado"
                        name="estado"
                    >

                        <option
                            value="Activa"
                            <?= $estadoActual === 'Activa' ? 'selected' : '' ?>
                        >
                            Activa
                        </option>

                        <option
                            value="Inactiva"
                            <?= $estadoActual === 'Inactiva' ? 'selected' : '' ?>
                        >
                            Inactiva
                        </option>

                    </select>

                </div>


                <!-- Descripción -->

                <div class="campo campo-completo">

                    <label for="descripcion">
                        Descripción
                    </label>

                    <textarea
                        id="descripcion"
                        name="descripcion"
                        rows="4"
                    ><?= htmlspecialchars($_POST['descripcion'] ?? ($receta['descripcion'] ?? '')) ?></textarea>

                </div>


                <!-- Observaciones -->

                <div class="campo campo-completo">

                    <label for="observaciones">
                        Observaciones
                    </label>

                    <textarea
                        id="observaciones"
                        name="observaciones"
                        rows="4"
                    ><?= htmlspecialchars($_POST['observaciones'] ?? ($receta['observaciones'] ?? '')) ?></textarea>

                </div>


            </div>


            <!-- INSUMOS -->

            <section class="panel-insumos">

                <div class="panel-header">

                    <div>

                        <h2>
                            Insumos del Batido
                        </h2>

                        <p>
                            Modifique los insumos utilizados en la elaboración.
                        </p>

                    </div>

                    <button
                        type="button"
                        class="btn btn-principal"
                        onclick="agregarInsumo()"
                    >
                        + Agregar Insumo
                    </button>

                </div>


                <div class="tabla-insumos-contenedor">

                    <table
                        class="tabla tabla-insumos"
                        id="tablaInsumos"
                    >

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


                            <?php if(count($detallesReceta) > 0): ?>

                                <?php foreach($detallesReceta as $detalle): ?>

                                    <tr class="fila-insumo">

                                        <td>

                                            <select
                                                name="id_insumo[]"
                                                required
                                            >

                                                <option value="">
                                                    Seleccione un insumo
                                                </option>

                                                <?php foreach($insumos as $insumo): ?>

                                                    <option
                                                        value="<?= $insumo['id_insumo'] ?>"
                                                        <?=
                                                            (
                                                                (int)$detalle['id_insumo']
                                                                ===
                                                                (int)$insumo['id_insumo']
                                                            )
                                                            ? 'selected'
                                                            : ''
                                                        ?>
                                                    >
                                                        <?= htmlspecialchars($insumo['producto']) ?>
                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                        </td>

                                        <td>

                                            <input
                                                type="number"
                                                name="cantidad[]"
                                                min="0.001"
                                                step="0.001"
                                                value="<?= htmlspecialchars($detalle['cantidad']) ?>"
                                                required
                                            >

                                        </td>

                                        <td>

                                            <select name="unidad_medida[]">

                                                <option
                                                    value="g"
                                                    <?= $detalle['unidad_medida'] === 'g' ? 'selected' : '' ?>
                                                >
                                                    g
                                                </option>

                                                <option
                                                    value="kg"
                                                    <?= $detalle['unidad_medida'] === 'kg' ? 'selected' : '' ?>
                                                >
                                                    kg
                                                </option>

                                                <option
                                                    value="ml"
                                                    <?= $detalle['unidad_medida'] === 'ml' ? 'selected' : '' ?>
                                                >
                                                    ml
                                                </option>

                                                <option
                                                    value="L"
                                                    <?= $detalle['unidad_medida'] === 'L' ? 'selected' : '' ?>
                                                >
                                                    L
                                                </option>

                                                <option
                                                    value="unidad"
                                                    <?= $detalle['unidad_medida'] === 'unidad' ? 'selected' : '' ?>
                                                >
                                                    unidad
                                                </option>

                                            </select>

                                        </td>

                                        <td>

                                            <input
                                                type="text"
                                                name="observaciones_insumo[]"
                                                placeholder="Opcional"
                                                value="<?= htmlspecialchars($detalle['observaciones'] ?? '') ?>"
                                            >

                                        </td>

                                        <td>

                                            <button
                                                type="button"
                                                class="btn-eliminar-insumo"
                                                onclick="eliminarInsumo(this)"
                                            >
                                                Eliminar
                                            </button>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr class="fila-insumo">

                                    <td>

                                        <select
                                            name="id_insumo[]"
                                            required
                                        >

                                            <option value="">
                                                Seleccione un insumo
                                            </option>

                                            <?php foreach($insumos as $insumo): ?>

                                                <option value="<?= $insumo['id_insumo'] ?>">
                                                    <?= htmlspecialchars($insumo['producto']) ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </td>

                                    <td>

                                        <input
                                            type="number"
                                            name="cantidad[]"
                                            min="0.001"
                                            step="0.001"
                                            placeholder="Cantidad"
                                            required
                                        >

                                    </td>

                                    <td>

                                        <select name="unidad_medida[]">

                                            <option value="g">
                                                g
                                            </option>

                                            <option value="kg">
                                                kg
                                            </option>

                                            <option value="ml">
                                                ml
                                            </option>

                                            <option value="L">
                                                L
                                            </option>

                                            <option value="unidad">
                                                unidad
                                            </option>

                                        </select>

                                    </td>

                                    <td>

                                        <input
                                            type="text"
                                            name="observaciones_insumo[]"
                                            placeholder="Opcional"
                                        >

                                    </td>

                                    <td>

                                        <button
                                            type="button"
                                            class="btn-eliminar-insumo"
                                            onclick="eliminarInsumo(this)"
                                        >
                                            Eliminar
                                        </button>

                                    </td>

                                </tr>

                            <?php endif; ?>


                        </tbody>

                    </table>

                </div>

            </section>


            <!-- BOTONES -->

            <div class="botones-formulario">

                <a
                    href="Recetas.php"
                    class="btn btn-cancelar"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="btn btn-principal"
                >
                    Guardar Cambios
                </button>

            </div>

        </form>

    </section>

</main>


<script>


/* AGREGAR INSUMO */

function agregarInsumo(){

    const tbody = document.getElementById("insumosBody");

    const filas = tbody.querySelectorAll(".fila-insumo");

    const filaOriginal = filas[0];

    const nuevaFila = filaOriginal.cloneNode(true);


    nuevaFila.querySelectorAll("input").forEach(function(input){

        input.value = "";

    });


    nuevaFila.querySelectorAll("select").forEach(function(select){

        select.selectedIndex = 0;

    });


    tbody.appendChild(nuevaFila);

}


/* ELIMINAR INSUMO */

function eliminarInsumo(boton){

    const filas = document.querySelectorAll(
        "#insumosBody .fila-insumo"
    );


    if(filas.length <= 1){

        const fila = boton.closest(".fila-insumo");


        fila.querySelectorAll("input").forEach(function(input){

            input.value = "";

        });


        fila.querySelectorAll("select").forEach(function(select){

            select.selectedIndex = 0;

        });


        return;

    }


    boton.closest("tr").remove();

}

</script>


<?php

include("../../includes/footer.php");

?>