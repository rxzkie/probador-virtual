<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Inicializar la sesión (siempre necesario antes de trabajar con sesiones)
if (!isset($_SESSION)) {
  session_start();
}

// 2. Vaciar todas las variables de sesión
$_SESSION = array();

// 3. Destruir la cookie de sesión si existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// 4. Destruir la sesión
session_destroy();

// 5. Opcional: Redireccionar a otra página (por ejemplo, login)
header('Location: index.php');
exit();

?>