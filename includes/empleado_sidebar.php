<?php
// No iniciamos la sesión aquí porque ya se inicia en el archivo principal
// session_start();
?>
<div class="sidebar">
    <div class="sidebar-content">
        <div class="profile">
            <div class="profile-img">
                <i class="fas fa-user-circle" style="font-size: 48px; color: #2c3e50;"></i>
            </div>
            <p class="employee-name"><?php 
                $nombre_completo = '';
                if (isset($_SESSION['nombre_empleado'])) {
                    $nombre_completo = $_SESSION['nombre_empleado'];
                    if (isset($_SESSION['apellido_empleado'])) {
                        $nombre_completo .= ' ' . $_SESSION['apellido_empleado'];
                    }
                } else {
                    $nombre_completo = 'Empleado';
                }
                echo $nombre_completo;
            ?></p>
        </div>
        <nav class="menu">
            <ul>
                <li class="active"><a href="/form/empleado_dashboard.php">Inicio</a></li>
                
                <!-- Sección Estudiantes -->
                <li class="menu-section">
                    <span class="section-title">Estudiantes</span>
                    <ul class="submenu">
                        <li><a href="/form/visor_admision.php">Admisión</a></li>
                        <li><a href="/form/estudiantes/inscripcion.php">Inscripción</a></li>
                        <li><a href="/form/estudiantes/gestion.php">Gestión</a></li>
                    </ul>
                </li>

                <!-- Sección Reportes -->
                <li class="menu-section">
                    <span class="section-title">Reportes</span>
                    <ul class="submenu">
                        <li><a href="/form/reportes/gestionar.php">Gestionar</a></li>
                    </ul>
                </li>

                <!-- Sección Gestión del Centro -->
                <li class="menu-section">
                    <span class="section-title">Gestión del Centro</span>
                    <ul class="submenu">
                        <li><a href="/form/gestion_mapa.php">Mapa Interactivo</a></li>
                        <li><a href="/form/gestion/usuarios.php">Gestión de Usuarios</a></li>
                        <li><a href="/form/gestion/personal.php">Gestión de Personal</a></li>
                        <li><a href="/form/gestion/clases.php">Gestión de Clases</a></li>
                        <li><a href="/form/gestion/inventario.php">Gestión de Inventario</a></li>
                        <li><a href="/form/gestion/calendario.php">Gestión de Calendario</a></li>
                        <li><a href="/form/gestion/horarios.php">Gestor de Horarios</a></li>
                        <li><a href="/admin/gestion/auditoria.php">Auditoría</a></li>
                    </ul>
                </li>

                <!-- Sección Tareas -->
                <li class="menu-section">
                    <span class="section-title">Tareas</span>
                    <ul class="submenu">
                        <li><a href="/form/tareas/gestion.php">Gestión de Tareas</a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
    <div class="logout">
        <a href="/logout.php">Cerrar Sesión</a>
    </div>
</div>

<script>
// Función para inicializar el sidebar
function initializeSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const container = document.querySelector('.container');
    
    if (sidebarToggle && container) {
        // Toggle del sidebar
        sidebarToggle.addEventListener('click', () => {
            container.classList.toggle('sidebar-collapsed');
            const isCollapsed = container.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });

        // Cargar estado del sidebar
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            container.classList.add('sidebar-collapsed');
        }
    }

    // Manejo de las secciones del menú
    const menuSections = document.querySelectorAll('.menu-section');
    menuSections.forEach(section => {
        const sectionTitle = section.querySelector('.section-title');
        if (sectionTitle) {
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

            // Cargar estado guardado de la sección
            const savedState = localStorage.getItem(`menuSection_${sectionTitle.textContent.trim()}`);
            if (savedState === 'true') {
                section.classList.add('active');
            }
        }
    });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', initializeSidebar);
</script>
