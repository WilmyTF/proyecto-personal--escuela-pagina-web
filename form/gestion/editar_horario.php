<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    header("Location: ../../login.php");
    exit;
}

require_once '../../includes/conexion.php';

// Función para obtener el nombre del día
function getNombreDia($numero) {
    $dias = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
        7 => 'Domingo'
    ];
    return $dias[$numero];
}

// Obtener el periodo académico si se especifica
$periodo = isset($_GET['periodo']) ? $_GET['periodo'] : '2025-1';

try {
    verificarConexion();
    // Consulta para obtener los horarios con información de docentes y cursos
    $query = "
        SELECT 
            h.id as horario_id,
            h.dia_semana,
            h.hora_inicio,
            h.hora_fin,
            h.estado,
            c.nombre as nombre_curso,
            c.descripcion as descripcion_curso,
            d.nombre as nombre_docente,
            d.apellido as apellido_docente,
            d.especialidad,
            h.periodo_academico,
            a.codigo_asignatura,
            a.nombre as nombre_asignatura,
            a.especializacion as especializacion_asignatura
        FROM 
            public.horarios h
            INNER JOIN public.docente_curso dc ON h.docente_curso_id = dc.id
            INNER JOIN public.cursos c ON dc.curso_id = c.id
            INNER JOIN public.docentes d ON dc.docente_id = d.id
            LEFT JOIN public.asignaturas a ON h.asignatura_id = a.codigo_asignatura
        WHERE 
            h.periodo_academico = $1";

    $result = pg_query_params($conexion, $query, array($periodo));
    
    if (!$result) {
        throw new Exception("Error al ejecutar la consulta: " . pg_last_error($conexion));
    }
    
    $horarios = pg_fetch_all($result);
    
    if (!$horarios) {
        $horarios = array(); // Si no hay resultados, inicializar como array vacío
    }

} catch (Exception $e) {
    echo "Error al obtener los horarios: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Horarios</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <?php if (!isset($_GET['iframe'])): ?>
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        <?php if (isset($_GET['iframe'])): ?>
        .container {
            display: block;
            padding: 0;
            margin: 0;
        }
        .main-content {
            margin-left: 0;
            padding: 20px;
        }
        .sidebar-toggle {
            display: none;
        }
        <?php endif; ?>

        .horarios-container {
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: <?php echo isset($_GET['iframe']) ? '0' : '20px'; ?>;
        }

        .horario-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .horario-table th, .horario-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .horario-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .horario-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .horario-table tr:hover {
            background-color: #f0f0f0;
        }

        .periodo-selector {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .periodo-selector button {
            background-color: #34495e;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-right: 10px;
        }

        .periodo-selector button:hover {
            background-color: #2c3e50;
        }

        .periodo-selector form {
            margin-left: 10px;
        }

        .periodo-selector select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .btn-action {
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin-right: 5px;
        }

        .btn-edit {
            background-color: #3498db;
            color: white;
        }

        .btn-delete {
            background-color: #e74c3c;
            color: white;
        }

        .btn-back {
            background-color: #34495e;
            color: white;
            transition: background-color 0.3s;
        }
        
        .btn-back:hover {
            background-color: #2c3e50;
        }

        .btn-add {
            background-color: #27ae60;
            color: white;
            margin-left: 10px;
        }

        .btn-add:hover {
            background-color: #219a52;
        }

        .curso-descripcion {
            color: #666;
            font-size: 0.9em;
            margin: -10px 0 20px 0;
            padding: 0 5px;
            font-style: italic;
        }

        .mensaje-error {
            color: #e74c3c;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #fde9e8;
        }

        .mensaje-exito {
            color: #27ae60;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #e8f6e9;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!isset($_GET['iframe'])): ?>
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <?php include '../../includes/empleado_sidebar.php'; ?>
        <?php endif; ?>
        
        <div class="main-content">
            <div class="horarios-container">
                <?php
                // Obtener información del curso si se especifica
                $curso_info = null;
                if (isset($_GET['curso'])) {
                    try {
                        verificarConexion();
                        $query = "SELECT nombre, descripcion FROM public.cursos WHERE nombre = $1 LIMIT 1";
                        $result = pg_query_params($conexion, $query, array($_GET['curso']));
                        if ($result) {
                            $curso_info = pg_fetch_assoc($result);
                        }
                    } catch (Exception $e) {
                        // Manejar el error silenciosamente
                    }
                }
                ?>
                
                <h2>Editar Horarios <?php 
                    if ($curso_info) {
                        echo "- " . htmlspecialchars($curso_info['nombre']);
                    }
                    ?> - Periodo <?php echo htmlspecialchars($periodo); ?></h2>
                
                <?php if ($curso_info): ?>
                <p class="curso-descripcion"><?php echo htmlspecialchars($curso_info['descripcion']); ?></p>
                <?php endif; ?>

                <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'eliminado'): ?>
                    <div class="mensaje-exito">
                        El horario ha sido eliminado exitosamente.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'actualizado'): ?>
                    <div class="mensaje-exito">
                        El horario ha sido actualizado exitosamente.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="mensaje-error">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="periodo-selector">
                    <button class="btn-action btn-back" onclick="window.location.href='horarios.php'">
                        <i class="fas fa-arrow-left"></i> Volver
                    </button>
                    <button class="btn-action btn-add" onclick="agregarClase()">
                        <i class="fas fa-plus"></i> Agregar Clase
                    </button>
                    <form method="GET" style="display: inline-block; margin-left: 10px;">
                        <select name="periodo" onchange="this.form.submit()">
                            <option value="2025-1" <?php echo $periodo === '2025-1' ? 'selected' : ''; ?>>2025-1</option>
                            <option value="2025-2" <?php echo $periodo === '2025-2' ? 'selected' : ''; ?>>2025-2</option>
                        </select>
                    </form>
                </div>

                <table class="horario-table">
                    <thead>
                        <tr>
                            <th>Día</th>
                            <th>Hora</th>
                            <th>Curso</th>
                            <th>Docente</th>
                            <th>Asignatura</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horarios as $horario): ?>
                            <tr>
                                <td><?php echo getNombreDia($horario['dia_semana']); ?></td>
                                <td>
                                    <?php 
                                    echo date('H:i', strtotime($horario['hora_inicio'])) . ' - ' . 
                                         date('H:i', strtotime($horario['hora_fin'])); 
                                    ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($horario['nombre_curso']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($horario['descripcion_curso']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($horario['nombre_docente'] . ' ' . $horario['apellido_docente']) . '<br>';
                                    echo '<small>Especialidad: ' . htmlspecialchars($horario['especialidad']) . '</small>';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($horario['codigo_asignatura']): ?>
                                        <strong><?php echo htmlspecialchars($horario['nombre_asignatura']); ?></strong><br>
                                        <small>Código: <?php echo htmlspecialchars($horario['codigo_asignatura']); ?></small><br>
                                        <small>Especialización: <?php echo htmlspecialchars($horario['especializacion_asignatura']); ?></small>
                                    <?php else: ?>
                                        <em>No asignada</em>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($horario['estado']); ?></td>
                                <td>
                                    <button class="btn-action btn-edit" onclick="editarHorario(<?php echo $horario['horario_id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-action btn-delete" onclick="eliminarHorario(<?php echo $horario['horario_id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Función para agregar clase
        function agregarClase() {
            const urlParams = new URLSearchParams(window.location.search);
            const periodo = urlParams.get('periodo') || '2025-1';
            const curso = urlParams.get('curso') || '';
            window.location.href = `agregar_clase.php?periodo=${periodo}&curso=${encodeURIComponent(curso)}`;
        }

        // Función para editar horario
        function editarHorario(id) {
            window.location.href = 'modificar_horario.php?id=' + id;
        }

        // Función para eliminar horario
        function eliminarHorario(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este horario? Esta acción no se puede deshacer.')) {
                window.location.href = 'eliminar_horario.php?id=' + id;
            }
        }
    </script>
</body>
</html> 