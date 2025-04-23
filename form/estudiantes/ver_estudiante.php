<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    header("Location: ../../login.php");
    exit;
}

require_once '../../includes/conexion.php';
verificarConexion();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: gestion.php");
    exit;
}

$estudiante_id = $_GET['id'];

// Obtener información del estudiante
$query = "SELECT e.*, u.email 
          FROM estudiantes e 
          LEFT JOIN usuarios u ON e.usuario_id = u.id 
          WHERE e.usuario_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$estudiante_id]);
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    header("Location: gestion.php");
    exit;
}

// Obtener información de los padres/tutores
$query = "SELECT p.*, ep.es_principal 
          FROM padres_tutores p 
          JOIN estudiante_padre ep ON p.id = ep.padre_id 
          WHERE ep.estudiante_id = ?
          ORDER BY ep.es_principal DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$estudiante_id]);
$padres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Estudiante</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .estudiante-container {
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
            display: inline-block;
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
        .info-section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .info-section h3 {
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-item label {
            font-weight: bold;
            color: #666;
        }
        .info-item p {
            margin: 5px 0;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn-secondary {
            padding: 8px 15px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
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
            <div class="header-actions">
                <h1>Detalles del Estudiante</h1>
                <a href="gestion.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
            
            <div class="estudiante-container">
                <div class="info-section">
                    <h3>Información Personal</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Nombre:</label>
                            <p><?php echo htmlspecialchars($estudiante['nombre'] ?? ''); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Apellido:</label>
                            <p><?php echo htmlspecialchars($estudiante['apellido'] ?? ''); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Email:</label>
                            <p><?php echo htmlspecialchars($estudiante['email'] ?? ''); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Matrícula:</label>
                            <p><?php echo htmlspecialchars($estudiante['matricula'] ?? ''); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Estado:</label>
                            <p>
                                <span class="estado-badge estado-<?php echo strtolower($estudiante['estado'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($estudiante['estado'] ?? ''); ?>
                                </span>
                            </p>
                        </div>
                        <div class="info-item">
                            <label>Dirección:</label>
                            <p><?php echo htmlspecialchars($estudiante['direccion'] ?? ''); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h3>Información Académica</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Curso:</label>
                            <p><?php echo htmlspecialchars($estudiante['curso_id'] ?? 'No especificado'); ?></p>
                        </div>
                        <div class="info-item">
                            <label>Historial Académico:</label>
                            <p><?php echo htmlspecialchars($estudiante['historial_academico'] ?? 'No especificado'); ?></p>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($padres)): ?>
                <div class="info-section padres-container">
                    <h3>Padres/Tutores</h3>
                    <?php foreach ($padres as $padre): ?>
                        <div class="padre-card">
                            <div class="padre-header">
                                <h4><?php echo htmlspecialchars($padre['nombre'] . ' ' . $padre['apellido']); ?></h4>
                                <?php if ($padre['es_principal']): ?>
                                    <span class="es-principal">Principal</span>
                                <?php endif; ?>
                            </div>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Teléfono:</label>
                                    <p><?php echo htmlspecialchars($padre['telefono'] ?? 'No especificado'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Email:</label>
                                    <p><?php echo htmlspecialchars($padre['email'] ?? 'No especificado'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Relación:</label>
                                    <p><?php echo htmlspecialchars($padre['relacion'] ?? 'No especificada'); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 