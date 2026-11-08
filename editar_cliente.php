<?php
// editar_cliente.php - Listado, buscador y actualización de clientes para cajeros/admins
session_start();
require_once 'conexionDB.php';

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['id_cliente'] ?? null;
$error = "";
$mensaje = "";

// Si es un cliente común, él solo edita sus propios datos directamente
if ($rol === 'cliente') {
    // Si viene un ID por GET para actualizar (o usa su propio ID)
    $id_a_editar = $usuario_id;
    
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
    // SI ES CAJERO O ADMINISTRADOR: Maneja la búsqueda y el listado general de clientes con sus cuentas
    $busqueda = trim($_GET['q'] ?? '');
    
    try {
        if ($busqueda !== '') {
            $stmt = $conexion->prepare("
                SELECT DISTINCT c.*, cu.num_cuenta, tc.nomb_tipo_cuenta 
                FROM clientes c
                LEFT JOIN cuentas cu ON c.id_cliente = cu.id_cliente
                LEFT JOIN tipo_cuentas tc ON cu.cod_tipo_cuenta = tc.cod_tipo_cuenta
                WHERE c.nomb_cli ILIKE ? OR c.apelli_cli ILIKE ? OR c.id_cliente::text ILIKE ?
                ORDER BY c.id_cliente DESC
            ");
            $like = "%" . $busqueda . "%";
            $stmt->execute([$like, $like, $like]);
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión y Actualización de Clientes - Banco Villavicencio</title>
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
            <!-- VISTA PARA CLIENTE: Edita sus propios datos -->
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
            <!-- VISTA PARA CAJERO / ADMINISTRADOR: Listado y Buscador de Clientes -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold" style="color: var(--color-0e1b48);">Gestión y Actualización de Clientes</h2>
                    <p class="text-muted mb-0">Busca un cliente por nombre o ID y selecciona actualizar sus datos.</p>
                </div>
                <a href="index.php" class="btn btn-custom px-4 py-2">← Volver al Panel</a>
            </div>

            <?php if ($error): ?><div class="alert alert-danger rounded-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

            <!-- Barra de Búsqueda -->
            <div class="card card-custom p-3 bg-white mb-4">
                <form method="GET" class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Buscar por nombre, apellido o ID de cliente..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                    <button class="btn btn-dark px-4" type="submit">Buscar</button>
                    <?php if (!empty($_GET['q'])): ?>
                        <a href="editar_cliente.php" class="btn btn-outline-secondary">Limpiar</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tabla de Clientes con Cuentas -->
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
                                                <span class="text-muted small">Sin cuenta</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($cli['nomb_tipo_cuenta'] ?? 'N/A'); ?></td>
                                        <td class="text-center">
                                            <!-- Enlace que permite editar los datos de este cliente específico -->
                                            <a href="editar_cliente_form.php?id=<?php echo $cli['id_cliente']; ?>" class="btn btn-sm btn-outline-dark px-3 rounded-pill">
                                                Actualizar Datos
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No se encontraron clientes registrados o coincidentes con la búsqueda.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
