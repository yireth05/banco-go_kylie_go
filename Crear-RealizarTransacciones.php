try {
        $sql = "SELECT realizar_transaccion(:cliente, :cuenta, :tipo, :forma, :monto, :cajero, :banca)";
        $stmt = $conexion->prepare($sql);
        
        $stmt->execute([
            ':cliente' => $id_cliente,
            ':cuenta'  => $num_cuenta,
            ':tipo'    => $cod_tipo,
            ':forma'   => $cod_forma,
            ':monto'   => $monto,
            ':cajero'  => $id_cajero,
            ':banca'   => $id_banca_virtual
        ]);
        
        $mensaje = "<div class='alert alert-success alert-dismissible fade show' role='alert'>¡Transacción realizada con éxito!<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    
    } catch (Exception $e) {
        // Capturamos el mensaje completo de la base de datos
        $errorCompleto = $e->getMessage();
        
        // Extraemos limpiamente el texto del error que viene después de "ERROR:"
        if (preg_match('/ERROR:\s*(.*?)(?=\nCONTEXT:|$)/s', $errorCompleto, $coincidencias)) {
            $mensajeAmigable = trim($coincidencias[1]);
        } else {
            $mensajeAmigable = $errorCompleto; // Por si acaso toma el texto completo
        }

        // Mostramos el error limpio y legible para el cajero
        $mensaje = "<div class='alert alert-danger alert-dismissible fade show' role='alert'><strong>Atención:</strong> " . htmlspecialchars($mensajeAmigable) . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    }
