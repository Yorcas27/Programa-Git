<?php

$titulo = "Reporte de Facturación";
$css = "../Reportes.css";

include("../../includes/config.php");
include("../../includes/conexion.php");
include("../../includes/header.php");


/* FILTROS */

$buscar = trim($_GET["buscar"] ?? "");
$desde = $_GET["desde"] ?? "";
$hasta = $_GET["hasta"] ?? "";

$metodo_pago = $_GET["metodo_pago"] ?? "Todos";
$estado = $_GET["estado"] ?? "Todos";


/* VALIDAR MÉTODO DE PAGO */

$metodos_validos = [
    "Todos",
    "Efectivo",
    "Transferencia",
    "Tarjeta"
];

if (!in_array($metodo_pago, $metodos_validos, true)) {
    $metodo_pago = "Todos";
}


/* VALIDAR ESTADO */

$estados_validos = [
    "Todos",
    "Pendiente",
    "Pagada",
    "Anulada"
];

if (!in_array($estado, $estados_validos, true)) {
    $estado = "Todos";
}


/* VALIDAR FECHAS */

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
    $desde = "";
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
    $hasta = "";
}


/* CONSTRUIR CONSULTA */

$where = [];
$params = [];
$types = "";


/* BÚSQUEDA */

if ($buscar !== "") {

    $where[] = "(
        f.numero_factura LIKE ?
        OR p.codigo_pedido LIKE ?
        OR c.nombre LIKE ?
        OR p.nombre_cliente LIKE ?
    )";

    $valor_busqueda = "%" . $buscar . "%";

    $params[] = $valor_busqueda;
    $params[] = $valor_busqueda;
    $params[] = $valor_busqueda;
    $params[] = $valor_busqueda;

    $types .= "ssss";
}


/* FECHA DESDE */

if ($desde !== "") {

    $where[] = "f.fecha >= ?";

    $params[] = $desde;

    $types .= "s";
}


/* FECHA HASTA */

if ($hasta !== "") {

    $where[] = "f.fecha <= ?";

    $params[] = $hasta;

    $types .= "s";
}


/* MÉTODO DE PAGO */

if ($metodo_pago !== "Todos") {

    $where[] = "f.metodo_pago = ?";

    $params[] = $metodo_pago;

    $types .= "s";
}


/* ESTADO */

if ($estado !== "Todos") {

    $where[] = "f.estado = ?";

    $params[] = $estado;

    $types .= "s";
}


/* WHERE FINAL */

$where_sql = "";

if (!empty($where)) {
    $where_sql = "WHERE " . implode(" AND ", $where);
}


/* CONSULTA PRINCIPAL */

$sql = "
    SELECT
        f.id_factura,
        f.numero_factura,
        f.fecha,
        f.metodo_pago,
        f.total,
        f.estado,
        p.codigo_pedido,
        COALESCE(
            NULLIF(c.nombre, ''),
            NULLIF(p.nombre_cliente, ''),
            'Cliente ocasional'
        ) AS cliente
    FROM facturas f
    INNER JOIN pedidos p
        ON f.id_pedido = p.id_pedido
    LEFT JOIN clientes c
        ON f.id_cliente = c.id_cliente
    $where_sql
    ORDER BY f.fecha DESC, f.id_factura DESC
";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$resultado = $stmt->get_result();


/* RESUMEN */

$sql_resumen = "
    SELECT
        COUNT(*) AS total_facturas,
        SUM(CASE WHEN f.estado = 'Pendiente' THEN 1 ELSE 0 END) AS pendientes,
        SUM(CASE WHEN f.estado = 'Pagada' THEN 1 ELSE 0 END) AS pagadas,
        SUM(CASE WHEN f.estado = 'Anulada' THEN 1 ELSE 0 END) AS anuladas
    FROM facturas f
    INNER JOIN pedidos p
        ON f.id_pedido = p.id_pedido
    LEFT JOIN clientes c
        ON f.id_cliente = c.id_cliente
    $where_sql
";

$stmt_resumen = $conexion->prepare($sql_resumen);

if (!$stmt_resumen) {
    die("Error al preparar el resumen: " . $conexion->error);
}

if (!empty($params)) {
    $stmt_resumen->bind_param($types, ...$params);
}

$stmt_resumen->execute();

$resumen = $stmt_resumen->get_result()->fetch_assoc();

$total_facturas = (int)($resumen["total_facturas"] ?? 0);
$pendientes = (int)($resumen["pendientes"] ?? 0);
$pagadas = (int)($resumen["pagadas"] ?? 0);
$anuladas = (int)($resumen["anuladas"] ?? 0);


/* PARÁMETROS PARA EXPORTAR */

$params_exportar = http_build_query([
    "buscar" => $buscar,
    "desde" => $desde,
    "hasta" => $hasta,
    "metodo_pago" => $metodo_pago,
    "estado" => $estado
]);

?>

<main class="contenido">

    <!-- BARRA SUPERIOR -->

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

        <h1>Reporte de Facturación</h1>

        <p>
            Consulta las facturas generadas, sus estados,
            métodos de pago y valores registrados.
        </p>

    </header>


    <!-- RESUMEN -->

    <section class="cards">

        <div class="card">

            <h2><?= $total_facturas ?></h2>

            <span>
                Facturas Totales
            </span>

        </div>


        <div class="card">

            <h2><?= $pendientes ?></h2>

            <span>
                Pendientes
            </span>

        </div>


        <div class="card">

            <h2><?= $pagadas ?></h2>

            <span>
                Pagadas
            </span>

        </div>


        <div class="card">

            <h2><?= $anuladas ?></h2>

            <span>
                Anuladas
            </span>

        </div>

    </section>


    <!-- FILTROS -->

    <section class="panel">

        <div class="panel-header">

            <h2>Filtros del Reporte</h2>

        </div>


        <form method="GET" class="formulario">

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
                        placeholder="Factura, pedido o cliente"
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

                    <label for="metodo_pago">
                        Método de Pago
                    </label>

                    <select id="metodo_pago" name="metodo_pago">

                        <option value="Todos" <?= $metodo_pago === "Todos" ? "selected" : "" ?>>
                            Todos
                        </option>

                        <option value="Efectivo" <?= $metodo_pago === "Efectivo" ? "selected" : "" ?>>
                            Efectivo
                        </option>

                        <option value="Transferencia" <?= $metodo_pago === "Transferencia" ? "selected" : "" ?>>
                            Transferencia
                        </option>

                        <option value="Tarjeta" <?= $metodo_pago === "Tarjeta" ? "selected" : "" ?>>
                            Tarjeta
                        </option>

                    </select>

                </div>


                <div class="campo">

                    <label for="estado">
                        Estado
                    </label>

                    <select id="estado" name="estado">

                        <option value="Todos" <?= $estado === "Todos" ? "selected" : "" ?>>
                            Todos
                        </option>

                        <option value="Pendiente" <?= $estado === "Pendiente" ? "selected" : "" ?>>
                            Pendiente
                        </option>

                        <option value="Pagada" <?= $estado === "Pagada" ? "selected" : "" ?>>
                            Pagada
                        </option>

                        <option value="Anulada" <?= $estado === "Anulada" ? "selected" : "" ?>>
                            Anulada
                        </option>

                    </select>

                </div>

            </div>


            <div class="acciones-formulario">

                <button type="submit" class="btn-consultar">
                    Consultar
                </button>

                <a href="ReporteFacturacion.php" class="btn-verde">
                    Limpiar
                </a>

            </div>

        </form>

    </section>


    <!-- RESULTADOS -->

    <section class="panel">

        <div class="panel-header">

            <h2>Facturas Generadas</h2>

        </div>


        <table>

            <thead>

                <tr>

                    <th>Factura</th>
                    <th>Cliente</th>
                    <th>Pedido</th>
                    <th>Fecha</th>
                    <th>Método Pago</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Acción</th>

                </tr>

            </thead>


            <tbody>

                <?php if ($resultado->num_rows > 0): ?>

                    <?php while ($factura = $resultado->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($factura["numero_factura"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($factura["cliente"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($factura["codigo_pedido"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($factura["fecha"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($factura["metodo_pago"]) ?>
                            </td>

                            <td>
                                $<?= number_format((float)$factura["total"], 0, ",", ".") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($factura["estado"]) ?>
                            </td>

                            <td>

                                <a
                                    href="../../FACTURACION/VerFactura.php?id_factura=<?= (int)$factura["id_factura"] ?>"
                                    class="btn-ver"
                                >
                                    Ver
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="8">
                            No se encontraron facturas con los filtros seleccionados.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </section>


    <!-- EXPORTACIONES -->

    <section class="panel">

        <div class="panel-header">

            <h2>Exportar Reporte</h2>

        </div>


        <div class="acciones-formulario">

            <a
                href="ExportarFacturacionPDF.php?<?= htmlspecialchars($params_exportar) ?>"
                class="btn-exportar"
                onclick="return confirmarExportacion('PDF');"
            >
                Exportar PDF
            </a>


            <a
                href="ExportarFacturacionExcel.php?<?= htmlspecialchars($params_exportar) ?>"
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
        "¿Deseas exportar el reporte de facturación en " + formato + "?"
    );

}

</script>


<?php include("../../includes/footer.php"); ?>