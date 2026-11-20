
<?php

require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../registro.html");
    exit;
}

$nombre = trim($_POST["NvoNombreUsuario"] ?? "");
$correo = trim($_POST["NvoCorreo"] ?? "");
$password = $_POST["NvaContraseña"] ?? "";
$confirmar = $_POST["ConfirmarContraseña"] ?? "";

if (
    $nombre === "" ||
    $correo === "" ||
    $password === "" ||
    $confirmar === ""
) {
    die("Completa todos los campos.");
}

if (strlen($nombre) < 3 || strlen($nombre) > 50) {
    die("El usuario debe tener entre 3 y 50 caracteres.");
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    die("El correo electrónico no es válido.");
}

if (strlen($password) < 8) {
    die("La contraseña debe tener al menos 8 caracteres.");
}

if ($password !== $confirmar) {
    die("Las contraseñas no coinciden.");
}

try {

    $consulta = $conexion->prepare(
        "SELECT id_usuario FROM usuarios
         WHERE nombre_usuario = ? OR correo = ?"
    );

    $consulta->execute([$nombre, $correo]);

    if ($consulta->fetch()) {
        die("El usuario o correo ya está registrado.");
    }

    $conexion->beginTransaction();

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $insertar = $conexion->prepare(
        "INSERT INTO usuarios
        (nombre_usuario, correo, contrasena)
        VALUES (?, ?, ?)"
    );

    $insertar->execute([
        $nombre,
        $correo,
        $hash
    ]);

    $id = $conexion->lastInsertId();

    $crearPerfil = $conexion->prepare(
        "INSERT INTO perfiles
        (id_usuario, cancion_favorita)
        VALUES (?, ?)"
    );

    $crearPerfil->execute([$id, ""]);

    $conexion->commit();

    header("Location: ../login.html?registro=correcto");
    exit;

} catch (PDOException $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    die("Error al registrar la cuenta: " . $e->getMessage());

}

?>
