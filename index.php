<?php
session_start();
if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit;
}

$rol = $_SESSION['rol'];
// Aquí va tu código PHP para conectar a la BD, procesar el INSERT del formulario 
// y hacer el SELECT para llenar la tabla de la derecha.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Préstamos - GO KYLIE GO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --color-1: #0E1B48; /* Azul Marino */
            --color-2: #C18DB4; /* Rosa Oscuro */
            --color-3: #E2CAD8; /* Rosa Claro */
            --color-4: #87A7D0; /* Azul Claro */
            --color-5: #27425D; /* Azul Pizarra */
            --color-6: #0E1F2F; /* Azul Noche */
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, var(--color-3) 200%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        /* Tarjetas estilo Fintech */
        .card-custom {
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 30px rgba(14, 27, 72, 0.05);
            background-color: #ffffff;
            overflow: hidden; /* Para que la tabla no se salga de los bordes curvos */
        }
        
        /* Cabeceras de las tarjetas */
        .card-header-custom {
            background-color: var(--color-1);
            color: #ffffff;
            font-weight: 600;
            padding: 16px 20px;
            border-bottom: none;
            letter-spacing: 0.5px;
        }

        /* Estilos de Inputs (Formulario) */
        .form-label {
            color: var(--color-5);
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }
        .form-control-custom, .form-select-custom {
            border-radius: 12px;
            padding: 10px 14px;
            border: 2px solid #eef0f5;
            background-color: #fcfcfd;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        .form-control-custom:focus, .form-select-custom:focus {
            border-color: var(--color-4);
            box-shadow: 0 0 0 4px rgba(135, 167, 208, 0.2);
            background-color: #ffffff;
        }

        /* Botón Estético */
        .btn-custom {
            background-color: var(--color-1);
            color: #ffffff;
            border-radius: 50rem;
            padding: 12px 24px;
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(14, 27, 72, 0.2);
        }
        .btn-custom:hover {
            background-color: var(--color-5);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(39, 66, 93, 0.3);
            color: #ffffff;
        }

        /* Estilos de la Tabla */
        .table-custom {
            margin-bottom: 0;
        }
        .table-custom thead th {
            background-color: var(--color-6);
            color: var(--color-3);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: none;
            padding: 15px 12px;
        }
        .table-custom tbody td {
            padding: 15px 12px;
            vertical-align: middle;
            color: #444;
            border-bottom: 1px solid #f0f0f0;
        }
        .table-custom tbody tr:hover {
            background-color: #f8faff;
        }
        
        /* Colores tipográficos para montos */
        .text-monto { color: var(--color-4); font-weight: 600; }
        .text-deuda { color: var(--color-2); font-weight: 600; }
        
        /* Badge para la Tasa */
        .badge-tasa {
            background-color: var(--color-5);
            color: white;
            border-radius: 8px;
            padding: 5px 8px;
            font-weight: 500;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <!-- Incluir barra de navegación -->
    <?php include 'navbar.php'; ?>

    <div class="container-fluid px-4 py-3">
        <div class="row g-4">
            
            <!-- Columna Izquierda: Formulario -->
            <div class="col-lg-4">
                <div class="card card-custom h-100">
                    <div class="card-header-custom">
                        Solicitud y Aprobación de Préstamos
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="gestion_prestamos.php">
                            <div class="mb-3">
                                <label class="form-label">Código de Préstamo (*)</label>
                                <input type="text" name="id_prestamo" class="form-control form-control-custom" placeholder="Ej: PR-004" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Cliente / Titular (*)</label>
                                <select name="id_cliente" class="form-select form-select-custom" required>
                                    <option value="" disabled selected>Seleccione un cliente...</option>
                                    <!-- Aquí va tu bucle PHP para cargar opciones de clientes -->
                                    <option value="1006123456">Laura Gómez Rojas</option>
                                    <option value="1006987654">Andrés Pardo Medina</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Monto Aprobado ($) (*)</label>
                                <input type="number" step="0.01" name="monto" class="form-control form-control-custom" value="1000000.00" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tasa de Interés Mensual (%) (*)</label>
                                <input type="number" step="0.01" name="tasa" class="form-control form-control-custom" value="1.80" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Cuotas Aprobadas (*)</label>
                                <input type="number" name="cuotas" class="form-control form-control-custom" value="12" required>
                            </div>

                            <button type="submit" class="btn btn-custom w-100">Registrar Préstamo</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Tabla de Cartera -->
            <div class="col-lg-8">
                <div class="card card-custom h-100">
                    <div class="card-header-custom" style="background-color: var(--color-6);">
                        Cartera de Préstamos Vigentes
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-custom table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Cliente</th>
                                    <th>Monto</th>
                                    <th>Saldo Pendiente</th>
                                    <th>Tasa</th>
                                    <th>Cuotas</th>
                                    <th>Aprobación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Ejemplo de fila estática (Reemplazar con tu bucle PHP) -->
                                <tr>
                                    <td class="fw-bold">PR-002</td>
                                    <td>Andrés Pardo<br><small class="text-muted">Medina</small></td>
                                    <td class="text-monto">$5,000,000.00</td>
                                    <td class="text-deuda">$5,000,000.00</td>
                                    <td><span class="badge-tasa">2.10%</span></td>
                                    <td>12 <span class="text-muted small">mes(es)</span></td>
                                    <td class="text-muted small">2025-03-01</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">PR-001</td>
                                    <td>Laura Gómez<br><small class="text-muted">Rojas</small></td>
                                    <td class="text-monto">$10,000,000.00</td>
                                    <td class="text-deuda">$7,500,000.00</td>
                                    <td><span class="badge-tasa">1.80%</span></td>
                                    <td>24 <span class="text-muted small">mes(es)</span></td>
                                    <td class="text-muted small">2024-02-15</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
