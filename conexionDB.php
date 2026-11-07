<?php
// conexionDB.php
$host     = "127.0.0.1";
$port     = "5432";
$dbname   = "banco_go_kylie_go";
$user     = "postgres";
$password = "admin123";

try {
    $conexion = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexion a la base de datos: " . $e->getMessage());
}
?>
