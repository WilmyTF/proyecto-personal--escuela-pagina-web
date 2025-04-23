<?php
// Iniciar la sesión y verificar si el usuario está autenticado como profesor
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'profesor') {
    // Redirigir si no es un profesor autenticado
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Profesor</title>
    <link rel="stylesheet" href="../css/profesor_dashboard.css">
    <link rel="stylesheet" href="../css/profesor_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php include '../includes/profesor_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="info-cards">
                <div class="card id-profesor">
                    <img src="../assets/icons/id-icon.png" alt="ID Profesor">
                    <div class="card-content">
                        <h3>ID Profesor</h3>
                        <p id="idProfesor"><?php echo isset($_SESSION['docente_id']) ? $_SESSION['docente_id'] : '****'; ?></p>
                    </div>
                </div>
                
                <div class="card especialidad">
                    <img src="../assets/icons/specialty-icon.png" alt="Especialidad">
                    <div class="card-content">
                        <h3>Especialidad</h3>
                        <p id="especialidad">Especialidad del profesor</p>
                    </div>
                </div>

                <div class="card cursos">
                    <img src="../assets/icons/courses-icon.png" alt="Cursos">
                    <div class="card-content">
                        <h3>Cursos Asignados</h3>
                        <p id="cursos">0 cursos</p>
                    </div>
                </div>
            </div>

            <div class="clases-proximas">
                <h2>Clases Próximas</h2>
                <div class="clases-container">
                    <!-- Aquí se insertarán las clases próximas dinámicamente -->
                </div>
            </div>

            <div class="tareas-pendientes">
                <h2>Tareas Pendientes</h2>
                <div class="tareas-container">
                    <!-- Aquí se insertarán las tareas pendientes dinámicamente -->
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
    // Personalizar el nombre del profesor en la barra lateral
    document.addEventListener('DOMContentLoaded', function() {
        const teacherName = document.querySelector('.teacher-name');
        if (teacherName) {
            teacherName.textContent = "<?php echo $_SESSION['nombre'] . ' ' . $_SESSION['apellido']; ?>";
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

    // Funcionalidad para las secciones del menú
    document.addEventListener('DOMContentLoaded', function() {
        const menuSections = document.querySelectorAll('.menu-section');
        
        menuSections.forEach(section => {
            const sectionTitle = section.querySelector('.section-title');
            
            sectionTitle.addEventListener('click', function() {
                section.classList.toggle('active');
                
                // Guardar el estado en localStorage
                const isActive = section.classList.contains('active');
                const sectionName = sectionTitle.textContent.trim();
                localStorage.setItem(`menuSection_${sectionName}`, isActive);
            });
            
            // Recuperar el estado
            const sectionName = sectionTitle.textContent.trim();
            const savedState = localStorage.getItem(`menuSection_${sectionName}`);
            if (savedState === 'true') {
                section.classList.add('active');
            }
        });
    });
    </script>

    <!-- Incluir el chatbot -->
    <?php include '../includes/chatbot.php'; ?>
</body>
</html> 