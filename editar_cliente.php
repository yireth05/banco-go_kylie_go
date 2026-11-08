<?php
// editar_cliente.php
session_start();
require_once 'conexionDB.php';

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$id_cliente = $_GET['id'] ?? $_SESSION['usuario_id'] ?? $_SESSION['id_cliente'] ?? null;
$cliente = null;
$mensaje = "";
$error = "";

if ($id_cliente) {
    try {
        $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_cliente = :id");
        $stmt->execute([':id' => $id_cliente]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error al consultar cliente: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cliente) {
    $nomb_cli = trim($_POST['nomb_cli']);
    $apelli_cli = trim($_POST['apelli_cli']);
    $direccion = trim($_POST['direccion']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);

    try {
        $stmt = $conexion->prepare("
            UPDATE clientes 
            SET nomb_cli = :nomb, apelli_cli = :apelli, direccion = :dir, telefono = :tel, correo = :cor 
            WHERE id_cliente = :id
        ");
        $stmt->execute([
            ':nomb' => $nomb_cli,
            ':apelli' => $apelli_cli,
            ':dir' => $direccion,
            ':tel' => $telefono,
            ':cor' => $correo,
            ':id' => $id_cliente
        ]);
        $mensaje = "Datos actualizados exitosamente.";
        // Recargar datos
        $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_cliente = :id");
        $stmt->execute([':id' => $id_cliente]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error al actualizar: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Datos Personales - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-0e1b48: #0E1B48; --color-c18db4: #C18DB4;
            --color-e2cad8: #E2CAD8; --color-87a7d0: #87A7D0;
            --color-27425d: #27425D;
        }
        body { background: linear-gradient(135deg, #f8f9fa 0%, var(--color-e2cad8) 100%); min-height: 100vh; }
        .card-custom { border-radius: 16px; border: none; box-shadow: 0 8px 24px rgba(14, 27, 72, 0.08); }
        .btn-custom { background-color: var(--color-0e1b48); color: #fff; border-radius: 50rem; font-weight: 600; }
        .btn-custom:hover { background-color: var(--color-27425d); color: #fff; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container py-5 mt-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: var(--color-0e1b48);">Mis Datos Personales</h2>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill">← Volver al Panel</a>
        </div>

        <?php if ($mensaje): ?><div class="alert alert-success rounded-4"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger rounded-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <?php if ($cliente): ?>
            <div class="card card-custom p-4 bg-white col-lg-8 mx-auto">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cédula / Identificación</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($cliente['id_cliente']); ?>" disabled>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nombre</label>
                            <input type="text" name="nomb_cli" class="form-control" value="<?php echo htmlspecialchars($cliente['nomb_cli']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Apellido</label>
                            <input type="text" name="apelli_cli" class="form-control" value="<?php echo htmlspecialchars($cliente['apelli_cli']); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Dirección</label>
                        <input type="text" name="direccion" class="form-control" value="<?php echo htmlspecialchars($cliente['direccion'] ?? ''); ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($cliente['telefono'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Correo Electrónico</label>
                            <input type="email" name="correo" class="form-control" value="<?php echo htmlspecialchars($cliente['correo'] ?? ''); ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-custom w-100 py-2 mt-3">Guardar Cambios</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center rounded-4">No se encontró información para el cliente especificado.</div>
        <?php endif; ?>
    </div>
</body>
</html>
