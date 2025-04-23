<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    header("Location: ../../login.php");
    exit;
}

require_once '../../includes/conexion.php';

$mensaje = '';
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '2025-1';
$curso = isset($_GET['curso']) ? $_GET['curso'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verificarConexion();
        
        $docente_curso_id = $_POST['docente_curso_id'];
        $dia_semana = $_POST['dia_semana'];
        $hora_inicio = $_POST['hora_inicio'];
        $hora_fin = $_POST['hora_fin'];
        $asignatura_id = $_POST['asignatura_id'];
        
        // Verificar si ya existe un horario para ese día y hora
        $query_verificar = "SELECT id FROM public.horarios 
                          WHERE docente_curso_id = $1 
                          AND dia_semana = $2 
                          AND ((hora_inicio <= $3 AND hora_fin > $3) 
                          OR (hora_inicio < $4 AND hora_fin >= $4))";
        
        $result_verificar = pg_query_params($conexion, $query_verificar, 
            array($docente_curso_id, $dia_semana, $hora_inicio, $hora_fin));
        
        if (pg_num_rows($result_verificar) > 0) {
            throw new Exception("Ya existe una clase programada en ese horario.");
        }
        
        // Insertar el nuevo horario
        $query_insertar = "INSERT INTO public.horarios 
                          (docente_curso_id, dia_semana, hora_inicio, hora_fin, 
                           periodo_academico, estado, asignatura_id) 
                          VALUES ($1, $2, $3, $4, $5, 'activo', $6)";
        
        $result = pg_query_params($conexion, $query_insertar, 
            array($docente_curso_id, $dia_semana, $hora_inicio, $hora_fin, $periodo, $asignatura_id));
        
        if ($result) {
            header("Location: editar_horario.php?periodo=" . urlencode($periodo) . "&curso=" . urlencode($curso));
            exit;
        } else {
            throw new Exception("Error al guardar el horario.");
        }
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
    }
}

// Obtener lista de docentes y sus cursos
try {
    verificarConexion();
    $query_docentes = "SELECT dc.id as docente_curso_id, 
                             d.nombre as nombre_docente, 
                             d.apellido as apellido_docente,
                             c.nombre as nombre_curso
                      FROM public.docente_curso dc
                      INNER JOIN public.docentes d ON dc.docente_id = d.id
                      INNER JOIN public.cursos c ON dc.curso_id = c.id
                      ORDER BY d.apellido, d.nombre";
    
    $result_docentes = pg_query($conexion, $query_docentes);
    $docentes_cursos = pg_fetch_all($result_docentes);

    // Obtener lista de asignaturas
    $query_asignaturas = "SELECT codigo_asignatura, nombre, especializacion 
                         FROM public.asignaturas 
                         ORDER BY nombre";
    
    $result_asignaturas = pg_query($conexion, $query_asignaturas);
    $asignaturas = pg_fetch_all($result_asignaturas);
} catch (Exception $e) {
    $mensaje = "Error al cargar los datos: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Clase</title>
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
                <h2>Agregar Nueva Clase</h2>
                
                <?php if ($mensaje): ?>
                    <div class="mensaje-error">
                        <?php echo htmlspecialchars($mensaje); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="docente_curso_id">Docente y Curso:</label>
                        <select name="docente_curso_id" id="docente_curso_id" required>
                            <option value="">-- Seleccione un docente y curso --</option>
                            <?php foreach ($docentes_cursos as $dc): ?>
                                <option value="<?php echo htmlspecialchars($dc['docente_curso_id']); ?>">
                                    <?php echo htmlspecialchars($dc['nombre_docente'] . ' ' . 
                                          $dc['apellido_docente'] . ' - ' . $dc['nombre_curso']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="asignatura_id">Asignatura:</label>
                        <select name="asignatura_id" id="asignatura_id" required>
                            <option value="">-- Seleccione una asignatura --</option>
                            <?php foreach ($asignaturas as $asignatura): ?>
                                <option value="<?php echo htmlspecialchars($asignatura['codigo_asignatura']); ?>">
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
                            <option value="1">Lunes</option>
                            <option value="2">Martes</option>
                            <option value="3">Miércoles</option>
                            <option value="4">Jueves</option>
                            <option value="5">Viernes</option>
                            <option value="6">Sábado</option>
                            <option value="7">Domingo</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="hora_inicio">Hora de inicio:</label>
                        <input type="time" name="hora_inicio" id="hora_inicio" required>
                    </div>

                    <div class="form-group">
                        <label for="hora_fin">Hora de fin:</label>
                        <input type="time" name="hora_fin" id="hora_fin" required>
                    </div>

                    <div class="btn-container">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                        <button type="button" class="btn btn-secondary" 
                                onclick="window.location.href='editar_horario.php?periodo=<?php echo urlencode($periodo); ?>&curso=<?php echo urlencode($curso); ?>'">
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
            
            horaFin.addEventListener('change', function() {
                if (horaInicio.value && horaFin.value) {
                    if (horaFin.value <= horaInicio.value) {
                        alert('La hora de fin debe ser posterior a la hora de inicio');
                        horaFin.value = '';
                    }
                }
            });
        });
    </script>
</body>
</html> 