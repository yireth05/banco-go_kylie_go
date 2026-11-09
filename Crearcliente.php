<?php
// Crearcliente.php
session_start();
require_once "conexionDB.php";

$mensaje = "";
$error = "";

// Verificar si el formulario fue enviado por POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_cliente = trim($_POST["id_cliente"] ?? '');
    $nomb_cli   = trim($_POST["nomb_cli"] ?? '');
    $apelli_cli = trim($_POST["apelli_cli"] ?? '');
    $direccion  = trim($_POST["direccion"] ?? '');
    $telefono   = trim($_POST["telefono"] ?? '');
    $correo     = trim($_POST["correo"] ?? '');

    if (empty($id_cliente) || empty($nomb_cli) || empty($apelli_cli)) {
        $error = "Faltan datos obligatorios por enviar, por favor ingresar datos faltantes.";
    } else {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $sentencia = $conexion->prepare("INSERT INTO clientes (id_cliente, nomb_cli, apelli_cli, direccion, telefono, correo) VALUES (?, ?, ?, ?, ?, ?)");
            $resultado = $sentencia->execute([$id_cliente, $nomb_cli, $apelli_cli, $direccion, $telefono, $correo]);
    
            if ($resultado) {
                
                $mensaje = "¡Cliente registrado exitosamente en el sistema!";
            }
        } catch (PDOException $e) {
            $error = "Error al crear nuevo cliente: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cliente - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold" style="color: #0E1B48;">Registrar Nuevo Cliente</h2>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill">← Volver al Panel</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4 p-4 bg-white col-lg-8 mx-auto">
            <!-- El formulario apunta a este mismo archivo mediante POST -->
            <form action="Crearcliente.php" method="POST">
                <div class="mb-3">
                    <label for="id_cliente" class="form-label fw-bold">Cédula / Identificación *</label>
                    <input type="text" class="form-control" id="id_cliente" name="id_cliente" required placeholder="Ej: 1006123456">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nomb_cli" class="form-label fw-bold">Nombre *</label>
                        <input type="text" class="form-control" id="nomb_cli" name="nomb_cli" required placeholder="Ej: Laura">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="apelli_cli" class="form-label fw-bold">Apellido *</label>
                        <input type="text" class="form-control" id="apelli_cli" name="apelli_cli" required placeholder="Ej: Parra">
                    </div>
                </div>
                      <div class="mb-3">
                    <label for="password" class="form-label fw-bold">Contraseña de Acceso *</label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Crea una contraseña para el cliente">
                </div> 
                <div class="mb-3">
                    <label for="direccion" class="form-label fw-bold">Dirección</label>
                    <input type="text" class="form-control" id="direccion" name="direccion" placeholder="Ej: Calle 45 # 12-34">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-label fw-bold">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" placeholder="Ej: 3101234567">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="correo" class="form-label fw-bold">Correo Electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" placeholder="Ej: correo@mail.com">
                    </div>
                </div>

                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill" style="background-color: #0E1B48; border: none;">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
