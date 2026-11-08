<?php
// sucursales.php - Red de Sucursales y Contacto
session_start();
require_once 'conexionDB.php';

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

// Consultar las sucursales desde la base de datos
try {
    $stmt = $conexion->query("SELECT * FROM sucursales ORDER BY id_sucursal ASC");
    $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sucursales = [];
    $error = "Error al cargar las sucursales: " . $e->getMessage();
}

// Si se seleccionó una sucursal para ver más detalle
$sucursal_seleccionada = null;
if (isset($_GET['id'])) {
    $id_suc = $_GET['id'];
    try {
        $stmt_detalle = $conexion->prepare("SELECT * FROM sucursales WHERE id_sucursal = ?");
        $stmt_detalle->execute([$id_suc]);
        $sucursal_seleccionada = $stmt_detalle->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Manejo de error silencioso o log
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Red de Sucursales - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .card-custom {
            border: none;
            border-radius: 18px;
            box-shadow: 0 6px 18px rgba(14, 27, 72, 0.08);
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

    <div class="container py-5">
        
        <!-- Encabezado -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">
                    <i class="bi bi-building-fill me-2"></i>Red de Sucursales y Oficinas
                </h2>
                <p class="text-muted">Consulte nuestras sucursales físicas, números de contacto y cajeros automáticos disponibles.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-arrow-left me-1"></i> Volver al Inicio
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger rounded-4 shadow-sm"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Tabla de Sucursales -->
        <div class="card card-custom p-4 bg-white mb-5">
            <h4 class="fw-bold mb-3" style="color: var(--color-0e1b48);">Listado de Oficinas Principales</h4>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre de Sucursal</th>
                            <th>Ciudad / Dirección</th>
                            <th>Teléfono</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sucursales)): ?>
                            <?php foreach ($sucursales as $suc): ?>
                                <tr>
                                    <td class="fw-bold">#<?php echo htmlspecialchars($suc['id_sucursal']); ?></td>
                                    <td><?php echo htmlspecialchars($suc['nombre_sucursal'] ?? 'Sucursal Villavicencio'); ?></td>
                                    <td><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?php echo htmlspecialchars($suc['direccion_sucursal'] ?? 'Centro Principal'); ?></td>
                                    <td><i class="bi bi-telephone-fill text-success me-1"></i> <?php echo htmlspecialchars($suc['telefono_sucursal'] ?? '(608) 6700000'); ?></td>
                                    <td class="text-center">
                                        <a href="sucursales.php?id=<?php echo $suc['id_sucursal']; ?>" class="btn btn-sm btn-custom px-3">
                                            <i class="bi bi-info-circle me-1"></i> Más Info
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No hay sucursales registradas en este momento.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECCIÓN DINÁMICA: DETALLE Y CONTACTO DE LA SUCURSAL SELECCIONADA -->
        <?php if ($sucursal_seleccionada): ?>
            <div class="card card-custom p-4 bg-white border-top border-4 border-primary mb-5 animate__animated animate__fadeIn">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <span class="badge bg-primary mb-2">Sucursal Seleccionada</span>
                        <h3 class="fw-bold text-dark"><?php echo htmlspecialchars($sucursal_seleccionada['nombre_sucursal']); ?></h3>
                        <p class="text-muted mb-1"><i class="bi bi-geo-alt me-1"></i> <strong>Dirección:</strong> <?php echo htmlspecialchars($sucursal_seleccionada['direccion_sucursal']); ?></p>
                        <p class="text-muted mb-0"><i class="bi bi-clock me-1"></i> <strong>Horario de Atención:</strong> Lunes a Viernes 8:00 a.m. - 4:00 p.m.</p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <!-- Botón Contactarse que despliega la info -->
                        <button class="btn btn-success btn-lg rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#modalContacto">
                            <i class="bi bi-headset me-2"></i> Contactarse
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal de Contacto y Cajeros de la Sucursal -->
            <div class="modal fade" id="modalContacto" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 rounded-4 shadow">
                        <div class="modal-header bg-dark text-white rounded-top-4">
                            <h5 class="modal-title"><i class="bi bi-telephone-forward me-2"></i>Canales de Atención y Soporte</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <h6 class="fw-bold text-primary">Oficina: <?php echo htmlspecialchars($sucursal_seleccionada['nombre_sucursal']); ?></h6>
                            <p class="text-muted small">Comuníquese directamente con nuestros asesores o localice los cajeros habilitados en esta zona.</p>
                            
                            <hr>
                            
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <i class="bi bi-telephone-fill text-success fs-5 me-2 align-middle"></i>
                                    <strong>Línea Directa Oficina:</strong><br>
                                    <span class="text-muted ms-4"><?php echo htmlspecialchars($sucursal_seleccionada['telefono_sucursal'] ?? '(608) 6712345'); ?></span>
                                </li>
                                <li class="mb-3">
                                    <i class="bi bi-whatsapp text-success fs-5 me-2 align-middle"></i>
                                    <strong>Línea de WhatsApp Institucional:</strong><br>
                                    <span class="text-muted ms-4">+57 320 400 1234</span>
                                </li>
                                <li class="mb-3">
                                    <i class="bi bi-cash-coin text-warning fs-5 me-2 align-middle"></i>
                                    <strong>Cajeros Automáticos Disponibles (ATM):</strong><br>
                                    <span class="text-muted ms-4">2 Cajeros habilitados 24/7 en el lobby de la sucursal.</span>
                                </li>
                            </ul>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
