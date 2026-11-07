<?php
include_once "conexionDB.php"

$busqueda=$_GET['busqueda'] ?? '';
$cuentas=[];
if($busqueda! =''){
  $sql="SELECT c.*,cli.nomb_cli, cli.apelli_cli FROM cuentas c
        JOIN clientes cli ON cli.id_cliente=c.ide_cliente
        WHERE c.id_cliente LIKE ? OR c.num_cuenta LIKE ?";
$stmt=$conexion->query($sql);
$cuentas=$stmt->fetchAll(PDO::FETCH_ASSOC);
}else{
  $sql="SELECT c.*, cli.nomb_cli, cli.apelli_cli FROM cuentas c
        JOIN clientes cli ON c.id_cliente=cli.id_cliente";
  $stmt=$conexion->query($sql);
  $cuentas=$stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estado de Cuentas - Banco Villavicencio</title>
    <!-- Estilos de Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Estado de Cuentas - Banco Villavicencio</h4>
                        <a href="Crear-RealizarTransacciones.php" class="btn btn-success btn-sm">Hacer Transacción</a>
                    </div>
                    
                    <div class="card-body">
                        
                        <!-- Barra de búsqueda rápida -->
                        <form method="GET" action="" class="row mb-4">
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="busqueda" placeholder="Buscar por Cédula del cliente o Número de cuenta..." value="<?php echo htmlspecialchars($busqueda); ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-outline-primary w-100">Buscar Cuenta</button>
                            </div>
                        </form>

                        <!-- Tabla de resultados -->
                        <table class="table table-striped table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Nº Cuenta</th>
                                    <th>Titular</th>
                                    <th>Cédula (ID)</th>
                                    <th>Saldo Actual</th>
                                    <th>Tipo de Cuenta</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($cuentas) > 0) { ?>
                                    <?php foreach($cuentas as $cuenta) { ?>
                                        <tr>
                                            <td><strong><?php echo $cuenta['num_cuenta']; ?></strong></td>
                                            <td><?php echo $cuenta['nomb_cli'] . ' ' . $cuenta['apelli_cli']; ?></td>
                                            <td><?php echo $cuenta['id_cliente']; ?></td>
                                            <!-- Pintamos el saldo con formato de dinero y color verdecito si tiene plata -->
                                            <td class="text-success fw-bold">$<?php echo number_format($cuenta['saldo'], 2); ?></td>
                                            <td><?php echo $cuenta['cod_tipo']; ?></td>
                                            <td>
                                                <a href="Crear-RealizarTransacciones.php?cuenta=<?php echo $cuenta['num_cuenta']; ?>" class="btn btn-primary btn-sm">Transaccionar</a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No se encontraron cuentas registradas o coincidentes.</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
