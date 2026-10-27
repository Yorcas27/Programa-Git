<?php

$titulo = "REPORTE DE PEDIDOS";
$css = "../Reportes.css";

include("../../includes/config.php");
include("../../includes/conexion.php");
include("../../includes/header.php");


/* FILTROS */

$buscar = trim($_GET['buscar'] ?? '');
$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$estado = $_GET['estado'] ?? '';


/* CONSULTA PRINCIPAL */

$sql = "
    SELECT
        p.id_pedido,
        p.codigo_pedido,
        p.fecha,
        p.fecha_entrega,
        p.subtotal,
        p.iva,
        p.total,
        p.estado,
        COALESCE(c.nombre, p.nombre_cliente) AS cliente
    FROM pedidos p
    LEFT JOIN clientes c
        ON p.id_cliente = c.id_cliente
    WHERE 1=1
";

$parametros = [];
$tipos = "";


/* BUSQUEDA */

if ($buscar !== '') {

    $sql .= "
        AND (
            p.codigo_pedido LIKE ?
            OR c.nombre LIKE ?
            OR p.nombre_cliente LIKE ?
        )
    ";

    $buscarLike = "%{$buscar}%";

    $parametros[] = $buscarLike;
    $parametros[] = $buscarLike;
    $parametros[] = $buscarLike;

    $tipos .= "sss";
}


/* FECHA DESDE */

if ($desde !== '') {

    $sql .= " AND DATE(p.fecha) >= ? ";

    $parametros[] = $desde;
    $tipos .= "s";
}


/* FECHA HASTA */

if ($hasta !== '') {

    $sql .= " AND DATE(p.fecha) <= ? ";

    $parametros[] = $hasta;
    $tipos .= "s";
}


/* ESTADO */

if ($estado !== '') {

    $sql .= " AND p.estado = ? ";

    $parametros[] = $estado;
    $tipos .= "s";
}


$sql .= " ORDER BY p.fecha DESC, p.id_pedido DESC";


/* EJECUTAR CONSULTA */

$stmt = $conexion->prepare($sql);

if (!empty($parametros)) {
    $stmt->bind_param($tipos, ...$parametros);
}

$stmt->execute();

$resultado = $stmt->get_result();


/* RESUMEN */

$sqlResumen = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN p.estado = 'Pendiente' THEN 1 ELSE 0 END) AS pendientes,
        SUM(CASE WHEN p.estado = 'En Producción' THEN 1 ELSE 0 END) AS produccion,
        SUM(CASE WHEN p.estado = 'Entregado' THEN 1 ELSE 0 END) AS entregados,
        SUM(CASE WHEN p.estado = 'Cancelado' THEN 1 ELSE 0 END) AS cancelados
    FROM pedidos p
    LEFT JOIN clientes c
        ON p.id_cliente = c.id_cliente
    WHERE 1=1
";

$parametrosResumen = [];
$tiposResumen = "";


/* BUSQUEDA RESUMEN */

if ($buscar !== '') {

    $sqlResumen .= "
        AND (
            p.codigo_pedido LIKE ?
            OR c.nombre LIKE ?
            OR p.nombre_cliente LIKE ?
        )
    ";

    $buscarLikeResumen = "%{$buscar}%";

    $parametrosResumen[] = $buscarLikeResumen;
    $parametrosResumen[] = $buscarLikeResumen;
    $parametrosResumen[] = $buscarLikeResumen;

    $tiposResumen .= "sss";
}


/* FECHA DESDE RESUMEN */

if ($desde !== '') {

    $sqlResumen .= " AND DATE(p.fecha) >= ? ";

    $parametrosResumen[] = $desde;
    $tiposResumen .= "s";
}


/* FECHA HASTA RESUMEN */

if ($hasta !== '') {

    $sqlResumen .= " AND DATE(p.fecha) <= ? ";

    $parametrosResumen[] = $hasta;
    $tiposResumen .= "s";
}


/* ESTADO RESUMEN */

if ($estado !== '') {

    $sqlResumen .= " AND p.estado = ? ";

    $parametrosResumen[] = $estado;
    $tiposResumen .= "s";
}


$stmtResumen = $conexion->prepare($sqlResumen);

if (!empty($parametrosResumen)) {

    $stmtResumen->bind_param(
        $tiposResumen,
        ...$parametrosResumen
    );
}

$stmtResumen->execute();

$resumen = $stmtResumen->get_result()->fetch_assoc();


$totalPedidos = (int)($resumen['total'] ?? 0);
$pendientes = (int)($resumen['pendientes'] ?? 0);
$produccion = (int)($resumen['produccion'] ?? 0);
$entregados = (int)($resumen['entregados'] ?? 0);
$cancelados = (int)($resumen['cancelados'] ?? 0);

?>


<style>
.barra-superior-pedidos{
    transform:translateY(-5px);
}
</style>


<main class="contenido">


    <!-- BARRA SUPERIOR -->

    <section class="barra-superior barra-superior-pedidos">

        <a
            href="../Reportes.php"
            class="btn-regresar"
        >
            ← Volver a Reportes
        </a>

        <a
            href="../../DASHBOARD/Dashboard.php"
            class="btn-inicio"
        >
            ⌂ Inicio
        </a>

    </section>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>
            Reporte de Pedidos
        </h1>

        <p>
            Consulta los pedidos registrados y su estado actual.
        </p>

    </header>


    <!-- RESUMEN -->

    <section class="cards">

        <div class="card">

            <h2>
                <?= $totalPedidos ?>
            </h2>

            <span>
                Total de pedidos
            </span>

        </div>


        <div class="card">

            <h2>
                <?= $pendientes ?>
            </h2>

            <span>
                Pendientes
            </span>

        </div>


        <div class="card">

            <h2>
                <?= $produccion ?>
            </h2>

            <span>
                En producción
            </span>

        </div>


        <div class="card">

            <h2>
                <?= $entregados ?>
            </h2>

            <span>
                Entregados
            </span>

        </div>


        <div class="card">

            <h2>
                <?= $cancelados ?>
            </h2>

            <span>
                Cancelados
            </span>

        </div>

    </section>


    <!-- FILTROS -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Filtros de Consulta
                </h2>

                <p>
                    Filtra los pedidos por código, cliente, fecha o estado.
                </p>

            </div>

        </div>


        <form
            class="formulario"
            method="GET"
            action="ReportePedidos.php"
        >

            <div class="grupo-formulario">


                <div class="campo">

                    <label for="buscar">
                        Buscar
                    </label>

                    <input
                        type="text"
                        id="buscar"
                        name="buscar"
                        value="<?= htmlspecialchars($buscar) ?>"
                        placeholder="Código o cliente"
                    >

                </div>


                <div class="campo">

                    <label for="desde">
                        Desde
                    </label>

                    <input
                        type="date"
                        id="desde"
                        name="desde"
                        value="<?= htmlspecialchars($desde) ?>"
                    >

                </div>


                <div class="campo">

                    <label for="hasta">
                        Hasta
                    </label>

                    <input
                        type="date"
                        id="hasta"
                        name="hasta"
                        value="<?= htmlspecialchars($hasta) ?>"
                    >

                </div>


                <div class="campo">

                    <label for="estado">
                        Estado
                    </label>

                    <select
                        id="estado"
                        name="estado"
                    >

                        <option value="">
                            Todos
                        </option>

                        <option
                            value="Pendiente"
                            <?= $estado === 'Pendiente' ? 'selected' : '' ?>
                        >
                            Pendiente
                        </option>

                        <option
                            value="En Producción"
                            <?= $estado === 'En Producción' ? 'selected' : '' ?>
                        >
                            En Producción
                        </option>

                        <option
                            value="Entregado"
                            <?= $estado === 'Entregado' ? 'selected' : '' ?>
                        >
                            Entregado
                        </option>

                        <option
                            value="Cancelado"
                            <?= $estado === 'Cancelado' ? 'selected' : '' ?>
                        >
                            Cancelado
                        </option>

                    </select>

                </div>


            </div>


            <div class="acciones-formulario">

                <button
                    type="submit"
                    class="btn-consultar"
                >
                    Buscar
                </button>

                <a
                    href="ReportePedidos.php"
                    class="btn-consultar"
                >
                    Refrescar
                </a>

            </div>

        </form>

    </section>


    <!-- TABLA -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Pedidos Registrados
                </h2>

                <p>
                    Resultado de la consulta realizada.
                </p>

            </div>

        </div>


        <table>

            <thead>

                <tr>

                    <th>
                        Código
                    </th>

                    <th>
                        Cliente
                    </th>

                    <th>
                        Fecha
                    </th>

                    <th>
                        Fecha Entrega
                    </th>

                    <th>
                        Total
                    </th>

                    <th>
                        Estado
                    </th>

                    <th>
                        Acción
                    </th>

                </tr>

            </thead>


            <tbody>


                <?php if ($resultado->num_rows > 0): ?>


                    <?php while ($pedido = $resultado->fetch_assoc()): ?>


                        <tr>

                            <td>
                                <?= htmlspecialchars($pedido['codigo_pedido']) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars($pedido['cliente'] ?? '') ?>
                            </td>


                            <td>
                                <?= !empty($pedido['fecha'])
                                    ? date('d/m/Y', strtotime($pedido['fecha']))
                                    : ''
                                ?>
                            </td>


                            <td>
                                <?= !empty($pedido['fecha_entrega'])
                                    ? date('d/m/Y', strtotime($pedido['fecha_entrega']))
                                    : ''
                                ?>
                            </td>


                            <td>
                                $<?= number_format(
                                    (float)$pedido['total'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </td>


                            <td>

                                <?php

                                $claseEstado = 'estado';

                                if ($pedido['estado'] === 'Pendiente') {
                                    $claseEstado .= ' pendiente';
                                } elseif ($pedido['estado'] === 'En Producción') {
                                    $claseEstado .= ' produccion';
                                } elseif ($pedido['estado'] === 'Entregado') {
                                    $claseEstado .= ' entregado';
                                } elseif ($pedido['estado'] === 'Cancelado') {
                                    $claseEstado .= ' cancelado';
                                }

                                ?>

                                <span class="<?= $claseEstado ?>">
                                    <?= htmlspecialchars($pedido['estado']) ?>
                                </span>

                            </td>


                            <td>

                                <a
                                    href="../../PEDIDOS/VerPedido.php?id=<?= (int)$pedido['id_pedido'] ?>&origen=reporte"
                                    class="btn-ver"
                                >
                                    Ver
                                </a>

                            </td>

                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td colspan="7">
                            No se encontraron pedidos con los filtros seleccionados.
                        </td>

                    </tr>


                <?php endif; ?>


            </tbody>

        </table>


        <!-- EXPORTACIONES -->

        <div class="acciones-formulario">

            <a
                href="ExportarPedidosPDF.php?buscar=<?= urlencode($buscar) ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&estado=<?= urlencode($estado) ?>"
                class="btn-exportar"
                onclick="return confirmarExportacion('PDF');"
            >
                Exportar PDF
            </a>

            <a
                href="ExportarPedidosExcel.php?buscar=<?= urlencode($buscar) ?>&desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>&estado=<?= urlencode($estado) ?>"
                class="btn-exportar"
                onclick="return confirmarExportacion('Excel');"
            >
                Exportar Excel
            </a>

        </div>

    </section>


</main>


<script>

function confirmarExportacion(formato){

    return confirm(
        "¿Deseas exportar el reporte de pedidos en " + formato + "?"
    );

}

</script>


<?php include("../../includes/footer.php"); ?>