<?php
// estado_cuentas.php
session_start();
require_once 'conexionDB.php';

// 1. Validar que exista sesión (permite la entrada sin sacarte al login)
if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$rol = strtolower(trim($_SESSION['rol']));
$error = "";
$cuentas_clientes = [];
$total_banco = 0;

try {
    if ($rol === 'cliente') {
        // Si es cliente, muestra únicamente sus propias cuentas
        $stmt = $conexion->prepare("
            SELECT c.num_cuenta, c.saldo, c.fecha_apertura, cli.id_cliente, cli.nomb_cli, cli.apelli_cli, cli.correo, tc.nomb_tipo_cuenta
            FROM cuentas c
            JOIN clientes cli ON c.id_cliente = cli.id_cliente
            JOIN tipo_cuentas tc ON c.cod_tipo_cuenta = tc.cod_tipo_cuenta
            WHERE c.id_cliente = :id_cliente
            ORDER BY c.fecha_apertura DESC
        ");
        $stmt->execute([':id_cliente' => $_SESSION['id_cliente']]);
        $cuentas_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {
        // Si es administrador o cajero, muestra la lista global de todas las cuentas
        $stmt = $conexion->query("
            SELECT c.num_cuenta, c.saldo, c.fecha_apertura, cli.id_cliente, cli.nomb_cli, cli.apelli_cli, cli.correo, tc.nomb_tipo_cuenta
            FROM cuentas c
            JOIN clientes cli ON c.id_cliente = cli.id_cliente
            JOIN tipo_cuentas tc ON c.cod_tipo_cuenta = tc.cod_tipo_cuenta
            ORDER BY c.fecha_apertura DESC
        ");
        $cuentas_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular el capital total del banco (solo para el administrador)
        $stmt_total = $conexion->query("SELECT SUM(saldo) AS total_banco FROM cuentas");
        $total_banco = $stmt_total->fetch(PDO::FETCH_ASSOC)['total_banco'] ?? 0;
    }
} catch (PDOException $e) {
    $error = "Error al consultar las cuentas: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($rol === 'cliente') ? 'Mis Cuentas - Banco Villavicencio' : 'Estado de Cuentas Global - Banco Villavicencio'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-0e1b48: #0E1B48;
            --color-27425d: #27425D;
        }
        body { background-color: #f4f6f9; font-family: sans-serif; }
        .card-custom { border-radius: 16px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">
                    <?php echo ($rol === 'cliente') ? 'Mis Cuentas Bancarias y Saldos' : 'Estado de Cuentas y Saldos Globales'; ?>
                </h2>
                <p class="text-muted mb-0">
                    <?php echo ($rol === 'cliente') ? 'Consulta el estado actual de tus productos financieros.' : 'Supervisión general de cuentas, titulares y capital disponible en el banco.'; ?>
                </p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill">← Volver al Panel</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Tarjeta de Resumen Total (Visible solo para Administrador) -->
        <?php if ($rol === 'administrador'): ?>
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card card-custom bg-white p-4 border-start border-4 border-primary">
                        <span class="text-muted small text-uppercase fw-bold">Capital Total en Cuentas</span>
                        <h3 class="fw-bold text-dark mt-2">$<?php echo number_format($total_banco, 2, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabla de Cuentas -->
        <div class="card card-custom p-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Nº Cuenta</th>
                            <?php if ($rol !== 'cliente'): ?>
                                <th>Titular</th>
                                <th>Cédula / ID</th>
                            <?php endif; ?>
                            <th>Tipo de Cuenta</th>
                            <th>Fecha Apertura</th>
                            <th>Saldo Actual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($cuentas_clientes)): ?>
                            <?php foreach ($cuentas_clientes as $cta): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($cta['num_cuenta']); ?></code></td>
                                    <?php if ($rol !== 'cliente'): ?>
                                        <td><strong><?php echo htmlspecialchars($cta['nomb_cli'] . ' ' . $cta['apelli_cli']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($cta['id_cliente']); ?></td>
                                    <?php endif; ?>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($cta['nomb_tipo_cuenta']); ?></span></td>
                                    <td><?php echo htmlspecialchars($cta['fecha_apertura']); ?></td>
                                    <td class="fw-bold text-success">$<?php echo number_format($cta['saldo'], 2, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="<?php echo ($rol === 'cliente') ? '4' : '6'; ?>" class="text-center text-muted py-4">No hay cuentas registradas en este perfil.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
