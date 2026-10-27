
<?php

require_once("../../includes/conexion.php");

$titulo = "Proveedores";
$css = "Proveedores.css";

$buscar = isset($_GET["buscar"]) ? trim($_GET["buscar"]) : "";

/* INDICADORES */

$sql_total = "SELECT COUNT(*) AS total FROM proveedores";
$resultado_total = $conexion->query($sql_total);
$total = $resultado_total->fetch_assoc()["total"];

$sql_activos = "SELECT COUNT(*) AS total FROM proveedores WHERE estado = 'Activo'";
$resultado_activos = $conexion->query($sql_activos);
$activos = $resultado_activos->fetch_assoc()["total"];

$sql_inactivos = "SELECT COUNT(*) AS total FROM proveedores WHERE estado = 'Inactivo'";
$resultado_inactivos = $conexion->query($sql_inactivos);
$inactivos = $resultado_inactivos->fetch_assoc()["total"];

$sql_compras = "SELECT COUNT(*) AS total FROM compras";
$resultado_compras = $conexion->query($sql_compras);
$compras = $resultado_compras->fetch_assoc()["total"];


/* CONSULTA DE PROVEEDORES */

if ($buscar !== "") {

    $sql = "SELECT
                id_proveedor,
                codigo,
                nombre,
                contacto,
                telefono,
                ciudad,
                estado
            FROM proveedores
            WHERE codigo LIKE ?
               OR nombre LIKE ?
               OR contacto LIKE ?
               OR telefono LIKE ?
               OR ciudad LIKE ?
            ORDER BY id_proveedor DESC";

    $stmt = $conexion->prepare($sql);

    $termino = "%" . $buscar . "%";

    $stmt->bind_param(
        "sssss",
        $termino,
        $termino,
        $termino,
        $termino,
        $termino
    );

    $stmt->execute();

    $proveedores = $stmt->get_result();

} else {

    $sql = "SELECT
                id_proveedor,
                codigo,
                nombre,
                contacto,
                telefono,
                ciudad,
                estado
            FROM proveedores
            ORDER BY id_proveedor DESC";

    $proveedores = $conexion->query($sql);
}

include("../../includes/header.php");

?>

<div class="contenedor proveedores-modulo">


    <!-- VOLVER -->

    <div class="volver">

        <a href="../Inventarios/Inventarios.php">
            ← Volver a Inventarios
        </a>

        <a
            href="/YCL_Solutions/DASHBOARD/Dashboard.php"
            class="btn-inicio"
        >
            ⌂ Inicio
        </a>

    </div>


    <!-- ENCABEZADO -->

    <div class="encabezado">

        <h1>
            Gestión de Proveedores
        </h1>

        <p>
            Administra los proveedores registrados para el suministro
            de insumos utilizados en la producción.
        </p>

    </div>


    <!-- INDICADORES -->

    <section class="cards">

        <div class="card proveedores">

            <h2>
                <?php echo $total; ?>
            </h2>

            <span class="titulo-card">
                Total de Proveedores
            </span>

            <p>
                Proveedores registrados.
            </p>

        </div>


        <div class="card activos">

            <h2>
                <?php echo $activos; ?>
            </h2>

            <span class="titulo-card">
                Proveedores Activos
            </span>

            <p>
                Disponibles para compras.
            </p>

        </div>


        <div class="card inactivos">

            <h2>
                <?php echo $inactivos; ?>
            </h2>

            <span class="titulo-card">
                Proveedores Inactivos
            </span>

            <p>
                Suspendidos temporalmente.
            </p>

        </div>


        <div class="card compras">

            <h2>
                <?php echo $compras; ?>
            </h2>

            <span class="titulo-card">
                Compras Registradas.
            </span>

            <p>
                Historial de compras.
            </p>

        </div>

    </section>


    <!-- PANEL -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Listado de Proveedores
                </h2>

                <p>
                    Consulta y administra la información de los proveedores.
                </p>

            </div>


            <!-- ACCIONES -->

            <div class="acciones">

                <input
                    type="text"
                    id="buscarProveedor"
                    class="buscar"
                    placeholder="Buscar proveedor..."
                    value="<?php echo htmlspecialchars($buscar); ?>"
                >

                <button
                    type="button"
                    class="btn-buscar"
                    onclick="buscarProveedor()"
                >
                    Buscar
                </button>

                <button
                    type="button"
                    class="btn-buscar"
                    onclick="refrescarProveedores()"
                >
                    Refrescar
                </button>

                <a
                    href="NuevoProveedor.php"
                    class="btn btn-nuevo"
                >
                    Nuevo Proveedor
                </a>

            </div>

        </div>


        <!-- TABLA -->

        <table>

            <thead>

                <tr>

                    <th>Código</th>
                    <th>Empresa</th>
                    <th>Contacto</th>
                    <th>Teléfono</th>
                    <th>Ciudad</th>
                    <th>Estado</th>
                    <th>Acciones</th>

                </tr>

            </thead>


            <tbody id="tablaProveedores">

                <?php if ($proveedores && $proveedores->num_rows > 0): ?>

                    <?php while ($proveedor = $proveedores->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo htmlspecialchars($proveedor["codigo"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($proveedor["nombre"]); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($proveedor["contacto"] ?? ""); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($proveedor["telefono"] ?? ""); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($proveedor["ciudad"] ?? ""); ?>
                            </td>

                            <td>

                                <?php if ($proveedor["estado"] === "Activo"): ?>

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

                                <div class="acciones-tabla">

                                    <a
                                        href="VerProveedor.php?id=<?php echo $proveedor["id_proveedor"]; ?>"
                                        class="btn btn-ver"
                                    >
                                        Ver
                                    </a>

                                    <a
                                        href="EditarProveedores.php?id=<?php echo $proveedor["id_proveedor"]; ?>"
                                        class="btn btn-editar"
                                    >
                                        Editar
                                    </a>

                                    <a
                                        href="EliminarProveedor.php?id=<?php echo $proveedor["id_proveedor"]; ?>"
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

                        <td colspan="7" class="sin-datos">
                            No hay proveedores registrados.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </section>

</div>


<!-- BUSCADOR EN TIEMPO REAL -->

<script>

function buscarProveedor(){

    const texto = document
        .getElementById("buscarProveedor")
        .value
        .toLowerCase()
        .trim();

    const filas = document.querySelectorAll("#tablaProveedores tr");

    filas.forEach(function(fila){

        const contenido = fila.textContent.toLowerCase();

        fila.style.display =
            contenido.includes(texto) ? "" : "none";

    });

}


function refrescarProveedores(){

    const buscador =
        document.getElementById("buscarProveedor");

    buscador.value = "";

    const filas =
        document.querySelectorAll("#tablaProveedores tr");

    filas.forEach(function(fila){

        fila.style.display = "";

    });

}


const buscadorProveedor =
    document.getElementById("buscarProveedor");


/* BUSQUEDA EN TIEMPO REAL */

buscadorProveedor.addEventListener("input", function(){

    buscarProveedor();

});


/* BUSQUEDA CON ENTER */

buscadorProveedor.addEventListener("keyup", function(event){

    if(event.key === "Enter"){

        buscarProveedor();

    }

});

</script>


<?php

include("../../includes/footer.php");

?>

