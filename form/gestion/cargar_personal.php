<?php
header('Content-Type: application/json');
require_once '../../includes/conexion.php';

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$response = ['success' => false, 'data' => [], 'message' => '', 'debug' => []];

try {
    verificarConexion();
    $response['debug'][] = "Conexión verificada";

    if ($tipo === 'empleados') {
        $query = "SELECT * FROM empleados ORDER BY id ASC";
        $result = pg_query($conexion, $query);
        
        if ($result === false) {
            throw new Exception("Error en la consulta: " . pg_last_error($conexion));
        }
        
        $empleados = [];
        while ($row = pg_fetch_assoc($result)) {
            $empleados[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'apellido' => $row['apellido'],
                'departamento' => $row['departamento'],
                'cargo' => $row['cargo'],
                'horario' => $row['horario'],
                'estado' => $row['estado']
            ];
        }
        $response['success'] = true;
        $response['data'] = $empleados;
        $response['debug'][] = "Empleados encontrados: " . count($empleados);
        
    } elseif ($tipo === 'docentes') {
        $query = "SELECT * FROM docentes ORDER BY id ASC";
        $result = pg_query($conexion, $query);
        
        if ($result === false) {
            throw new Exception("Error en la consulta: " . pg_last_error($conexion));
        }
        
        $docentes = [];
        while ($row = pg_fetch_assoc($result)) {
            $docentes[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'apellido' => $row['apellido'],
                'especialidad' => $row['especialidad'],
                'horario' => $row['horario'],
                'estado' => $row['estado']
            ];
        }
        $response['success'] = true;
        $response['data'] = $docentes;
        $response['debug'][] = "Docentes encontrados: " . count($docentes);
        
    } else {
        throw new Exception('Tipo de personal no válido: ' . $tipo);
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['debug'][] = "Excepción capturada: " . $e->getMessage();
    error_log("Error en cargar_personal.php: " . $e->getMessage());
}

echo json_encode($response);
exit; 