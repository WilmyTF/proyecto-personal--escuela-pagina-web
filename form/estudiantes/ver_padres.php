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
$query = "SELECT e.nombre, e.apellido 
          FROM estudiantes e 
          WHERE e.usuario_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$estudiante_id]);
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    header("Location: gestion.php");
    exit;
}

// Obtener información de los padres usando la tabla estudiante_padre
$query = "SELECT p.*, ep.es_principal, ep.fecha_creacion
          FROM padres_tutores p
          INNER JOIN estudiante_padre ep ON p.id = ep.padre_id
          WHERE ep.estudiante_id = ?
          ORDER BY ep.es_principal DESC, p.apellido, p.nombre";
$stmt = $conn->prepare($query);
$stmt->execute([$estudiante_id]);
$padres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Padres/Tutores del Estudiante</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .padres-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .padre-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .padre-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .padre-nombre {
            font-size: 1.2em;
            font-weight: bold;
            color: #2c3e50;
        }
        .badge-principal {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
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
            display: block;
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
        }
        .info-item p {
            margin: 0;
            color: #333;
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
        .btn-primary {
            padding: 8px 15px;
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
        .header-buttons {
            display: flex;
            gap: 10px;
        }
        .estudiante-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .estudiante-info h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.5em;
        }
        .fecha-asignacion {
            color: #6c757d;
            font-size: 0.9em;
            margin-top: 5px;
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
                <h1>Padres/Tutores</h1>
                <div class="header-buttons">
                    <a href="#" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a href="gestion.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            <div class="estudiante-info">
                <h2>Estudiante: <?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellido']); ?></h2>
            </div>
            
            <div class="padres-container">
                <?php if (empty($padres)): ?>
                    <p>No hay padres o tutores registrados para este estudiante.</p>
                <?php else: ?>
                    <?php foreach ($padres as $padre): ?>
                        <div class="padre-card">
                            <div class="padre-header">
                                <span class="padre-nombre">
                                    <?php echo htmlspecialchars($padre['nombre'] . ' ' . $padre['apellido']); ?>
                                </span>
                                <?php if ($padre['es_principal']): ?>
                                    <span class="badge-principal">Tutor Principal</span>
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
                                    <label>Ocupación:</label>
                                    <p><?php echo htmlspecialchars($padre['ocupacion'] ?? 'No especificada'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Dirección:</label>
                                    <p><?php echo htmlspecialchars($padre['direccion'] ?? 'No especificada'); ?></p>
                                </div>
                            </div>
                            
                            <div class="fecha-asignacion">
                                Asignado el: <?php echo date('d/m/Y', strtotime($padre['fecha_creacion'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 