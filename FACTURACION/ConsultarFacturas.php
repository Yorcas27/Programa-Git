<?php

$titulo = "Consultar Facturas";
$css = "Facturacion.css";

include("../includes/config.php");
require_once("../includes/conexion.php");
include("../includes/header.php");

/* FILTROS */

$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

/* CONSULTAR CLIENTES CON FACTURAS */

$sqlClientes = "SELECT DISTINCT
                    f.id_cliente,
                    COALESCE(
                        NULLIF(c.nombre, ''),
                        NULLIF(p.nombre_cliente, ''),
                        'Cliente ocasional'
                    ) AS nombre
                FROM facturas f
                LEFT JOIN clientes c
                    ON f.id_cliente = c.id_cliente
                INNER JOIN pedidos p
                    ON f.id_pedido = p.id_pedido
                ORDER BY nombre ASC";

$resultadoClientes = $conexion->query($sqlClientes);

/* CONSULTAR FACTURAS: REGISTRADAS Y OCASIONALES */

$sql = "SELECT
            f.id_factura,
            f.numero_factura,
            COALESCE(
                NULLIF(c.nombre, ''),
                NULLIF(p.nombre_cliente, ''),
                'Cliente ocasional'
            ) AS cliente,
            f.fecha,
            f.total,
            f.estado
        FROM facturas f
        LEFT JOIN clientes c
            ON f.id_cliente = c.id_cliente
        INNER JOIN pedidos p
            ON f.id_pedido = p.id_pedido
        WHERE 1=1";

/* BUSCAR POR NÚMERO DE FACTURA */

if ($buscar !== '') {
    $buscarSeguro = $conexion->real_escape_string($buscar);
    $sql .= " AND f.numero_factura LIKE '%$buscarSeguro%'";
}

/* FILTRAR CLIENTE */

if ($cliente !== '') {
    if ($cliente === 'ocasional') {
        $sql .= " AND f.id_cliente IS NULL";
    } else {
        $clienteSeguro = (int) $cliente;
        $sql .= " AND f.id_cliente = $clienteSeguro";
    }
}

/* FILTRAR ESTADO */

if ($estado !== '') {
    $estadoSeguro = $conexion->real_escape_string($estado);
    $sql .= " AND f.estado = '$estadoSeguro'";
}

/* ORDEN */

$sql .= " ORDER BY f.id_factura DESC";

$resultadoFacturas = $conexion->query($sql);

?>

<div class="pagina-factura">

    <!-- BOTONES SUPERIORES -->

    <div class="factura-superior">

        <a href="Facturacion.php" class="btn-regresar">
            ← Volver a Facturación
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </div>

    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>Consultar Facturas</h1>

        <p>
            Consulta y búsqueda de facturas registradas en el sistema.
        </p>

    </header>

    <!-- FILTROS -->

    <section class="panel">

        <h2>Filtros de búsqueda</h2>

        <form method="GET" class="filtros" id="formFiltros">

            <div class="campo">

                <label>Número Factura</label>

                <input
                    type="text"
                    name="buscar"
                    id="buscar"
                    placeholder="Ej: FAC001"
                    value="<?= htmlspecialchars($buscar) ?>"
                    autocomplete="off"
                >

            </div>

            <div class="campo">

                <label>Cliente</label>

                <select name="cliente" id="cliente">

                    <option value="">Todos</option>

                    <?php if ($resultadoClientes && $resultadoClientes->num_rows > 0): ?>

                        <?php while ($clienteBD = $resultadoClientes->fetch_assoc()): ?>

                            <?php
                            $valorCliente = $clienteBD['id_cliente'] === null
                                ? 'ocasional'
                                : (string) $clienteBD['id_cliente'];
                            ?>

                            <option
                                value="<?= htmlspecialchars($valorCliente) ?>"
                                <?= $cliente === $valorCliente ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($clienteBD['nombre']) ?>
                            </option>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </select>

            </div>

            <div class="campo">

                <label>Estado</label>

                <select name="estado" id="estado">

                    <option value="">Todos</option>

                    <option
                        value="Pagada"
                        <?= $estado === 'Pagada' ? 'selected' : '' ?>
                    >
                        Pagada
                    </option>

                    <option
                        value="Pendiente"
                        <?= $estado === 'Pendiente' ? 'selected' : '' ?>
                    >
                        Pendiente
                    </option>

                    <option
                        value="Anulada"
                        <?= $estado === 'Anulada' ? 'selected' : '' ?>
                    >
                        Anulada
                    </option>

                </select>

            </div>

            <button type="submit" class="btn-accion">
                Buscar
            </button>

            <a href="ConsultarFacturas.php" class="btn-accion">
                Refrescar
            </a>

        </form>

    </section>

    <!-- TABLA -->

    <section class="panel">

        <h2>Facturas registradas</h2>

        <?php if ($resultadoFacturas && $resultadoFacturas->num_rows > 0): ?>

            <table>

                <thead>

                    <tr>
                        <th>Factura</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>

                </thead>

                <tbody>

                    <?php while ($factura = $resultadoFacturas->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($factura['numero_factura']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($factura['cliente']) ?>
                            </td>

                            <td>
                                <?= date('d/m/Y', strtotime($factura['fecha'])) ?>
                            </td>

                            <td>
                                $<?= number_format($factura['total'], 0, ',', '.') ?>
                            </td>

                            <td>

                                <?php if ($factura['estado'] === 'Pagada'): ?>

                                    <span class="pagada">Pagada</span>

                                <?php elseif ($factura['estado'] === 'Pendiente'): ?>

                                    <span class="pendiente">Pendiente</span>

                                <?php elseif ($factura['estado'] === 'Anulada'): ?>

                                    <span class="anulada">Anulada</span>

                                <?php else: ?>

                                    <?= htmlspecialchars($factura['estado']) ?>

                                <?php endif; ?>

                            </td>

                            <td>

                                <a
                                    href="VerFactura.php?id_factura=<?= (int) $factura['id_factura'] ?>"
                                    class="btn-ver"
                                >
                                    Ver
                                </a>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        <?php else: ?>

            <p>
                No se encontraron facturas con los criterios seleccionados.
            </p>

        <?php endif; ?>

    </section>

</div>

<!-- BÚSQUEDA AUTOMÁTICA -->

<script>
const formFiltros = document.getElementById('formFiltros');
const buscar = document.getElementById('buscar');
const cliente = document.getElementById('cliente');
const estado = document.getElementById('estado');

let temporizador;

function buscarAutomaticamente() {
    clearTimeout(temporizador);

    temporizador = setTimeout(function() {
        formFiltros.submit();
    }, 300);
}

buscar.addEventListener('input', buscarAutomaticamente);
cliente.addEventListener('change', buscarAutomaticamente);
estado.addEventListener('change', buscarAutomaticamente);
</script>

<?php include("../includes/footer.php"); ?>