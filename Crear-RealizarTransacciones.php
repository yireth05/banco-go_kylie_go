<?php
include("conexionDB.php");

$mensaje_exito = "";
$mensaje_error = "";

// Variables de control
$num_cuenta_seleccionada = $_GET['cuenta'] ?? ($_POST['num_cuenta'] ?? '');
$tipo_transaccion_seleccionada = $_POST['tipo_transaccion'] ?? 'Deposito';
$id_prestamo_seleccionado = $_POST['id_prestamo'] ?? '';
$prestamos_cliente = [];
$cliente_tiene_prestamos = false;

// 1. Cargar todas las cuentas disponibles usando PDO
try {
    $stmt_cuentas = $conexion->query("SELECT c.num_cncta, c.saldo, c.id_cliente, cl.nombre_cl, cl.apellido_cl 
                                       FROM cuenta c 
                                       JOIN cliente cl ON c.id_cliente = cl.id_cliente");
    $cuentas = $stmt_cuentas->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cuentas = [];
}

// 2. Si hay una cuenta seleccionada, buscamos los préstamos de ese cliente
if (!empty($num_cuenta_seleccionada)) {
    $stmt_cl = $conexion->prepare("SELECT id_cliente FROM cuenta WHERE num_cncta = ?");
    $stmt_cl->execute([$num_cuenta_seleccionada]);
    $row_cl = $stmt_cl->fetch(PDO::FETCH_ASSOC);
    
    if ($row_cl) {
        $id_cliente = $row_cl['id_cliente'];
        
        // Consultar préstamos activos del cliente
        $stmt_prestamos = $conexion->prepare("SELECT id_prestamo, monto_prestamo, saldo_actual 
                                              FROM prestamos 
                                              WHERE id_cliente = ? AND saldo_actual > 0");
        $stmt_prestamos->execute([$id_cliente]);
        $prestamos_cliente = $stmt_prestamos->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($prestamos_cliente) > 0) {
            $cliente_tiene_prestamos = true;
        }
    }
}

// 3. Procesar la transacción cuando se presione el botón principal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['procesar_transaccion'])) {
    $num_cuenta = $_POST['num_cuenta'] ?? '';
    $tipo_transaccion = $_POST['tipo_transaccion'] ?? '';
    $monto = floatval($_POST['monto'] ?? 0);
    $id_prestamo = $_POST['id_prestamo'] ?? null;

    if (empty($num_cuenta) || empty($tipo_transaccion) || $monto <= 0) {
        $mensaje_error = "Por favor ingrese todos los datos obligatorios y un monto válido.";
    } else {
        // Obtener saldo actual de la cuenta
        $stmt_saldo = $conexion->prepare("SELECT saldo FROM cuenta WHERE num_cncta = ?");
        $stmt_saldo->execute([$num_cuenta]);
        $data_cuenta = $stmt_saldo->fetch(PDO::FETCH_ASSOC);
        $saldo_cuenta = floatval($data_cuenta['saldo'] ?? 0);

        if ($tipo_transaccion == 'Abonar a Préstamo') {
            if (empty($id_prestamo)) {
                $mensaje_error = "Debe seleccionar un préstamo activo para realizar el abono.";
            } elseif ($saldo_cuenta < $monto) {
                $mensaje_error = "Saldo insuficiente en la cuenta para realizar el abono.";
            } else {
                // a) Restar dinero de la cuenta
                $nuevo_saldo_cuenta = $saldo_cuenta - $monto;
                $update_cuenta = $conexion->prepare("UPDATE cuenta SET saldo = ? WHERE num_cncta = ?");
                $update_cuenta->execute([$nuevo_saldo_cuenta, $num_cuenta]);

                // b) Disminuir el saldo del préstamo
                $stmt_p_info = $conexion->prepare("SELECT saldo_actual FROM prestamos WHERE id_prestamo = ?");
                $stmt_p_info->execute([$id_prestamo]);
                $data_p = $stmt_p_info->fetch(PDO::FETCH_ASSOC);
                $nuevo_saldo_prestamo = floatval($data_p['saldo_actual']) - $monto;
                if ($nuevo_saldo_prestamo < 0) {
                    $nuevo_saldo_prestamo = 0;
                }

                $nuevo_estado = ($nuevo_saldo_prestamo == 0) ? 'pagado' : 'activo';
                $update_prestamo = $conexion->prepare("UPDATE prestamos SET saldo_actual = ?, estado = ? WHERE id_prestamo = ?");
                $update_prestamo->execute([$nuevo_saldo_prestamo, $nuevo_estado, $id_prestamo]);

                // c) Registrar transacción
                $fecha = date('Y-m-d H:i:s');
                $insert_trans = $conexion->prepare("INSERT INTO transaccion (num_cncta, tipo_transaccion, monto, fecha) VALUES (?, 'Abono Prestamo', ?, ?)");
                $insert_trans->execute([$num_cuenta, $monto, $fecha]);

                $mensaje_exito = "¡Abono procesado con éxito! Saldo restante del préstamo: $" . number_format($nuevo_saldo_prestamo, 2);
            }
        } elseif ($tipo_transaccion == 'Deposito') {
            $nuevo_saldo = $saldo_cuenta + $monto;
            $update_cuenta = $conexion->prepare("UPDATE cuenta SET saldo = ? WHERE num_cncta = ?");
            $update_cuenta->execute([$nuevo_saldo, $num_cuenta]);

            $fecha = date('Y-m-d H:i:s');
            $insert_trans = $conexion->prepare("INSERT INTO transaccion (num_cncta, tipo_transaccion, monto, fecha) VALUES (?, 'Deposito', ?, ?)");
            $insert_trans->execute([$num_cuenta, $monto, $fecha]);
            
            $mensaje_exito = "Depósito realizado correctamente.";
        } elseif ($tipo_transaccion == 'Retiro') {
            if ($saldo_cuenta < $monto) {
                $mensaje_error = "Saldo insuficiente en la cuenta.";
            } else {
                $nuevo_saldo = $saldo_cuenta - $monto;
                $update_cuenta = $conexion->prepare("UPDATE cuenta SET saldo = ? WHERE num_cncta = ?");
                $update_cuenta->execute([$nuevo_saldo, $num_cuenta]);

                $fecha = date('Y-m-d H:i:s');
                $insert_trans = $conexion->prepare("INSERT INTO transaccion (num_cncta, tipo_transaccion, monto, fecha) VALUES (?, 'Retiro', ?, ?)");
                $insert_trans->execute([$num_cuenta, $monto, $fecha]);
                
                $mensaje_exito = "Retiro realizado correctamente.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Banco Villavicencio - Realizar Transacción</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include("navbar.php"); ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm p-4">
                <h3 class="text-center text-primary mb-4">Realizar Transacción</h3>

                <?php if (!empty($mensaje_error)): ?>
                    <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
                <?php endif; ?>

                <?php if (!empty($mensaje_exito)): ?>
                    <div class="alert alert-success"><?php echo $mensaje_exito; ?></div>
                <?php endif; ?>

                <!-- Formulario para cargar los préstamos de la cuenta seleccionada -->
                <div class="mb-3">
                    <label class="form-label">Seleccionar Cuenta</label>
                    <form method="GET" action="Crear-RealizarTransacciones.php" class="input-group">
                        <select class="form-select" name="cuenta" required>
                            <option value="">-- Elige una cuenta --</option>
                            <?php foreach ($cuentas as $c): ?>
                                <option value="<?php echo $c['num_cncta']; ?>" <?php echo ($num_cuenta_seleccionada == $c['num_cncta']) ? 'selected' : ''; ?>>
                                    Cuenta N° <?php echo $c['num_cncta']; ?> - <?php echo $c['nombre_cl'] . " " . $c['apellido_cl']; ?> ($<?php echo number_format($c['saldo'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-secondary" type="submit">Cargar Préstamos</button>
                    </form>
                </div>

                <!-- Formulario principal de la Transacción -->
                <form method="POST" action="Crear-RealizarTransacciones.php?cuenta=<?php echo $num_cuenta_seleccionada; ?>">
                    <input type="hidden" name="num_cuenta" value="<?php echo $num_cuenta_seleccionada; ?>">

                    <div class="mb-3">
                        <label for="tipo_transaccion" class="form-label">Tipo de Transacción</label>
                        <select class="form-select" id="tipo_transaccion" name="tipo_transaccion" required>
                            <option value="Deposito" <?php echo ($tipo_transaccion_seleccionada == 'Deposito') ? 'selected' : ''; ?>>Depósito</option>
                            <option value="Retiro" <?php echo ($tipo_transspan_seleccionada ?? '') == 'Retiro' ? 'selected' : ''; ?>>Retiro</option>
                            <option value="Abonar a Préstamo" <?php echo ($tipo_transaccion_seleccionada == 'Abonar a Préstamo') ? 'selected' : ''; ?>>Abonar a Préstamo</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="id_prestamo" class="form-label">Seleccionar Préstamo a Abonar</label>
                        <select class="form-select" id="id_prestamo" name="id_prestamo">
                            <?php if ($cliente_tiene_prestamos): ?>
                                <option value="">-- Elige un préstamo --</option>
                                <?php foreach ($prestamos_cliente as $prestamo): ?>
                                    <option value="<?php echo $prestamo['id_prestamo']; ?>" <?php echo ($id_prestamo_seleccionado == $prestamo['id_prestamo']) ? 'selected' : ''; ?>>
                                        Préstamo #<?php echo $prestamo['id_prestamo']; ?> (Saldo pendiente: $<?php echo number_format($prestamo['saldo_actual'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">Este cliente no tiene préstamos activos</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="monto" class="form-label">Monto a Transaccionar ($)</label>
                        <input type="number" step="0.01" class="form-control" id="monto" name="monto" placeholder="0.00" required>
                    </div>

                    <button type="submit" name="procesar_transaccion" class="btn btn-primary w-100">Procesar Transacción</button>
                </form>

            </div>
        </div>
    </div>
</div>

</body>
</html>
