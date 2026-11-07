<?php
// gestion_prestamos.php
include_once "conexionDB.php";

$mensaje = "";

// Procesar el registro de un nuevo préstamo
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_prestamo      = trim($_POST["id_prestamo"]);
    $id_cliente       = trim($_POST["id_cliente"]);
    $monto_aprobado   = (float)$_POST["monto_aprobado"];
    $tasa_interes     = (float)$_POST["tasa_interes"];
    $cuotas_aprobadas = (int)$_POST["cuotas_aprobadas"];

    // En un préstamo nuevo, el saldo pendiente equivale al monto aprobado
    $saldo_pendiente = $monto_aprobado;

    try {
        $sql = "INSERT INTO prestamos (id_prestamo, monto_aprobado, saldo_pendiente, tasa_interes, cuotas_aprobadas, id_cliente) 
                VALUES (:id_p, :monto, :saldo, :tasa, :cuotas, :id_cli)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':id_p'   => $id_prestamo,
            ':monto'  => $monto_aprobado,
            ':saldo'  => $saldo_pendiente,
            ':tasa'   => $tasa_interes,
            ':cuotas' => $cuotas_aprobadas,
            ':id_cli' => $id_cliente
        ]);
        $mensaje = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                        <strong>Confirmacion:</strong> El prestamo numero <strong>{$id_prestamo}</strong> ha sido registrado exitosamente.
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    } catch (PDOException $e) {
        $error_msg = $e->getMessage();
        if ($e->getCode() === '23505') {
            $error_user = "El identificador de prestamo ingresado ya existe en el sistema.";
        } else {
            $error_user = $error_msg;
        }
        $mensaje = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        <strong>Error de registro:</strong> {$error_user}
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    }
}

// Consultar la lista general de préstamos junto con la información del cliente
try {
    $sql_consulta = "SELECT p.*, c.nomb_cli, c.apelli_cli 
                    FROM prestamos p 
                    JOIN clientes c ON p.id_cliente = c.id_cliente 
                    ORDER BY p.fecha_aprobacion DESC";
    $stmt_p = $conexion->query($sql_consulta);
    $prestamos = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

    // Consultar lista de clientes para el selector del formulario
    $stmt_c = $conexion->query("SELECT id_cliente, nomb_cli, apelli_cli FROM clientes ORDER BY nomb_cli ASC");
    $clientes = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error en la consulta a la base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion de Prestamos - Banco GO KYLIE GO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Incluir barra de navegación si existe -->
    <?php if (file_exists("navbar.php")) include_once "navbar.php"; ?>

    <div class="container mt-4 mb-5">
        
        <?php echo $mensaje; ?>

        <div class="row">
            <!-- Formulario de Registro de Préstamo -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 fs-6 fw-semibold">Solicitud y Aprobacion de Prestamos</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-medium">Codigo de Prestamo (*)</label>
                                <input type="text" class="form-control" name="id_prestamo" placeholder="Ej: PR-004" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Cliente / Titular (*)</label>
                                <select class="form-select" name="id_cliente" required>
                                    <option value="" disabled selected>Seleccione un cliente...</option>
                                    <?php foreach ($clientes as $cli): ?>
                                        <option value="<?php echo htmlspecialchars($cli['id_cliente']); ?>">
                                            <?php echo htmlspecialchars($cli['nomb_cli'] . ' ' . $cli['apelli_cli'] . ' (' . $cli['id_cliente'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Monto Aprobado ($) (*)</label>
                                <input type="number" step="0.01" min="1" class="form-control" name="monto_aprobado" placeholder="1000000.00" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Tasa de Interes Mensual (%) (*)</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="tasa_interes" placeholder="1.80" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Cuotas Aprobadas (*)</label>
                                <input type="number" min="1" class="form-control" name="cuotas_aprobadas" placeholder="12" required>
                            </div>

                            <button type="submit" class="btn btn-success w-100 py-2 font-weight-bold">Registrar Prestamo</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabla de Préstamos Registrados -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fs-6 fw-semibold">Cartera de Prestamos Vigentes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Cliente</th>
                                        <th>Monto</th>
                                        <th>Saldo Pendiente</th>
                                        <th>Tasa</th>
                                        <th>Cuotas</th>
                                        <th>Aprobacion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($prestamos) > 0): ?>
                                        <?php foreach ($prestamos as $p): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($p['id_prestamo']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($p['nomb_cli'] . ' ' . $p['apelli_cli']); ?></td>
                                                <td class="fw-bold text-primary">$<?php echo number_format($p['monto_aprobado'], 2); ?></td>
                                                <td class="fw-bold text-danger">$<?php echo number_format($p['saldo_pendiente'], 2); ?></td>
                                                <td><span class="badge bg-secondary"><?php echo $p['tasa_interes']; ?>%</span></td>
                                                <td><?php echo $p['cuotas_aprobadas']; ?> mes(es)</td>
                                                <td><small class="text-muted"><?php echo $p['fecha_aprobacion']; ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">No se encontraron registros de prestamos en el sistema.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
