<?php
session_start();
require_once "conexionDB.php";

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'cajero'])) {
    header("Location: login.php");
    exit;
}

$es_admin = ($_SESSION['rol'] === 'administrador'); // solo el administrador ve el estado de la contraseña

try {
    $sentencia = $conexion->query("SELECT * FROM clientes ORDER BY nomb_cli ASC");
    $clientes  = $sentencia->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al consultar clientes: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Clientes - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
    <?php if (file_exists("navbar.php")) include_once "navbar.php"; ?>

    <div class="container mt-4 mb-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow border-0 rounded-4">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                        <h4 class="mb-0 fs-5">Lista de Clientes - Banco Villavicencio</h4>
                        <div>
                            <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill me-2">Volver al Panel</a>
                            <a href="Crearcliente.php" class="btn btn-success btn-sm rounded-pill">Registrar Nuevo Cliente</a>
                        </div>
                    </div>
                    
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Cédula / ID</th>
                                        <th>Nombre</th>
                                        <th>Apellido</th>
                                        <th>Dirección</th>
                                        <th>Teléfono</th>
                                        <th>Correo</th>
                                        <?php if ($es_admin): ?><th>Contraseña</th><?php endif; ?>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($clientes)): ?>
                                        <?php foreach($clientes as $cliente): ?>
                                            <!-- Añadimos un ID dinámico a la fila para poder borrarla con JS -->
                                            <tr id="fila-<?php echo $cliente['id_cliente']; ?>">
                                                <td><strong><?php echo htmlspecialchars($cliente['id_cliente']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($cliente['nomb_cli']); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['apelli_cli']); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['direccion'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['telefono'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($cliente['correo'] ?? 'N/A'); ?></td>
                                                <?php if ($es_admin): ?>
                                                    <td>
                                                        <?php if (!empty($cliente['password'])): ?>
                                                            <span class="badge bg-success">Establecida</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">Vacía</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <td>
                                                    <a href="editar_cliente.php?id=<?php echo urlencode($cliente['id_cliente']); ?>" class="btn btn-warning btn-sm rounded-pill">Editar</a>
                                                    <button type="button" class="btn btn-danger btn-sm rounded-pill" onclick="confirmarEliminacion('<?php echo $cliente['id_cliente']; ?>')">Eliminar</button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr id="fila-vacia">
                                            <td colspan="<?php echo $es_admin ? 8 : 7; ?>" class="text-center text-muted py-4">No hay clientes registrados en la base de datos.</td>
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
    
    <!-- Script AJAX para eliminar sin recargar -->
    <script>
    function confirmarEliminacion(idCliente) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡Esta acción eliminará al cliente del sistema!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-4 shadow'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Petición AJAX (Fetch API)
                fetch('eliminar_cliente.php?id=' + idCliente, {
                    method: 'GET'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Eliminar la fila de la tabla visualmente con una transición suave
                        const fila = document.getElementById('fila-' + idCliente);
                        if (fila) {
                            fila.style.transition = "all 0.5s ease";
                            fila.style.opacity = "0";
                            setTimeout(() => fila.remove(), 500);
                        }
                        
                        Swal.fire(
                            '¡Eliminado!',
                            'El cliente ha sido borrado exitosamente.',
                            'success'
                        );
                    } else {
                        // Mostrar error si tiene cuentas o préstamos amarrados
                        Swal.fire(
                            'No se pudo eliminar',
                            data.message,
                            'error'
                        );
                    }
                })
                .catch(error => {
                    Swal.fire(
                        'Error',
                        'Ocurrió un error de conexión con el servidor.',
                        'error'
                    );
                });
            }
        });
    }
    </script>
</body>
</html>
