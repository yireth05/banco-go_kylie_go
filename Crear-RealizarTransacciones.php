<?php
include_once "conexion.php";
$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente = $_POST["id_cliente"];
    $num_cuenta = $_POST["num_cuenta"];
    $cod_tipo = $_POST["cod_tipo"];       // 1 para Depósito, 2 para Retiro 
    $cod_forma = $_POST["cod_forma"];     // 1: Efectivo, 2: Cheque, 3: Transferencia, etc.
    $monto = $_POST["monto"];
    
    $id_banca_virtual = !empty($_POST["id_banca_virtual"]) ? $_POST["id_banca_virtual"] : null;
    $id_cajero = !empty($_POST["id_cajero"]) ? $_POST["id_cajero"] : null;

    try {
        // Llamar a la función almacenada de PostgreSQL usando PDO
        $sql = "SELECT realizar_transaccion(:cliente, :cuenta, :tipo, :forma, :monto, :cajero, :banca)";
        $stmt = $conexion->prepare($sql);
        
        $stmt->execute([
            ':cliente' => $id_cliente,
            ':cuenta' => $num_cuenta,
            ':tipo' => $cod_tipo,
            ':forma' => $cod_forma,
            ':monto' => $monto,
            ':cajero' => $id_cajero,
            ':banca' => $id_banca_virtual
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
    <title>Realizar Transacción - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark shadow-sm mb-4">
        <div class="container">
            <span class="navbar-brand mb-0 h1">Banco Villavicencio</span>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0">Módulo de Transacciones</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php echo $mensaje; ?>
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">ID del Cliente</label>
                                <input type="text" class="form-control" name="id_cliente" placeholder="Ingrese ID del cliente" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Número de Cuenta</label>
                                <input type="text" class="form-control" name="num_cuenta" placeholder="Ingrese número de cuenta" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Tipo de Transacción</label>
                                    <select class="form-select" name="cod_tipo" required>
                                        <option value="" selected disabled>Seleccione...</option>
                                        <option value="1">1 - Depósito</option>
                                        <option value="2">2 - Retiro</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Forma de Transacción</label>
                                    <select class="form-select" name="cod_forma" required>
                                        <option value="" selected disabled>Seleccione...</option>
                                        <option value="1">1 - Efectivo</option>
                                        <option value="2">2 - Cheque</option>
                                        <option value="3">3 - Transferencia</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Monto</label>
                                <input type="number" step="0.01" min="0.01" class="form-control" name="monto" placeholder="0.00" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">ID Cajero (Físico)</label>
                                    <input type="number" class="form-control" name="id_cajero" placeholder="Opcional">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">ID Banca Virtual</label>
                                    <input type="number" class="form-control" name="id_banca_virtual" placeholder="Opcional">
                                </div>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success btn-lg">Procesar Transacción</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
