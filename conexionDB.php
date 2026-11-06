<?php
$host='localhost';
$dbname='banco_villavicencio';
$user= 'postgrest';
$password='contraseña';

try{
  $conexion= new PDO("pgsql:host=$host;dbname=$dbname",$user,$password);}
  $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//METODO para cambiar una confi o atributo, ERRMODE indica a PDO que se modifica el atributo 
//encargado del control de comportamiento de errores, EXCEPTION, lanza una excepcion en lugar de solo 
//mostrar una advertencia en pantalla
  echo "Estas conectado al Banco Villavicencio :)";
}
  catch (Exception $e){
    echo "No se logra conectar al Banco VIllavicencio: " . $e->getMessage();
  }
?>
