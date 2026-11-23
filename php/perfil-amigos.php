
<?php

session_start();

require_once "conexion.php";
// Verificar que el usuario inició sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.html");
    exit;
}

// Obtener el ID del amigo desde la URL
$id_amigo = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);// Verificar que el usuario inició sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.html");
    exit;
}

// Obtener el ID del amigo desde la URL
$id_amigo = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
 
if(!$id_amigo || $id_amigo <=0) {
    die("No se encontro el usuario solicitado");
}

//Evitar consultar el perfil propio como perfil de amigo
if ($id_amigo == $_SESSION["id_usuario"]) {
    header("Location: perfil.php");
    exit;
}

try{
    //Consultar informacion del amigo
    $consulta = $conexion->prepare(
        "SELECT u.id_usuaio,
        u.nombre_usuario,
        p.biografia,
        p.foto_perfil,
        p.artista_favorito,
        p.cancion_favorita
        FROM usuarios u
        LEFT JOIN perfiles p
        ON u.id_usuario = p.id_ usuario
        WHERE u.id_usuario=?"
    );
    $consulta->execute([$id_amigo]);

    $perfil = $consulta->fetch();

    if (!$perfil) {
        die("No se encontró este usuario.");
    }
 
    // Consultar publicaciones del amigo
    $consultaPublicaciones = $conexion->prepare(
        "SELECT id_publicacion, titulo, contenido, fecha_creacion
         FROM publicaciones
         WHERE id_usuario = ?
         ORDER BY fecha_creacion DESC"
    );
    $consultaPublicaciones->execute([$id_amigo]);
    $publicaciones = $consultaPublicaciones->fetchALL();

} catch (PDOException $e) {
    die("Error al consultar el perfil.");
}

// Proteger los textos que se muestran en HTML
function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

?>
