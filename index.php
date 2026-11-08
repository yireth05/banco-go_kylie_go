<?php
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
            --color-0e1b48: #0E1B48; --color-c18db4: #C18DB4;
            --color-e2cad8: #E2CAD8; --color-87a7d0: #87A7D0;
            --color-27425d: #27425D;
        }
        body { 
            background: linear-gradient(135deg, #f8f9fa 0%, var(--color-e2cad8) 100%);
            min-height: 100vh;
        }
        .dashboard-header {
            background-color: var(--color-0e1b48); color: #ffffff;
            border-radius: 16px; padding: 2rem; margin-bottom: 2rem;
        }
        .card-menu {
            border-radius: 16px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-top: 4px solid var(--color-c18db4); transition: transform 0.2s;
        }
        .card-menu:hover { transform: translateY(-4px); }
        .btn-custom { background-color: var(--color-0e1b48); color: #ffffff; border-radius: 50rem; font-weight: 600; }
        .btn-custom:hover { background-color: var(--color-27425d); color: #ffffff; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container py-4 mt-3">
        
        <div class="dashboard-header">
            <h1 class="fw-bold mb-1">Bienvenido, <?php echo htmlspecialchars($nombres); ?></h1>
            <p class="mb-0 fs-5" style="color: var(--color-87a7d0);">Estás operando bajo el perfil de <strong><?php echo strtoupper($rol); ?></strong>.</p>
        </div>

        <!-- PERFIL ADMINISTRADOR -->
        <?php if ($rol === 'administrador'): ?>
            <h4 class="fw-bold mb-3" style="color: var(--color-0e1b48);">Módulos del Administrador</h4>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Gestión de Usuarios</h5>
                        <p class="text-muted small">Crear, editar o eliminar usuarios y empleados del sistema.</p>
                        <a href="listadeClientesRead.php" class="btn btn-sm btn-custom mt-auto w-100">Administrar Usuarios</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Clientes, Cuentas y Préstamos</h5>
                        <p class="text-muted small">Ver, modificar o eliminar clientes, cuentas bancarias y préstamos.</p>
                        <a href="estado_cuentas.php" class="btn btn-sm btn-custom mt-auto w-100">Administrar Cuentas</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Configuraciones del Banco</h5>
                        <p class="text-muted small">Sucursales, ciudades, cajeros, tipos de cuenta y transacciones.</p>
                        <a href="admin_configuraciones.php" class="btn btn-sm btn-custom mt-auto w-100">Ver Configuraciones</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Auditoría y Bitácora</h5>
                        <p class="text-muted small">Bitácora de transacciones en tiempo real y auditoría del sistema.</p>
                        <a href="admin_bitacora.php" class="btn btn-sm btn-custom mt-auto w-100">Ver Bitácora</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Gestión de Préstamos</h5>
                        <p class="text-muted small">Aprobación, registro y control general de créditos.</p>
                        <a href="gestion_prestamos.php" class="btn btn-sm btn-custom mt-auto w-100">Ver Préstamos</a>
                    </div>
                </div>
            </div>

        <!-- PERFIL CAJERO -->
        <?php elseif ($rol === 'cajero'): ?>
            <h4 class="fw-bold mb-3" style="color: var(--color-0e1b48);">Ventanilla y Operaciones de Caja</h4>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Gestión de Clientes</h5>
                        <p class="text-muted small">Registrar nuevos clientes, actualizar datos y realizar búsquedas.</p>
                        <a href="Crearcliente.php" class="btn btn-sm btn-custom mt-auto w-100 mb-2">Nuevo Cliente</a>
                        <a href="editar_cliente.php" class="btn btn-sm btn-outline-secondary w-100">Actualizar Datos</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Cuentas y Préstamos</h5>
                        <p class="text-muted small">Abrir o modificar cuentas, registrar o actualizar préstamos.</p>
                        <a href="crear_cuenta.php" class="btn btn-sm btn-custom mt-auto w-100 mb-2">Abrir Cuenta</a>
                        <a href="gestion_prestamos.php" class="btn btn-sm btn-outline-secondary w-100">Gestionar Préstamos</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Ventanilla de Transacciones</h5>
                        <p class="text-muted small">Realizar depósitos/retiros y consultar historial de operaciones.</p>
                        <a href="Crear-RealizarTransacciones.php" class="btn btn-sm btn-custom mt-auto w-100 mb-2">Hacer Transacción</a>
                        <a href="admin_bitacora.php" class="btn btn-sm btn-outline-secondary w-100">Bitácora</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Consultas de Red</h5>
                        <p class="text-muted small">Consultar sucursales, cajeros, tipos de cuenta y transacciones.</p>
                        <a href="admin_configuraciones.php" class="btn btn-sm btn-custom mt-auto w-100">Ver Red Bancaria</a>
                    </div>
                </div>
            </div>

        <!-- PERFIL CLIENTE -->
        <?php else: ?>
            <h4 class="fw-bold mb-3" style="color: var(--color-0e1b48);">Mi Portal Financiero</h4>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Mis Datos Personales</h5>
                        <p class="text-muted small">Consulta tu información registrada y mantén actualizados tus datos.</p>
                        <a href="editar_cliente.php" class="btn btn-sm btn-custom mt-auto w-100">Ver / Editar Mis Datos</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Mis Cuentas y Saldos</h5>
                        <p class="text-muted small">Consulta el estado actual de tus cuentas y fondos disponibles.</p>
                        <a href="estado_cuentas.php" class="btn btn-sm btn-custom mt-auto w-100">Consultar Saldos</a>
                    </div>
                </div>
                <div class="col-md-4">
    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
        <h5 class="fw-bold text-dark">Transferencias y Movimientos</h5>
        <p class="text-muted small">Realizar transferencias y revisar el historial de transacciones.</p>
        
       <div class="dropdown mt-auto w-100">
    <button class="btn btn-sm btn-custom w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        Transferir / Ver Historial
    </button>
    <ul class="dropdown-menu shadow-sm border-0 mt-2 p-2 w-100" style="min-width: 220px;">
        <li>
            <a class="dropdown-item rounded py-2 fw-semibold text-dark text-wrap" href="Crear-RealizarTransacciones.php">
                <i class="bi bi-arrow-left-right me-2"></i> Hacer transferencia
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item rounded py-2 fw-semibold text-dark text-wrap" href="historial_transacciones.php">
                <i class="bi bi-clock-history me-2"></i> Ver su historial
            </a>
        </li>
    </ul>
</div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Servicios Bancarios</h5>
                        <p class="text-muted small">Consultar tipos de cuenta y tipos de transacciones disponibles.</p>
                        <a href="admin_configuraciones.php" class="btn btn-sm btn-custom mt-auto w-100">Ver Productos</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
