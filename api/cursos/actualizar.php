<?php
require_once '../../includes/conexion.php';
verificarConexion();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = $_POST;
    
    if (empty($data['curso_id']) || empty($data['nombre_curso']) || empty($data['docente_id']) || 
        empty($data['cupo_maximo']) || empty($data['aula_id'])) {
        throw new Exception('Faltan campos requeridos');
    }

    $stmt = $conn->prepare("UPDATE cursos 
                          SET nombre = :nombre, 
                              descripcion = :descripcion, 
                              docente_id = :docente_id, 
                              cupo_maximo = :cupo_maximo, 
                              aula_id = :aula_id 
                          WHERE id = :id");
    
    $stmt->execute([
        ':id' => $data['curso_id'],
        ':nombre' => $data['nombre_curso'],
        ':descripcion' => $data['descripcion'] ?? '',
        ':docente_id' => $data['docente_id'],
        ':cupo_maximo' => $data['cupo_maximo'],
        ':aula_id' => $data['aula_id']
    ]);

    echo json_encode(['success' => true]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 