<?php
include 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar la conexión
        if (!$conn) {
            throw new Exception("Error de conexión a la base de datos");
        }

        // Iniciar transacción
        pg_query($conn, "BEGIN");

        // Preparar la consulta para insertar la solicitud
        $query = "INSERT INTO solicitud_admision (
            nombre_estudiante, apellido_estudiante, direccion_estudiante, grado_cursar,
            nombre_tutor1, apellido_tutor1, direccion_tutor1, telefono_tutor1, correo_tutor1, relacion_tutor1,
            nombre_tutor2, apellido_tutor2, direccion_tutor2, telefono_tutor2, correo_tutor2, relacion_tutor2
        ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16) RETURNING id_solicitud";

        $result = pg_query_params($conn, $query, array(
            $_POST['nombre'], $_POST['apellido'], $_POST['direccion'], $_POST['grado'],
            $_POST['nombre_padre1'], $_POST['apellido_padre1'], $_POST['direccion_padre1'], 
            $_POST['telefono_padre1'], $_POST['correo_padre1'], $_POST['relacion_padre1'],
            $_POST['nombre_padre2'] ?: null, $_POST['apellido_padre2'] ?: null, 
            $_POST['direccion_padre2'] ?: null, $_POST['telefono_padre2'] ?: null, 
            $_POST['correo_padre2'] ?: null, $_POST['relacion_padre2'] ?: null
        ));

        if (!$result) {
            throw new Exception("Error al insertar la solicitud: " . pg_last_error($conn));
        }

        $row = pg_fetch_row($result);
        $id_solicitud = $row[0];

        // Directorio para guardar los documentos
        $upload_dir = "uploads/documentos/solicitud_" . $id_solicitud . "/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Array de documentos a procesar
        $documentos = [
            'acta_nacimiento',
            'cedula',
            'record_notas',
            'foto',
            'certificado_conducta',
            'certificado_medico',
            'tipo_sangre'
        ];

        // Procesar cada documento
        foreach ($documentos as $doc) {
            if (isset($_FILES[$doc]) && $_FILES[$doc]['error'] == 0) {
                $extension = pathinfo($_FILES[$doc]['name'], PATHINFO_EXTENSION);
                $nuevo_nombre = $doc . '_' . time() . '.' . $extension;
                $ruta_completa = $upload_dir . $nuevo_nombre;

                if (move_uploaded_file($_FILES[$doc]['tmp_name'], $ruta_completa)) {
                    $query_doc = "INSERT INTO solicitud_admision_documento (id_solicitud, url_documento) VALUES ($1, $2)";
                    $result_doc = pg_query_params($conn, $query_doc, array($id_solicitud, $ruta_completa));
                    
                    if (!$result_doc) {
                        throw new Exception("Error al registrar el documento: " . pg_last_error($conn));
                    }
                } else {
                    throw new Exception("Error al subir el documento: " . $doc);
                }
            }
        }

        // Confirmar transacción
        pg_query($conn, "COMMIT");

        // Redirigir con mensaje de éxito
        header("Location: admision.php?status=success");
        exit();

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        if (isset($conn)) {
            pg_query($conn, "ROLLBACK");
        }
        
        // Redirigir con mensaje de error
        header("Location: admision.php?status=error&message=" . urlencode($e->getMessage()));
        exit();
    }
}
?> 