<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    header("Location: ../../login.php");
    exit;
}

require_once '../../includes/conexion.php';

$mensaje = '';
$horario_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$horario_id || !is_numeric($horario_id)) {
    header("Location: editar_horario.php?error=" . urlencode("ID de horario no válido"));
    exit;
}

try {
    verificarConexion();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $dia_semana = $_POST['dia_semana'];
        $hora_inicio = $_POST['hora_inicio'];
        $hora_fin = $_POST['hora_fin'];
        $asignatura_id = $_POST['asignatura_id'];
        
        // Verificar si ya existe un horario para ese día y hora (excluyendo el actual)
        $query_verificar = "SELECT id FROM public.horarios 
                          WHERE docente_curso_id = (SELECT docente_curso_id FROM public.horarios WHERE id = $1)
                          AND dia_semana = $2 
                          AND ((hora_inicio <= $3 AND hora_fin > $3) 
                          OR (hora_inicio < $4 AND hora_fin >= $4))
                          AND id != $1";
        
        $result_verificar = pg_query_params($conexion, $query_verificar, 
            array($horario_id, $dia_semana, $hora_inicio, $hora_fin));
        
        if (pg_num_rows($result_verificar) > 0) {
            throw new Exception("Ya existe una clase programada en ese horario.");
        }
        
        // Actualizar el horario
        $query_actualizar = "UPDATE public.horarios 
                           SET dia_semana = $1, 
                               hora_inicio = $2, 
                               hora_fin = $3, 
                               asignatura_id = $4
                           WHERE id = $5
                           RETURNING id";
        
        $result = pg_query_params($conexion, $query_actualizar, 
            array($dia_semana, $hora_inicio, $hora_fin, $asignatura_id, $horario_id));

        if (!$result) {
            throw new Exception("Error al actualizar: " . pg_last_error($conexion));
        }

        $updated = pg_fetch_assoc($result);
        if ($updated) {
            header("Location: editar_horario.php?mensaje=actualizado");
            exit;
        } else {
            throw new Exception("No se pudo actualizar el horario");
        }
    }

    // Obtener datos actuales del horario
    $query_horario = "SELECT h.*, 
                            c.nombre as nombre_curso,
                            d.nombre as nombre_docente,
                            d.apellido as apellido_docente,
                            a.codigo_asignatura,
                            a.nombre as nombre_asignatura,
                            a.especializacion as especializacion_asignatura
                     FROM public.horarios h
                     INNER JOIN public.docente_curso dc ON h.docente_curso_id = dc.id
                     INNER JOIN public.cursos c ON dc.curso_id = c.id
                     INNER JOIN public.docentes d ON dc.docente_id = d.id
                     LEFT JOIN public.asignaturas a ON h.asignatura_id = a.codigo_asignatura
                     WHERE h.id = $1";

    $result_horario = pg_query_params($conexion, $query_horario, array($horario_id));
    
    if (!$result_horario) {
        throw new Exception("Error al obtener datos del horario: " . pg_last_error($conexion));
    }
    
    $horario = pg_fetch_assoc($result_horario);
    
    if (!$horario) {
        throw new Exception("Horario no encontrado");
    }

    // Obtener lista de asignaturas
    $query_asignaturas = "SELECT codigo_asignatura, nombre, especializacion 
                         FROM public.asignaturas 
                         ORDER BY nombre";
    
    $result_asignaturas = pg_query($conexion, $query_asignaturas);
    $asignaturas = pg_fetch_all($result_asignaturas);

} catch (Exception $e) {
    $mensaje = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Horario</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .form-container {
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        .form-group select, .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .info-group {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .info-group strong {
            color: #2c3e50;
        }

        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .mensaje-error {
            color: #e74c3c;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #fde9e8;
        }
    </style>
</head>
<body>
    <div class="container">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php include '../../includes/empleado_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="form-container">
                <h2>Modificar Horario</h2>
                
                <?php if ($mensaje): ?>
                    <div class="mensaje-error">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>

                <div class="info-group">
                    <strong>Curso:</strong> <?php echo htmlspecialchars($horario['nombre_curso']); ?><br>
                    <strong>Docente:</strong> <?php echo htmlspecialchars($horario['nombre_docente'] . ' ' . $horario['apellido_docente']); ?>
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="asignatura_id">Asignatura:</label>
                        <select name="asignatura_id" id="asignatura_id" required>
                            <option value="">-- Seleccione una asignatura --</option>
                            <?php foreach ($asignaturas as $asignatura): ?>
                                <option value="<?php echo htmlspecialchars($asignatura['codigo_asignatura']); ?>"
                                        <?php echo $asignatura['codigo_asignatura'] === $horario['asignatura_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($asignatura['nombre'] . ' - ' . 
                                          $asignatura['especializacion']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="dia_semana">Día de la semana:</label>
                        <select name="dia_semana" id="dia_semana" required>
                            <option value="">-- Seleccione un día --</option>
                            <option value="1" <?php echo $horario['dia_semana'] == 1 ? 'selected' : ''; ?>>Lunes</option>
                            <option value="2" <?php echo $horario['dia_semana'] == 2 ? 'selected' : ''; ?>>Martes</option>
                            <option value="3" <?php echo $horario['dia_semana'] == 3 ? 'selected' : ''; ?>>Miércoles</option>
                            <option value="4" <?php echo $horario['dia_semana'] == 4 ? 'selected' : ''; ?>>Jueves</option>
                            <option value="5" <?php echo $horario['dia_semana'] == 5 ? 'selected' : ''; ?>>Viernes</option>
                            <option value="6" <?php echo $horario['dia_semana'] == 6 ? 'selected' : ''; ?>>Sábado</option>
                            <option value="7" <?php echo $horario['dia_semana'] == 7 ? 'selected' : ''; ?>>Domingo</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="hora_inicio">Hora de inicio:</label>
                        <input type="time" name="hora_inicio" id="hora_inicio" 
                               value="<?php echo htmlspecialchars(substr($horario['hora_inicio'], 0, 5)); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="hora_fin">Hora de fin:</label>
                        <input type="time" name="hora_fin" id="hora_fin" 
                               value="<?php echo htmlspecialchars(substr($horario['hora_fin'], 0, 5)); ?>" required>
                    </div>

                    <div class="btn-container">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <button type="button" class="btn btn-secondary" 
                                onclick="window.location.href='editar_horario.php'">
                            <i class="fas fa-arrow-left"></i> Volver
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const container = document.querySelector('.container');
            
            if (sidebarToggle && container) {
                sidebarToggle.addEventListener('click', () => {
                    container.classList.toggle('sidebar-collapsed');
                });
            }

            // Validación de horas
            const horaInicio = document.getElementById('hora_inicio');
            const horaFin = document.getElementById('hora_fin');
            
            function validarHoras() {
                if (horaInicio.value && horaFin.value) {
                    if (horaFin.value <= horaInicio.value) {
                        alert('La hora de fin debe ser posterior a la hora de inicio');
                        horaFin.value = '';
                        return false;
                    }
                }
                return true;
            }

            horaFin.addEventListener('change', validarHoras);
            
            // Validar antes de enviar el formulario
            document.querySelector('form').addEventListener('submit', function(e) {
                if (!validarHoras()) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html> 