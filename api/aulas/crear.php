<?php
require_once '../../includes/conexion.php';
verificarConexion();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = $_POST;
    
    if (empty($data['nombre_aula']) || empty($data['capacidad']) || empty($data['tipo'])) {
        throw new Exception('Faltan campos requeridos');
    }

    $stmt = $conn->prepare("INSERT INTO aulas (nombre, capacidad, tipo) 
                          VALUES (:nombre, :capacidad, :tipo) RETURNING id");
    
    $stmt->execute([
        ':nombre' => $data['nombre_aula'],
        ':capacidad' => $data['capacidad'],
        ':tipo' => $data['tipo']
    ]);

    $id = $stmt->fetchColumn();
    echo json_encode(['success' => true, 'id' => $id]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 