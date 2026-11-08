<?php
// editar_cliente.php - Edición de datos y creación de cuentas para cajeros/admins
session_start();
require_once 'conexionDB.php';

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$error = "";
$mensaje = "";

// Solo cajeros y administradores pueden acceder a este módulo de gestión
if ($rol === 'cliente') {
    header("Location: index.php");
    exit;
}

$id_cliente = $_GET['id'] ?? null;

if (!$id_cliente) {
    header("Location: index.php");
    exit;
}

// Procesar formularios enviados por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'actualizar_datos') {
        $nomb = $_POST['nomb_cli'] ?? '';
        $apelli = $_POST['apelli_cli'] ?? '';
        $tel = $_POST['tel_cli'] ?? '';
        $dir = $_POST['dir_cli'] ?? '';
        
        try {
            $stmt = $conexion->prepare("UPDATE clientes SET nomb_cli = ?, apelli_cli = ?, tel_cli = ?, dir_cli = ? WHERE id_cliente = ?");
            $stmt->execute([$nomb, $apelli, $tel, $dir, $id_cliente]);
            $mensaje = "¡Datos personales actualizados exitosamente!";
        } catch (PDOException $e) {
            $error = "Error al actualizar los datos: " . $e->getMessage();
        }
    } 
    elseif ($accion === 'crear_cuenta') {
        $cod_tipo_cuenta = $_POST['cod_tipo_cuenta'] ?? '';
        $saldo_inicial = $_POST['saldo_inicial'] ?? 0.00;
        
        // Generar un número de cuenta aleatorio único de 10 dígitos
        $num_cuenta = '99' . mt_rand(10000000, 99999999);

        try {
            $stmt = $conexion->prepare("
                INSERT INTO cuentas (num_cuenta, id_cliente, cod_tipo_cuenta, saldo, fecha_apertura) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$num_cuenta, $id_cliente, $cod_tipo_cuenta, $saldo_inicial]);
            $mensaje = "¡Cuenta bancaria creada exitosamente con el N°: " . $num_cuenta . "!";
        } catch (PDOException $e) {
            $error = "Error al crear la cuenta: " . $e->getMessage();
        }
    }
}

// Obtener la información actual del cliente
try {
    $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
    $stmt->execute([$id_cliente]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        header("Location: index.php");
        exit;
    }

    // Consultar si el cliente ya tiene cuentas asociadas
    $stmt_cuentas = $conexion->prepare("
        SELECT cu.*, tc.nomb_tipo_cuenta 
        FROM cuentas cu 
        JOIN tipo_cuentas tc ON cu.cod_tipo_cuenta = tc.cod_tipo_cuenta 
        WHERE cu.id_cliente = ?
    ");
    $stmt_cuentas->execute([$id_cliente]);
    $cuentas_cliente = $stmt_cuentas->fetchAll(PDO::FETCH_ASSOC);

    // Obtener los tipos de cuenta disponibles para el formulario de creación
    $tipos_cuenta = $conexion->query("SELECT * FROM tipo_cuentas")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error al cargar la información: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Cliente y Cuentas - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-0e1b48: #0E1B48; --color-c18db4: #C18DB4;
            --color-e2cad8: #E2CAD8; --color-87a7d0: #87A7D0;
            --color-27425d: #27425D;
        }
        body { background: linear-gradient(135deg, #f8f9fa 0%, var(--color-e2cad8) 100%); min-height: 100vh; }
        .card-custom { border-radius: 16px; border: none; box-shadow: 0 8px 24px rgba(14, 27, 72, 0.08); }
        .table-dark { background-color: var(--color-0e1b48); }
        .table-dark th { background-color: var(--color-0e1b48); color: #fff; }
        .btn-custom { background-color: var(--color-c18db4); color: #fff; border-radius: 50rem; font-weight: 600; }
        .btn-custom:hover { background-color: var(--color-87a7d0); color: var(--color-0e1b48); }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container py-5 mt-3">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">Gestión Integral de Cliente</h2>
                <p class="text-muted mb-0">Cliente: <strong><?php echo htmlspecialchars($cliente['nomb_cli'] . ' ' . $cliente['apelli_cli']); ?></strong> (ID: #<?php echo $cliente['id_cliente']; ?>)</p>
            </div>
            <a href="index.php" class="btn btn-custom px-4 py-2">← Volver al Panel</a>
        </div>

        <?php if ($error): ?><div class="alert alert-danger rounded-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($mensaje): ?><div class="alert alert-success rounded-4"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

        <div class="row g-4">
            <!-- COLUMNA 1: Formulario de Actualización de Datos Personales -->
            <div class="col-md-6">
                <div class="card card-custom p-4 bg-white h-100">
                    <h4 class="fw-bold mb-3" style="color: var(--color-0e1b48);">Datos Personales</h4>
                    <form method="POST">
                        <input type="hidden" name="accion" value="actualizar_datos">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombres</label>
                            <input type="text" name="nomb_cli" class="form-control" value="<?php echo htmlspecialchars($cliente['nomb_cli']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Apellidos</label>
                            <input type="text" name="apelli_cli" class="form-control" value="<?php echo htmlspecialchars($cliente['apelli_cli']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Teléfono</label>
                            <input type="text" name="tel_cli" class="form-control" value="<?php echo htmlspecialchars($cliente['tel_cli'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Dirección</label>
                            <input type="text" name="dir_cli" class="form-control" value="<?php echo htmlspecialchars($cliente['dir_cli'] ?? ''); ?>">
                        </div>
                        <button type="submit" class="btn btn-custom w-100 py-2">Guardar Cambios Personales</button>
                    </form>
                </div>
            </div>

            <!-- COLUMNA 2: Estado de Cuentas y Creación de Cuenta Nueva si no posee -->
            <div class="col-md-6">
                <div class="card card-custom p-4 bg-white h-100 d-flex flex-column">
                    <h4 class="fw-bold mb-3" style="color: var(--color-0e1b48);">Cuentas Bancarias Asociadas</h4>
                    
                    <?php if (count($cuentas_cliente) > 0): ?>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th>N° Cuenta</th>
                                        <th>Tipo</th>
                                        <th>Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cuentas_cliente as $cta): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($cta['num_cuenta']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($cta['nomb_tipo_cuenta']); ?></td>
                                            <td class="text-success fw-bold">$<?php echo number_format($cta['saldo'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted small mt-auto">Este cliente ya cuenta con productos financieros activos en el sistema.</p>
                    <?php else: ?>
                        <div class="alert alert-warning rounded-4 mb-3">
                            Este cliente <strong>no tiene ninguna cuenta bancaria</strong> asociada actualmente.
                        </div>

                        <!-- Formulario para crear una cuenta nueva -->
                        <form method="POST" class="mt-auto">
                            <input type="hidden" name="accion" value="crear_cuenta">
                            <h5 class="fw-bold text-dark mb-3">Asignar Nueva Cuenta</h5>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tipo de Cuenta</label>
                                <select name="cod_tipo_cuenta" class="form-select" required>
                                    <option value="">Seleccione el tipo...</option>
                                    <?php foreach ($tipos_cuenta as $tc): ?>
                                        <option value="<?php echo $tc['cod_tipo_cuenta']; ?>"><?php echo htmlspecialchars($tc['nomb_tipo_cuenta']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Saldo Inicial ($)</label>
                                <input type="number" step="0.01" name="saldo_inicial" class="form-control" value="0.00" min="0" required>
                            </div>
                            <button type="submit" class="btn btn-dark w-100 py-2">Crear Cuenta Bancaria</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</body>
</html>
