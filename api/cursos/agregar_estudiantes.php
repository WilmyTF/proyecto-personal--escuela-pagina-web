<?php
require_once '../../includes/conexion.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $curso_id = isset($_POST['curso_id']) ? $_POST['curso_id'] : null;
    $estudiantes = isset($_POST['estudiantes']) ? json_decode($_POST['estudiantes']) : null;
    
    if (!$curso_id || !$estudiantes) {
        throw new Exception('Datos incompletos');
    }

    verificarConexion();
    
    // Iniciar transacción
    $conn->beginTransaction();

    try {
        // Primero eliminamos las inscripciones existentes
        $stmt = $conn->prepare("DELETE FROM public.curso_estudiante WHERE curso_id = :curso_id");
        $stmt->execute([':curso_id' => $curso_id]);

        // Luego insertamos las nuevas inscripciones
        $stmt = $conn->prepare("INSERT INTO public.curso_estudiante (curso_id, usuario_id) VALUES (:curso_id, :usuario_id)");
        
        foreach ($estudiantes as $estudiante_id) {
            $stmt->execute([
                ':curso_id' => $curso_id,
                ':usuario_id' => $estudiante_id
            ]);
        }

        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Estudiantes actualizados correctamente'
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

} catch(Exception $e) {
    error_log('Error en agregar_estudiantes.php: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 