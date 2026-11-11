<?php
session_start();
require_once "conexionDB.php";
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'cajero'])) {
    header("Location: login.php");
    exit;
}
$es_admin= ($_SESSION['rol'] === 'administrador'); 
$busqueda = trim($_GET['busqueda'] ?? '');

try {
    if ($busqueda !== '') {
        $stmt = $conexion->prepare("
            SELECT id_cliente, nomb_cli, apelli_cli, direccion, telefono, correo, fecha_registro 
            FROM clientes 
            WHERE CAST(id_cliente AS TEXT) ILIKE :busq 
               OR nomb_cli ILIKE :busq 
               OR apelli_cli ILIKE :busq 
               OR correo ILIKE :busq
            ORDER BY fecha_registro DESC
        ");
        $stmt->execute([':busq' => '%' . $busqueda . '%']);
    } else {
        $stmt = $conexion->query("SELECT id_cliente, nomb_cli, apelli_cli, direccion, telefono, correo, fecha_registro FROM clientes ORDER BY fecha_registro DESC");
    }
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar los clientes: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Clientes - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-0e1b48: #0E1B48;
            --color-c18db4: #C18DB4;
            --color-e2cad8: #E2CAD8;
            --color-87a7d0: #87A7D0;
        }
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .text-brand { color: var(--color-0e1b48); }
        .btn-brand { background-color: var(--color-0e1b48); color: white; border: none; }
        .btn-brand:hover { background-color: #1a2f6c; color: white; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-brand">Listado de Clientes</h2>
                <p class="text-muted mb-0">Gestión y consulta de usuarios registrados</p>
            </div>
            <a href="Crearcliente.php" class="btn btn-brand rounded-pill px-4">+ Registrar Nuevo Cliente</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Formulario de búsqueda basado puramente en PHP/SQL -->
        <div class="card shadow-sm border-0 rounded-4 p-3 mb-4 bg-white">
            <form method="GET" action="listadeClientesRead.php" class="input-group">
                <span class="input-group-text bg-light border-0">🔍</span>
                <input type="text" name="busqueda" class="form-control border-0 bg-light" placeholder="Buscar por cédula, nombre, apellido o correo..." value="<?php echo htmlspecialchars($busqueda); ?>">
                <button type="submit" class="btn btn-brand px-4">Buscar</button>
                <?php if ($busqueda !== ''): ?>
                    <a href="listadeClientesRead.php" class="btn btn-outline-secondary">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card shadow-sm border-0 rounded-4 bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 ps-4">Cédula / ID</th>
                            <th class="py-3">Nombre Completo</th>
                            <th class="py-3">Dirección</th>
                            <th class="py-3">Teléfono</th>
                            <th class="py-3">Correo</th>
                            <th class="py-3">Fecha Registro</th>
                            <th class="py-3 text-end pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($clientes)): ?>
                            <?php foreach ($clientes as $c): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-brand"><?php echo htmlspecialchars($c['id_cliente']); ?></td>
                                    <td><?php echo htmlspecialchars($c['nomb_cli'] . ' ' . $c['apelli_cli']); ?></td>
                                    <td><?php echo htmlspecialchars($c['direccion']); ?></td>
                                    <td><?php echo htmlspecialchars($c['telefono']); ?></td>
                                    <td><?php echo htmlspecialchars($c['correo']); ?></td>
                                    <td><?php echo htmlspecialchars($c['fecha_registro']); ?></td>
                                    <td class="text-end pe-4">
                                        <a href="editar_cliente.php?id=<?php echo $c['id_cliente']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Editar</a>
                                        <a href="eliminar_cliente.php?id=<?php echo $c['id_cliente']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('¿Estás seguro de eliminar este cliente?');">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No se encontraron clientes que coincidan con la búsqueda.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
