<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    header("Location: ../../login.php");
    exit;
}

require_once '../../includes/conexion.php';

try {
    verificarConexion();
    
    // Verificar si se proporcionó un ID válido
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("ID de horario no válido");
    }

    $horario_id = $_GET['id'];

    // Obtener información del horario antes de eliminarlo para la redirección
    $query_info = "SELECT h.periodo_academico, c.nombre as curso_nombre 
                  FROM public.horarios h
                  INNER JOIN public.docente_curso dc ON h.docente_curso_id = dc.id
                  INNER JOIN public.cursos c ON dc.curso_id = c.id
                  WHERE h.id = $1";
    
    $result_info = pg_query_params($conexion, $query_info, array($horario_id));
    
    if (!$result_info) {
        throw new Exception("Error en la consulta: " . pg_last_error($conexion));
    }
    
    $horario_info = pg_fetch_assoc($result_info);

    if (!$horario_info) {
        throw new Exception("Horario no encontrado");
    }

    // Eliminar el horario
    $query_eliminar = "DELETE FROM public.horarios WHERE id = $1 RETURNING id";
    $result = pg_query_params($conexion, $query_eliminar, array($horario_id));

    if (!$result) {
        throw new Exception("Error al eliminar: " . pg_last_error($conexion));
    }

    $deleted = pg_fetch_assoc($result);
    if (!$deleted) {
        throw new Exception("No se pudo eliminar el horario");
    }

    // Redirigir de vuelta a la página de edición con los parámetros correctos
    $periodo = $horario_info['periodo_academico'];
    $curso = $horario_info['curso_nombre'];
    header("Location: editar_horario.php?periodo=" . urlencode($periodo) . "&curso=" . urlencode($curso) . "&mensaje=eliminado");
    exit;

} catch (Exception $e) {
    // En caso de error, redirigir con mensaje de error
    header("Location: editar_horario.php?error=" . urlencode($e->getMessage()));
    exit;
}
?> 