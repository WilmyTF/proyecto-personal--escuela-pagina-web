<?php
// Iniciar la sesión y verificar si el usuario está autenticado como empleado/admin
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    // Redirigir si no es un empleado/admin autenticado
    header("Location: ../login.php");
    exit;
}

// Incluir archivo de conexión a la base de datos
include_once('../includes/db_connection.php');

// Consultar estadísticas
$stats = [
    'estudiantes' => 0,
    'profesores' => 0,
    'empleados' => 0,
    'cursos' => 0
];

// Obtener conteo de estudiantes
$query = "SELECT COUNT(*) as total FROM estudiantes WHERE activo = 1";
$result = mysqli_query($conn, $query);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $stats['estudiantes'] = $row['total'];
}

// Obtener conteo de profesores
$query = "SELECT COUNT(*) as total FROM profesores WHERE activo = 1";
$result = mysqli_query($conn, $query);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $stats['profesores'] = $row['total'];
}

// Obtener conteo de empleados
$query = "SELECT COUNT(*) as total FROM empleados WHERE activo = 1";
$result = mysqli_query($conn, $query);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $stats['empleados'] = $row['total'];
}

// Obtener conteo de cursos
$query = "SELECT COUNT(*) as total FROM cursos WHERE activo = 1";
$result = mysqli_query($conn, $query);
if ($result && $row = mysqli_fetch_assoc($result)) {
    $stats['cursos'] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador</title>
    <link rel="stylesheet" href="../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php include '../includes/empleado_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="info-cards">
                <div class="card id-empleado">
                    <img src="../assets/icons/id-icon.png" alt="ID Empleado">
                    <div class="card-content">
                        <h3>ID Empleado</h3>
                        <p id="idEmpleado"><?php echo isset($_SESSION['empleado_id']) ? $_SESSION['empleado_id'] : '****'; ?></p>
                    </div>
                </div>
                
                <div class="card departamento">
                    <img src="../assets/icons/department-icon.png" alt="Departamento">
                    <div class="card-content">
                        <h3>Departamento</h3>
                        <p id="departamento"><?php echo isset($_SESSION['departamento']) ? $_SESSION['departamento'] : 'No asignado'; ?></p>
                    </div>
                </div>

                <div class="card cargo">
                    <img src="../assets/icons/position-icon.png" alt="Cargo">
                    <div class="card-content">
                        <h3>Cargo</h3>
                        <p id="cargo"><?php echo isset($_SESSION['es_admin']) ? 'Administrador' : 'Empleado'; ?></p>
                    </div>
                </div>
            </div>

            <div class="estadisticas">
                <h2>Estadísticas del Sistema</h2>
                <div class="stats-container">
                    <div class="stat-item">
                        <h3>Estudiantes</h3>
                        <p class="stat-count"><?php echo $stats['estudiantes']; ?></p>
                        <a href="estudiantes/gestion.php" class="stat-link">Ver detalles</a>
                    </div>
                    <div class="stat-item">
                        <h3>Profesores</h3>
                        <p class="stat-count"><?php echo $stats['profesores']; ?></p>
                        <a href="profesores/gestion.php" class="stat-link">Ver detalles</a>
                    </div>
                    <div class="stat-item">
                        <h3>Empleados</h3>
                        <p class="stat-count"><?php echo $stats['empleados']; ?></p>
                        <a href="empleados/gestion.php" class="stat-link">Ver detalles</a>
                    </div>
                    <div class="stat-item">
                        <h3>Cursos</h3>
                        <p class="stat-count"><?php echo $stats['cursos']; ?></p>
                        <a href="cursos/gestion.php" class="stat-link">Ver detalles</a>
                    </div>
                </div>
            </div>

            <div class="tareas-pendientes">
                <h2>Tareas Pendientes</h2>
                <div class="tareas-container">
                    <?php
                    // Obtener tareas pendientes del empleado actual
                    $empleado_id = $_SESSION['empleado_id'];
                    $query = "SELECT * FROM tareas WHERE empleado_id = ? AND estado != 'completada' ORDER BY fecha_vencimiento ASC LIMIT 5";
                    
                    if ($stmt = mysqli_prepare($conn, $query)) {
                        mysqli_stmt_bind_param($stmt, "i", $empleado_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while ($tarea = mysqli_fetch_assoc($result)) {
                                $fecha_vencimiento = new DateTime($tarea['fecha_vencimiento']);
                                $hoy = new DateTime();
                                $dias_restantes = $hoy->diff($fecha_vencimiento)->days;
                                $clase_urgencia = '';
                                
                                if ($fecha_vencimiento < $hoy) {
                                    $clase_urgencia = 'atrasada';
                                } elseif ($dias_restantes <= 2) {
                                    $clase_urgencia = 'urgente';
                                }
                                
                                echo '<div class="tarea-item ' . $clase_urgencia . '">';
                                echo '<h3>' . htmlspecialchars($tarea['titulo']) . '</h3>';
                                echo '<p>' . htmlspecialchars($tarea['descripcion']) . '</p>';
                                echo '<div class="tarea-footer">';
                                echo '<span class="fecha">Vence: ' . date('d/m/Y', strtotime($tarea['fecha_vencimiento'])) . '</span>';
                                echo '<a href="tareas/ver.php?id=' . $tarea['id'] . '" class="btn-tarea">Ver detalles</a>';
                                echo '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="no-tareas">No tienes tareas pendientes.</p>';
                        }
                        
                        mysqli_stmt_close($stmt);
                    } else {
                        echo '<p class="error">Error al cargar las tareas.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <h2>Confirmar Cierre de Sesión</h2>
            <p>¿Estás seguro que deseas cerrar la sesión?</p>
            <div class="modal-buttons">
                <button id="confirmLogout" class="btn-confirm">Sí, cerrar sesión</button>
                <button id="cancelLogout" class="btn-cancel">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
    // No necesitamos personalizar el nombre del empleado aquí, ya se hace desde PHP
    /*document.addEventListener('DOMContentLoaded', function() {
        const employeeName = document.querySelector('.employee-name');
        if (employeeName) {
            employeeName.textContent = "<?php echo $_SESSION['nombre'] . ' ' . $_SESSION['apellido']; ?>";
        }
    });*/

    const modal = document.getElementById('logoutModal');
    const logoutLink = document.querySelector('.logout a');
    const confirmBtn = document.getElementById('confirmLogout');
    const cancelBtn = document.getElementById('cancelLogout');

    logoutLink.addEventListener('click', function(e) {
        e.preventDefault();
        modal.style.display = 'flex';
    });

    confirmBtn.addEventListener('click', function() {
        window.location.href = '../logout.php';
    });

    cancelBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    const sidebarToggle = document.getElementById('sidebarToggle');
    const container = document.querySelector('.container');
    const sidebar = document.querySelector('.sidebar');

    sidebarToggle.addEventListener('click', () => {
        container.classList.toggle('sidebar-collapsed');
        const isCollapsed = container.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    });

    window.addEventListener('load', () => {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            container.classList.add('sidebar-collapsed');
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const menuSections = document.querySelectorAll('.menu-section');
        
        menuSections.forEach(section => {
            const sectionTitle = section.querySelector('.section-title');
            
            sectionTitle.addEventListener('click', function() {
                menuSections.forEach(otherSection => {
                    if (otherSection !== section) {
                        otherSection.classList.remove('active');
                    }
                });
                
                section.classList.toggle('active');
                
                const isActive = section.classList.contains('active');
                localStorage.setItem(`menuSection_${sectionTitle.textContent.trim()}`, isActive);
            });
            
            const savedState = localStorage.getItem(`menuSection_${sectionTitle.textContent.trim()}`);
            if (savedState === 'true') {
                section.classList.add('active');
            }
        });
    });
    </script>

    <?php include '../includes/chatbot.php'; ?>
</body>
</html> 