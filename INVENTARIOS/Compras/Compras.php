
<?php

$titulo = "Compras";
$css = "Compras.css";

require_once "../../includes/conexion.php";
require_once "../../includes/header.php";

$busqueda = trim($_GET['buscar'] ?? '');

$totalCompras = 0;
$pendientes = 0;
$recibidas = 0;

/* CONTADORES */

$resultado = $conexion->query("SELECT estado, COUNT(*) AS total FROM compras GROUP BY estado");

if($resultado){
    while($fila = $resultado->fetch_assoc()){

        $totalCompras += $fila['total'];

        if($fila['estado'] == 'Pendiente'){
            $pendientes = $fila['total'];
        }elseif($fila['estado'] == 'Recibida'){
            $recibidas = $fila['total'];
        }
    }
}

/* CONSULTA DE COMPRAS */

$sql = "SELECT
            c.id_compra,
            c.codigo_compra,
            c.tipo_proveedor,
            c.id_proveedor,
            c.nombre_proveedor_casual,
            c.tipo_compra,
            c.fecha,
            c.subtotal,
            c.iva,
            c.total,
            c.estado,
            CASE
                WHEN c.tipo_proveedor = 'Casual'
                    THEN c.nombre_proveedor_casual
                ELSE p.nombre
            END AS proveedor
        FROM compras c
        LEFT JOIN proveedores p
            ON c.id_proveedor = p.id_proveedor";

if($busqueda != ''){

    $sql .= " WHERE
                CAST(c.id_compra AS CHAR) LIKE ?
                OR c.codigo_compra LIKE ?
                OR CAST(c.id_proveedor AS CHAR) LIKE ?
                OR c.nombre_proveedor_casual LIKE ?
                OR c.tipo_proveedor LIKE ?
                OR c.tipo_compra LIKE ?
                OR c.fecha LIKE ?
                OR DATE_FORMAT(c.fecha, '%d/%m/%Y') LIKE ?
                OR CAST(c.subtotal AS CHAR) LIKE ?
                OR CAST(c.iva AS CHAR) LIKE ?
                OR CAST(c.total AS CHAR) LIKE ?
                OR c.estado LIKE ?
                OR p.nombre LIKE ?";
}

$sql .= " ORDER BY c.id_compra DESC";

/* EJECUTAR BUSQUEDA */

if($busqueda != ''){

    $stmt = $conexion->prepare($sql);

    $termino = "%".$busqueda."%";

    $stmt->bind_param(
        "sssssssssssss",
        $termino,
        $termino,
        $termino,
        $termino,
        $termino,
        $termino,
        $termino,
        $termino,
        $termino,
        $termino,
        $termino,
        $termino,
        $termino
    );

    $stmt->execute();

    $compras = $stmt->get_result();

}else{

    $compras = $conexion->query($sql);
}

?>

<div class="contenedor compras-modulo">

    <!-- BARRA SUPERIOR -->

    <div class="barra-superior">

        <a href="../Inventarios/Inventarios.php" class="btn-volver">
            ← Volver a Inventarios
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </div>

    <!-- ENCABEZADO -->

    <div class="encabezado">

        <h1>Compras</h1>

        <p>
            Gestiona las compras de insumos y el abastecimiento de inventario.
        </p>

    </div>

    <!-- TARJETAS -->

    <div class="cards">

        <div class="card compras">
            <span class="titulo-card">Compras</span>
            <h2><?= $totalCompras ?></h2>
            <p>Total registradas</p>
        </div>

        <div class="card pendientes">
            <span class="titulo-card">Pendientes</span>
            <h2><?= $pendientes ?></h2>
            <p>Compras pendientes</p>
        </div>

        <div class="card recibidas">
            <span class="titulo-card">Recibidas</span>
            <h2><?= $recibidas ?></h2>
            <p>Compras recibidas</p>
        </div>

    </div>

    <!-- PANEL DE COMPRAS -->

    <div class="panel">

        <div class="panel-header">

            <div>

                <h2>Compras Registradas</h2>

                <p>
                    Consulta y administra las compras realizadas.
                </p>

            </div>

        </div>

        <!-- BUSCADOR Y BOTONES -->

        <form method="GET" class="buscador">

            <input
                type="text"
                name="buscar"
                id="campoBusqueda"
                class="campo-busqueda"
                placeholder="Buscar compra..."
                value="<?= htmlspecialchars($busqueda) ?>"
                autocomplete="off"
            >

            <button type="submit" class="btn btn-buscar">
                Buscar
            </button>

            <button
                type="button"
                class="btn btn-refrescar"
                onclick="window.location.href='Compras.php'"
            >
                ↻ Refrescar
            </button>

            <a href="NuevaCompra.php" class="btn btn-nuevo">
                + Nueva Compra
            </a>

        </form>

        <!-- TABLA -->

        <div class="tabla-contenedor">

            <table>

                <thead>

                    <tr>
                        <th>Código</th>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if($compras && $compras->num_rows > 0): ?>

                        <?php while($compra = $compras->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars($compra['codigo_compra']) ?>
                                </td>

                                <td>
                                    <?= date('d/m/Y', strtotime($compra['fecha'])) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($compra['proveedor'] ?? 'Sin proveedor') ?>
                                </td>

                                <td>
                                    $<?= number_format($compra['total'], 0, ',', '.') ?>
                                </td>

                                <td>

                                    <span class="estado <?= strtolower($compra['estado']) ?>">
                                        <?= htmlspecialchars($compra['estado']) ?>
                                    </span>

                                </td>

                                <td>

                                    <div class="acciones-tabla">

                                        <a
                                            href="VerCompra.php?id=<?= $compra['id_compra'] ?>"
                                            class="btn-ver"
                                        >
                                            Ver
                                        </a>

                                        <a
                                            href="EditarCompra.php?id=<?= $compra['id_compra'] ?>"
                                            class="btn-editar"
                                        >
                                            Editar
                                        </a>

                                        <a
                                            href="EliminarCompra.php?id=<?= $compra['id_compra'] ?>"
                                            class="btn-eliminar"
                                        >
                                            Eliminar
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>

                            <td colspan="6" class="sin-datos">
                                No hay compras registradas.
                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- BUSQUEDA AUTOMATICA -->

<script>

const campoBusqueda = document.getElementById('campoBusqueda');

let tiempoBusqueda;

campoBusqueda.addEventListener('input', function(){

    clearTimeout(tiempoBusqueda);

    tiempoBusqueda = setTimeout(function(){

        const valor = campoBusqueda.value.trim();

        if(valor === ''){

            window.location.href = 'Compras.php';

        }else{

            window.location.href =
                'Compras.php?buscar=' + encodeURIComponent(valor);

        }

    }, 350);

});

</script>

<?php require_once "../../includes/footer.php"; ?>

