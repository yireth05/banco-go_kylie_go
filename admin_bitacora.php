<?php
// admin_bitacora.php
session_start();
require_once 'conexionDB.php';

// Validar que el usuario tenga rol de administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

try {
    // Consulta uniendo la bitácora con la tabla transacciones y el tipo de transacción
    $sql = "
        SELECT 
            b.id_bitacora,
            b.usuario_bd,
            b.fecha_operacion,
            b.operacion,
            t.id_transaccion,
            t.monto_transaccion,
            t.num_cuenta_origen,
            tp.nomb_tipo_transaccion
        FROM bitacora_transacciones b
        LEFT JOIN transacciones t ON b.id_transaccion = t.id_transaccion
        LEFT JOIN tipo_transacciones tp ON t.cod_tipo_transaccion = tp.cod_tipo_transaccion
        ORDER BY b.fecha_operacion DESC 
        LIMIT 50
    ";
    $stmt = $conexion->query($sql);
    $bitacora = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al consultar la bitácora: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría y Bitácora - Banco Villavicencio</title>
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
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">Bitácora de Transacciones</h2>
                <p class="text-muted mb-0">Registro y monitoreo de operaciones de transacciones en tiempo real.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill">Volver al Panel</a>
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
                            <th>Fecha y Hora Operación</th>
                            <th>Operación BD</th>
                            <th>Cuenta Origen</th>
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th>Usuario BD</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bitacora)): ?>
                            <?php foreach ($bitacora as $row): ?>
                                <tr>
                                    <td>#<?php echo $row['id_bitacora']; ?></td>
                                    <td><?php echo $row['fecha_operacion']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($row['operacion'] === 'INSERT') ? 'success' : (($row['operacion'] === 'UPDATE') ? 'warning text-dark' : 'danger'); ?>">
                                            <?php echo $row['operacion']; ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($row['num_cuenta_origen'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['nomb_tipo_transaccion'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (isset($row['monto_transaccion'])): ?>
                                            <span class="text-success fw-bold">$<?php echo number_format($row['monto_transaccion'], 2, ',', '.'); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($row['usuario_bd']); ?></code></td>
                                </tr>
                            <?endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No hay registros de transacciones en la bitácora todavía.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
