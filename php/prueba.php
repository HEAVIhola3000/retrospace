<?php
require __DIR__ . '/../includes/conexion.php';

echo "Conectado a retro_space<br>";

$tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tablas: " . implode(", ", $tablas);