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
    <title>Panel Principal - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Iconos de Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --color-0e1b48: #0E1B48;
            --color-c18db4: #C18DB4;
            --color-e2cad8: #E2CAD8;
            --color-87a7d0: #87A7D0;
            --color-27425d: #27425D;
        }

        body {
            background: linear-gradient(135deg, #f4f6f9 0%, var(--color-e2cad8) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Banner de Bienvenida */
        .welcome-hero {
            background: linear-gradient(135deg, var(--color-0e1b48) 0%, var(--color-27425d) 100%);
            border-radius: 20px;
            color: white;
            box-shadow: 0 10px 30px rgba(14, 27, 72, 0.2);
            position: relative;
            overflow: hidden;
        }

        .welcome-hero::after {
            content: '';
            position: absolute;
            right: -50px;
            bottom: -50px;
            width: 200px;
            height: 200px;
            background: rgba(193, 141, 180, 0.15);
            border-radius: 50%;
            pointer-events: none;
        }

        /* Tarjetas de Menú */
        .card-menu {
            border: none;
            border-radius: 18px;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
            box-shadow: 0 6px 18px rgba(14, 27, 72, 0.05);
            border-top: 4px solid var(--color-87a7d0);
        }

        .card-menu:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 28px rgba(14, 27, 72, 0.12);
            border-top-color: var(--color-c18db4);
        }

        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background-color: rgba(135, 167, 208, 0.15);
            color: var(--color-0e1b48);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 1rem;
        }

        /* Botones personalizados */
        .btn-custom {
            background-color: var(--color-0e1b48);
            color: #ffffff;
            border-radius: 50rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-custom:hover {
            background-color: var(--color-27425d);
            color: #ffffff;
            transform: scale(1.01);
        }

        .btn-outline-custom {
            border: 1.5px solid var(--color-27425d);
            color: var(--color-27425d);
            border-radius: 50rem;
            font-weight: 600;
            background: transparent;
            transition: all 0.2s ease;
        }

        .btn-outline-custom:hover {
            background-color: var(--color-27425d);
            color: #ffffff;
        }
    </style>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        
        <!-- Hero de Bienvenida -->
        <div class="welcome-hero p-4 p-md-5 mb-5">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <span class="badge bg-light text-dark mb-2 px-3 py-2 rounded-pill fw-bold">
                        <i class="bi bi-shield-check text-primary me-1"></i> Sesión Activa
                    </span>
                    <h1 class="display-5 fw-bold mb-2">Bienvenido,</h1>
                    <p class="fs-5 mb-0 opacity-75">
                        Estás operando bajo el perfil de <span class="badge text-bg-warning text-uppercase px-3 py-2"><?php echo htmlspecialchars($rol); ?></span>
                    </p>
                </div>
            </div>
        </div>

        <?php if ($rol === 'cajero'): ?>
            <!-- SECCIÓN CAJERO -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h3 class="fw-bold m-0" style="color: var(--color-0e1b48);">
                    <i class="bi bi-display me-2"></i>Ventanilla y Operaciones de Caja
                </h3>
            </div>

            <div class="row g-4">
                <!-- Tarjeta 1: Gestión de Clientes -->
                <div class="col-md-6 col-lg-3">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <div class="icon-box">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Gestión de Clientes</h5>
                        <p class="text-muted small flex-grow-1">Registrar nuevos clientes, actualizar datos y realizar búsquedas.</p>
                        
                        <a href="Crearcliente.php" class="btn btn-sm btn-custom w-100 mb-2 py-2">
                            <i class="bi bi-person-plus-fill me-1"></i> Nuevo Cliente
                        </a>
                        <form action="editar_cliente.php" method="GET" class="w-100">
                            <button type="submit" class="btn btn-sm btn-outline-custom w-100 py-2">
                                <i class="bi bi-pencil-square me-1"></i> Actualizar Datos
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tarjeta 2: Cuentas y Préstamos -->
                <div class="col-md-6 col-lg-3">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <div class="icon-box">
                            <i class="bi bi-credit-card-2-front-fill"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Cuentas y Préstamos</h5>
                        <p class="text-muted small flex-grow-1">Abrir o modificar cuentas, registrar o actualizar préstamos.</p>
                        
                        <a href="crear_cuenta.php" class="btn btn-sm btn-custom w-100 mb-2 py-2">
                            <i class="bi bi-plus-circle-fill me-1"></i> Abrir Cuenta
                        </a>
                        <a href="gestion_prestamos.php" class="btn btn-sm btn-outline-custom w-100 py-2">
                            <i class="bi bi-bank me-1"></i> Gestionar Préstamos
                        </a>
                    </div>
                </div>

                <!-- Tarjeta 3: Ventanilla de Transacciones -->
                <div class="col-md-6 col-lg-3">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <div class="icon-box">
                            <i class="bi bi-arrow-left-right"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Ventanilla Transacciones</h5>
                        <p class="text-muted small flex-grow-1">Realizar depósitos/retiros y consultar historial de operaciones.</p>
                        
                        <a href="Crear-RealizarTransacciones.php" class="btn btn-sm btn-custom w-100 mb-2 py-2">
                            <i class="bi bi-cash-stack me-1"></i> Hacer Transacción
                        </a>
                        <a href="admin_bitacora.php" class="btn btn-sm btn-outline-custom w-100 py-2">
                            <i class="bi bi-journal-text me-1"></i> Bitácora
                        </a>
                    </div>
                </div>

                <!-- Tarjeta 4: Consultas de Red -->
                <div class="col-md-6 col-lg-3">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <div class="icon-box">
                            <i class="bi bi-diagram-3-fill"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Consultas de Red</h5>
                        <p class="text-muted small flex-grow-1">Consultar sucursales, cajeros, tipos de cuenta y transacciones.</p>
                        
                        <a href="admin_configuraciones.php" class="btn btn-sm btn-custom w-100 py-2 mt-auto">
                            <i class="bi bi-building me-1"></i> Ver Red Bancaria
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- SECCIÓN CLIENTE -->
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h3 class="fw-bold m-0" style="color: var(--color-0e1b48);">
                    <i class="bi bi-wallet2 me-2"></i>Mi Portal Financiero
                </h3>
            </div>

            <div class="row g-4">
                <!-- Tarjeta 1: Mis Datos Personales -->
                <div class="col-md-6 col-lg-3">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <div class="icon-box"><i class="bi bi-person-badge-fill"></i></div>
                        <h5 class="fw-bold text-dark">Mis Datos Personales</h5>
                        <p class="text-muted small flex-grow-1">Consulta tu información registrada y mantén actualizados tus datos.</p>
                        <a href="editar_cliente.php" class="btn btn-sm btn-custom w-100 py-2 mt-auto">
                            <i class="bi bi-pencil-fill me-1"></i> Ver / Editar Datos
                        </a>
                    </div>
                </div>

                <!-- Tarjeta 2: Mis Cuentas y Saldos -->
                <div class="col-md-6 col-lg-3">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <div class="icon-box"><i class="bi bi-cash-coin"></i></div>
                        <h5 class="fw-bold text-dark">Mis Cuentas y Saldos</h5>
                        <p class="text-muted small flex-grow-1">Consulta el estado actual de tus cuentas y fondos disponibles.</p>
                        <a href="estado_cuentas.php" class="btn btn-sm btn-custom w-100 py-2 mt-auto">
                            <i class="bi bi-eye-fill me-1"></i> Consultar Saldos
                        </a>
                    </div>
                </div>

                <!-- Tarjeta 3: Transferencias -->
                <div class="col-md-6 col-lg-3">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <div class="icon-box"><i class="bi bi-send-fill"></i></div>
                        <h5 class="fw-bold text-dark">Transferencias</h5>
                        <p class="text-muted small flex-grow-1">Realizar transferencias y revisar el historial de transacciones.</p>
                        
                        <div class="dropdown w-100 mt-auto">
                            <button class="btn btn-sm btn-custom w-100 dropdown-toggle py-2" type="button" data-bs-toggle="dropdown">
                                Opciones
                            </button>
                            <ul class="dropdown-menu shadow border-0 mt-2 p-2 w-100">
                                <li>
                                    <a class="dropdown-item rounded py-2" href="Crear-RealizarTransacciones.php">
                                        <i class="bi bi-arrow-right-circle me-2"></i>Hacer transferencia
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item rounded py-2" href="historial_transacciones.php">
                                        <i class="bi bi-clock-history me-2"></i>Ver historial
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta 4: Servicios Bancarios -->
                <div class="col-md-6 col-lg-3">
                    <div class="card card-menu p-4 bg-white h-100 d-flex flex-column">
                        <div class="icon-box"><i class="bi bi-info-circle-fill"></i></div>
                        <h5 class="fw-bold text-dark">Servicios Bancarios</h5>
                        <p class="text-muted small flex-grow-1">Consultar tipos de cuenta y servicios disponibles.</p>
                        <a href="admin_configuraciones.php" class="btn btn-sm btn-custom w-100 py-2 mt-auto">
                            <i class="bi bi-building me-1"></i> Ver Info
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
