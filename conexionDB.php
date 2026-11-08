<?php
// conexionDB.php
$host     = getenv('DB_HOST') ?: "127.0.0.1";
$port     = getenv('DB_PORT') ?: "5432";
$dbname   = getenv('DB_NAME') ?: "banco_villavicencio";
$user     = getenv('DB_USER') ?: "postgres";
$password = getenv('DB_PASS') ?: "admin123";

try {
    $conexion = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión a la base de datos: " . $e->getMessage());
}
?>





































