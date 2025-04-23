<?php

session_start();

if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'estudiante') {
    header('Location: ../../login.php');
    exit;
}

require_once '../../includes/conexion.php';

$errores = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $tipo_id = $_POST['tipo_id'] ?? '';
    $area_id = $_POST['area_id'] ?? '';
    $data_id = $_POST['data_id'] ?? '';
    $descripcion = trim($_POST['descripcion'] ?? '');
   
    $form_data = [
        'titulo' => $titulo,
        'tipo_id' => $tipo_id,
        'area_id' => $area_id,
        'data_id' => $data_id,
        'descripcion' => $descripcion
    ];
    
    if (empty($titulo)) {
        $errores[] = 'El título es obligatorio';
    } elseif (strlen($titulo) > 200) {
        $errores[] = 'El título no puede exceder los 200 caracteres';
    }
    
    if (empty($tipo_id)) {
        $errores[] = 'Debe seleccionar un tipo de reporte';
    }
    
    if (empty($descripcion)) {
        $errores[] = 'La descripción es obligatoria';
    }
    
    if (empty($errores)) {
        try {
            $fecha_actual = date('Ymd');
            $aleatorio = strtoupper(substr(md5(uniqid()), 0, 4));
            $reporte_id = "REP-{$fecha_actual}-{$aleatorio}";
            
            pg_query($conexion, 'BEGIN');
            
            $query = "INSERT INTO reportes (
                id,
                titulo, tipo_id, area_id, data_id, descripcion, 
                usuario_id, estado_id, fecha_creacion
            ) VALUES (
                $1, $2, $3, $4, $5, $6,
                $7, 1, NOW()
            ) RETURNING id";
            
            $params = [
                $reporte_id,
                $titulo,
                $tipo_id,
                $area_id ?: null,
                $data_id ?: null,
                $descripcion,
                $_SESSION['usuario_id']
            ];
            
            $result = pg_query_params($conexion, $query, $params);
            
            if (!$result) {
                throw new Exception('Error al crear el reporte: ' . pg_last_error($conexion));
            }
            
            $reporte_id = pg_fetch_result($result, 0, 0);
     
            if (!empty($_FILES['imagenes']['name'][0])) {
                $upload_dir = '../../uploads/reportes/';
             
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
                    $file_name = $_FILES['imagenes']['name'][$key];
                    $file_size = $_FILES['imagenes']['size'][$key];
                    $file_error = $_FILES['imagenes']['error'][$key];
                    
                    if ($file_size > 5 * 1024 * 1024) {
                        throw new Exception('La imagen ' . $file_name . ' excede el tamaño máximo permitido de 5MB');
                    }
            
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $file_type = mime_content_type($tmp_name);
                    
                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception('El archivo ' . $file_name . ' no es una imagen válida');
                    }
                 
                    $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $new_file_name = uniqid() . '.' . $extension;
                    $target_path = $upload_dir . $new_file_name;
                    
                    if (!move_uploaded_file($tmp_name, $target_path)) {
                        throw new Exception('Error al subir la imagen ' . $file_name);
                    }
                    
                    $query = "INSERT INTO reportes_imagenes (reporte_id, nombre_archivo, ruta) VALUES ($1, $2, $3)";
                    $params = [$reporte_id, $file_name, $new_file_name];
                    
                    if (!pg_query_params($conexion, $query, $params)) {
                        throw new Exception('Error al guardar la información de la imagen ' . $file_name);
                    }
                }
            }
  
            pg_query($conexion, 'COMMIT');
      
            $_SESSION['mensaje'] = [
                'tipo' => 'success',
                'texto' => 'El reporte se ha creado exitosamente'
            ];
            
            header('Location: estudiante_gestionar.php');
            exit;
            
        } catch (Exception $e) {
            pg_query($conexion, 'ROLLBACK');
            
            $errores[] = $e->getMessage();
        }
    }
}

if (!empty($errores)) {
    $_SESSION['errores_reporte'] = $errores;
    $_SESSION['form_data'] = $form_data;
    header('Location: estudiante_nuevo_reporte.php');
    exit;
}
?> 