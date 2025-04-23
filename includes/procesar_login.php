<?php
session_start();
require_once 'conexion.php';

// Verificar si se recibieron los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $tipo_solicitado = isset($_POST['tipo']) ? intval($_POST['tipo']) : 0;

    try {
        // Verificar que la conexión está establecida
        if (!$conn) {
            throw new Exception("No se pudo establecer la conexión con la base de datos");
        }

        // Consultar usuario por email
        $stmt = $conn->prepare("SELECT id, email, \"contraseña\", nombre, apellido, \"Estado\" FROM usuarios WHERE email = :email");
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta de usuario");
        }
        
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $_SESSION['error'] = "Usuario no encontrado con el email proporcionado";
            header("Location: ../login.php");
            exit;
        }

        // Verificación simple: contraseña en texto plano
        if ($usuario['contraseña'] === $password) {
            // Verificar si el usuario está activo
            if ($usuario['Estado'] != 1) {
                $_SESSION['error'] = "Usuario inactivo. Contacte al administrador.";
                header("Location: ../login.php");
                exit;
            }

            // Consultar los permisos del usuario
            $stmt = $conn->prepare("SELECT p.id, p.nombre_permiso 
                                    FROM \"Permisos\" p 
                                    INNER JOIN \"usuario-permiso\" up ON p.id = up.\"id_Permisos\" 
                                    WHERE up.id_usuarios = :usuario_id");
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta de permisos");
            }
            
            $stmt->bindParam(':usuario_id', $usuario['id']);
            $stmt->execute();
            $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($permisos)) {
                $_SESSION['error'] = "Usuario sin permisos asignados. Contacte al administrador.";
                header("Location: ../login.php");
                exit;
            }
            
            // Verificar si tiene el permiso solicitado
            $tiene_permiso = false;
            $rol_usuario = '';
            foreach ($permisos as $permiso) {
                // Comparar el nombre del permiso con el tipo solicitado
                if (($tipo_solicitado == 1 && strtolower($permiso['nombre_permiso']) == 'estudiante') ||
                    ($tipo_solicitado == 2 && strtolower($permiso['nombre_permiso']) == 'profesor') ||
                    ($tipo_solicitado == 3 && (strtolower($permiso['nombre_permiso']) == 'empleado' || 
                                              strtolower($permiso['nombre_permiso']) == 'administrador'))) {
                    $tiene_permiso = true;
                    $rol_usuario = $permiso['nombre_permiso'];
                    break;
                }
            }
            
            if (!$tiene_permiso) {
                $_SESSION['error'] = "No tiene permisos para acceder como " . 
                    ($tipo_solicitado == 1 ? "estudiante" : ($tipo_solicitado == 2 ? "profesor" : "empleado"));
                header("Location: ../login.php");
                exit;
            }

            // Registrar el inicio de sesión en logs_acceso
            $ip = $_SERVER['REMOTE_ADDR'];
            $accion = "Inicio de sesión como " . $rol_usuario;
            
            $stmt = $conn->prepare("INSERT INTO logs_acceso (usuario_id, ip, accion) VALUES (:usuario_id, :ip, :accion)");
            if (!$stmt) {
                // Solo registrar el error, pero continuar con el proceso de login
                error_log("Error al preparar la consulta de registro de acceso");
            } else {
                $stmt->bindParam(':usuario_id', $usuario['id']);
                $stmt->bindParam(':ip', $ip);
                $stmt->bindParam(':accion', $accion);
                $stmt->execute();
            }

            // Establecer variables de sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['apellido'] = $usuario['apellido'];
            $_SESSION['email'] = $usuario['email'];
            $_SESSION['permisos'] = array_column($permisos, 'nombre_permiso');
            
            // Redirigir según el tipo de usuario
            switch ($tipo_solicitado) {
                case 1: // Estudiante
                    $_SESSION['tipo_usuario'] = 'estudiante';
                    
                    // Obtener la matrícula del estudiante
                    $stmt = $conn->prepare("SELECT matricula FROM estudiantes WHERE usuario_id = :usuario_id");
                    if ($stmt) {
                        $stmt->bindParam(':usuario_id', $usuario['id']);
                        $stmt->execute();
                        $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($estudiante) {
                            $_SESSION['matricula'] = $estudiante['matricula'];
                        }
                    }
                    
                    header("Location: ../estudiante/dashboard.php");
                    break;
                    
                case 2: // Profesor
                    $_SESSION['tipo_usuario'] = 'profesor';
                    
                    // Obtener el ID del docente
                    $stmt = $conn->prepare("SELECT id FROM docentes WHERE usuario_id = :usuario_id");
                    if ($stmt) {
                        $stmt->bindParam(':usuario_id', $usuario['id']);
                        $stmt->execute();
                        $docente = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($docente) {
                            $_SESSION['docente_id'] = $docente['id'];
                        }
                    }
                    
                    header("Location: ../profesor/dashboard.php");
                    break;
                    
                case 3: // Empleado
                    $_SESSION['tipo_usuario'] = 'empleado';
                    
                    // Obtener los datos del empleado
                    $stmt = $conn->prepare("SELECT id, departamento, cargo FROM empleados WHERE usuario_id = :usuario_id");
                    if ($stmt) {
                        $stmt->bindParam(':usuario_id', $usuario['id']);
                        $stmt->execute();
                        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($empleado) {
                            $_SESSION['empleado_id'] = $empleado['id'];
                            $_SESSION['departamento_empleado'] = $empleado['departamento'];
                            $_SESSION['cargo_empleado'] = $empleado['cargo'];
                            $_SESSION['nombre_empleado'] = $usuario['nombre'];
                            $_SESSION['apellido_empleado'] = $usuario['apellido'];
                        }
                    }
                    
                    // Verificar si tiene permiso de administrador
                    $es_admin = false;
                    foreach ($permisos as $permiso) {
                        if (strtolower($permiso['nombre_permiso']) == 'administrador') {
                            $es_admin = true;
                            break;
                        }
                    }
                    
                    if ($es_admin) {
                        $_SESSION['es_admin'] = true;
                    }
                    
                    header("Location: ../form/empleado_dashboard.php");
                    break;
                    
                default:
                    $_SESSION['tipo_usuario'] = 'usuario';
                    header("Location: ../dashboard.php");
                    break;
            }
            exit;
        } else {
            $_SESSION['error'] = "Email o contraseña incorrectos";
            header("Location: ../login.php");
            exit;
        }
    } catch(Exception $e) {
        error_log("Error en login: " . $e->getMessage());
        $_SESSION['error'] = "Error al procesar el login: " . $e->getMessage();
        header("Location: ../login.php");
        exit;
    }
} else {
    header("Location: ../login.php");
    exit;
}
?>
