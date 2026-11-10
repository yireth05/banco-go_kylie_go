<?php
// cambiar_password.php
// - Cualquier usuario (cliente, cajero o administrador) cambia su propia contraseña.
// - El administrador puede restablecer la de cualquier cliente o empleado por cédula / ID.
ob_start();
session_start();
require_once "conexionDB.php";

if (!isset($_SESSION['rol']) || !isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$rol        = $_SESSION['rol'];
$usuario_id = (string)$_SESSION['usuario_id'];
$es_admin   = ($rol === 'administrador');

$mensaje       = "";
$error         = "";
$mensaje_admin = "";
$error_admin   = "";

// Tabla e ID según quién es el usuario que inició sesión
$tabla_propia = ($rol === 'cliente') ? 'clientes' : 'empleados';
$col_id_propia = ($rol === 'cliente') ? 'id_cliente' : 'id_empleado';

// Crea la columna password en la tabla si no existe (solo hace ALTER cuando hace falta).
// Los hashes miden unos 60 caracteres, por eso la columna debe ser de al menos 255.
function asegurar_columna_password(PDO $db, string $tabla): void {
    $q = $db->prepare(
        "SELECT character_maximum_length FROM information_schema.columns
         WHERE table_schema = current_schema() AND table_name = :t AND column_name = 'password'"
    );
    $q->execute([':t' => $tabla]);
    $fila = $q->fetch(PDO::FETCH_ASSOC);

    if ($fila === false) {
        $db->exec("ALTER TABLE $tabla ADD COLUMN password character varying(255)");
    } elseif ($fila['character_maximum_length'] !== null && (int)$fila['character_maximum_length'] < 255) {
        $db->exec("ALTER TABLE $tabla ALTER COLUMN password TYPE character varying(255)");
    }
}

// Compara lo escrito con lo guardado (hash, o texto plano antiguo).
function coincide_password(string $escrita, string $guardada): bool {
    if ($guardada === '') return false;
    if (!empty(password_get_info($guardada)['algo'])) {
        return password_verify($escrita, $guardada);
    }
    return hash_equals($guardada, $escrita);
}

function validar_password_nueva(string $pass): ?string {
    if (strlen($pass) < 4 || strlen($pass) > 20) {
        return "La nueva contraseña debe tener entre 4 y 20 caracteres.";
    }
    return null;
}

// Asegura la columna en ambas tablas (si no hay permisos, el error se muestra al intentar guardar)
try {
    asegurar_columna_password($conexion, 'empleados');
    asegurar_columna_password($conexion, 'clientes');
} catch (PDOException $e) {
    $error = "No se pudo preparar la columna password: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // ---------- Cambiar MI contraseña ----------
    if ($accion === 'cambiar_propia') {
        $actual    = trim($_POST['password_actual'] ?? '');
        $nueva     = trim($_POST['nueva_password'] ?? '');
        $confirmar = trim($_POST['confirmar_password'] ?? '');

        if ($nueva === '' || $confirmar === '') {
            $error = "Escribe la nueva contraseña y su confirmación.";
        } elseif ($err = validar_password_nueva($nueva)) {
            $error = $err;
        } elseif ($nueva !== $confirmar) {
            $error = "La nueva contraseña y su confirmación no coinciden.";
        } else {
            try {
                $q = $conexion->prepare("SELECT password FROM $tabla_propia WHERE $col_id_propia = ?");
                $q->execute([$usuario_id]);
                $guardada = $q->fetchColumn();

                if ($guardada === false) {
                    $error = "No se encontró tu usuario en la base de datos.";
                } elseif ((string)$guardada !== '' && !coincide_password($actual, (string)$guardada)) {
                    // Si ya tenía contraseña, se exige la actual
                    $error = "La contraseña actual no es correcta.";
                } else {
                    $up = $conexion->prepare("UPDATE $tabla_propia SET password = ? WHERE $col_id_propia = ?");
                    $up->execute([password_hash($nueva, PASSWORD_DEFAULT), $usuario_id]);
                    $mensaje = "¡Tu contraseña fue cambiada exitosamente!";
                }
            } catch (PDOException $e) {
                $error = "Error al cambiar la contraseña: " . $e->getMessage();
            }
        }
    }

    // ---------- ADMIN: restablecer la contraseña de otro usuario ----------
    elseif ($accion === 'restablecer' && $es_admin) {
        $tipo   = $_POST['tipo_usuario'] ?? '';
        $cedula = trim($_POST['cedula'] ?? '');
        $nueva  = trim($_POST['nueva_password_admin'] ?? '');

        if (!in_array($tipo, ['cliente', 'empleado'], true)) {
            $error_admin = "Selecciona si es cliente o empleado.";
        } elseif ($cedula === '' || $nueva === '') {
            $error_admin = "Escribe la cédula / ID y la nueva contraseña.";
        } elseif ($err = validar_password_nueva($nueva)) {
            $error_admin = $err;
        } else {
            $tabla  = ($tipo === 'cliente') ? 'clientes' : 'empleados';
            $col_id = ($tipo === 'cliente') ? 'id_cliente' : 'id_empleado';
            try {
                $chk = $conexion->prepare("SELECT 1 FROM $tabla WHERE $col_id = ?");
                $chk->execute([$cedula]);
                if (!$chk->fetchColumn()) {
                    $error_admin = "No existe un " . $tipo . " con esa cédula / ID.";
                } else {
                    $up = $conexion->prepare("UPDATE $tabla SET password = ? WHERE $col_id = ?");
                    $up->execute([password_hash($nueva, PASSWORD_DEFAULT), $cedula]);
                    $mensaje_admin = "Contraseña del " . $tipo . " " . $cedula . " restablecida exitosamente.";
                }
            } catch (PDOException $e) {
                $error_admin = "Error al restablecer la contraseña: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Contraseña - Banco Villavicencio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-0e1b48: #0E1B48; --color-c18db4: #C18DB4;
            --color-e2cad8: #E2CAD8; --color-87a7d0: #87A7D0;
        }
        body { background: linear-gradient(135deg, #f8f9fa 0%, var(--color-e2cad8) 100%); min-height: 100vh; }
        .card-custom { border-radius: 16px; border: none; box-shadow: 0 8px 24px rgba(14, 27, 72, 0.08); }
        .btn-custom { background-color: var(--color-c18db4); color: #fff; border-radius: 50rem; font-weight: 600; }
        .btn-custom:hover { background-color: var(--color-87a7d0); color: var(--color-0e1b48); }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold" style="color: var(--color-0e1b48);">Mi Contraseña</h2>
                <p class="text-muted mb-0">Cambia tu contraseña de acceso al sistema.</p>
            </div>
            <a href="index.php" class="btn btn-custom px-4 py-2">← Volver al Panel</a>
        </div>

        <?php if ($error): ?><div class="alert alert-danger rounded-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($mensaje): ?><div class="alert alert-success rounded-4"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-<?php echo $es_admin ? '6' : '8 mx-auto'; ?>">
                <div class="card card-custom p-4 bg-white h-100">
                    <h4 class="fw-bold mb-3" style="color: var(--color-0e1b48);">Cambiar mi contraseña</h4>
                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="accion" value="cambiar_propia">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Contraseña actual</label>
                            <input type="password" name="password_actual" class="form-control" autocomplete="current-password">
                            <div class="form-text">Si todavía no tienes una contraseña guardada, puedes dejarla vacía.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nueva contraseña</label>
                            <input type="password" name="nueva_password" class="form-control" minlength="4" maxlength="20" required autocomplete="new-password">
                            <div class="form-text">Entre 4 y 20 caracteres.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Confirmar nueva contraseña</label>
                            <input type="password" name="confirmar_password" class="form-control" minlength="4" maxlength="20" required autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-custom w-100 py-2">Guardar Contraseña</button>
                    </form>
                </div>
            </div>

            <?php if ($es_admin): ?>
            <div class="col-lg-6">
                <div class="card card-custom p-4 bg-white h-100">
                    <h4 class="fw-bold mb-1" style="color: var(--color-0e1b48);">Restablecer contraseña de otro usuario <span class="badge bg-secondary fs-6">solo administrador</span></h4>
                    <p class="text-muted small mb-3">Útil para asignar contraseñas a usuarios que la tienen vacía.</p>

                    <?php if ($error_admin): ?><div class="alert alert-danger rounded-4"><?php echo htmlspecialchars($error_admin); ?></div><?php endif; ?>
                    <?php if ($mensaje_admin): ?><div class="alert alert-success rounded-4"><?php echo htmlspecialchars($mensaje_admin); ?></div><?php endif; ?>

                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="accion" value="restablecer">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de usuario</label>
                            <select name="tipo_usuario" class="form-select" required>
                                <option value="" disabled selected>-- Elige una opción --</option>
                                <option value="cliente">Cliente</option>
                                <option value="empleado">Empleado (cajero / administrador)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Cédula / ID</label>
                            <input type="text" name="cedula" class="form-control" required placeholder="Ej: 1006123456">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nueva contraseña</label>
                            <input type="password" name="nueva_password_admin" class="form-control" minlength="4" maxlength="20" required autocomplete="new-password">
                            <div class="form-text">Entre 4 y 20 caracteres. Se guarda cifrada.</div>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 py-2">Restablecer Contraseña</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
