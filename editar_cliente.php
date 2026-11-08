<?php
// editar_cliente.php - Listado, buscador y formulario de actualización/creación de cuentas
session_start();
require_once 'conexionDB.php';

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$error = "";
$mensaje = "";

// Si es un cliente común, redirigir o manejar sus propios datos
if ($rol === 'cliente') {
    $usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['id_cliente'] ?? null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nomb = $_POST['nomb_cli'] ?? '';
        $apelli = $_POST['apelli_cli'] ?? '';
        $tel = $_POST['tel_cli'] ?? '';
        $dir = $_POST['dir_cli'] ?? '';
        try {
            $stmt = $conexion->prepare("UPDATE clientes SET nomb_cli = ?, apelli_cli = ?, tel_cli = ?, dir_cli = ? WHERE id_cliente = ?");
            $stmt->execute([$nomb, $apelli, $tel, $dir, $usuario_id]);
            $mensaje = "¡Tus datos han sido actualizados exitosamente!";
        } catch (PDOException $e) {
            $error = "Error al actualizar: " . $e->getMessage();
        }
    }
    try {
        $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
        $stmt->execute([$usuario_id]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error al cargar datos: " . $e->getMessage();
    }
} 
else {
    // SI ES CAJERO O ADMINISTRADOR
    $id_cliente = $_GET['id'] ?? null;

    // Si se envió un formulario POST dentro de la edición de un cliente específico
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_cliente) {
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
            $num_cuenta = '99' . mt_rand(10000000, 99999999);

            try {
                $stmt = $conexion->prepare("
                    INSERT INTO cuentas (num_cuenta, id_cliente, cod_tipo_cuenta, saldo, fecha_apertura) 
                    VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$num_cuenta, $id_cliente, $cod_tipo_cuenta, $saldo_inicial]);
                $mensaje = "¡Cuenta bancaria creada exitosamente (N°: " . $num_cuenta . ")!";
            } catch (PDOException $e) {
                $error = "Error al crear la cuenta: " . $e->getMessage();
            }
        }
    }

    // Si hay un ID en la URL, mostramos el panel de edición detallada de ese cliente
    $cliente_a_editar = null;
    $cuentas_cliente = [];
    $tipos_cuenta = [];

    if ($id_cliente) {
        try {
            $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
            $stmt->execute([$id_cliente]);
            $cliente_a_editar = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cliente_a_editar) {
                $stmt_cuentas = $conexion->prepare("
                    SELECT cu.*, tc.nomb_tipo_cuenta 
                    FROM cuentas cu 
                    JOIN tipo_cuentas tc ON cu.cod_tipo_cuenta = tc.cod_tipo_cuenta 
                    WHERE cu.id_cliente = ?
                ");
                $stmt_cuentas->execute([$id_cliente]);
                $cuentas_cliente = $stmt_cuentas->fetchAll(PDO::FETCH_ASSOC);

                $tipos_cuenta = $conexion->query("SELECT * FROM tipo_cuentas")->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error = "Error al obtener la información del cliente: " . $e->getMessage();
        }
    } else {
        // Si NO hay ID, mostramos la tabla general con el buscador de clientes
        $busqueda = trim($_GET['q'] ?? '');
        try {
            if ($busqueda !== '') {
                $stmt = $conexion->prepare("
                    SELECT DISTINCT c.*, cu.num_cuenta, tc.nomb_tipo_cuenta 
                    FROM clientes c
                    LEFT JOIN cuentas cu ON c.id_cliente = cu.id_cliente
                    LEFT JOIN tipo_cuentas tc ON cu.cod_tipo_cuenta = tc.cod_tipo_cuenta
                    WHERE LOWER(c.nomb_cli) LIKE LOWER(?) OR LOWER(c.apelli_cli) LIKE LOWER(?)
                    ORDER BY c.id_cliente DESC
                ");
                $like = "%" . $busqueda . "%";
                $stmt->execute([$like, $like]);
            } else {
                $stmt = $conexion->query("
                    SELECT DISTINCT c.*, cu.num_cuenta, tc.nomb_tipo_cuenta 
                    FROM clientes c
                    LEFT JOIN cuentas cu ON c.id_cliente = cu.id_cliente
                    LEFT JOIN tipo_cuentas tc ON cu.cod_tipo_cuenta = tc.cod_tipo_cuenta
                    ORDER BY c.id_cliente DESC
                ");
            }
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Error al obtener el listado de clientes: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Clientes - Banco Villavicencio</title>
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
        
        <?php if ($rol === 'cliente'): ?>
            <!-- VISTA CLIENTE -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold" style="color: var(--color-0e1b48);">Mis Datos Personales</h2>
                    <p class="text-muted mb-0">Actualiza tu información de contacto.</p>
                </div>
                <a href="index.php" class="btn btn-custom px-4 py-2">← Volver al Panel</a>
            </div>

            <?php if ($error): ?><div class="alert alert-danger rounded-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($mensaje): ?><div class="alert alert-success rounded-4"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

            <div class="card card-custom p-4 bg-white">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombres</label>
                        <input type="text" name="nomb_cli" class="form-control" value="<?php echo htmlspecialchars($cliente['nomb_cli'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Apellidos</label>
                        <input type="text" name="apelli_cli" class="form-control" value="<?php echo htmlspecialchars($cliente['apelli_cli'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Teléfono</label>
                        <input type="text" name="tel_cli" class="form-control" value="<?php echo htmlspecialchars($cliente['tel_cli'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Dirección</label>
                        <input type="text" name="dir_cli" class="form-control" value="<?php echo htmlspecialchars($cliente['dir_cli'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-custom w-100 py-2">Guardar Cambios</button>
                </form>
            </div>

        <?php else: ?>
            <!-- VISTA CAJERO / ADMIN -->
            
            <?php if ($id_cliente && $cliente_a_editar): ?>
                <!-- FORMULARIO DE EDICIÓN Y CREACIÓN DE CUENTA PARA UN CLIENTE ESPECÍFICO -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold" style="color: var(--color-0e1b48);">Gestión Integral de Cliente</h2>
                        <p class="text-muted mb-0">Cliente: <strong><?php echo htmlspecialchars($cliente_a_editar['nomb_cli'] . ' ' . $cliente_a_editar['apelli_cli']); ?></strong> (ID: #<?php echo $cliente_a_editar['id_cliente']; ?>)</p>
                    </div>
                    <a href="editar_cliente.php" class="btn btn-custom px-4 py-2">← Volver al Listado</a>
                </div>

                <?php if ($error): ?><div class="alert alert-danger rounded-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <?php if ($mensaje): ?><div class="alert alert-success rounded-4"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

                <div class="row g-4">
                    <!-- Columna 1: Actualizar Datos Personales -->
                    <div class="col-md-6">
                        <div class="card card-custom p-4 bg-white h-100">
                            <h4 class="fw-bold mb-3" style="color: var(--color-0e1b48);">Datos Personales</h4>
                            <form method="POST">
                                <input type="hidden" name="accion" value="actualizar_datos">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nombres</label>
                                    <input type="text" name="nomb_cli" class="form-control" value="<?php echo htmlspecialchars($cliente_a_editar['nomb_cli']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Apellidos</label>
                                    <input type="text" name="apelli_cli" class="form-control" value="<?php echo htmlspecialchars($cliente_a_editar['apelli_cli']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Teléfono</label>
                                    <input type="text" name="tel_cli" class="form-control" value="<?php echo htmlspecialchars($cliente_a_editar['tel_cli'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Dirección</label>
                                    <input type="text" name="dir_cli" class="form-control" value="<?php echo htmlspecialchars($cliente_a_editar['dir_cli'] ?? ''); ?>">
                                </div>
                                <button type="submit" class="btn btn-custom w-100 py-2">Guardar Cambios Personales</button>
                            </form>
                        </div>
                    </div>

                    <!-- Columna 2: Cuentas Asociadas y Crear Cuenta si no tiene -->
                    <div class="col-md-6">
                        <div class="card card-custom p-4 bg-white h-100 d-flex flex-column">
                            <h4 class="fw-bold mb-3" style="color: var(--color-0e1b48);">Cuentas Bancarias</h4>
                            
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
                                <p class="text-muted small mt-auto">Este cliente ya posee cuentas activas en el sistema.</p>
                            <?php else: ?>
                                <div class="alert alert-warning rounded-4 mb-3">
                                    Este cliente <strong>no tiene ninguna cuenta bancaria</strong> asociada.
                                </div>

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

            <?php else: ?>
                <!-- LISTADO GENERAL Y BUSCADOR -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold" style="color: var(--color-0e1b48);">Gestión y Actualización de Clientes</h2>
                        <p class="text-muted mb-0">Busca un cliente y haz clic en "Actualizar Datos" para editarlo o asignarle una cuenta.</p>
                    </div>
                    <a href="index.php" class="btn btn-custom px-4 py-2">← Volver al Panel</a>
                </div>

                <?php if ($error): ?><div class="alert alert-danger rounded-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                <div class="card card-custom p-3 bg-white mb-4">
                    <form method="GET" class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Buscar por nombre o apellido..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                        <button class="btn btn-dark px-4" type="submit">Buscar</button>
                        <?php if (!empty($_GET['q'])): ?>
                            <a href="editar_cliente.php" class="btn btn-outline-secondary">Limpiar</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card card-custom p-4 bg-white">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Teléfono</th>
                                    <th>N° Cuenta Asociada</th>
                                    <th>Tipo de Cuenta</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($clientes ?? []) > 0): ?>
                                    <?php foreach ($clientes as $cli): ?>
                                        <tr>
                                            <td>#<?php echo htmlspecialchars($cli['id_cliente']); ?></td>
                                            <td class="fw-bold"><?php echo htmlspecialchars($cli['nomb_cli'] . ' ' . $cli['apelli_cli']); ?></td>
                                            <td><?php echo htmlspecialchars($cli['tel_cli'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php if (!empty($cli['num_cuenta'])): ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($cli['num_cuenta']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-danger small fw-bold">Sin cuenta</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($cli['nomb_tipo_cuenta'] ?? 'N/A'); ?></td>
                                            <td class="text-center">
                                                <a href="editar_cliente.php?id=<?php echo $cli['id_cliente']; ?>" class="btn btn-sm btn-outline-dark px-3 rounded-pill">
                                                    Actualizar Datos
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No se encontraron clientes registrados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</body>
</html>
</html>
