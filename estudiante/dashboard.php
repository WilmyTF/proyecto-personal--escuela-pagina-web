<?php
// Iniciar la sesión y verificar si el usuario está autenticado como estudiante
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'estudiante') {
    // Redirigir si no es un estudiante autenticado
    header("Location: ../login.php");
    exit;
}

// Datos de demostración
$docente = [
    'nombre' => 'Juan',
    'apellido' => 'Pérez',
    'especialidad' => 'Matemática',
    'curso' => 'Curso asignado 2',
    'estado' => 'Activo'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Estudiante</title>
    <link rel="stylesheet" href="../css/estudiante_dashboard.css">
    <link rel="stylesheet" href="../css/estudiante_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php include '../includes/estudiante_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="info-cards">
                <div class="card matricula">
                    <img src="../assets/icons/matricula-icon.png" alt="Matrícula">
                    <div class="card-content">
                        <h3>Matrícula</h3>
                        <p id="matricula"><?php echo isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : '****'; ?></p>
                    </div>
                </div>
                
                <div class="card grado">
                    <img src="../assets/icons/grade-icon.png" alt="Grado">
                    <div class="card-content">
                        <h3>Curso Asignado</h3>
                        <p id="grado"><?php echo $docente['curso']; ?></p>
                    </div>
                </div>

                <div class="card especialidad">
                    <img src="../assets/icons/subject-icon.png" alt="Especialidad">
                    <div class="card-content">
                        <h3>Especialidad</h3>
                        <p id="especialidad"><?php echo $docente['especialidad']; ?></p>
                    </div>
                </div>

                <div class="card estado">
                    <img src="../assets/icons/status-icon.png" alt="Estado">
                    <div class="card-content">
                        <h3>Estado</h3>
                        <p id="estado"><?php echo $docente['estado']; ?></p>
                    </div>
                </div>
            </div>

            <div class="horario">
                <h2>Horario de Clases</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Lunes</th>
                            <th>Martes</th>
                            <th>Miércoles</th>
                            <th>Jueves</th>
                            <th>Viernes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>7:00 - 8:30</td>
                            <td>Matemáticas<br><?php echo $docente['nombre'] . ' ' . $docente['apellido']; ?></td>
                            <td>Español<br>Prof. García</td>
                            <td>Matemáticas<br><?php echo $docente['nombre'] . ' ' . $docente['apellido']; ?></td>
                            <td>Historia<br>Prof. Martínez</td>
                            <td>Matemáticas<br><?php echo $docente['nombre'] . ' ' . $docente['apellido']; ?></td>
                        </tr>
                        <tr>
                            <td>8:30 - 10:00</td>
                            <td>Ciencias<br>Prof. López</td>
                            <td>Matemáticas<br><?php echo $docente['nombre'] . ' ' . $docente['apellido']; ?></td>
                            <td>Inglés<br>Prof. Wilson</td>
                            <td>Matemáticas<br><?php echo $docente['nombre'] . ' ' . $docente['apellido']; ?></td>
                            <td>Educación Física<br>Prof. Sánchez</td>
                        </tr>
                        <tr>
                            <td>10:30 - 12:00</td>
                            <td>Arte<br>Prof. Ramírez</td>
                            <td>Música<br>Prof. Torres</td>
                            <td>Matemáticas<br><?php echo $docente['nombre'] . ' ' . $docente['apellido']; ?></td>
                            <td>Computación<br>Prof. Díaz</td>
                            <td>Laboratorio<br>Prof. Ruiz</td>
                        </tr>
                    </tbody>
                </table>
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
    // Personalizar el nombre del estudiante en la barra lateral
    document.addEventListener('DOMContentLoaded', function() {
        const studentName = document.querySelector('.student-name');
        if (studentName) {
            studentName.textContent = "<?php echo $_SESSION['nombre'] . ' ' . $_SESSION['apellido']; ?>";
        }
    });

    const modal = document.getElementById('logoutModal');
    const logoutLink = document.querySelector('.logout a');
    const confirmBtn = document.getElementById('confirmLogout');
    const cancelBtn = document.getElementById('cancelLogout');

    // Prevenir el comportamiento por defecto del enlace de cierre de sesión
    logoutLink.addEventListener('click', function(e) {
        e.preventDefault();
        modal.style.display = 'flex';
    });

    // Confirmar cierre de sesión
    confirmBtn.addEventListener('click', function() {
        window.location.href = '../logout.php';
    });

    // Cancelar cierre de sesión
    cancelBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    // Cerrar modal al hacer clic fuera de él
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Agregar funcionalidad para el sidebar
    const sidebarToggle = document.getElementById('sidebarToggle');
    const container = document.querySelector('.container');
    const sidebar = document.querySelector('.sidebar');

    sidebarToggle.addEventListener('click', () => {
        container.classList.toggle('sidebar-collapsed');
        
        // Guardar el estado en localStorage
        const isCollapsed = container.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    });

    // Recuperar el estado del sidebar al cargar la página
    window.addEventListener('load', () => {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            container.classList.add('sidebar-collapsed');
        }
    });
    </script>

    <!-- Incluir el chatbot -->
    <?php include '../includes/chatbot.php'; ?>
</body>
</html> 