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
define('LIMITE_MAX_CLIENTE_WEB', 10000000.00); 
define('UMBRAL_DEPOSITO_CAJERO', 50000000.00);  

$cuentas = [];
$tipos = [];
$formas = [];
$todos_prestamos = [];

try {
    // 1. Cargar cuentas según el rol
    if ($rol === 'cliente' && $id_cliente) {
        $stmt = $conexion->prepare("SELECT num_cuenta, saldo, id_cliente FROM cuentas WHERE id_cliente = :cli ORDER BY num_cuenta");
        $stmt->execute([':cli' => $id_cliente]);
        $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Cliente no puede hacer depósitos directos web
        $stmtTipos = $conexion->prepare("SELECT * FROM tipo_transacciones WHERE LOWER(nomb_tipo_transaccion) NOT LIKE '%dep%' ORDER BY cod_tipo_transaccion");
        $stmtTipos->execute();
        $tipos = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $conexion->query("SELECT num_cuenta, saldo, id_cliente FROM cuentas ORDER BY num_cuenta");
        $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmtTipos = $conexion->query("SELECT * FROM tipo_transacciones ORDER BY cod_tipo_transaccion");
        $tipos = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // 2. Cargar formas de transacción
    $formas = $conexion->query("SELECT * FROM formas_transacciones ORDER BY cod_forma_transaccion")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Cargar TODOS los préstamos activos mapeados por cliente para usarlos dinámicamente en JavaScript
    $stmtPres = $conexion->query("SELECT id_prestamo, id_cliente, saldo_pendiente FROM prestamos WHERE saldo_pendiente > 0");
    $todos_prestamos = $stmtPres->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error al obtener datos: " . $e->getMessage();
}

// Procesar transacción
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $num_cuenta = $_POST['num_cuenta_origen'] ?? '';
    $monto = floatval($_POST['monto_transaccion'] ?? 0);
    $cod_tipo = intval($_POST['cod_tipo_transaccion'] ?? 1);
    $cod_forma = intval($_POST['cod_forma_transaccion'] ?? 1);
    $id_prestamo_abono = $_POST['id_prestamo'] ?? null;

    $stmtNombreTipo = $conexion->prepare("SELECT nomb_tipo_transaccion FROM tipo_transacciones WHERE cod_tipo_transaccion = :tipo");
    $stmtNombreTipo->execute([':tipo' => $cod_tipo]);
    $tipoRow = $stmtNombreTipo->fetch(PDO::FETCH_ASSOC);
    $nombreTipo = strtolower($tipoRow['nomb_tipo_transaccion'] ?? '');

    $esDeposito = (strpos($nombreTipo, 'dep') !== false || $cod_tipo == 1);
    $esRetiro = (strpos($nombreTipo, 'retiro') !== false || $cod_tipo == 2);
    $esAbonoPrestamo = (strpos($nombreTipo, 'abono') !== false || strpos($nombreTipo, 'préstamo') !== false || $cod_tipo == 3);

    if ($monto <= 0) {
        $error = "El monto ingresado debe ser mayor a cero ($0.00).";
    } elseif ($rol === 'cliente' && $esDeposito) {
        $error = "Protocolo de Seguridad: Los clientes no pueden realizar depósitos directos en la banca web.";
    } elseif ($rol === 'cliente' && $monto > LIMITE_MAX_CLIENTE_WEB) {
        $error = "El monto máximo permitido por transacción en la banca virtual es de $" . number_format(LIMITE_MAX_CLIENTE_WEB, 2) . " COP.";
    } elseif ($esAbonoPrestamo && empty($id_prestamo_abono)) {
        $error = "Debe seleccionar el préstamo al cual desea realizar el abono.";
    } else {
        try {
            $stmtCuenta = $conexion->prepare("SELECT saldo, id_cliente FROM cuentas WHERE num_cuenta = :cuenta");
            $stmtCuenta->execute([':cuenta' => $num_cuenta]);
            $cuentaData = $stmtCuenta->fetch(PDO::FETCH_ASSOC);

            if (!$cuentaData) {
                $error = "La cuenta seleccionada no existe.";
            } else {
                $saldoActual = floatval($cuentaData['saldo']);
                $idClienteCuenta = $cuentaData['id_cliente'];

                $prestamoData = null;
                if ($esAbonoPrestamo) {
                    $stmtPCheck = $conexion->prepare("SELECT * FROM prestamos WHERE id_prestamo = :pres AND id_cliente = :cli");
                    $stmtPCheck->execute([':pres' => $id_prestamo_abono, ':cli' => $idClienteCuenta]);
                    $prestamoData = $stmtPCheck->fetch(PDO::FETCH_ASSOC);

                    if (!$prestamoData) {
                        $error = "El préstamo seleccionado no pertenece al titular de la cuenta o no existe.";
                    } elseif ($monto > $saldoActual) {
                        $error = "Fondos insuficientes en la cuenta para realizar el abono. Saldo disponible: $" . number_format($saldoActual, 2);
                    } elseif ($monto > floatval($prestamoData['saldo_pendiente'])) {
                        $error = "El monto del abono supera el saldo pendiente del préstamo ($" . number_format($prestamoData['saldo_pendiente'], 2) . ").";
                    }
                }

                if (!$error && $esRetiro && $monto > $saldoActual) {
                    $error = "Fondos insuficientes. Saldo disponible: $" . number_format($saldoActual, 2);
                } elseif (!$error) {
                    if ($esRetiro || $esAbonoPrestamo) {
                        $nuevoSaldoCuenta = $saldoActual - $monto;
                    } else {
                        $nuevoSaldoCuenta = $saldoActual + $monto;
                    }

                    $id_cajero = ($cod_forma == 3) ? null : 1;
                    $id_banca_virtual = ($cod_forma == 3) ? 1 : null;

                    $conexion->beginTransaction();

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

                    $stmtUpd = $conexion->prepare("UPDATE cuentas SET saldo = :nuevo_saldo WHERE num_cuenta = :cuenta");
                    $stmtUpd->execute([
                        ':nuevo_saldo' => $nuevoSaldoCuenta,
                        ':cuenta'      => $num_cuenta
                    ]);

                    if ($esAbonoPrestamo && $prestamoData) {
                        $nuevoSaldoPrestamo = floatval($prestamoData['saldo_pendiente']) - $monto;
                        $stmtUpdPres = $conexion->prepare("UPDATE prestamos SET saldo_pendiente = :nuevo_saldo_pres WHERE id_prestamo = :pres");
                        $stmtUpdPres->execute([
                            ':nuevo_saldo_pres' => $nuevoSaldoPrestamo,
                            ':pres'             => $id_prestamo_abono
                        ]);
                    }

                    $conexion->commit();

                    if ($esAbonoPrestamo) {
                        $mensaje = "Abono al préstamo <strong>{$id_prestamo_abono}</strong> realizado con éxito. Nuevo saldo pendiente: <strong>$" . number_format($nuevoSaldoPrestamo, 2) . "</strong>.";
                    } else {
                        $accionTexto = $esRetiro ? "Retiro realizado exitosamente" : "Depósito procesado en ventanilla";
                        $mensaje = "{$accionTexto}. El nuevo saldo de la cuenta <strong>{$num_cuenta}</strong> es: <strong>$" . number_format($nuevoSaldoCuenta, 2) . "</strong>.";
                    }

                    // Recargar cuentas
                    if ($rol === 'cliente' && $id_cliente) {
                        $stmt = $conexion->prepare("SELECT num_cuenta, saldo, id_cliente FROM cuentas WHERE id_cliente = :cli ORDER BY num_cuenta");
                        $stmt->execute([':cli' => $id_cliente]);
                        $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } else {
                        $stmt = $conexion->query("SELECT num_cuenta, saldo, id_cliente FROM cuentas ORDER BY num_cuenta");
                        $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

                <!-- SELECT DE CUENTA ORIGEN CON EL EVENTO ONCHANGE -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Seleccione Cuenta Origen *</label>
                    <select name="num_cuenta_origen" id="num_cuenta_origen" class="form-select" required onchange="filtrarOpcionesPorCuenta(this)">
                        <option value="" disabled selected>-- Seleccione una cuenta --</option>
                        <?php foreach ($cuentas as $cta): ?>
                            <option value="<?php echo htmlspecialchars($cta['num_cuenta']); ?>" data-cliente="<?php echo htmlspecialchars($cta['id_cliente']); ?>">
                                <?php echo htmlspecialchars($cta['num_cuenta']); ?> (Saldo: $<?php echo number_format($cta['saldo'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- SELECT DE TIPO DE TRANSACCIÓN (DINÁMICO) -->
                <div class="mb-3">
                    <label class="form-label fw-bold">Tipo de Transacción *</label>
                    <select name="cod_tipo_transaccion" id="cod_tipo_transaccion" class="form-select" required onchange="verificarTipoTransaccion(this)">
                        <option value="" disabled selected>-- Seleccione tipo de transaccion --</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?php echo $t['cod_tipo_transaccion']; ?>" data-nombre="<?php echo strtolower($t['nomb_tipo_transaccion']); ?>">
                                <?php echo htmlspecialchars($t['nomb_tipo_transaccion']); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="3" data-nombre="abono a préstamo" id="opcion_abono_prestamo" class="d-none">Abono a Préstamo</option>
                    </select>
                </div>

                <!-- SECCIÓN DINÁMICA: SELECCIÓN DE PRÉSTAMO -->
                <div class="mb-3 d-none" id="seccion_prestamo">
                    <label class="form-label fw-bold text-primary">Seleccione el Préstamo a Abonar *</label>
                    <select name="id_prestamo" id="id_prestamo" class="form-select">
                        <option value="" disabled selected>-- Seleccione un préstamo activo --</option>
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

    <!-- SCRIPT JS PARA LA SINCRONIZACIÓN AUTOMÁTICA -->
    <script>
        // Mapeo seguro de préstamos por ID de cliente desde PHP
        const prestamosPorCliente = <?php echo json_encode($todos_prestamos); ?>;

        function filtrarOpcionesPorCuenta(selectCuenta) {
            const optionSeleccionada = selectCuenta.options[selectCuenta.selectedIndex];
            const idCliente = optionSeleccionada.getAttribute('data-cliente');
            
            const opcionAbono = document.getElementById('opcion_abono_prestamo');
            const selectPrestamo = document.getElementById('id_prestamo');
            const seccionPrestamo = document.getElementById('seccion_prestamo');
            const selectTipo = document.getElementById('cod_tipo_transaccion');

            // Filtrar los préstamos que pertenecen al cliente de esta cuenta
            const prestamosDelCliente = prestamosPorCliente.filter(p => p.id_cliente === idCliente);

            // Resetear sección de préstamo y tipo al cambiar de cuenta
            selectPrestamo.innerHTML = '<option value="" disabled selected>-- Seleccione un préstamo activo --</option>';
            seccionPrestamo.classList.add('d-none');
            selectPrestamo.removeAttribute('required');

            if (prestamosDelCliente.length > 0) {
                // Mostrar la opción de Abono a Préstamo en el select de tipos
                opcionAbono.classList.remove('d-none');

                // Poblar el select de préstamos de este cliente específico
                prestamosDelCliente.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id_prestamo;
                    opt.textContent = `Préstamo #${p.id_prestamo} (Saldo Pendiente: $${Number(p.saldo_pendiente).toLocaleString('es-CO', {minimumFractionDigits: 2})})`;
                    selectPrestamo.appendChild(opt);
                });
            } else {
                // Ocultar la opción si el dueño de esta cuenta no tiene préstamos
                opcionAbono.classList.add('d-none');
                if (selectTipo.value === '3') {
                    selectTipo.value = ''; // Desseleccionar si estaba marcado
                }
            }
        }

        function verificarTipoTransaccion(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const nombreTipo = selectedOption.getAttribute('data-nombre') || '';
            const codTipo = selectElement.value;
            const seccionPrestamo = document.getElementById('seccion_prestamo');
            const selectPrestamo = document.getElementById('id_prestamo');

            if (codTipo === '3' || nombreTipo.includes('abono') || nombreTipo.includes('préstamo')) {
                seccionPrestamo.classList.remove('d-none');
                selectPrestamo.setAttribute('required', 'true');
            } else {
                seccionPrestamo.classList.add('d-none');
                selectPrestamo.removeAttribute('required');
                selectPrestamo.value = '';
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
