<?php
session_start();
require_once "conexion.php";
if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.html");
    exit;
}
$id = $_SESSION["id_usuario"];

$consulta = $conexion->prepare(
    "SELECT u.nombre_usuario,
            p.biografia,
            p.foto_perfil,
            p.artista_favorito,
            p.cancion_favorita
     FROM usuarios u
     LEFT JOIN perfiles p ON u.id_usuario = p.id_usuario
     WHERE u.id_usuario = ?"
);

$consulta->execute([$id]);

$perfil = $consulta->fetch();

if (!$perfil) {
    die("No se encontró el usuario.");
}

$publicaciones = $conexion->prepare(
    "SELECT *
     FROM publicaciones
     WHERE id_usuario = ?
     ORDER BY fecha_creacion DESC"
);

$publicaciones->execute([$id]);

function e($valor) {
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/perfil.css">
    <title>RetroSpace - Mi perfil</title>
</head>

<body>

<header class="encabezado">
    <h1>RetroSpace</h1>
    <p>Tu espacio. Solo tú.</p>
</header>

<nav class="navegacion">
    <a href="../index.html">Inicio</a>
    <a href="perfil.php">Mi perfil</a>
    <a href="amigos.php">Amigos</a>
    <a href="cerrar-sesion.php">Cerrar sesión</a>
</nav>

<main>

<section id="Barralateral">

    <div id="ImagenPerfil">

        <img
            src="../<?php echo e($perfil["foto_perfil"]); ?>"
            alt="Foto de perfil"
        >

    </div>

    <div id="InfoPersonal">

        <p><?php echo e($perfil["nombre_usuario"]); ?></p>

        <button type="button" id="BotonUsuario">
            Editar mi Usuario
        </button>

        <p>Artista Favorito</p>

        <p><?php echo e($perfil["artista_favorito"] ?? ""); ?></p>

        <button type="button" id="BotonArtista">
            Editar mi Artista
        </button>

        <p>Canción Favorita</p>

        <p><?php echo e($perfil["cancion_favorita"]); ?></p>

        <button type="button" id="BotonCancion">
            Editar mi Canción
        </button>

    </div>

</section>

<section id="Amigos">

    <div id="ContenedorAmigos">

        <h2>MIS AMIGOS</h2>

        <p>Tu lista de amigos en RetroSpace</p>

        <button
            type="button"
            onclick="location.href='amigos.php'"
        >
            Ver todos los amigos
        </button>

    </div>

</section>

<section id="Muro">

    <h2>Mi Muro</h2>

    <form
        id="FormularioPublicacion"
        action="publicar.php"
        method="POST"
    >

        <input
            type="text"
            name="titulo"
            placeholder="Escribe el título..."
            maxlength="100"
            required
        >

        <textarea
            name="publicacion"
            id="TextoPublicacion"
            placeholder="¿Qué estás pensando?"
            required
        ></textarea>

        <button type="submit">Publicar</button>

    </form>

    <?php while ($pub = $publicaciones->fetch()): ?>

        <article class="publicacion">

            <h3><?php echo e($pub["titulo"]); ?></h3>

            <p>
                <?php echo nl2br(e($pub["contenido"])); ?>
            </p>

            <p>Comentarios</p>

            <a href="comentar.php?id=<?php echo (int)$pub["id_publicacion"]; ?>">
                Ver comentarios
            </a>

            <form action="comentar.php" method="POST">

                <input
                    type="hidden"
                    name="id_publicacion"
                    value="<?php echo (int)$pub["id_publicacion"]; ?>"
                >

                <input
                    type="text"
                    name="comentario"
                    placeholder="Escribe un comentario..."
                    required
                >

                <button type="submit">Enviar</button>

            </form>

        </article>

    <?php endwhile; ?>

</section>

</main>

<footer>
    <p>RetroSpace - Mi espacio personal</p>
</footer>

</body>
</html>
