<?php
session_start();
require_once __DIR__ . "/conexiones.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.html");
    exit;
}

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

function resolverFoto($foto, $default = "https://placehold.co/150x150") {
    $limpia = trim((string)$foto, "'\" ");
    if ($limpia === "" || $limpia === "default_150x150.png") {
        return $default;
    }
    return "../" . ltrim($limpia, "/");
}

$idVisitante = (int)$_SESSION["id_usuario"];
$usuarioParam = trim($_GET["usuario"] ?? "");
$idParam = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($usuarioParam === "" && $idParam === 0) {
    die("No se especificó qué perfil visitar.");
}

$baseQuery = ($idParam > 0)
    ? "perfil-amigo.php?id=" . $idParam
    : "perfil-amigo.php?usuario=" . urlencode($usuarioParam);

try {
    //Obtener datos del usuario visitado
    if ($idParam > 0) {
        $sqlAmigo = "SELECT u.id_usuario, u.nombre_usuario,
                            p.biografia, p.frase_estado, p.foto_perfil,
                            p.artista_favorito, p.cancion_favorita
                     FROM usuarios u
                     LEFT JOIN perfiles p ON u.id_usuario = p.id_usuario
                     WHERE u.id_usuario = :id
                     LIMIT 1";
        $stmtAmigo = $conexion->prepare($sqlAmigo);
        $stmtAmigo->execute([":id" => $idParam]);
    } else {
        $sqlAmigo = "SELECT u.id_usuario, u.nombre_usuario,
                            p.biografia, p.frase_estado, p.foto_perfil,
                            p.artista_favorito, p.cancion_favorita
                     FROM usuarios u
                     LEFT JOIN perfiles p ON u.id_usuario = p.id_usuario
                     WHERE u.nombre_usuario = :usuario
                     LIMIT 1";
        $stmtAmigo = $conexion->prepare($sqlAmigo);
        $stmtAmigo->execute([":usuario" => $usuarioParam]);
    }

    $amigo = $stmtAmigo->fetch(PDO::FETCH_ASSOC);

    if (!$amigo) {
        die("Usuario no encontrado.");
    }

    $idAmigo = (int)$amigo["id_usuario"];

    if ($idAmigo === $idVisitante) {
        header("Location: perfil.php");
        exit;
    }

    //Procesar acciones POST, comentar, borrar comentario o gestionar amistad
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $accion = $_POST["accion"] ?? "comentar";

        if ($accion === "enviar_solicitud") {
            $check = $conexion->prepare(
                "SELECT id_amistad FROM amistades
                 WHERE (id_usuario_solicitud = :u1 AND id_usuario_aceptado = :u2)
                    OR (id_usuario_solicitud = :u2 AND id_usuario_aceptado = :u1)
                 LIMIT 1"
            );
            $check->execute([":u1" => $idVisitante, ":u2" => $idAmigo]);

            if (!$check->fetch()) {
                $ins = $conexion->prepare(
                    "INSERT INTO amistades (id_usuario_solicitud, id_usuario_aceptado, estado)
                     VALUES (:sol, :acp, 'pendiente')"
                );
                $ins->execute([":sol" => $idVisitante, ":acp" => $idAmigo]);
            }

            header("Location: " . $baseQuery . "&ok=solicitud");
            exit;
        } elseif ($accion === "aceptar_solicitud") {
            $upd = $conexion->prepare(
                "UPDATE amistades
                 SET estado = 'aceptado'
                 WHERE id_usuario_solicitud = :sol
                   AND id_usuario_aceptado = :acp
                   AND estado = 'pendiente'"
            );
            $upd->execute([":sol" => $idAmigo, ":acp" => $idVisitante]);

            header("Location: " . $baseQuery . "&ok=aceptada");
            exit;
        } elseif ($accion === "eliminar_amistad") {
            $del = $conexion->prepare(
                "DELETE FROM amistades
                 WHERE (id_usuario_solicitud = :u1 AND id_usuario_aceptado = :u2)
                    OR (id_usuario_solicitud = :u2 AND id_usuario_aceptado = :u1)"
            );
            $del->execute([":u1" => $idVisitante, ":u2" => $idAmigo]);

            header("Location: " . $baseQuery . "&ok=eliminada");
            exit;
        } elseif ($accion === "borrar_comentario") {
            $idCom = (int)($_POST["id_comentario"] ?? 0);

            if ($idCom > 0) {
                $delCom = $conexion->prepare(
                    "DELETE FROM comentarios
                     WHERE id_comentario = :id_com AND id_usuario = :id_usr"
                );
                $delCom->execute([
                    ":id_com" => $idCom,
                    ":id_usr" => $idVisitante
                ]);
            }

            header("Location: " . $baseQuery . "&ok=del_com");
            exit;
        } elseif (isset($_POST["id_publicacion"], $_POST["comentario"])) {
            $idPub = (int)$_POST["id_publicacion"];
            $comentario = trim($_POST["comentario"]);

            if ($idPub > 0 && $comentario !== "") {
                $stmtInsertCom = $conexion->prepare(
                    "INSERT INTO comentarios (id_publicacion, id_usuario, contenido)
                     VALUES (:id_pub, :id_usr, :contenido)"
                );
                $stmtInsertCom->execute([
                    ":id_pub"    => $idPub,
                    ":id_usr"    => $idVisitante,
                    ":contenido" => $comentario
                ]);
            }

            header("Location: " . $baseQuery . "&ok=comentario");
            exit;
        }
    }

    //Estado de amistad entre usuarios
    $stmtAmistad = $conexion->prepare(
        "SELECT id_usuario_solicitud, id_usuario_aceptado, estado
         FROM amistades
         WHERE (id_usuario_solicitud = :u1 AND id_usuario_aceptado = :u2)
            OR (id_usuario_solicitud = :u2 AND id_usuario_aceptado = :u1)
         LIMIT 1"
    );
    $stmtAmistad->execute([":u1" => $idVisitante, ":u2" => $idAmigo]);
    $relacion = $stmtAmistad->fetch(PDO::FETCH_ASSOC);

    //Amigos aceptados del perfil
    $stmtAmigosDeAmigo = $conexion->prepare(
        "SELECT u.id_usuario, u.nombre_usuario
         FROM amistades a
         INNER JOIN usuarios u
            ON u.id_usuario = CASE
                WHEN a.id_usuario_solicitud = :id1 THEN a.id_usuario_aceptado
                ELSE a.id_usuario_solicitud
            END
         WHERE (a.id_usuario_solicitud = :id2 OR a.id_usuario_aceptado = :id3)
           AND a.estado = 'aceptado'
         ORDER BY u.nombre_usuario ASC
         LIMIT 10"
    );
    $stmtAmigosDeAmigo->execute([":id1" => $idAmigo, ":id2" => $idAmigo, ":id3" => $idAmigo]);
    $amigosDelPerfil = $stmtAmigosDeAmigo->fetchAll(PDO::FETCH_ASSOC);

    //Publicaciones del usuario
    $stmtPubs = $conexion->prepare(
        "SELECT id_publicacion, titulo, contenido, fecha_creacion
         FROM publicaciones
         WHERE id_usuario = :id
         ORDER BY fecha_creacion DESC, id_publicacion DESC"
    );
    $stmtPubs->execute([":id" => $idAmigo]);
    $publicaciones = $stmtPubs->fetchAll(PDO::FETCH_ASSOC);

    //Comentarios de publicaciones
    $comentariosPorPub = [];
    if (!empty($publicaciones)) {
        $idsPubs = array_map(fn($p) => (int)$p["id_publicacion"], $publicaciones);
        $placeholders = implode(",", array_fill(0, count($idsPubs), "?"));

        $stmtComs = $conexion->prepare(
            "SELECT c.id_comentario, c.id_publicacion, c.id_usuario, c.contenido, c.fecha_creacion, u.nombre_usuario
             FROM comentarios c
             INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
             WHERE c.id_publicacion IN ($placeholders)
             ORDER BY c.fecha_creacion ASC, c.id_comentario ASC"
        );
        $stmtComs->execute($idsPubs);

        while ($rowCom = $stmtComs->fetch(PDO::FETCH_ASSOC)) {
            $comentariosPorPub[(int)$rowCom["id_publicacion"]][] = $rowCom;
        }
    }
} catch (PDOException $e) {
    die("Error al cargar el perfil: " . $e->getMessage());
}

$mensaje = "";
if (isset($_GET["ok"])) {
    $mensajesOk = [
        "solicitud"  => "Solicitud de amistad enviada.",
        "aceptada"   => "Ahora son amigos.",
        "eliminada"  => "Relación de amistad actualizada.",
        "comentario" => "Comentario publicado.",
        "del_com"    => "Comentario eliminado."
    ];
    $mensaje = $mensajesOk[$_GET["ok"]] ?? "";
}

$fotoSrc = resolverFoto($amigo["foto_perfil"] ?? "");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/perfilamigo.css">
    <title>RetroSpace - Perfil de <?php echo e($amigo["nombre_usuario"]); ?></title>
</head>
<body>

    <header class="encabezado">
        <h1>RetroSpace</h1>
        <p>Tu espacio. Solo tu.</p>
    </header>

    <nav class="navegacion">
        <a href="../index.html">Inicio</a>
        <a href="perfil.php">Mi perfil</a>
        <a href="amigos.php">Amigos</a>
        <a href="cerrar-sesion.php">Cerrar sesión</a>
    </nav>

    <main>
        <?php if ($mensaje !== ""): ?>
            <p style="grid-column: 1 / -1; color: #00FF2F;"><?php echo e($mensaje); ?></p>
        <?php endif; ?>

        <section id="Barralateral">
            <div id="ImagenPerfil">
                <img id="FotoPerfil" src="<?php echo e($fotoSrc); ?>" alt="Foto de <?php echo e($amigo["nombre_usuario"]); ?>">
            </div>

            <div id="InfoPersonal">
                <p id="NombreUsuario"><?php echo e($amigo["nombre_usuario"]); ?></p>
                <p id="ArtistaFavorito"><?php echo e($amigo["artista_favorito"] ?: "Artista Favorito"); ?></p>
                <p id="CancionFavorita"><?php echo e($amigo["cancion_favorita"] ?: "Cancion Favorita"); ?></p>

                <!-- Estado y acción de amistad -->
                <div style="margin-top: 12px;">
                    <?php if (!$relacion): ?>
                        <form action="<?php echo e($baseQuery); ?>" method="POST">
                            <input type="hidden" name="accion" value="enviar_solicitud">
                            <button type="submit">Agregar amigo</button>
                        </form>
                    <?php elseif ($relacion["estado"] === "aceptado"): ?>
                        <p><strong>✓ Ya son amigos</strong></p>
                        <form action="<?php echo e($baseQuery); ?>" method="POST">
                            <input type="hidden" name="accion" value="eliminar_amistad">
                            <button type="submit">Eliminar amigo</button>
                        </form>
                    <?php elseif ((int)$relacion["id_usuario_solicitud"] === $idVisitante): ?>
                        <p>Solicitud pendiente</p>
                        <form action="<?php echo e($baseQuery); ?>" method="POST">
                            <input type="hidden" name="accion" value="eliminar_amistad">
                            <button type="submit">Cancelar solicitud</button>
                        </form>
                    <?php else: ?>
                        <p>Te envió solicitud de amistad</p>
                        <form action="<?php echo e($baseQuery); ?>" method="POST" style="display:inline;">
                            <input type="hidden" name="accion" value="aceptar_solicitud">
                            <button type="submit">Aceptar</button>
                        </form>
                        <form action="<?php echo e($baseQuery); ?>" method="POST" style="display:inline;">
                            <input type="hidden" name="accion" value="eliminar_amistad">
                            <button type="submit">Rechazar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section id="Amigos">
            <div id="ContenedorAmigos">
                <h2>AMIGOS DE <?php echo e(strtoupper($amigo["nombre_usuario"])); ?></h2>
                <?php if (empty($amigosDelPerfil)): ?>
                    <p>Aún no tiene amigos agregados.</p>
                <?php else: ?>
                    <?php foreach ($amigosDelPerfil as $amItem): ?>
                        <p>
                            <a href="perfil-amigo.php?id=<?php echo (int)$amItem["id_usuario"]; ?>">
                                <?php echo e($amItem["nombre_usuario"]); ?>
                            </a>
                        </p>
                    <?php endforeach; ?>
                <?php endif; ?>
                <button type="button" id="BotonVerAmigos" onclick="window.location.href='amigos.php'">Ver todos los amigos</button>
            </div>
        </section>

        <section id="Muro">
            <h2>Muro de <?php echo e($amigo["nombre_usuario"]); ?></h2>

            <?php if (empty($publicaciones)): ?>
                <p>Este usuario aún no tiene publicaciones.</p>
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
                                    <?php $idComActual = (int)$com["id_comentario"]; ?>
                                    <p>
                                        <strong><?php echo e($com["nombre_usuario"]); ?>:</strong>
                                        <?php echo e($com["contenido"]); ?>

                                        <?php if ((int)$com["id_usuario"] === $idVisitante): ?>
                                            <form action="<?php echo e($baseQuery); ?>" method="POST" style="display:inline; margin-left: 8px;" onsubmit="return confirm('¿Borrar este comentario?');">
                                                <input type="hidden" name="accion" value="borrar_comentario">
                                                <input type="hidden" name="id_comentario" value="<?php echo $idComActual; ?>">
                                                <button type="submit">Borrar</button>
                                            </form>
                                        <?php endif; ?>
                                    </p>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Sin comentarios todavía.</p>
                            <?php endif; ?>
                        </div>

                        <form action="<?php echo e($baseQuery); ?>" method="POST">
                            <input type="hidden" name="accion" value="comentar">
                            <input type="hidden" name="id_publicacion" value="<?php echo $idPubActual; ?>">
                            <input type="text" name="comentario" placeholder="Escribe un comentario..." required>
                            <button type="submit">Enviar</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <?php if (!empty($amigo["biografia"])): ?>
            <div id="resultado" style="grid-column: 1 / -1;">
                <?php echo $amigo["biografia"]; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <p>RetroSpace - Perfil de comunidad</p>
    </footer>

</body>
</html>