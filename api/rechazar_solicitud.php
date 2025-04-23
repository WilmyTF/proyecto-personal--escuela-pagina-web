<?php
header('Content-Type: application/json');
require_once '../includes/conexion.php';

// Obtener los datos enviados por POST
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['solicitud_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de solicitud no proporcionado'
    ]);
    exit;
}

$solicitud_id = $data['solicitud_id'];

try {
    verificarConexion();
    
    // Consulta para actualizar el estado de la solicitud a 'rechazada'
    $query = "UPDATE solicitud_admision SET estado = 'rechazada' WHERE id_solicitud = ?";
    
    $stmt = $conn->prepare($query);
    $resultado = $stmt->execute([$solicitud_id]);
    
    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Solicitud rechazada correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo actualizar la solicitud'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
    ]);
    
    // Registrar el error en el log
    error_log('Error en rechazar_solicitud.php: ' . $e->getMessage());
}
?> 