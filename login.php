<?php
// login.php
ob_start();
session_start();
require_once "conexionDB.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario_rol = $_POST['usuario'] ?? '';
    $password    = trim($_POST['password'] ?? '');
    $cedula      = trim($_POST['cedula'] ?? '');

    try {
        if ($usuario_rol === 'administrador' || $usuario_rol === 'cajero') {
            $stmt = $conexion->prepare("SELECT * FROM empleados WHERE LOWER(rol) = :rol AND id_empleado = :cedula");
            $stmt->execute([':rol' => $usuario_rol, ':cedula' => $cedula]);
            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($empleado) {
                $nombreEmp   = (string)($empleado['nomb_emp'] ?? $empleado['nombres'] ?? '');
                $apellidoEmp = (string)($empleado['apelli_emp'] ?? $empleado['apellidos'] ?? '');
                $passBD      = (string)($empleado['password'] ?? '');
                
                $claveDinamica = strtolower($nombreEmp) . "123";
                $ok = false;

                // Validación directa de contraseñas de respaldo y dinámicas
                if ($password === $claveDinamica || 
                    $password === "cajero123" || 
                    $password === "administrador123" || 
                    $password === "admin123" || 
                    $password === ($usuario_rol . "123")) {
                    $ok = true;
                } 
                // Si no es ninguna de las anteriores, verificar si coincide con la base de datos (hash o texto plano)
                elseif ($passBD !== '') {
                    if (password_verify($password, $passBD) || hash_equals($passBD, $password)) {
                        $ok = true;
                    }
                }

                if ($ok) {
                    session_regenerate_id(true);
                    $_SESSION['nombres']    = trim($nombreEmp . ' ' . $apellidoEmp);
                    $_SESSION['rol']        = strtolower($empleado['rol']);
                    $_SESSION['usuario_id'] = $empleado['id_empleado'];
                    header("Location: index.php");
                    exit;
                }
                $error = "Contraseña incorrecta para el perfil de " . ucfirst($usuario_rol) . ".";
            } else {
                $error = "La cédula ingresada no corresponde a un " . ucfirst($usuario_rol) . ".";
            }

        } elseif ($usuario_rol === 'cliente') {
            $stmt = $conexion->prepare("SELECT * FROM clientes WHERE id_cliente = :cedula");
            $stmt->execute([':cedula' => $cedula]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cliente) {
                $hash = (string)($cliente['password'] ?? '');
                $claveDinamicaCliente = strtolower($cliente['nomb_cli'] ?? '') . "123";
                $ok = false;

                if ($password === $claveDinamicaCliente || $password === "cliente123") {
                    $ok = true;
                } elseif ($hash !== '') {
                    if (password_verify($password, $hash) || hash_equals($hash, $password)) {
                        $ok = true;
                    }
                }

                if ($ok) {
                    session_regenerate_id(true);
                    $_SESSION['nombres']    = trim(($cliente['nomb_cli'] ?? '') . ' ' . ($cliente['apelli_cli'] ?? ''));
                    $_SESSION['rol']        = 'cliente';
                    $_SESSION['usuario_id'] = $cliente['id_cliente'];
                    header("Location: index.php");
                    exit;
                }
                $error = "Contraseña incorrecta para el cliente.";
            } else {
                $error = "La cédula ingresada no se encuentra registrada en la base de datos.";
            }
        } else {
            $error = "Por favor selecciona un rol válido.";
        }
    } catch (PDOException $e) {
        $error = "Error de autenticación: " . $e->getMessage();
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
        function toggleCedula(select) {
            var campoCedula = document.getElementById('campo-cedula');
            var labelCedula = document.getElementById('label-cedula');
            
            if (select.value === 'cliente') {
                campoCedula.style.display = 'block';
                labelCedula.innerText = 'Número de Cédula (ID Cliente)';
                document.getElementById('cedula-input').required = true;
            } else if (select.value === 'administrador' || select.value === 'cajero') {
                campoCedula.style.display = 'block';
                labelCedula.innerText = 'Identificación / Cédula de Empleado';
                document.getElementById('cedula-input').required = true;
            } else {
                campoCedula.style.display = 'none';
                document.getElementById('cedula-input').required = false;
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

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small text-center rounded-4" style="background-color: #f8d7da; color: #842029; border: none;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label class="form-label">Seleccionar Tipo de Usuario</label>
                        <select name="usuario" id="usuario-select" class="form-select form-control-custom" onchange="toggleCedula(this)" required>
                            <option value="" disabled selected>-- Elige una opción --</option>
                            <option value="administrador">Administrador</option>
                            <option value="cajero">Cajero</option>
                            <option value="cliente">Cliente</option>
                        </select>
                    </div>

                    <div class="mb-3" id="campo-cedula" style="display: none;">
                        <label class="form-label" id="label-cedula">Identificación / Cédula</label>
                        <input type="text" name="cedula" id="cedula-input" class="form-control form-control-custom" placeholder="Ingrese ID o Cédula">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control form-control-custom" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-custom w-100 mt-2">Iniciar Sesión</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
