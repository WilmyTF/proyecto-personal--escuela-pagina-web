<?php
require_once '../../includes/conexion.php';
require_once '../../includes/funciones.php';

header('Content-Type: application/json');

if (!isset($_POST['accion'])) {
    echo json_encode(['exito' => false, 'mensaje' => 'Acción no especificada']);
    exit;
}

$accion = $_POST['accion'];

switch ($accion) {
    case 'actualizar_posicion':
        actualizarPosicion();
        break;
    case 'actualizar_propiedades_elemento':
        actualizarPropiedadesElemento();
        break;
    default:
        echo json_encode(['exito' => false, 'mensaje' => 'Acción no válida']);
        break;
}

function actualizarPosicion() {
    global $conexion;
    
    // Validar datos requeridos
    $campos_requeridos = ['data_id', 'x', 'y', 'width', 'height'];
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo])) {
            echo json_encode(['exito' => false, 'mensaje' => "Campo requerido: $campo"]);
            exit;
        }
    }
    
    $data_id = $_POST['data_id'];
    $x = floatval($_POST['x']);
    $y = floatval($_POST['y']);
    $width = floatval($_POST['width']);
    $height = floatval($_POST['height']);
    
    try {
        // Primero intentamos actualizar en la tabla areas_mapa
        $query = "UPDATE areas_mapa SET 
                 posicion_x = $1,
                 posicion_y = $2,
                 ancho = $3,
                 alto = $4
                 WHERE id = $5";
        
        $stmt = pg_prepare($conexion, "actualizar_area", $query);
        $resultado = pg_execute($conexion, "actualizar_area", [$x, $y, $width, $height, $data_id]);
        
        if (pg_affected_rows($resultado) == 0) {
            // Si no se actualizó ningún área, intentamos con subdivisiones
            $query = "UPDATE subdivisiones_area SET 
                     posicion_x = $1,
                     posicion_y = $2,
                     ancho = $3,
                     alto = $4
                     WHERE id = $5";
            
            $stmt = pg_prepare($conexion, "actualizar_subdivision", $query);
            $resultado = pg_execute($conexion, "actualizar_subdivision", [$x, $y, $width, $height, $data_id]);
            
            if (pg_affected_rows($resultado) == 0) {
                echo json_encode(['exito' => false, 'mensaje' => 'No se encontró el elemento a actualizar']);
                exit;
            }
        }
        
        echo json_encode(['exito' => true, 'mensaje' => 'Posición actualizada correctamente']);
        
    } catch (Exception $e) {
        echo json_encode(['exito' => false, 'mensaje' => 'Error al actualizar la posición: ' . $e->getMessage()]);
    }
}

function actualizarPropiedadesElemento() {
    global $conexion;

    // Validar datos requeridos (data_id y color por ahora)
    $campos_requeridos = ['data_id', 'color'];
    foreach ($campos_requeridos as $campo) {
        if (!isset($_POST[$campo])) {
            echo json_encode(['exito' => false, 'mensaje' => "Campo requerido para actualizar propiedades: $campo"]);
            exit;
        }
    }

    $data_id = $_POST['data_id'];
    $color = $_POST['color']; // Validar formato de color si es necesario

    // Aquí puedes añadir validación para el formato del color (ej. #RRGGBB)
    if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
        echo json_encode(['exito' => false, 'mensaje' => 'Formato de color inválido. Debe ser #RRGGBB.']);
        exit;
    }

    try {
        // Intentar actualizar en areas_mapa
        $query_area = "UPDATE areas_mapa SET color = $1 WHERE data_id = $2";
        $stmt_area = pg_prepare($conexion, "actualizar_color_area", $query_area);
        $resultado_area = pg_execute($conexion, "actualizar_color_area", [$color, $data_id]);
        $affected_area = pg_affected_rows($resultado_area);

        // Intentar actualizar en subdivisiones_area
        $query_sub = "UPDATE subdivisiones_area SET color = $1 WHERE data_id = $2";
        $stmt_sub = pg_prepare($conexion, "actualizar_color_subdivision", $query_sub);
        $resultado_sub = pg_execute($conexion, "actualizar_color_subdivision", [$color, $data_id]);
        $affected_sub = pg_affected_rows($resultado_sub);

        // Verificar si se actualizó al menos una tabla
        if ($affected_area > 0 || $affected_sub > 0) {
            echo json_encode(['exito' => true, 'mensaje' => 'Propiedades actualizadas correctamente']);
        } else {
            // Podría ser que el data_id no exista o el color ya fuera el mismo
            // Para diferenciar, podríamos hacer un SELECT previo, pero por simplicidad lo dejamos así
            echo json_encode(['exito' => false, 'mensaje' => 'No se encontró el elemento a actualizar o el color ya era el mismo.']);
        }

    } catch (Exception $e) {
        // Log del error
        error_log("Error al actualizar propiedades del elemento ($data_id): " . $e->getMessage());
        echo json_encode(['exito' => false, 'mensaje' => 'Error interno al actualizar las propiedades: ' . $e->getMessage()]);
    }
}
?> 