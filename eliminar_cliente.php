<?php
// eliminar_cliente.php
session_start();
require_once "conexionDB.php";

// Validar que el usuario tenga permisos (administrador o cajero)
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'cajero'])) {
    header("Location: login.php");
    exit;
}

// Verificar si se recibió el ID del cliente a eliminar
if (isset($_GET['id'])) {
    $id_cliente = $_GET['id'];

    try {
        // Preparar la consulta de eliminación para evitar inyecciones SQL
        $stmt = $conexion->prepare("DELETE FROM clientes WHERE id_cliente = :id");
        $stmt->bindParam(':id', $id_cliente, PDO::PARAM_STR);
        $stmt->execute();

        // Redirigir de regreso al listado de clientes
        header("Location: listar_clientes.php");
        exit;

    } catch (PDOException $e) {
        // Error común en bancos: restricciones de llave foránea (si el cliente tiene cuentas o préstamos activos)
        echo "<div style='font-family: sans-serif; padding: 20px; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;'>";
        echo "<h4>Error al eliminar el cliente</h4>";
        echo "<p>No se puede eliminar este cliente porque tiene registros asociados (cuentas, préstamos o transacciones) en el sistema.</p>";
        echo "<p><small>Detalle técnico: " . htmlspecialchars($e->getMessage()) . "</small></p>";
        echo "<br><a href='listar_clientes.php' style='text-decoration: none; background: #0E1B48; color: white; padding: 8px 15px; border-radius: 5px;'>Volver al Listado</a>";
        echo "</div>";
    }
} else {
    header("Location: listar_clientes.php");
    exit;
}
?>
