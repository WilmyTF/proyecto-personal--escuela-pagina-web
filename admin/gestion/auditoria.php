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

// Verificar si se ha enviado un nuevo registro de auditoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_log'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $tipo_accion = $_POST['tipo_accion'];
    $descripcion = $_POST['descripcion'];
    
    try {
        // Verificar que la conexión esté disponible
        if ($conn) {
            // Preparar la consulta para insertar un nuevo registro
            $query = "INSERT INTO auditoria_sistema (usuario_id, tipo_accion, descripcion) 
                    VALUES (:usuario_id, :tipo_accion, :descripcion)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
            $stmt->bindParam(':tipo_accion', $tipo_accion, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $mensaje_exito = "Registro de auditoría agregado correctamente.";
            } else {
                $mensaje_error = "Error al agregar el registro de auditoría.";
            }
        } else {
            $mensaje_error = "No hay conexión a la base de datos disponible.";
        }
    } catch (PDOException $e) {
        $mensaje_error = "Error en la base de datos: " . $e->getMessage();
    }
}

// Función para obtener los registros de auditoría con paginación
function obtenerRegistrosAuditoria($conn, $pagina = 1, $por_pagina = 10, $filtro = '') {
    $inicio = ($pagina - 1) * $por_pagina;
    
    $condicion = "";
    if (!empty($filtro)) {
        $condicion = " WHERE tipo_accion LIKE :filtro OR descripcion LIKE :filtro";
    }
    
    // Contar total de registros
    $query_count = "SELECT COUNT(*) FROM auditoria_sistema" . $condicion;
    $stmt_count = $conn->prepare($query_count);
    
    if (!empty($filtro)) {
        $param_filtro = '%' . $filtro . '%';
        $stmt_count->bindParam(':filtro', $param_filtro, PDO::PARAM_STR);
    }
    
    $stmt_count->execute();
    $total_registros = $stmt_count->fetchColumn();
    
    // Obtener registros paginados
    $query = "SELECT a.*, u.nombre, u.apellido 
              FROM auditoria_sistema a
              LEFT JOIN usuarios u ON a.usuario_id = u.id" . 
              $condicion . 
              " ORDER BY a.fecha DESC
              LIMIT :limite OFFSET :inicio";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':inicio', $inicio, PDO::PARAM_INT);
    $stmt->bindParam(':limite', $por_pagina, PDO::PARAM_INT);
    
    if (!empty($filtro)) {
        $param_filtro = '%' . $filtro . '%';
        $stmt->bindParam(':filtro', $param_filtro, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'registros' => $registros,
        'total' => $total_registros,
        'paginas' => ceil($total_registros / $por_pagina),
        'pagina_actual' => $pagina
    ];
}

// Función para obtener logs de acceso con paginación
function obtenerLogsAcceso($conn, $pagina = 1, $por_pagina = 10, $filtro = '') {
    $inicio = ($pagina - 1) * $por_pagina;
    
    $condicion = "";
    if (!empty($filtro)) {
        $condicion = " WHERE accion LIKE :filtro";
    }
    
    // Contar total de registros
    $query_count = "SELECT COUNT(*) FROM logs_acceso" . $condicion;
    $stmt_count = $conn->prepare($query_count);
    
    if (!empty($filtro)) {
        $param_filtro = '%' . $filtro . '%';
        $stmt_count->bindParam(':filtro', $param_filtro, PDO::PARAM_STR);
    }
    
    $stmt_count->execute();
    $total_registros = $stmt_count->fetchColumn();
    
    // Obtener registros paginados
    $query = "SELECT l.*, u.nombre, u.apellido 
              FROM logs_acceso l
              LEFT JOIN usuarios u ON l.usuario_id = u.id" . 
              $condicion . 
              " ORDER BY l.fecha DESC
              LIMIT :limite OFFSET :inicio";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':inicio', $inicio, PDO::PARAM_INT);
    $stmt->bindParam(':limite', $por_pagina, PDO::PARAM_INT);
    
    if (!empty($filtro)) {
        $param_filtro = '%' . $filtro . '%';
        $stmt->bindParam(':filtro', $param_filtro, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'registros' => $registros,
        'total' => $total_registros,
        'paginas' => ceil($total_registros / $por_pagina),
        'pagina_actual' => $pagina
    ];
}

// Función para obtener información de inicializadores
function obtenerInicializadores($conn) {
    try {
        // Verificar si la tabla existe
        $checkTable = $conn->prepare("
            SELECT EXISTS (
                SELECT 1 
                FROM information_schema.tables 
                WHERE table_name = 'inicializadores_mapa'
            )
        ");
        $checkTable->execute();
        $tableExists = $checkTable->fetchColumn();
        
        if (!$tableExists) {
            return ['error' => 'La tabla inicializadores_mapa no existe. Debe inicializar la base de datos primero.'];
        }
        
        // Consultar los inicializadores del mapa
        $query = "SELECT * FROM inicializadores_mapa ORDER BY fecha_creacion DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $inicializadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $inicializadores;
    } catch (PDOException $e) {
        error_log("Error al obtener inicializadores: " . $e->getMessage());
        return ['error' => 'Error al consultar inicializadores: ' . $e->getMessage()];
    }
}

// Determinar qué pestaña está activa
$pestana_activa = isset($_GET['tab']) ? $_GET['tab'] : 'auditoria';

// Obtener la página actual y filtro
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '';

// Obtener los registros según la pestaña activa
if ($pestana_activa === 'auditoria') {
    $resultado = obtenerRegistrosAuditoria($conn, $pagina_actual, 10, $filtro);
    $registros = $resultado['registros'];
    $total_paginas = $resultado['paginas'];
} elseif ($pestana_activa === 'acceso') {
    $resultado = obtenerLogsAcceso($conn, $pagina_actual, 10, $filtro);
    $registros = $resultado['registros'];
    $total_paginas = $resultado['paginas'];
} elseif ($pestana_activa === 'inicializadores') {
    $inicializadores = obtenerInicializadores($conn);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Auditoría</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="../../css/auditoria.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Aseguramos que la barra lateral aparezca correctamente */
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
        
        /* Estilos específicos para la página de auditoría */
        .tabs {
            display: flex;
            border-bottom: 1px solid #ccc;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: 1px solid transparent;
            border-bottom: none;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }
        
        .tab.active {
            background-color: #f0f0f0;
            border-color: #ccc;
            border-bottom-color: white;
            margin-bottom: -1px;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
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
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
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
        }
        
        .paginacion a:hover {
            background-color: #f2f2f2;
        }
        
        .paginacion .active {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
        }
        
        .form-container {
            margin-top: 20px;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
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
        }
        
        .btn-primary {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
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
        }
        
        .filtro-container input {
            padding: 8px;
            width: 250px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .filtro-container button {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        /* Estilos para los inicializadores */
        .inicializador-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }
        
        .inicializador-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .inicializador-params {
            margin-top: 10px;
        }
        
        .inicializador-params pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        
        .loading-indicator {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            text-align: center;
        }
        
        .loading-indicator i {
            margin-right: 5px;
            color: #007bff;
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
            <h1>Gestión de Auditoría</h1>
            
            <?php if (isset($mensaje_exito)): ?>
                <div class="alert alert-success"><?php echo $mensaje_exito; ?></div>
            <?php endif; ?>
            
            <?php if (isset($mensaje_error)): ?>
                <div class="alert alert-danger"><?php echo $mensaje_error; ?></div>
            <?php endif; ?>
            
            <div class="tabs">
                <a href="?tab=auditoria" class="tab <?php echo $pestana_activa === 'auditoria' ? 'active' : ''; ?>">Auditoría del Sistema</a>
                <a href="?tab=acceso" class="tab <?php echo $pestana_activa === 'acceso' ? 'active' : ''; ?>">Logs de Acceso</a>
                <a href="?tab=inicializadores" class="tab <?php echo $pestana_activa === 'inicializadores' ? 'active' : ''; ?>">Inicializadores</a>
                <a href="?tab=nuevo" class="tab <?php echo $pestana_activa === 'nuevo' ? 'active' : ''; ?>">Registrar Nueva Auditoría</a>
            </div>
            
            <div class="tab-content <?php echo $pestana_activa === 'auditoria' ? 'active' : ''; ?>">
                <h2>Registros de Auditoría del Sistema</h2>
                
                <form class="filtro-container" method="GET" action="">
                    <input type="hidden" name="tab" value="auditoria">
                    <input type="text" name="filtro" placeholder="Filtrar por tipo o descripción" value="<?php echo htmlspecialchars($filtro); ?>">
                    <button type="submit">Filtrar</button>
                    <?php if (!empty($filtro)): ?>
                        <a href="?tab=auditoria">Limpiar filtro</a>
                    <?php endif; ?>
                </form>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Tipo de Acción</th>
                            <th>Fecha</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($registros) && $pestana_activa === 'auditoria'): ?>
                            <?php foreach ($registros as $registro): ?>
                                <tr>
                                    <td><?php echo $registro['id']; ?></td>
                                    <td><?php echo htmlspecialchars($registro['nombre'] . ' ' . $registro['apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($registro['tipo_accion']); ?></td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($registro['fecha'])); ?></td>
                                    <td><?php echo htmlspecialchars($registro['descripcion']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($registros)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No se encontraron registros.</td>
                                </tr>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if (isset($total_paginas) && $pestana_activa === 'auditoria'): ?>
                    <div class="paginacion">
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <?php if ($i == $pagina_actual): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?tab=auditoria&pagina=<?php echo $i; ?>&filtro=<?php echo urlencode($filtro); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content <?php echo $pestana_activa === 'acceso' ? 'active' : ''; ?>">
                <h2>Logs de Acceso</h2>
                
                <form class="filtro-container" method="GET" action="">
                    <input type="hidden" name="tab" value="acceso">
                    <input type="text" name="filtro" placeholder="Filtrar por acción" value="<?php echo htmlspecialchars($filtro); ?>">
                    <button type="submit">Filtrar</button>
                    <?php if (!empty($filtro)): ?>
                        <a href="?tab=acceso">Limpiar filtro</a>
                    <?php endif; ?>
                </form>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>IP</th>
                            <th>Fecha</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($registros) && $pestana_activa === 'acceso'): ?>
                            <?php foreach ($registros as $registro): ?>
                                <tr>
                                    <td><?php echo $registro['id']; ?></td>
                                    <td><?php echo htmlspecialchars($registro['nombre'] . ' ' . $registro['apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($registro['ip']); ?></td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($registro['fecha'])); ?></td>
                                    <td><?php echo htmlspecialchars($registro['accion']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($registros)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No se encontraron registros.</td>
                                </tr>
                            <?php endif; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if (isset($total_paginas) && $pestana_activa === 'acceso'): ?>
                    <div class="paginacion">
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <?php if ($i == $pagina_actual): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?tab=acceso&pagina=<?php echo $i; ?>&filtro=<?php echo urlencode($filtro); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content <?php echo $pestana_activa === 'inicializadores' ? 'active' : ''; ?>">
                <h2>Inicializadores del Sistema</h2>
                
                <!-- Botón para inicializador principal -->
                <div class="inicializador-card" style="background-color: #f0f8ff; border-left: 5px solid #007bff;">
                    <div class="inicializador-header">
                        <h3>Inicializador Principal del Sistema</h3>
                        <span>Sistema de gestión escolar</span>
                    </div>
                    <p>Este inicializador configura los permisos básicos del sistema y crea usuarios de prueba con roles predefinidos.</p>
                    <div class="inicializador-params">
                        <h4>Usuarios creados:</h4>
                        <pre>- Estudiante: estudiante@test.com / password123
- Profesor: profesor@test.com / password123
- Empleado/Admin: empleado@test.com / password123</pre>
                    </div>
                    <div class="inicializador-actions">
                        <a href="../../inicializador.php" class="btn-primary" target="_blank">Ejecutar Inicializador Principal</a>
                    </div>
                </div>
                
                <!-- Botón para inicializador de mapa -->
                <div class="inicializador-card" style="background-color: #e6f7ff; border-left: 5px solid #0096c7; margin-top: 20px;">
                    <div class="inicializador-header">
                        <h3>Inicializador del Mapa</h3>
                        <span>Mapa interactivo del centro educativo</span>
                    </div>
                    <p>Este inicializador configura todas las tablas relacionadas con el mapa interactivo del centro educativo, 
                       incluyendo áreas, tipos de áreas, subdivisiones y responsables.</p>
                    <div class="inicializador-params">
                        <h4>Tablas inicializadas:</h4>
                        <pre>- mapas
- areas_mapa
- subdivisiones_area
- tipos_area
- personal_area
- responsables_area</pre>
                    </div>
                    <div class="inicializador-actions">
                        <a href="../../inicializador_mapa.php" class="btn-primary btn-web" target="_blank">Ver Inicializador de Mapa</a>
                        <a href="../../inicializador_mapa.php?api=true" class="btn-primary btn-api" style="margin-left: 10px; background-color: #6c757d;">Ejecutar vía API</a>
                    </div>
                </div>
                
                <?php if (isset($inicializadores)): ?>
                    <?php if (isset($inicializadores['error'])): ?>
                        <div class="alert alert-warning" style="margin-top: 20px;">
                            <h4>Advertencia</h4>
                            <p><?php echo $inicializadores['error']; ?></p>
                            <p>Es posible que necesite ejecutar primero el Inicializador Principal para configurar la base de datos.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-danger" style="margin-top: 20px;">
                        <p>Error al cargar inicializadores. Por favor, compruebe la conexión a la base de datos.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content <?php echo $pestana_activa === 'nuevo' ? 'active' : ''; ?>">
                <h2>Registrar Nueva Auditoría</h2>
                
                <div class="form-container">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="tipo_accion">Tipo de Acción:</label>
                            <select name="tipo_accion" id="tipo_accion" class="form-control" required>
                                <option value="">Seleccione un tipo</option>
                                <option value="Modificación Usuario">Modificación Usuario</option>
                                <option value="Cambio Mapa">Cambio Mapa</option>
                                <option value="Ejecución Inicializador">Ejecución Inicializador</option>
                                <option value="Modificación Sistema">Modificación Sistema</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción:</label>
                            <textarea name="descripcion" id="descripcion" class="form-control" rows="4" required></textarea>
                        </div>
                        
                        <button type="submit" name="agregar_log" class="btn-primary">Registrar Auditoría</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../../js/auditoria.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Activar submenús al cargar la página
            const menuSections = document.querySelectorAll('.menu-section');
            
            menuSections.forEach(section => {
                // Activar el menú de Gestión del Centro ya que estamos en auditoría
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
            
            // Marcar enlace activo en el sidebar
            const activeLink = document.querySelector('.menu a[href="/admin/gestion/auditoria.php"]');
            if (activeLink) {
                activeLink.closest('li').classList.add('active');
            }
        });
    </script>
</body>
</html> 