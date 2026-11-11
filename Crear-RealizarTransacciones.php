<?php
// Crear-RealizarTransacciones.php
session_start();
require_once "conexionDB.php";

$mensaje = "";
$error = "";

// 1. Obtener cuentas del banco con su respectivo cliente y verificar si tienen préstamos
try {
    $stmtCuentas = $conexion->query("
        SELECT c.num_cuenta, c.tipo_cuenta, c.saldo, cl.id_cliente, cl.nomb_cli, cl.apelli_cli 
        FROM cuentas c 
        JOIN clientes cl ON c.id_cliente = cl.id_cliente 
        ORDER BY c.num_cuenta ASC
    ");
    $cuentas = $stmtCuentas->fetchAll(PDO::FETCH_ASSOC);

    // Obtener todos los préstamos existentes para asociarlos
    $stmtPrestamos = $conexion->query("SELECT * FROM prestamos ORDER BY id_prestamo ASC");
    $prestamos = $stmtPrestamos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
    $cuentas = [];
    $prestamos = [];
}

// 2. Procesar la transacción cuando se envía el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $num_cuenta   = trim($_POST['num_cuenta'] ?? '');
    $tipo_trans   = trim($_POST['tipo_transaccion'] ?? '');
    $monto        = floatval($_POST['monto'] ?? 0);
    $id_prestamo  = trim($_POST['id_prestamo'] ?? '');

    if ($num_cuenta === '' || $tipo_trans === '' || $monto <= 0) {
        $error = "Por favor completa todos los campos obligatorios correctamente.";
    } else {
        try {
            $conexion->beginTransaction();

            // Verificar saldo y cuenta origen
            $stmtCta = $conexion->prepare("SELECT saldo, id_cliente FROM cuentas WHERE num_cuenta = ?");
            $stmtCta->execute([$num_cuenta]);
            $cuentaData = $stmtCta->fetch(PDO::FETCH_ASSOC);

            if (!$cuentaData) {
                throw new Exception("La cuenta seleccionada no existe.");
            }

            $saldoActual = floatval($cuentaData['saldo']);

            if ($tipo_trans === 'retiro' && $saldoActual < $monto) {
                throw new Exception("Saldo insuficiente para realizar el retiro.");
            }

            // Calcular nuevo saldo en cuenta según el tipo de transacción
            if ($tipo_trans === 'deposito') {
                $nuevoSaldo = $saldoActual + $monto;
                $updCta = $conexion->prepare("UPDATE cuentas SET saldo = ? WHERE num_cuenta = ?");
                $updCta->execute([$nuevoSaldo, $num_cuenta]);
            } elseif ($tipo_trans === 'retiro') {
                $nuevoSaldo = $saldoActual - $monto;
                $updCta = $conexion->prepare("UPDATE cuentas SET saldo = ? WHERE num_cuenta = ?");
                $updCta->execute([$nuevoSaldo, $num_cuenta]);
            } elseif ($tipo_trans === 'abono_prestamo') {
                if ($id_prestamo === '') {
                    throw new Exception("Debe seleccionar un préstamo válido para abonar.");
                }

                if ($saldoActual < $monto) {
                    throw new Exception("Saldo insuficiente en la cuenta para realizar el abono al préstamo.");
                }

                // Descontar de la cuenta
                $nuevoSaldo = $saldoActual - $monto;
                $updCta = $conexion->prepare("UPDATE cuentas SET saldo = ? WHERE num_cuenta = ?");
                $updCta->execute([$nuevoSaldo, $num_cuenta]);

                // Disminuir el valor del préstamo en la base de datos
                $updPrestamo = $conexion->prepare("UPDATE prestamos SET valor_prestamo = valor_prestamo - ? WHERE id_prestamo = ?");
                $updPrestamo->execute([$monto, $id_prestamo]);
            }

            // Registrar en el historial de transacciones (si aplica la tabla)
            $stmtIns = $conexion->prepare("
                INSERT INTO transacciones (num_cuenta, tipo_transaccion, monto, fecha_transaccion) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmtIns->execute([$num_cuenta, $tipo_trans, $monto]);

            $conexion->commit();
            $mensaje = "Transacción procesada con éxito.";
        } catch (Exception $e) {
            $conexion->rollBack();
            $error = "Error en la transacción: " . $e->getMessage();
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
    <script>
        // Mapeo de préstamos por cliente para mostrar dinámicamente en el selector
        const prestamosPorCliente = {
            <?php 
            // Agrupar préstamos por id_cliente para filtrado en JavaScript
            $prestamosCliente = [];
            foreach($prestamos as $p) {
                // Asumiendo que la tabla prestamos tiene id_cliente o se relaciona por cuenta
                $cliId = $p['id_cliente'] ?? '';
                if($cliId) {
                    $prestamosCliente[$cliId][] = $p;
                }
            }
            // Si la relación es directa por cliente:
            foreach($cuentas as $c) {
                $cId = $c['id_cliente'];
                // Buscar préstamos que coincidan con este cliente
                echo "'$cId': [";
                foreach($prestamos as $p) {
                    if(isset($p['id_cliente']) && $p['id_cliente'] == $cId) {
                        echo "{id: '{$p['id_prestamo']}', monto: '{$p['valor_prestamo']}'},";
                    }
                }
                echo "],";
            }
            ?>
        };

        function actualizarFormulario() {
            const selectCuenta = document.getElementById('num_cuenta');
            const selectTipo = document.getElementById('tipo_transaccion');
            const seccionPrestamo = document.getElementById('seccion-prestamo');
            const selectPrestamo = document.getElementById('id_prestamo');

            // Obtener el cliente ID de la cuenta seleccionada
            const optionSeleccionada = selectCuenta.options[selectCuenta.selectedIndex];
            const clienteId = optionSeleccionada.getAttribute('data-cliente');
            const tipoSeleccionado = selectTipo.value;

            // Mostrar u ocultar la opción de abonar préstamo según si el cliente tiene préstamos
            const prestamosDelCliente = prestamosPorCliente[clienteId] || [];
            
            if (tipoSeleccionado === 'abono_prestamo') {
                seccionPrestamo.style.display = 'block';
                selectPrestamo.required = true;

                // Rellenar el select de préstamos del cliente
                selectPrestamo.innerHTML = '<option value="" disabled selected>-- Seleccione un préstamo --</option>';
                prestamosDelCliente.forEach(p => {
                    let opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = `Préstamo ID: ${p.id} - Saldo Pendiente: $${parseFloat(p.monto).toLocaleString()}`;
                    selectPrestamo.appendChild(opt);
                });

                if(prestamosDelCliente.length === 0) {
                    selectPrestamo.innerHTML = '<option value="" disabled selected>Este cliente no tiene préstamos activos</option>';
                }
            } else {
                seccionPrestamo.style.display = 'none';
                selectPrestamo.required = false;
            }
        }
    </script>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container py-5" style="max-width: 600px;">
        <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
            <h3 class="fw-bold text-primary mb-3 text-center">Realizar Transacción</h3>

            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-success py-2 text-center"><?php echo htmlspecialchars($mensaje); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 text-center"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="Crear-RealizarTransacciones.php">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Seleccionar Cuenta</label>
                    <select name="num_cuenta" id="num_cuenta" class="form-select" onchange="actualizarFormulario()" required>
                        <option value="" disabled selected>-- Elige una cuenta --</option>
                        <?php foreach ($cuentas as $c): ?>
                            <option value="<?php echo $c['num_cuenta']; ?>" data-cliente="<?php echo $c['id_cliente']; ?>">
                                Cuenta #<?php echo $c['num_cuenta']; ?> - <?php echo htmlspecialchars($c['nomb_cli'] . ' ' . $c['apelli_cli']); ?> (Saldo: $<?php echo number_format($c['saldo'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Tipo de Transacción</label>
                    <select name="tipo_transaccion" id="tipo_transaccion" class="form-select" onchange="actualizarFormulario()" required>
                        <option value="" disabled selected>-- Seleccione tipo --</option>
                        <option value="deposito">Depósito</option>
                        <option value="retiro">Retiro</option>
                        <option value="abono_prestamo">Abonar a Préstamo</option> <!-- Única opción sin duplicar -->
                    </select>
                </div>

                <!-- Sección dinámica para seleccionar préstamo (solo si aplica) -->
                <div class="mb-3" id="seccion-prestamo" style="display: none;">
                    <label class="form-label fw-semibold">Seleccionar Préstamo a Abonar</label>
                    <select name="id_prestamo" id="id_prestamo" class="form-select">
                        <option value="" disabled selected>-- Seleccione un préstamo --</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Monto a Transaccionar ($)</label>
                    <input type="number" step="0.01" name="monto" class="form-control" placeholder="0.00" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold">Procesar Transacción</button>
            </form>
        </div>
    </div>
</body>
</html>
