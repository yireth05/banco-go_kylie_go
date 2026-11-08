<?php
// Crearcliente.php
session_start();

$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cedula   = trim($_POST['cedula']);
    $nombre   = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $correo   = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);

    // Validación básica y conexión o simulación de guardado
    if (!empty($cedula) && !empty($nombre) && !empty($apellido)) {
        // Aquí puedes incluir tu lógica de conexión a la base de datos (conexionDB.php)
        // Ejemplo de redirección exitosa hacia crear_cuenta.php pasando la cédula del nuevo cliente
        header("Location: crear_cuenta.php?cedula=" . urlencode($cedula));
        exit;
    } else {
        $error = "Por favor completa los campos obligatorios (Cédula, Nombre y Apellido).";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cliente - Banco GO KYLIE GO</title>
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
            display: flex;
            flex-direction: column;
        }
        .navbar-custom {
            background-color: var(--color-0e1b48);
            box-shadow: 0 4px 12px rgba(14, 27, 72, 0.15);
        }
        .navbar-brand {
            color: #ffffff !important;
            font-weight: 700;
        }
        .nav-link {
            color: var(--color-e2cad8) !important;
            font-weight: 500;
        }
        .nav-link:hover {
            color: #ffffff !important;
        }
        .card-custom {
            border-radius: 24px;
            border: none;
            box-shadow: 0 15px 35px rgba(14, 27, 72, 0.15);
            background-color: #ffffff;
        }
        .text-brand { color: var(--color-0e1b48); }
        .form-label {
            color: var(--color-0e1f2f);
            font-weight: 600;
            font-size: 0.9rem;
        }
        .form-control-custom {
            border-radius: 12px;
            padding: 10px 14px;
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
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(14, 27, 72, 0.2);
        }
        .btn-custom:hover {
            background-color: var(--color-27425d);
            transform: translateY(-2px);
            color: var(--color-e2cad8);
        }
    </style>
</head>
<body>

    <!-- Menú de Navegación Superior -->
    <nav class="navbar navbar-expand-lg navbar-custom px-4 py-3">
        <div class="container-fluid">
            <a class="navbar-brand fs-4" href="index.php">Banco GO KYLIE GO</a>
            <div class="navbar-nav ms-auto flex-row gap-3">
                <a class="nav-link" href="index.php">Inicio</a>
                <a class="nav-link" href="login.php">Entrar</a>
            </div>
        </div>
    </nav>

    <!-- Contenido del Formulario -->
    <div class="container my-5" style="max-width: 600px;">
        <div class="card card-custom p-4">
            <div class="card-body">
                <div class="text-center mb-4">
                    <span class="badge rounded-pill px-3 py-1 mb-2" style="background-color: var(--color-c18db4); color: white;">Módulo de Gestión</span>
                    <h3 class="fw-bold text-brand">Registrar Nuevo Cliente</h3>
                    <p class="text-muted small">Ingresa los datos personales para dar de alta al cliente y proceder a crear su cuenta.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small text-center rounded-4"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="Crearcliente.php">
                    <div class="mb-3">
                        <label class="form-label">Cédula / Identificación</label>
                        <input type="text" name="cedula" class="form-control form-control-custom" placeholder="Ej: 1006123456" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control form-control-custom" placeholder="Ej: Kylie" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Apellido</label>
                            <input type="text" name="apellido" class="form-control form-control-custom" placeholder="Ej: Parra" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" name="correo" class="form-control form-control-custom" placeholder="correo@banco.com">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control form-control-custom" placeholder="Ej: 3001234567">
                    </div>
                    <button type="submit" class="btn btn-custom w-100">Guardar Cliente y Crear Cuenta ➔</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
