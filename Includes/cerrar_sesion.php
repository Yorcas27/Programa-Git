<?php

/* CERRAR SESIÓN */

session_start();

$_SESSION = [];

session_destroy();

/* VOLVER AL LOGIN */

header("Location: /YCL_Solutions/LOGIN/Login.php");
exit;