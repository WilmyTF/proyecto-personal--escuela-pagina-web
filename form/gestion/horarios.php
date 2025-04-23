<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Horarios</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .horarios-container {
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
    </style>
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
        header("Location: ../../login.php");
        exit;
    }

    // Conexión a la base de datos
    require_once '../../includes/conexion.php';

    try {
        verificarConexion();
        // Consulta para obtener los cursos
        $query = "SELECT nombre, descripcion FROM public.cursos ORDER BY nombre";
        $result = pg_query($conexion, $query);
        
        if (!$result) {
            throw new Exception("Error al ejecutar la consulta: " . pg_last_error($conexion));
        }
        
        $cursos = pg_fetch_all($result);
    } catch (Exception $e) {
        echo "Error al cargar los cursos: " . $e->getMessage();
    }
    ?>
    <div class="container">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php include '../../includes/empleado_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="horarios-container">
                <h2>Gestor de Horarios</h2>
                <form id="horarioForm">
                    <div class="form-group">
                        <label for="curso">Seleccionar Curso:</label>
                        <select id="curso" name="curso" required>
                            <option value="">-- Seleccione un curso --</option>
                            <?php
                            if ($cursos && is_array($cursos)) {
                                foreach ($cursos as $curso) {
                                    echo '<option value="' . htmlspecialchars($curso['nombre']) . '" title="' . htmlspecialchars($curso['descripcion']) . '">';
                                    echo htmlspecialchars($curso['nombre']) . ' - ' . htmlspecialchars($curso['descripcion']);
                                    echo '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="periodo">Periodo Académico:</label>
                        <select id="periodo" name="periodo" required>
                            <option value="">-- Seleccione un periodo --</option>
                            <option value="2025-1">2025-1</option>
                            <option value="2025-2">2025-2</option>
                        </select>
                    </div>

                    <div class="btn-container">
                        <button type="button" class="btn btn-primary" id="btnEditarHorario">
                            <i class="fas fa-calendar-alt"></i> Ver/Editar Horarios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar el sidebar
            const sidebarToggle = document.getElementById('sidebarToggle');
            const container = document.querySelector('.container');
            
            if (sidebarToggle && container) {
                sidebarToggle.addEventListener('click', () => {
                    container.classList.toggle('sidebar-collapsed');
                });
            }

            // Funcionalidad del botón Ver/Editar Horarios
            document.getElementById('btnEditarHorario').addEventListener('click', function() {
                const periodo = document.getElementById('periodo').value;
                const curso = document.getElementById('curso').value;
                
                if (!periodo) {
                    alert('Por favor seleccione un periodo académico');
                    return;
                }
                if (!curso) {
                    alert('Por favor seleccione un curso');
                    return;
                }
                
                window.location.href = `editar_horario.php?periodo=${periodo}&curso=${encodeURIComponent(curso)}`;
            });
        });
    </script>
</body>
</html> 