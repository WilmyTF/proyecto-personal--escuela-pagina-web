<?php
require_once '../../includes/conexion.php';
verificarConexion();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = $_POST;
    
    if (empty($data['aula_id']) || empty($data['nombre_aula']) || empty($data['capacidad']) || empty($data['tipo'])) {
        throw new Exception('Faltan campos requeridos');
    }

    $stmt = $conn->prepare("UPDATE aulas 
                          SET nombre = :nombre, 
                              capacidad = :capacidad, 
                              tipo = :tipo 
                          WHERE id = :id");
    
    $stmt->execute([
        ':id' => $data['aula_id'],
        ':nombre' => $data['nombre_aula'],
        ':capacidad' => $data['capacidad'],
        ':tipo' => $data['tipo']
    ]);

    echo json_encode(['success' => true]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 