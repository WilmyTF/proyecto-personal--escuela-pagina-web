<?php

session_start();


if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit;
}

$mensaje = null;
if (isset($_SESSION['mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    unset($_SESSION['mensaje']); 
}

$errores = [];
if (isset($_SESSION['errores_reporte'])) {
    $errores = $_SESSION['errores_reporte'];
    unset($_SESSION['errores_reporte']); 
}

$form_data = [];
if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    unset($_SESSION['form_data']); 
}

require_once '../../includes/conexion.php';


$estados = [];
$query = "SELECT id, nombre, color FROM estados_reporte ORDER BY nombre";
$result = pg_query($conexion, $query);
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $estados[] = $row;
    }
}


$tipos = [];
$query = "SELECT id, nombre FROM tipos_reporte ORDER BY nombre";
$result = pg_query($conexion, $query);
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $tipos[] = $row;
    }
}

$areas = [];
$query = "SELECT area_id, nombre, data_id FROM public.subdivisiones_area ORDER BY nombre";
$result = pg_query($conexion, $query);
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $areas[] = $row;
    }
}


$filtro_estado = isset($_GET['estado']) ? intval($_GET['estado']) : 0;
$filtro_tipo = isset($_GET['tipo']) ? intval($_GET['tipo']) : 0;
$filtro_fecha = isset($_GET['fecha']) ? $_GET['fecha'] : '';
$filtro_texto = isset($_GET['texto']) ? $_GET['texto'] : '';
$filtro_area = isset($_GET['area']) ? intval($_GET['area']) : 0;

$where_clauses = [];
$params = [];
$param_index = 1;

if ($filtro_estado > 0) {
    $where_clauses[] = "r.estado_id = $".$param_index;
    $params[] = $filtro_estado;
    $param_index++;
}

if ($filtro_tipo > 0) {
    $where_clauses[] = "r.tipo_id = $".$param_index;
    $params[] = $filtro_tipo;
    $param_index++;
}

if (!empty($filtro_fecha)) {
    $where_clauses[] = "DATE(r.fecha_creacion) = $".$param_index;
    $params[] = $filtro_fecha;
    $param_index++;
}

if (!empty($filtro_texto)) {
    $where_clauses[] = "(r.titulo ILIKE $".$param_index." OR r.descripcion ILIKE $".$param_index.")";
    $params[] = "%$filtro_texto%";
    $param_index++;
}

if ($filtro_area > 0) {
    $where_clauses[] = "r.area_id = $".$param_index;
    $params[] = $filtro_area;
    $param_index++;
}

$where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$query_count = "SELECT COUNT(*) FROM reportes r $where_clause";
$result_count = pg_query_params($conexion, $query_count, $params);
$total_reportes = ($result_count) ? pg_fetch_result($result_count, 0, 0) : 0;


$reportes_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_actual - 1) * $reportes_por_pagina;
$total_paginas = ceil($total_reportes / $reportes_por_pagina);

$query = "
    SELECT 
        r.id, 
        r.titulo, 
        r.fecha_creacion, 
        r.fecha_actualizacion,
        e.nombre as estado_nombre, 
        e.color as estado_color,
        t.nombre as tipo_nombre,
        a.nombre as area_nombre
    FROM 
        reportes r
        JOIN estados_reporte e ON r.estado_id = e.id
        JOIN tipos_reporte t ON r.tipo_id = t.id
        LEFT JOIN public.subdivisiones_area a ON r.area_id = a.area_id AND r.data_id = a.data_id
    $where_clause
    ORDER BY 
        r.fecha_actualizacion DESC NULLS LAST, 
        r.fecha_creacion DESC
    LIMIT $reportes_por_pagina OFFSET $offset
";

$reportes = [];
$result = pg_query_params($conexion, $query, $params);
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $reportes[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reportes</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="../../css/reportes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php include '../../includes/empleado_sidebar.php'; ?>
        
        <div class="main-content">
            <?php if ($mensaje): ?>
            <div class="mensaje-alerta <?php echo $mensaje['tipo']; ?>">
                <i class="fas fa-<?php echo $mensaje['tipo'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($mensaje['texto']); ?></span>
                <button class="cerrar-alerta">&times;</button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($errores)): ?>
            <div class="mensaje-alerta error">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="lista-errores">
                    <strong>Se encontraron los siguientes errores:</strong>
                    <ul>
                        <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <button class="cerrar-alerta">&times;</button>
            </div>
            <?php endif; ?>
            
            <h1 class="page-title">Gestión de Reportes</h1>
            
            <div class="actions-bar">
                <button id="btnNuevoReporte" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Reporte
                </button>
            </div>

            <div class="filter-section">
                <h3>Filtros</h3>
                <form id="filterForm" action="gestionar.php" method="GET">
                    <div class="filter-grid">
                        <div class="filter-item">
                            <label for="estado">Estado:</label>
                            <select name="estado" id="estado">
                                <option value="0">Todos</option>
                                <?php foreach ($estados as $estado): ?>
                                <option value="<?php echo $estado['id']; ?>" <?php echo ($filtro_estado == $estado['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($estado['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="tipo">Tipo:</label>
                            <select name="tipo" id="tipo">
                                <option value="0">Todos</option>
                                <?php foreach ($tipos as $tipo): ?>
                                <option value="<?php echo $tipo['id']; ?>" <?php echo ($filtro_tipo == $tipo['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tipo['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="area">Área:</label>
                            <select name="area" id="area">
                                <option value="0">Todas</option>
                                <?php foreach ($areas as $area): ?>
                                <option value="<?php echo $area['area_id']; ?>" <?php echo ($filtro_area == $area['area_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($area['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-item">
                            <label for="fecha">Fecha:</label>
                            <input type="date" name="fecha" id="fecha" value="<?php echo $filtro_fecha; ?>">
                        </div>
                        
                        <div class="filter-item">
                            <label for="texto">Búsqueda:</label>
                            <input type="text" name="texto" id="texto" placeholder="Título o descripción" value="<?php echo htmlspecialchars($filtro_texto); ?>">
                        </div>
                    </div>
                    
                    <div class="filter-buttons">
                        <button type="submit" class="btn btn-search">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <button type="button" id="btnLimpiarFiltros" class="btn btn-clear">
                            <i class="fas fa-eraser"></i> Limpiar
                        </button>
                    </div>
                </form>
            </div>

            <div class="reportes-list">
                <?php if (empty($reportes)): ?>
                <div class="no-results">
                    <i class="fas fa-info-circle"></i>
                    <p>No se encontraron reportes con los criterios seleccionados</p>
                </div>
                <?php else: ?>
                <table class="reportes-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Área</th>
                            <th>Fecha Creación</th>
                            <th>Última Actualización</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportes as $reporte): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reporte['id']); ?></td>
                            <td class="report-title"><?php echo htmlspecialchars($reporte['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($reporte['tipo_nombre']); ?></td>
                            <td>
                                <span class="status-badge" style="background-color: <?php echo $reporte['estado_color']; ?>">
                                    <?php echo htmlspecialchars($reporte['estado_nombre']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($reporte['area_nombre'] ?? 'N/A'); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($reporte['fecha_creacion'])); ?></td>
                            <td>
                                <?php 
                                echo !empty($reporte['fecha_actualizacion']) 
                                    ? date('d/m/Y H:i', strtotime($reporte['fecha_actualizacion'])) 
                                    : 'Sin actualizar';
                                ?>
                            </td>
                            <td class="actions">
                                <a href="ver_reporte.php?id=<?php echo $reporte['id']; ?>" class="action-btn view-btn" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="editar_reporte.php?id=<?php echo $reporte['id']; ?>" class="action-btn edit-btn" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_paginas > 1): ?>
                <div class="pagination">
                    <?php if ($pagina_actual > 1): ?>
                    <a href="?pagina=<?php echo $pagina_actual - 1; ?>&estado=<?php echo $filtro_estado; ?>&tipo=<?php echo $filtro_tipo; ?>&area=<?php echo $filtro_area; ?>&fecha=<?php echo $filtro_fecha; ?>&texto=<?php echo urlencode($filtro_texto); ?>" class="page-btn">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                    <?php endif; ?>
                    
                    <span class="page-info">Página <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
                    
                    <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="?pagina=<?php echo $pagina_actual + 1; ?>&estado=<?php echo $filtro_estado; ?>&tipo=<?php echo $filtro_tipo; ?>&area=<?php echo $filtro_area; ?>&fecha=<?php echo $filtro_fecha; ?>&texto=<?php echo urlencode($filtro_texto); ?>" class="page-btn">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php endif; ?>
            </div>
        </div>
    </div>


    <div id="reporteModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2>Nuevo Reporte</h2>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="reporteForm" action="guardar_reporte.php" method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="titulo">Título *</label>
                            <input type="text" id="titulo" name="titulo" required maxlength="200" value="<?php echo htmlspecialchars($form_data['titulo'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="tipo_id">Tipo de Reporte *</label>
                            <select id="tipo_id" name="tipo_id" required>
                                <option value="">Seleccione un tipo</option>
                                <?php foreach ($tipos as $tipo): ?>
                                <option value="<?php echo $tipo['id']; ?>" <?php echo (isset($form_data['tipo_id']) && $form_data['tipo_id'] == $tipo['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tipo['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="descripcion">Descripción *</label>
                            <textarea id="descripcion" name="descripcion" rows="5" required><?php echo htmlspecialchars($form_data['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="area_id">Área (Opcional)</label>
                            <select id="area_id" name="area_id">
                                <option value="">Ninguna</option>
                                <?php foreach ($areas as $area): ?>
                                <option value="<?php echo $area['area_id']; ?>" data-data-id="<?php echo htmlspecialchars($area['data_id']); ?>" <?php echo (isset($form_data['area_id']) && $form_data['area_id'] == $area['area_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($area['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="data_id" name="data_id" value="<?php echo htmlspecialchars($form_data['data_id'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="imagenes">Imágenes (opcional)</label>
                            <div class="file-upload">
                                <input type="file" id="imagenes" name="imagenes[]" multiple accept="image/*">
                                <label for="imagenes" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i> Seleccionar imágenes
                                </label>
                                <div id="file-list" class="file-list"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-submit">
                            <i class="fas fa-save"></i> Guardar Reporte
                        </button>
                        <button type="button" class="btn btn-cancel modal-cancel">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const botonesAlerta = document.querySelectorAll('.cerrar-alerta');
        botonesAlerta.forEach(boton => {
            boton.addEventListener('click', function() {
                const alerta = this.closest('.mensaje-alerta');
                alerta.style.opacity = '0';
                setTimeout(() => {
                    alerta.style.display = 'none';
                }, 300);
            });
        });
        
        const alertasExito = document.querySelectorAll('.mensaje-alerta.success');
        if (alertasExito.length > 0) {
            setTimeout(() => {
                alertasExito.forEach(alerta => {
                    alerta.style.opacity = '0';
                    setTimeout(() => {
                        alerta.style.display = 'none';
                    }, 300);
                });
            }, 5000);
        }
        
        const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');
        if (btnLimpiarFiltros) {
            btnLimpiarFiltros.addEventListener('click', function() {
                document.getElementById('estado').value = "0";
                document.getElementById('tipo').value = "0";
                document.getElementById('area').value = "0";
                document.getElementById('fecha').value = "";
                document.getElementById('texto').value = "";
                document.getElementById('filterForm').submit();
            });
        }
       
        const modal = document.getElementById('reporteModal');
        const btnNuevoReporte = document.getElementById('btnNuevoReporte');
        const closeBtn = document.querySelector('.modal-close');
        const cancelBtn = document.querySelector('.modal-cancel');
        
        btnNuevoReporte.addEventListener('click', function() {
            modal.style.display = 'flex';
        });
        
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        const areaSelect = document.getElementById('area_id');
        const dataIdInput = document.getElementById('data_id');
        
        areaSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                dataIdInput.value = selectedOption.getAttribute('data-data-id');
            } else {
                dataIdInput.value = '';
            }
        });
        
        const inputImagenes = document.getElementById('imagenes');
        const fileList = document.getElementById('file-list');
        
        inputImagenes.addEventListener('change', function() {
            fileList.innerHTML = '';
            
            if (this.files.length > 0) {
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item';
                    
                    const fileName = document.createElement('span');
                    fileName.className = 'file-name';
                    fileName.textContent = file.name;
                    
                    const fileSize = document.createElement('span');
                    fileSize.className = 'file-size';
                    fileSize.textContent = formatFileSize(file.size);
                    
                    const fileIcon = document.createElement('i');
                    fileIcon.className = 'fas fa-file-image';
                    
                    fileItem.appendChild(fileIcon);
                    fileItem.appendChild(fileName);
                    fileItem.appendChild(fileSize);
                    
                    fileList.appendChild(fileItem);
                }
            }
        });
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
  
        const reporteForm = document.getElementById('reporteForm');
        
        reporteForm.addEventListener('submit', function(event) {
            let hasError = false;
         
            const titulo = document.getElementById('titulo');
            if (titulo.value.trim() === '') {
                markInvalid(titulo, 'El título es obligatorio');
                hasError = true;
            } else {
                markValid(titulo);
            }
       
            const tipo = document.getElementById('tipo_id');
            if (tipo.value === '') {
                markInvalid(tipo, 'Debe seleccionar un tipo de reporte');
                hasError = true;
            } else {
                markValid(tipo);
            }
            
         
            const descripcion = document.getElementById('descripcion');
            if (descripcion.value.trim() === '') {
                markInvalid(descripcion, 'La descripción es obligatoria');
                hasError = true;
            } else {
                markValid(descripcion);
            }
            
            if (hasError) {
                event.preventDefault();
            }
        });
        
        function markInvalid(element, message) {
            element.classList.add('invalid');
          
            let errorMessage = element.parentElement.querySelector('.error-message');
            
            if (!errorMessage) {
                errorMessage = document.createElement('div');
                errorMessage.className = 'error-message';
                element.parentElement.appendChild(errorMessage);
            }
            
            errorMessage.textContent = message;
        }
        
        function markValid(element) {
            element.classList.remove('invalid');
            
            const errorMessage = element.parentElement.querySelector('.error-message');
            if (errorMessage) {
                errorMessage.remove();
            }
        }
    });
    </script>
</body>
</html> 