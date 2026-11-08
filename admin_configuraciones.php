<?php
// admin_configuraciones.php
session_start();
require_once 'conexionDB.php';

// Validar inicio de sesión sin expulsar a clientes
if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$ciudades = [];
$sucursales = [];
$cajeros = [];
$tipo_cuentas = [];

try {
    $ciudades = $conexion->query("SELECT * FROM ciudades ORDER BY id_ciudad")->fetchAll(PDO::FETCH_ASSOC);
    $sucursales = $conexion->query("
        SELECT s.id_sucursal, s.nomb_sucursal, s.direccion_sucursal, c.nomb_ciudad 
        FROM sucursales s
        JOIN ciudades c ON s.id_ciudad = c.id_ciudad
        ORDER BY s.id_sucursal
    ")->fetchAll(PDO::FETCH_ASSOC);
    $cajeros = $conexion->query("
        SELECT caj.id_cajero, caj.ubicacion, caj.estado, suc.nomb_sucursal 
        FROM cajeros_automaticos caj
        JOIN sucursales suc ON caj.id_sucursal = suc.id_sucursal
        ORDER BY caj.id_cajero
    ")->fetchAll(PDO::FETCH_ASSOC);
    $tipo_cuentas = $conexion->query("SELECT * FROM tipo_cuentas ORDER BY cod_tipo_cuenta")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al consultar las configuraciones: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Servicios y Red Bancaria - Banco Villavicencio</title>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">Servicios y Red del Banco</h2>
                <p class="text-muted mb-0">Información sobre sucursales, cajeros automáticos y tipos de cuenta.</p>
            </div>
            <a href="index.php" class="btn btn-custom px-4 py-2">← Volver al Panel</a>
        </div>

        <div class="row g-4">
            <!-- TABLA TIPOS DE CUENTA -->
            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-white h-100">
                    <h5 class="fw-bold mb-3" style="color: var(--color-0e1b48);">Tipos de Cuenta Disponibles</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr><th>Código</th><th>Tipo de Cuenta</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tipo_cuentas as $tc): ?>
                                    <tr><td><?php echo $tc['cod_tipo_cuenta']; ?></td><td><strong><?php echo htmlspecialchars($tc['nomb_tipo_cuenta']); ?></strong></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TABLA SUCURSALES -->
            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-white h-100">
                    <h5 class="fw-bold mb-3" style="color: var(--color-0e1b48);">Sucursales del Banco</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr><th>ID</th><th>Nombre</th><th>Dirección</th><th>Ciudad</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sucursales as $suc): ?>
                                    <tr>
                                        <td><?php echo $suc['id_sucursal']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($suc['nomb_sucursal']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($suc['direccion_sucursal']); ?></td>
                                        <td><?php echo htmlspecialchars($suc['nomb_ciudad']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
