<?php
session_start();
require_once 'conexionDB.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

try {
    $stmt = $pdo->query("SELECT * FROM bitacora_transacciones ORDER BY fecha_operacion DESC LIMIT 50");
    $bitacora = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al consultar la bitácora: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría y Bitácora - Banco GO KYLIE GO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: #0E1B48;">📋 Bitácora de Auditoría del Sistema</h2>
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
                            <th>ID Bitácora</th>
                            <th>Usuario BD</th>
                            <th>Fecha y Hora</th>
                            <th>Operación</th>
                            <th>ID Transacción</th>
                            <th>Detalle JSON</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bitacora)): ?>
                            <?php foreach ($bitacora as $row): ?>
                                <tr>
                                    <td>#<?php echo $row['id_bitacora']; ?></td>
                                    <td><code><?php echo htmlspecialchars($row['usuario_bd']); ?></code></td>
                                    <td><?php echo $row['fecha_operacion']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($row['operacion'] === 'INSERT') ? 'success' : (($row['operacion'] === 'UPDATE') ? 'warning text-dark' : 'danger'); ?>">
                                            <?php echo $row['operacion']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['id_transaccion'] ?? 'N/A'; ?></td>
                                    <td><small class="text-muted font-monospace"><?php echo htmlspecialchars($row['detalle']); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No hay registros en la bitácora todavía.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
