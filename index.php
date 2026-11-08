<?php
session_start();
if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];

// Aquí puedes mantener tus consultas a la base de datos si las tienes,
// por ejemplo: $total_clientes = 5;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GO KYLIE GO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-1: #0E1B48; /* Azul Marino */
            --color-2: #C18DB4; /* Rosa Oscuro */
            --color-3: #E2CAD8; /* Rosa Claro */
            --color-4: #87A7D0; /* Azul Claro */
            --color-5: #27425D; /* Azul Pizarra */
            --color-6: #0E1F2F; /* Azul Noche */
        }
        body {
            /* Fondo con degradado muy sutil que combina con tu navbar */
            background: linear-gradient(135deg, #f5f7fa 0%, #E2CAD8 200%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        /* Estilo general para las tarjetas (bordes curvos y sombra suave) */
        .card-dashboard {
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 30px rgba(14, 27, 72, 0.05);
            transition: transform 0.3s ease;
            background-color: #ffffff;
        }
        .card-metric:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(14, 27, 72, 0.1);
        }
        
        /* Colores de las 3 tarjetas de métricas según la paleta */
        .bg-metric-1 { background-color: var(--color-1); color: white; }
        .bg-metric-2 { background-color: var(--color-5); color: white; }
        .bg-metric-3 { background-color: var(--color-2); color: white; }
        
        .metric-title {
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            opacity: 0.9;
        }
        .metric-value {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1;
            margin: 10px 0;
        }
        .metric-link {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        .metric-link:hover {
            color: white;
            text-decoration: underline;
        }
        
        /* Botones estéticos en forma de píldora */
        .btn-action {
            border-radius: 50rem;
            padding: 10px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            color: var(--color-6);
        }
        .btn-action-blue { background-color: var(--color-4); }
        .btn-action-blue:hover { background-color: var(--color-5); color: white; }
        
        .btn-action-pink { background-color: var(--color-3); }
        .btn-action-pink:hover { background-color: var(--color-2); color: white; }
        
        .btn-action-outline {
            background-color: transparent;
            border: 2px solid var(--color-1);
            color: var(--color-1);
        }
        .btn-action-outline:hover { background-color: var(--color-1); color: white; }
        
        .header-title {
            color: var(--color-1);
            font-weight: 800;
        }
    </style>
</head>
<body>
    <!-- Incluir tu barra de navegación ya diseñada -->
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        
        <!-- Panel de Control -->
        <div class="card card-dashboard mb-4 p-2">
            <div class="card-body">
                <h2 class="header-title mb-2">Panel de Control Principal</h2>
                <p class="text-muted mb-0">Bienvenido al sistema de gestión bancaria del Banco GO KYLIE GO.</p>
            </div>
        </div>

        <?php if ($rol === 'administrador' || $rol === 'cajero'): ?>
        <!-- Tarjetas de Métricas -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card card-dashboard card-metric bg-metric-1 h-100">
                    <div class="card-body p-4">
                        <div class="metric-title">Clientes Registrados</div>
                        <!-- Reemplaza el 5 por tu variable PHP si la tienes -->
                        <div class="metric-value">5</div>
                        <a href="listadeClientesRead.php" class="metric-link">Ver directorio →</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-dashboard card-metric bg-metric-2 h-100">
                    <div class="card-body p-4">
                        <div class="metric-title">Cuentas Activas</div>
                        <div class="metric-value">6</div>
                        <a href="estado_cuentas.php" class="metric-link">Ver estados de cuenta →</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-dashboard card-metric bg-metric-3 h-100">
                    <div class="card-body p-4">
                        <div class="metric-title">Préstamos Vigentes</div>
                        <div class="metric-value">3</div>
                        <a href="gestion_prestamos.php" class="metric-link">Ver cartera de préstamos →</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Operaciones Frecuentes -->
        <div class="card card-dashboard p-2">
            <div class="card-body">
                <h5 class="header-title mb-4">Operaciones Frecuentes</h5>
                <div class="d-flex flex-wrap gap-3">
                    <a href="Crearcliente.php" class="btn btn-action btn-action-blue">Registrar Nuevo Cliente</a>
                    <a href="Crear-RealizarTransacciones.php" class="btn btn-action btn-action-pink">Procesar Transacción</a>
                    <a href="gestion_prestamos.php" class="btn btn-action btn-action-outline">Solicitar Préstamo</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
