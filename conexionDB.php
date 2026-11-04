<?php
$host='localhost';
$dbname='banco_villavicencio';
$user= 'postgrest';
$password='contraseña';

try{
  $conexion= new PDO("pgsql:host=$host;dbname=$dbname",$user,$password);
  echo "Estas conectado al Banco Villavicencio :)";
}
  catch (Exception $e){
    echo "No se logra conectar al Banco VIllavicencio: " . $e->getMessage();
  }
?>
