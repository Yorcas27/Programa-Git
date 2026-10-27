
<?php

$titulo = "PEDIDOS";
$css = "Pedidos.css";

include("../includes/header.php");

require_once("../includes/conexion.php");


/* MENSAJES */

$mensaje = $_GET['mensaje'] ?? '';
$tipo_mensaje = $_GET['tipo'] ?? '';


/* CONSULTA DE PEDIDOS */

$sql = "SELECT
            p.id_pedido,
            p.codigo_pedido,
            p.fecha,
            p.fecha_entrega,
            p.total,
            p.estado,
            COALESCE(c.nombre, p.nombre_cliente) AS cliente
        FROM pedidos p
        LEFT JOIN clientes c
            ON p.id_cliente = c.id_cliente
        ORDER BY p.id_pedido DESC";

$resultado = $conexion->query($sql);


/* TARJETAS */

$sql_pendientes = "SELECT COUNT(*) AS total
                   FROM pedidos
                   WHERE estado = 'Pendiente'";

$pendientes = $conexion->query($sql_pendientes)->fetch_assoc()['total'];


$sql_produccion = "SELECT COUNT(*) AS total
                   FROM pedidos
                   WHERE estado = 'En Producción'";

$en_produccion = $conexion->query($sql_produccion)->fetch_assoc()['total'];


$sql_entregados = "SELECT COUNT(*) AS total
                   FROM pedidos
                   WHERE estado = 'Entregado'";

$entregados = $conexion->query($sql_entregados)->fetch_assoc()['total'];


$sql_total = "SELECT COUNT(*) AS total
              FROM pedidos";

$total_pedidos = $conexion->query($sql_total)->fetch_assoc()['total'];

?>

<main class="contenido">

    <!-- ALERTAS -->

    <?php if ($mensaje !== ''): ?>

        <script>
            alert(<?= json_encode($mensaje) ?>);
        </script>

    <?php endif; ?>


    <!-- BOTÓN VOLVER -->

    <section class="barra-superior">

        <a href="../Dashboard/Dashboard.php" class="btn btn-principal">

            ← Volver al Dashboard

        </a>

    </section>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>

            Gestión de Pedidos

        </h1>

        <p>

            Administra los pedidos registrados y realiza seguimiento a su estado.

        </p>

    </header>


    <!-- TARJETAS -->

    <section class="cards">

        <div class="card pendientes">

            <span class="titulo-card">

                Pendientes

            </span>

            <h2><?= $pendientes ?></h2>

            <p>

                Pedidos por iniciar.

            </p>

        </div>


        <div class="card proceso">

            <span class="titulo-card">

                En Producción

            </span>

            <h2><?= $en_produccion ?></h2>

            <p>

                Pedidos en proceso.

            </p>

        </div>


        <div class="card entregados">

            <span class="titulo-card">

                Entregados

            </span>

            <h2><?= $entregados ?></h2>

            <p>

                Pedidos finalizados.

            </p>

        </div>


        <div class="card total">

            <span class="titulo-card">

                Total Pedidos

            </span>

            <h2><?= $total_pedidos ?></h2>

            <p>

                Registros acumulados.

            </p>

        </div>

    </section>


    <!-- PANEL -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>

                    Listado de Pedidos

                </h2>

                <p>

                    Consulta todos los pedidos registrados en el sistema.

                </p>

            </div>


            <div class="barra-acciones">

                <!-- BUSCADOR -->

                <input
                    type="text"
                    class="buscar"
                    placeholder="Buscar pedido..."
                    id="buscarPedido"
                >

                <button
                    type="button"
                    class="btn btn-buscar"
                    id="botonBuscar"
                >

                    Buscar

                </button>


                <!-- REFRESCAR -->

                <a
                    href="Pedidos.php"
                    class="btn btn-refrescar"
                >

                    ↻ Refrescar

                </a>


                <!-- NUEVO PEDIDO -->

                <a
                    href="NuevoPedido.php"
                    class="btn btn-principal"
                >

                    + Nuevo Pedido

                </a>

            </div>

        </div>


        <!-- TABLA -->

        <table class="tabla" id="tablaPedidos">

            <thead>

                <tr>

                    <th>Código</th>

                    <th>Cliente</th>

                    <th>Fecha</th>

                    <th>Total</th>

                    <th>Estado</th>

                    <th>Acciones</th>

                </tr>

            </thead>


            <tbody>

                <?php if ($resultado && $resultado->num_rows > 0): ?>

                    <?php while ($pedido = $resultado->fetch_assoc()): ?>

                        <tr>

                            <td>

                                <?= htmlspecialchars($pedido['codigo_pedido']) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars($pedido['cliente'] ?? 'Cliente ocasional') ?>

                            </td>


                            <td>

                                <?= date(
                                    'd/m/Y',
                                    strtotime($pedido['fecha'])
                                ) ?>

                            </td>


                            <td>

                                $<?= number_format(
                                    $pedido['total'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>

                            </td>


                            <td>

                                <?php if ($pedido['estado'] === 'Pendiente'): ?>

                                    <span class="badge badge-pendiente">

                                        Pendiente

                                    </span>

                                <?php elseif ($pedido['estado'] === 'En Producción'): ?>

                                    <span class="badge badge-proceso">

                                        En Producción

                                    </span>

                                <?php elseif ($pedido['estado'] === 'Cancelado'): ?>

                                    <span class="badge badge-cancelado">

                                        Cancelado

                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-entregado">

                                        Entregado

                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <div class="acciones">

                                    <!-- VER -->

                                    <a
                                        href="VerPedido.php?id=<?= $pedido['id_pedido'] ?>"
                                        class="btn btn-ver"
                                    >

                                        Ver

                                    </a>


                                    <!-- EDITAR -->

                                    <a
                                        href="EditarPedido.php?id=<?= $pedido['id_pedido'] ?>"
                                        class="btn btn-editar"
                                    >

                                        Editar

                                    </a>


                                    <!-- ELIMINAR -->

                                    <a
    href="EliminarPedido.php?id=<?= (int) $pedido['id_pedido'] ?>"
    class="btn btn-eliminar"
>
    Eliminar
</a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="6">

                            No hay pedidos registrados.

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </section>

</main>


<!-- BUSCADOR -->

<script>

const buscador = document.getElementById('buscarPedido');
const botonBuscar = document.getElementById('botonBuscar');
const filas = document.querySelectorAll('#tablaPedidos tbody tr');


function buscarPedidos() {

    const texto = buscador.value.toLowerCase().trim();

    filas.forEach(function (fila) {

        const contenido = fila.textContent.toLowerCase();

        fila.style.display =
            contenido.includes(texto)
            ? ''
            : 'none';

    });

}


/* BUSCAR AL ESCRIBIR */

if (buscador) {

    buscador.addEventListener('keyup', buscarPedidos);

}


/* BUSCAR AL PRESIONAR EL BOTÓN */

if (botonBuscar) {

    botonBuscar.addEventListener('click', buscarPedidos);

}

</script>


<?php

include("../includes/footer.php");

?>

