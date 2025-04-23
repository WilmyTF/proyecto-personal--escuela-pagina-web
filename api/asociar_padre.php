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
$required_fields = ['solicitud_id', 'nombre', 'apellido', 'telefono', 'correo', 'direccion'];

foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Campo requerido faltante: $field"]);
        exit;
    }
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

    // 2. Insertar padre/tutor
    $stmt = $conn->prepare("
        INSERT INTO padres_tutores (
            nombre, apellido, telefono,
            correo, direccion
        ) VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $data['nombre'],
        $data['apellido'],
        $data['telefono'],
        $data['correo'],
        $data['direccion']
    ]);

    $padre_id = $conn->lastInsertId();

    // 3. Actualizar estado de la solicitud
    $stmt = $conn->prepare("
        UPDATE solicitud_admision 
        SET estado = 'aprobada'
        WHERE id_solicitud = ?
    ");

    $stmt->execute([$data['solicitud_id']]);

    // Confirmar transacción
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Padre registrado correctamente. La asociación se realizará al inscribir al estudiante.',
        'padre_id' => $padre_id
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conn->rollBack();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al registrar el padre: ' . $e->getMessage()
    ]);
} 