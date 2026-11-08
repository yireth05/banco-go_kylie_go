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

// Definición de límites del protocolo bancario
define('LIMITE_MAX_CLIENTE_WEB', 10000000.00); // Límite de 10 millones por transacción para clientes
define('UMBRAL_DEPOSITO_CAJERO', 50000000.00);  // Umbral de auditoría de fondos para depósitos altos

// Cargar cuentas
$cuentas = [];
try {
    if ($rol === 'cliente' && $id_cliente) {
        $stmt = $conexion->prepare("SELECT num_cuenta, saldo FROM cuentas WHERE id_cliente = :cli ORDER BY num_cuenta");
        $stmt->execute([':cli' => $id_cliente]);
        $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Para el cliente, restringir la lista de tipos de transacción (excluir Depósitos)
        $stmtTipos = $conexion->prepare("SELECT * FROM tipo_transacciones WHERE LOWER(nomb_tipo_transaccion) NOT LIKE '%dep%' ORDER BY cod_tipo_transaccion");
        $stmtTipos->execute();
        $tipos = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $cuentas = $conexion->query("SELECT num_cuenta, saldo FROM cuentas ORDER BY num_cuenta")->fetchAll(PDO::FETCH_ASSOC);
        $tipos = $conexion->query("SELECT * FROM tipo_transacciones ORDER BY cod_tipo_transaccion")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $formas = $conexion->query("SELECT * FROM formas_transacciones ORDER BY cod_forma_transaccion")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al obtener datos: " . $e->getMessage();
}

// Procesar transacción con protocolo estricto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $num_cuenta = $_POST['num_cuenta_origen'] ?? '';
    $monto = floatval($_POST['monto_transaccion'] ?? 0);
    $cod_tipo = intval($_POST['cod_tipo_transaccion'] ?? 1);
    $cod_forma = intval($_POST['cod_forma_transaccion'] ?? 1);

    // 1. Obtener la transacción a realizar
    $stmtNombreTipo = $conexion->prepare("SELECT nomb_tipo_transaccion FROM tipo_transacciones WHERE cod_tipo_transaccion = :tipo");
    $stmtNombreTipo->execute([':tipo' => $cod_tipo]);
    $tipoRow = $stmtNombreTipo->fetch(PDO::FETCH_ASSOC);
    $nombreTipo = strtolower($tipoRow['nomb_tipo_transaccion'] ?? '');

    $esDeposito = (strpos($nombreTipo, 'dep') !== false || $cod_tipo == 1);
    $esRetiro = (strpos($nombreTipo, 'retiro') !== false || $cod_tipo == 2);

    // 2. PROTOCOLO DE SEGURIDAD POR ROLES
    if ($monto <= 0) {
        $error = "El monto ingresado debe ser mayor a cero ($0.00).";
    } elseif ($rol === 'cliente' && $esDeposito) {
        // REGLA: Los clientes no pueden acreditarse dinero a sí mismos mediante depósitos directos
        $error = "Protocolo de Seguridad: Los clientes no pueden realizar depósitos directos en la banca web. Debe dirigirse a una sucursal con un cajero o solicitar una transferencia.";
    } elseif ($rol === 'cliente' && $monto > LIMITE_MAX_CLIENTE_WEB) {
        // REGLA: Límite por operación en banca virtual
        $error = "El monto máximo permitido por transacción en la banca virtual es de $" . number_format(LIMITE_MAX_CLIENTE_WEB, 2) . " COP.";
    } else {
        try {
            // Consultar saldo actual de la cuenta
            $stmtCuenta = $conexion->prepare("SELECT saldo FROM cuentas WHERE num_cuenta = :cuenta");
            $stmtCuenta->execute([':cuenta' => $num_cuenta]);
            $cuentaData = $stmtCuenta->fetch(PDO::FETCH_ASSOC);

            if (!$cuentaData) {
                $error = "La cuenta seleccionada no existe.";
            } else {
                $saldoActual = floatval($cuentaData['saldo']);

                // 3. PROTOCOLO DE RETIRO: Evitar números negativos
                if ($esRetiro && $monto > $saldoActual) {
                    $error = "Fondos insuficientes. El saldo disponible es de $" . number_format($saldoActual, 2) . " y el monto a retirar es de $" . number_format($monto, 2) . ".";
                } else {
                    $nuevoSaldo = $esRetiro ? ($saldoActual - $monto) : ($saldoActual + $monto);

                    $id_cajero = ($cod_forma == 3) ? null : 1;
                    $id_banca_virtual = ($cod_forma == 3) ? 1 : null;

                    $conexion->beginTransaction();

                    // Registrar transacción
                    $stmtIns = $conexion->prepare("
                        INSERT INTO transacciones (
                            fecha_transaccion, monto_transaccion, num_cuenta_origen, 
                            cod_tipo_transaccion, cod_forma_transaccion, id_cajero, id_banca_virtual
                        )
                        VALUES (NOW(), :monto, :cuenta, :tipo, :forma, :cajero, :banca)
                    ");
                    $stmtIns->execute([
                        ':monto'   => $monto,
                        ':cuenta'  => $num_cuenta,
                        ':tipo'    => $cod_tipo,
                        ':forma'   => $cod_forma,
                        ':cajero'  => $id_cajero,
                        ':banca'   => $id_banca_virtual
                    ]);

                    // Actualizar el saldo real de la cuenta
                    $stmtUpd = $conexion->prepare("UPDATE cuentas SET saldo = :nuevo_saldo WHERE num_cuenta = :cuenta");
                    $stmtUpd->execute([
                        ':nuevo_saldo' => $nuevoSaldo,
                        ':cuenta'      => $num_cuenta
                    ]);

                    $conexion->commit();

                    $alertaProtocolo = ($esDeposito && $monto >= UMBRAL_DEPOSITO_CAJERO) ? " (Operación registrada bajo auditoría de origen de fondos)" : "";
                    $accionTexto = $esRetiro ? "Retiro realizado exitosamente" : "Depósito procesado en ventanilla";
                    $mensaje = "{$accionTexto}{$alertaProtocolo}. El nuevo saldo de la cuenta <strong>{$num_cuenta}</strong> es: <strong>$" . number_format($nuevoSaldo, 2) . "</strong>.";

                    // Recargar cuentas
                    if ($rol === 'cliente' && $id_cliente) {
                        $stmt = $conexion->prepare("SELECT num_cuenta, saldo FROM cuentas WHERE id_cliente = :cli ORDER BY num_cuenta");
                        $stmt->execute([':cli' => $id_cliente]);
                        $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } else {
                        $cuentas = $conexion->query("SELECT num_cuenta, saldo FROM cuentas ORDER BY num_cuenta")->fetchAll(PDO::FETCH_ASSOC);
                    }
                }
            }
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            $error = "Error al procesar la operación: " . $e->getMessage();
        }
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
        }
        body { 
            background: linear-gradient(135deg, #f8f9fa 0%, var(--color-e2cad8) 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            min-height: 100vh;
        }
        .card-custom { border-radius: 16px; border: none; box-shadow: 0 8px 24px rgba(14, 27, 72, 0.08); }
        .btn-custom { background-color: var(--color-0e1b48); color: #ffffff; border-radius: 50rem; font-weight: 600; }
        .btn-custom:hover { background-color: var(--color-27425d); color: #ffffff; }
        .btn-back { background-color: var(--color-c18db4); color: #ffffff; border-radius: 50rem; border: none; font-weight: 600; }
        .btn-back:hover { background-color: var(--color-87a7d0); color: var(--color-0e1b48); }
    </style>
</head>
<body>
    <?php if (file_exists("navbar.php")) include_once "navbar.php"; ?>

    <div class="container py-5 mt-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">Módulo de Transacciones</h2>
                <p class="text-muted mb-0">Gestión segura de operaciones financieras según perfil de usuario.</p>
            </div>
            <a href="index.php" class="btn btn-back px-4 py-2">← Volver al Panel</a>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card card-custom p-4 bg-white col-lg-6 mx-auto">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">Titular / Operador</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['nombres'] ?? 'Usuario'); ?> (Rol: <?php echo strtoupper($rol); ?>)" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Seleccione Cuenta Origen *</label>
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
                    <?php if ($rol === 'cliente'): ?>
                        <div class="form-text text-muted">Límite máximo por operación web: $10.000.000 COP</div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-custom w-100 py-2">Procesar Transacción</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
