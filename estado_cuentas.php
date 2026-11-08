<?php
// historial_transacciones.php
session_start();
require_once "conexionDB.php";

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cliente') {
    header("Location: login.php");
    exit;
}

$id_cliente = $_SESSION['id_cliente'];
$cuenta_seleccionada = $_GET['num_cuenta'] ?? '';
$movimientos = [];
$error = "";

try {
    // 1. Obtener todas las cuentas del cliente para llenar el selector
    $stmt_cuentas = $conexion->prepare("
        SELECT c.num_cuenta, c.saldo, tc.nomb_tipo_cuenta 
        FROM cuentas c 
        JOIN tipo_cuentas tc ON c.cod_tipo_cuenta = tc.cod_tipo_cuenta 
        WHERE c.id_cliente = :id_cliente
        ORDER BY c.num_cuenta
    ");
    $stmt_cuentas->execute([':id_cliente' => $id_cliente]);
    $mis_cuentas = $stmt_cuentas->fetchAll(PDO::FETCH_ASSOC);

    // Si el cliente no seleccionó ninguna cuenta por defecto y tiene cuentas, tomamos la primera
    if (empty($cuenta_seleccionada) && !empty($mis_cuentas)) {
        $cuenta_seleccionada = $mis_cuentas[0]['num_cuenta'];
    }

    // 2. Si hay una cuenta seleccionada, consultamos su historial de transacciones
    if (!empty($cuenta_seleccionada)) {
        // Validamos primero que la cuenta pertenezca realmente al cliente logueado por seguridad
        $stmt_valida = $conexion->prepare("SELECT num_cuenta FROM cuentas WHERE num_cuenta = :num AND id_cliente = :id");
        $stmt_valida->execute([':num' => $cuenta_seleccionada, ':id' => $id_cliente]);
        
        if ($stmt_valida->fetch()) {
            // Consultar transacciones de esta cuenta (origen o involucradas)
            $stmt_historial = $conexion->prepare("
                SELECT 
                    t.num_transaccion,
                    t.fecha_transaccion,
                    tt.nomb_tipo_transaccion,
                    ft.nomb_forma_transaccion,
                    t.monto_transaccion,
                    t.num_cuenta_origen
                FROM transacciones t
                JOIN tipo_transacciones tt ON t.cod_tipo_transaccion = tt.cod_tipo_transaccion
                JOIN formas_transacciones ft ON t.cod_forma_transaccion = ft.cod_forma_transaccion
                WHERE t.num_cuenta_origen = :cuenta
                ORDER BY t.fecha_transaccion DESC
            ");
            $stmt_historial->execute([':cuenta' => $cuenta_seleccionada]);
            $movimientos = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "La cuenta seleccionada no es válida o no le pertenece.";
        }
    }

} catch (Exception $e) {
    $error = "Error al cargar el historial: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Transacciones - Banco Villavicencio</title>
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
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">Historial de Transacciones</h2>
                <p class="text-muted mb-0">Selecciona una de tus cuentas para consultar el detalle de tus movimientos.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill">← Volver al Panel</a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Selector de Cuenta -->
        <div class="card card-custom p-4 bg-white mb-4">
            <form method="GET" action="" class="row align-items-end">
                <div class="col-md-8">
                    <label class="form-label fw-bold text-dark">Seleccione la Cuenta a Consultar:</label>
                    <select name="num_cuenta" class="form-select form-select-lg" onchange="this.form.submit()">
                        <?php foreach ($mis_cuentas as $cta): ?>
                            <option value="<?php echo htmlspecialchars($cta['num_cuenta']); ?>" <?php echo ($cuenta_seleccionada == $cta['num_cuenta']) ? 'selected' : ''; ?>>
                                Cuenta Nº: <?php echo htmlspecialchars($cta['num_cuenta']); ?> (<?php echo htmlspecialchars($cta['nomb_tipo_cuenta']); ?>) - Saldo: $<?php echo number_format($cta['saldo'], 2, ',', '.'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <button type="submit" class="btn btn-dark w-100 py-2 rounded-pill" style="background-color: #0E1B48;">Consultar Movimientos</button>
                </div>
            </form>
        </div>

        <!-- Tabla de Movimientos / Historial -->
        <div class="card card-custom p-4 bg-white">
            <h5 class="fw-bold mb-3 text-secondary">Movimientos de la cuenta: <span class="text-dark"><?php echo htmlspecialchars($cuenta_seleccionada); ?></span></h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Nº Transacción</th>
                            <th>Fecha y Hora</th>
                            <th>Tipo de Transacción</th>
                            <th>Forma de Pago</th>
                            <th>Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($movimientos)): ?>
                            <?php foreach ($movimientos as $mov): ?>
                                <tr>
                                    <td><code>#<?php echo htmlspecialchars($mov['num_transaccion']); ?></code></td>
                                    <td><?php echo htmlspecialchars($mov['fecha_transaccion']); ?></td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($mov['nomb_tipo_transaccion']); ?></span></td>
                                    <td><?php echo htmlspecialchars($mov['nomb_forma_transaccion']); ?></td>
                                    <td class="fw-bold text-success">
                                        +$<?php echo number_format($mov['monto_transaccion'], 2, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No hay transacciones registradas para esta cuenta.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
