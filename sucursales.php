<?php
session_start();
require_once 'conexionDB.php';

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

// 1. Consultar todas las sucursales para llenar la tabla
try {
    $stmt = $conexion->query("SELECT * FROM sucursales ORDER BY id_sucursal ASC");
    $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sucursales = [];
}

// 2. Si el usuario dio clic en "Más Info", capturamos el ID de la sucursal seleccionada
$sucursal_seleccionada = null;
if (isset($_GET['id'])) {
    $id_suc = $_GET['id'];
    try {
        $stmt_det = $conexion->prepare("SELECT * FROM sucursales WHERE id_sucursal = ?");
        $stmt_det->execute([$id_suc]);
        $sucursal_seleccionada = $stmt_det->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Error silencioso o manejo de excepción
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sucursales y Contacto - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">

    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        
        <h2 class="fw-bold mb-4" style="color: #0E1B48;">
            <i class="bi bi-building-fill me-2"></i>Red de Sucursales
        </h2>

        <!-- TABLA PRINCIPAL DE SUCURSALES -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <h5 class="fw-bold mb-3 text-secondary">Listado de Oficinas</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nombre de Sucursal</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sucursales)): ?>
                            <?php foreach ($sucursales as $suc): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($suc['id_sucursal']); ?></td>
                                    <td><?php echo htmlspecialchars($suc['nombre_sucursal']); ?></td>
                                    <td><i class="bi bi-geo-alt-fill text-danger me-1"></i><?php echo htmlspecialchars($suc['direccion_sucursal']); ?></td>
                                    <td><i class="bi bi-telephone-fill text-success me-1"></i><?php echo htmlspecialchars($suc['telefono_sucursal']); ?></td>
                                    <td class="text-center">
                                        <!-- BOTÓN MÁS INFO: Recarga la página añadiendo el ID y bajando al ancla -->
                                        <a href="sucursales.php?id=<?php echo $suc['id_sucursal']; ?>#seccion-contacto" class="btn btn-sm btn-primary rounded-pill px-3">
                                            <i class="bi bi-info-circle me-1"></i> Más Info
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No hay sucursales registradas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECCIÓN INFERIOR: APARECE SOLO DESPUÉS DE DAR CLIC EN "MÁS INFO" -->
        <?php if ($sucursal_seleccionada): ?>
            <div id="seccion-contacto" class="card border-0 shadow rounded-4 p-4 bg-white border-start border-4 border-success">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <span class="badge bg-success mb-2">Sucursal Seleccionada</span>
                        <h3 class="fw-bold text-dark"><?php echo htmlspecialchars($sucursal_seleccionada['nombre_sucursal']); ?></h3>
                        <p class="text-muted mb-1"><i class="bi bi-geo-alt me-1"></i> <strong>Dirección:</strong> <?php echo htmlspecialchars($sucursal_seleccionada['direccion_sucursal']); ?></p>
                        <p class="text-muted mb-0"><i class="bi bi-telephone me-1"></i> <strong>Teléfono Principal:</strong> <?php echo htmlspecialchars($sucursal_seleccionada['telefono_sucursal']); ?></p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <!-- EL BOTÓN DE CONTACTARSE ABAJO DE LA TABLA -->
                        <button type="button" class="btn btn-success btn-lg rounded-pill shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#modalContactoSucursal">
                            <i class="bi bi-headset me-2"></i> Contactarse
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL QUE SE ABRE AL DAR CLIC EN "CONTACTARSE" -->
            <div class="modal fade" id="modalContactoSucursal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 rounded-4 shadow">
                        <div class="modal-header bg-dark text-white rounded-top-4">
                            <h5 class="modal-title"><i class="bi bi-chat-dots-fill me-2"></i>Atención - <?php echo htmlspecialchars($sucursal_seleccionada['nombre_sucursal']); ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <p class="text-muted small">Canales directos de comunicación asignados a esta sucursal:</p>
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <i class="bi bi-telephone-fill text-success fs-5 me-2 align-middle"></i>
                                    <strong>Teléfono de Oficina:</strong><br>
                                    <span class="text-muted ms-4"><?php echo htmlspecialchars($sucursal_seleccionada['telefono_sucursal']); ?></span>
                                </li>
                                <li class="mb-3">
                                    <i class="bi bi-whatsapp text-success fs-5 me-2 align-middle"></i>
                                    <strong>Línea de WhatsApp:</strong><br>
                                    <span class="text-muted ms-4">+57 320 456 7890</span>
                                </li>
                                <li class="mb-3">
                                    <i class="bi bi-cash-coin text-warning fs-5 me-2 align-middle"></i>
                                    <strong>Cajeros Automáticos (ATM):</strong><br>
                                    <span class="text-muted ms-4">Disponibles en el lobby (24 horas).</span>
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
