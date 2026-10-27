<?php
$titulo = "CATÁLOGO DE PRODUCTOS";
$css = "Catalogos.css";

require_once("../includes/config.php");
require_once("../includes/conexion.php");

$catalogo = [];
$errorCatalogo = false;

try {
    $sql = "SELECT
                p.id_producto,
                p.nombre AS nombre_producto,
                p.presentacion,
                p.precio,
                p.id_producto_principal,
                COALESCE(NULLIF(pp.nombre, ''), p.nombre) AS nombre_catalogo,
                COALESCE(NULLIF(pp.descripcion, ''), p.descripcion) AS descripcion_catalogo,
                COALESCE(NULLIF(pp.imagen, ''), NULLIF(p.imagen, '')) AS imagen_catalogo,
                COALESCE(cpp.nombre, cp.nombre) AS categoria
            FROM productos p
            LEFT JOIN productos_principales pp
                ON pp.id_producto_principal = p.id_producto_principal
            LEFT JOIN categorias_producto cp
                ON cp.id_categoria_producto = p.id_categoria_producto
            LEFT JOIN categorias_producto cpp
                ON cpp.id_categoria_producto = pp.id_categoria_producto
            WHERE LOWER(p.estado) = 'activo'
              AND (
                    (p.id_producto_principal IS NULL AND cp.estado = 'Activa')
                    OR
                    (p.id_producto_principal IS NOT NULL
                     AND LOWER(pp.estado) = 'activo'
                     AND cpp.estado = 'Activa')
              )
            ORDER BY nombre_catalogo,
                FIELD(p.presentacion, 'Pequeña', 'Pequena', 'Mediana', 'Grande'),
                p.id_producto";

    $resultado = $conexion->query($sql);

    while ($fila = $resultado->fetch_assoc()) {
        $idPrincipal = (int)($fila["id_producto_principal"] ?? 0);
        $clave = $idPrincipal > 0
            ? "principal_" . $idPrincipal
            : "producto_" . (int)$fila["id_producto"];

        if (!isset($catalogo[$clave])) {
            $catalogo[$clave] = [
                "nombre" => $fila["nombre_catalogo"],
                "descripcion" => $fila["descripcion_catalogo"] ?? "",
                "categoria" => $fila["categoria"] ?? "Sin categoría",
                "imagen" => $fila["imagen_catalogo"] ?? "",
                "presentaciones" => []
            ];
        }

        $catalogo[$clave]["presentaciones"][] = [
            "id" => (int)$fila["id_producto"],
            "nombre" => $fila["presentacion"] ?: "Única",
            "precio" => (float)$fila["precio"]
        ];
    }
} catch (mysqli_sql_exception $e) {
    $errorCatalogo = true;
}

include("../includes/header.php");
?>

<main class="contenido">
    <section class="barra-superior">
        <a href="../DASHBOARD/Dashboard.php" class="btn btn-principal">
            ← Volver al Dashboard
        </a>
    </section>

    <section class="encabezado">
        <h1><?= htmlspecialchars($titulo) ?></h1>
        <p>Consulta los productos disponibles para venta directa.</p>
    </section>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Productos disponibles</h2>
                <p>Productos y precios registrados en la matriz de Productos.</p>
            </div>

            <div class="barra-acciones">
                <input type="text" id="buscarProducto" class="buscar"
                       placeholder="Buscar producto..." autocomplete="off">

                <button type="button" class="btn-buscar" onclick="buscarProductos()">
                    Buscar
                </button>

                <button type="button" class="btn-refrescar" onclick="refrescarProductos()">
                    Refrescar
                </button>
            </div>
        </div>

        <?php if ($errorCatalogo): ?>
            <div class="sin-resultados">
                No fue posible cargar los productos. Verifica la conexión con la base de datos.
            </div>

        <?php elseif (empty($catalogo)): ?>
            <div class="sin-resultados">
                No hay productos activos disponibles en el catálogo.
            </div>

        <?php else: ?>
            <div id="resumenVenta" style="margin:15px 0;padding:15px;border-radius:8px;background:#f4f6f9;">
                <strong>Productos agregados:</strong>
                <span id="cantidadCarrito">0</span>

                <button type="button" class="btn-seleccionar" onclick="continuarFacturacion()">
                    Continuar a facturar
                </button>

                <button type="button" class="btn-vaciar" onclick="vaciarCarrito()">
                    Vaciar selección
                </button>

                <div id="detalleCarrito" style="margin-top:10px;"></div>
            </div>

            <form id="formVenta" action="../FACTURACION/GenerarFacturaDirecta.php" method="POST">
                <input type="hidden" name="accion" value="continuar">
                <div id="productosCarrito"></div>
            </form>

            <div class="catalogo-grid" id="catalogoProductos">
                <?php foreach ($catalogo as $producto): ?>
                    <?php
                    $primeraPresentacion = $producto["presentaciones"][0];
                    $imagen = trim((string)$producto["imagen"]);
                    $rutaImagen = $imagen !== ""
                        ? "/YCL_Solutions/" . ltrim($imagen, "/")
                        : "";
                    ?>

                    <article class="producto-card">
                        <div class="producto-imagen">
                            <?php if ($rutaImagen !== ""): ?>
                                <img src="<?= htmlspecialchars($rutaImagen) ?>"
                                     alt="<?= htmlspecialchars($producto["nombre"]) ?>"
                                     loading="lazy"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">

                                <span class="sin-foto" style="display:none;">SIN FOTO</span>
                            <?php else: ?>
                                <span class="sin-foto">SIN FOTO</span>
                            <?php endif; ?>
                        </div>

                        <div class="producto-info">
                            <span class="producto-codigo">
                                <?= htmlspecialchars($producto["categoria"]) ?>
                            </span>

                            <h3><?= htmlspecialchars($producto["nombre"]) ?></h3>

                            <?php if (!empty($producto["descripcion"])): ?>
                                <p class="producto-descripcion">
                                    <?= htmlspecialchars($producto["descripcion"]) ?>
                                </p>
                            <?php endif; ?>

                            <label class="etiqueta-presentacion">Presentación</label>

                            <select class="selector-presentacion" onchange="actualizarPresentacion(this)">
                                <?php foreach ($producto["presentaciones"] as $presentacion): ?>
                                    <option value="<?= $presentacion["id"] ?>"
                                            data-precio="<?= $presentacion["precio"] ?>"
                                            data-nombre="<?= htmlspecialchars($presentacion["nombre"], ENT_QUOTES) ?>"
                                            <?= $presentacion["id"] === $primeraPresentacion["id"] ? "selected" : "" ?>>
                                        <?= htmlspecialchars($presentacion["nombre"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <strong class="precio-producto">
                                $<?= number_format($primeraPresentacion["precio"], 0, ",", ".") ?>
                            </strong>

                            <label style="display:block;margin-top:10px;">Cantidad</label>

                            <input type="number" class="cantidad-producto" value="1"
                                   min="1" max="999" step="1"
                                   style="width:90px;padding:8px;margin:5px 0;">

                            <button type="button" class="btn-seleccionar"
                                    onclick="agregarProducto(this)">
                                Agregar a venta
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div id="sinResultados" class="sin-resultados" style="display:none;">
                No se encontraron productos para la búsqueda.
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
const campoBusqueda = document.getElementById("buscarProducto");
const contenedorProductos = document.getElementById("catalogoProductos");
const sinResultados = document.getElementById("sinResultados");
const carrito = {};
let tiempoBusqueda;

function actualizarPresentacion(selector) {
    const tarjeta = selector.closest(".producto-card");
    const opcion = selector.options[selector.selectedIndex];
    const precio = Number(opcion.dataset.precio || 0);

    tarjeta.querySelector(".precio-producto").textContent =
        "$" + precio.toLocaleString("es-CO", {maximumFractionDigits: 0});
}

function agregarProducto(boton) {
    const tarjeta = boton.closest(".producto-card");
    const selector = tarjeta.querySelector(".selector-presentacion");
    const opcion = selector.options[selector.selectedIndex];
    const id = String(opcion.value);
    const cantidad = Number(tarjeta.querySelector(".cantidad-producto").value);

    if (!Number.isInteger(cantidad) || cantidad < 1 || cantidad > 999) {
        alert("Ingresa una cantidad válida.");
        return;
    }

    if (carrito[id]) {
        carrito[id].cantidad += cantidad;
    } else {
        carrito[id] = {
            id: Number(id),
            nombre: tarjeta.querySelector("h3").textContent.trim(),
            presentacion: opcion.dataset.nombre || "Única",
            precio: Number(opcion.dataset.precio || 0),
            cantidad: cantidad
        };
    }

    actualizarCarrito();
}

function actualizarCarrito() {
    const detalle = document.getElementById("detalleCarrito");
    const cantidadTotal = document.getElementById("cantidadCarrito");
    const contenedor = document.getElementById("productosCarrito");
    let unidades = 0;
    let html = "";

    contenedor.innerHTML = "";

    Object.values(carrito).forEach(function(item) {
        unidades += item.cantidad;

        html += `<div style="margin:5px 0;">
            ${escapeHtml(item.nombre)} (${escapeHtml(item.presentacion)})
            × ${item.cantidad}
            <button type="button" class="btn-quitar"
                    onclick="quitarProducto(${item.id})">Quitar</button>
        </div>`;

        const idInput = document.createElement("input");
        idInput.type = "hidden";
        idInput.name = "productos[]";
        idInput.value = item.id;

        const cantidadInput = document.createElement("input");
        cantidadInput.type = "hidden";
        cantidadInput.name = "cantidades[]";
        cantidadInput.value = item.cantidad;

        contenedor.appendChild(idInput);
        contenedor.appendChild(cantidadInput);
    });

    cantidadTotal.textContent = unidades;
    detalle.innerHTML = html || "Todavía no has agregado productos.";
}

function quitarProducto(id) {
    delete carrito[id];
    actualizarCarrito();
}

function vaciarCarrito() {
    Object.keys(carrito).forEach(id => delete carrito[id]);
    actualizarCarrito();
}

function continuarFacturacion() {
    if (Object.keys(carrito).length === 0) {
        alert("Agrega al menos un producto antes de continuar.");
        return;
    }

    document.getElementById("formVenta").submit();
}

function escapeHtml(texto) {
    return String(texto).replace(/[&<>"']/g, function(caracter) {
        return {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        }[caracter];
    });
}

function buscarProductos() {
    if (!contenedorProductos) return;

    const texto = campoBusqueda.value.trim().toLocaleLowerCase("es");
    const tarjetas = contenedorProductos.querySelectorAll(".producto-card");
    let encontrados = 0;

    tarjetas.forEach(function(tarjeta) {
        const coincide = tarjeta.textContent.toLocaleLowerCase("es").includes(texto);
        tarjeta.style.display = coincide ? "" : "none";

        if (coincide) encontrados++;
    });

    sinResultados.style.display = encontrados === 0 ? "block" : "none";
}

function refrescarProductos() {
    campoBusqueda.value = "";
    buscarProductos();
    campoBusqueda.focus();
}

if (campoBusqueda) {
    campoBusqueda.addEventListener("input", function() {
        clearTimeout(tiempoBusqueda);
        tiempoBusqueda = setTimeout(buscarProductos, 300);
    });

    campoBusqueda.addEventListener("keydown", function(event) {
        if (event.key === "Enter") {
            event.preventDefault();
            buscarProductos();
        }
    });
}

actualizarCarrito();
</script>

<?php include("../includes/footer.php"); ?>