<?php
$titulo = "Productos Terminados";
$css = "ProductosTerminados.css";

require_once("../../includes/conexion.php");
require_once("../../includes/header.php");

/* FORMATO COLOMBIANO */
function formatoCantidad($cantidad) {
    $cantidad = number_format((float)$cantidad, 2, ',', '.');
    return rtrim(rtrim($cantidad, '0'), ',');
}

/* PRODUCTOS DE LOS ÚLTIMOS 30 DÍAS */
$sql = "
    SELECT pt.*, op.id_orden
    FROM productos_terminados pt
    LEFT JOIN ordenes_produccion op ON pt.id_orden = op.id_orden
    WHERE pt.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY pt.fecha DESC
";
$resultado = $conexion->query($sql);

if (!$resultado) {
    die("Error al consultar los productos terminados: " . $conexion->error);
}

/* PRODUCTOS ANTERIORES PARA DEPURACIÓN */
$sqlDepuracion = "
    SELECT pt.*, op.id_orden
    FROM productos_terminados pt
    LEFT JOIN ordenes_produccion op ON pt.id_orden = op.id_orden
    WHERE pt.fecha < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY pt.fecha DESC
";
$resultadoDepuracion = $conexion->query($sqlDepuracion);

if (!$resultadoDepuracion) {
    die("Error al consultar los productos para depuración: " . $conexion->error);
}

/* MENSAJES */
$mensaje = "";
$tipoMensaje = "";

if (isset($_GET["eliminacion"])) {
    if ($_GET["eliminacion"] === "correcta") {
        $mensaje = "Producto eliminado correctamente.";
        $tipoMensaje = "exito";
    } elseif ($_GET["eliminacion"] === "error") {
        $mensaje = "No fue posible eliminar el producto.";
        $tipoMensaje = "error";
    } elseif ($_GET["eliminacion"] === "orden") {
        $mensaje = "No se puede eliminar un producto asociado a una orden de producción.";
        $tipoMensaje = "error";
    }
}
?>

<main class="contenido">
    <div class="barra-superior">
        <a href="../Produccion/Produccion.php" class="btn-volver">
            ← Volver a Producción
        </a>
        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            Inicio
        </a>
    </div>

    <section class="contenedor-principal">
        <header class="encabezado">
            <h1>Productos Terminados</h1>
            <p>Consulta los productos obtenidos de las órdenes de producción.</p>
        </header>

        <?php if ($mensaje !== ""): ?>
            <div id="mensajeSistema"
                 class="mensaje-contenedor mensaje-<?= htmlspecialchars($tipoMensaje) ?>">
                <span><?= htmlspecialchars($mensaje) ?></span>
                <button type="button" class="cerrar-mensaje"
                        onclick="cerrarMensaje()" aria-label="Cerrar mensaje">&times;</button>
            </div>
        <?php endif; ?>

        <section class="panel">
            <div class="barra-acciones">
                <div class="busqueda">
                    <input type="text" id="buscarProducto"
                           placeholder="Buscar producto..."
                           oninput="buscarProducto()">

                    <button type="button" onclick="buscarProducto()">Buscar</button>
                    <button type="button" onclick="refrescarProductos()">Refrescar</button>
                </div>

                <button type="button" class="btn-nuevo" onclick="irANuevaOrden()">
                    + Nuevo producto
                </button>
            </div>

            <h2>Productos de los últimos 30 días</h2>

            <div class="tabla-contenedor">
                <table class="tabla" id="tablaProductos">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultado->num_rows > 0): ?>
                            <?php while ($producto = $resultado->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($producto["codigo"] ?? "") ?></td>
                                    <td>
                                        <?= !empty($producto["fecha"])
                                            ? date("d/m/Y", strtotime($producto["fecha"]))
                                            : "" ?>
                                    </td>
                                    <td><?= htmlspecialchars($producto["producto"] ?? "") ?></td>
                                    <td><?= formatoCantidad($producto["cantidad"] ?? 0) ?></td>
                                    <td><?= htmlspecialchars($producto["estado"] ?? "") ?></td>
                                    <td class="acciones">
                                        <a href="VerProducto.php?id=<?= (int)$producto["id_producto_terminado"] ?>"
                                           class="btn-ver">Ver</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    No hay productos terminados registrados durante los últimos 30 días.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h2>Productos anteriores para depuración</h2>

            <div class="tabla-contenedor">
                <table class="tabla" id="tablaDepuracion">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($resultadoDepuracion->num_rows > 0): ?>
                            <?php while ($producto = $resultadoDepuracion->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($producto["codigo"] ?? "") ?></td>
                                    <td>
                                        <?= !empty($producto["fecha"])
                                            ? date("d/m/Y", strtotime($producto["fecha"]))
                                            : "" ?>
                                    </td>
                                    <td><?= htmlspecialchars($producto["producto"] ?? "") ?></td>
                                    <td><?= formatoCantidad($producto["cantidad"] ?? 0) ?></td>
                                    <td><?= htmlspecialchars($producto["estado"] ?? "") ?></td>
                                    <td class="acciones">
                                        <a href="VerProducto.php?id=<?= (int)$producto["id_producto_terminado"] ?>"
                                           class="btn-ver">Ver</a>

                                        <?php if (empty($producto["id_orden"])): ?>
                                            <form action="EliminarProducto.php" method="POST"
                                                  onsubmit="return confirm('¿Estás seguro de eliminar este producto?');">
                                                <input type="hidden"
                                                       name="id_producto_terminado"
                                                       value="<?= (int)$producto["id_producto_terminado"] ?>">
                                                <button type="submit" class="btn-eliminar">
                                                    Eliminar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">No hay productos anteriores para depuración.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</main>

<script>
function buscarProducto() {
    const campo = document.getElementById("buscarProducto");
    const texto = campo.value.toLocaleLowerCase("es-CO").trim();

    document.querySelectorAll("#tablaProductos tbody tr, #tablaDepuracion tbody tr")
        .forEach(function(fila) {
            fila.style.display = fila.textContent
                .toLocaleLowerCase("es-CO")
                .includes(texto) ? "" : "none";
        });
}

function refrescarProductos() {
    window.location.href = "ProductosTerminados.php";
}

function irANuevaOrden() {
    alert("Los productos terminados se registran automáticamente al finalizar una orden de producción. Serás dirigido al módulo de Órdenes de Producción.");
    window.location.href = "/YCL_SOLUTIONS/PRODUCCION/OrdenesProduccion/NuevaOrden.php";
}

function cerrarMensaje() {
    const mensaje = document.getElementById("mensajeSistema");
    if (mensaje) mensaje.classList.add("oculto");
}

document.addEventListener("DOMContentLoaded", function() {
    const mensaje = document.getElementById("mensajeSistema");

    if (mensaje) {
        setTimeout(function() {
            mensaje.classList.add("oculto");
        }, 4000);
    }
});
</script>

<?php include("../../includes/footer.php"); ?>