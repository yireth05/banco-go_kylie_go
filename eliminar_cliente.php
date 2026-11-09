<?php
// eliminar_cliente.php - responde JSON para el fetch de listadeClientesRead.php
ob_start();
session_start();
require_once 'conexionDB.php';

function responder(bool $success, string $message, int $http = 200): void {
    ob_clean(); // descarta cualquier salida accidental antes del JSON
    http_response_code($http);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    responder(false, 'Solo el administrador puede eliminar clientes.', 403);
}

$id_cliente = trim($_GET['id'] ?? $_POST['id_cliente'] ?? '');
if ($id_cliente === '') {
    responder(false, 'No se recibió el ID del cliente.', 400);
}

try {
    $stmt = $conexion->prepare("SELECT COUNT(*) FROM cuentas WHERE id_cliente = ?");
    $stmt->execute([$id_cliente]);
    $total_cuentas = (int)$stmt->fetchColumn();

    $stmt = $conexion->prepare("SELECT COUNT(*) FROM prestamos WHERE id_cliente = ?");
    $stmt->execute([$id_cliente]);
    $total_prestamos = (int)$stmt->fetchColumn();

    if ($total_cuentas > 0 || $total_prestamos > 0) {
        responder(false, "El cliente #{$id_cliente} tiene cuentas ({$total_cuentas}) y/o préstamos ({$total_prestamos}). Solo se pueden eliminar clientes sin cuentas ni préstamos.");
    }

    $del = $conexion->prepare("DELETE FROM clientes WHERE id_cliente = ?");
    $del->execute([$id_cliente]);

    if ($del->rowCount() === 0) {
        responder(false, 'El cliente no existe o ya fue eliminado.');
    }

    responder(true, 'Cliente eliminado correctamente.');

} catch (PDOException $e) {
    responder(false, 'Error en la base de datos al intentar eliminar: ' . $e->getMessage(), 500);
}
