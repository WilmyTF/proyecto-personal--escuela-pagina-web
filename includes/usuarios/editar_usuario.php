<?php
// Incluir archivo de conexión
require_once '../conexion.php';
require_once '../registrar_auditoria.php';

// Verificar si se ha iniciado sesión
session_start();
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    header('Location: ../../login.php');
    exit;
}

// Verificar si tenemos un ID de usuario válido
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => 'ID de usuario no válido'
    ];
    header('Location: listar_usuarios.php');
    exit;
}

$usuario_id = intval($_GET['id']);
$usuario_actual = $_SESSION['usuario_id'];

// Objeto para almacenar errores
$errores = [];
$mensaje_exito = null;

// Si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos del formulario
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $apellido = isset($_POST['apellido']) ? trim($_POST['apellido']) : '';
    $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';
    $estado = isset($_POST['estado']) ? intval($_POST['estado']) : 0;
    $nueva_password = isset($_POST['nueva_password']) ? trim($_POST['nueva_password']) : '';
    
    // Verificar campos obligatorios
    if (empty($nombre)) $errores[] = "El nombre es obligatorio";
    if (empty($apellido)) $errores[] = "El apellido es obligatorio";
    if (empty($email)) $errores[] = "El email es obligatorio";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = "El email no tiene un formato válido";
    
    // Si no hay errores, actualizar usuario
    if (empty($errores)) {
        try {
            // Iniciar transacción
            $conn->beginTransaction();
            
            // Primero obtener datos actuales para registrar cambios
            $stmt = $conn->prepare("SELECT nombre, apellido, email, \"Estado\" FROM usuarios WHERE id = :id");
            $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
            $stmt->execute();
            $usuario_anterior = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Preparar la consulta para actualizar datos básicos
            $sql = "UPDATE usuarios SET 
                    nombre = :nombre, 
                    apellido = :apellido, 
                    email = :email, 
                    \"Estado\" = :estado";
            
            // Si se proporcionó una nueva contraseña, incluirla en la actualización
            $cambio_password = false;
            if (!empty($nueva_password)) {
                $sql .= ", \"contraseña\" = :password";
                $cambio_password = true;
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':apellido', $apellido);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':estado', $estado);
            $stmt->bindParam(':id', $usuario_id);
            
            if ($cambio_password) {
                $stmt->bindParam(':password', $nueva_password);
            }
            
            $resultado = $stmt->execute();
            
            if ($resultado) {
                // Confirmar transacción
                $conn->commit();
                
                // Construir detalle de cambios para auditoría
                $cambios = [];
                if ($usuario_anterior['nombre'] != $nombre) $cambios[] = "Nombre: {$usuario_anterior['nombre']} -> $nombre";
                if ($usuario_anterior['apellido'] != $apellido) $cambios[] = "Apellido: {$usuario_anterior['apellido']} -> $apellido";
                if ($usuario_anterior['email'] != $email) $cambios[] = "Email: {$usuario_anterior['email']} -> $email";
                if ($usuario_anterior['Estado'] != $estado) $cambios[] = "Estado: {$usuario_anterior['Estado']} -> $estado";
                if ($cambio_password) $cambios[] = "Contraseña modificada";
                
                $detalle_cambios = "Cambios realizados: " . implode(", ", $cambios);
                
                // Registrar en auditoría
                registrarModificacionUsuario($conn, $usuario_actual, $usuario_id, $detalle_cambios);
                
                $mensaje_exito = "Usuario actualizado correctamente";
            } else {
                $conn->rollBack();
                $errores[] = "Error al actualizar el usuario";
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $errores[] = "Error en la base de datos: " . $e->getMessage();
        }
    }
}

// Obtener datos del usuario a editar
try {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        $_SESSION['mensaje'] = [
            'tipo' => 'error',
            'texto' => 'Usuario no encontrado'
        ];
        header('Location: listar_usuarios.php');
        exit;
    }
} catch (PDOException $e) {
    $errores[] = "Error al obtener datos del usuario: " . $e->getMessage();
}

// Obtener roles/permisos del usuario
try {
    $stmt = $conn->prepare("
        SELECT p.id, p.nombre_permiso 
        FROM \"Permisos\" p 
        INNER JOIN \"usuario-permiso\" up ON p.id = up.\"id_Permisos\" 
        WHERE up.id_usuarios = :usuario_id
    ");
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $permisos_usuario = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = "Error al obtener permisos del usuario: " . $e->getMessage();
}

// Obtener todos los permisos disponibles
try {
    $stmt = $conn->prepare("SELECT * FROM \"Permisos\" WHERE estado = 1");
    $stmt->execute();
    $permisos_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = "Error al obtener permisos disponibles: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="../../css/usuarios.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .form-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
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
        }
        
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
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
        
        .permisos-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .permiso-item {
            display: flex;
            align-items: center;
        }
        
        .permiso-item input {
            margin-right: 5px;
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
            <h1>Editar Usuario</h1>
            
            <?php if (!empty($errores)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errores as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($mensaje_exito): ?>
                <div class="alert alert-success">
                    <?php echo $mensaje_exito; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="apellido">Apellido:</label>
                        <input type="text" id="apellido" name="apellido" class="form-control" value="<?php echo htmlspecialchars($usuario['apellido']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado:</label>
                        <select id="estado" name="estado" class="form-control">
                            <option value="1" <?php echo $usuario['Estado'] == 1 ? 'selected' : ''; ?>>Activo</option>
                            <option value="0" <?php echo $usuario['Estado'] == 0 ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="nueva_password">Nueva Contraseña (dejar en blanco para no cambiar):</label>
                        <input type="password" id="nueva_password" name="nueva_password" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Permisos:</label>
                        <div class="permisos-container">
                            <?php foreach ($permisos_disponibles as $permiso): 
                                $checked = false;
                                foreach ($permisos_usuario as $pu) {
                                    if ($pu['id'] == $permiso['id']) {
                                        $checked = true;
                                        break;
                                    }
                                }
                            ?>
                            <div class="permiso-item">
                                <input type="checkbox" id="permiso_<?php echo $permiso['id']; ?>" name="permisos[]" value="<?php echo $permiso['id']; ?>" <?php echo $checked ? 'checked' : ''; ?>>
                                <label for="permiso_<?php echo $permiso['id']; ?>"><?php echo htmlspecialchars($permiso['nombre_permiso']); ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="btn-container">
                        <a href="listar_usuarios.php" class="btn-secondary">Cancelar</a>
                        <button type="submit" class="btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle para la barra lateral
            const sidebarToggle = document.getElementById('sidebarToggle');
            const container = document.querySelector('.container');
            
            sidebarToggle.addEventListener('click', function() {
                container.classList.toggle('sidebar-collapsed');
                const isCollapsed = container.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
            });
            
            // Cargar estado de la barra lateral
            window.addEventListener('load', function() {
                const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                if (isCollapsed) {
                    container.classList.add('sidebar-collapsed');
                }
            });
        });
    </script>
</body>
</html>