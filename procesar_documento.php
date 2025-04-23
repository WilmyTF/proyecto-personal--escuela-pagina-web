<?php
include 'includes/conexion.php';

header('Content-Type: application/json');

try {
    // Validar la conexión
    if (!$conn) {
        throw new Exception("Error de conexión a la base de datos");
    }

    // Validar que se recibió el archivo y los datos necesarios
    if (!isset($_FILES['documento']) || !isset($_POST['id_documento_requerido'])) {
        throw new Exception("Datos incompletos");
    }

    $archivo = $_FILES['documento'];
    $id_documento_requerido = $_POST['id_documento_requerido'];
    $id_solicitud = $_SESSION['id_solicitud_actual'] ?? null;

    if (!$id_solicitud) {
        throw new Exception("No se encontró una solicitud activa");
    }

    // Validar el archivo
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error al subir el archivo");
    }

    // Validar el tipo de archivo
    $tipos_permitidos = ['image/jpeg', 'image/png', 'application/pdf'];
    $tipo_archivo = mime_content_type($archivo['tmp_name']);
    
    if (!in_array($tipo_archivo, $tipos_permitidos)) {
        throw new Exception("Tipo de archivo no permitido. Solo se permiten JPG, PNG y PDF");
    }

    // Crear directorio si no existe
    $upload_dir = "uploads/documentos/solicitud_" . $id_solicitud . "/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generar nombre único para el archivo
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $nombre_archivo = uniqid() . '_' . time() . '.' . $extension;
    $ruta_completa = $upload_dir . $nombre_archivo;

    // Mover el archivo
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        throw new Exception("Error al guardar el archivo");
    }

    // Iniciar transacción
    pg_query($conn, "BEGIN");

    // Verificar si ya existe un documento para esta solicitud y tipo
    $query = "SELECT id_documento FROM solicitud_admision_documento 
              WHERE id_solicitud = $1 AND id_documento_requerido = $2";
    $result = pg_query_params($conn, $query, array($id_solicitud, $id_documento_requerido));

    if (pg_num_rows($result) > 0) {
        // Actualizar documento existente
        $row = pg_fetch_assoc($result);
        $query = "UPDATE solicitud_admision_documento 
                 SET url_documento = $1, fecha_carga = CURRENT_TIMESTAMP 
                 WHERE id_documento = $2";
        $result = pg_query_params($conn, $query, array($ruta_completa, $row['id_documento']));
    } else {
        // Insertar nuevo documento
        $query = "INSERT INTO solicitud_admision_documento 
                 (id_solicitud, id_documento_requerido, url_documento) 
                 VALUES ($1, $2, $3)";
        $result = pg_query_params($conn, $query, array($id_solicitud, $id_documento_requerido, $ruta_completa));
    }

    if (!$result) {
        throw new Exception("Error al guardar el documento en la base de datos");
    }

    // Confirmar transacción
    pg_query($conn, "COMMIT");

    echo json_encode([
        'success' => true,
        'message' => 'Documento subido correctamente'
    ]);

} catch (Exception $e) {
    // Revertir transacción si está activa
    if (isset($conn)) {
        pg_query($conn, "ROLLBACK");
    }

    // Si se subió un archivo, eliminarlo
    if (isset($ruta_completa) && file_exists($ruta_completa)) {
        unlink($ruta_completa);
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 