<div class="sidebar">
    <div class="sidebar-content">
        <div class="profile">
            <img src="../assets/icons/profile-default.png" alt="Perfil" class="profile-img">
            <p class="teacher-name">Nombre de Profesor</p>
            <p class="teacher-specialty">Especialidad</p>
        </div>
        <nav class="menu">
            <ul>
                <li class="active"><a href="profesor_dashboard.php">Inicio</a></li>
                
                <!-- Sección Cursos -->
                <li class="menu-section">
                    <span class="section-title">Mis Cursos</span>
                    <ul class="submenu">
                        <li><a href="cursos/lista.php">Lista de Cursos</a></li>
                        <li><a href="cursos/asistencia.php">Control de Asistencia</a></li>
                    </ul>
                </li>

                <!-- Sección Calificaciones -->
                <li class="menu-section">
                    <span class="section-title">Calificaciones</span>
                    <ul class="submenu">
                        <li><a href="calificaciones/registro.php">Registrar Calificaciones</a></li>
                        <li><a href="calificaciones/consulta.php">Consultar Calificaciones</a></li>
                    </ul>
                </li>

                <!-- Sección Estudiantes -->
                <li class="menu-section">
                    <span class="section-title">Estudiantes</span>
                    <ul class="submenu">
                        <li><a href="estudiantes/lista.php">Lista de Estudiantes</a></li>
                        <li><a href="estudiantes/reportes.php">Reportes Académicos</a></li>
                    </ul>
                </li>

                <!-- Sección Planificación -->
                <li class="menu-section">
                    <span class="section-title">Planificación</span>
                    <ul class="submenu">
                        <li><a href="planificacion/clases.php">Plan de Clases</a></li>
                        <li><a href="planificacion/examenes.php">Exámenes</a></li>
                    </ul>
                </li>
                
                <li><a href="perfil.php">Mi Perfil</a></li>
            </ul>
        </nav>
    </div>
    <div class="logout">
        <a href="../logout.php">Cerrar Sesión</a>
    </div>
</div> 