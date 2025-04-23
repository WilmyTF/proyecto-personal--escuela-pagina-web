<?php

session_start();


if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gestionar.php');
    exit;
}


if (!isset($_POST['reporte_id']) || empty($_POST['reporte_id']) || 
    !isset($_POST['estado_id']) || empty($_POST['estado_id'])) {
    
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => 'Los campos reporte_id y estado_id son obligatorios'
    ];
    

    if (isset($_POST['reporte_id'])) {
        header('Location: ver_reporte.php?id=' . urlencode($_POST['reporte_id']));
    } else {
        header('Location: gestionar.php');
    }
    exit;
}


$reporte_id = $_POST['reporte_id'];
$nuevo_estado_id = intval($_POST['estado_id']);
$comentario = isset($_POST['comentario']) ? $_POST['comentario'] : '';
$usuario_id = $_SESSION['usuario_id'];

require_once '../../includes/conexion.php';


pg_query($conexion, "BEGIN");

try {

    $query_estado_actual = "SELECT estado_id FROM reportes WHERE id = $1";
    $result_estado_actual = pg_query_params($conexion, $query_estado_actual, [$reporte_id]);
    
    if (!$result_estado_actual || pg_num_rows($result_estado_actual) === 0) {
        throw new Exception("No se encontró el reporte con ID: {$reporte_id}");
    }
    
    $estado_actual_row = pg_fetch_assoc($result_estado_actual);
    $estado_actual_id = intval($estado_actual_row['estado_id']);

    if ($estado_actual_id === $nuevo_estado_id) {
        throw new Exception("El estado seleccionado es el mismo que el estado actual.");
    }
    

    $query_update = "
        UPDATE reportes 
        SET estado_id = $1, fecha_actualizacion = $2
        WHERE id = $3
        RETURNING id
    ";
    
    $params_update = [
        $nuevo_estado_id,
        date('Y-m-d H:i:s'),
        $reporte_id
    ];
    
    $result_update = pg_query_params($conexion, $query_update, $params_update);
    
    if (!$result_update || pg_num_rows($result_update) === 0) {
        throw new Exception("Error al actualizar el estado del reporte: " . pg_last_error($conexion));
    }
    

    $query_historial = "
        INSERT INTO historial_reportes (
            reporte_id, 
            usuario_id, 
            fecha_cambio, 
            estado_anterior, 
            estado_nuevo, 
            comentario
        ) VALUES (
            $1, $2, $3, $4, $5, $6
        )
    ";
    
    $params_historial = [
        $reporte_id,
        $usuario_id,
        date('Y-m-d H:i:s'),
        $estado_actual_id,
        $nuevo_estado_id,
        $comentario
    ];
    
    $result_historial = pg_query_params($conexion, $query_historial, $params_historial);
    
    if (!$result_historial) {
        throw new Exception("Error al registrar el cambio en el historial: " . pg_last_error($conexion));
    }

    pg_query($conexion, "COMMIT");
    

    $_SESSION['mensaje'] = [
        'tipo' => 'success',
        'texto' => "Estado del reporte actualizado exitosamente."
    ];
    
 
    header('Location: ver_reporte.php?id=' . urlencode($reporte_id));
    exit;
    
} catch (Exception $e) {

    pg_query($conexion, "ROLLBACK");

    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => $e->getMessage()
    ];
    

    header('Location: ver_reporte.php?id=' . urlencode($reporte_id));
    exit;
}
?> 