
<?php

$titulo = "Insumos";
$css = "Insumos.css";

require_once "../../includes/conexion.php";
require_once "../../includes/header.php";

$busqueda = trim($_GET['buscar'] ?? '');


/* INDICADORES */

$totalInsumos = 0;
$stockBajo = 0;
$totalCategorias = 0;
$actualizados = 0;


$resultado = $conexion->query(
    "SELECT COUNT(*) AS total FROM insumos"
);

if($resultado){
    $totalInsumos = $resultado->fetch_assoc()['total'];
}


$resultado = $conexion->query(
    "SELECT COUNT(*) AS total
     FROM insumos
     WHERE estado = 'Bajo Stock'"
);

if($resultado){
    $stockBajo = $resultado->fetch_assoc()['total'];
}


$resultado = $conexion->query(
    "SELECT COUNT(DISTINCT id_categoria) AS total
     FROM insumos
     WHERE id_categoria IS NOT NULL"
);

if($resultado){
    $totalCategorias = $resultado->fetch_assoc()['total'];
}


$resultado = $conexion->query(
    "SELECT COUNT(*) AS total
     FROM insumos
     WHERE estado = 'Disponible'"
);

if($resultado){
    $actualizados = $resultado->fetch_assoc()['total'];
}


/* LISTADO */

$sql = "SELECT
            i.id_insumo,
            i.codigo,
            i.producto,
            c.nombre AS categoria,
            i.stock,
            i.unidad,
            i.estado
        FROM insumos i
        LEFT JOIN categorias c
            ON i.id_categoria = c.id_categoria";


if($busqueda != ''){

    $sql .= " WHERE i.codigo LIKE ?
              OR i.producto LIKE ?
              OR c.nombre LIKE ?";
}


$sql .= " ORDER BY i.id_insumo DESC";


if($busqueda != ''){

    $stmt = $conexion->prepare($sql);

    $termino = "%".$busqueda."%";

    $stmt->bind_param(
        "sss",
        $termino,
        $termino,
        $termino
    );

    $stmt->execute();

    $insumos = $stmt->get_result();

}else{

    $insumos = $conexion->query($sql);

}

?>

<main class="contenido">

    <!-- VOLVER -->

    <div class="volver">

        <a href="../Inventarios/Inventarios.php">
            ← Volver a Inventarios
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </div>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>
            Gestión de Insumos
        </h1>

        <p>
            Registro, control y administración de materias primas utilizadas en YCL_Solutions.
        </p>

    </header>


    <!-- INDICADORES -->

    <section class="cards">

        <div class="card insumos">

            <h2>
                <?= $totalInsumos ?>
            </h2>

            <span>
                Total Insumos
            </span>

        </div>


        <div class="card stock">

            <h2>
                <?= $stockBajo ?>
            </h2>

            <span>
                Stock Bajo
            </span>

        </div>


        <div class="card categorias">

            <h2>
                <?= $totalCategorias ?>
            </h2>

            <span>
                Categorías
            </span>

        </div>


        <div class="card actualizados">

            <h2>
                <?= $actualizados ?>
            </h2>

            <span>
                Disponibles
            </span>

        </div>

    </section>


    <!-- LISTADO -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Listado de Insumos
                </h2>

                <p>
                    Consulta y administra los insumos registrados en el sistema.
                </p>

            </div>


            <!-- BUSCADOR Y BOTONES -->

            <div class="acciones">

                <input
                    type="text"
                    id="buscarInsumo"
                    class="buscar"
                    placeholder="Buscar insumo..."
                    value="<?= htmlspecialchars($busqueda) ?>"
                >

                <button
                    type="button"
                    class="btn-buscar"
                    onclick="buscarInsumo()"
                >
                    Buscar
                </button>

                <button
                    type="button"
                    class="btn-buscar"
                    onclick="refrescarInsumos()"
                >
                    ↻ Refrescar
                </button>

                <a
                    href="NuevoInsumo.php"
                    class="btn-nuevo"
                >
                    + Nuevo Insumo
                </a>

            </div>

        </div>


        <!-- TABLA -->

        <table id="tablaInsumos">

            <thead>

                <tr>

                    <th>Código</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Stock</th>
                    <th>Unidad</th>
                    <th>Estado</th>
                    <th>Acciones</th>

                </tr>

            </thead>


            <tbody>

                <?php if($insumos && $insumos->num_rows > 0): ?>

                    <?php while($insumo = $insumos->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($insumo['codigo']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($insumo['producto']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $insumo['categoria'] ?? 'Sin categoría'
                                ) ?>
                            </td>

                            <td>
                                <?= rtrim(rtrim(number_format((float)$insumo['stock'], 2, ',', '.'), '0'), ',') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($insumo['unidad']) ?>
                            </td>

                            <td>

                                <span class="<?= $insumo['estado'] == 'Disponible' ? 'disponible' : 'bajo' ?>">

                                    <?= htmlspecialchars($insumo['estado']) ?>

                                </span>

                            </td>

                            <td>

                                <div class="acciones-tabla">

                                    <a
                                        href="VerInsumo.php?id=<?= $insumo['id_insumo'] ?>"
                                        class="btn-ver"
                                    >
                                        Ver
                                    </a>

                                    <a
                                        href="EditarInsumo.php?id=<?= $insumo['id_insumo'] ?>"
                                        class="btn-editar"
                                    >
                                        Editar
                                    </a>

                                    <a
                                        href="EliminarInsumo.php?id=<?= $insumo['id_insumo'] ?>"
                                        class="btn-eliminar"
                                        onclick="return confirm('¿Está seguro de eliminar este insumo?');"
                                    >
                                        Eliminar
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="7" class="sin-datos">

                            <?= $busqueda != ''
                                ? 'No se encontraron insumos.'
                                : 'No hay insumos registrados.'
                            ?>

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </section>

</main>


<!-- BUSCADOR EN TIEMPO REAL -->

<script>

/* BUSCAR */

function buscarInsumo(){

    const texto = document
        .getElementById("buscarInsumo")
        .value
        .toLowerCase()
        .trim();

    const filas = document.querySelectorAll(
        "#tablaInsumos tbody tr"
    );

    filas.forEach(function(fila){

        const contenido =
            fila.textContent.toLowerCase();

        fila.style.display =
            contenido.includes(texto) ? "" : "none";

    });

}


/* REFRESCAR */

function refrescarInsumos(){

    const buscador =
        document.getElementById("buscarInsumo");

    buscador.value = "";

    const filas = document.querySelectorAll(
        "#tablaInsumos tbody tr"
    );

    filas.forEach(function(fila){

        fila.style.display = "";

    });

}


/* BUSCAR AL ESCRIBIR */

document
    .getElementById("buscarInsumo")
    .addEventListener("input", function(){

        buscarInsumo();

    });


/* BUSCAR CON ENTER */

document
    .getElementById("buscarInsumo")
    .addEventListener("keyup", function(event){

        if(event.key === "Enter"){

            buscarInsumo();

        }

    });

</script>


<?php

require_once "../../includes/footer.php";

?>

