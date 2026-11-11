<?php
include("conexionDB.php");

$mensaje_exito = "";
$mensaje_error = "";

// Variables para controlar la cuenta y préstamo seleccionados
$num_cuenta_seleccionada = $_POST['num_cuenta'] ?? '';
$id_prestamo_seleccionado = $_POST['id_prestamo'] ?? '';
$prestamos_cliente = [];
$cliente_tiene_prestamos = false;

// 1. Cargar todas las cuentas disponibles
$query_cuentas = "SELECT c.num_cncta, c.saldo, c.id_cliente, cl.nombre_cl, cl.apellido_cl 
                  FROM cuenta c 
                  JOIN cliente cl ON c.id_cliente = cl.id_cliente";
$res_cuentas = mysqli_query($conexion, $query_cuentas);

// 2. Si el usuario ya eligió una cuenta, buscamos los préstamos del cliente dueño de esa cuenta
if (!empty($num_cuenta_seleccionada)) {
    $sql_cliente = "SELECT id_cliente FROM cuenta WHERE num_cncta = '$num_cuenta_seleccionada'";
    $res_cl = mysqli_query($conexion, $sql_cliente);
    if ($row_cl = mysqli_fetch_assoc($res_cl)) {
        $id_cliente = $row_cl['id_cliente'];
        
        // Consultar préstamos con saldo pendiente de este cliente
        $sql_prestamos = "SELECT id_prestamo, monto_prestamo, saldo_actual 
                          FROM prestamos 
                          WHERE id_cliente = '$id_cliente' AND saldo_actual > 0";
        $res_prestamos = mysqli_query($conexion, $sql_prestamos);
        
        while ($p = mysqli_fetch_assoc($res_prestamos)) {
            $prestamos_cliente[] = $p;
        }
        
        if (count($prestamos_cliente) > 0) {
            $cliente_tiene_prestamos = true;
        }
    }
}

// 3. Procesar la transacción cuando presione "Procesar Transacción"
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['procesar_transaccion'])) {
    $num_cuenta = $_POST['num_cuenta'];
    $tipo_transaccion = $_POST['tipo_transaccion'];
    $monto = floatval($_POST['monto']);
    $id_prestamo = $_POST['id_prestamo'] ?? null;

    if (empty($num_cuenta) || empty($tipo_transaccion) || $monto <= 0) {
        $mensaje_error = "Por favor ingrese todos los datos válidos.";
    } else {
        // Obtener saldo actual de la cuenta
        $res_saldo = mysqli_query($conexion, $sql_saldo_q = "SELECT saldo FROM cuenta WHERE num_cncta = '$num_cuenta'");
        $data_cuenta = mysqli_fetch_assoc($res_saldo);
        $saldo_cuenta = floatval($data_cuenta['saldo']);

        if ($tipo_transaccion == 'Abonar a Préstamo') {
            if (empty($id_prestamo)) {
                $mensaje_error = "Debe seleccionar un préstamo activo para realizar el abono.";
            } elseif ($saldo_cuenta < $monto) {
                $mensaje_error = "Saldo insuficiente en la cuenta para realizar el abono.";
            } else {
                // a) Restar dinero de la cuenta bancaria
                $nuevo_saldo_cuenta = $saldo_cuenta - $monto;
                mysqli_query($conexion, "UPDATE cuenta SET saldo = $nuevo_saldo_cuenta WHERE num_cncta = '$num_cuenta'");

                // b) Disminuir el saldo del préstamo
                $sql_p_info = mysqli_query($conexion, "SELECT saldo_actual FROM prestamos WHERE id_prestamo = '$id_prestamo'");
                $data_p = mysqli_fetch_assoc($sql_p_info);
                $nuevo_saldo_prestamo = floatval($data_p['saldo_actual']) - $monto;
                if ($nuevo_saldo_prestamo < 0) {
                    $nuevo_saldo_prestamo = 0;
                }

                // Definir estado del préstamo
                $nuevo_estado = ($nuevo_saldo_prestamo == 0) ? 'pagado' : 'activo';

                mysqli_query($conexion, "UPDATE prestamos SET saldo_actual = $nuevo_saldo_prestamo, estado = '$nuevo_estado' WHERE id_prestamo = '$id_prestamo'");

                // c) Registrar la transacción en el historial
                $fecha = date('Y-m-d H:i:s');
                mysqli_query($conexion, "INSERT INTO transaccion (num_cncta, tipo_transaccion, monto, fecha) VALUES ('$num_cuenta', 'Abono Prestamo', $monto, '$fecha')");

                $mensaje_exito = "¡Abono al préstamo procesado con éxito! El saldo restante del préstamo es: $" . number_format($nuevo_saldo_prestamo, 2);
            }
        } elseif ($tipo_transaccion == 'Deposito') {
            $nuevo_saldo = $saldo_cuenta + $monto;
            mysqli_query($conexion, "UPDATE cuenta SET saldo = $nuevo_saldo WHERE num_cncta = '$num_cuenta'");
            $fecha = date('Y-m-d H:i:s');
            mysqli_query($conexion, "INSERT INTO transaccion (num_cncta, tipo_transaccion, monto, fecha) VALUES ('$num_cuenta', 'Deposito', $monto, '$fecha')");
            $mensaje_exito = "Depósito realizado correctamente.";
        } elseif ($tipo_transaccion == 'Retiro') {
            if ($saldo_cuenta < $monto) {
                $mensaje_error = "Saldo insuficiente.";
            } else {
                $nuevo_saldo = $saldo_cuenta - $monto;
                mysqli_query($conexion, "UPDATE cuenta SET saldo = $nuevo_saldo WHERE num_cncta = '$num_cuenta'");
                $fecha = date('Y-m-d H:i:s');
                mysqli_query($conexion, "INSERT INTO transaccion (num_cncta, tipo_transaccion, monto, fecha) VALUES ('$num_cuenta', 'Retiro', $monto, '$fecha')");
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
    <title">Banco Villavicencio - Realizar Transacción</title>
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

                <form method="POST" action="Crear-RealizarTransacciones.php">
                    <!-- 1. Selección de Cuenta -->
                    <div class="mb-3">
                        <label for="num_cuenta" class="form-label">Seleccionar Cuenta</label>
                        <select class="form-select" id="num_cuenta" name="num_cuenta" onchange="this.form.submit()">
                            <option value="">-- Elige una cuenta --</option>
                            <?php 
                            mysqli_data_seek($res_cuentas, 0);
                            while ($c = mysqli_fetch_assoc($res_cuentas)): 
                            ?>
                                <option value="<?php echo $c['num_cncta']; ?>" <?php echo ($num_cuenta_seleccionada == $c['num_cncta']) ? 'selected' : ''; ?>>
                                    Cuenta N° <?php echo $c['num_cncta']; ?> - <?php echo $c['nombre_cl'] . " " . $c['apellido_cl']; ?> ($<?php echo number_format($c['saldo'], 2); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <small class="text-muted">Al cambiar de cuenta se cargarán sus préstamos automáticamente.</small>
                    </div>

                    <!-- 2. Tipo de Transacción (Sin duplicados) -->
                    <div class="mb-3">
                        <label for="tipo_transaccion" class="form-label">Tipo de Transacción</label>
                        <select class="form-select" id="tipo_transaccion" name="tipo_transaccion">
                            <option value="Deposito">Depósito</option>
                            <option value="Retiro">Retiro</option>
                            <option value="Abonar a Préstamo">Abonar a Préstamo</option>
                        </select>
                    </div>

                    <!-- 3. Selección de Préstamo (Solo si tiene préstamos activos el cliente) -->
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

                    <!-- 4. Monto -->
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
