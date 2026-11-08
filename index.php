<?php
session_start();

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
$usuario = $_SESSION['usuario'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - Banco GO KYLIE GO</title>
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
            background-color: #f4f6f9;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .navbar-custom { background-color: var(--color-0e1b48); }
        .card-menu {
            border-radius: 16px;
            border: none;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-menu:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(14, 27, 72, 0.12);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-4 py-3">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">Banco GO KYLIE GO</a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-light small">Hola, <strong><?php echo htmlspecialchars($usuario); ?></strong> (Rol: <span class="badge bg-light text-dark"><?php echo ucfirst($rol); ?></span>)</span>
                <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="p-4 rounded-4 shadow-sm text-white" style="background: linear-gradient(135deg, var(--color-0e1b48), var(--color-27425d));">
                    <h1 class="fw-bold fs-3">Bienvenido al Panel de Control</h1>
                    <p class="mb-0 text-white-50">Estás operando bajo el perfil de <strong><?php echo strtoupper($rol); ?></strong>.</p>
                </div>
            </div>
        </div>

        <!-- VISTA ADMINISTRADOR -->
        <?php if ($rol === 'administrador'): ?>
            <h4 class="fw-bold mb-3" style="color: var(--color-0e1b48);">Gestión Administrativa y Auditoría</h4>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Gestión de Usuarios</h5>
                        <p class="text-muted small">Control de roles y accesos del sistema definidos en PostgreSQL.</p>
                        <a href="admin_usuarios.php" class="btn btn-sm btn-dark mt-auto rounded-pill">Administrar Usuarios</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Configuraciones del Banco</h5>
                        <p class="text-muted small">Gestión de sucursales, ciudades, cajeros y tipos de cuenta.</p>
                        <a href="admin_configuraciones.php" class="btn btn-sm btn-dark mt-auto rounded-pill">Ver Configuraciones</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Auditoría y Bitácora</h5>
                        <p class="text-muted small">Revisión de la bitácora global de transacciones y seguridad.</p>
                        <a href="admin_bitacora.php" class="btn btn-sm btn-dark mt-auto rounded-pill">Ver Auditoría</a>
                    </div>
                </div>
            </div>

        <!-- VISTA CAJERO -->
        <?php elseif ($rol === 'cajero'): ?>
            <h4 class="fw-bold mb-3" style="color: var(--color-0e1b48);">Ventanilla y Operaciones de Caja</h4>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Gestión de Clientes</h5>
                        <p class="text-muted small">Registrar nuevo cliente, actualizar datos y consultar perfiles.</p>
                        <a href="Crearcliente.php" class="btn btn-sm btn-primary mt-auto rounded-pill" style="background-color: var(--color-0e1b48);">Registrar Cliente</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Cuentas y Préstamos</h5>
                        <p class="text-muted small">Abrir nueva cuenta, modificar cuentas y registrar préstamos.</p>
                        <a href="crear_cuenta.php" class="btn btn-sm btn-primary mt-auto rounded-pill" style="background-color: var(--color-0e1b48);">Gestionar Cuentas</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Transacciones</h5>
                        <p class="text-muted small">Realizar transacciones en ventanilla y leer bitácoras de operaciones.</p>
                        <a href="Crear-RealizarTransacciones.php" class="btn btn-sm btn-primary mt-auto rounded-pill" style="background-color: var(--color-0e1b48);">Hacer Transacción</a>
                    </div>
                </div>
            </div>

        <!-- VISTA CLIENTE -->
        <?php elseif ($rol === 'cliente'): ?>
            <h4 class="fw-bold mb-3" style="color: var(--color-0e1b48);"> Mi Portal Bancario</h4>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Mis Datos Personales</h5>
                        <p class="text-muted small">Ver tu información personal registrada en el banco.</p>
                        <a href="cliente_datos.php" class="btn btn-sm btn-outline-dark mt-auto rounded-pill">Ver mis datos</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Mis Cuentas y Saldos</h5>
                        <p class="text-muted small">Consulta el saldo actual de tus cuentas y estado de productos.</p>
                        <a href="estado_cuentas.php" class="btn btn-sm btn-outline-dark mt-auto rounded-pill">Ver mis cuentas</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark">Transferencias</h5>
                        <p class="text-muted small">Realiza transferencias y revisa tu historial personal de movimientos.</p>
                        <a href="Crear-RealizarTransacciones.php" class="btn btn-sm btn-outline-dark mt-auto rounded-pill">Transferir Fondos</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
