<?php
session_start();
require_once "conexionDB.php";

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'cajero'])) {
    header("Location: login.php");
    exit;
}

try {
    $sentencia = $conexion->query("SELECT * FROM clientes ORDER BY nomb_cli ASC");
    $clientes  = $sentencia->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al consultar clientes: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Clientes - Banco Villavicencio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <?php if (file_exists("navbar.php")) include_once "navbar.php"; ?>

  <div class="container mt-4 mb-5">
      <div class="row">
          <div class="col-md-12">
              <div class="card shadow border-0 rounded-4">
                  <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                      <h4 class="mb-0 fs-5">Lista de Clientes - Banco Villavicencio</h4>
                      <a href="Crearcliente.php" class="btn btn-success btn-sm rounded-pill">Registrar Nuevo Cliente</a>
                  </div>
                  
                  <div class="card-body p-4">
                      <div class="table-responsive">
                          <table class="table table-striped table-bordered align-middle mb-0">
                              <thead class="table-dark">
                                  <tr>
                                      <th>Cédula / ID</th>
                                      <th>Nombre</th>
                                      <th>Apellido</th>
                                      <th>Dirección</th>
                                      <th>Teléfono</th>
                                      <th>Correo</th>
                                      <th>Acciones</th>
                                  </tr>
                              </thead>
                              <tbody>
                                  <?php if (!empty($clientes)): ?>
                                      <?php foreach($clientes as $cliente): ?>
                                          <tr>
                                              <td><strong><?php echo htmlspecialchars($cliente['id_cliente']); ?></strong></td>
                                              <td><?php echo htmlspecialchars($cliente['nomb_cli']); ?></td>
                                              <td><?php echo htmlspecialchars($cliente['apelli_cli']); ?></td>
                                              <td><?php echo htmlspecialchars($cliente['direccion'] ?? 'N/A'); ?></td>
                                              <td><?php echo htmlspecialchars($cliente['telefono'] ?? 'N/A'); ?></td>
                                              <td><?php echo htmlspecialchars($cliente['correo'] ?? 'N/A'); ?></td>
                                              <td>
                                                  <a href="editar_cliente.php?id=<?php echo urlencode($cliente['id_cliente']); ?>" class="btn btn-warning btn-sm rounded-pill">Editar</a>
                                              </td>
                                          </tr>
                                      <?php endforeach; ?>
                                  <?php else: ?>
                                      <tr>
                                          <td colspan="7" class="text-center text-muted py-4">No hay clientes registrados en la base de datos.</td>
                                      </tr>
                                  <?php endif; ?>
                              </tbody>
                          </table>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
        </div>
    </div>

</body>
</html>
  
