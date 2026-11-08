<?php
// admin_usuarios.php
session_start();
require_once 'conexionDB.php';

// Validar que el usuario tenga rol de administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

try {
    // Consultar los roles y usuarios configurados en PostgreSQL usando la variable $conexion
    $stmt = $conexion->query("
        SELECT usename AS usuario, usesysid AS id, valuntil AS expira
        FROM pg_user 
        WHERE usename IN ('administrador', 'cajero', 'cliente', 'postgres')
        ORDER BY usename
    ");
    $usuarios_bd = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al consultar los usuarios de la base de datos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Banco GO KYLIE GO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-0e1b48: #0E1B48;
            --color-27425d: #27425D;
        }
        body { background-color: #f4f6f9; font-family: sans-serif; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">👥 Gestión de Usuarios y Roles</h2>
                <p class="text-muted mb-0">Control de perfiles de acceso definidos en PostgreSQL.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill">← Volver al Panel</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Usuario / Rol</th>
                            <th>ID de Sistema</th>
                            <th>Estado</th>
                            <th>Grupo / Permisos Asignados</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($usuarios_bd)): ?>
                            <?php foreach ($usuarios_bd as $usr): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($usr['usuario']); ?></strong></td>
                                    <td><code><?php echo $usr['id']; ?></code></td>
                                    <td><span class="badge bg-success">Activo</span></td>
                                    <td>
                                        <?php 
                                            if ($usr['usuario'] === 'administrador') echo 'grup_admins (Acceso total)';
                                            elseif ($usr['usuario'] === 'cajero') echo 'grup_cajeros (Ventanilla y Operaciones)';
                                            elseif ($usr['usuario'] === 'cliente') echo 'grup_clientes (Portal propio)';
                                            else echo 'Superusuario General';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No se encontraron usuarios en la base de datos.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
