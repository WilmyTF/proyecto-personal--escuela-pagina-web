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
    !isset($_POST['comentario']) || empty($_POST['comentario'])) {
    
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => 'Los campos reporte_id y comentario son obligatorios'
    ];
    
 
    if (isset($_POST['reporte_id'])) {
        header('Location: ver_reporte.php?id=' . urlencode($_POST['reporte_id']));
    } else {
        header('Location: gestionar.php');
    }
    exit;
}


$reporte_id = $_POST['reporte_id'];
$comentario = $_POST['comentario'];
$usuario_id = $_SESSION['usuario_id'];


require_once '../../includes/conexion.php';

try {
   
    $query_verificar = "SELECT id FROM reportes WHERE id = $1";
    $result_verificar = pg_query_params($conexion, $query_verificar, [$reporte_id]);
    
    if (!$result_verificar || pg_num_rows($result_verificar) === 0) {
        throw new Exception("No se encontró el reporte con ID: {$reporte_id}");
    }
    

    $query = "
        INSERT INTO comentarios_reporte (
            reporte_id, 
            usuario_id, 
            comentario, 
            fecha_comentario
        ) VALUES (
            $1, $2, $3, $4
        ) RETURNING id
    ";
    
    $params = [
        $reporte_id,
        $usuario_id,
        $comentario,
        date('Y-m-d H:i:s')
    ];
    
    $result = pg_query_params($conexion, $query, $params);
    
    if (!$result) {
        throw new Exception("Error al guardar el comentario: " . pg_last_error($conexion));
    }
    
   
    $query_update = "
        UPDATE reportes 
        SET fecha_actualizacion = $1
        WHERE id = $2
    ";
    
    $params_update = [
        date('Y-m-d H:i:s'),
        $reporte_id
    ];
    
    pg_query_params($conexion, $query_update, $params_update);
    
    
    $_SESSION['mensaje'] = [
        'tipo' => 'success',
        'texto' => "Comentario agregado exitosamente."
    ];
    
 
    header('Location: ver_reporte.php?id=' . urlencode($reporte_id));
    exit;
    
} catch (Exception $e) {
  
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => $e->getMessage()
    ];
    
   
    header('Location: ver_reporte.php?id=' . urlencode($reporte_id));
    exit;
}
?> 