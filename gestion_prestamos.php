<?php
// gestion_prestamos.php
session_start();
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
        $mensaje = "<div class='alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm' role='alert'>
                        <strong>Confirmación:</strong> El préstamo número <strong>{$id_prestamo}</strong> ha sido registrado exitosamente.
                        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
                    </div>";
    } catch (PDOException $e) {
        $error_msg = $e->getMessage();
        if ($e->getCode() === '23505') {
            $error_user = "El identificador de préstamo ingresado ya existe en el sistema.";
        } else {
            $error_user = $error_msg;
        }
        $mensaje = "<div class='alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm' role='alert'>
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
    <title>Gestión de Préstamos - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-0e1b48: #0E1B48;
            --color-c18db4: #C18DB4;
            --color-e2cad8: #E2CAD8;
            --color-87a7d0: #87A7D0;
            --color-27425d: #27425D;
            --color-0e1f2f: #0E1F2F;
        }
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, var(--color-e2cad8) 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
        }
        .card-custom {
            border-radius: 16px;
            border: none;
            box-shadow: 0 8px 24px rgba(14, 27, 72, 0.08);
        }
        .card-header-brand {
            background-color: var(--color-0e1b48) !important;
            color: #ffffff !important;
            border-radius: 16px 16px 0 0 !important;
        }
        .table-dark {
            background-color: var(--color-0e1b48);
        }
        .table-dark th {
            background-color: var(--color-0e1b48);
            color: #ffffff;
            border-color: var(--color-27425d);
        }
        .btn-custom {
            background-color: var(--color-0e1b48);
            color: #ffffff;
            border-radius: 50rem;
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            background-color: var(--color-27425d);
            color: #ffffff;
        }
        .btn-back {
            background-color: var(--color-c18db4);
            color: #ffffff;
            border-radius: 50rem;
            border: none;
            font-weight: 600;
        }
        .btn-back:hover {
            background-color: var(--color-87a7d0);
            color: var(--color-0e1b48);
        }
    </style>
</head>
<body>

    <!-- Incluir barra de navegación si existe -->
    <?php if (file_exists("navbar.php")) include_once "navbar.php"; ?>

    <div class="container mt-4 mb-5">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">Gestión de Préstamos</h2>
                <p class="text-muted mb-0">Solicitud, aprobación y cartera general de créditos del banco.</p>
            </div>
            <a href="index.php" class="btn btn-back px-4 py-2">← Volver al Panel</a>
        </div>
        
        <?php echo $mensaje; ?>

        <div class="row">
            <!-- Formulario de Registro de Préstamo -->
            <div class="col-md-4 mb-4">
                <div class="card card-custom bg-white">
                    <div class="card-header card-header-brand p-3">
                        <h5 class="mb-0 fs-6 fw-semibold">Solicitud y Aprobación de Préstamos</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-medium">Código de Préstamo (*)</label>
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
                                <label class="form-label fw-medium">Tasa de Interés Mensual (%) (*)</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="tasa_interes" placeholder="1.80" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Cuotas Aprobadas (*)</label>
                                <input type="number" min="1" class="form-control" name="cuotas_aprobadas" placeholder="12" required>
                            </div>

                            <button type="submit" class="btn btn-custom w-100 py-2 font-weight-bold">Registrar Préstamo</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabla de Préstamos Registrados -->
            <div class="col-md-8">
                <div class="card card-custom p-4 bg-white">
                    <h5 class="mb-3 fw-bold" style="color: var(--color-0e1b48);">Cartera de Préstamos Vigentes</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Código</th>
                                    <th>Cliente</th>
                                    <th>Monto</th>
                                    <th>Saldo Pendiente</th>
                                    <th>Tasa</th>
                                    <th>Cuotas</th>
                                    <th>Aprobación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($prestamos) > 0): ?>
                                    <?php foreach ($prestamos as $p): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($p['id_prestamo']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($p['nomb_cli'] . ' ' . $p['apelli_cli']); ?></td>
                                            <td class="fw-bold text-success">$<?php echo number_format($p['monto_aprobado'], 2); ?></td>
                                            <td class="fw-bold text-danger">$<?php echo number_format($p['saldo_pendiente'], 2); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo $p['tasa_interes']; ?>%</span></td>
                                            <td><?php echo $p['cuotas_aprobadas']; ?> mes(es)</td>
                                            <td><small class="text-muted"><?php echo $p['fecha_aprobacion']; ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No se encontraron registros de préstamos en el sistema.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
