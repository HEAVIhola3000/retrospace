
<?php

session_start();

require_once __DIR__ . "/conexiones.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $usuario = trim($_POST["Usuario"] ?? "");
    $contrasena = $_POST["Contraseña"] ?? "";

    if (empty($usuario) || empty($contrasena)) {
        die("Completa todos los campos.");
    }

    try {

        $sql = "SELECT * FROM usuarios
                WHERE nombre_usuario = :usuario
                OR correo = :usuario
                LIMIT 1";

        $consulta = $conexion->prepare($sql);
        $consulta->execute([
            ":usuario" => $usuario
        ]);

        $resultado = $consulta->fetch();

        if ($resultado && password_verify($contrasena, $resultado["contrasena"])) {

            session_regenerate_id(true);

            $_SESSION["id_usuario"] = $resultado["id_usuario"];
            $_SESSION["nombre_usuario"] = $resultado["nombre_usuario"];

            header("Location: perfil.php");
            exit();

        } else {
            echo "Usuario o contraseña incorrectos.";
        }

    } catch (PDOException $e) {
        echo "Error al iniciar sesión: " . $e->getMessage();
    }

} else {
    header("Location: ../login.html");
    exit();
}

?>
