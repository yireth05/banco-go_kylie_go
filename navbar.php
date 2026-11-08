<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$rol = $_SESSION['rol'] ?? 'invitado';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold text-warning" href="index.php">Banco GO KYLIE GO</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="index.php">Inicio</a>
        </li>
        
        <!-- Opciones visibles solo para Administrador y Cajero -->
        <?php if ($rol === 'administrador' || $rol === 'cajero'): ?>
            <li class="nav-item"><a class="nav-link" href="listadeClientesRead.php">Clientes</a></li>
            <li class="nav-item"><a class="nav-link" href="Crearcliente.php">Registrar Cliente</a></li>
            <li class="nav-item"><a class="nav-link" href="estado_cuentas.php">Estado de Cuentas</a></li>
            <li class="nav-item"><a class="nav-link" href="Crear-RealizarTransacciones.php">Transacciones</a></li>
        <?php endif; ?>

        <!-- Opciones visibles solo para Administrador -->
        <?php if ($rol === 'administrador'): ?>
            <li class="nav-item"><a class="nav-link" href="gestion_prestamos.php">Préstamos</a></li>
        <?php endif; ?>

        <!-- Opción visible para el Cliente -->
        <?php if ($rol === 'cliente'): ?>
            <li class="nav-item"><a class="nav-link" href="estado_cuentas.php">Mi Cuenta</a></li>
            <li class="nav-item"><a class="nav-link" href="Crear-RealizarTransacciones.php">Transferencias</a></li>
        <?php endif; ?>
      </ul>
      <span class="navbar-text text-white me-3">
        Rol: <strong class="text-warning"><?php echo ucfirst($rol); ?></strong>
      </span>
      <a href="logout.php" class="btn btn-outline-danger btn-sm">Cerrar Sesión</a>
    </div>
  </div>
</nav>
