<?php
// login.php
session_start();
require_once "conexionDB.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario_rol = $_POST['usuario'] ?? '';
    $password    = trim($_POST['password'] ?? '');
    $cedula      = trim($_POST['cedula'] ?? '');
    $id_empleado = trim($_POST['id_empleado'] ?? '');

    try {
        if ($usuario_rol === 'administrador') {
            if (!empty($id_empleado) && !empty($password)) {
                $stmt = $conexion->prepare("SELECT * FROM empleados WHERE id_empleado = :id AND rol = 'administrador'");
                $stmt->execute(['id' => $id_empleado]);
                $emp = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($emp && $password === 'admin123') {
                    $_SESSION['usuario'] = $emp['nombres'] . ' ' . $emp['apellidos'];
                    $_SESSION['rol']     = 'administrador';
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "ID de administrador o contraseña incorrectos.";
                }
            } else {
                $error = "Por favor ingrese su ID de empleado y contraseña.";
            }

        } elseif ($usuario_rol === 'cajero') {
            if (!empty($id_empleado) && !empty($password)) {
                $stmt = $conexion->prepare("SELECT * FROM empleados WHERE id_empleado = :id AND rol = 'cajero'");
                $stmt->execute(['id' => $id_empleado]);
                $emp = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($emp && $password === 'cajero123') {
                    $_SESSION['usuario'] = $emp['nombres'] . ' ' . $emp['apellidos'];
                    $_SESSION['rol']     = 'cajero';
                    $_SESSION['id_cajero'] = $emp['id_empleado'];
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "ID de cajero o contraseña incorrectos.";
                }
            } else {
                $error = "Por favor ingrese su ID de empleado y contraseña.";
            }

        } elseif ($usuario_rol === 'cliente') {
            if (!empty($cedula) && !empty($password)) {
                if ($password === 'cliente123') {
                    $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_cliente = :cedula");
                    $stmt->execute(['cedula' => $cedula]);
                    $cliente_data = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($cliente_data) {
                        $_SESSION['usuario']    = $cliente_data['nomb_cli'] . ' ' . $cliente_data['apelli_cli'];
                        $_SESSION['rol']        = 'cliente';
                        $_SESSION['id_cliente'] = $cliente_data['id_cliente'];
                        header("Location: index.php");
                        exit;
                    } else {
                        $error = "La cedula ingresada no se encuentra registrada en el banco.";
                    }
                } else {
                    $error = "Contraseña de cliente incorrecta.";
                }
            } else {
                $error = "Por favor ingrese su cedula y contraseña.";
            }
        } else {
            $error = "Por favor seleccione un rol valido.";
        }
    } catch (PDOException $e) {
        $error = "Error de conexion a la base de datos: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-0e1b48: #0E1B48; 
            --color-c18db4: #C18DB4; 
            --color-e2cad8: #E2CAD8; 
            --color-87a7d0: #87A7D0; 
            --color-27425d: #27425D; 
            --color-0e1f2f: #0E1F2F; 
        }
        body {
            background: linear-gradient(135deg, var(--color-e2cad8) 0%, var(--color-87a7d0) 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
        }
        .card-custom {
            border-radius: 24px;
            border: none;
            box-shadow: 0 15px 35px rgba(14, 27, 72, 0.15);
            background-color: #ffffff;
        }
        .text-brand { color: var(--color-0e1b48); }
        .form-label { color: var(--color-0e1f2f); font-weight: 600; font-size: 0.9rem; }
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
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(14, 27, 72, 0.2);
        }
        .btn-custom:hover {
            background-color: var(--color-27425d);
            transform: translateY(-2px);
            color: var(--color-e2cad8);
        }
        .badge-accent {
            background-color: var(--color-c18db4);
            color: #ffffff;
            font-weight: 500;
        }
    </style>
    <script>
        function actualizarCampos() {
            var rol = document.getElementById('usuario-select').value;
            var campoCedula = document.getElementById('campo-cedula');
            var campoIdEmpleado = document.getElementById('campo-id-empleado');
            
            campoCedula.style.display = 'none';
            campoIdEmpleado.style.display = 'none';
            document.getElementById('cedula-input').required = false;
            document.getElementById('id-empleado-input').required = false;

            if (rol === 'cliente') {
                campoCedula.style.display = 'block';
                document.getElementById('cedula-input').required = true;
            } else if (rol === 'administrador' || rol === 'cajero') {
                campoIdEmpleado.style.display = 'block';
                document.getElementById('id-empleado-input').required = true;
            }
        }
    </script>
</head>
<body class="d-flex align-items-center vh-100">
    <div class="container" style="max-width: 420px;">
        <div class="card card-custom p-2">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <span class="badge badge-accent rounded-pill px-3 py-1 mb-2">Banca Segura</span>
                    <h3 class="fw-bold text-brand mb-1">Banco Villavicencio</h3>
                    <p class="text-muted small">Acceso al sistema por perfil</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small text-center rounded-4" style="background-color: #f8d7da; color: #842029; border: none;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label">Seleccionar Tipo de Usuario</label>
                        <select name="usuario" id="usuario-select" class="form-select form-control-custom" onchange="actualizarCampos()" required>
                            <option value="" disabled selected>-- Elige una opcion --</option>
                            <option value="administrador">Administrador</option>
                            <option value="cajero">Cajero</option>
                            <option value="cliente">Cliente</option>
                        </select>
                    </div>

                    <div class="mb-3" id="campo-id-empleado" style="display: none;">
                        <label class="form-label">ID de Empleado</label>
                        <input type="text" name="id_empleado" id="id-empleado-input" class="form-control form-control-custom" placeholder="Ej: 2001001001">
                    </div>

                    <div class="mb-3" id="campo-cedula" style="display: none;">
                        <label class="form-label">Numero de Cedula (ID Cliente)</label>
                        <input type="text" name="cedula" id="cedula-input" class="form-control form-control-custom" placeholder="Ej: 1006123456">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control form-control-custom" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-custom w-100 mt-2">Iniciar Sesion</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
