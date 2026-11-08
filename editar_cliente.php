<?php
session_start();
require_once "conexionDB.php";

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

// Determinar a qué ID se va a consultar/modificar según quién está logueado
if ($_SESSION['rol'] === 'cliente') {
    // El cliente solo puede ver y editar sus propios datos
    $id_objetivo = $_SESSION['id_cliente'];
} else {
    // Si es administrador o cajero, puede buscar el ID que reciba por la URL (GET) o POST
    $id_objetivo = $_GET['id'] ?? $_POST['id_cliente'] ?? null;
    if (!$id_objetivo) {
        die("Acceso denegado: No se especificó el ID del cliente.");
    }
}

$mensaje = "";

// Procesar la actualización si envían el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $direccion = trim($_POST['direccion']);
    $telefono  = trim($_POST['telefono']);
    $correo    = trim($_POST['correo']);

    try {
        $stmt = $conexion->prepare("UPDATE clientes SET direccion = :dir, telefono = :tel, correo = :corr WHERE id_cliente = :id");
        $stmt->execute(['dir' => $direccion, 'tel' => $telefono, 'corr' => $correo, 'id' => $id_objetivo]);
        $mensaje = "<div class='alert alert-success'>Datos actualizados correctamente.</div>";
    } catch (Exception $e) {
        $mensaje = "<div class='alert alert-danger'>Error al actualizar: " . $e->getMessage() . "</div>";
    }
}

// Consultar los datos actuales del cliente objetivo
$stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_cliente = :id");
$stmt->execute(['id' => $id_objetivo]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die("Cliente no encontrado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Datos - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-4 mb-5" style="max-width: 600px;">
        <div class="card shadow border-0 rounded-4">
            <div class="card-header text-white py-3 rounded-top-4" style="background-color: #0E1B48;">
                <h4 class="mb-0 fs-5">
                    <?php echo ($_SESSION['rol'] === 'cliente') ? 'Mis Datos Personales' : 'Modificar Cliente (Vista Administrador)'; ?>
                </h4>
            </div>
            <div class="card-body p-4">
                <?php echo $mensaje; ?>
                <form method="POST">
                    <!-- Si es admin, pasamos el ID oculto por POST para no perderlo -->
                    <?php if ($_SESSION['rol'] !== 'cliente'): ?>
                        <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($id_objetivo); ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Cédula / ID:</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($cliente['id_cliente']); ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombres y Apellidos:</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($cliente['nomb_cli'] . ' ' . $cliente['apelli_cli']); ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Dirección:</label>
                        <input type="text" class="form-label form-control" name="direccion" value="<?php echo htmlspecialchars($cliente['direccion']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Teléfono:</label>
                        <input type="text" class="form-control" name="telefono" value="<?php echo htmlspecialchars($cliente['telefono']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Correo Electrónico:</label>
                        <input type="email" class="form-control" name="correo" value="<?php echo htmlspecialchars($cliente['correo']); ?>" required>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="index.php" class="btn btn-outline-secondary rounded-pill">Volver</a>
                        <button type="submit" class="btn btn-success rounded-pill px-4">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
