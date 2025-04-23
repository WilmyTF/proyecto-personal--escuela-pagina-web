<?php
include '../includes/conexion.php';

if (!$conn) {
    die('Error de conexión: ' . pg_last_error());
}

// Obtener documentos requeridos activos
$query = "SELECT * FROM documentos_requerido_admision WHERE activo = true ORDER BY nombre";
$result = pg_query($conn, $query);

if (!$result) {
    die('Error en la consulta: ' . pg_last_error($conn));
}

$documentos_requeridos = pg_fetch_all($result);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos Requeridos</title>
    <link rel="stylesheet" href="../css/documentos.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-primary mb-4">Documentos Solicitados</h2>
        
        <!-- Agregar div para mensajes de estado global -->
        <div id="status-message" class="alert" style="display: none;"></div>
        
        <div class="documento-upload-container">
            <div class="documento-lista">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documentos_requeridos as $doc): ?>
                            <tr id="doc-row-<?php echo $doc['id_documento_requerido']; ?>">
                                <td><?php echo htmlspecialchars($doc['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($doc['descripcion']); ?></td>
                                <td>
                                    <span class="badge bg-warning">Pendiente</span>
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm upload-btn" 
                                            onclick="seleccionarArchivo(<?php echo $doc['id_documento_requerido']; ?>)">
                                        <i class="fas fa-upload"></i> Subir
                                    </button>
                                    <input type="file" 
                                           id="file-<?php echo $doc['id_documento_requerido']; ?>" 
                                           style="display: none;"
                                           onchange="subirDocumento(this, <?php echo $doc['id_documento_requerido']; ?>)">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    const ALLOWED_FILE_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];
    const MAX_FILE_SIZE = 20 * 1024 * 1024; // 20MB

    function mostrarMensaje(mensaje, tipo) {
        const statusDiv = document.getElementById('status-message');
        statusDiv.className = `alert alert-${tipo}`;
        statusDiv.textContent = mensaje;
        statusDiv.style.display = 'block';
        
        setTimeout(() => {
            statusDiv.style.display = 'none';
        }, 5000);
    }

    function validarArchivo(file) {
        if (!file) {
            throw new Error('Por favor seleccione un archivo.');
        }
        
        if (!ALLOWED_FILE_TYPES.includes(file.type)) {
            throw new Error('Tipo de archivo no permitido. Solo se aceptan PDF, JPG y PNG.');
        }
        
        if (file.size > MAX_FILE_SIZE) {
            throw new Error('El archivo es demasiado grande. El tamaño máximo es 20MB.');
        }
        
        return true;
    }

    function seleccionarArchivo(idDocumento) {
        document.getElementById('file-' + idDocumento).click();
    }

    function subirDocumento(input, idDocumento) {
        try {
            if (!input.files || !input.files[0]) return;
            
            validarArchivo(input.files[0]);

            const formData = new FormData();
            formData.append('documento', input.files[0]);
            formData.append('id_documento_requerido', idDocumento);

            const row = document.getElementById('doc-row-' + idDocumento);
            const statusBadge = row.querySelector('.badge');
            const uploadBtn = row.querySelector('.upload-btn');

            // Deshabilitar botón y mostrar estado de carga
            uploadBtn.disabled = true;
            statusBadge.className = 'badge bg-info';
            statusBadge.textContent = 'Subiendo...';

            fetch('procesar_documento.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error del servidor: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    statusBadge.className = 'badge bg-success';
                    statusBadge.textContent = 'Completado';
                    uploadBtn.innerHTML = '<i class="fas fa-check"></i> Actualizar';
                    mostrarMensaje('Documento subido exitosamente', 'success');
                } else {
                    throw new Error(data.message || 'Error al procesar el documento');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                statusBadge.className = 'badge bg-danger';
                statusBadge.textContent = 'Error';
                mostrarMensaje(error.message || 'Error al subir el documento', 'danger');
            })
            .finally(() => {
                uploadBtn.disabled = false;
                input.value = ''; // Limpiar input
            });
        } catch (error) {
            mostrarMensaje(error.message, 'warning');
            input.value = ''; // Limpiar input en caso de error de validación
        }
    }
    </script>
    
    <!-- Agregar Font Awesome para los iconos -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html> 