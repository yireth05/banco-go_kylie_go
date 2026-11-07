<?php
include_once "conexionDB.php";

$sentencia=$conexion->query("SELECT * FROM clientes;");
$clientes=$sentencia->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <tittle>
    Listado de CLientes
  </tittle>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Lista de Clientes - Banco Villavicencio</h4>
                        <!-- Botón para ir al formulario de crear que ya teníamos -->
                        <a href="Crearcliente.php" class="btn btn-success btn-sm">Registrar Nuevo Cliente</a>
                    </div>
                    
                    <div class="card-body">
                        <table class="table table-striped table-bordered align-middle">
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
                                <?php foreach($clientes as $cliente) { ?>
                                    <tr>
                                        <td><?php echo $cliente['id_cliente']; ?></td>
                                        <td><?php echo $cliente['nomb_cli']; ?></td>
                                        <td><?php echo $cliente['apelli_cli']; ?></td>
                                        <td><?php echo $cliente['direccion']; ?></td>
                                        <td><?php echo $cliente['telefono']; ?></td>
                                        <td><?php echo $cliente['correo']; ?></td>
                                        <td>
                                              <!--botones-->
                                            <a href="editar_cliente.php?id=<?php echo $cliente['id_cliente']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                            <a href="eliminar_cliente.php?id=<?php echo $cliente['id_cliente']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que deseas eliminar este cliente?');">Eliminar</a>
                                        </td>
                                    </tr>
                                <?php 
                               } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
  
