<?php
// index.php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$nombres = $_SESSION['nombres'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-0e1b48: #0E1B48;
            --color-c18db4: #C18DB4;
            --color-e2cad8: #E2CAD8;
            --color-87a7d0: #87A7D0;
            --color-27425d: #27425D;
        }
        body { 
            background: linear-gradient(135deg, #f8f9fa 0%, var(--color-e2cad8) 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            min-height: 100vh;
        }
        .dashboard-header {
            background-color: var(--color-0e1b48);
            color: #ffffff;
            border-radius: 16px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(14, 27, 72, 0.15);
        }
        .card-menu {
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-top: 4px solid var(--color-c18db4);
        }
        .card-menu:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.1);
        }
        .btn-custom {
            background-color: var(--color-0e1b48);
            color: #ffffff;
            border-radius: 50rem;
            font-weight: 600;
        }
        .btn-custom:hover {
            background-color: var(--color-27425d);
            color: #ffffff;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container py-5 mt-4">
        
        <!-- CABECERA -->
        <div class="dashboard-header">
            <h1 class="fw-bold mb-2">Bienvenido, <?php echo htmlspecialchars($nombres); ?></h1>
            <p class="mb-0 fs-5" style="color: var(--color-87a7d0);">Estás operando bajo el perfil de <strong><?php echo strtoupper($rol); ?></strong>.</p>
        </div>

        <!-- MÓDULOS DE ADMINISTRADOR -->
        <?php if ($rol === 'administrador'): ?>
            <h4 class="fw-bold mb-4" style="color: var(--color-0e1b48);">Gestión y Administración del Sistema</h4>
            
            <div class="row g-4 mb-5">
                <!-- Gestión de Empleados -->
                <div class="col-md-3">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold" style="color: var(--color-27425d);">Empleados</h5>
                        <p class="text-muted small">Crear, editar o eliminar usuarios y cajeros del sistema.</p>
                        <a href="gestion_empleados.php" class="btn btn-sm btn-custom mt-auto w-100">Administrar Usuarios</a>
                    </div>
                </div>

                <!-- Gestión Bancaria -->
                <div class="col-md-3">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold" style="color: var(--color-27425d);">Clientes y Productos</h5>
                        <p class="text-muted small">Modificar o eliminar clientes, cuentas bancarias y préstamos.</p>
                        <a href="estado_cuentas.php" class="btn btn-sm btn-custom mt-auto w-100">Gestión Bancaria</a>
                    </div>
                </div>

                <!-- Configuraciones Maestras -->
                <div class="col-md-3">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold" style="color: var(--color-27425d);">Configuraciones</h5>
                        <p class="text-muted small">Gestión de sucursales, ciudades, cajeros y tipos de cuenta.</p>
                        <a href="admin_configuraciones.php" class="btn btn-sm btn-custom mt-auto w-100">Parámetros del Banco</a>
                    </div>
                </div>

                <!-- Auditoría y Bitácora -->
                <div class="col-md-3">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold" style="color: var(--color-27425d);">Auditoría</h5>
                        <p class="text-muted small">Revisión de la bitácora global de transacciones y seguridad.</p>
                        <a href="admin_bitacora.php" class="btn btn-sm btn-custom mt-auto w-100">Ver Bitácora</a>
                    </div>
                </div>
            </div>

        <!-- MÓDULOS DE CAJERO -->
        <?php elseif ($rol === 'cajero'): ?>
            <!-- Aquí mantienes los módulos de cajero que ya tenías diseñados (Crearcliente, editar_cliente, crear_cuenta, Crear-RealizarTransacciones) -->
        
        <!-- MÓDULOS DE CLIENTE -->
        <?php else: ?>
            <!-- Módulos de cliente estándar -->
        <?php endif; ?>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
