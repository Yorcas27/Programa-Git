
<?php
$titulo = "Historial de Producción";
$css = "HistorialProduccion.css";

require_once("../../includes/conexion.php");
require_once("../../includes/header.php");

$resultado = $conexion->query("
    SELECT
        op.id_orden,
        op.codigo,
        op.fecha,
        op.id_producto,
        op.cantidad,
        op.responsable,
        op.fecha_entrega,
        op.estado,
        p.nombre AS nombre_producto
    FROM ordenes_produccion op
    LEFT JOIN productos p
        ON op.id_producto = p.id_producto
    WHERE op.estado = 'Finalizada'
    ORDER BY op.id_orden DESC
");
?>

<main class="contenido">

    <section class="barra-superior">
        <a href="../Produccion/Produccion.php" class="btn btn-principal">
            ← Volver a Producción
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>
    </section>

    <header class="encabezado">
        <h1><?= htmlspecialchars($titulo) ?></h1>
        <p>Consulte las órdenes finalizadas y los productos fabricados.</p>
    </header>

    <section class="panel">

        <div class="panel-header">
            <div>
                <h2>Registro Histórico de Producción</h2>
                <p>Órdenes de producción finalizadas.</p>
            </div>

            <div class="barra-acciones">
                <input
                    type="text"
                    id="buscarProduccion"
                    placeholder="Buscar historial..."
                    autocomplete="off"
                >

                <button
                    type="button"
                    class="btn btn-principal"
                    onclick="buscarProduccion()"
                >
                    Buscar
                </button>

                <button
                    type="button"
                    class="btn btn-principal"
                    onclick="refrescarProduccion()"
                >
                    ↻ Refrescar
                </button>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="tabla" id="tablaProduccion">
                <thead>
                    <tr>
                        <th>Código de historial</th>
                        <th>Fecha</th>
                        <th>Orden</th>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Responsable</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($resultado && $resultado->num_rows > 0): ?>
                        <?php while ($produccion = $resultado->fetch_assoc()): ?>
                            <tr class="fila-produccion">
                                <td>
                                    HP-<?= str_pad(
                                        (string)$produccion["id_orden"],
                                        4,
                                        "0",
                                        STR_PAD_LEFT
                                    ) ?>
                                </td>

                                <td>
                                    <?= !empty($produccion["fecha"])
                                        ? date("d/m/Y", strtotime($produccion["fecha"]))
                                        : "—" ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($produccion["codigo"] ?? "—") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $produccion["nombre_producto"] ?? "No disponible"
                                    ) ?>
                                </td>

                                <td>
                                    <?= number_format(
                                        (float)$produccion["cantidad"],
                                        2,
                                        ",",
                                        "."
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($produccion["responsable"] ?? "—") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($produccion["estado"]) ?>
                                </td>

                                <td class="acciones">
                                    <a
                                        href="VerProduccion.php?id=<?= (int)$produccion["id_orden"] ?>"
                                        class="btn-verde"
                                    >Ver</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="fila-vacia">
                            <td colspan="8">
                                No hay producciones finalizadas registradas.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div
            id="mensajeBusqueda"
            class="mensaje-busqueda"
            role="status"
            style="display:none;"
        >
            No se encontraron resultados para la búsqueda.
        </div>

    </section>

</main>

<style>
.mensaje-busqueda{
    margin-top:15px;
    padding:12px 16px;
    border:1px solid #f2c3c7;
    border-radius:7px;
    background:#fcebed;
    color:#a52834;
    font-size:14px;
}
</style>

<script>
function buscarProduccion(){
    const entrada = document.getElementById("buscarProduccion");
    const texto = entrada.value.toLowerCase().trim();
    const filas = document.querySelectorAll(
        "#tablaProduccion tbody tr.fila-produccion"
    );
    const mensaje = document.getElementById("mensajeBusqueda");
    let encontrados = 0;

    filas.forEach(function(fila){
        const coincide = fila.textContent.toLowerCase().includes(texto);
        fila.style.display = coincide ? "" : "none";

        if(coincide){
            encontrados++;
        }
    });

    mensaje.style.display =
        texto !== "" && encontrados === 0 ? "block" : "none";
}

function refrescarProduccion(){
    window.location.href = "Historial.php";
}

document
    .getElementById("buscarProduccion")
    .addEventListener("input", buscarProduccion);

document
    .getElementById("buscarProduccion")
    .addEventListener("keydown", function(event){
        if(event.key === "Enter"){
            event.preventDefault();
            buscarProduccion();
        }
    });
</script>

<?php include("../../includes/footer.php"); ?>