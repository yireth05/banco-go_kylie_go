<?php
// admin_configuraciones.php
session_start();
require_once 'conexionDB.php';

// Validar que solo el administrador pueda entrar
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: login.php");
    exit;
}

try {
    // 1. Obtener Ciudades (usando $conexion en lugar de $pdo)
    $ciudades = $conexion->query("SELECT * FROM ciudades ORDER BY id_ciudad")->fetchAll(PDO::FETCH_ASSOC);

    // 2. Obtener Sucursales con sus respectivas ciudades mediante JOIN
    $sucursales = $conexion->query("
        SELECT s.id_sucursal, s.nomb_sucursal, s.direccion_sucursal, c.nomb_ciudad 
        FROM sucursales s
        JOIN ciudades c ON s.id_ciudad = c.id_ciudad
        ORDER BY s.id_sucursal
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Obtener Cajeros con sus sucursales
    $cajeros = $conexion->query("
        SELECT caj.id_cajero, caj.ubicacion, caj.estado, suc.nomb_sucursal 
        FROM cajeros caj
        JOIN sucursales suc ON caj.id_sucursal = suc.id_sucursal
        ORDER BY caj.id_cajero
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 4. Obtener Tipos de Cuentas
    $tipo_cuentas = $conexion->query("SELECT * FROM tipo_cuentas ORDER BY cod_tipo_cuenta")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Error al consultar las configuraciones: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuraciones del Banco - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-0e1b48: #0E1B48;
            --color-27425d: #27425D;
        }
        body { background-color: #f4f6f9; font-family: sans-serif; }
        .card-custom { border-radius: 16px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">Configuraciones y Parámetros del Banco</h2>
                <p class="text-muted mb-0">Gestión de tablas maestras del sistema en PostgreSQL.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill">← Volver al Panel</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- TABLA SUCURSALES -->
            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-white h-100">
                    <h5 class="fw-bold text-dark mb-3">Sucursales del Banco</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Dirección</th>
                                    <th>Ciudad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($sucursales)): ?>
                                    <?php foreach ($sucursales as $suc): ?>
                                        <tr>
                                            <td><?php echo $suc['id_sucursal']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($suc['nomb_sucursal']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($suc['direccion_sucursal']); ?></td>
                                            <td><?php echo htmlspecialchars($suc['nomb_ciudad']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted py-3">No hay sucursales.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TABLA CAJEROS -->
            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-white h-100">
                    <h5 class="fw-bold text-dark mb-3">Cajeros Automáticos</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Ubicación</th>
                                    <th>Sucursal</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($cajeros)): ?>
                                    <?php foreach ($cajeros as $caj): ?>
                                        <tr>
                                            <td><?php echo $caj['id_cajero']; ?></td>
                                            <td><?php echo htmlspecialchars($caj['ubicacion']); ?></td>
                                            <td><?php echo htmlspecialchars($caj['nomb_sucursal']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($caj['estado'] === 'Activo') ? 'success' : (($caj['estado'] === 'En mantenimiento') ? 'warning text-dark' : 'danger'); ?>">
                                                    <?php echo $caj['estado']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted py-3">No hay cajeros.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TABLA TIPOS DE CUENTA -->
            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-white h-100">
                    <h5 class="fw-bold text-dark mb-3">Tipos de Cuenta</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre del Tipo de Cuenta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tipo_cuentas)): ?>
                                    <?php foreach ($tipo_cuentas as $tc): ?>
                                        <tr>
                                            <td><?php echo $tc['cod_tipo_cuenta']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($tc['nomb_tipo_cuenta']); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" class="text-center text-muted py-3">No hay tipos de cuenta.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TABLA CIUDADES -->
            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-white h-100">
                    <h5 class="fw-bold text-dark mb-3">Ciudades Operativas</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Ciudad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ciudades)): ?>
                                    <?php foreach ($ciudades as $ciu): ?>
                                        <tr>
                                            <td><?php echo $ciu['id_ciudad']; ?></td>
                                            <td><?php echo htmlspecialchars($ciu['nomb_ciudad']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" class="text-center text-muted py-3">No hay ciudades.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
