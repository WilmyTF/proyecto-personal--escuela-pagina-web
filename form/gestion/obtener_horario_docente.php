<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../../includes/conexion.php';

try {
    verificarConexion();
    
    if (!isset($_GET['docente_id']) || !is_numeric($_GET['docente_id'])) {
        throw new Exception("ID de docente no válido");
    }

    $docente_id = $_GET['docente_id'];

    // Obtener información del docente
    $query_docente = "SELECT nombre, apellido, especialidad 
                     FROM public.docentes 
                     WHERE id = $1";
    
    $result_docente = pg_query_params($conexion, $query_docente, array($docente_id));
    
    if (!$result_docente) {
        throw new Exception("Error al obtener datos del docente: " . pg_last_error($conexion));
    }
    
    $docente = pg_fetch_assoc($result_docente);
    
    if (!$docente) {
        throw new Exception("Docente no encontrado");
    }

    // Obtener horarios del docente
    $query_horarios = "SELECT h.dia_semana, h.hora_inicio, h.hora_fin,
                             a.nombre as nombre_asignatura,
                             c.nombre as nombre_curso
                      FROM public.horarios h
                      INNER JOIN public.docente_curso dc ON h.docente_curso_id = dc.id
                      INNER JOIN public.docentes d ON dc.docente_id = d.id
                      INNER JOIN public.cursos c ON dc.curso_id = c.id
                      LEFT JOIN public.asignaturas a ON h.asignatura_id = a.codigo_asignatura
                      WHERE d.id = $1
                      ORDER BY h.dia_semana, h.hora_inicio";

    $result_horarios = pg_query_params($conexion, $query_horarios, array($docente_id));
    
    if (!$result_horarios) {
        throw new Exception("Error al obtener horarios: " . pg_last_error($conexion));
    }
    
    $horarios = pg_fetch_all($result_horarios);

    // Preparar respuesta
    $response = [
        'success' => true,
        'docente' => $docente,
        'horarios' => $horarios ?: []
    ];

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?> 