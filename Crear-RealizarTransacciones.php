<?php
session_start();
include("conexionDB.php");

$mensaje_exito = "";
$mensaje_error = "";

// Variables de control
$num_cuenta_seleccionada =$_POST['num_cuenta'] ?? '';
$tipo_transaccion_seleccionada =$_POST['tipo_transaccion'] ?? 'Deposito';
$id_prestamo_seleccionado =$_POST['id_prestamo'] ?? '';
$prestamos_cliente = [];$cliente_tiene_prestamos = false;

// Obtener datos del usuario en sesión
$id_usuario_sesion = $_SESSION['id_cliente'] ?? $_SESSION['usuario_id'] ?? null;
$rol_sesion =$_SESSION['rol'] ?? 'cliente'; 

// 1. Cargar las cuentas disponibles según el rol
try {
    if ($rol_sesion === 'cliente') {
        $stmt_cuentas =$conexion->prepare("
            SELECT c.num_cuenta, c.saldo, c.id_cliente, cl.nomb_cli, cl.apelli_cli 
            FROM cuentas c 
            JOIN clientes cl ON c.id_cliente = cl.id_cliente
            WHERE c.id_cliente = ?
        ");
        $stmt_cuentas->execute([$id_usuario_sesion]);
    } else {
        $stmt_cuentas =$conexion->query("
            SELECT c.num_cuenta, c.saldo, c.id_cliente, cl.nomb_cli, cl.apelli_cli 
            FROM cuentas c 
            JOIN clientes cl ON c.id_cliente = cl.id_cliente
        ");
    }
    $cuentas =$stmt_cuentas->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {$cuentas = [];
}

// LÓGICA AUTOMÁTICA: Si el cliente solo tiene 1 cuenta, se asigna automáticamente
if (count($cuentas) === 1) {
    $num_cuenta_seleccionada =$cuentas[0]['num_cuenta'];
}

// 2. Si hay una cuenta seleccionada (ya sea manual o automática), buscamos los préstamos
if (!empty($num_cuenta_seleccionada)) {
    $stmt_cl =$conexion->prepare("SELECT id_cliente FROM cuentas WHERE num_cuenta = ?");
    $stmt_cl->execute([$num_cuenta_seleccionada]);
    $row_cl =$stmt_cl->fetch(PDO::FETCH_ASSOC);
    
    if ($row_cl) {
        $id_cliente =$row_cl['id_cliente'];
        
        $stmt_prestamos =$conexion->prepare("
            SELECT id_prestamo, monto_aprobado, saldo_pendiente 
            FROM prestamos 
            WHERE id_cliente = ? AND saldo_pendiente > 0
        ");
        $stmt_prestamos->execute([$id_cliente]);
        $prestamos_cliente =$stmt_prestamos->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($prestamos_cliente) > 0) {$cliente_tiene_prestamos = true;
        }
    }
}

// 3. Procesar la transacción al presionar el botón
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['procesar_transaccion'])) {
    $num_cuenta =$_POST['num_cuenta'] ?? '';
    $tipo_transaccion =$_POST['tipo_transaccion'] ?? '';
    $monto = floatval($_POST['monto'] ?? 0);
    $id_prestamo =$_POST['id_prestamo'] ?? null;

    if (empty($num_cuenta) \vert{}\vert{} empty($tipo_transaccion) || $monto <= 0) {$mensaje_error = "Por favor complete todos los campos obligatorios y asigne un monto válido.";
    } else {
        $stmt_saldo =$conexion->prepare("SELECT saldo FROM cuentas WHERE num_cuenta = ?");
        $stmt_saldo->execute([$num_cuenta]);
        $data_cuenta =$stmt_saldo->fetch(PDO::FETCH_ASSOC);
        $saldo_cuenta = floatval($data_cuenta['saldo'] ?? 0);

        if ($tipo_transaccion == 'Abonar a Préstamo') {
            if (empty($id_prestamo)) {$mensaje_error = "Debe seleccionar un préstamo activo para realizar el abono.";
            } elseif ($saldo_cuenta < $monto) {$mensaje_error = "Saldo insuficiente en la cuenta para realizar este abono.";
            } else {
                $nuevo_saldo_cuenta = $saldo_cuenta -$monto;
                $update_cuenta =$conexion->prepare("UPDATE cuentas SET saldo = ? WHERE num_cuenta = ?");
                $update_cuenta->execute([$nuevo_saldo_cuenta,$num_cuenta]);

                $stmt_p_info =$conexion->prepare("SELECT saldo_pendiente FROM prestamos WHERE id_prestamo = ?");
                $stmt_p_info->execute([$id_prestamo]);$data_p = $stmt_p_info->fetch(PDO::FETCH_ASSOC);$nuevo_saldo_prestamo = floatval($data_p['saldo_pendiente']) -$monto;
                if ($nuevo_saldo_prestamo < 0) {$nuevo_saldo_prestamo = 0;
                }

                $update_prestamo =$conexion->prepare("UPDATE prestamos SET saldo_pendiente = ? WHERE id_prestamo = ?");
                $update_prestamo->execute([$nuevo_saldo_prestamo,$id_prestamo]);

                $mensaje_exito = "¡Abono al préstamo realizado con éxito! Saldo pendiente del préstamo: $" . number_format($nuevo_saldo_prestamo, 2);
            }
        } elseif ($tipo_transaccion == 'Deposito' \vert{}\vert{}$tipo_transaccion == 'Depósito') {
            $nuevo_saldo = $saldo_cuenta +$monto;
            $update_cuenta =$conexion->prepare("UPDATE cuentas SET saldo = ? WHERE num_cuenta = ?");
            $update_cuenta->execute([$nuevo_saldo, $num_cuenta]);$mensaje_exito = "Depósito realizado correctamente.";
        } elseif ($tipo_transaccion == 'Retiro') {
            if ($saldo_cuenta < $monto) {$mensaje_error = "Saldo insuficiente en la cuenta para realizar el retiro.";
            } else {
                $nuevo_saldo = $saldo_cuenta -$monto;
                $update_cuenta =$conexion->prepare("UPDATE cuentas SET saldo = ? WHERE num_cuenta = ?");
                $update_cuenta->execute([$nuevo_saldo, $num_cuenta]);$mensaje_exito = "Retiro realizado correctamente.";
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

                <form method="POST" action="Crear-RealizarTransacciones.php">
                    
                    <!-- 1. SECCIÓN DINÁMICA DE CUENTA -->
                    <div class="mb-3">
                        <label class="form-label">Cuenta Bancaria</label>
                        
                        <?php if (count($cuentas) === 0): ?>
                            <!-- CASO 0: No tiene cuentas -->
                            <div class="alert alert-warning m-0">No posees cuentas bancarias registradas en el sistema.</div>
                            
                        <?php elseif (count($cuentas) === 1): ?>
                            <!-- CASO 1: Tiene EXACTAMENTE 1 cuenta (Se muestra fija, sin menú) -->
                            <input type="text" class="form-control fw-bold bg-white" readonly 
                                   value="Cuenta N° <?php echo $cuentas[0]['num_cuenta']; ?> - <?php echo $cuentas[0]['nomb_cli'] . " " . $cuentas[0]['apelli_cli']; ?> ($<?php echo number_format($cuentas[0]['saldo'], 2); ?>)">
                            <input type="hidden" name="num_cuenta" value="<?php echo $cuentas[0]['num_cuenta']; ?>">
                            
                        <?php else: ?>
                            <!-- CASO 2: Tiene VARIAS cuentas (Muestra el menú desplegable) -->
                            <select class="form-select" id="num_cuenta" name="num_cuenta" required onchange="this.form.submit()">
                                <option value="">-- Elige una cuenta --</option>
                                <?php foreach ($cuentas as$c): ?>
                                    <option value="<?php echo $c['num_cuenta']; ?>" <?php echo ($num_cuenta_seleccionada ==$c['num_cuenta']) ? 'selected' : ''; ?>>
                                        Cuenta N° <?php echo $c['num_cuenta']; ?> - <?php echo $c['nomb_cli'] . " " . $c['apelli_cli']; ?> ($<?php echo number_format($c['saldo'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <!-- Si NO hay cuentas disponibles, el resto del formulario no se habilita -->
                    <?php if (count($cuentas) > 0): ?>
                        
                        <!-- 2. Tipo de Transacción -->
                        <div class="mb-3">
                            <label for="tipo_transaccion" class="form-label">Tipo de Transacción</label>
                            <select class="form-select" id="tipo_transaccion" name="tipo_transaccion" required>
                                <option value="Deposito" <?php echo ($tipo_transaccion_seleccionada == 'Deposito') ? 'selected' : ''; ?>>Depósito</option>
                                <option value="Retiro" <?php echo ($tipo_transaccion_seleccionada == 'Retiro') ? 'selected' : ''; ?>>Retiro</option>
                                
                                <?php if ($cliente_tiene_prestamos): ?>
                                    <option value="Abonar a Préstamo" <?php echo ($tipo_transaccion_seleccionada == 'Abonar a Préstamo') ? 'selected' : ''; ?>>Abonar a Préstamo</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- 3. Seleccionar Préstamo a Abonar -->
                        <div class="mb-3">
                            <label for="id_prestamo" class="form-label">Seleccionar Préstamo a Abonar</label>
                            <select class="form-select" id="id_prestamo" name="id_prestamo">
                                <?php if ($cliente_tiene_prestamos): ?>
                                    <option value="">-- Elige un préstamo --</option>
                                    <?php foreach ($prestamos_cliente as$prestamo): ?>
                                        <option value="<?php echo $prestamo['id_prestamo']; ?>" <?php echo ($id_prestamo_seleccionado ==$prestamo['id_prestamo']) ? 'selected' : ''; ?>>
                                            Préstamo #<?php echo $prestamo['id_prestamo']; ?> (Saldo pendiente: $<?php echo number_format($prestamo['saldo_pendiente'], 2); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">Este cliente no tiene préstamos activos</option>
                                <?php endif; ?>
                            </
