<?php

require_once '../conexion.php';
require_once '../registrar_auditoria.php';

session_start();
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['tipo_usuario']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    header('Location: ../../login.php');
    exit;
}


if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => 'ID de área no válido'
    ];
    header('Location: listar_areas.php');
    exit;
}

$area_id = intval($_GET['id']);
$usuario_id = $_SESSION['usuario_id'];


$errores = [];
$mensaje_exito = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $tipo_id = isset($_POST['tipo_id']) ? intval($_POST['tipo_id']) : 0;
    $color = isset($_POST['color']) ? trim($_POST['color']) : '';
    $data_id = isset($_POST['data_id']) ? trim($_POST['data_id']) : '';
    
   
    if (empty($nombre)) $errores[] = "El nombre es obligatorio";
    if ($tipo_id <= 0) $errores[] = "Debe seleccionar un tipo de área";
    if (empty($color)) $errores[] = "El color es obligatorio";
    
  
    if (empty($errores)) {
        try {
           
            $conn->beginTransaction();
            
           
            $stmt = $conn->prepare("
                SELECT a.nombre, a.color, a.data_id, t.nombre as tipo_nombre 
                FROM areas_mapa a
                LEFT JOIN tipos_area t ON a.tipo_id = t.id
                WHERE a.id = :id
            ");
            $stmt->bindParam(':id', $area_id, PDO::PARAM_INT);
            $stmt->execute();
            $area_anterior = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Preparar la consulta para actualizar el área
            $sql = "UPDATE areas_mapa SET 
                    nombre = :nombre, 
                    tipo_id = :tipo_id, 
                    color = :color";
            
            // Si se proporcionó un data_id, incluirlo en la actualización
            if (!empty($data_id)) {
                $sql .= ", data_id = :data_id";
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':tipo_id', $tipo_id);
            $stmt->bindParam(':color', $color);
            $stmt->bindParam(':id', $area_id);
            
            if (!empty($data_id)) {
                $stmt->bindParam(':data_id', $data_id);
            }
            
            $resultado = $stmt->execute();
            
            if ($resultado) {
                // Confirmar transacción
                $conn->commit();
                
                // Obtener información del nuevo tipo
                $stmt = $conn->prepare("SELECT nombre FROM tipos_area WHERE id = :id");
                $stmt->bindParam(':id', $tipo_id);
                $stmt->execute();
                $tipo_nuevo = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Construir detalle de cambios para auditoría
                $cambios = [];
                if ($area_anterior['nombre'] != $nombre) $cambios[] = "Nombre: {$area_anterior['nombre']} -> $nombre";
                if ($area_anterior['tipo_nombre'] != $tipo_nuevo['nombre']) $cambios[] = "Tipo: {$area_anterior['tipo_nombre']} -> {$tipo_nuevo['nombre']}";
                if ($area_anterior['color'] != $color) $cambios[] = "Color: {$area_anterior['color']} -> $color";
                if (!empty($data_id) && $area_anterior['data_id'] != $data_id) $cambios[] = "ID de datos: {$area_anterior['data_id']} -> $data_id";
                
                $detalle_cambios = "Cambios realizados: " . implode(", ", $cambios);
                
                // Registrar en auditoría
                registrarCambioMapa($conn, $usuario_id, $area_id, $detalle_cambios);
                
                $mensaje_exito = "Área actualizada correctamente";
            } else {
                $conn->rollBack();
                $errores[] = "Error al actualizar el área";
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $errores[] = "Error en la base de datos: " . $e->getMessage();
        }
    }
}

// Obtener datos del área a editar
try {
    $stmt = $conn->prepare("
        SELECT a.*, t.nombre as tipo_nombre 
        FROM areas_mapa a
        LEFT JOIN tipos_area t ON a.tipo_id = t.id
        WHERE a.id = :id
    ");
    $stmt->bindParam(':id', $area_id, PDO::PARAM_INT);
    $stmt->execute();
    $area = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$area) {
        $_SESSION['mensaje'] = [
            'tipo' => 'error',
            'texto' => 'Área no encontrada'
        ];
        header('Location: listar_areas.php');
        exit;
    }
} catch (PDOException $e) {
    $errores[] = "Error al obtener datos del área: " . $e->getMessage();
}

// Obtener todos los tipos de área disponibles
try {
    $stmt = $conn->prepare("SELECT * FROM tipos_area WHERE activo = TRUE");
    $stmt->execute();
    $tipos_area = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errores[] = "Error al obtener tipos de área: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Área del Mapa</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="../../css/mapa.css">
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
        
        .color-preview {
            display: inline-block;
            width: 30px;
            height: 30px;
            margin-left: 10px;
            border: 1px solid #ddd;
            vertical-align: middle;
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
    </style>
</head>
<body>
    <div class="container">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php include '../../includes/empleado_sidebar.php'; ?>
        
        <div class="main-content">
            <h1>Editar Área del Mapa</h1>
            
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
                        <input type="text" id="nombre" name="nombre" class="form-control" value="<?php echo htmlspecialchars($area['nombre']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_id">Tipo de Área:</label>
                        <select id="tipo_id" name="tipo_id" class="form-control" required>
                            <option value="">Seleccione un tipo...</option>
                            <?php foreach ($tipos_area as $tipo): ?>
                                <option value="<?php echo $tipo['id']; ?>" <?php echo $area['tipo_id'] == $tipo['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tipo['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="color">Color:</label>
                        <input type="color" id="color" name="color" class="form-control" value="<?php echo htmlspecialchars($area['color']); ?>" style="width: auto;">
                        <span class="color-preview" id="colorPreview" style="background-color: <?php echo htmlspecialchars($area['color']); ?>"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="data_id">ID de Datos (opcional):</label>
                        <input type="text" id="data_id" name="data_id" class="form-control" value="<?php echo htmlspecialchars($area['data_id']); ?>">
                        <small>Identificador único para el área en el mapa SVG</small>
                    </div>
                    
                    <div class="btn-container">
                        <a href="listar_areas.php" class="btn-secondary">Cancelar</a>
                        <button type="submit" class="btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar previsualización de color cuando cambie
            const colorInput = document.getElementById('color');
            const colorPreview = document.getElementById('colorPreview');
            
            colorInput.addEventListener('input', function() {
                colorPreview.style.backgroundColor = this.value;
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