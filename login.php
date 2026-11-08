<?php
// index.php o portada principal del banco
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco GO KYLIE GO - Banca Segura</title>
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
            justify-content: space-between;
        }
        .navbar-custom {
            background-color: var(--color-0e1b48);
            box-shadow: 0 4px 12px rgba(14, 27, 72, 0.15);
        }
        .navbar-brand {
            color: #ffffff !important;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .nav-link {
            color: var(--color-e2cad8) !important;
            font-weight: 500;
            transition: color 0.2s ease;
        }
        .nav-link:hover {
            color: #ffffff !important;
        }
        .hero-card {
            border-radius: 24px;
            border: none;
            box-shadow: 0 15px 35px rgba(14, 27, 72, 0.15);
            background-color: #ffffff;
            padding: 40px;
        }
        .text-brand {
            color: var(--color-0e1b48);
        }
        .btn-primary-custom {
            background-color: var(--color-0e1b48);
            color: #ffffff;
            border-radius: 50rem;
            padding: 12px 28px;
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(14, 27, 72, 0.2);
        }
        .btn-primary-custom:hover {
            background-color: var(--color-27425d);
            transform: translateY(-2px);
            color: #ffffff;
        }
        .btn-outline-custom {
            border: 2px solid var(--color-0e1b48);
            color: var(--color-0e1b48);
            border-radius: 50rem;
            padding: 12px 28px;
            font-weight: 600;
            background-color: transparent;
            transition: all 0.3s ease;
        }
        .btn-outline-custom:hover {
            background-color: var(--color-0e1b48);
            color: #ffffff;
            transform: translateY(-2px);
        }
        footer {
            background-color: var(--color-0e1f2f);
            color: var(--color-e2cad8);
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

    <!-- Menú de Navegación Superior -->
    <nav class="navbar navbar-expand-lg navbar-custom px-4 py-3">
        <div class="container-fluid">
            <a class="navbar-brand fs-4" href="index.php">🏦 Banco GO KYLIE GO</a>
            <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-3">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Iniciar Sesión</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-light rounded-pill px-4 text-dark fw-bold" href="Crearcliente.php" style="color: var(--color-0e1b48) !important;">Crear Cliente</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenido Principal / Bienvenida -->
    <div class="container my-auto py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="hero-card">
                    <span class="badge rounded-pill px-3 py-2 mb-3" style="background-color: var(--color-c18db4); color: white; font-weight: 500;">Banca en Línea Segura</span>
                    <h1 class="fw-bold text-brand display-5 mb-3">Bienvenido a Banco GO KYLIE GO</h1>
                    <p class="text-muted mb-4 lead">Tu dinero, tus cuentas y tus transacciones gestionadas de forma rápida, moderna y segura en un solo lugar.</p>
                    
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="login.php" class="btn btn-primary-custom btn-lg">Entrar al Sistema</a>
                        <a href="Crearcliente.php" class="btn btn-outline-custom btn-lg">Crear Nuevo Cliente</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pie de página -->
    <footer class="text-center py-3">
        <p class="mb-0">&copy; 2026 Banco GO KYLIE GO. Todos los derechos reservados.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
