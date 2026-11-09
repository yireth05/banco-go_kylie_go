<?php
// Crearcliente.php
ob_start();
session_start();
require_once "conexionDB.php";

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'cajero'], true)) {
    header("Location: login.php");
    exit;
}

$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_cliente = trim($_POST["id_cliente"] ?? '');
    $nomb_cli   = trim($_POST["nomb_cli"] ?? '');
    $apelli_cli = trim($_POST["apelli_cli"] ?? '');
    $password   = trim($_POST["password"] ?? '');
    $direccion  = trim($_POST["direccion"] ?? '');
    $telefono   = trim($_POST["telefono"] ?? '');
    $correo     = trim($_POST["correo"] ?? '');

    if ($id_cliente === '' || $nomb_cli === '' || $apelli_cli === '' || $password === '') {
        $error = "Faltan datos obligatorios por enviar, por favor ingresar datos faltantes.";
    } elseif (strlen($password) < 4 || strlen($password) > 20) {
        $error = "La contraseña debe tener entre 4 y 20 caracteres.";
    } elseif (!preg_match('/^[0-9]+$/', $telefono)) {
        $error = "Error: El número de teléfono solo debe contener dígitos numéricos y no puede incluir caracteres especiales.";
    } else {
        try {
            // Por si la tabla se creó con un SQL viejo sin la columna password
            $conexion->exec("ALTER TABLE clientes ADD COLUMN IF NOT EXISTS password character varying(255)");

            $sentencia = $conexion->prepare(
                "INSERT INTO clientes (id_cliente, nomb_cli, apelli_cli, direccion, telefono, correo, password)
                 VALUES (:id, :nom, :ape, :dir, :tel, :cor, :pass)"
            );
            $sentencia->execute([
                ':id'   => $id_cliente,
                ':nom'  => $nomb_cli,
                ':ape'  => $apelli_cli,
                ':dir'  => $direccion !== '' ? $direccion : null,
                ':tel'  => $telefono,
                ':cor'  => $correo !== '' ? $correo : null,
                ':pass' => $password,
            ]);

            // Verificar que realmente quedó guardada
            $chk = $conexion->prepare("SELECT password FROM clientes WHERE id_cliente = ?");
            $chk->execute([$id_cliente]);
            $guardada = (string)$chk->fetchColumn();

            if ($guardada !== '' && hash_equals($guardada, $password)) {
                $mensaje = "¡Cliente registrado exitosamente con su contraseña!";
            } else {
                $error = "El cliente se creó pero la contraseña no quedó guardada. Revisa la columna password de la tabla clientes.";
            }
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $error = "Ya existe un cliente con esa cédula.";
            } else {
                $error = "Error al crear nuevo cliente: " . $e->getMessage();
            }
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
            <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4 p-4 bg-white col-lg-8 mx-auto">
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
                    <input type="text" class="form-control" id="password" name="password" required minlength="4" maxlength="20" autocomplete="off" placeholder="Contraseña corta (4 a 20 caracteres)">
                </div> 

                <div class="mb-3">
                    <label for="direccion" class="form-label fw-bold">Dirección</label>
                    <input type="text" class="form-control" id="direccion" name="direccion" placeholder="Ej: Calle 45 # 12-34">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-label fw-bold">Teléfono *</label>
                        <input type="tel" class="form-control" id="telefono" name="telefono" pattern="[0-9]+" title="Solo se permiten números" placeholder="Ej: 3101234567" required>
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
