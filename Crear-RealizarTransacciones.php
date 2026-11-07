<?php
include_once "conexion.php";
$mensaje="";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente = $_POST["id_cliente"];
    $num_cuenta = $_POST["num_cuenta"];
    $cod_tipo = $_POST["cod_tipo"];       // 1 para Depósito, 2 para Retiro 
    $cod_forma = $_POST["cod_forma"];     // 1: Efectivo, 2: Cheque, 3: Transferencia, etc.
    $monto = $_POST["monto"];
    // Dependiendo de quién haga la transacción, se pasa el cajero o la banca virtual:
    // Si es en ventanilla física:
    $id_cajero = !empty($POST["id_cajero"]) ? $_POST["id_cajero"] : null; 
    // Si es por la App/Web del cliente:
    $id_banca_virtual = !empty($POST["id_banca_virual"]) ? $_POST["id_banca_virtual"] :null; 
try {
        // 2. Llamar a la función almacenada de PostgreSQL usando PDO
    $sql = "SELECT realizar_transaccion(:cliente, :cuenta, :tipo, :forma, :monto, :cajero, :banca)";
    $stmt = $conexion->prepare($sql);
        
    $stmt->execute([':cliente' => $id_cliente, 
                   ':cuenta' => $num_cuenta, 
                   ':tipo' => $cod_tipo,
                   ':forma' => $cod_forma,
                   ':monto' => $monto, 
                   ':cajero' => $id_cajero,
                   ':banca' => $id_banca_virtual]);
        $mensaje="<div class='alert alert-success'> Transaccion realizada con éxito!!</div>";
    } catch (Exception $e) {
        $mensaje="<div class='alert alert-danger'>Error en la transacción: " . $e->getMessage() . "</div>";
    }
}
?>
<DOCTYPE html>
    <html lang="es">
        <head>
            <meta charset="UTF-8">
            <tittle>
                Realizar Transacción
            </tittle>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4>Módulo de Transacciones</h4>
                    </div>
                    <div class="card-body">
                        
                        <!-- Aquí mostramos el mensaje de éxito o error si el PHP procesó algo -->
                        <?php echo $mensaje; ?>

                        <!-- El formulario se apunta a sí mismo (action="") -->
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label">ID del Cliente</label>
                                <input type="text" class="form-control" name="id_cliente" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Número de Cuenta</label>
                                <input type="text" class="form-control" name="num_cuenta" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tipo de Transacción (1: Depósito, 2: Retiro)</label>
                                <input type="number" class="form-control" name="cod_tipo" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Forma de Transacción (1: Efectivo, 2: Cheque, 3: Transferencia)</label>
                                <input type="number" class="form-control" name="cod_forma" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Monto</label>
                                <input type="number" step="0.01" class="form-control" name="monto" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ID Cajero (Si es físico, si no déjalo vacío)</label>
                                <input type="number" class="form-control" name="id_cajero">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ID Banca Virtual (Si es por App/Web, si no déjalo vacío)</label>
                                <input type="number" class="form-control" name="id_banca_virtual">
                            </div>
                            <button type="submit" class="btn btn-success w-100">Procesar Transacción</button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
