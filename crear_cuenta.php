<?php
// cajero_nueva_cuenta.php
session_start();
require_once 'conexionDB.php';

// Validar que el usuario tenga rol de cajero
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'cajero') {
    header("Location: login.php");
    exit;
}

$mensaje = '';
$error = '';

// Obtener la lista de clientes para seleccionarlos en el formulario
try {
    $stmt_cli = $conexion->query("SELECT id_cliente, nomb_cli, apelli_cli FROM clientes ORDER BY nomb_cli");
    $clientes = $stmt_cli->fetchAll(PDO::FETCH_ASSOC);

    $stmt_tipo = $conexion->query("SELECT cod_tipo_cuenta, nomb_tipo_cuenta FROM tipo_cuentas ORDER BY cod_tipo_cuenta");
    $tipos_cuenta = $stmt_tipo->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar datos iniciales: " . $e->getMessage();
}

// Procesar la creación de la cuenta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $num_cuenta      = trim($_POST['num_cuenta'] ?? '');
    $id_cliente      = trim($_POST['id_cliente'] ?? '');
    $cod_tipo_cuenta = trim($_POST['cod_tipo_cuenta'] ?? '');
    $saldo_inicial   = trim($_POST['saldo'] ?? 0.00);

    if (empty($num_cuenta) || empty($id_cliente) || empty($cod_tipo_cuenta)) {
        $error = "Por favor, complete todos los campos obligatorios.";
    } else {
        try {
            $stmt = $conexion->prepare("
                INSERT INTO cuentas (num_cuenta, saldo, fecha_apertura, id_cliente, cod_tipo_cuenta) 
                VALUES (?, ?, CURRENT_DATE, ?, ?)
            ");
            $stmt->execute([$num_cuenta, $saldo_inicial, $id_cliente, $cod_tipo_cuenta]);
            $mensaje = "¡Cuenta bancaria creada exitosamente para el cliente!";
        } catch (PDOException $e) {
            $error = "Error al crear la cuenta: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nueva Cuenta - Banco GO KYLIE GO</title>
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
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">💳 Apertura de Nueva Cuenta</h2>
                <p class="text-muted mb-0">Módulo de ventanilla para asociar cuentas a clientes registrados.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill">← Volver al Panel</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card card-custom p-4 bg-white col-lg-8 mx-auto">
            <form action="cajero_nueva_cuenta.php" method="POST">
                <div class="mb-3">
                    <label for="num_cuenta" class="form-label fw-bold">Número de Cuenta <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="num_cuenta" name="num_cuenta" required placeholder="Ej: AH-0007 o CO-0008">
                </div>

                <div class="mb-3">
                    <label for="id_cliente" class="form-label fw-bold">Seleccionar Cliente Titular <span class="text-danger">*</span></label>
                    <select class="form-select" id="id_cliente" name="id_cliente" required>
                        <option value="">-- Seleccione un cliente --</option>
                        <?php foreach ($clientes as $cli): ?>
                            <option value="<?php echo $cli['id_cliente']; ?>">
                                <?php echo htmlspecialchars($cli['nomb_cli'] . ' ' . $cli['apelli_cli'] . ' (ID: ' . $cli['id_cliente'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="cod_tipo_cuenta" class="form-label fw-bold">Tipo de Cuenta <span class="text-danger">*</span></label>
                        <select class="form-select" id="cod_tipo_cuenta" name="cod_tipo_cuenta" required>
                            <option value="">-- Seleccione tipo --</option>
                            <?php foreach ($tipos_cuenta as $tc): ?>
                                <option value="<?php echo $tc['cod_tipo_cuenta']; ?>">
                                    <?php echo htmlspecialchars($tc['nomb_tipo_cuenta']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="saldo" class="form-label fw-bold">Saldo Inicial ($)</label>
                        <input type="number" step="0.01" class="form-control" id="saldo" name="saldo" value="0.00" min="0" required>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill" style="background-color: var(--color-0e1b48); border: none;">Crear Cuenta Bancaria</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
