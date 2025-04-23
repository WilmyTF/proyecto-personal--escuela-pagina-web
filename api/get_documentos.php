<?php
header('Content-Type: application/json');
require_once '../includes/conexion.php';

if (!isset($_GET['solicitud_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de solicitud no proporcionado']);
    exit;
}

$solicitud_id = $_GET['solicitud_id'];

try {
    verificarConexion();
    
    // Verificar primero si la tabla existe y tiene estructura correcta
    try {
        $checkTableQuery = "SELECT column_name FROM information_schema.columns 
                           WHERE table_name = 'solicitud_admision_documento'";
        $checkStmt = $conn->query($checkTableQuery);
        $columns = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Si la tabla no está configurada correctamente, devolvemos un array vacío sin error
        if (!in_array('id_documento_requerido', $columns)) {
            echo json_encode([]);
            exit;
        }
    } catch (Exception $e) {
        // Si hay algún error, simplemente devolvemos un array vacío
        echo json_encode([]);
        exit;
    }
    
    // Consulta para obtener los documentos de la solicitud
    $query = "
        SELECT 
            d.nombre as nombre_documento,
            s.url_documento
        FROM solicitud_admision_documento s
        JOIN documentos_requerido_admision d ON s.id_documento_requerido = d.id_documento_requerido
        WHERE s.id_solicitud = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$solicitud_id]);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($documentos);
    
} catch (Exception $e) {
    // Para evitar errores en el frontend, devolvemos un array vacío
    echo json_encode([]);
    
    // Registrar el error en el log
    error_log('Error en get_documentos.php: ' . $e->getMessage());
}
?> 