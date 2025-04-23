<?php
// Iniciar la sesión y verificar si el usuario está autenticado como empleado/admin
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    // Redirigir si no es un empleado/admin autenticado
    header("Location: ../../login.php");
    exit;
}

// Incluir archivo de conexión a la base de datos
include_once('../../includes/conexion.php');

// Verificar si hay conexión a la base de datos
$db_error = false;
if (!$conn) {
    $db_error = true;
    $mensaje_error = "No se pudo establecer conexión con la base de datos. Verifique la configuración.";
}

// Manejar la creación de un nuevo usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
    $estado = isset($_POST['estado']) ? 1 : 0;
    $tipo_usuario = $_POST['tipo_usuario'];
    
    try {
        // Verificar que la conexión esté disponible
        if ($conn) {
            // Iniciar transacción
            $conn->beginTransaction();
            
            // Preparar la consulta para insertar un nuevo usuario
            $query = "INSERT INTO usuarios (nombre, apellido, email, \"contraseña\", \"Estado\") 
                    VALUES (:nombre, :apellido, :email, :contrasena, :estado) RETURNING id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':apellido', $apellido, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':contrasena', $contrasena, PDO::PARAM_STR);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $usuario_id = $stmt->fetchColumn();
                
                // Según el tipo de usuario, crear el registro correspondiente
                switch($tipo_usuario) {
                    case 'empleado':
                        $departamento = $_POST['departamento'];
                        $cargo = $_POST['cargo'];
                        $horario = $_POST['horario'];
                        
                        $query = "INSERT INTO empleados (usuario_id, departamento, cargo, horario, estado) 
                                VALUES (:usuario_id, :departamento, :cargo, :horario, :estado)";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
                        $stmt->bindParam(':departamento', $departamento, PDO::PARAM_STR);
                        $stmt->bindParam(':cargo', $cargo, PDO::PARAM_STR);
                        $stmt->bindParam(':horario', $horario, PDO::PARAM_STR);
                        $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
                        $stmt->execute();
                        break;
                        
                    case 'docente':
                        $especialidad = $_POST['especialidad'];
                        $horario_docente = $_POST['horario_docente'];
                        
                        $query = "INSERT INTO docentes (usuario_id, especialidad, horario, estado) 
                                VALUES (:usuario_id, :especialidad, :horario, :estado)";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
                        $stmt->bindParam(':especialidad', $especialidad, PDO::PARAM_STR);
                        $stmt->bindParam(':horario', $horario_docente, PDO::PARAM_STR);
                        $stmt->bindParam(':estado', $estado ? 'Activo' : 'Inactivo', PDO::PARAM_STR);
                        $stmt->execute();
                        break;
                        
                    case 'estudiante':
                        $matricula = $_POST['matricula'];
                        $historial_academico = $_POST['historial_academico'];
                        
                        $query = "INSERT INTO estudiantes (usuario_id, matricula, historial_academico, estado) 
                                VALUES (:usuario_id, :matricula, :historial_academico, :estado)";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
                        $stmt->bindParam(':matricula', $matricula, PDO::PARAM_STR);
                        $stmt->bindParam(':historial_academico', $historial_academico, PDO::PARAM_STR);
                        $stmt->bindParam(':estado', $estado ? 'Activo' : 'Inactivo', PDO::PARAM_STR);
                        $stmt->execute();
                        break;
                }
                
                // Si todo salió bien, confirmar la transacción
                $conn->commit();
                $mensaje_exito = "Usuario creado y asociado correctamente.";
            } else {
                $conn->rollBack();
                $mensaje_error = "Error al crear el usuario.";
            }
        } else {
            $mensaje_error = "No hay conexión a la base de datos disponible.";
        }
    } catch (PDOException $e) {
        if ($conn) {
            $conn->rollBack();
        }
        $mensaje_error = "Error en la base de datos: " . $e->getMessage();
    }
}

// Manejar la actualización de un usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_usuario'])) {
    $id = $_POST['usuario_id'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $estado = isset($_POST['estado']) ? 1 : 0;
    
    try {
        // Verificar que la conexión esté disponible
        if ($conn) {
            // Preparar la consulta para actualizar un usuario
            $query = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, 
                    email = :email, \"Estado\" = :estado WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':apellido', $apellido, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_INT);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $mensaje_exito = "Usuario actualizado correctamente.";
            } else {
                $mensaje_error = "Error al actualizar el usuario.";
            }
        } else {
            $mensaje_error = "No hay conexión a la base de datos disponible.";
        }
    } catch (PDOException $e) {
        $mensaje_error = "Error en la base de datos: " . $e->getMessage();
    }
}

// Manejar el cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_contrasena'])) {
    $id = $_POST['usuario_id'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
    
    try {
        // Verificar que la conexión esté disponible
        if ($conn) {
            // Preparar la consulta para actualizar la contraseña
            $query = "UPDATE usuarios SET contraseña = :contrasena WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':contrasena', $contrasena, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $mensaje_exito = "Contraseña actualizada correctamente.";
            } else {
                $mensaje_error = "Error al actualizar la contraseña.";
            }
        } else {
            $mensaje_error = "No hay conexión a la base de datos disponible.";
        }
    } catch (PDOException $e) {
        $mensaje_error = "Error en la base de datos: " . $e->getMessage();
    }
}

// Función para obtener usuarios con paginación
function obtenerUsuarios($conn, $pagina = 1, $por_pagina = 10, $filtro = '') {
    $inicio = ($pagina - 1) * $por_pagina;
    
    $condicion = "";
    if (!empty($filtro)) {
        $condicion = " WHERE nombre LIKE :filtro OR apellido LIKE :filtro OR email LIKE :filtro";
    }
    
    // Contar total de registros
    $query_count = "SELECT COUNT(*) FROM usuarios" . $condicion;
    $stmt_count = $conn->prepare($query_count);
    
    if (!empty($filtro)) {
        $param_filtro = '%' . $filtro . '%';
        $stmt_count->bindParam(':filtro', $param_filtro, PDO::PARAM_STR);
    }
    
    $stmt_count->execute();
    $total_registros = $stmt_count->fetchColumn();
    
    // Obtener registros paginados
    $query = "SELECT * FROM usuarios" . 
              $condicion . 
              " ORDER BY id DESC
              LIMIT :limite OFFSET :inicio";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':inicio', $inicio, PDO::PARAM_INT);
    $stmt->bindParam(':limite', $por_pagina, PDO::PARAM_INT);
    
    if (!empty($filtro)) {
        $param_filtro = '%' . $filtro . '%';
        $stmt->bindParam(':filtro', $param_filtro, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'usuarios' => $usuarios,
        'total' => $total_registros,
        'paginas' => ceil($total_registros / $por_pagina),
        'pagina_actual' => $pagina
    ];
}

// Obtener la página actual y filtro
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '';

// Obtener los usuarios
$resultado = obtenerUsuarios($conn, $pagina_actual, 10, $filtro);
$usuarios = $resultado['usuarios'];
$total_paginas = $resultado['paginas'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Estilos generales */
        .container {
            display: flex;
            min-height: 100vh;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            transition: margin-left 0.3s ease;
        }
        
        .sidebar-collapsed .main-content {
            margin-left: 0;
        }
        
        .sidebar-toggle {
            position: fixed;
            left: 10px;
            top: 10px;
            z-index: 1000;
            background-color: #0d6efd;
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        /* Estilos específicos para la gestión de usuarios */
        .panel-usuarios {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        
        .panel-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th, td {
            padding: 10px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
            border: 1px solid #ffc107;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #d39e00;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: 1px solid #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
            border: 1px solid #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        
        .form-container {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .filtro-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .filtro-container input {
            padding: 8px;
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .actions-cell {
            display: flex;
            gap: 5px;
        }
        
        .paginacion {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .paginacion a, .paginacion span {
            padding: 8px 16px;
            text-decoration: none;
            border: 1px solid #ddd;
            margin: 0 4px;
            color: #007bff;
        }
        
        .paginacion a:hover {
            background-color: #f2f2f2;
        }
        
        .paginacion .active {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            margin: 20px auto;
        }
        
        .table-responsive {
            margin-top: 15px;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        
        .table {
            margin-bottom: 0;
            background-color: white;
        }
        
        .table thead th {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 1;
            border-bottom: 2px solid #dee2e6;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .close-modal {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .close-modal:hover {
            color: #000;
        }
        
        .modal-title {
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn i {
            font-size: 16px;
        }
        
        .btn-success {
            background-color: #28a745;
            color: white;
            border: none;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: none;
        }
        
        .input-group {
            display: flex;
            align-items: center;
        }
        
        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        
        .input-group .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        
        /* Estilos para las tablas dentro del modal */
        #tabla_selector_empleado,
        #tabla_selector_docente,
        #tabla_selector_estudiante {
            margin-top: 20px;
        }
        
        .table td, .table th {
            padding: 8px;
            vertical-align: middle;
        }
        
        /* Estilo para el campo de búsqueda */
        .search-container {
            margin-bottom: 15px;
        }
        
        .search-container input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        /* Estilo para el checkbox de estado */
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 15px 0;
        }
        
        .checkbox-container input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                padding: 15px;
            }
            
            .table-responsive {
                max-height: 250px;
            }
            
            .btn {
                padding: 6px 12px;
                font-size: 13px;
            }
        }
        
        .estado-activo {
            color: #28a745;
            font-weight: bold;
        }
        
        .estado-inactivo {
            color: #dc3545;
            font-weight: bold;
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
            <h1>Gestión de Usuarios</h1>
            
            <?php if(isset($mensaje_exito)): ?>
                <div class="alert alert-success"><?php echo $mensaje_exito; ?></div>
            <?php endif; ?>
            
            <?php if(isset($mensaje_error)): ?>
                <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
            <?php endif; ?>
            
            <div class="panel-usuarios">
                <h2 class="panel-title">Usuarios del Sistema</h2>
                
                <div class="filtro-container">
                    <form action="" method="GET" style="display: flex; gap: 10px; width: 100%;">
                        <input type="text" name="filtro" placeholder="Buscar por nombre, apellido o email" class="form-control" value="<?php echo htmlspecialchars($filtro); ?>">
                        <button type="submit" class="btn btn-primary">Buscar</button>
                        <a href="?pagina=1" class="btn btn-warning">Limpiar</a>
                    </form>
                    <button id="btnNuevoUsuario" class="btn btn-success">Nuevo Usuario</button>
                </div>
                
                <?php if ($db_error): ?>
                    <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
                <?php else: ?>
                    <?php if (empty($usuarios)): ?>
                        <p>No se encontraron usuarios.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Email</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr>
                                        <td><?php echo $usuario['id']; ?></td>
                                        <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td>
                                            <?php if ($usuario['Estado'] == 1): ?>
                                                <span class="estado-activo">Activo</span>
                                            <?php else: ?>
                                                <span class="estado-inactivo">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions-cell">
                                            <button class="btn btn-primary btn-editar" 
                                                data-id="<?php echo $usuario['id']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                                data-apellido="<?php echo htmlspecialchars($usuario['apellido']); ?>"
                                                data-email="<?php echo htmlspecialchars($usuario['email']); ?>"
                                                data-estado="<?php echo $usuario['Estado']; ?>">
                                                Editar
                                            </button>
                                            <button class="btn btn-warning btn-contrasena" 
                                                data-id="<?php echo $usuario['id']; ?>">
                                                Contraseña
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Paginación -->
                        <?php if ($total_paginas > 1): ?>
                            <div class="paginacion">
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <?php if ($i == $pagina_actual): ?>
                                        <span class="active"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?pagina=<?php echo $i; ?>&filtro=<?php echo urlencode($filtro); ?>"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal Nuevo Usuario -->
    <div id="modalNuevoUsuario" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeModalNuevo">&times;</span>
            <h3 class="modal-title">Nuevo Usuario</h3>
            <form action="" method="POST" class="form-horizontal">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="apellido">Apellido:</label>
                    <input type="text" id="apellido" name="apellido" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" readonly>
                    <small class="form-text text-muted">El email se generará automáticamente</small>
                </div>
                <div class="form-group">
                    <label for="contrasena">Contraseña:</label>
                    <div class="input-group">
                        <input type="password" id="contrasena" name="contrasena" class="form-control" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="tipo_usuario">Tipo de Usuario:</label>
                    <select id="tipo_usuario" name="tipo_usuario" class="form-control" required>
                        <option value="">Seleccione un tipo</option>
                        <option value="empleado">Empleado</option>
                        <option value="docente">Profesor</option>
                        <option value="estudiante">Estudiante</option>
                    </select>
                </div>

                <!-- Tablas de selección -->
                <!-- Tabla Empleados -->
                <div id="tabla_selector_empleado" style="display: none;" class="form-group">
                    <div class="form-group">
                        <input type="text" id="buscar_empleado" class="form-control" placeholder="Buscar empleado...">
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Seleccionar</th>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Departamento</th>
                                    <th>Cargo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tabla_empleados">
                                <?php
                                try {
                                    $query = "SELECT e.*, u.nombre, u.apellido, u.email 
                                             FROM empleados e 
                                             INNER JOIN usuarios u ON e.usuario_id = u.id 
                                             WHERE e.estado = 1";
                                    $stmt = $conn->prepare($query);
                                    $stmt->execute();
                                    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($empleados as $empleado): ?>
                                        <tr>
                                            <td>
                                                <input type="radio" name="empleado_id" value="<?php echo $empleado['usuario_id']; ?>">
                                            </td>
                                            <td><?php echo $empleado['usuario_id']; ?></td>
                                            <td><?php echo htmlspecialchars($empleado['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($empleado['apellido']); ?></td>
                                            <td><?php echo $empleado['departamento']; ?></td>
                                            <td><?php echo $empleado['cargo']; ?></td>
                                            <td><?php echo $empleado['estado']; ?></td>
                                        </tr>
                                    <?php endforeach;
                                } catch (PDOException $e) {
                                    echo "<tr><td colspan='7'>Error al cargar los datos: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tabla Docentes -->
                <div id="tabla_selector_docente" style="display: none;" class="form-group">
                    <div class="form-group">
                        <input type="text" id="buscar_docente" class="form-control" placeholder="Buscar profesor...">
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Seleccionar</th>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Especialidad</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tabla_docentes">
                                <?php
                                try {
                                    $query = "SELECT d.*, u.nombre, u.apellido, u.email 
                                             FROM docentes d 
                                             INNER JOIN usuarios u ON d.usuario_id = u.id 
                                             WHERE d.estado = 'Activo'";
                                    $stmt = $conn->prepare($query);
                                    $stmt->execute();
                                    $docentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($docentes as $docente): ?>
                                        <tr>
                                            <td>
                                                <input type="radio" name="docente_id" value="<?php echo $docente['usuario_id']; ?>">
                                            </td>
                                            <td><?php echo $docente['usuario_id']; ?></td>
                                            <td><?php echo htmlspecialchars($docente['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($docente['apellido']); ?></td>
                                            <td><?php echo $docente['especialidad']; ?></td>
                                            <td><?php echo $docente['estado']; ?></td>
                                        </tr>
                                    <?php endforeach;
                                } catch (PDOException $e) {
                                    echo "<tr><td colspan='6'>Error al cargar los datos: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tabla Estudiantes -->
                <div id="tabla_selector_estudiante" style="display: none;" class="form-group">
                    <div class="form-group">
                        <input type="text" id="buscar_estudiante" class="form-control" placeholder="Buscar estudiante...">
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Seleccionar</th>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Matrícula</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tabla_estudiantes">
                                <?php
                                try {
                                    $query = "SELECT e.*, u.nombre, u.apellido, u.email 
                                             FROM estudiantes e 
                                             INNER JOIN usuarios u ON e.usuario_id = u.id 
                                             WHERE e.estado = 'Activo'";
                                    $stmt = $conn->prepare($query);
                                    $stmt->execute();
                                    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($estudiantes as $estudiante): ?>
                                        <tr>
                                            <td>
                                                <input type="radio" name="estudiante_id" value="<?php echo $estudiante['usuario_id']; ?>">
                                            </td>
                                            <td><?php echo $estudiante['usuario_id']; ?></td>
                                            <td><?php echo htmlspecialchars($estudiante['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($estudiante['apellido']); ?></td>
                                            <td><?php echo $estudiante['matricula']; ?></td>
                                            <td><?php echo $estudiante['estado']; ?></td>
                                        </tr>
                                    <?php endforeach;
                                } catch (PDOException $e) {
                                    echo "<tr><td colspan='6'>Error al cargar los datos: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                // Mostrar/ocultar tablas según el tipo de usuario seleccionado
                document.getElementById('tipo_usuario').addEventListener('change', function() {
                    // Ocultar todas las tablas
                    document.getElementById('tabla_selector_empleado').style.display = 'none';
                    document.getElementById('tabla_selector_docente').style.display = 'none';
                    document.getElementById('tabla_selector_estudiante').style.display = 'none';
                    
                    // Mostrar la tabla correspondiente
                    if (this.value) {
                        document.getElementById('tabla_selector_' + this.value).style.display = 'block';
                    }
                });
                </script>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="estado" name="estado" checked>
                        Usuario Activo
                    </label>
                </div>

                <div class="form-group text-center">
                    <button type="submit" name="crear_usuario" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar Usuario
                    </button>
                    <button type="button" class="btn btn-danger" onclick="document.getElementById('modalNuevoUsuario').style.display='none'">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Editar Usuario -->
    <div id="modalEditarUsuario" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeModalEditar">&times;</span>
            <h3 class="modal-title">Editar Usuario</h3>
            <form action="" method="POST">
                <input type="hidden" id="editar_usuario_id" name="usuario_id">
                <div class="form-group">
                    <label for="editar_nombre">Nombre:</label>
                    <input type="text" id="editar_nombre" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editar_apellido">Apellido:</label>
                    <input type="text" id="editar_apellido" name="apellido" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editar_email">Email:</label>
                    <input type="email" id="editar_email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="editar_estado">
                        <input type="checkbox" id="editar_estado" name="estado">
                        Usuario Activo
                    </label>
                </div>
                <div class="form-group">
                    <button type="submit" name="actualizar_usuario" class="btn btn-primary">Actualizar Usuario</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Cambiar Contraseña -->
    <div id="modalContrasena" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeModalContrasena">&times;</span>
            <h3 class="modal-title">Cambiar Contraseña</h3>
            <form action="" method="POST">
                <input type="hidden" id="contrasena_usuario_id" name="usuario_id">
                <div class="form-group">
                    <label for="nueva_contrasena">Nueva Contraseña:</label>
                    <input type="password" id="nueva_contrasena" name="contrasena" class="form-control" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="cambiar_contrasena" class="btn btn-warning">Cambiar Contraseña</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Toggle sidebar
    const sidebarToggle = document.getElementById('sidebarToggle');
    const container = document.querySelector('.container');

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
    
    // Modal Nuevo Usuario
    const modalNuevo = document.getElementById('modalNuevoUsuario');
    const btnNuevoUsuario = document.getElementById('btnNuevoUsuario');
    const closeModalNuevo = document.getElementById('closeModalNuevo');
    
    btnNuevoUsuario.addEventListener('click', () => {
        modalNuevo.style.display = 'flex';
    });
    
    closeModalNuevo.addEventListener('click', () => {
        modalNuevo.style.display = 'none';
    });
    
    // Modal Editar Usuario
    const modalEditar = document.getElementById('modalEditarUsuario');
    const btnEditar = document.querySelectorAll('.btn-editar');
    const closeModalEditar = document.getElementById('closeModalEditar');
    
    btnEditar.forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const nombre = btn.dataset.nombre;
            const apellido = btn.dataset.apellido;
            const email = btn.dataset.email;
            const estado = btn.dataset.estado === '1';
            
            document.getElementById('editar_usuario_id').value = id;
            document.getElementById('editar_nombre').value = nombre;
            document.getElementById('editar_apellido').value = apellido;
            document.getElementById('editar_email').value = email;
            document.getElementById('editar_estado').checked = estado;
            
            modalEditar.style.display = 'flex';
        });
    });
    
    closeModalEditar.addEventListener('click', () => {
        modalEditar.style.display = 'none';
    });
    
    // Modal Cambiar Contraseña
    const modalContrasena = document.getElementById('modalContrasena');
    const btnContrasena = document.querySelectorAll('.btn-contrasena');
    const closeModalContrasena = document.getElementById('closeModalContrasena');
    
    btnContrasena.forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            document.getElementById('contrasena_usuario_id').value = id;
            modalContrasena.style.display = 'flex';
        });
    });
    
    closeModalContrasena.addEventListener('click', () => {
        modalContrasena.style.display = 'none';
    });
    
    // Cerrar modales al hacer clic fuera
    window.addEventListener('click', (e) => {
        if (e.target === modalNuevo) {
            modalNuevo.style.display = 'none';
        }
        if (e.target === modalEditar) {
            modalEditar.style.display = 'none';
        }
        if (e.target === modalContrasena) {
            modalContrasena.style.display = 'none';
        }
    });

    document.getElementById('tipo_usuario').addEventListener('change', function() {
        // Ocultar todos los campos específicos
        document.getElementById('campos_empleado').style.display = 'none';
        document.getElementById('campos_docente').style.display = 'none';
        document.getElementById('campos_estudiante').style.display = 'none';
        
        // Mostrar los campos según el tipo seleccionado
        switch(this.value) {
            case 'empleado':
                document.getElementById('campos_empleado').style.display = 'block';
                break;
            case 'docente':
                document.getElementById('campos_docente').style.display = 'block';
                break;
            case 'estudiante':
                document.getElementById('campos_estudiante').style.display = 'block';
                break;
        }
    });

    // Función para filtrar las tablas
    function filtrarTabla(inputId, tablaId, columnaIndice) {
        const input = document.getElementById(inputId);
        const tabla = document.getElementById(tablaId);
        
        if (!input || !tabla) return;
        
        const filas = tabla.getElementsByTagName('tr');

        input.addEventListener('keyup', function() {
            const filtro = input.value.toLowerCase();
            
            for (let i = 0; i < filas.length; i++) {
                const celdas = filas[i].getElementsByTagName('td');
                let mostrarFila = false;
                
                // Buscar en nombre, apellido y otros campos relevantes
                if (celdas.length > 0) {
                    for (let j = 2; j < 5; j++) { // Buscar en nombre, apellido y campo específico
                        if (celdas[j]) {
                            const texto = celdas[j].textContent || celdas[j].innerText;
                            if (texto.toLowerCase().indexOf(filtro) > -1) {
                                mostrarFila = true;
                                break;
                            }
                        }
                    }
                }
                
                filas[i].style.display = mostrarFila ? '' : 'none';
            }
        });
    }

    // Función para generar email automáticamente
    function generarEmail() {
        const nombre = document.getElementById('nombre').value.toLowerCase().replace(/\s+/g, '');
        const apellido = document.getElementById('apellido').value.toLowerCase().replace(/\s+/g, '');
        const tipoUsuario = document.getElementById('tipo_usuario').value;
        const emailField = document.getElementById('email');
        
        if (tipoUsuario === 'estudiante') {
            const matricula = document.getElementById('matricula').value.toLowerCase().replace(/\s+/g, '');
            if (matricula) {
                emailField.value = matricula + '@test.com';
            }
        } else {
            const userId = document.querySelector('input[name="' + tipoUsuario + '_id"]:checked');
            if (nombre && apellido && userId) {
                emailField.value = nombre + apellido + userId.value + '@test.com';
            }
        }
    }

    // Función para autocompletar los campos del formulario
    function actualizarCampos(tipo) {
        const radios = document.getElementsByName(tipo + '_id');
        for (const radio of radios) {
            if (!radio) continue;
            
            radio.addEventListener('change', function() {
                if (this.checked) {
                    const fila = this.closest('tr');
                    if (!fila) return;
                    
                    const celdas = fila.getElementsByTagName('td');
                    
                    // Campos comunes para todos los tipos
                    const nombreField = document.getElementById('nombre');
                    const apellidoField = document.getElementById('apellido');
                    
                    if (nombreField && celdas[2]) nombreField.value = celdas[2].textContent.trim();
                    if (apellidoField && celdas[3]) apellidoField.value = celdas[3].textContent.trim();
                    
                    // Campos específicos según el tipo
                    switch(tipo) {
                        case 'empleado':
                            const deptoField = document.getElementById('departamento');
                            const cargoField = document.getElementById('cargo');
                            if (deptoField && celdas[4]) deptoField.value = celdas[4].textContent.trim();
                            if (cargoField && celdas[5]) cargoField.value = celdas[5].textContent.trim();
                            break;
                        case 'docente':
                            const espField = document.getElementById('especialidad');
                            if (espField && celdas[4]) espField.value = celdas[4].textContent.trim();
                            break;
                        case 'estudiante':
                            const matField = document.getElementById('matricula');
                            if (matField && celdas[4]) matField.value = celdas[4].textContent.trim();
                            break;
                    }
                    
                    // Generar email automáticamente
                    generarEmail();
                }
            });
        }
    }

    // Inicializar los manejadores cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar los filtros
        filtrarTabla('buscar_empleado', 'tabla_empleados', 2);
        filtrarTabla('buscar_docente', 'tabla_docentes', 2);
        filtrarTabla('buscar_estudiante', 'tabla_estudiantes', 2);

        // Inicializar el selector de tipo de usuario
        const tipoSelect = document.getElementById('tipo_usuario');
        if (tipoSelect) {
            tipoSelect.addEventListener('change', function() {
                const tipo = this.value;
                if (tipo) {
                    actualizarCampos(tipo);
                }
            });
        }

        // Agregar event listeners para la generación automática de email
        const nombreInput = document.getElementById('nombre');
        const apellidoInput = document.getElementById('apellido');
        const matriculaInput = document.getElementById('matricula');

        if (nombreInput) nombreInput.addEventListener('input', generarEmail);
        if (apellidoInput) apellidoInput.addEventListener('input', generarEmail);
        if (matriculaInput) matriculaInput.addEventListener('input', generarEmail);
    });

    function togglePassword() {
        const passwordInput = document.getElementById('contrasena');
        const icon = document.querySelector('.fa-eye');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Función para ajustar la altura máxima de las tablas
    function adjustTableHeight() {
        const modalContent = document.querySelector('.modal-content');
        const tableContainers = document.querySelectorAll('.table-responsive');
        
        if (modalContent && tableContainers.length) {
            const modalHeight = modalContent.clientHeight;
            const maxTableHeight = Math.max(300, modalHeight * 0.4); // 40% del alto del modal o mínimo 300px
            
            tableContainers.forEach(container => {
                container.style.maxHeight = `${maxTableHeight}px`;
            });
        }
    }

    // Ajustar altura de tablas cuando se abre el modal
    document.getElementById('btnNuevoUsuario').addEventListener('click', function() {
        setTimeout(adjustTableHeight, 100);
    });

    // Ajustar altura de tablas cuando cambia el tamaño de la ventana
    window.addEventListener('resize', adjustTableHeight);
    </script>
</body>
</html> 