<?php
if (!isset($_POST["id_cliente"]) || !isset($_POST["nomb_cli"]) || !isset($_POST["apelli_cli"])) {
    exit("Faltan datos obligatorios por enviar, porfavor ingresar datos faltantes.");
}
include_once "conexiondb-php";
$id_cliente=$_POST["id_cliente"];
$nomb_cli=$_POST["nomb_cli"];
$apelli_cli=$_POST["apelli_cli"];
$direccion=$_POST["direccion"];
$telefono=$_POST["telefono"];
$correo=$_POST["correo"];

$sentencia=$conexion->prepare("INSERT INTO clientes(id_cliente,nomb_cli, apelli_cli,direccion,telefono, correo) VALUES (?,?,?,?,?,?);");

$resultado=$sentencia->exxecute([$id_cliente,$nomb_cli,$apelli_cli,$direccion,$telefono,$correo]);
if($resultado===true){
  header("Location:nuevo_cliente.php?exito=1");
}else{
  echo "Error al crear nuevo cliente,verificar los datos";}
?>
