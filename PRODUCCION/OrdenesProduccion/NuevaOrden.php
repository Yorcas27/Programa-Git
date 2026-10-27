<?php
$titulo = "NUEVA ORDEN";
$css = "OrdenesProduccion.css";

require_once("../../includes/conexion.php");

header(
    "Location: ../../PEDIDOS/Pedidos.php?mensaje="
    . urlencode("Registra primero el pedido del cliente. La orden de producción se creará automáticamente.")
);
exit;
?>