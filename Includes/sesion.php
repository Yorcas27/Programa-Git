<?php

/* INICIAR SESIÓN */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* TIEMPO DE INACTIVIDAD */

$tiempoInactividad = 1800; // 30 minutos

/* VERIFICAR SESIÓN */

if (!isset($_SESSION["id_usuario"])) {

    header("Location: /YCL_Solutions/LOGIN/Login.php");
    exit;
}

/* VERIFICAR INACTIVIDAD */

if (isset($_SESSION["ultima_actividad"])) {

    $inactividad = time() - $_SESSION["ultima_actividad"];

    if ($inactividad >= $tiempoInactividad) {

        $_SESSION = [];

        session_destroy();

        header("Location: /YCL_Solutions/LOGIN/Login.php?expirada=1");
        exit;
    }
}

/* ACTUALIZAR ACTIVIDAD */

$_SESSION["ultima_actividad"] = time();