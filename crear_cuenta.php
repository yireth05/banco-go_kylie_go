<?php
// crear_cuenta.php
include_once "conexionDB.php";

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $num_cuenta      = trim($_POST["num_cuenta"]);
    $id_cliente      = trim($_POST["id_cliente"]);
    $cod_tipo_cuenta = (int)$_POST["cod_tipo_cuenta"];
    $saldo_inicial   = (float)$_POST["saldo_inicial"];

    try {
        $sql = "INSERT INTO cuentas (num_cuenta, saldo, id_cliente, cod_tipo_cuenta) 
                VALUES (:num, :saldo, :cli, :tipo)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':num'   => $num_cuenta,
            ':saldo' => $saldo_inicial,
            ':cli'   => $id_cliente,
            ':tipo'  => $cod_tipo_cuenta
        ]);
        $mensaje = "<div class='alert alert-success'>✅ Cuenta creada exitosamente.</div>";
    } catch (PDOException $e) {
        $mensaje = "<div class='alert alert-danger'>❌ Error al crear la cuenta: " . $e->getMessage() . "</div>";
    }
}

// Obtener lista de clientes y tipos de cuenta para los select
$clientes = $conexion->query("SELECT id_cliente, nomb_cli, apelli_cli FROM clientes ORDER BY nomb_cli")->fetchAll();
$tipos_cuenta = $conexion->query("SELECT * FROM tipo_cuentas")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Apertura de Cuenta - Banco GO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow border-0">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🏦 Apertura de Nueva Cuenta</h4>
                    </div>
                    <div class="card-body">
                        <?= $mensaje ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Número de Cuenta (*)</label>
                                <input type="text" name="num_cuenta" class="form-control" placeholder="Ej: AH-0008" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Titular / Cliente (*)</label>
                                <select name="id_cliente" class="form-select" required>
                                    <option value="" disabled selected>Seleccione un cliente...</option>
                                    <?php foreach ($clientes as $c): ?>
                                        <option value="<?= $c['id_cliente'] ?>">
                                            <?= htmlspecialchars($c['nomb_cli'] . ' ' . $c['apelli_cli'] . ' (' . $c['id_cliente'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tipo de Cuenta (*)</label>
                                <select name="cod_tipo_cuenta" class="form-select" required>
                                    <?php foreach ($tipos_cuenta as $t): ?>
                                        <option value="<?= $t['cod_tipo_cuenta'] ?>"><?= htmlspecialchars($t['nomb_tipo_cuenta']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Saldo Inicial ($) (*)</label>
                                <input type="number" step="0.01" min="0" name="saldo_inicial" class="form-control" value="0.00" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Crear Cuenta</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
