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
        // Intentamos conectar a PostgreSQL con las credenciales ingresadas
        $conexion = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $usuario, $password);
        $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Guardamos las credenciales y definimos el rol de forma segura
        $_SESSION['usuario'] = $usuario;
        $_SESSION['password'] = $password;

        if ($usuario === 'administrador') {
            $_SESSION['rol'] = 'administrador';
        } elseif ($usuario === 'cajero') {
            $_SESSION['rol'] = 'cajero';
        } else {
            $_SESSION['rol'] = 'cliente';
            $_SESSION['id_cliente'] = '1006123456'; // Cédula de ejemplo para aislar sus datos privados
        }

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Banco GO KYLIE GO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-1: #0E1B48; /* Azul Marino (Principal) */
            --color-2: #C18DB4; /* Rosa Oscuro (Acentos) */
            --color-3: #E2CAD8; /* Rosa Claro (Fondos suaves) */
            --color-4: #87A7D0; /* Azul Claro (Focos y bordes) */
            --color-5: #27425D; /* Azul Pizarra (Hover de botones) */
            --color-6: #0E1F2F; /* Azul Noche (Textos oscuros) */
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, var(--color-3) 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
        }
        .card-custom {
            border-radius: 24px;
            border: none;
            box-shadow: 0 15px 35px rgba(14, 27, 72, 0.1);
            background-color: #ffffff;
        }
        .text-brand { color: var(--color-1); }
        .form-label {
            color: var(--color-5);
            font-weight: 600;
            font-size: 0.9rem;
        }
        .form-control-custom {
            border-radius: 12px;
            padding: 12px 16px;
            border: 2px solid #eef0f5;
            background-color: #fcfcfd;
            transition: all 0.3s ease;
        }
        .form-control-custom:focus {
            border-color: var(--color-4);
            box-shadow: 0 0 0 4px rgba(135, 167, 208, 0.2);
            background-color: #ffffff;
        }
        .btn-custom {
            background-color: var(--color-1);
            color: #ffffff;
            border-radius: 50rem;
            padding: 12px 24px;
            border: none;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(14, 27, 72, 0.2);
        }
        .btn-custom:hover {
            background-color: var(--color-5);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(39, 66, 93, 0.3);
            color: #ffffff;
        }
    </style>
</head>
<body class="d-flex align-items-center vh-100">
    <div class="container" style="max-width: 400px;">
        <div class="card card-custom p-2">
            <div class="card-body p-4">
                <h3 class="fw-bold text-brand mb-2 text-center">GO KYLIE GO</h3>
                <p class="text-muted small text-center mb-4">Accede a tu banca segura</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small text-center rounded-4" style="background-color: #ffe6e6; color: #cc0000; border: none;"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input type="text" name="usuario" class="form-control form-control-custom" placeholder="Ej: administrador" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control form-control-custom" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn btn-custom w-100 mt-2">Iniciar Sesión</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
