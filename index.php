<?php
// index.php
include_once "conexionDB.php";

// Consultas rápidas para los indicadores del Dashboard
try {
    $total_clientes = $conexion->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    $total_cuentas = $conexion->query("SELECT COUNT(*) FROM cuentas")->fetchColumn();
    $total_prestamos = $conexion->query("SELECT COUNT(*) FROM prestamos")->fetchColumn();
} catch (PDOException $e) {
    $total_clientes = 0;
    $total_cuentas = 0;
    $total_prestamos = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Banco GO KYLIE GO</title>
    <!-- Enlace a Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <!-- Incluir el menú de navegación -->
    <?php include_once "navbar.php"; ?>

    <div class="container mb-5">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="p-4 bg-white rounded shadow-sm">
                    <h2 class="fw-bold text-primary">Panel de Control Principal</h2>
                    <p class="text-muted mb-0">Bienvenido al sistema de gestion bancaria del Banco GO KYLIE GO.</p>
                </div>
            </div>
        </div>

        <!-- Tarjetas Informativas (Cards) -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-primary text-white">
                    <div class="card-body">
                        <h6 class="text-uppercase font-weight-bold">Clientes Registrados</h6>
                        <h3 class="display-6 fw-bold"><?php echo $total_clientes; ?></h3>
                        <a href="listadeClientesRead.php" class="text-white text-decoration-underline small">Ver directorio</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-success text-white">
                    <div class="card-body">
                        <h6 class="text-uppercase font-weight-bold">Cuentas Activas</h6>
                        <h3 class="display-6 fw-bold"><?php echo $total_cuentas; ?></h3>
                        <a href="estado_cuentas.php" class="text-white text-decoration-underline small">Ver estados de cuenta</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm bg-dark text-white">
                    <div class="card-body">
                        <h6 class="text-uppercase font-weight-bold">Prestamos Vigentes</h6>
                        <h3 class="display-6 fw-bold"><?php echo $total_prestamos; ?></h3>
                        <a href="gestion_prestamos.php" class="text-white text-decoration-underline small">Ver cartera de prestamos</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accesos Rápidos -->
        <div class="row">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-semibold">Operaciones Frecuentes</h5>
                    </div>
                    <div class="card-body d-flex gap-2 flex-wrap">
                        <a href="Crearcliente.php" class="btn btn-outline-primary">Registrar Nuevo Cliente</a>
                        <a href="Crear-RealizarTransacciones.php" class="btn btn-outline-success">Procesar Transaccion</a>
                        <a href="gestion_prestamos.php" class="btn btn-outline-dark">Solicitar Prestamo</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script de Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
