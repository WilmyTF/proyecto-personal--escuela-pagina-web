<?php

session_start();


if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'estudiante') {
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Reporte</title>
    <link rel="stylesheet" href="../../css/estudiante_dashboard.css">
    <link rel="stylesheet" href="../../css/estudiante_sidebar.css">
    <link rel="stylesheet" href="../../css/reportes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php include '../../includes/estudiante_sidebar.php'; ?>
        
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
            
            <div class="page-header">
                <h1 class="page-title">Nuevo Reporte</h1>
                <a href="estudiante_gestionar.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver a mis reportes
                </a>
            </div>

            <div class="form-container">
                <form id="reporteForm" action="estudiante_guardar_reporte.php" method="POST" enctype="multipart/form-data">
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
                        
                        <div class="form-group">
                            <label for="area_id">Área (opcional)</label>
                            <select id="area_id" name="area_id">
                                <option value="">Seleccione un área</option>
                                <?php foreach ($areas as $area): ?>
                                <option value="<?php echo $area['area_id']; ?>" data-data-id="<?php echo htmlspecialchars($area['data_id']); ?>" <?php echo (isset($form_data['area_id']) && $form_data['area_id'] == $area['area_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($area['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="data_id" name="data_id" value="<?php echo htmlspecialchars($form_data['data_id'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="descripcion">Descripción *</label>
                            <textarea id="descripcion" name="descripcion" rows="5" required><?php echo htmlspecialchars($form_data['descripcion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="imagenes">Imágenes (opcional)</label>
                            <div class="file-upload">
                                <input type="file" id="imagenes" name="imagenes[]" accept="image/*" multiple>
                                <label for="imagenes" class="btn btn-secondary">
                                    <i class="fas fa-upload"></i> Seleccionar imágenes
                                </label>
                            </div>
                            <div id="file-list" class="file-list"></div>
                            <small class="form-text text-muted">Puede seleccionar múltiples imágenes. Formatos permitidos: JPG, PNG, GIF. Tamaño máximo por imagen: 5MB.</small>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-cancel" onclick="window.location.href='estudiante_gestionar.php'">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-submit">
                            <i class="fas fa-save"></i> Crear Reporte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    
    document.querySelectorAll('.cerrar-alerta').forEach(function(button) {
        button.addEventListener('click', function() {
            this.closest('.mensaje-alerta').remove();
        });
    });


    const sidebarToggle = document.getElementById('sidebarToggle');
    const container = document.querySelector('.container');
    const sidebar = document.querySelector('.sidebar');

    sidebarToggle.addEventListener('click', () => {
        container.classList.toggle('sidebar-collapsed');
        
      
        const isCollapsed = container.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    });

 
    window.addEventListener('load', () => {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            container.classList.add('sidebar-collapsed');
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
      // Previsualización de imágenes seleccionadas
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
    </script>
</body>
</html> 