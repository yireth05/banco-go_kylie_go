<?php
// editar_cliente.php
session_start();
require_once "conexionDB.php";

$mensaje = "";
$error = "";

// Obtener el ID del cliente desde la URL (GET) o desde el formulario (POST)
$id_cliente = $_GET['id'] ?? $_POST['id_cliente'] ?? null;

if (!$id_cliente) {
    header("Location: listadeClientesRead.php");
    exit;
}

// 1. Procesar la actualización cuando se envíe el formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nomb_cli   = trim($_POST["nomb_cli"] ?? '');
    $apelli_cli = trim($_POST["apelli_cli"] ?? '');
    $direccion  = trim($_POST["direccion"] ?? '');
    $telefono   = trim($_POST["telefono"] ?? '');
    $correo     = trim($_POST["correo"] ?? '');

    if (empty($nomb_cli) || empty($apelli_cli)) {
        $error = "El nombre y el apellido son obligatorios.";
    } else {
        try {
            $sql = "UPDATE clientes SET nomb_cli = ?, apelli_cli = ?, direccion = ?, telefono = ?, correo = ? WHERE id_cliente = ?";
            $sentencia = $conexion->prepare($sql);
            $resultado = $sentencia->execute([$nomb_cli, $apelli_cli, $direccion, $telefono, $correo, $id_cliente]);

            if ($resultado) {
                $mensaje = "¡Datos del cliente actualizados exitosamente!";
            }
        } catch (PDOException $e) {
            $error = "Error al actualizar el cliente: " . $e->getMessage();
        }
    }
}

// 2. Consultar los datos actuales del cliente para rellenar el formulario
try {
    $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
    $stmt->execute([$id_cliente]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        exit("Cliente no encontrado en el sistema.");
    }
} catch (PDOException $e) {
    exit("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - Banco GO KYLIE GO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: #0E1B48;">✏️ Modificar Datos del Cliente</h2>
            <a href="listadeClientesRead.php" class="btn btn-outline-secondary rounded-pill">← Volver a la Lista</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4 p-4 bg-white col-lg-8 mx-auto">
            <form action="editar_cliente.php" method="POST">
                <!-- Campo oculto para mantener el ID del cliente sin que el usuario lo altere -->
                <input type="hidden" name="id_cliente" value="<?php echo htmlspecialchars($cliente['id_cliente']); ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">Cédula / Identificación (No modificable)</label>
                    <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($cliente['id_cliente']); ?>" disabled>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nomb_cli" class="form-label fw-bold">Nombre *</label>
                        <input type="text" class="form-control" id="nomb_cli" name="nomb_cli" value="<?php echo htmlspecialchars($cliente['nomb_cli']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="apelli_cli" class="form-label fw-bold">Apellido *</label>
                        <input type="text" class="form-control" id="apelli_cli" name="apelli_cli" value="<?php echo htmlspecialchars($cliente['apelli_cli']); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="direccion" class="form-label fw-bold">Dirección</label>
                    <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo htmlspecialchars($cliente['direccion']); ?>">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-label fw-bold">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($cliente['telefono']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="correo" class="form-label fw-bold">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" value="<?php echo htmlspecialchars($cliente['correo']); ?>">
                    </div>
                </div>

                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill" style="background-color: #0E1B48; border: none;">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
