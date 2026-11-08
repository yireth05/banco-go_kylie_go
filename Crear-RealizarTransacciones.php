<?php
// Crear-RealizarTransacciones.php
session_start();
require_once 'conexionDB.php';

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$id_cliente = $_SESSION['usuario_id'] ?? $_SESSION['id_cliente'] ?? null;
$mensaje = "";
$error = "";

// Cargar cuentas pertenecientes al cliente o todas si es cajero/admin
$cuentas = [];
try {
    if ($rol === 'cliente' && $id_cliente) {
        $stmt = $conexion->prepare("SELECT num_cuenta, saldo FROM cuentas WHERE id_cliente = :cli ORDER BY num_cuenta");
        $stmt->execute([':cli' => $id_cliente]);
        $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $cuentas = $conexion->query("SELECT num_cuenta, saldo FROM cuentas ORDER BY num_cuenta")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $tipos = $conexion->query("SELECT * FROM tipo_transacciones ORDER BY cod_tipo_transaccion")->fetchAll(PDO::FETCH_ASSOC);
    $formas = $conexion->query("SELECT * FROM formas_transacciones ORDER BY cod_forma_transaccion")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al obtener datos: " . $e->getMessage();
}

// Procesar transacción
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $num_cuenta = $_POST['num_cuenta_origen'] ?? '';
    $monto = floatval($_POST['monto_transaccion'] ?? 0);
    $cod_tipo = intval($_POST['cod_tipo_transaccion'] ?? 1);
    $cod_forma = intval($_POST['cod_forma_transaccion'] ?? 1);

    // Asignación de canal para cumplir con el CHECK constraint ck_transacciones_origen
    // Si la transacción es por Banca Virtual (cod_forma = 3 o transferencia web) asigna id_banca_virtual
    // Si es por Efectivo/Cajero (cod_forma = 1 o 2) asigna id_cajero = 1
    $id_cajero = ($cod_forma == 3) ? null : 1;
    $id_banca_virtual = ($cod_forma == 3) ? 1 : null;

    try {
        $stmt = $conexion->prepare("
            INSERT INTO transacciones (
                fecha_transaccion, 
                monto_transaccion, 
                num_cuenta_origen, 
                cod_tipo_transaccion, 
                cod_forma_transaccion, 
                id_cajero, 
                id_banca_virtual
            )
            VALUES (NOW(), :monto, :cuenta, :tipo, :forma, :cajero, :banca)
        ");
        $stmt->execute([
            ':monto'   => $monto,
            ':cuenta'  => $num_cuenta,
            ':tipo'    => $cod_tipo,
            ':forma'   => $cod_forma,
            ':cajero'  => $id_cajero,
            ':banca'   => $id_banca_virtual
        ]);
        $mensaje = "Transacción realizada con éxito.";

        // Recargar las cuentas para actualizar el saldo visible
        if ($rol === 'cliente' && $id_cliente) {
            $stmt = $conexion->prepare("SELECT num_cuenta, saldo FROM cuentas WHERE id_cliente = :cli ORDER BY num_cuenta");
            $stmt->execute([':cli' => $id_cliente]);
            $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error = "Error en la transacción: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Transacciones - Banco Villavicencio</title>
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
        .btn-custom { 
            background-color: var(--color-0e1b48); 
            color: #ffffff; 
            border-radius: 50rem; 
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-custom:hover { 
            background-color: var(--color-27425d); 
            color: #ffffff; 
        }
        .btn-back {
            background-color: var(--color-c18db4);
            color: #ffffff;
            border-radius: 50rem;
            border: none;
            font-weight: 600;
        }
        .btn-back:hover {
            background-color: var(--color-87a7d0);
            color: var(--color-0e1b48);
        }
    </style>
</head>
<body>
    <?php if (file_exists("navbar.php")) include_once "navbar.php"; ?>

    <div class="container py-5 mt-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">Módulo de Transacciones</h2>
                <p class="text-muted mb-0">Realiza depósitos, retiros o transferencias en tu cuenta.</p>
            </div>
            <a href="index.php" class="btn btn-back px-4 py-2">← Volver al Panel</a>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card card-custom p-4 bg-white col-lg-6 mx-auto">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Titular de la Cuenta</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['nombres'] ?? 'Cliente'); ?> (ID: <?php echo htmlspecialchars($id_cliente); ?>)" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Seleccione su Cuenta *</label>
                    <select name="num_cuenta_origen" class="form-select" required>
                        <option value="" disabled selected>-- Seleccione una cuenta --</option>
                        <?php foreach ($cuentas as $cta): ?>
                            <option value="<?php echo htmlspecialchars($cta['num_cuenta']); ?>">
                                <?php echo htmlspecialchars($cta['num_cuenta']); ?> (Saldo: $<?php echo number_format($cta['saldo'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Tipo de Transacción *</label>
                    <select name="cod_tipo_transaccion" class="form-select" required>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?php echo $t['cod_tipo_transaccion']; ?>"><?php echo htmlspecialchars($t['nomb_tipo_transaccion']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Forma de Transacción *</label>
                    <select name="cod_forma_transaccion" class="form-select" required>
                        <?php foreach ($formas as $f): ?>
                            <option value="<?php echo $f['cod_forma_transaccion']; ?>"><?php echo htmlspecialchars($f['nomb_forma_transaccion']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">Monto ($) *</label>
                    <input type="number" step="0.01" min="1" name="monto_transaccion" class="form-control" placeholder="0.00" required>
                </div>
                <button type="submit" class="btn btn-custom w-100 py-2">Procesar Transacción</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
