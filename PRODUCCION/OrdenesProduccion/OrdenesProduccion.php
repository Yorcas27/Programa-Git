
<?php
$titulo = "Órdenes de Producción";
$css = "OrdenesProduccion.css";

require_once("../../includes/conexion.php");
require_once("../../includes/header.php");

$resultado = $conexion->query("
    SELECT o.id_orden, o.codigo, o.fecha, o.cantidad, o.estado,
           o.fecha_entrega, o.fecha_finalizacion, o.fecha_entrega_real,
           p.nombre AS producto, p.presentacion AS presentacion
    FROM ordenes_produccion o
    INNER JOIN productos p ON o.id_producto = p.id_producto
    ORDER BY o.id_orden DESC
");

$ordenesDepuracion = $conexion->query("
    SELECT o.id_orden, o.codigo, o.fecha_entrega_real, o.cantidad,
           p.nombre AS producto, p.presentacion AS presentacion,
           DATEDIFF(CURDATE(), o.fecha_entrega_real) AS dias_transcurridos
    FROM ordenes_produccion o
    INNER JOIN productos p ON o.id_producto = p.id_producto
    WHERE o.estado = 'Entregada'
      AND o.fecha_entrega_real IS NOT NULL
      AND o.fecha_entrega_real <= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY o.fecha_entrega_real ASC
");

$estadosDisponibles = [
    "Pendiente" => ["En Producción", "Anulada"],
    "En Producción" => ["Finalizada", "Anulada"],
    "Finalizada" => ["Entregada"],
    "Entregada" => [],
    "Anulada" => []
];
?>

<main class="contenido">
    <section class="barra-superior">
        <a href="../Produccion/Produccion.php" class="btn btn-verde">← Volver a Producción</a>
        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">⌂ Inicio</a>
    </section>

    <header class="encabezado">
        <h1><?= htmlspecialchars($titulo) ?></h1>
        <p>Gestione las órdenes de producción, programe nuevas fabricaciones y realice el seguimiento de cada proceso.</p>
    </header>

    <section class="panel" style="margin-bottom:20px;border-left:5px solid #f39c12;">
        <div class="panel-header">
            <div>
                <h2 style="color:#1f3f6d;">Órdenes pendientes de depuración</h2>
                <p>Órdenes entregadas hace 30 días o más. La eliminación es manual.</p>
            </div>
            <strong style="color:#f39c12;">
                <?= $ordenesDepuracion ? $ordenesDepuracion->num_rows : 0 ?> pendiente(s)
            </strong>
        </div>

        <?php if ($ordenesDepuracion && $ordenesDepuracion->num_rows > 0): ?>
            <div style="overflow-x:auto;">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>N.º Orden</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Entrega real</th>
                            <th>Días transcurridos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($depuracion = $ordenesDepuracion->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($depuracion["codigo"]) ?></td>
                                <td><?= htmlspecialchars($depuracion["producto"] . " — " . $depuracion["presentacion"]) ?></td>
                                <td><?= number_format($depuracion["cantidad"], 0, ",", ".") ?></td>
                                <td><?= date("d/m/Y", strtotime($depuracion["fecha_entrega_real"])) ?></td>
                                <td><?= (int)$depuracion["dias_transcurridos"] ?> días</td>
                                <td class="acciones">
                                    <a href="VerOrden.php?id=<?= (int)$depuracion["id_orden"] ?>" class="btn-ver">Ver</a>
                                    <button
                                        type="button"
                                        class="btn-eliminar"
                                        onclick='eliminarOrden(<?= (int)$depuracion["id_orden"] ?>, <?= json_encode($depuracion["codigo"], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'>
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="padding:12px 0;">No hay órdenes que cumplan los 30 días de antigüedad.</p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Listado de Órdenes</h2>
                <p>Consulte y administre todas las órdenes registradas.</p>
            </div>
            <div class="barra-acciones">
                <input type="text" id="buscarOrden" placeholder="Buscar orden...">
                <button type="button" class="btn btn-verde" onclick="buscarOrden()">Buscar</button>
                <button type="button" class="btn btn-principal" onclick="refrescarOrdenes()">↻ Refrescar</button>
                <a href="NuevaOrden.php" class="btn btn-verde">+ Nueva Orden</a>
            </div>
        </div>

        <div id="mensajeEstado" role="status" aria-live="polite"
             style="display:none;padding:12px;margin:12px 0;border-radius:6px;"></div>

        <table class="tabla" id="tablaOrdenes">
            <thead>
                <tr>
                    <th>N.º Orden</th>
                    <th>Fecha</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <?php while ($orden = $resultado->fetch_assoc()): ?>
                        <?php
                        $estadoActual = $orden["estado"];
                        $transiciones = $estadosDisponibles[$estadoActual] ?? [];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($orden["codigo"]) ?></td>
                            <td><?= !empty($orden["fecha"]) ? date("d/m/Y", strtotime($orden["fecha"])) : "—" ?></td>
                            <td><?= htmlspecialchars($orden["producto"] . " — " . $orden["presentacion"]) ?></td>
                            <td><?= number_format($orden["cantidad"], 0, ",", ".") ?></td>
                            <td>
                                <div style="display:flex;flex-direction:column;gap:7px;min-width:150px;">
                                    <strong><?= htmlspecialchars($estadoActual) ?></strong>
                                    <?php if (!empty($transiciones)): ?>
                                        <select
                                            class="selector-estado"
                                            aria-label="Cambiar estado de <?= htmlspecialchars($orden["codigo"]) ?>"
                                            onchange="cambiarEstado(<?= (int)$orden["id_orden"] ?>, this, <?= (int)$orden["cantidad"] ?>)"
                                            style="max-width:180px;padding:7px;border:1px solid #ccc;border-radius:5px;">
                                            <option value="">Cambiar estado...</option>
                                            <?php foreach ($transiciones as $estado): ?>
                                                <option value="<?= htmlspecialchars($estado) ?>"><?= htmlspecialchars($estado) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <small>Orden cerrada</small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="acciones">
                                <a href="VerOrden.php?id=<?= (int)$orden["id_orden"] ?>" class="btn-ver">Ver</a>
                                <button
                                    type="button"
                                    class="btn-editar"
                                    onclick="redirigirPedidos()">
                                    Editar
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">No hay órdenes de producción registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</main>

<script>
function redirigirPedidos() {
    if (confirm("Serás redirigido al módulo Pedidos para editar la información correspondiente. ¿Deseas continuar?")) {
        window.location.href = "/YCL_Solutions/PEDIDOS/Pedidos.php";
    }
}

function mostrarMensaje(mensaje, exito) {
    const elemento = document.getElementById("mensajeEstado");
    elemento.textContent = mensaje;
    elemento.style.display = "block";
    elemento.style.backgroundColor = exito ? "#e8f5e9" : "#fdecea";
    elemento.style.color = exito ? "#1b5e20" : "#b71c1c";
    elemento.style.border = exito ? "1px solid #a5d6a7" : "1px solid #ef9a9a";
    elemento.scrollIntoView({behavior: "smooth", block: "nearest"});
}

async function cambiarEstado(idOrden, selector, cantidadSolicitada) {
    const nuevoEstado = selector.value;
    if (!nuevoEstado) return;

    let cantidadFabricada = null;

    if (nuevoEstado === "Finalizada") {
        const respuestaCantidad = prompt(
            "¿Cuántas unidades se fabricaron realmente?\nCantidad solicitada: " + cantidadSolicitada,
            cantidadSolicitada
        );

        if (respuestaCantidad === null) {
            selector.value = "";
            return;
        }

        cantidadFabricada = respuestaCantidad.trim().replace(",", ".");
        const cantidad = Number(cantidadFabricada);

        if (!cantidadFabricada || !Number.isFinite(cantidad) || cantidad <= 0 || cantidad > cantidadSolicitada) {
            mostrarMensaje("Ingresa una cantidad mayor que cero y no superior a la solicitada.", false);
            selector.value = "";
            return;
        }
    }

    if (!confirm("¿Confirmas el cambio de estado de esta orden a \"" + nuevoEstado + "\"?")) {
        selector.value = "";
        return;
    }

    selector.disabled = true;
    const datos = new URLSearchParams();
    datos.append("id_orden", idOrden);
    datos.append("estado", nuevoEstado);

    if (nuevoEstado === "Finalizada") {
        datos.append("cantidad_fabricada", cantidadFabricada);
    }

    try {
        const respuesta = await fetch("CambiarEstadoOrden.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"},
            body: datos.toString()
        });

        const resultado = await respuesta.json();
        mostrarMensaje(resultado.mensaje || "Respuesta recibida.", respuesta.ok && resultado.exito);

        if (respuesta.ok && resultado.exito) {
            setTimeout(() => window.location.reload(), 700);
        } else {
            selector.disabled = false;
            selector.value = "";
        }
    } catch (error) {
        mostrarMensaje("No fue posible comunicarse con el servidor. Intenta nuevamente.", false);
        selector.disabled = false;
        selector.value = "";
    }
}

async function eliminarOrden(idOrden, codigo) {
    if (!confirm("¿Deseas eliminar la orden " + codigo + "?\n\nEsta acción es permanente y no se puede deshacer.\nVerifica que no necesites conservar información relacionada.")) return;

    const botones = document.querySelectorAll(".btn-eliminar");
    botones.forEach(boton => boton.disabled = true);

    const datos = new URLSearchParams();
    datos.append("id_orden", idOrden);

    try {
        const respuesta = await fetch("EliminarOrden.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"},
            body: datos.toString()
        });

        const resultado = await respuesta.json();
        mostrarMensaje(resultado.mensaje || "Respuesta recibida.", respuesta.ok && resultado.exito);

        if (respuesta.ok && resultado.exito) {
            setTimeout(() => window.location.reload(), 1000);
        } else {
            botones.forEach(boton => boton.disabled = false);
        }
    } catch (error) {
        mostrarMensaje("No fue posible comunicarse con el servidor. Verifica el resultado antes de volver a intentarlo.", false);
        botones.forEach(boton => boton.disabled = false);
    }
}

function buscarOrden() {
    const texto = document.getElementById("buscarOrden").value.toLowerCase().trim();
    document.querySelectorAll("#tablaOrdenes tbody tr").forEach(fila => {
        fila.style.display = fila.textContent.toLowerCase().includes(texto) ? "" : "none";
    });
}

function refrescarOrdenes() {
    document.getElementById("buscarOrden").value = "";
    window.location.reload();
}

document.getElementById("buscarOrden").addEventListener("input", buscarOrden);
document.getElementById("buscarOrden").addEventListener("keyup", function(event) {
    if (event.key === "Enter") buscarOrden();
});
</script>

<?php include("../../includes/footer.php"); ?>
