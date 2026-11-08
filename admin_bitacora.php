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
            --color-c18db4: #C18DB4;
            --color-e2cad8: #E2CAD8;
            --color-87a7d0: #87A7D0;
            --color-27425d: #27425D;
            --color-0e1f2f: #0E1F2F;
        }
        body { 
            background: linear-gradient(135deg, #f8f9fa 0%, var(--color-e2cad8) 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            min-height: 100vh;
        }
        .card-custom { 
            border-radius: 16px; 
            border: none; 
            box-shadow: 0 8px 24px rgba(14, 27, 72, 0.08); 
        }
        .table-dark {
            background-color: var(--color-0e1b48);
        }
        .table-dark th {
            background-color: var(--color-0e1b48);
            color: #ffffff;
            border-color: var(--color-27425d);
        }
        .btn-custom {
            background-color: var(--color-c18db4);
            color: #ffffff;
            border-radius: 50rem;
            border: none;
            font-weight: 600;
        }
        .btn-custom:hover {
            background-color: var(--color-87a7d0);
            color: var(--color-0e1b48);
        }
        .text-brand {
            color: var(--color-0e1b48);
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container py-5 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-brand">Bitácora de Transacciones y Auditoría</h2>
                <p class="text-muted mb-0">Registro y monitoreo de operaciones de la base de datos en tiempo real.</p>
            </div>
            <a href="index.php" class="btn btn-custom px-4 py-2">← Volver al Panel</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm" style="background-color: #f8d7da; color: #842029;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4 p-4 bg-white card-custom">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
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
                                    <td><strong>#<?php echo $row['id_bitacora']; ?></strong></td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($row['fecha_operacion'])); ?></td>
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
                                    <td><code class="text-primary"><?php echo htmlspecialchars($row['usuario_bd']); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
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
