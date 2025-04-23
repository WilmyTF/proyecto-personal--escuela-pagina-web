<?php
require_once '../../includes/conexion.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $id = isset($_POST['id']) ? $_POST['id'] : null;
    
    if (!$id) {
        throw new Exception('ID del curso no proporcionado');
    }

    verificarConexion();

    $stmt = $conn->prepare("DELETE FROM cursos WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('No se encontró el curso con el ID especificado');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Curso eliminado correctamente'
    ]);

} catch(Exception $e) {
    error_log('Error en eliminar.php: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 