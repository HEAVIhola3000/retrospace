
<?php

session_start();

require_once "conexion.php";

// Verificar sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.html");
    exit;
}

$id_usuario = $_SESSION["id_usuario"];

try {

    // Consultar datos del perfil
    $consulta = $conexion->prepare(
        "SELECT u.id_usuario,
                u.nombre_usuario,
                p.biografia,
                p.foto_perfil,
                p.artista_favorito,
                p.cancion_favorita
         FROM usuarios u
         LEFT JOIN perfiles p
         ON u.id_usuario = p.id_usuario
         WHERE u.id_usuario = ?"
    );

    $consulta->execute([$id_usuario]);

    $perfil = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$perfil) {
        die("No se encontró el perfil.");
    }

    // Consultar publicaciones
    $consultaPublicaciones = $conexion->prepare(
        "SELECT id_publicacion,
                titulo,
                contenido,
                fecha_creacion
         FROM publicaciones
         WHERE id_usuario = ?
         ORDER BY fecha_creacion DESC"
    );

    $consultaPublicaciones->execute([$id_usuario]);

    $publicaciones = $consultaPublicaciones->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    die("Error al consultar el perfil.");

}

// Función para proteger los datos
function e($dato) {
    return htmlspecialchars(
        (string)$dato,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>
