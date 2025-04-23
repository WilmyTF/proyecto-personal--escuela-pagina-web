<?php
// Iniciar sesión si no está iniciada
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Verificar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gestionar.php');
    exit;
}

// Incluir archivo de conexión
require_once '../../includes/conexion.php';

// Validar campos requeridos
$errores = [];

if (empty($_POST['titulo'])) {
    $errores[] = "El título es obligatorio";
}

if (empty($_POST['tipo_id'])) {
    $errores[] = "Debe seleccionar un tipo de reporte";
}

if (empty($_POST['descripcion'])) {
    $errores[] = "La descripción es obligatoria";
}

// Si hay errores, redirigir
if (!empty($errores)) {
    $_SESSION['errores_reporte'] = $errores;
    $_SESSION['form_data'] = $_POST;
    header('Location: gestionar.php');
    exit;
}

// Obtener los datos del formulario
$titulo = $_POST['titulo'];
$tipo_id = intval($_POST['tipo_id']);
$descripcion = $_POST['descripcion'];
$usuario_id = $_SESSION['usuario_id'];

// Obtener datos del área (opcionales)
$area_id = !empty($_POST['area_id']) ? intval($_POST['area_id']) : null;
$data_id = !empty($_POST['data_id']) ? $_POST['data_id'] : null;

// Generar ID único para el reporte (formato: REP-YYYYMMDD-XXXX)
$fecha_actual = date('Ymd');
$aleatorio = strtoupper(substr(md5(uniqid()), 0, 4));
$reporte_id = "REP-{$fecha_actual}-{$aleatorio}";

// Iniciar una transacción
pg_query($conexion, "BEGIN");

try {
    // Construir la consulta según si hay área seleccionada o no
    if ($area_id && $data_id) {
        $query = "
            INSERT INTO reportes (
                id, 
                fecha_creacion, 
                tipo_id, 
                estado_id, 
                titulo, 
                descripcion, 
                usuario_id,
                area_id,
                data_id
            ) VALUES (
                $1, $2, $3, $4, $5, $6, $7, $8, $9
            ) RETURNING id
        ";
        
        $params = [
            $reporte_id,
            date('Y-m-d H:i:s'),
            $tipo_id,
            1, // Estado ID = 1 (Nuevo)
            $titulo,
            $descripcion,
            $usuario_id,
            $area_id,
            $data_id
        ];
    } else {
        $query = "
            INSERT INTO reportes (
                id, 
                fecha_creacion, 
                tipo_id, 
                estado_id, 
                titulo, 
                descripcion, 
                usuario_id
            ) VALUES (
                $1, $2, $3, $4, $5, $6, $7
            ) RETURNING id
        ";
        
        $params = [
            $reporte_id,
            date('Y-m-d H:i:s'),
            $tipo_id,
            1, // Estado ID = 1 (Nuevo)
            $titulo,
            $descripcion,
            $usuario_id
        ];
    }
    
    $result = pg_query_params($conexion, $query, $params);
    
    if (!$result) {
        throw new Exception("Error al guardar el reporte: " . pg_last_error($conexion));
    }
    
    // Procesar las imágenes si existen
    if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
        // Directorio para guardar las imágenes
        $upload_dir = '../../uploads/reportes/';
        
        // Crear el directorio si no existe
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Crear subdirectorio para este reporte
        $reporte_dir = $upload_dir . $reporte_id . '/';
        if (!is_dir($reporte_dir)) {
            mkdir($reporte_dir, 0755, true);
        }
        
        // Recorrer todas las imágenes
        $total_files = count($_FILES['imagenes']['name']);
        
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['imagenes']['error'][$i] === UPLOAD_ERR_OK) {
                // Obtener información del archivo
                $tmp_name = $_FILES['imagenes']['tmp_name'][$i];
                $name = $_FILES['imagenes']['name'][$i];
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                
                // Generar nombre único para la imagen
                $imagen_nombre = uniqid('img_') . '.' . $ext;
                $imagen_ruta = $reporte_dir . $imagen_nombre;
                
                // Mover el archivo
                if (move_uploaded_file($tmp_name, $imagen_ruta)) {
                    // Guardar la referencia en la base de datos
                    $ruta_relativa = 'uploads/reportes/' . $reporte_id . '/' . $imagen_nombre;
                    
                    $query_imagen = "
                        INSERT INTO imagenes_reporte (
                            reporte_id, 
                            ruta_imagen, 
                            fecha_subida
                        ) VALUES (
                            $1, $2, $3
                        )
                    ";
                    
                    $params_imagen = [
                        $reporte_id,
                        $ruta_relativa,
                        date('Y-m-d H:i:s')
                    ];
                    
                    $result_imagen = pg_query_params($conexion, $query_imagen, $params_imagen);
                    
                    if (!$result_imagen) {
                        throw new Exception("Error al guardar la imagen: " . pg_last_error($conexion));
                    }
                } else {
                    throw new Exception("Error al mover el archivo subido.");
                }
            } elseif ($_FILES['imagenes']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                throw new Exception("Error al subir la imagen: " . $_FILES['imagenes']['error'][$i]);
            }
        }
    }
    
    // Si todo salió bien, confirmar la transacción
    pg_query($conexion, "COMMIT");
    
    // Mensaje de éxito
    $_SESSION['mensaje'] = [
        'tipo' => 'success',
        'texto' => "Reporte creado exitosamente con ID: {$reporte_id}"
    ];
    
    // Redirigir a la página de gestión
    header('Location: gestionar.php');
    exit;
    
} catch (Exception $e) {
    // Si hubo algún error, revertir la transacción
    pg_query($conexion, "ROLLBACK");
    
    // Mensaje de error
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => $e->getMessage()
    ];
    
    // Mantener los datos del formulario
    $_SESSION['form_data'] = $_POST;
    
    // Redirigir con error
    header('Location: gestionar.php');
    exit;
}
?> 