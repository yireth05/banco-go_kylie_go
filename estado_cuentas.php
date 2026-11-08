<?php
session_start();
require_once 'conexionDB.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: login.php");
    exit;
}

$cedula_cliente = $_SESSION['id_cliente'];
$nombre_cliente = $_SESSION['usuario'];

try {
    // Consultar las cuentas asociadas al cliente logueado
    $stmt = $pdo->prepare("
        SELECT c.num_cuenta, c.saldo, c.fecha_apertura, tc.nomb_tipo_cuenta 
        FROM cuentas c
        JOIN tipo_cuentas tc ON c.cod_tipo_cuenta = tc.cod_tipo_cuenta
        WHERE c.id_cliente = :cedula
    ");
    $stmt->execute(['cedula' => $cedula_cliente]);
    $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al obtener las cuentas: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Cuentas - Banco GO KYLIE GO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold" style="color: #0E1B48;">💳 Estado de mis Cuentas</h2>
                <p class="text-muted mb-0">Titular: <strong><?php echo htmlspecialchars($nombre_cliente); ?></strong> (Cédula: <?php echo $cedula_cliente; ?>)</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill">← Volver al Panel</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (!empty($cuentas)): ?>
                <?php foreach ($cuentas as $cta): ?>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 rounded-4 p-4 bg-white h-100">
                            <span class="badge bg-secondary rounded-pill align-self-start mb-2"><?php echo htmlspecialchars($cta['nomb_tipo_cuenta']); ?></span>
                            <h5 class="fw-bold text-dark mb-1">Cuenta: <?php echo htmlspecialchars($cta['num_cuenta']); ?></h5>
                            <p class="text-muted small mb-3">Fecha de apertura: <?php echo $cta['fecha_apertura']; ?></p>
                            <h3 class="fw-bold text-success mt-auto">$<?php echo number_format($cta['saldo'], 2, ',', '.'); ?></h3>
                            <span class="text-muted small">Saldo disponible</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center py-4">No tienes cuentas bancarias registradas actualmente a tu nombre.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
