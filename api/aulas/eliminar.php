<?php
require_once '../../includes/conexion.php';
verificarConexion();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = $_POST;
    
    if (empty($data['id'])) {
        throw new Exception('ID del aula es requerido');
    }

    // Verificar si el aula está siendo utilizada en algún curso
    $stmt = $conn->prepare("SELECT COUNT(*) FROM cursos WHERE aula_id = :id");
    $stmt->execute([':id' => $data['id']]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        throw new Exception('No se puede eliminar el aula porque está siendo utilizada en uno o más cursos');
    }

    $stmt = $conn->prepare("DELETE FROM aulas WHERE id = :id");
    $stmt->execute([':id' => $data['id']]);

    echo json_encode(['success' => true]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 