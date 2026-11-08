<?php
// login.php
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario  = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    $host   = "127.0.0.1";
    $port   = "5432";
    $dbname = "banco_go_kylie_go";

    try {
        // Intentar conectar con las credenciales ingresadas por el usuario
        $conexion = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $usuario, $password);
        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Guardar la sesión y el usuario
        $_SESSION['usuario'] = $usuario;
        $_SESSION['password'] = $password; // Utilizado para mantener la conexión por rol
        $_SESSION['rol'] = $usuario; // 'administrador', 'cajero' o 'cliente'

        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Banco GO KYLIE GO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center vh-100">
    <div class="container" style="max-width: 400px;">
        <div class="card shadow border-0">
            <div class="card-body p-4">
                <h3 class="text-center fw-bold text-primary mb-3">Banco GO KYLIE GO</h3>
                <p class="text-center text-muted small mb-4">Ingresa tus credenciales para acceder</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Usuario</label>
                        <input type="text" name="usuario" class="form-control" placeholder="Ej: cajero, cliente, administrador" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label font-weight-bold">Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Iniciar Sesión</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
