<?php
// Inicializador del mapa del centro educativo
session_start();

// Mostrar errores durante el desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar autenticación si es necesario
$mostrar_login = false;
if (!isset($_SESSION['usuario_id'])) {
    $mostrar_login = true;
}

// Incluir archivos necesarios
require_once 'includes/conexion.php';
require_once 'includes/inicializador_mapa.php';

// Función para registrar en la auditoría
function registrarAuditoriaMapa($conn, $accion, $descripcion) {
    if ($conn && isset($_SESSION['usuario_id'])) {
        try {
            $usuario_id = $_SESSION['usuario_id'];
            $query = "INSERT INTO auditoria_sistema (usuario_id, tipo_accion, descripcion) 
                    VALUES (:usuario_id, :tipo_accion, :descripcion)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(':tipo_accion', $accion, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al registrar auditoría: " . $e->getMessage());
        }
    }
}

// Variables para los resultados
$resultado = null;
$mensaje_error = null;
$mensaje_exito = null;
$subdivisiones = null;

// Verificar si es una solicitud de API
$es_api = isset($_GET['api']) && $_GET['api'] === 'true';

// Ejecutar inicialización si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inicializar'])) {
    if ($conn) {
        try {
            // Crear instancia del inicializador
            $inicializadorMapa = new InicializadorMapa($conn);
            
            // Ejecutar inicialización completa que genera todas las áreas y subdivisiones
            $resultado = $inicializadorMapa->inicializar();
            
            if ($resultado['exito']) {
                $mensaje_exito = $resultado['mensaje'];
                // Registrar en auditoría
                registrarAuditoriaMapa($conn, 'Inicialización del Mapa', 'Se inicializó correctamente el mapa del centro educativo con todas las áreas y subdivisiones');
            } else {
                $mensaje_error = $resultado['mensaje'];
                // Registrar en auditoría
                registrarAuditoriaMapa($conn, 'Error en Inicialización', 'Error al inicializar el mapa: ' . $resultado['mensaje']);
            }
            
            // Si es una solicitud de API, devolver JSON y terminar
            if ($es_api) {
                header('Content-Type: application/json');
                echo json_encode($resultado);
                exit;
            }
        } catch (Exception $e) {
            $mensaje_error = "Error: " . $e->getMessage();
            // Registrar en auditoría
            registrarAuditoriaMapa($conn, 'Error en Inicialización', 'Excepción: ' . $e->getMessage());
            
            // Si es una solicitud de API, devolver JSON de error y terminar
            if ($es_api) {
                header('Content-Type: application/json');
                echo json_encode(['exito' => false, 'mensaje' => $mensaje_error]);
                exit;
            }
        }
    } else {
        $mensaje_error = "No hay conexión a la base de datos.";
        
        // Si es una solicitud de API, devolver JSON de error y terminar
        if ($es_api) {
            header('Content-Type: application/json');
            echo json_encode(['exito' => false, 'mensaje' => $mensaje_error]);
            exit;
        }
    }
}

// Recuperar subdivisiones si se ha solicitado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recuperar_subdivisiones'])) {
    if ($conn) {
        try {
            // Crear instancia del inicializador
            $inicializadorMapa = new InicializadorMapa($conn);
            
            // Recuperar subdivisiones
            $subdivisiones = $inicializadorMapa->recuperarSubdivisiones();
            
            // Registrar en auditoría
            registrarAuditoriaMapa($conn, 'Recuperación de Subdivisiones', 'Se recuperaron las dimensiones de las subdivisiones del mapa');
            
            // Si es una solicitud de API, devolver JSON y terminar
            if ($es_api) {
                header('Content-Type: application/json');
                echo json_encode(['exito' => true, 'subdivisiones' => $subdivisiones]);
                exit;
            }
        } catch (Exception $e) {
            $mensaje_error = "Error al recuperar subdivisiones: " . $e->getMessage();
            
            // Registrar en auditoría
            registrarAuditoriaMapa($conn, 'Error en Recuperación', 'Excepción: ' . $e->getMessage());
            
            // Si es una solicitud de API, devolver JSON de error y terminar
            if ($es_api) {
                header('Content-Type: application/json');
                echo json_encode(['exito' => false, 'mensaje' => $mensaje_error]);
                exit;
            }
        }
    } else {
        $mensaje_error = "No hay conexión a la base de datos.";
        
        // Si es una solicitud de API, devolver JSON de error y terminar
        if ($es_api) {
            header('Content-Type: application/json');
            echo json_encode(['exito' => false, 'mensaje' => $mensaje_error]);
            exit;
        }
    }
}

// Si es una solicitud de API y no es POST, devolver información
if ($es_api) {
    header('Content-Type: application/json');
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Debe realizar una solicitud POST con el parámetro inicializar=true para ejecutar el inicializador'
    ]);
    exit;
}

// Verificar si existen las tablas necesarias (solo para la interfaz web)
$tablas_existen = false;
if ($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT EXISTS (
                SELECT 1 
                FROM information_schema.tables 
                WHERE table_name = 'mapas'
            )
        ");
        $stmt->execute();
        $tablas_existen = $stmt->fetchColumn();
    } catch (PDOException $e) {
        $mensaje_error = "Error al verificar tablas: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicializador del Mapa - Centro Educativo</title>
    <link rel="stylesheet" href="css/empleado_dashboard.css">
    <link rel="stylesheet" href="css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        .container {
            display: flex;
            min-height: 100vh;
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
        
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        
        .inicializador-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .info-box {
            background-color: #e8f4fd;
            padding: 15px;
            border-left: 5px solid #3498db;
            margin-bottom: 20px;
        }
        
        .warning-box {
            background-color: #fff3cd;
            padding: 15px;
            border-left: 5px solid #ffc107;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .btn:hover {
            background-color: #0b5ed7;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
        }
        
        .btn-info:hover {
            background-color: #138496;
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
        
        .action-buttons {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #6c757d;
            text-decoration: none;
        }
        
        .back-link:hover {
            color: #5a6268;
            text-decoration: underline;
        }
        
        .login-required {
            text-align: center;
            padding: 50px;
        }
        
        .mapa-preview {
            margin-top: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            background-color: #f8f9fa;
        }
        
        .mapa-preview h3 {
            margin-top: 0;
            color: #333;
        }
        
        .mapa-preview .areas {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .area-item {
            padding: 8px 12px;
            border-radius: 4px;
            background-color: #e9ecef;
            font-size: 0.9em;
            color: #495057;
        }
        
        .area-item.edificio {
            background-color: #D3D3D3;
        }
        
        .area-item.deporte {
            background-color: #6A5ACD;
            color: white;
        }
        
        .area-item.parqueo {
            background-color: #FFD700;
        }
        
        .area-item.comedor {
            background-color: #87CEFA;
        }
        
        .secciones-info {
            margin-top: 20px;
        }
        
        .seccion-detalle {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 4px;
        }
        
        .seccion-detalle h4 {
            margin-top: 0;
            color: #333;
        }
        
        .subdivisiones {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 8px;
            margin-top: 10px;
        }
        
        .subdivision {
            background-color: #e0e0e0;
            padding: 6px 10px;
            border-radius: 3px;
            font-size: 0.85em;
        }
        
        .dimensiones-info {
            margin-top: 15px;
            font-size: 0.9em;
            color: #666;
        }
        
        .subdivisiones-result {
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php if (!$mostrar_login): ?>
            <?php include 'includes/empleado_sidebar.php'; ?>
        <?php endif; ?>
        
        <div class="main-content">
            <h1>Inicializador del Mapa del Centro Educativo</h1>
            
            <?php if ($mostrar_login): ?>
                <div class="login-required">
                    <div class="warning-box">
                        <h3>Inicio de sesión requerido</h3>
                        <p>Debe iniciar sesión como empleado o administrador para acceder a esta función.</p>
                    </div>
                    <a href="login.php" class="btn">Iniciar sesión</a>
                </div>
            <?php else: ?>
                <div class="inicializador-container">
                    <?php if (isset($mensaje_exito)): ?>
                        <div class="alert alert-success">
                            <h3>¡Inicialización completada!</h3>
                            <p><?php echo $mensaje_exito; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($mensaje_error)): ?>
                        <div class="alert alert-danger">
                            <h3>Error en la inicialización</h3>
                            <p><?php echo $mensaje_error; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($subdivisiones): ?>
                        <div class="alert alert-success">
                            <h3>Subdivisiones recuperadas</h3>
                        </div>
                        <div class="subdivisiones-result">
                            <?php echo json_encode($subdivisiones, JSON_PRETTY_PRINT); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-box">
                        <h3>¿Qué hace este inicializador?</h3>
                        <p>Este inicializador configura todas las tablas relacionadas con el mapa interactivo del centro educativo:</p>
                        <ul>
                            <li>Crea el mapa principal</li>
                            <li>Configura los tipos de áreas (aulas, oficinas, laboratorios, etc.)</li>
                            <li>Genera las áreas principales del centro</li>
                            <li>Crea subdivisiones para cada área</li>
                        </ul>
                    </div>
                    
                    <div class="warning-box">
                        <h3>¡Advertencia!</h3>
                        <p><strong>Este proceso eliminará todos los datos existentes</strong> en las siguientes tablas:</p>
                        <ul>
                            <li>mapas</li>
                            <li>areas_mapa</li>
                            <li>subdivisiones_area</li>
                            <li>tipos_area</li>
                            <li>personal_area</li>
                            <li>responsables_area</li>
                        </ul>
                        <p>Asegúrese de tener una copia de seguridad si hay datos importantes.</p>
                    </div>
                    
                    <!-- Vista previa del mapa a generar -->
                    <div class="mapa-preview">
                        <h3>Vista previa del mapa a generar</h3>
                        
                        <div class="areas">
                            <div class="area-item parqueo">Parqueo</div>
                            <div class="area-item deporte">Cancha 1</div>
                            <div class="area-item deporte">Cancha 2</div>
                            <div class="area-item edificio">Sección 1</div>
                            <div class="area-item edificio">Sección 2</div>
                            <div class="area-item edificio">Sección 3</div>
                            <div class="area-item edificio">Sección 4</div>
                            <div class="area-item edificio">Sección 5</div>
                            <div class="area-item edificio">Sección 6</div>
                            <div class="area-item edificio">Sección 8</div>
                            <div class="area-item comedor">Comedor</div>
                        </div>
                        
                        <div class="secciones-info">
                            <p>Las secciones 1-6 y 8 incluirán subdivisiones para aulas y espacios:</p>
                            
                            <div class="seccion-detalle">
                                <h4>Dimensiones de áreas</h4>
                                <ul>
                                    <li>Parqueo: (0,800) -> (200,1000)</li>
                                    <li>Cancha 1: (100,30) -> (240,110)</li>
                                    <li>Cancha 2: (260,30) -> (400,110)</li>
                                    <li>Sección 1: (100,150) -> (430,230)</li>
                                    <li>Sección 2: (600,150) -> (880,230)</li>
                                    <li>Sección 3: (100,300) -> (430,380)</li>
                                    <li>Sección 4: (600,300) -> (930,380)</li>
                                    <li>Sección 5: (100,450) -> (430,530)</li>
                                    <li>Sección 6: (600,450) -> (930,530)</li>
                                    <li>Comedor: (100,600) -> (400,960)</li>
                                    <li>Sección 8: (600,600) -> (925,680)</li>
                                </ul>
                            </div>
                            
                            <div class="seccion-detalle">
                                <h4>Detalles de subdivisiones</h4>
                                <p>Las subdivisiones de cada sección seguirán este patrón:</p>
                                <ul>
                                    <li>Primeras 2 subdivisiones: 25px de ancho</li>
                                    <li>Siguientes 4 subdivisiones: 70px de ancho</li>
                                    <li>La sección 8 tiene dimensiones especiales y subdivisiones adicionales</li>
                                </ul>
                                <div class="dimensiones-info">
                                    <p>Todas las subdivisiones tienen una altura de 80px, excepto las subdivisiones especiales de la sección 8-6 que tienen 40px de altura.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!$tablas_existen): ?>
                        <div class="alert alert-danger">
                            <h3>Tablas no encontradas</h3>
                            <p>Las tablas necesarias para el mapa no existen en la base de datos.</p>
                            <p>Debe ejecutar primero el inicializador principal del sistema para crear la estructura de la base de datos.</p>
                        </div>
                        
                        <div class="action-buttons">
                            <a href="inicializador.php" class="btn">Ir al Inicializador Principal</a>
                            <a href="admin/gestion/auditoria.php" class="btn">Volver a Auditoría</a>
                        </div>
                    <?php else: ?>
                        <div class="action-buttons">
                            <form method="POST" action="" style="display: inline-block; margin-right: 10px;">
                                <button type="submit" name="inicializar" class="btn btn-warning" onclick="return confirm('¿Está seguro que desea inicializar el mapa? Esta acción eliminará todos los datos existentes.')">
                                    Inicializar Mapa
                                </button>
                            </form>
                            
                            <form method="POST" action="" style="display: inline-block; margin-right: 10px;">
                                <button type="submit" name="recuperar_subdivisiones" class="btn btn-info">
                                    Recuperar Dimensiones
                                </button>
                            </form>
                            
                            <a href="admin/gestion/auditoria.php" class="btn">Volver a Auditoría</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Activar submenús al cargar la página
            const menuSections = document.querySelectorAll('.menu-section');
            
            menuSections.forEach(section => {
                // Activar el menú de Gestión del Centro
                if (section.querySelector('.section-title').textContent.trim() === 'Gestión del Centro') {
                    section.classList.add('active');
                }
                
                // Configurar evento click para todas las secciones
                section.querySelector('.section-title').addEventListener('click', function() {
                    section.classList.toggle('active');
                });
            });
            
            // Toggle para la barra lateral
            const sidebarToggle = document.getElementById('sidebarToggle');
            const container = document.querySelector('.container');
            
            sidebarToggle.addEventListener('click', function() {
                container.classList.toggle('sidebar-collapsed');
                const isCollapsed = container.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
            });
            
            // Cargar estado de la barra lateral
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                container.classList.add('sidebar-collapsed');
            }
        });
    </script>
</body>
</html> 