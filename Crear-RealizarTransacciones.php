<?php
// Crear-RealizarTransacciones.php
session_start();
require_once "conexionDB.php";

$mensaje = "";

try {
    // 1. Cargar TODAS las cuentas asociadas a sus clientes para filtrarlas por ID en JavaScript
    $cuentas = $conexion->query("SELECT num_cuenta, id_cliente, saldo FROM cuentas ORDER BY num_cuenta")->fetchAll(PDO::FETCH_ASSOC);

    // 2. Cargar Tipos de Transacción y Formas de Pago desde las tablas maestras de PostgreSQL
    $tipos_transaccion = $conexion->query("SELECT cod_tipo_transaccion, nomb_tipo_transaccion FROM tipo_transacciones ORDER BY cod_tipo_transaccion")->fetchAll(PDO::FETCH_ASSOC);
    $formas_pago = $conexion->query("SELECT cod_forma_pago, nomb_forma_pago FROM forma_pagos ORDER BY cod_forma_pago")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $error_carga = "Aviso: " . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente = $_POST["id_cliente"];
    $num_cuenta = $_POST["num_cuenta"];
    $cod_tipo   = $_POST["cod_tipo"];       
    $cod_forma  = $_POST["cod_forma"];      
    $monto      = $_POST["monto"];
    
    // El cajero físico opera en ventanilla, por lo que la banca virtual siempre es NULL
    $id_banca_virtual = null; 
    
    // Puedes capturar el ID del cajero desde la sesión si lo tienes guardado, o dejarlo opcional/nulo
    $id_cajero = !empty($_POST["id_cajero"]) ? $_POST["id_cajero"] : null;

    try {
        $sql = "SELECT realizar_transaccion(:cliente, :cuenta, :tipo, :forma, :monto, :cajero, :banca)";
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
        
        $mensaje = "<div class='alert alert-success alert-dismissible fade show' role='alert'>¡Transacción realizada con éxito!<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    } catch (Exception $e) {
        $mensaje = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Error en la transacción: " . $e->getMessage() . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Realizar Transacción - Banco GO KYLIE GO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand mb-0 h1" href="index.php">Banco GO KYLIE GO</a>
            <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill">← Volver al Panel</a>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow border-0 rounded-4">
                    <div class="card-header text-white py-3 rounded-top-4" style="background-color: #0E1B48;">
                        <h4 class="mb-0">💸 Módulo de Transacciones en Ventanilla</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php echo $mensaje; ?>
                        
                        <form action="" method="POST" id="formTransaccion">
                            
                            <!-- 1. Escribir el ID del Cliente para buscar sus cuentas -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">ID / Cédula del Cliente <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="input_id_cliente" name="id_cliente" placeholder="Escriba el ID del cliente" required onkeyup="buscarCuentasPorId(this.value)" onchange="buscarCuentasPorId(this.value)">
                                <div id="mensaje_cliente" class="form-text">Escriba el ID del cliente para cargar sus cuentas automáticamente.</div>
                            </div>

                            <!-- 2. Seleccionar Cuenta (Se llena solo según el ID escrito) -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Número de Cuenta Asociada <span class="text-danger">*</span></label>
                                <select class="form-select" id="select_cuenta" name="num_cuenta" required disabled>
                                    <option value="" selected disabled>-- Escriba primero un ID de cliente válido --</option>
                                </select>
                            </div>
                            
                            <div class="row">
                                <!-- 3. Tipo de Transacción -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Tipo de Transacción <span class="text-danger">*</span></label>
                                    <select class="form-select" name="cod_tipo" required>
                                        <option value="" selected disabled>Seleccione...</option>
                                        <?php foreach ($tipos_transaccion as $tipo): ?>
                                            <option value="<?php echo $tipo['cod_tipo_transaccion']; ?>">
                                                <?php echo $tipo['cod_tipo_transaccion'] . " - " . $tipo['nomb_tipo_transaccion']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- 4. Forma de Transacción / Pago -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Forma de Transacción <span class="text-danger">*</span></label>
                                    <select class="form-select" name="cod_forma" required>
                                        <option value="" selected disabled>Seleccione...</option>
                                        <?php if (!empty($formas_pago)): ?>
                                            <?php foreach ($formas_pago as $forma): ?>
                                                <option value="<?php echo $forma['cod_forma_pago']; ?>">
                                                    <?php echo $forma['cod_forma_pago'] . " - " . $forma['nomb_forma_pago']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <!-- Respaldo por defecto si la tabla SQL está vacía -->
                                            <option value="1">1 - Efectivo</option>
                                            <option value="2">2 - Cheque</option>
                                            <option value="3">3 - Transferencia</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- 5. Monto -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Monto ($) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" class="form-control" name="monto" placeholder="0.00" required>
                            </div>

                            <!-- 6. ID Cajero Físico (Opcional) -->
                            <div class="mb-3">
                                <label class="form-label text-muted">ID Cajero Físico (Terminal de Sucursal)</label>
                                <input type="number" class="form-control" name="id_cajero" placeholder="Opcional">
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success btn-lg rounded-pill">Procesar Transacción</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script JavaScript para filtrar cuentas al teclear el ID -->
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
                    mensajeCliente.innerHTML = "<span class='text-success fw-semibold'>¡Cliente encontrado! Seleccione la cuenta.</span>";
                    
                    cuentasFiltradas.forEach(function(cuenta) {
                        var option = document.createElement('option');
                        option.value = cuenta.num_cuenta;
                        option.text = "Cuenta Nº: " + cuenta.num_cuenta + " (Saldo: $" + parseFloat(cuenta.saldo).toLocaleString('es-CO') + ")";
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
