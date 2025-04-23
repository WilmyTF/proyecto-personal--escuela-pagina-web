<?php
// Iniciar sesión si no está iniciada
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Verificar si se proporcionó un ID de reporte
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: gestionar.php');
    exit;
}

$reporte_id = $_GET['id'];

// Incluir archivo de conexión
require_once '../../includes/conexion.php';

// Consulta para obtener los detalles del reporte
$query = "
    SELECT 
        r.id, 
        r.titulo, 
        r.descripcion,
        r.fecha_creacion, 
        r.fecha_actualizacion,
        r.area_id,
        r.data_id,
        e.id as estado_id,
        e.nombre as estado_nombre, 
        e.color as estado_color,
        t.id as tipo_id,
        t.nombre as tipo_nombre,
        u.nombre as usuario_nombre,
        u.apellido as usuario_apellido,
        a.nombre as area_nombre
    FROM 
        reportes r
        JOIN estados_reporte e ON r.estado_id = e.id
        JOIN tipos_reporte t ON r.tipo_id = t.id
        JOIN usuarios u ON r.usuario_id = u.id
        LEFT JOIN public.subdivisiones_area a ON r.area_id = a.area_id AND r.data_id = a.data_id
    WHERE 
        r.id = $1
";

$result = pg_query_params($conexion, $query, [$reporte_id]);

if (!$result || pg_num_rows($result) === 0) {
    // Mensaje de error
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => "No se encontró el reporte con ID: {$reporte_id}"
    ];
    
    header('Location: gestionar.php');
    exit;
}

$reporte = pg_fetch_assoc($result);

// Obtener las imágenes del reporte
$query_imagenes = "
    SELECT 
        id,
        ruta_imagen,
        fecha_subida
    FROM 
        imagenes_reporte
    WHERE 
        reporte_id = $1
    ORDER BY
        fecha_subida ASC
";

$result_imagenes = pg_query_params($conexion, $query_imagenes, [$reporte_id]);
$imagenes = [];

if ($result_imagenes) {
    while ($row = pg_fetch_assoc($result_imagenes)) {
        $imagenes[] = $row;
    }
}

// Obtener el historial de cambios
$query_historial = "
    SELECT 
        h.fecha_cambio,
        e_ant.nombre as estado_anterior,
        e_ant.color as color_anterior,
        e_new.nombre as estado_nuevo,
        e_new.color as color_nuevo,
        u.nombre as usuario_nombre,
        u.apellido as usuario_apellido,
        h.comentario
    FROM 
        historial_reportes h
        LEFT JOIN estados_reporte e_ant ON h.estado_anterior = e_ant.id
        LEFT JOIN estados_reporte e_new ON h.estado_nuevo = e_new.id
        JOIN usuarios u ON h.usuario_id = u.id
    WHERE 
        h.reporte_id = $1
    ORDER BY
        h.fecha_cambio DESC
";

$result_historial = pg_query_params($conexion, $query_historial, [$reporte_id]);
$historial = [];

if ($result_historial) {
    while ($row = pg_fetch_assoc($result_historial)) {
        $historial[] = $row;
    }
}

// Obtener los comentarios
$query_comentarios = "
    SELECT 
        c.id,
        c.comentario,
        c.fecha_comentario,
        u.nombre as usuario_nombre,
        u.apellido as usuario_apellido
    FROM 
        comentarios_reporte c
        JOIN usuarios u ON c.usuario_id = u.id
    WHERE 
        c.reporte_id = $1
    ORDER BY
        c.fecha_comentario DESC
";

$result_comentarios = pg_query_params($conexion, $query_comentarios, [$reporte_id]);
$comentarios = [];

if ($result_comentarios) {
    while ($row = pg_fetch_assoc($result_comentarios)) {
        $comentarios[] = $row;
    }
}

// Obtener todos los estados para el formulario de cambio de estado
$query_estados = "SELECT id, nombre FROM estados_reporte ORDER BY nombre";
$result_estados = pg_query($conexion, $query_estados);
$estados = [];

if ($result_estados) {
    while ($row = pg_fetch_assoc($result_estados)) {
        $estados[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Reporte - <?php echo htmlspecialchars($reporte['id']); ?></title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="../../css/reportes.css">
    <link rel="stylesheet" href="../../css/reporte_detalle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php include '../../includes/empleado_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="reporte-header">
                <div class="back-button">
                    <a href="gestionar.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Volver a la lista
                    </a>
                </div>
                <h1 class="reporte-id"><?php echo htmlspecialchars($reporte['id']); ?></h1>
                <div class="reporte-meta">
                    <span class="status-badge" style="background-color: <?php echo $reporte['estado_color']; ?>">
                        <?php echo htmlspecialchars($reporte['estado_nombre']); ?>
                    </span>
                    <span class="tipo-badge">
                        <?php echo htmlspecialchars($reporte['tipo_nombre']); ?>
                    </span>
                    <?php if (!empty($reporte['area_nombre'])): ?>
                    <span class="area-badge">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($reporte['area_nombre']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="reporte-container">
                <div class="reporte-info">
                    <div class="reporte-section">
                        <h2 class="reporte-titulo"><?php echo htmlspecialchars($reporte['titulo']); ?></h2>
                        <div class="reporte-autor">
                            Creado por: <?php echo htmlspecialchars($reporte['usuario_nombre'] . ' ' . $reporte['usuario_apellido']); ?>
                            el <?php echo date('d/m/Y H:i', strtotime($reporte['fecha_creacion'])); ?>
                        </div>
                        <?php if (!empty($reporte['fecha_actualizacion'])): ?>
                        <div class="reporte-actualizacion">
                            Última actualización: <?php echo date('d/m/Y H:i', strtotime($reporte['fecha_actualizacion'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="reporte-section">
                        <h3 class="section-title">Descripción</h3>
                        <div class="reporte-descripcion">
                            <?php echo nl2br(htmlspecialchars($reporte['descripcion'])); ?>
                        </div>
                    </div>

                    <?php if (!empty($reporte['area_id']) && !empty($reporte['data_id'])): ?>
                    <div class="reporte-section">
                        <h3 class="section-title">Ubicación</h3>
                        <div class="reporte-area">
                            <p><strong>Área:</strong> <?php echo htmlspecialchars($reporte['area_nombre']); ?></p>
                            <p><strong>Identificador:</strong> <?php echo htmlspecialchars($reporte['data_id']); ?></p>
                            <div class="area-map" id="areaMap" data-area-id="<?php echo $reporte['area_id']; ?>" data-data-id="<?php echo htmlspecialchars($reporte['data_id']); ?>">
                                <!-- Aquí se podría integrar un mapa o visualización del área -->
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($imagenes)): ?>
                    <div class="reporte-section">
                        <h3 class="section-title">Imágenes</h3>
                        <div class="reporte-imagenes">
                            <?php foreach ($imagenes as $imagen): ?>
                            <div class="imagen-item">
                                <a href="../../<?php echo htmlspecialchars($imagen['ruta_imagen']); ?>" target="_blank">
                                    <img src="../../<?php echo htmlspecialchars($imagen['ruta_imagen']); ?>" alt="Imagen del reporte">
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="reporte-sidebar">
                    <div class="sidebar-section">
                        <h3 class="section-title">Cambiar Estado</h3>
                        <form action="cambiar_estado.php" method="POST" id="formCambiarEstado">
                            <input type="hidden" name="reporte_id" value="<?php echo htmlspecialchars($reporte['id']); ?>">
                            
                            <div class="form-group">
                                <label for="estado_id">Nuevo Estado:</label>
                                <select name="estado_id" id="estado_id" required>
                                    <option value="">Seleccione un estado</option>
                                    <?php foreach ($estados as $estado): ?>
                                    <option value="<?php echo $estado['id']; ?>" <?php echo ($reporte['estado_id'] == $estado['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($estado['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="comentario">Comentario:</label>
                                <textarea name="comentario" id="comentario" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </form>
                    </div>

                    <?php if (!empty($historial)): ?>
                    <div class="sidebar-section">
                        <h3 class="section-title">Historial de Cambios</h3>
                        <div class="historial-list">
                            <?php foreach ($historial as $cambio): ?>
                            <div class="historial-item">
                                <div class="historial-fecha">
                                    <?php echo date('d/m/Y H:i', strtotime($cambio['fecha_cambio'])); ?>
                                </div>
                                <div class="historial-cambio">
                                    <span class="status-badge mini" style="background-color: <?php echo $cambio['color_anterior']; ?>">
                                        <?php echo htmlspecialchars($cambio['estado_anterior']); ?>
                                    </span>
                                    <i class="fas fa-arrow-right"></i>
                                    <span class="status-badge mini" style="background-color: <?php echo $cambio['color_nuevo']; ?>">
                                        <?php echo htmlspecialchars($cambio['estado_nuevo']); ?>
                                    </span>
                                </div>
                                <div class="historial-usuario">
                                    por <?php echo htmlspecialchars($cambio['usuario_nombre'] . ' ' . $cambio['usuario_apellido']); ?>
                                </div>
                                <?php if (!empty($cambio['comentario'])): ?>
                                <div class="historial-comentario">
                                    <?php echo nl2br(htmlspecialchars($cambio['comentario'])); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="comentarios-section">
                <h3 class="section-title">Comentarios</h3>
                
                <form action="agregar_comentario.php" method="POST" id="formComentario" class="comentario-form">
                    <input type="hidden" name="reporte_id" value="<?php echo htmlspecialchars($reporte['id']); ?>">
                    
                    <div class="form-group">
                        <textarea name="comentario" id="nuevoComentario" rows="3" placeholder="Escriba su comentario aquí..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Enviar Comentario
                    </button>
                </form>
                
                <?php if (empty($comentarios)): ?>
                <div class="no-comentarios">
                    <p>No hay comentarios para este reporte.</p>
                </div>
                <?php else: ?>
                <div class="comentarios-list">
                    <?php foreach ($comentarios as $comentario): ?>
                    <div class="comentario-item">
                        <div class="comentario-header">
                            <div class="comentario-usuario">
                                <?php echo htmlspecialchars($comentario['usuario_nombre'] . ' ' . $comentario['usuario_apellido']); ?>
                            </div>
                            <div class="comentario-fecha">
                                <?php echo date('d/m/Y H:i', strtotime($comentario['fecha_comentario'])); ?>
                            </div>
                        </div>
                        <div class="comentario-texto">
                            <?php echo nl2br(htmlspecialchars($comentario['comentario'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Funcionalidad para sidebar y menú
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const container = document.querySelector('.container');
        
        sidebarToggle.addEventListener('click', () => {
            container.classList.toggle('sidebar-collapsed');
            const isCollapsed = container.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
        
        // Recuperar estado del sidebar
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            container.classList.add('sidebar-collapsed');
        }
        
        // Submenús
        const menuSections = document.querySelectorAll('.menu-section');
        
        menuSections.forEach(section => {
            const sectionTitle = section.querySelector('.section-title');
            
            sectionTitle.addEventListener('click', function() {
                menuSections.forEach(otherSection => {
                    if (otherSection !== section) {
                        otherSection.classList.remove('active');
                    }
                });
                
                section.classList.toggle('active');
                
                const isActive = section.classList.contains('active');
                localStorage.setItem(`menuSection_${sectionTitle.textContent.trim()}`, isActive);
            });
            
            const savedState = localStorage.getItem(`menuSection_${sectionTitle.textContent.trim()}`);
            if (savedState === 'true') {
                section.classList.add('active');
            }
        });
        
        // Activar la sección de Reportes en el menú
        const reportesSection = document.querySelector('.section-title:contains("Reportes")').closest('.menu-section');
        if (reportesSection) {
            reportesSection.classList.add('active');
        }
        
        // Validación del formulario de estado
        const formCambiarEstado = document.getElementById('formCambiarEstado');
        
        formCambiarEstado.addEventListener('submit', function(event) {
            const estadoActual = <?php echo $reporte['estado_id']; ?>;
            const nuevoEstado = parseInt(document.getElementById('estado_id').value);
            
            if (estadoActual === nuevoEstado) {
                alert('El estado seleccionado es el mismo que el estado actual. Seleccione un estado diferente.');
                event.preventDefault();
            }
        });
        
        // Galería de imágenes
        const imagenes = document.querySelectorAll('.imagen-item img');
        
        imagenes.forEach(imagen => {
            imagen.addEventListener('click', function(e) {
                e.preventDefault();
                
                const url = this.parentElement.getAttribute('href');
                
                // Crear modal para visualizar la imagen
                const modal = document.createElement('div');
                modal.className = 'imagen-modal';
                
                const modalContent = document.createElement('div');
                modalContent.className = 'imagen-modal-content';
                
                const closeBtn = document.createElement('span');
                closeBtn.className = 'imagen-modal-close';
                closeBtn.innerHTML = '&times;';
                
                const imgElement = document.createElement('img');
                imgElement.src = url;
                
                modalContent.appendChild(closeBtn);
                modalContent.appendChild(imgElement);
                modal.appendChild(modalContent);
                
                document.body.appendChild(modal);
                
                // Mostrar modal
                setTimeout(() => {
                    modal.style.opacity = '1';
                }, 10);
                
                // Cerrar modal
                closeBtn.addEventListener('click', function() {
                    modal.style.opacity = '0';
                    setTimeout(() => {
                        document.body.removeChild(modal);
                    }, 300);
                });
                
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.style.opacity = '0';
                        setTimeout(() => {
                            document.body.removeChild(modal);
                        }, 300);
                    }
                });
            });
        });
    });
    </script>
</body>
</html> 