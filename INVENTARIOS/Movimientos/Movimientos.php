
<?php

$titulo = "MOVIMIENTOS";
$css = "Movimientos.css";

include("../../includes/header.php");
require_once("../../includes/conexion.php");


// INDICADORES

$hoy = date("Y-m-d");
$mes = date("Y-m");


// Entradas de hoy

$sql = "SELECT COUNT(*) AS total
        FROM movimientos
        WHERE tipo = 'Entrada' AND fecha = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $hoy);
$stmt->execute();

$entradasHoy = $stmt->get_result()->fetch_assoc()['total'];


// Salidas de hoy

$sql = "SELECT COUNT(*) AS total
        FROM movimientos
        WHERE tipo = 'Salida' AND fecha = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $hoy);
$stmt->execute();

$salidasHoy = $stmt->get_result()->fetch_assoc()['total'];


// Movimientos del mes

$sql = "SELECT COUNT(*) AS total
        FROM movimientos
        WHERE DATE_FORMAT(fecha, '%Y-%m') = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $mes);
$stmt->execute();

$movimientosMes = $stmt->get_result()->fetch_assoc()['total'];


// Total de movimientos

$sql = "SELECT COUNT(*) AS total
        FROM movimientos";

$resultado = $conexion->query($sql);

$totalMovimientos = $resultado->fetch_assoc()['total'];


// HISTORIAL

$sql = "SELECT
            m.id_movimiento,
            m.codigo,
            m.fecha,
            m.tipo,
            i.producto AS insumo,
            m.cantidad,
            m.unidad,
            m.responsable,
            m.estado
        FROM movimientos m
        INNER JOIN insumos i
            ON m.id_insumo = i.id_insumo
        ORDER BY m.id_movimiento DESC";

$movimientos = $conexion->query($sql);

?>

<main class="contenido">


    <!-- BOTONES SUPERIORES -->

    <section class="barra-superior">

        <a
            href="../Inventarios/Inventarios.php"
            class="btn btn-azul"
        >
            ← Volver a Inventarios
        </a>


        <a
            href="/YCL_Solutions/DASHBOARD/Dashboard.php"
            class="btn-inicio"
        >
            ⌂ Inicio
        </a>

    </section>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>
            <?= $titulo ?>
        </h1>

        <p>
            Consulte y administre las entradas y salidas registradas en el inventario de YCL_Solutions.
        </p>

    </header>


    <!-- INDICADORES -->

    <section class="cards">


        <div class="card entradas">

            <span class="titulo-card">
                Entradas Hoy
            </span>

            <h2>
                <?= $entradasHoy ?>
            </h2>

            <p>
                Movimientos registrados
            </p>

        </div>


        <div class="card salidas">

            <span class="titulo-card">
                Salidas Hoy
            </span>

            <h2>
                <?= $salidasHoy ?>
            </h2>

            <p>
                Consumos registrados
            </p>

        </div>


        <div class="card mes">

            <span class="titulo-card">
                Este Mes
            </span>

            <h2>
                <?= $movimientosMes ?>
            </h2>

            <p>
                Movimientos realizados
            </p>

        </div>


        <div class="card total">

            <span class="titulo-card">
                Total Registros
            </span>

            <h2>
                <?= $totalMovimientos ?>
            </h2>

            <p>
                Historial acumulado
            </p>

        </div>


    </section>


    <!-- PANEL -->

    <section class="panel">


        <div class="panel-header">


            <div>

                <h2>
                    Historial de Movimientos
                </h2>

                <p>
                    Consulte todas las entradas y salidas registradas en el inventario.
                </p>

            </div>


            <!-- BUSCADOR Y BOTONES -->

            <div class="barra-acciones">


                <input
                    type="text"
                    id="buscarMovimiento"
                    class="buscar"
                    placeholder="Buscar movimiento..."
                >


                <button
                    type="button"
                    class="btn-buscar"
                    onclick="buscarMovimiento()"
                >
                    Buscar
                </button>


                <button
                    type="button"
                    class="btn-buscar"
                    onclick="refrescarMovimientos()"
                >
                    ↻ Refrescar
                </button>


                <a
                    href="NuevoMovimiento.php"
                    class="btn btn-azul"
                >
                    + Nuevo Movimiento
                </a>


            </div>


        </div>


        <!-- TABLA -->

        <table class="tabla">


            <thead>

                <tr>

                    <th>Código</th>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Insumo</th>
                    <th>Cantidad</th>
                    <th>Unidad</th>
                    <th>Responsable</th>
                    <th>Estado</th>
                    <th>Acciones</th>

                </tr>

            </thead>


            <tbody id="tablaMovimientos">


                <?php if ($movimientos && $movimientos->num_rows > 0): ?>


                    <?php while ($movimiento = $movimientos->fetch_assoc()): ?>


                        <tr>


                            <td>
                                <?= htmlspecialchars($movimiento['codigo']) ?>
                            </td>


                            <td>
                                <?= date(
                                    "d/m/Y",
                                    strtotime($movimiento['fecha'])
                                ) ?>
                            </td>


                            <td>


                                <?php if ($movimiento['tipo'] === 'Entrada'): ?>


                                    <span class="badge badge-entrada">
                                        Entrada
                                    </span>


                                <?php else: ?>


                                    <span class="badge badge-salida">
                                        Salida
                                    </span>


                                <?php endif; ?>


                            </td>


                            <td>
                                <?= htmlspecialchars($movimiento['insumo']) ?>
                            </td>


                            <td>
                                <?= rtrim(rtrim(number_format((float)$movimiento['cantidad'], 2, ',', '.'), '0'), ',') ?>
                            </td>


                            <td>
                                <?= htmlspecialchars($movimiento['unidad']) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars($movimiento['responsable']) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars($movimiento['estado']) ?>
                            </td>


                            <td class="acciones">


                                <a
                                    href="VerMovimiento.php?id=<?= $movimiento['id_movimiento'] ?>"
                                    class="btn-ver"
                                >
                                    Ver
                                </a>


                                <a
                                    href="EditarMovimiento.php?id=<?= $movimiento['id_movimiento'] ?>"
                                    class="btn-editar"
                                >
                                    Editar
                                </a>


                                <a
                                    href="EliminarMovimiento.php?id=<?= $movimiento['id_movimiento'] ?>"
                                    class="btn-eliminar"
                                    onclick="return confirm('¿Está seguro de eliminar este movimiento?');"
                                >
                                    Eliminar
                                </a>


                            </td>


                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr>

                        <td
                            colspan="9"
                            style="text-align:center;"
                        >
                            No hay movimientos registrados.
                        </td>

                    </tr>


                <?php endif; ?>


            </tbody>


        </table>


    </section>


</main>


<!-- BUSCADOR -->

<script>


/* BUSCAR MOVIMIENTO */

function buscarMovimiento(){

    const buscador =
        document.getElementById("buscarMovimiento");

    const texto =
        buscador.value.toLowerCase().trim();

    const filas =
        document.querySelectorAll("#tablaMovimientos tr");


    filas.forEach(function(fila){

        const contenido =
            fila.textContent.toLowerCase();

        fila.style.display =
            contenido.includes(texto) ? "" : "none";

    });

}


/* REFRESCAR */

function refrescarMovimientos(){

    const buscador =
        document.getElementById("buscarMovimiento");

    buscador.value = "";


    const filas =
        document.querySelectorAll("#tablaMovimientos tr");


    filas.forEach(function(fila){

        fila.style.display = "";

    });

}


/* BUSCAR EN TIEMPO REAL */

document
    .getElementById("buscarMovimiento")
    .addEventListener("input", function(){

        buscarMovimiento();

    });


/* BUSCAR CON ENTER */

document
    .getElementById("buscarMovimiento")
    .addEventListener("keyup", function(event){

        if(event.key === "Enter"){

            buscarMovimiento();

        }

    });


</script>


<?php

include("../../includes/footer.php");

?>

