<?php

$titulo = "Alertas de Stock";
$css = "Alertas.css";

require_once "../../includes/conexion.php";
require_once "../../includes/header.php";

$busqueda = trim($_GET['buscar'] ?? '');


/* INDICADORES */

$totalAlertas = 0;
$stockBajo = 0;
$disponibles = 0;

$resultado = $conexion->query(
    "SELECT COUNT(*) AS total
     FROM insumos
     WHERE estado = 'Bajo Stock'"
);

if($resultado){
    $totalAlertas = $resultado->fetch_assoc()['total'];
}

$stockBajo = $totalAlertas;

$resultado = $conexion->query(
    "SELECT COUNT(*) AS total
     FROM insumos
     WHERE estado = 'Disponible'"
);

if($resultado){
    $disponibles = $resultado->fetch_assoc()['total'];
}


/* LISTADO DE ALERTAS */

$sql = "SELECT
            i.id_insumo,
            i.codigo,
            i.producto,
            i.stock,
            i.unidad,
            i.estado,
            c.nombre AS categoria
        FROM insumos i
        LEFT JOIN categorias c
            ON i.id_categoria = c.id_categoria
        WHERE i.estado = 'Bajo Stock'";


/* BUSQUEDA POR TODOS LOS CAMPOS */

if($busqueda != ''){

    $sql .= " AND (
                i.codigo LIKE ?
                OR i.producto LIKE ?
                OR c.nombre LIKE ?
                OR CAST(i.stock AS CHAR) LIKE ?
                OR i.unidad LIKE ?
                OR i.estado LIKE ?
              )";
}

$sql .= " ORDER BY i.stock ASC, i.id_insumo DESC";


/* EJECUTAR BUSQUEDA */

if($busqueda != ''){

    $stmt = $conexion->prepare($sql);

    $termino = "%".$busqueda."%";

    $stmt->bind_param(
        "ssssss",
        $termino,
        $termino,
        $termino,
        $termino,
        $termino,
        $termino
    );

    $stmt->execute();

    $alertas = $stmt->get_result();

}else{

    $alertas = $conexion->query($sql);

}

?>

<main class="contenedor">

    <!-- NAVEGACION -->

    <div class="volver">

        <a href="../Inventarios/Inventarios.php">
            ← Volver a Inventarios
        </a>

        <a
            href="/YCL_Solutions/DASHBOARD/Dashboard.php"
            class="btn-inicio"
        >
            ⌂ Inicio
        </a>

    </div>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>Alertas de Stock</h1>

        <p>
            Control y seguimiento de insumos con niveles bajos para evitar faltantes
            durante el proceso productivo de YCL_Solutions.
        </p>

    </header>


    <!-- INDICADORES -->

    <section class="cards">

        <div class="card alertas-total">

            <h2><?= $totalAlertas ?></h2>

            <span>
                Total Alertas
            </span>

        </div>


        <div class="card critico">

            <h2><?= $stockBajo ?></h2>

            <span>
                Stock Bajo
            </span>

        </div>


        <div class="card bajo">

            <h2><?= $stockBajo ?></h2>

            <span>
                Insumos a Revisar
            </span>

        </div>


        <div class="card actualizado">

            <h2>Hoy</h2>

            <span>
                Última Actualización
            </span>

        </div>

    </section>


    <!-- PANEL -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Insumos con Alertas de Stock
                </h2>

                <p>
                    Insumos que requieren reposición para mantener la disponibilidad del inventario.
                </p>

            </div>


            <!-- BUSCADOR Y ACCIONES -->

            <div class="acciones">

                <input
                    type="text"
                    id="buscarAlerta"
                    class="buscar"
                    placeholder="Buscar insumo..."
                    value="<?= htmlspecialchars($busqueda) ?>"
                >

                <button
                    type="button"
                    class="btn-buscar"
                    onclick="buscarAlerta()"
                >
                    Buscar
                </button>

                <button
                    type="button"
                    class="btn-refrescar"
                    onclick="refrescarAlertas()"
                >
                    Refrescar
                </button>

            </div>

        </div>


        <!-- TABLA -->

        <table>

            <thead>

                <tr>

                    <th>Código</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Stock Actual</th>
                    <th>Unidad</th>
                    <th>Estado</th>
                    <th>Acciones</th>

                </tr>

            </thead>


            <tbody>

                <?php if($alertas && $alertas->num_rows > 0): ?>

                    <?php while($insumo = $alertas->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($insumo['codigo']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($insumo['producto']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($insumo['categoria'] ?? '') ?>
                            </td>

                            <td>
                                <?= number_format(
                                    $insumo['stock'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($insumo['unidad']) ?>
                            </td>

                            <td>

                                <span class="estado estado-bajo">
                                    <?= htmlspecialchars($insumo['estado']) ?>
                                </span>

                            </td>

                            <td>

                                <a
                                    href="../Insumos/VerInsumo.php?id=<?= $insumo['id_insumo'] ?>"
                                    class="btn-ver"
                                >
                                    Ver
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="7" class="sin-datos">

                            <?php if($busqueda != ''): ?>

                                No se encontraron alertas para la búsqueda.

                            <?php else: ?>

                                No hay insumos con stock bajo.

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </section>

</main>


<!-- BUSQUEDA AUTOMATICA -->

<script>

const campoBusqueda = document.getElementById('buscarAlerta');

let tiempoBusqueda;

campoBusqueda.addEventListener('input', function(){

    clearTimeout(tiempoBusqueda);

    tiempoBusqueda = setTimeout(function(){

        buscarAlerta();

    }, 350);

});


campoBusqueda.addEventListener('keydown', function(event){

    if(event.key === 'Enter'){

        event.preventDefault();

        buscarAlerta();

    }

});


function buscarAlerta(){

    const valor = campoBusqueda.value.trim();

    if(valor === ''){

        window.location.href = 'Alertas.php';

        return;

    }

    window.location.href =
        'Alertas.php?buscar=' + encodeURIComponent(valor);

}


function refrescarAlertas(){

    window.location.href = 'Alertas.php';

}

</script>


<?php require_once "../../includes/footer.php"; ?>