<?php
// eliminar_cliente.php
session_start();
require_once "conexionDB.php";

header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'cajero'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (isset($_GET['id'])) {
    $id_cliente = $_GET['id'];

    try {
        $stmt = $conexion->prepare("DELETE FROM clientes WHERE id_cliente = :id");
        $stmt->bindParam(':id', $id_cliente, PDO::PARAM_STR);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Cliente eliminado correctamente']);
        exit;

    } catch (PDOException $e) {
        // Error por restricciones de llaves foráneas (cuentas, préstamos activos)
        echo json_encode([
            'success' => false, 
            'message' => 'No se puede eliminar este cliente porque tiene cuentas, préstamos o transacciones asociadas en el sistema.'
        ]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID de cliente no proporcionado']);
    exit;
}
?>
