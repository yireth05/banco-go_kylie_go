<?php
session_start();
require_once 'conexionDB.php';
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios y Red del Banco - Banco Villavicencio</title>
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
            background: linear-gradient(135deg, #f8f9fa 0%, var(--color-e2cad8) 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            min-height: 100vh;
        }
        .card-custom { 
            border-radius: 16px; 
            border: none; 
            box-shadow: 0 8px 24px rgba(14, 27, 72, 0.08); 
        }
        .table-dark {
            background-color: var(--color-0e1b48);
        }
        .table-dark th {
            background-color: var(--color-0e1b48);
            color: #ffffff;
            border-color: var(--color-27425d);
        }
        .btn-custom {
            background-color: var(--color-c18db4);
            color: #ffffff;
            border-radius: 50rem;
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-custom:hover {
            background-color: var(--color-87a7d0);
            color: var(--color-0e1b48);
        }
        .text-brand {
            color: var(--color-0e1b48);
        }
    </style>
</head>
<body>
    <?php if (file_exists("navbar.php")) include_once "navbar.php"; ?>

    <div class="container py-5 mt-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-brand">Servicios y Red del Banco</h2>
                <p class="text-muted mb-0">Información sobre sucursales, cajeros automáticos, tipos de cuenta y presencia operativa.</p>
            </div>
            <a href="index.php" class="btn btn-custom px-4 py-2">← Volver al Panel</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- TABLA SUCURSALES -->
            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-white h-100">
                    <h5 class="fw-bold text-brand mb-3">Sucursales del Banco</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
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

            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-white h-100">
                    <h5 class="fw-bold text-brand mb-3">Cajeros Automáticos</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
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
                                                    <?php echo htmlspecialchars($caj['estado']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center text-muted py-3">No hay cajeros automáticos.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-white h-100">
                    <h5 class="fw-bold text-brand mb-3">Tipos de Cuenta</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
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

            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-white h-100">
                    <h5 class="fw-bold text-brand mb-3">Ciudades Operativas</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
