<?php
header('Content-Type: application/json');
require_once '../includes/conexion.php';

if (!isset($_GET['solicitud_id'])) {
    echo json_encode(['error' => 'ID de solicitud no proporcionado']);
    exit;
}

$solicitud_id = $_GET['solicitud_id'];

try {
    verificarConexion();
    
    // Consulta para obtener todos los datos de la solicitud
    $query = "SELECT * FROM solicitud_admision WHERE id_solicitud = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$solicitud_id]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solicitud) {
        // Datos de muestra si no se encuentra la solicitud
        $solicitud = [
            'id_solicitud' => $solicitud_id,
            'nombre_estudiante' => 'Datos',
            'apellido_estudiante' => 'No encontrados',
            'direccion_estudiante' => 'No disponible',
            'grado_cursar' => 'No disponible',
            'especialidad' => 'No disponible',
            'estado' => 'pendiente',
            'fecha_solicitud' => date('Y-m-d H:i:s')
        ];
    }
    
    echo json_encode($solicitud);
    
} catch (Exception $e) {
    // Devolver datos mínimos para evitar errores en el frontend
    echo json_encode([
        'id_solicitud' => $solicitud_id,
        'nombre_estudiante' => 'Error',
        'apellido_estudiante' => 'del sistema',
        'direccion_estudiante' => 'No disponible',
        'estado' => 'pendiente'
    ]);
    
    // Registrar el error en el log
    error_log('Error en get_solicitud.php: ' . $e->getMessage());
}
?> 