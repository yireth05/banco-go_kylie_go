<?php
session_start();
require_once 'conexionDB.php';

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$id_cliente = $_SESSION['usuario_id'] ?? $_SESSION['id_cliente'] ?? null;
$movimientos = [];
$error = "";

try {
    if ($rol === 'cliente' && $id_cliente) {
        $stmt = $conexion->prepare("
            SELECT t.fecha_transaccion, t.monto_transaccion, t.num_cuenta_origen, 
                   tt.nomb_tipo_transaccion, ft.nomb_forma_transaccion,
                   cl.nomb_cli, cl.apelli_cli
            FROM transacciones t
            JOIN tipo_transacciones tt ON t.cod_tipo_transaccion = tt.cod_tipo_transaccion
            JOIN formas_transacciones ft ON t.cod_forma_transaccion = ft.cod_forma_transaccion
            JOIN cuentas cu ON t.num_cuenta_origen = cu.num_cuenta
            JOIN clientes cl ON cu.id_cliente = cl.id_cliente
            WHERE cu.id_cliente = :cli
            ORDER BY t.fecha_transaccion DESC
        ");
        $stmt->execute([':cli' => $id_cliente]);
    } else {

        $stmt = $conexion->query("
            SELECT t.fecha_transaccion, t.monto_transaccion, t.num_cuenta_origen, 
                   tt.nomb_tipo_transaccion, ft.nomb_forma_transaccion,
                   cl.nomb_cli, cl.apelli_cli
            FROM transacciones t
            JOIN tipo_transacciones tt ON t.cod_tipo_transaccion = tt.cod_tipo_transaccion
            JOIN formas_transacciones ft ON t.cod_forma_transaccion = ft.cod_forma_transaccion
            LEFT JOIN cuentas cu ON t.num_cuenta_origen = cu.num_cuenta
            LEFT JOIN clientes cl ON cu.id_cliente = cl.id_cliente
            ORDER BY t.fecha_transaccion DESC
        ");
    }
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al obtener el historial: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Movimientos - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-0e1b48: #0E1B48; --color-c18db4: #C18DB4;
            --color-e2cad8: #E2CAD8; --color-87a7d0: #87A7D0;
            --color-27425d: #27425D;
        }
        body { background: linear-gradient(135deg, #f8f9fa 0%, var(--color-e2cad8) 100%); min-height: 100vh; }
        .card-custom { border-radius: 16px; border: none; box-shadow: 0 8px 24px rgba(14, 27, 72, 0.08); }
        .table-dark { background-color: var(--color-0e1b48); }
        .table-dark th { background-color: var(--color-0e1b48); color: #fff; }
        .btn-custom { background-color: var(--color-c18db4); color: #fff; border-radius: 50rem; font-weight: 600; }
        .btn-custom:hover { background-color: var(--color-87a7d0); color: var(--color-0e1b48); }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container py-5 mt-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">Historial de Transferencias y Movimientos</h2>
                <p class="text-muted mb-0">Consulta detallada de las operaciones y transferencias realizadas.</p>
            </div>
            <a href="index.php" class="btn btn-custom px-4 py-2">← Volver al Panel</a>
        </div>

        <?php if ($error): ?><div class="alert alert-danger rounded-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="card card-custom p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha y Hora</th>
                            <th>Tipo de Movimiento</th>
                            <th>Forma</th>
                            <th>N° Cuenta Origen</th>
                            <?php if ($rol !== 'cliente'): ?><th>Cliente / Titular</th><?php endif; ?>
                            <th>Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($movimientos) > 0): ?>
                            <?php foreach ($movimientos as $mov): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($mov['fecha_transaccion'])); ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <?php echo htmlspecialchars($mov['nomb_tipo_transaccion']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($mov['nomb_forma_transaccion']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($mov['num_cuenta_origen']); ?></strong></td>
                                    <?php if ($rol !== 'cliente'): ?>
                                        <td><?php echo htmlspecialchars(($mov['nomb_cli'] ?? 'N/A') . ' ' . ($mov['apelli_cli'] ?? '')); ?></td>
                                    <?php endif; ?>
                                    <td class="fw-bold text-success">$<?php echo number_format($mov['monto_transaccion'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No hay transacciones ni movimientos registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
