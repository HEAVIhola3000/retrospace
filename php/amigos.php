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

function resolverFoto($foto) {
    $limpia = trim((string)$foto, "'\" ");
    if ($limpia === "" || $limpia === "default_150x150.png") {
        return "https://placehold.co/80x80";
    }
    return "../" . ltrim($limpia, "/");
}

$id_usuario = (int)$_SESSION["id_usuario"];
$busqueda = trim($_GET["buscar"] ?? "");
$mensaje = "";
$error = "";

// Acciones POST sobre amistades
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";
    $idOtro = (int)($_POST["id_otro"] ?? 0);

    if ($idOtro > 0 && $idOtro !== $id_usuario) {
        try {
            if ($accion === "enviar") {
                // Evita duplicados en cualquier direccion
                $check = $conexion->prepare(
                    "SELECT id_amistad FROM amistades
                     WHERE (id_usuario_solicitud = :u1 AND id_usuario_aceptado = :u2)
                        OR (id_usuario_solicitud = :u2 AND id_usuario_aceptado = :u1)
                     LIMIT 1"
                );
                $check->execute([":u1" => $id_usuario, ":u2" => $idOtro]);

                if (!$check->fetch()) {
                    $ins = $conexion->prepare(
                        "INSERT INTO amistades (id_usuario_solicitud, id_usuario_aceptado, estado)
                         VALUES (:sol, :acp, 'pendiente')"
                    );
                    $ins->execute([":sol" => $id_usuario, ":acp" => $idOtro]);
                }

                $redir = "amigos.php?ok=enviada" . ($busqueda !== "" ? "&buscar=" . urlencode($busqueda) : "");
                header("Location: " . $redir);
                exit;
            } elseif ($accion === "aceptar") {
                $upd = $conexion->prepare(
                    "UPDATE amistades
                     SET estado = 'aceptado'
                     WHERE id_usuario_solicitud = :sol
                       AND id_usuario_aceptado = :acp
                       AND estado = 'pendiente'"
                );
                $upd->execute([":sol" => $idOtro, ":acp" => $id_usuario]);

                $redir = "amigos.php?ok=aceptada" . ($busqueda !== "" ? "&buscar=" . urlencode($busqueda) : "");
                header("Location: " . $redir);
                exit;
            } elseif ($accion === "eliminar") {
                $del = $conexion->prepare(
                    "DELETE FROM amistades
                     WHERE (id_usuario_solicitud = :u1 AND id_usuario_aceptado = :u2)
                        OR (id_usuario_solicitud = :u2 AND id_usuario_aceptado = :u1)"
                );
                $del->execute([":u1" => $id_usuario, ":u2" => $idOtro]);

                $redir = "amigos.php?ok=eliminada" . ($busqueda !== "" ? "&buscar=" . urlencode($busqueda) : "");
                header("Location: " . $redir);
                exit;
            }
        } catch (PDOException $e) {
            $error = "Error al actualizar amistad: " . $e->getMessage();
        }
    }
}

if (isset($_GET["ok"])) {
    $mensajesOk = [
        "enviada"   => "Solicitud de amistad enviada.",
        "aceptada"  => "Ahora son amigos.",
        "eliminada" => "Relación actualizada."
    ];
    $mensaje = $mensajesOk[$_GET["ok"]] ?? "";
}

try {
    //Solicitudes recibidas pendientes
    $stmtRecibidas = $conexion->prepare(
        "SELECT u.id_usuario, u.nombre_usuario, p.foto_perfil, p.artista_favorito
         FROM amistades a
         INNER JOIN usuarios u ON a.id_usuario_solicitud = u.id_usuario
         LEFT JOIN perfiles p ON u.id_usuario = p.id_usuario
         WHERE a.id_usuario_aceptado = :id
           AND a.estado = 'pendiente'
         ORDER BY a.id_amistad DESC"
    );
    $stmtRecibidas->execute([":id" => $id_usuario]);
    $solicitudesRecibidas = $stmtRecibidas->fetchAll(PDO::FETCH_ASSOC);

    //Amigos aceptados
    $stmtAmigos = $conexion->prepare(
        "SELECT u.id_usuario, u.nombre_usuario, p.foto_perfil, p.artista_favorito, p.cancion_favorita
         FROM amistades a
         INNER JOIN usuarios u
            ON u.id_usuario = CASE
                WHEN a.id_usuario_solicitud = :id1 THEN a.id_usuario_aceptado
                ELSE a.id_usuario_solicitud
            END
         LEFT JOIN perfiles p ON u.id_usuario = p.id_usuario
         WHERE (a.id_usuario_solicitud = :id2 OR a.id_usuario_aceptado = :id3)
           AND a.estado = 'aceptado'
         ORDER BY u.nombre_usuario ASC"
    );
    $stmtAmigos->execute([":id1" => $id_usuario, ":id2" => $id_usuario, ":id3" => $id_usuario]);
    $amigos = $stmtAmigos->fetchAll(PDO::FETCH_ASSOC);

    //Explorar / buscar otros usuarios
    if ($busqueda !== "") {
        $stmtExplorar = $conexion->prepare(
            "SELECT u.id_usuario, u.nombre_usuario, p.foto_perfil, p.artista_favorito,
                    a.estado, a.id_usuario_solicitud
             FROM usuarios u
             LEFT JOIN perfiles p ON u.id_usuario = p.id_usuario
             LEFT JOIN amistades a
                ON (a.id_usuario_solicitud = :id1 AND a.id_usuario_aceptado = u.id_usuario)
                OR (a.id_usuario_aceptado = :id2 AND a.id_usuario_solicitud = u.id_usuario)
             WHERE u.id_usuario != :id3
               AND u.nombre_usuario LIKE :q
             ORDER BY u.nombre_usuario ASC
             LIMIT 30"
        );
        $stmtExplorar->execute([
            ":id1" => $id_usuario,
            ":id2" => $id_usuario,
            ":id3" => $id_usuario,
            ":q"   => "%" . $busqueda . "%"
        ]);
    } else {
        $stmtExplorar = $conexion->prepare(
            "SELECT u.id_usuario, u.nombre_usuario, p.foto_perfil, p.artista_favorito,
                    a.estado, a.id_usuario_solicitud
             FROM usuarios u
             LEFT JOIN perfiles p ON u.id_usuario = p.id_usuario
             LEFT JOIN amistades a
                ON (a.id_usuario_solicitud = :id1 AND a.id_usuario_aceptado = u.id_usuario)
                OR (a.id_usuario_aceptado = :id2 AND a.id_usuario_solicitud = u.id_usuario)
             WHERE u.id_usuario != :id3
             ORDER BY u.id_usuario DESC
             LIMIT 20"
        );
        $stmtExplorar->execute([
            ":id1" => $id_usuario,
            ":id2" => $id_usuario,
            ":id3" => $id_usuario
        ]);
    }
    $usuariosExplorar = $stmtExplorar->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al cargar amigos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/amigos.css">
    <title>RetroSpace - Amigos</title>
</head>
<body>
    <header>
        <h1>RetroSpace</h1>
        <p>Tu espacio, solo tu.</p>
    </header>

    <nav>
        <a href="../index.html">Inicio</a>
        <a href="perfil.php">Mi perfil</a>
        <a href="amigos.php">Amigos</a>
        <a href="cerrar-sesion.php">Cerrar sesión</a>
    </nav>

    <main>
        <?php if ($mensaje !== ""): ?>
            <p style="color: #00FF2F;"><?php echo e($mensaje); ?></p>
        <?php endif; ?>
        <?php if ($error !== ""): ?>
            <p style="color: #ff007f;"><?php echo e($error); ?></p>
        <?php endif; ?>

        //buscador de usuarios
        <section id="BuscadorAmigos">
            <h2>Buscar personas en RetroSpace</h2>
            <form action="amigos.php" method="GET">
                <input type="text" name="buscar" value="<?php echo e($busqueda); ?>" placeholder="Escribe un nombre de usuario...">
                <button type="submit">Buscar</button>
                <?php if ($busqueda !== ""): ?>
                    <a href="amigos.php">Limpiar</a>
                <?php endif; ?>
            </form>
        </section>

        //solicitudes recibidas
        <section id="SolicitudesPendientes">
            <h2>Solicitudes recibidas (<?php echo count($solicitudesRecibidas); ?>)</h2>
            <?php if (empty($solicitudesRecibidas)): ?>
                <p>No tienes solicitudes pendientes.</p>
            <?php else: ?>
                <?php foreach ($solicitudesRecibidas as $sol): ?>
                    <article class="tarjeta-amigo">
                        <img src="<?php echo e(resolverFoto($sol["foto_perfil"])); ?>" alt="Foto" width="60" height="60">
                        <div>
                            <a href="perfil-amigo.php?id=<?php echo (int)$sol["id_usuario"]; ?>">
                                <strong><?php echo e($sol["nombre_usuario"]); ?></strong>
                            </a>
                            <p><?php echo e($sol["artista_favorito"] ?: "Sin artista favorito"); ?></p>
                        </div>
                        <form action="amigos.php<?php echo $busqueda !== "" ? "?buscar=" . urlencode($busqueda) : ""; ?>" method="POST" style="display:inline;">
                            <input type="hidden" name="accion" value="aceptar">
                            <input type="hidden" name="id_otro" value="<?php echo (int)$sol["id_usuario"]; ?>">
                            <button type="submit">Aceptar</button>
                        </form>
                        <form action="amigos.php<?php echo $busqueda !== "" ? "?buscar=" . urlencode($busqueda) : ""; ?>" method="POST" style="display:inline;">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_otro" value="<?php echo (int)$sol["id_usuario"]; ?>">
                            <button type="submit">Rechazar</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        //lista de amnigos recibidas
        <section id="ListaMisAmigos">
            <h2>Mis Amigos (<?php echo count($amigos); ?>)</h2>
            <?php if (empty($amigos)): ?>
                <p>Aún no tienes amigos agregados.</p>
            <?php else: ?>
                <?php foreach ($amigos as $am): ?>
                    <article class="tarjeta-amigo">
                        <img src="<?php echo e(resolverFoto($am["foto_perfil"])); ?>" alt="Foto" width="60" height="60">
                        <div>
                            <a href="perfil-amigo.php?id=<?php echo (int)$am["id_usuario"]; ?>">
                                <strong><?php echo e($am["nombre_usuario"]); ?></strong>
                            </a>
                            <p>Artista: <?php echo e($am["artista_favorito"] ?: "N/A"); ?> | Canción: <?php echo e($am["cancion_favorita"] ?: "N/A"); ?></p>
                        </div>
                        <a href="perfil-amigo.php?id=<?php echo (int)$am["id_usuario"]; ?>">Ver perfil</a>
                        <form action="amigos.php<?php echo $busqueda !== "" ? "?buscar=" . urlencode($busqueda) : ""; ?>" method="POST" style="display:inline;">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="id_otro" value="<?php echo (int)$am["id_usuario"]; ?>">
                            <button type="submit">Eliminar amigo</button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        //explorar usuarios
        <section id="ExplorarUsuarios">
            <h2><?php echo $busqueda !== "" ? "Resultados de búsqueda" : "Comunidad RetroSpace"; ?></h2>
            <?php if (empty($usuariosExplorar)): ?>
                <p>No se encontraron usuarios.</p>
            <?php else: ?>
                <?php foreach ($usuariosExplorar as $usr): ?>
                    <article class="tarjeta-amigo">
                        <img src="<?php echo e(resolverFoto($usr["foto_perfil"])); ?>" alt="Foto" width="60" height="60">
                        <div>
                            <a href="perfil-amigo.php?id=<?php echo (int)$usr["id_usuario"]; ?>">
                                <strong><?php echo e($usr["nombre_usuario"]); ?></strong>
                            </a>
                            <p><?php echo e($usr["artista_favorito"] ?: "Usuario de RetroSpace"); ?></p>
                        </div>

                        <?php if ($usr["estado"] === "aceptado"): ?>
                            <span>Ya son amigos</span>
                        <?php elseif ($usr["estado"] === "pendiente" && (int)$usr["id_usuario_solicitud"] === $id_usuario): ?>
                            <form action="amigos.php<?php echo $busqueda !== "" ? "?buscar=" . urlencode($busqueda) : ""; ?>" method="POST" style="display:inline;">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_otro" value="<?php echo (int)$usr["id_usuario"]; ?>">
                                <button type="submit">Cancelar solicitud</button>
                            </form>
                        <?php elseif ($usr["estado"] === "pendiente"): ?>
                            <form action="amigos.php<?php echo $busqueda !== "" ? "?buscar=" . urlencode($busqueda) : ""; ?>" method="POST" style="display:inline;">
                                <input type="hidden" name="accion" value="aceptar">
                                <input type="hidden" name="id_otro" value="<?php echo (int)$usr["id_usuario"]; ?>">
                                <button type="submit">Aceptar solicitud</button>
                            </form>
                        <?php else: ?>
                            <form action="amigos.php<?php echo $busqueda !== "" ? "?buscar=" . urlencode($busqueda) : ""; ?>" method="POST" style="display:inline;">
                                <input type="hidden" name="accion" value="enviar">
                                <input type="hidden" name="id_otro" value="<?php echo (int)$usr["id_usuario"]; ?>">
                                <button type="submit">Agregar amigo</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>RetroSpace - 2026 - Proyecto Escolar</p>
    </footer>
</body>
</html>