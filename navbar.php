<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
?>
<style>
    :root {
        --color-1: #0E1B48; 
        --color-2: #C18DB4; 
        --color-3: #E2CAD8; 
        --color-4: #87A7D0; 
        --color-5: #27425D; 
        --color-6: #0E1F2F; 
    }
    
    /* Fondo de la barra de navegación usando el Azul Noche */
    .navbar-custom {
        background-color: var(--color-6);
        padding: 12px 0;
    }
    .navbar-brand {
        color: var(--color-3) !important; /* Rosa Claro para la marca */
        letter-spacing: 1px;
    }
    .nav-link {
        color: #ffffff !important;
        font-weight: 500;
        margin: 0 5px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    .nav-link:hover {
        background-color: rgba(226, 202, 216, 0.1); /* Efecto hover sutil */
        color: var(--color-4) !important; /* Azul Claro al pasar el mouse */
    }
    
    /* Botón de cerrar sesión práctico y estilizado */
    .btn-logout {
        color: var(--color-2);
        border: 2px solid var(--color-2);
        border-radius: 50rem;
        padding: 6px 20px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-logout:hover {
        background-color: var(--color-2);
        color: var(--color-6);
        box-shadow: 0 4px 10px rgba(193, 141, 180, 0.3);
    }
</style>

<nav class="navbar navbar-expand-lg navbar-custom shadow-sm mb-4">
  <div class="container-fluid px-4">
    <a class="navbar-brand fw-bold fs-5" href="index.php">GO KYLIE GO</a>
    
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" style="border-color: var(--color-4);">
      <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto ms-lg-3">
        <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>

        <?php if ($rol === 'administrador' || $rol === 'cajero'): ?>
            <li class="nav-item"><a class="nav-link" href="listadeClientesRead.php">Clientes</a></li>
            <li class="nav-item"><a class="nav-link" href="Crearcliente.php">Registrar Cliente</a></li>
            <li class="nav-item"><a class="nav-link" href="estado_cuentas.php">Cuentas</a></li>
            <li class="nav-item"><a class="nav-link" href="Crear-RealizarTransacciones.php">Transacciones</a></li>
        <?php endif; ?>

        <?php if ($rol === 'administrador'): ?>
            <li class="nav-item"><a class="nav-link" href="gestion_prestamos.php">Préstamos</a></li>
        <?php endif; ?>

        <?php if ($rol === 'cliente'): ?>
            <li class="nav-item"><a class="nav-link" href="estado_cuentas.php">Mi Cuenta</a></li>
            <li class="nav-item"><a class="nav-link" href="Crear-RealizarTransacciones.php">Transferencias</a></li>
        <?php endif; ?>
      </ul>

      <div class="d-flex align-items-center mt-3 mt-lg-0">
        <span class="me-4" style="color: var(--color-4); font-size: 0.9rem;">
          Rol: <strong style="color: var(--color-3);"><?php echo ucfirst($rol); ?></strong>
        </span>
        <a href="logout.php" class="btn btn-logout btn-sm text-decoration-none">Cerrar Sesión</a>
      </div>
    </div>
  </div>
</nav>
