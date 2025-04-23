<?php
/*
require_once 'conexion.php';

Código original comentado...
*/

/**
 * @param string $question La pregunta del usuario
 * @param int $usuario_id ID del usuario que hace la pregunta (opcional)
 * @return string La respuesta generada
 */
function getChatbotResponse($question, $usuario_id = null) {
    $question = strtolower($question);
    
    // Solo responder a "quien tiene mas clases"
    if (strpos($question, 'quien') !== false && strpos($question, 'clases') !== false) {
        return "Prueba docente, diríjase a gestión de personal - docente para consultar su horario";
    }
    
    // Para cualquier otra pregunta
    return "Lo siento, no puedo ayudarte con esa consulta.";
}

// Procesar la solicitud AJAX
if (isset($_POST['question'])) {
    $question = $_POST['question'];
    $response = getChatbotResponse($question);
    echo json_encode(['response' => $response]);
    exit;
}
?> 