
<?php

session_start();

// Vaciar todas las variables de sesión
$_SESSION = array();

// Eliminar la cookie de sesión
if (ini_get("session.use_cookies")) {

    $parametros = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $parametros["path"],
        $parametros["domain"],
        $parametros["secure"],
        $parametros["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Regresar al inicio
header("Location: ../index.html");
exit;

?>
