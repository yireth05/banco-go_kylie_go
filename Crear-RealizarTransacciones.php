<?php
session_start();
require_once "conexionDB.php";

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Si es cliente, forzamos de forma segura el id_cliente desde la sesión activa
    $id_cliente = ($rol === 'cliente') ? $_SESSION['id_cliente'] : trim($_POST["id_cliente"] ?? '');
    $num_cuenta = trim($_POST["num_cuenta"] ?? '');
    $cod_tipo   = (int)($_POST["cod_tipo"] ?? 0);
    $cod_forma  = (int)($_POST["cod_forma"] ?? 0);
    $monto      = (float)($_POST["monto"] ?? 0);

    // Definir origen: Si es cliente es banca virtual (2), si es empleado/cajero toma su ID de sesión
    $id_banca_virtual = ($rol === 'cliente') ? 2 : null;
    
    $id_cajero = null;
    if ($rol !== 'cliente') {
        $id_cajero = $_SESSION['id_empleado'] ?? $_SESSION['usuario_id'] ?? 1; 
    }

    try {
        $sql  = "SELECT realizar_transaccion(:cliente, :cuenta, :tipo, :forma, :monto, :cajero, :banca)";
        $stmt = $conexion->prepare($sql);
        
        $stmt->execute([
            ':cliente' => $id_cliente,
            ':cuenta'  => $num_cuenta,
            ':tipo'    => $cod_tipo,
            ':forma'   => $cod_forma,
            ':monto'   => $monto,
            ':cajero'  => $id_cajero,
            ':banca'   => $id_banca_virtual
        ]);
        
        $mensaje = "<div class='alert alert-success alert-dismissible fade show shadow-sm rounded-3' role='alert'><strong>¡Transacción realizada con éxito!</strong> El saldo se ha actualizado en tiempo real.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } catch (Exception $e) {
        $errorCompleto = $e->getMessage();
        if (preg_match('/ERROR:\s*(.*?)(?=\nCONTEXT:|$)/s', $errorCompleto, $coincidencias)) {
            $mensajeAmigable = trim($coincidencias[1]);
        } else {
            $mensajeAmigable = $errorCompleto;
        }
        $mensaje = "<div class='alert alert-danger alert-dismissible fade show shadow-sm rounded-3' role='alert'><strong>Error en la Transacción:</strong> " . htmlspecialchars($mensajeAmigable) . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// Carga de datos optimizada según el rol
try {
    if ($rol === 'cliente') {
        // Cargar únicamente las cuentas del cliente que ha iniciado sesión
        $stmt_cuentas = $conexion->prepare("SELECT num_cuenta, id_cliente, saldo FROM cuentas WHERE id_cliente = :id ORDER BY num_cuenta");
        $stmt_cuentas->execute([':id' => $_SESSION['id_cliente']]);
        $cuentas = $stmt_cuentas->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Cargar todas las cuentas para que el cajero/admin pueda buscar por ID de cliente
        $cuentas = $conexion->query("SELECT num_cuenta, id_cliente, saldo FROM cuentas ORDER BY num_cuenta")->fetchAll(PDO::FETCH_ASSOC);
    }

    $tipos_transaccion = $conexion->query("SELECT cod_tipo_transaccion, nomb_tipo_transaccion FROM tipo_transacciones ORDER BY cod_tipo_transaccion")->fetchAll(PDO::FETCH_ASSOC);
    $formas_transaccion = $conexion->query("SELECT cod_forma_transaccion, nomb_forma_transaccion FROM formas_transacciones ORDER BY cod_forma_transaccion")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mensaje = "<div class='alert alert-warning'>Aviso al cargar datos: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Transacción - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php if (file_exists("navbar.php")) include_once "navbar.php"; ?>

    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow border-0 rounded-4">
                    <div class="card-header text-white py-3 rounded-top-4" style="background-color: #0E1B48;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0 fs-5">Módulo de Transacciones - Banco Villavicencio</h4>
                            <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill">Volver al Panel</a>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <?php echo $mensaje; ?>
                        
                        <form action="" method="POST" id="formTransaccion">
                            <?php if ($rol === 'cliente'): ?>
                                <!-- Si es cliente, la cédula va oculta y fija con el valor de su sesión -->
                                <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($_SESSION['id_cliente']); ?>">
                                
                                <div class="mb-3 p-3 bg-white rounded-3 border">
                                    <span class="text-muted small d-block">Titular de la cuenta:</span>
                                    <strong class="text-dark fs-6"><?php echo htmlspecialchars($_SESSION['usuario']); ?> (Cédula: <?php echo htmlspecialchars($_SESSION['id_cliente']); ?>)</strong>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Seleccione su Cuenta <span class="text-danger">*</span></label>
                                    <select class="form-select" id="select_cuenta" name="num_cuenta" required>
                                        <option value="" selected disabled>-- Seleccione una cuenta --</option>
                                        <?php foreach ($cuentas as $cuenta): ?>
                                            <option value="<?php echo $cuenta['num_cuenta']; ?>">
                                                <?php echo "Cuenta Nº: " . $cuenta['num_cuenta'] . " (Saldo: $" . number_format($cuenta['saldo'], 2, ',', '.') . ")"; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php else: ?>
                                <!-- Si es cajero o administrador, mantiene la búsqueda manual por ID -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">ID / Cédula del Cliente <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="input_id_cliente" name="id_cliente" placeholder="Escriba el ID del cliente" required onkeyup="buscarCuentasPorId(this.value)" onchange="buscarCuentasPorId(this.value)">
                                    <div id="mensaje_cliente" class="form-text">Escriba el ID del cliente para cargar sus cuentas automáticamente.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Número de Cuenta Asociada <span class="text-danger">*</span></label>
                                    <select class="form-select" id="select_cuenta" name="num_cuenta" required disabled>
                                        <option value="" selected disabled>-- Escriba primero un ID de cliente válido --</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Tipo de Transacción <span class="text-danger">*</span></label>
                                    <select class="form-select" name="cod_tipo" required>
                                        <option value="" selected disabled>Seleccione...</option>
                                        <?php foreach ($tipos_transaccion as $tipo): ?>
                                            <option value="<?php echo $tipo['cod_tipo_transaccion']; ?>">
                                                <?php echo $tipo['cod_tipo_transaccion'] . " - " . htmlspecialchars($tipo['nomb_tipo_transaccion']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Forma de Transacción <span class="text-danger">*</span></label>
                                    <select class="form-select" name="cod_forma" required>
                                        <option value="" selected disabled>Seleccione...</option>
                                        <?php foreach ($formas_transaccion as $forma): ?>
                                            <option value="<?php echo $forma['cod_forma_transaccion']; ?>">
                                                <?php echo $forma['cod_forma_transaccion'] . " - " . htmlspecialchars($forma['nomb_forma_transaccion']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Monto ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" class="form-control" name="monto" placeholder="0.00" required>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success btn-lg rounded-pill shadow-sm">Procesar Transacción</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($rol !== 'cliente'): ?>
    <script>
        var todasLasCuentas = <?php echo json_encode($cuentas); ?>;

        function buscarCuentasPorId(clienteId) {
            var selectCuenta = document.getElementById('select_cuenta');
            var mensajeCliente = document.getElementById('mensaje_cliente');
            
            clienteId = clienteId.trim();
            selectCuenta.innerHTML = '<option value="" selected disabled>-- Seleccione una cuenta --</option>';
            
            if (clienteId.length > 0) {
                var cuentasFiltradas = todasLasCuentas.filter(function(cuenta) {
                    return cuenta.id_cliente.toString().toLowerCase() === clienteId.toLowerCase();
                });

                if (cuentasFiltradas.length > 0) {
                    selectCuenta.disabled = false;
                    mensajeCliente.innerHTML = "<span class='text-success fw-semibold'>¡Cliente encontrado! Seleccione una cuenta.</span>";
                    
                    cuentasFiltradas.forEach(function(cuenta) {
                        var option = document.createElement('option');
                        option.value = cuenta.num_cuenta;
                        option.text = "Cuenta Nº: " + cuenta.num_cuenta + " (Saldo: $" + parseFloat(cuenta.saldo).toLocaleString('es-CO', {minimumFractionDigits: 2}) + ")";
                        selectCuenta.appendChild(option);
                    });
                } else {
                    selectCuenta.disabled = true;
                    mensajeCliente.innerHTML = "<span class='text-danger'>No se encontraron cuentas para este ID o el cliente no existe.</span>";
                }
            } else {
                selectCuenta.disabled = true;
                mensajeCliente.innerHTML = "Escriba el ID del cliente para cargar sus cuentas automáticamente.";
            }
        }
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
