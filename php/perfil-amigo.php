<?php
session_start();
//hace la carga de la conexion
require_once __DIR__ . "/includes/conexion.php";
if(!isset($_SESSION["id_usuario"])){
    header("location: login.html");
    exit;
}
//comprueba la sesion del usuario
function e($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

//toma el id del usuario
$idvisitante = (int)$_SESSION["id_usuario"];
//lee a usuario desde la url
$usuarioParam = trim($_GET["usuario"] ?? "");
//convierte el id del usuario a un entero, si nmo le asigna 0
$idParam = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
//si no se mando ningun usuario ni id, detiene la script
if ($usuarioParam === "" && $idParam === 0) {
    die("No se especifico que amigo ver.");
}
//entra cuando el formulario se envia por POST
if($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["id_publicacion"], $_POST["comentario"])){
    //convierte id_publicacion en un entro y limpia espacios
    $idPub = (int)$_POST["id_publicacion"];
    $comentario = trim($_POST["comentario"]);
    //verifica que haya una publicacion valida y texto no vacio
    //$pdo prepare protege contra inyeccion de sql
    if ($idPub > 0 && $comentario !== "") {
        $stmtInsertCom = $pdo->prepare(
            "INSERT INTO comentarios (id_publicacion, id_usuario, contenido)
             VALUES (:id_pub, :id_usr, :contenido)"
        );
        $stmtInsertCom->execute([
            ":id_pub"    => $idPub,
            ":id_usr"    => $idvisitante,
            ":contenido" => $comentario
        ]);
    }
    //arma la url de regreso y codifica caracteres especiales
    //header redirige y termina con exit
    $redir = ($idParam > 0)
        ? "perfil-amigo.php?id=$idParam"
        : "perfil-amigo.php?usuario=" . urlencode($usuarioParam);
    header("Location: " . $redir);
    exit;
}

//consultas a la base de datos
try {
    //realiza busquedas, por id o nombre de usuario
    //left join une los datos del usuario
    //tiene el limite de dar como maximo 1 usuario
    if($idParam > 0){
        $sqlAmigo = "SELECT u.id_usuario, u.nombre_usuario, p.biografia, p.foto_perfil, p.artista_favorito, p.cancion_favorita
                     FROM usuarios u
                     LEFT JOIN perfiles p ON u.id_usuario = p.id_usuario
                     WHERE u.id_usuario = :id
                     LIMIT 1";
        $stmtAmigo = $pdo->prepare($sqlAmigo);
        $stmtAmigo->execute([":id" => $idParam]);
    } else {
        $sqlAmigo = "SELECT u.id_usuario, u.nombre_usuario, p.biografia, p.foto_perfil, p.artista_favorito, p.cancion_favorita
                     FROM usuarios u
                     LEFT JOIN perfiles p ON u.id_usuario = p.id_usuario
                     WHERE u.nombre_usuario = :usuario
                     LIMIT 1";
        $stmtAmigo = $pdo->prepare($sqlAmigo);
        $stmtAmigo->execute([":usuario" => $usuarioParam]);
    }

    //obtiene la fila como arreglo
    //si no existre el usuario detiene la ejecucucion
    $amigo = $stmtAmigo->fetch(PDO::FETCH_ASSOC);

    if (!$amigo) {
        die("Usuario no encontrado.");
    }

    $idAmigo = (int)$amigo["id_usuario"];
    //busca si existe un regtistro entre visitante y amigo
    $sqlAmistad = "SELECT estado
                   FROM amistades
                   WHERE (id_usuario_solicitud = :u1 AND id_usuario_aceptado = :u2)
                      OR (id_usuario_solicitud = :u2 AND id_usuario_aceptado = :u1)
                   LIMIT 1";
    $stmtAmistad = $pdo->prepare($sqlAmistad);
    $stmtAmistad->execute([
        ":u1" => $idvisitante,
        ":u2" => $idAmigo
    ]);
    $relacion = $stmtAmistad->fetch(PDO::FETCH_ASSOC);
    $estadoAmistad = $relacion ? $relacion["estado"] : "Sin_Relacion";
    //consulta las publicaciones por el amigo
    //fetchall devuelve un arreglo con todas las publicaciones
    $stmtPubs = $pdo->prepare(
        "SELECT id_publicacion, titulo, contenido, fecha_creacion
         FROM publicaciones
         WHERE id_usuario = :id_amigo
         ORDER BY fecha_creacion DESC"
    );
    $stmtPubs->execute([":id_amigo" => $idAmigo]);
    $publicaciones = $stmtPubs->fetchAll(PDO::FETCH_ASSOC);
    //inicializa comentariosporpub como arreglo vacio
    //extrae las publicaciones y genera signos como publicaciones
    //obtiene el nombre de usuario de quien escrbio cada comentario
    $comentariosPorPub = [];
    if (count($publicaciones) > 0) {
        $idsPubs = array_map(fn($p) => (int)$p["id_publicacion"], $publicaciones);
        $placeholders = implode(",", array_fill(0, count($idsPubs), "?"));

        $sqlComs = "SELECT c.id_publicacion,
                           c.contenido,
                           c.fecha_creacion,
                           u.nombre_usuario
                    FROM comentarios c
                    INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
                    WHERE c.id_publicacion IN ($placeholders)
                    ORDER BY c.fecha_creacion ASC";
        $stmtComs = $pdo->prepare($sqlComs);
        $stmtComs->execute($idsPubs);

        while ($com = $stmtComs->fetch(PDO::FETCH_ASSOC)) {
            $comentariosPorPub[(int)$com["id_publicacion"]][] = $com;
        }
    }
    //si ocurre un error en la base de datos o SQl atrapa la pdo y muestra el meensaje
} catch (PDOException $ex) {
    die("Error al cargar el perfil del amigo: " . $ex->getMessage());
}
//quita comillas o esapcios vacios de foto_perfil
//si esta vacio o es el valor por defecto, usa imagen de remplazo
$fotoLimpia = trim((string)($amigo["foto_perfil"] ?? ""), "'\" ");
if ($fotoLimpia === "" || $fotoLimpia === "default_150x150.png") {
    $fotoSrc = "https://placehold.co/150x150";
} else {
    $fotoSrc = $fotoLimpia;
}
//define la url exacra del formulario
$actionUrl = ($idParam > 0)
    ? "perfil-amigo.php?id=" . $idParam
    : "perfil-amigo.php?usuario=" . urlencode($amigo["nombre_usuario"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/perfilamigo.css">
    <title>RetroSpace - Perfil de <?php echo e($amigo["nombre_usuario"]); ?></title>
</head>
<body>

    <header class="encabezado">
        <h1>RetroSpace</h1>
        <p>Tu espacio. Solo tu.</p>
    </header>

    <nav class="navegacion">
        <a href="index.html">Inicio</a>
        <a href="PHP/perfil.php">Mi perfil</a>
        <a href="amigos.html">Amigos</a>
        <a href="PHP/cerrar-sesion.php">Cerrar sesion</a>
    </nav>

    <main>

        <section id="Barralateral">
            <div id="ImagenPerfil">
                <img id="FotoPerfil" src="<?php echo e($fotoSrc); ?>" alt="Foto de perfil de <?php echo e($amigo["nombre_usuario"]); ?>">
            </div>

            <div id="InfoPersonal">
                <p id="NombreUsuario"><?php echo e($amigo["nombre_usuario"]); ?></p>
                <p id="EstadoAmistad">Estado: <?php echo e($estadoAmistad); ?></p>
                <p id="ArtistaFavorito"><?php echo e($amigo["artista_favorito"] ?: "Artista Favorito"); ?></p>
                <p id="CancionFavorita"><?php echo e($amigo["cancion_favorita"] ?: "Cancion Favorita"); ?></p>
                <?php if (!empty($amigo["biografia"])): ?>
                    <p id="Biografia"><?php echo nl2br(e($amigo["biografia"])); ?></p>
                <?php endif; ?>
            </div>
        </section>

        <section id="MuroAmigo">
            <?php if (count($publicaciones) === 0): ?>
                <p>Este usuario aun no tiene publicaciones.</p>
            <?php else: ?>
                <?php foreach ($publicaciones as $pub): ?>
                    <?php $idPubActual = (int)$pub["id_publicacion"]; ?>
                    <article class="publicacion">
                        <h3><?php echo e($pub["titulo"]); ?></h3>
                        <p><?php echo nl2br(e($pub["contenido"])); ?></p>

                        <div class="listaComentarios">
                            <p>Comentarios</p>
                            <?php if (!empty($comentariosPorPub[$idPubActual])): ?>
                                <?php foreach ($comentariosPorPub[$idPubActual] as $com): ?>
                                    <p>
                                        <strong><?php echo e($com["nombre_usuario"]); ?>:</strong>
                                        <?php echo e($com["contenido"]); ?>
                                    </p>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Sin comentarios todavia.</p>
                            <?php endif; ?>
                        </div>

                        <form action="<?php echo e($actionUrl); ?>" method="POST">
                            <input type="hidden" name="id_publicacion" value="<?php echo $idPubActual; ?>">
                            <input type="text" name="comentario" placeholder="Escribe un comentario..." required>
                            <button type="submit">Enviar</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

    </main>

    <footer>
        <p>RetroSpace - Mi espacio personal</p>
    </footer>

</body>
</html>