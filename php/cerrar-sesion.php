
<?php

session_start();

// Vaciar todas las variables de sesion
$_SESSION = array();

// Eliminar la cookie de sesion
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

// Destruir la sesion
session_destroy();

// Regresar al inicio
header("Location: ../index.html");
exit;

?>
