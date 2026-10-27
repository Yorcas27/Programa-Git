<?php

$titulo = "Ver Insumo";
$css = "Insumos.css";

require_once "../../includes/conexion.php";

$id = $_GET['id'] ?? '';

if(!is_numeric($id)){

    header("Location: Insumos.php");
    exit;

}

$stmt = $conexion->prepare(
    "SELECT
        i.id_insumo,
        i.codigo,
        i.producto,
        c.nombre AS categoria,
        i.stock,
        i.unidad,
        i.estado
     FROM insumos i
     LEFT JOIN categorias c
        ON i.id_categoria = c.id_categoria
     WHERE i.id_insumo = ?"
);

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();

if($resultado->num_rows === 0){

    header("Location: Insumos.php");
    exit;

}

$insumo = $resultado->fetch_assoc();

$stmt->close();

require_once "../../includes/header.php";

?>

<main class="contenedor">

    <!-- BOTON VOLVER -->

    <div class="volver">

        <a href="Insumos.php">
            ← Volver a Insumos
        </a>

        <a href="/YCL_Solutions/DASHBOARD/Dashboard.php" class="btn-inicio">
            ⌂ Inicio
        </a>

    </div>


    <!-- ENCABEZADO -->

    <header class="encabezado">

        <h1>
            Ver Insumo
        </h1>

        <p>
            Consulta la información registrada del insumo seleccionado.
        </p>

    </header>


    <!-- INFORMACION DEL INSUMO -->

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2>
                    Información del Insumo
                </h2>

                <p>
                    Detalle completo del registro seleccionado.
                </p>

            </div>

        </div>


        <!-- DATOS DEL INSUMO -->

        <div class="formulario">

            <div class="grupo-formulario">

                <div class="campo">

                    <label>
                        Código del Insumo
                    </label>

                    <p class="dato">
                        <?= htmlspecialchars($insumo['codigo']) ?>
                    </p>

                </div>


                <div class="campo">

                    <label>
                        Nombre del Producto
                    </label>

                    <p class="dato">
                        <?= htmlspecialchars($insumo['producto']) ?>
                    </p>

                </div>


                <div class="campo">

                    <label>
                        Categoría
                    </label>

                    <p class="dato">
                        <?= htmlspecialchars($insumo['categoria'] ?? 'Sin categoría') ?>
                    </p>

                </div>


                <div class="campo">

                    <label>
                        Cantidad Disponible
                    </label>

                    <p class="dato">
                        <?= number_format($insumo['stock'], 2, ',', '.') ?>
                        <?= htmlspecialchars($insumo['unidad']) ?>
                    </p>

                </div>


                <div class="campo">

                    <label>
                        Unidad de Medida
                    </label>

                    <p class="dato">
                        <?= htmlspecialchars($insumo['unidad']) ?>
                    </p>

                </div>


                <div class="campo">

                    <label>
                        Estado
                    </label>

                    <p class="dato">
                        <?= htmlspecialchars($insumo['estado']) ?>
                    </p>

                </div>

            </div>

        </div>


        <!-- BOTONES -->

        <div class="botones-formulario">

            <a
                href="EditarInsumo.php?id=<?= $insumo['id_insumo'] ?>"
                class="btn-editar">
                Editar
            </a>

            <a
                href="Insumos.php"
                class="btn-cancelar">
                Volver
            </a>

        </div>

    </section>

</main>


<?php

require_once "../../includes/footer.php";

?>