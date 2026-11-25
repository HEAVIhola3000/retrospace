<?php
session_start();
require_once __DIR__ . "/conexiones.php";

if (!isset($_SESSION["id_usuario"])) {
    header("Location: ../login.html");
    exit;
}

function e($dato) {
    return htmlspecialchars((string)$dato, ENT_QUOTES, "UTF-8");
}

$id_usuario = (int)$_SESSION["id_usuario"];
$mensaje = "";
$error = "";

// Procesar acciones enviadas por POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";

    try {
        if ($accion === "editar_perfil") {
            $nuevoNombre = trim($_POST["nombre_usuario"] ?? "");
            $artista = trim($_POST["artista_favorito"] ?? "");
            $cancion = trim($_POST["cancion_favorita"] ?? "");

            if ($nuevoNombre === "" || strlen($nuevoNombre) < 3 || strlen($nuevoNombre) > 50) {
                $error = "El nombre de usuario debe tener entre 3 y 50 caracteres.";
            } else {
                $checkUser = $conexion->prepare(
                    "SELECT id_usuario FROM usuarios WHERE nombre_usuario = :nombre AND id_usuario != :id LIMIT 1"
                );
                $checkUser->execute([":nombre" => $nuevoNombre, ":id" => $id_usuario]);

                if ($checkUser->fetch()) {
                    $error = "Ese nombre de usuario ya está en uso.";
                } else {
                    $conexion->beginTransaction();

                    $updUser = $conexion->prepare(
                        "UPDATE usuarios SET nombre_usuario = :nombre WHERE id_usuario = :id"
                    );
                    $updUser->execute([":nombre" => $nuevoNombre, ":id" => $id_usuario]);

                    $updPerfil = $conexion->prepare(
                        "INSERT INTO perfiles (id_usuario, artista_favorito, cancion_favorita)
                         VALUES (:id, :artista, :cancion)
                         ON DUPLICATE KEY UPDATE
                            artista_favorito = VALUES(artista_favorito),
                            cancion_favorita = VALUES(cancion_favorita)"
                    );
                    $updPerfil->execute([
                        ":id" => $id_usuario,
                        ":artista" => $artista,
                        ":cancion" => $cancion
                    ]);

                    $conexion->commit();
                    $_SESSION["nombre_usuario"] = $nuevoNombre;

                    header("Location: perfil.php?ok=perfil");
                    exit;
                }
            }
        } elseif ($accion === "subir_foto" && isset($_FILES["foto_perfil"])) {
            $archivo = $_FILES["foto_perfil"];

            if ($archivo["error"] === UPLOAD_ERR_OK) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($archivo["tmp_name"]);
                $permitidos = [
                    "image/jpeg" => "jpg",
                    "image/png"  => "png",
                    "image/webp" => "webp",
                    "image/gif"  => "gif"
                ];

                if (!isset($permitidos[$mime])) {
                    $error = "Solo se permiten imágenes JPG, PNG, WEBP o GIF.";
                } elseif ($archivo["size"] > 3 * 1024 * 1024) {
                    $error = "La imagen no debe superar los 3 MB.";
                } else {
                    $carpetaDestino = __DIR__ . "/../uploads/perfiles";
                    if (!is_dir($carpetaDestino)) {
                        mkdir($carpetaDestino, 0777, true);
                    }

                    $nombreArchivo = "user_" . $id_usuario . "_" . time() . "." . $permitidos[$mime];
                    $rutaFisica = $carpetaDestino . "/" . $nombreArchivo;
                    $rutaRelativa = "uploads/perfiles/" . $nombreArchivo;

                    if (move_uploaded_file($archivo["tmp_name"], $rutaFisica)) {
                        $stmtFoto = $conexion->prepare(
                            "INSERT INTO perfiles (id_usuario, foto_perfil, cancion_favorita)
                             VALUES (:id, :foto, '')
                             ON DUPLICATE KEY UPDATE foto_perfil = VALUES(foto_perfil)"
                        );
                        $stmtFoto->execute([":id" => $id_usuario, ":foto" => $rutaRelativa]);

                        header("Location: perfil.php?ok=foto");
                        exit;
                    } else {
                        $error = "No se pudo guardar el archivo en el servidor.";
                    }
                }
            } else {
                $error = "Selecciona una imagen válida.";
            }
        } elseif ($accion === "nueva_publicacion") {
            $titulo = trim($_POST["titulo"] ?? "");
            $contenido = trim($_POST["publicacion"] ?? "");

            if ($titulo === "" || $contenido === "") {
                $error = "Completa el título y el contenido de la publicación.";
            } else {
                $stmtPub = $conexion->prepare(
                    "INSERT INTO publicaciones (id_usuario, titulo, contenido)
                     VALUES (:id_usuario, :titulo, :contenido)"
                );
                $stmtPub->execute([
                    ":id_usuario" => $id_usuario,
                    ":titulo" => $titulo,
                    ":contenido" => $contenido
                ]);

                header("Location: perfil.php?ok=pub");
                exit;
            }
        } elseif ($accion === "borrar_publicacion") {
            $idPub = (int)($_POST["id_publicacion"] ?? 0);

            if ($idPub > 0) {
                $conexion->beginTransaction();

                $delComsPub = $conexion->prepare(
                    "DELETE c FROM comentarios c
                     INNER JOIN publicaciones p ON c.id_publicacion = p.id_publicacion
                     WHERE c.id_publicacion = :id_pub AND p.id_usuario = :id_usr"
                );
                $delComsPub->execute([":id_pub" => $idPub, ":id_usr" => $id_usuario]);

                $delPub = $conexion->prepare(
                    "DELETE FROM publicaciones WHERE id_publicacion = :id_pub AND id_usuario = :id_usr"
                );
                $delPub->execute([":id_pub" => $idPub, ":id_usr" => $id_usuario]);

                $conexion->commit();
            }

            header("Location: perfil.php?ok=del_pub");
            exit;
        } elseif ($accion === "nuevo_comentario") {
            $idPub = (int)($_POST["id_publicacion"] ?? 0);
            $textoCom = trim($_POST["comentario"] ?? "");

            if ($idPub > 0 && $textoCom !== "") {
                $stmtCom = $conexion->prepare(
                    "INSERT INTO comentarios (id_publicacion, id_usuario, contenido)
                     VALUES (:id_pub, :id_usr, :contenido)"
                );
                $stmtCom->execute([
                    ":id_pub" => $idPub,
                    ":id_usr" => $id_usuario,
                    ":contenido" => $textoCom
                ]);

                header("Location: perfil.php?ok=com");
                exit;
            }
        } elseif ($accion === "borrar_comentario") {
            $idCom = (int)($_POST["id_comentario"] ?? 0);

            if ($idCom > 0) {
                $delCom = $conexion->prepare(
                    "DELETE c FROM comentarios c
                     INNER JOIN publicaciones p ON c.id_publicacion = p.id_publicacion
                     WHERE c.id_comentario = :id_com
                       AND (c.id_usuario = :id_usr1 OR p.id_usuario = :id_usr2)"
                );
                $delCom->execute([
                    ":id_com"  => $idCom,
                    ":id_usr1" => $id_usuario,
                    ":id_usr2" => $id_usuario
                ]);
            }

            header("Location: perfil.php?ok=del_com");
            exit;
        } elseif ($accion === "guardar_widget") {
            $widgetHtml = trim($_POST["widget_html"] ?? "");

            $stmtWidget = $conexion->prepare(
                "INSERT INTO perfiles (id_usuario, biografia, cancion_favorita)
                 VALUES (:id, :bio, '')
                 ON DUPLICATE KEY UPDATE biografia = VALUES(biografia)"
            );
            $stmtWidget->execute([
                ":id"  => $id_usuario,
                ":bio" => $widgetHtml
            ]);

            header("Location: perfil.php?ok=widget");
            exit;
        }
    } catch (PDOException $e) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        $error = "Error en base de datos: " . $e->getMessage();
    }
}

if (isset($_GET["ok"])) {
    $mapOk = [
        "perfil"  => "Datos de perfil actualizados.",
        "foto"    => "Foto de perfil actualizada.",
        "pub"     => "Publicación creada en tu muro.",
        "com"     => "Comentario guardado.",
        "widget"  => "Personalización del perfil guardada.",
        "del_pub" => "Publicación eliminada.",
        "del_com" => "Comentario eliminado."
    ];
    $mensaje = $mapOk[$_GET["ok"]] ?? "";
}

try {
    //Datos del usuario y perfil
    $consulta = $conexion->prepare(
        "SELECT u.id_usuario,
                u.nombre_usuario,
                p.biografia,
                p.frase_estado,
                p.foto_perfil,
                p.artista_favorito,
                p.cancion_favorita
         FROM usuarios u
         LEFT JOIN perfiles p ON u.id_usuario = p.id_usuario
         WHERE u.id_usuario = :id
         LIMIT 1"
    );
    $consulta->execute([":id" => $id_usuario]);
    $perfil = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$perfil) {
        die("No se encontró el perfil.");
    }

    // Amigos aceptados
    $stmtAmigos = $conexion->prepare(
        "SELECT u.id_usuario, u.nombre_usuario
         FROM amistades a
         INNER JOIN usuarios u
            ON u.id_usuario = CASE
                WHEN a.id_usuario_solicitud = :id1 THEN a.id_usuario_aceptado
                ELSE a.id_usuario_solicitud
            END
         WHERE (a.id_usuario_solicitud = :id2 OR a.id_usuario_aceptado = :id3)
           AND a.estado = 'aceptado'
         ORDER BY u.nombre_usuario ASC"
    );
    $stmtAmigos->execute([":id1" => $id_usuario, ":id2" => $id_usuario, ":id3" => $id_usuario]);
    $amigos = $stmtAmigos->fetchAll(PDO::FETCH_ASSOC);

    // Publicaciones propias
    $consultaPublicaciones = $conexion->prepare(
        "SELECT id_publicacion, titulo, contenido, fecha_creacion
         FROM publicaciones
         WHERE id_usuario = :id
         ORDER BY fecha_creacion DESC, id_publicacion DESC"
    );
    $consultaPublicaciones->execute([":id" => $id_usuario]);
    $publicaciones = $consultaPublicaciones->fetchAll(PDO::FETCH_ASSOC);

    // Comentarios de cada publicacion
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
    die("Error al consultar el perfil: " . $e->getMessage());
}

$fotoLimpia = trim((string)($perfil["foto_perfil"] ?? ""), "'\" ");
if ($fotoLimpia === "" || $fotoLimpia === "default_150x150.png") {
    $fotoSrc = "https://placehold.co/150x150";
} else {
    $fotoSrc = "../" . ltrim($fotoLimpia, "/");
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
        <?php if ($error !== ""): ?>
            <p style="grid-column: 1 / -1; color: #ff007f;"><?php echo e($error); ?></p>
        <?php endif; ?>

        <section id="Barralateral">
            <div id="ImagenPerfil">
                <img id="FotoPerfil" src="<?php echo e($fotoSrc); ?>" alt="Foto de perfil">

                <form id="FormFotoPerfil" action="perfil.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="accion" value="subir_foto">
                    <input type="file" id="ArchivoFoto" name="foto_perfil" accept="image/*" hidden>
                    <button type="button" id="CambiarFoto">Cambiar Foto</button>
                </form>
            </div>

            <div id="InfoPersonal">
                <p id="NombreUsuario"><?php echo e($perfil["nombre_usuario"]); ?></p>
                <button type="button" id="BotonUsuario">Editar mi Usuario</button>

                <p id="ArtistaFavorito"><?php echo e($perfil["artista_favorito"] ?: "Artista Favorito"); ?></p>
                <button type="button" id="BotonArtista">Editar mi Artista</button>

                <p id="CancionFavorita"><?php echo e($perfil["cancion_favorita"] ?: "Cancion Favorita"); ?></p>
                <button type="button" id="BotonCancion">Editar mi Cancion</button>

                <form id="FormEditarPerfil" action="perfil.php" method="POST" style="display: none; margin-top: 12px;">
                    <input type="hidden" name="accion" value="editar_perfil">
                    <input type="hidden" id="InputNombreUsuario" name="nombre_usuario" value="<?php echo e($perfil["nombre_usuario"]); ?>">
                    <input type="hidden" id="InputArtistaFavorito" name="artista_favorito" value="<?php echo e($perfil["artista_favorito"] ?? ""); ?>">
                    <input type="hidden" id="InputCancionFavorita" name="cancion_favorita" value="<?php echo e($perfil["cancion_favorita"] ?? ""); ?>">
                    <button type="submit" id="GuardarCambiosPerfil">Guardar cambios de perfil</button>
                </form>
            </div>
        </section>

        <section id="Amigos">
            <div id="ContenedorAmigos">
                <h2>MIS AMIGOS</h2>
                <?php if (empty($amigos)): ?>
                    <p>Aún no tienes amigos agregados.</p>
                <?php else: ?>
                    <?php foreach ($amigos as $amigoItem): ?>
                        <p>
                            <a href="perfil-amigo.php?id=<?php echo (int)$amigoItem["id_usuario"]; ?>">
                                <?php echo e($amigoItem["nombre_usuario"]); ?>
                            </a>
                        </p>
                    <?php endforeach; ?>
                <?php endif; ?>
                <button type="button" id="BotonVerAmigos" onclick="window.location.href='amigos.php'">Ver todos los amigos</button>
            </div>
        </section>

        <section id="Muro">
            <h2>Mi Muro</h2>

            <form id="FormularioPublicacion" action="perfil.php" method="POST">
                <input type="hidden" name="accion" value="nueva_publicacion">
                <input type="text" id="TituloPublicacion" name="titulo" placeholder="Escribe el titulo..." required>
                <textarea name="publicacion" id="TextoPublicacion" placeholder="¿Qué estas pensando?" required></textarea>
                <button type="submit">Publicar</button>
            </form>

            <?php if (empty($publicaciones)): ?>
                <p>Aún no has creado publicaciones.</p>
            <?php else: ?>
                <?php foreach ($publicaciones as $pub): ?>
                    <?php $idPubActual = (int)$pub["id_publicacion"]; ?>
                    <article class="publicacion">
                        <h3><?php echo e($pub["titulo"]); ?></h3>
                        <p><?php echo nl2br(e($pub["contenido"])); ?></p>

                        <form action="perfil.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Borrar esta publicación?');">
                            <input type="hidden" name="accion" value="borrar_publicacion">
                            <input type="hidden" name="id_publicacion" value="<?php echo $idPubActual; ?>">
                            <button type="submit">Borrar publicación</button>
                        </form>

                        <div class="listaComentarios">
                            <p>Comentarios</p>
                            <?php if (!empty($comentariosPorPub[$idPubActual])): ?>
                                <?php foreach ($comentariosPorPub[$idPubActual] as $com): ?>
                                    <?php $idComActual = (int)$com["id_comentario"]; ?>
                                    <p>
                                        <strong><?php echo e($com["nombre_usuario"]); ?>:</strong>
                                        <?php echo e($com["contenido"]); ?>

                                        <?php if ((int)$com["id_usuario"] === $id_usuario): ?>
                                            <form action="perfil.php" method="POST" style="display:inline; margin-left: 8px;" onsubmit="return confirm('¿Borrar este comentario?');">
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

                        <form action="perfil.php" method="POST">
                            <input type="hidden" name="accion" value="nuevo_comentario">
                            <input type="hidden" name="id_publicacion" value="<?php echo $idPubActual; ?>">
                            <input id="EscribirComentario" type="text" name="comentario" placeholder="Escribe un comentario..." required>
                            <button type="submit">Enviar</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <button type="button" id="insertar">?</button><br>
        <div id="bonus" class="solidSnake">
            <textarea id="texto" placeholder="Pega aquí tu HTML y <style>..."></textarea>
            <button type="button" id="guardar">Guardar cambios</button>
        </div><br>

        <form id="FormGuardarWidget" action="perfil.php" method="POST" style="display: none;">
            <input type="hidden" name="accion" value="guardar_widget">
            <textarea name="widget_html" id="InputWidgetHtml"></textarea>
        </form>

        <div id="resultado" style="grid-column: 1 / -1;">
            <?php echo $perfil["biografia"] ?? ""; ?>
        </div>
    </main>

    <footer>
        <p>RetroSpace - Mi espacio personal</p>
    </footer>

    <script src="../js/perfil.js"></script>
</body>
</html>