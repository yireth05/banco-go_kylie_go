<?php

session_start();
require_once 'conexionDB.php';


if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['id'])) {
    $id_cliente = $_GET['id'] ?? $_POST['id_cliente'] ?? null;

    if ($id_cliente) {
        try {
            // 1. Verificar si el cliente tiene cuentas 
            $stmt_cuentas = $conexion->prepare("SELECT COUNT(*) FROM cuentas WHERE id_cliente = ?");
            $stmt_cuentas->execute([$id_cliente]);
            $total_cuentas = $stmt_cuentas->fetchColumn();

            // 2. Verificar si el cliente tiene préstamos 
            $stmt_prestamos = $conexion->prepare("SELECT COUNT(*) FROM prestamos WHERE id_cliente = ?");
            $stmt_prestamos->execute([$id_cliente]);
            $total_prestamos = $stmt_prestamos->fetchColumn();

            // 3. Si tiene cuentas o préstamos, NO eliminación
            if ($total_cuentas > 0 || $total_prestamos > 0) {
                $mensaje_error = "No se puede eliminar al cliente #{$id_cliente} porque posee cuentas activas ({$total_cuentas}) y/o préstamos registrados ({$total_prestamos}). Debe cancelar sus productos primero.";
                header("Location: Crearcliente.php?error=" . urlencode($mensaje_error));
                exit;
            }

            $stmt_delete = $conexion->prepare("DELETE FROM clientes WHERE id_cliente = ?");
            $stmt_delete->execute([$id_cliente]);

            header("Location: Crearcliente.php?exito=" . urlencode("Cliente eliminado correctamente."));
            exit;

        } catch (PDOException $e) {
            $mensaje_error = "Error en la base de datos al intentar eliminar: " . $e->getMessage();
            header("Location: Crearcliente.php?error=" . urlencode($mensaje_error));
            exit;
        }
    }
}
