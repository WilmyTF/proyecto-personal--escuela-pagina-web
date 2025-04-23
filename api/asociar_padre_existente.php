<?php
header('Content-Type: application/json');
require_once '../includes/conexion.php';
verificarConexion();

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener y validar los datos
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['solicitud_id']) || !isset($data['padre_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    // Iniciar transacción
    $conn->beginTransaction();

    // 1. Verificar que la solicitud existe y está pendiente
    $stmt = $conn->prepare("SELECT * FROM solicitud_admision WHERE id_solicitud = ? AND estado = 'pendiente'");
    $stmt->execute([$data['solicitud_id']]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        throw new Exception('Solicitud no encontrada o ya procesada');
    }

    // 2. Verificar que el padre existe
    $stmt = $conn->prepare("SELECT * FROM padres_tutores WHERE id = ?");
    $stmt->execute([$data['padre_id']]);
    $padre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$padre) {
        throw new Exception('Padre/Tutor no encontrado');
    }

    // 3. Actualizar la solicitud con el ID del padre
    $stmt = $conn->prepare("
        UPDATE solicitud_admision 
        SET padre_id = ?, 
            estado = 'padre_asociado'
        WHERE id_solicitud = ?
    ");

    $stmt->execute([$data['padre_id'], $data['solicitud_id']]);

    // Confirmar transacción
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Padre asociado correctamente'
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollBack();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al asociar el padre: ' . $e->getMessage()
    ]);
} 