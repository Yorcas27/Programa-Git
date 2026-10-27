<?php

$titulo = "CLIENTES";
$css = "Clientes.css";

include("../../includes/header.php");
require_once("../../includes/conexion.php");

/* BUSCADOR */

$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

/* CONSULTA DE CLIENTES */

$sql = "SELECT
            id_cliente,
            documento,
            nombre,
            telefono,
            correo,
            ciudad,
            estado
        FROM clientes";

if ($buscar !== '') {

    $buscar_sql = $conexion->real_escape_string($buscar);

    $sql .= " WHERE
                documento LIKE '%$buscar_sql%'
                OR nombre LIKE '%$buscar_sql%'
                OR telefono LIKE '%$buscar_sql%'
                OR correo LIKE '%$buscar_sql%'
                OR ciudad LIKE '%$buscar_sql%'
                OR estado LIKE '%$buscar_sql%'";
}

$sql .= " ORDER BY nombre ASC";

$resultado = $conexion->query($sql);


/* DATOS PARA LAS TARJETAS */

$sql_activos = "SELECT COUNT(*) AS total FROM clientes WHERE estado = 'Activo'";
$clientes_activos = $conexion->query($sql_activos)->fetch_assoc()['total'];

$sql_total = "SELECT COUNT(*) AS total FROM clientes";
$total_clientes = $conexion->query($sql_total)->fetch_assoc()['total'];

?>

<main class="contenido">

    <!-- BOTÓN VOLVER -->

    <section class="barra-superior">

        <a
            href="../../Dashboard/Dashboard.php"
            class="btn btn-principal"
        >

            ← Volver al Dashboard

        </a>

    </section>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>

            Clientes

        </h1>

        <p>

            Gestión y administración de los clientes registrados en YCL_Solutions.

        </p>

    </header>


    <!-- TARJETAS -->

    <section class="cards">

        <div class="card activos">

            <span class="titulo-card">

                Clientes Activos

            </span>

            <h2>

                <?= $clientes_activos ?>

            </h2>

            <p>

                Clientes habilitados.

            </p>

        </div>


        <div class="card nuevos">

            <span class="titulo-card">

                Nuevos este Mes

            </span>

            <h2>0</h2>

            <p>

                Registros recientes.

            </p>

        </div>


        <div class="card pedidos">

            <span class="titulo-card">

                Con Pedidos

            </span>

            <h2>0</h2>

            <p>

                Clientes con pedidos activos.

            </p>

        </div>


        <div class="card total">

            <span class="titulo-card">

                Total Clientes

            </span>

            <h2>

                <?= $total_clientes ?>

            </h2>

            <p>

                Clientes registrados.

            </p>

        </div>

    </section>


    <!-- PANEL -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>

                    Clientes Registrados

                </h2>

                <p>

                    Consulte la información de todos los clientes.

                </p>

            </div>


            <div class="barra-acciones">

                <form method="GET" action="Clientes.php" class="form-busqueda">

                    <input
                        type="text"
                        name="buscar"
                        class="buscar"
                        id="buscarCliente"
                        placeholder="Buscar cliente..."
                        value="<?= htmlspecialchars($buscar) ?>"
                    >

                    <button
                        type="submit"
                        class="btn btn-buscar"
                    >

                        Buscar

                    </button>

                    <a
                        href="Clientes.php"
                        class="btn btn-refrescar"
                    >

                        ↻ Refrescar

                    </a>

                </form>


                <a
                    href="NuevoCliente.php"
                    class="btn btn-principal"
                >

                    + Nuevo Cliente

                </a>

            </div>

        </div>


        <!-- TABLA -->

        <table class="tabla">

            <thead>

                <tr>

                    <th>Documento</th>
                    <th>Cliente</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Ciudad</th>
                    <th>Estado</th>
                    <th>Acciones</th>

                </tr>

            </thead>


            <tbody id="tablaClientes">

                <?php if ($resultado && $resultado->num_rows > 0): ?>

                    <?php while ($cliente = $resultado->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($cliente['documento'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($cliente['nombre'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($cliente['telefono'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($cliente['correo'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($cliente['ciudad'] ?? '') ?>
                            </td>

                            <td>

                                <?php if ($cliente['estado'] === 'Activo'): ?>

                                    <span class="badge badge-activo">
                                        Activo
                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-inactivo">
                                        Inactivo
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <div class="acciones">

                                    <a
                                        href="VerCliente.php?id=<?= $cliente['id_cliente'] ?>"
                                        class="btn btn-ver"
                                    >

                                        Ver

                                    </a>

                                    <a
                                        href="EditarCliente.php?id=<?= $cliente['id_cliente'] ?>"
                                        class="btn btn-editar"
                                    >

                                        Editar

                                    </a>

                                    <a
                                        href="EliminarCliente.php?id=<?= $cliente['id_cliente'] ?>"
                                        class="btn btn-eliminar"
                                        onclick="return confirm('¿Está seguro de que desea eliminar al cliente <?= htmlspecialchars($cliente['nombre'], ENT_QUOTES) ?>?');"
                                    >

                                        Eliminar

                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="7" style="text-align:center;">

                            No se encontraron clientes.

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </section>

</main>


<!-- BUSCADOR EN TIEMPO REAL -->

<script>

const buscarCliente =
    document.getElementById("buscarCliente");

const filasClientes =
    document.querySelectorAll("#tablaClientes tr");


buscarCliente.addEventListener("input", function(){

    const texto =
        this.value.toLowerCase().trim();

    let encontrados = 0;

    filasClientes.forEach(function(fila){

        const contenido =
            fila.textContent.toLowerCase();

        if(contenido.includes(texto)){

            fila.style.display = "";

            encontrados++;

        }else{

            fila.style.display = "none";

        }

    });

});

</script>


<?php

include("../../includes/footer.php");

?>