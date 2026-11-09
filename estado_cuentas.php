<?php
session_start();
require_once 'conexionDB.php';

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$id_cliente = $_SESSION['usuario_id'] ?? $_SESSION['id_cliente'] ?? null;
$cuentas = [];
$error = "";

try {
    if ($rol === 'cliente' && $id_cliente) {

        $stmt = $conexion->prepare("
            SELECT cu.num_cuenta, cu.saldo, cu.fecha_apertura, tc.nomb_tipo_cuenta, cl.id_cliente, cl.nomb_cli, cl.apelli_cli 
            FROM cuentas cu
            JOIN tipo_cuentas tc ON cu.cod_tipo_cuenta = tc.cod_tipo_cuenta
            JOIN clientes cl ON cu.id_cliente = cl.id_cliente
            WHERE cu.id_cliente = :cli
            ORDER BY cu.fecha_apertura DESC
        ");
        $stmt->execute([':cli' => $id_cliente]);
    } else {
   
        $stmt = $conexion->query("
            SELECT cu.num_cuenta, cu.saldo, cu.fecha_apertura, tc.nomb_tipo_cuenta, cl.id_cliente, cl.nomb_cli, cl.apelli_cli 
            FROM cuentas cu
            JOIN tipo_cuentas tc ON cu.cod_tipo_cuenta = tc.cod_tipo_cuenta
            JOIN clientes cl ON cu.id_cliente = cl.id_cliente
            ORDER BY cu.fecha_apertura DESC
        ");
    }
    $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al obtener las cuentas: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Cuentas y Saldos - Banco Villavicencio</title>
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
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">Mis Cuentas Bancarias y Saldos</h2>
                <p class="text-muted mb-0">Consulta el estado actual de tus cuentas y fondos disponibles.</p>
            </div>
            <a href="index.php" class="btn btn-custom px-4 py-2">← Volver al Panel</a>
        </div>

        <?php if ($error): ?><div class="alert alert-danger rounded-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <div class="card card-custom p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>N° Cuenta</th>
                            <th>Tipo de Cuenta</th>
                            <?php if ($rol !== 'cliente'): ?><th>Titular</th><?php endif; ?>
                            <th>Fecha Apertura</th>
                            <th>Saldo Actual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($cuentas) > 0): ?>
                            <?php foreach ($cuentas as $cuenta): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($cuenta['num_cuenta']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($cuenta['nomb_tipo_cuenta']); ?></td>
                                    <?php if ($rol !== 'cliente'): ?>
                                        <td><?php echo htmlspecialchars($cuenta['nomb_cli'] . ' ' . $cuenta['apelli_cli']); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo date('d/m/Y', strtotime($cuenta['fecha_apertura'])); ?></td>
                                    <td class="fw-bold text-success">$<?php echo number_format($cuenta['saldo'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No hay cuentas registradas en este perfil.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
