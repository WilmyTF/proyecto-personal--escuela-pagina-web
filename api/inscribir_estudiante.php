<?php
require_once '../includes/sesion.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

if (!verificarSesion()) {
    http_response_code(401);
    echo json_encode(['error' => 'No hay una sesión activa']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato JSON inválido']);
    exit;
}

if (!isset($data['solicitud_id']) || !is_numeric($data['solicitud_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de solicitud inválido']);
    exit;
}

try {
    $empleado_id = obtenerEmpleadoId();
    if ($empleado_id === null) {
        throw new Exception('ID de empleado no encontrado en la sesión');
    }

    $pdo = Database::connect();
    $pdo->beginTransaction();

    // 1. Obtener datos de la solicitud
    $stmt = $pdo->prepare("SELECT * FROM solicitud_admision WHERE id_solicitud = ? AND estado = 'pendiente'");
    $stmt->execute([$data['solicitud_id']]);
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud) {
        throw new Exception('Solicitud no encontrada o ya procesada');
    }

    // 2. Insertar estudiante
    $stmt = $pdo->prepare("
        INSERT INTO estudiantes (
            nombre, apellido, direccion, grado, especialidad,
            nombre_tutor1, apellido_tutor1, direccion_tutor1,
            telefono_tutor1, correo_tutor1, relacion_tutor1
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $solicitud['nombre_estudiante'],
        $solicitud['apellido_estudiante'],
        $solicitud['direccion_estudiante'],
        $solicitud['grado_cursar'],
        $solicitud['especialidad'],
        $solicitud['nombre_tutor1'],
        $solicitud['apellido_tutor1'],
        $solicitud['direccion_tutor1'],
        $solicitud['telefono_tutor1'],
        $solicitud['correo_tutor1'],
        $solicitud['relacion_tutor1']
    ]);

    $estudiante_id = $pdo->lastInsertId();

    // Verificar si hay un padre temporal registrado
    if (!empty($solicitud['padre_temporal_id'])) {
        // Asociar el padre con el estudiante
        $stmt = $pdo->prepare("
            INSERT INTO estudiante_padre (
                estudiante_id, padre_id, es_principal
            ) VALUES (?, ?, true)
        ");
        $stmt->execute([$estudiante_id, $solicitud['padre_temporal_id']]);
    }

    // 3. Actualizar estado de la solicitud
    $stmt = $pdo->prepare("UPDATE solicitud_admision SET estado = 'inscrito' WHERE id_solicitud = ?");
    $stmt->execute([$data['solicitud_id']]);

    // 4. Insertar en historial de inscripción
    $stmt = $pdo->prepare("
        INSERT INTO historial_inscripcion (
            estudiante_id, solicitud_id, fecha_inscripcion,
            grado, especialidad, empleado_id
        ) VALUES (?, ?, NOW(), ?, ?, ?)
    ");

    $stmt->execute([
        $estudiante_id,
        $data['solicitud_id'],
        $solicitud['grado_cursar'],
        $solicitud['especialidad'],
        $empleado_id
    ]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Estudiante inscrito correctamente']);

} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
} 