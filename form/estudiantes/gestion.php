<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    header("Location: ../../login.php");
    exit;
}

require_once '../../includes/conexion.php';
verificarConexion();

// Obtener el filtro de estado si existe
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Obtener todos los estudiantes
$query = "SELECT e.usuario_id, e.matricula, e.estado, e.curso_id, e.historial_academico, 
                 e.nombre, e.apellido, e.direccion, u.email 
          FROM estudiantes e 
          LEFT JOIN usuarios u ON e.usuario_id = u.id";

// Agregar filtro de estado si se seleccionó uno
if (!empty($filtro_estado)) {
    $query .= " WHERE e.estado = :estado";
}
$query .= " ORDER BY e.nombre, e.apellido";

$stmt = $conn->prepare($query);
if (!empty($filtro_estado)) {
    $stmt->bindParam(':estado', $filtro_estado, PDO::PARAM_STR);
}
$stmt->execute();
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar cambio de estado si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['estudiante_id']) && isset($_POST['nuevo_estado'])) {
    $estudiante_id = $_POST['estudiante_id'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    $query = "UPDATE estudiantes SET estado = :estado WHERE usuario_id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':estado', $nuevo_estado, PDO::PARAM_STR);
    $stmt->bindParam(':id', $estudiante_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $mensaje_exito = "Estado actualizado correctamente";
        // Recargar la página para mostrar los cambios
        header("Location: " . $_SERVER['PHP_SELF'] . (!empty($filtro_estado) ? "?estado=" . urlencode($filtro_estado) : ""));
        exit;
    } else {
        $mensaje_error = "Error al actualizar el estado";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Estudiantes</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .estudiantes-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .estado-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .estado-activo {
            background-color: #d4edda;
            color: #155724;
        }
        .estado-inactivo {
            background-color: #f8d7da;
            color: #721c24;
        }
        .estado-sancionado {
            background-color: #fff3cd;
            color: #856404;
        }
        .estado-graduado {
            background-color: #cce5ff;
            color: #004085;
        }
        .filtros-container {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .filtro-item {
            display: flex;
            flex-direction: column;
        }
        .filtro-item label {
            margin-bottom: 5px;
            font-weight: bold;
        }
        .filtro-item select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .table tr:hover {
            background-color: #f5f5f5;
        }
        .estado-select {
            padding: 6px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background-color: white;
        }
        .btn-info {
            padding: 6px 12px;
            background-color: #17a2b8;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-right: 5px;
        }
        .btn-info:hover {
            background-color: #138496;
        }
        .btn-primary {
            padding: 6px 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .actions {
            display: flex;
            gap: 5px;
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
            <h1>Gestión de Estudiantes</h1>
            
            <?php if (isset($mensaje_exito)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($mensaje_exito); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($mensaje_error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($mensaje_error); ?>
                </div>
            <?php endif; ?>
            
            <div class="filtros-container">
                <form method="GET" action="">
                    <div class="filtros-grid">
                        <div class="filtro-item">
                            <label for="estado">Estado:</label>
                            <select name="estado" id="estado">
                                <option value="">Todos</option>
                                <option value="Activo" <?php echo $filtro_estado === 'Activo' ? 'selected' : ''; ?>>Activo</option>
                                <option value="Inactivo" <?php echo $filtro_estado === 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                <option value="Sancionado" <?php echo $filtro_estado === 'Sancionado' ? 'selected' : ''; ?>>Sancionado</option>
                                <option value="Graduado" <?php echo $filtro_estado === 'Graduado' ? 'selected' : ''; ?>>Graduado</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="estudiantes-container">
                <?php if (empty($estudiantes)): ?>
                    <p>No se encontraron estudiantes.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Email</th>
                                <th>Matrícula</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($estudiantes as $estudiante): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($estudiante['nombre'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['apellido'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['email'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($estudiante['matricula'] ?? ''); ?></td>
                                    <td>
                                        <form method="POST" action="" class="estado-form">
                                            <?php if (isset($estudiante['usuario_id'])): ?>
                                            <input type="hidden" name="estudiante_id" value="<?php echo htmlspecialchars($estudiante['usuario_id']); ?>">
                                            <select name="nuevo_estado" class="estado-select" onchange="this.form.submit()">
                                                <option value="Activo" <?php echo ($estudiante['estado'] ?? '') === 'Activo' ? 'selected' : ''; ?>>Activo</option>
                                                <option value="Inactivo" <?php echo ($estudiante['estado'] ?? '') === 'Inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                                <option value="Sancionado" <?php echo ($estudiante['estado'] ?? '') === 'Sancionado' ? 'selected' : ''; ?>>Sancionado</option>
                                                <option value="Graduado" <?php echo ($estudiante['estado'] ?? '') === 'Graduado' ? 'selected' : ''; ?>>Graduado</option>
                                            </select>
                                            <?php else: ?>
                                            <span class="text-muted">ID no disponible</span>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td class="actions">
                                        <?php if (isset($estudiante['usuario_id'])): ?>
                                        <a href="ver_estudiante.php?id=<?php echo htmlspecialchars($estudiante['usuario_id']); ?>" class="btn btn-info" title="Ver datos del estudiante">
                                            <i class="fas fa-user"></i> Datos
                                        </a>
                                        <a href="ver_padres.php?id=<?php echo htmlspecialchars($estudiante['usuario_id']); ?>" class="btn btn-primary" title="Ver datos de los padres">
                                            <i class="fas fa-users"></i> Padres
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted">Acciones no disponibles</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Manejar el filtro de estado
        const estadoSelect = document.getElementById('estado');
        if (estadoSelect) {
            estadoSelect.addEventListener('change', function() {
                this.form.submit();
            });
        }
    });
    </script>
</body>
</html> 