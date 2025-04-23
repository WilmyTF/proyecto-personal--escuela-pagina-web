<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Empleado</title>
    <link rel="stylesheet" href="../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php

    session_start();
    if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
        
        header("Location: ../login.php");
        exit;
    }
    ?>
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
                        <p id="departamento"><?php echo isset($_SESSION['departamento_empleado']) ? $_SESSION['departamento_empleado'] : 'No asignado'; ?></p>
                    </div>
                </div>

                <div class="card cargo">
                    <img src="../assets/icons/position-icon.png" alt="Cargo">
                    <div class="card-content">
                        <h3>Cargo</h3>
                        <p id="cargo"><?php echo isset($_SESSION['cargo_empleado']) ? $_SESSION['cargo_empleado'] : 'No asignado'; ?></p>
                    </div>
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

    

    <?php include '../includes/chatbot.php'; ?>
</body>
</html>
