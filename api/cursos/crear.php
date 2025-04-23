<?php
require_once '../../includes/conexion.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = $_POST;
    
    if (empty($data['nombre']) || empty($data['docente_id']) || empty($data['cupo_maximo']) || empty($data['aula_id'])) {
        throw new Exception('Faltan campos requeridos');
    }

    verificarConexion();

    $stmt = $conn->prepare("INSERT INTO public.cursos (nombre, descripcion, docente_id, cupo_maximo, aula_id) 
                          VALUES (:nombre, :descripcion, :docente_id, :cupo_maximo, :aula_id) RETURNING id");
    
    $stmt->execute([
        ':nombre' => $data['nombre'],
        ':descripcion' => $data['descripcion'] ?? '',
        ':docente_id' => $data['docente_id'],
        ':cupo_maximo' => $data['cupo_maximo'],
        ':aula_id' => $data['aula_id']
    ]);

    $id = $stmt->fetchColumn();
    echo json_encode(['success' => true, 'id' => $id]);
} catch(Exception $e) {
    error_log('Error en crear.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => 'Error al crear el curso'
    ]);
}
?> 