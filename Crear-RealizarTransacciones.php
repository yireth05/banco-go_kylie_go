<?php
include_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id_cliente = $_POST["id_cliente"];
    $num_cuenta = $_POST["num_cuenta"];
    $cod_tipo = $_POST["cod_tipo"];       // 1 para Depósito, 2 para Retiro 
    $cod_forma = $_POST["cod_forma"];     // 1: Efectivo, 2: Cheque, 3: Transferencia, etc.
    $monto = $_POST["monto"];
    // Dependiendo de quién haga la transacción, se pasa el cajero o la banca virtual:
    // Si es en ventanilla física:
    $id_cajero = $_POST["id_cajero"] ?? null; 
    // Si es por la App/Web del cliente:
    $id_banca_virtual = $_POST["id_banca_virtual"] ?? null; 
try {
        // 2. Llamar a la función almacenada de PostgreSQL usando PDO
    $sql = "SELECT realizar_transaccion(:cliente, :cuenta, :tipo, :forma, :monto, :cajero, :banca)";
    $stmt = $conexion->prepare($sql);
        
    $stmt->execute([':cliente' => $id_cliente, ':cuenta' => $num_cuenta, ':tipo' => $cod_tipo,':forma' => $cod_forma,':monto' => $monto, ':cajero' => $id_cajero,':banca' => $id_banca_virtual]);
        $resultado = $stmt->fetch();
        echo "<div class='alert alert-success'>¡Transacción exitosa! ID de transacción generado: </div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>Error en la operación: " . $e->getMessage() . "</div>";
    }
}
?>
