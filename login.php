<?php
// login.php
<?php
// login.php
session_start();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario  = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (!empty($usuario) && !empty($password)) {
        $_SESSION['usuario'] = $usuario;

        // Validamos los tres roles definidos para el banco
        if (strtolower($usuario) === 'administrador') {
            $_SESSION['rol'] = 'administrador';
        } elseif (strtolower($usuario) === 'cajero') {
            $_SESSION['rol'] = 'cajero';
        } else {
            $_SESSION['rol'] = 'cliente';
            $_SESSION['id_cliente'] = '1006123456'; // Cédula o ID para aislar los datos privados del cliente
        }

        header("Location: index.php");
        exit;
    } else {
        $error = "Por favor completa todos los campos.";
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
            --color-0e1b48: #0E1B48; /* Azul Marino Principal */
            --color-c18db4: #C18DB4; /* Rosa Oscuro / Acento */
            --color-e2cad8: #E2CAD8; /* Rosa Claro / Fondo suave */
            --color-87a7d0: #87A7D0; /* Azul Claro / Bordes */
            --color-27425d: #27425D; /* Azul Pizarra / Hover */
            --color-0e1f2f: #0E1F2F; /* Azul Noche / Textos */
        }
        body {
            background: linear-gradient(135deg, var(--color-e2cad8) 0%, var(--color-87a7d0) 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
        }
        .card-custom {
            border-radius: 24px;
            border: none;
            box-shadow: 0 15px 35px rgba(14, 27, 72, 0.15);
            background-color: #ffffff;
        }
        .text-brand { 
            color: var(--color-0e1b48); 
        }
        .form-label {
            color: var(--color-0e1f2f);
            font-weight: 600;
            font-size: 0.9rem;
        }
        .form-control-custom {
            border-radius: 12px;
            padding: 12px 16px;
            border: 2px solid var(--color-e2cad8);
            background-color: #fcfcfd;
            color: var(--color-0e1f2f);
            transition: all 0.3s ease;
        }
        .form-control-custom:focus {
            border-color: var(--color-87a7d0);
            box-shadow: 0 0 0 4px rgba(135, 167, 208, 0.25);
            background-color: #ffffff;
        }
        .btn-custom {
            background-color: var(--color-0e1b48);
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
            background-color: var(--color-27425d);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(39, 66, 93, 0.3);
            color: var(--color-e2cad8);
        }
        .badge-accent {
            background-color: var(--color-c18db4);
            color: #ffffff;
            font-weight: 500;
        }
        .link-brand {
            color: var(--color-0e1b48);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .link-brand:hover {
            color: var(--color-27425d);
            text-decoration: underline;
        }
    </style>
</head>
<body class="d-flex align-items-center vh-100">
    <div class="container" style="max-width: 420px;">
        <div class="card card-custom p-2">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <span class="badge badge-accent rounded-pill px-3 py-1 mb-2">Banca Segura</span>
                    <h3 class="fw-bold text-brand mb-1">Banco GO KYLIE GO</h3>
                    <p class="text-muted small">Inicia sesión en tu cuenta</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small text-center rounded-4" style="background-color: #f8d7da; color: #842029; border: none;"><?php echo $error; ?></div>
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

                <!-- Botón / Enlace para ir a Crear Cliente -->
                <div class="text-center mt-4 pt-3 border-top">
                    <p class="text-muted small mb-2">¿Eres nuevo o necesitas registrar a alguien?</p>
                    <a href="Crearcliente.php" class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-semibold" style="border-color: var(--color-87a7d0); color: var(--color-0e1b48);">
                        Crear Nuevo Cliente ➔
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
