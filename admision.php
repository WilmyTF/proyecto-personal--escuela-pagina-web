<?php include 'includes/conexion.php'; ?>
<?php include 'includes/header.php'; ?>

<link rel="stylesheet" href="css/admision.css">

<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="text-center mb-0">Formulario de Admisión</h3>
        </div>
        <div class="card-body">
            <form id="formAdmision" action="procesar_admision.php" method="POST" enctype="multipart/form-data">
                <!-- Indicador de pasos -->
                <div class="progress mb-4">
                    <div class="progress-bar" role="progressbar" style="width: 33%" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <!-- Paso 1: Datos del Estudiante -->
                <div class="step" id="step1">
                    <h4>Datos del Estudiante</h4>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="nombre">Nombre del Estudiante:</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="apellido">Apellido del Estudiante:</label>
                            <input type="text" class="form-control" name="apellido" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="direccion">Dirección del Estudiante:</label>
                            <input type="text" class="form-control" name="direccion" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="grado">Grado a Cursar:</label>
                            <select class="form-control" name="grado" required>
                                <option value="">Seleccione un grado</option>
                                <option value="1">Primer Grado</option>
                                <option value="2">Segundo Grado</option>
                                <option value="3">Tercer Grado</option>
                                <option value="4">Cuarto Grado</option>
                                <option value="5">Quinto Grado</option>
                                <option value="6">Sexto Grado</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="tecnico">Técnico:</label>
                            <select class="form-control" name="tecnico" required>
                                <option value="">Seleccione un técnico</option>
                                <option value="artes">Artes</option>
                                <option value="contabilidad">Contabilidad</option>
                                <option value="informatica">Informática</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Paso 2: Datos de los Padres/Tutores -->
                <div class="step" id="step2" style="display: none;">
                    <div class="tutor-section mb-4">
                        <h4>1er Padre o Tutor</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Nombre del Padre:</label>
                                <input type="text" class="form-control" name="nombre_padre1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Apellido del Padre:</label>
                                <input type="text" class="form-control" name="apellido_padre1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Número de Teléfono:</label>
                                <input type="tel" class="form-control" name="telefono_padre1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Dirección del Padre:</label>
                                <input type="text" class="form-control" name="direccion_padre1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Correo del Padre:</label>
                                <input type="email" class="form-control" name="correo_padre1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Relación:</label>
                                <select class="form-control" name="relacion_padre1" required>
                                    <option value="">Seleccione relación</option>
                                    <option value="padre">Padre</option>
                                    <option value="madre">Madre</option>
                                    <option value="tutor">Tutor Legal</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="tutor-section">
                        <h4>2do Padre o Tutor (Opcional)</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Nombre del Padre:</label>
                                <input type="text" class="form-control" name="nombre_padre2">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Apellido del Padre:</label>
                                <input type="text" class="form-control" name="apellido_padre2">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Número de Teléfono:</label>
                                <input type="tel" class="form-control" name="telefono_padre2">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Dirección del Padre:</label>
                                <input type="text" class="form-control" name="direccion_padre2">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Correo del Padre:</label>
                                <input type="email" class="form-control" name="correo_padre2">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Relación:</label>
                                <select class="form-control" name="relacion_padre2">
                                    <option value="">Seleccione relación</option>
                                    <option value="padre">Padre</option>
                                    <option value="madre">Madre</option>
                                    <option value="tutor">Tutor Legal</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paso 3: Documentos -->
                <div class="step" id="step3" style="display: none;">
                    <div class="row mb-4">
                        <div class="col-12">
                            <button class="btn btn-info" type="button" onclick="toggleDocumentosList()">
                                <i class="fas fa-list"></i> Ver Documentos Requeridos
                            </button>
                            <div id="documentosList" class="card mt-2" style="display: none;">
                                <div class="card-body">
                                    <h6 class="card-title">Documentos Necesarios:</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">• Acta de Nacimiento - Documento oficial que certifica el nacimiento</li>
                                        <li class="list-group-item">• Cédula - Documento de identidad</li>
                                        <li class="list-group-item">• Record de Notas - Historial académico del estudiante</li>
                                        <li class="list-group-item">• Foto 2x2 - Fotografía reciente tamaño 2x2</li>
                                        <li class="list-group-item">• Certificado de Buena Conducta - Certificado de comportamiento de la escuela anterior</li>
                                        <li class="list-group-item">• Certificado Médico - Certificado de salud actual</li>
                                        <li class="list-group-item">• Tipificación Sanguínea - Documento que indica el tipo de sangre</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Subir Documentos</h5>
                            <p class="card-text text-muted mb-4">
                                Seleccione los archivos que desea subir.
                                Se aceptan archivos PDF, JPG y PNG (máx. 5MB por archivo)
                            </p>
                            
                            <div class="mb-3">
                                <label for="documento" class="form-label">Archivos</label>
                                <input type="file" class="form-control" id="documento" accept=".pdf,.jpg,.jpeg,.png" multiple onchange="mostrarArchivosSeleccionados(this)">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de documentos seleccionados -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5>Documentos Seleccionados</h5>
                            <button type="button" class="btn btn-primary" onclick="subirDocumentos()" id="btnSubir" disabled>
                                <i class="fas fa-upload"></i> Subir Documentos
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover" id="documentosSubidos">
                                <thead>
                                    <tr>
                                        <th>Nombre del Archivo</th>
                                        <th>Tamaño</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Los documentos se agregarán aquí dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Botones de navegación -->
                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-secondary" id="prevBtn" onclick="navegarPaso(-1)" style="display: none;">Anterior</button>
                    <button type="button" class="btn btn-primary" id="nextBtn" onclick="navegarPaso(1)">Siguiente</button>
                    <button type="submit" class="btn btn-success" id="submitBtn" style="display: none;">Enviar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let pasoActual = 1;
const totalPasos = 3;

// Mantener un registro de los archivos seleccionados
let archivosSeleccionados = new Map();

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function mostrarArchivosSeleccionados(input) {
    const files = input.files;
    const tabla = document.getElementById('documentosSubidos').getElementsByTagName('tbody')[0];
    const btnSubir = document.getElementById('btnSubir');
    
    for (let file of files) {
        if (!validateFile(file)) continue;
        
        // Generar ID único para el archivo
        const fileId = 'file-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        archivosSeleccionados.set(fileId, file);
        
        const newRow = tabla.insertRow();
        newRow.id = fileId;
        newRow.innerHTML = `
            <td>${file.name}</td>
            <td>${formatBytes(file.size)}</td>
            <td><span class="badge bg-secondary">Pendiente</span></td>
            <td>
                <button class="btn btn-danger btn-sm" onclick="eliminarArchivo('${fileId}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
    }
    
    // Habilitar botón de subida si hay archivos
    btnSubir.disabled = archivosSeleccionados.size === 0;
    
    // Limpiar input para permitir seleccionar el mismo archivo nuevamente
    input.value = '';
}

function eliminarArchivo(fileId) {
    const row = document.getElementById(fileId);
    if (row) {
        row.remove();
        archivosSeleccionados.delete(fileId);
        
        // Deshabilitar botón de subida si no hay archivos
        const btnSubir = document.getElementById('btnSubir');
        btnSubir.disabled = archivosSeleccionados.size === 0;
    }
}

async function subirDocumentos() {
    if (archivosSeleccionados.size === 0) return;
    
    const btnSubir = document.getElementById('btnSubir');
    btnSubir.disabled = true;
    btnSubir.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Subiendo...';
    
    try {
        for (const [fileId, file] of archivosSeleccionados) {
            const row = document.getElementById(fileId);
            const statusBadge = row.querySelector('.badge');
            const deleteBtn = row.querySelector('.btn-danger');
            
            // Actualizar estado a "Subiendo"
            statusBadge.className = 'badge bg-info';
            statusBadge.textContent = 'Subiendo...';
            deleteBtn.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('documento', file);
                
                const response = await fetch('form/procesar_documento.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    statusBadge.className = 'badge bg-success';
                    statusBadge.textContent = 'Completado';
                } else {
                    throw new Error(data.message || 'Error al procesar el documento');
                }
            } catch (error) {
                console.error('Error:', error);
                statusBadge.className = 'badge bg-danger';
                statusBadge.textContent = 'Error';
                deleteBtn.disabled = false;
                alert(`Error al subir ${file.name}: ${error.message}`);
            }
        }
    } finally {
        btnSubir.disabled = false;
        btnSubir.innerHTML = '<i class="fas fa-upload"></i> Subir Documentos';
    }
}

function validateFile(file) {
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];

    if (file.size > maxSize) {
        alert(`El archivo ${file.name} es demasiado grande. El tamaño máximo es 5MB.`);
        return false;
    }

    if (!allowedTypes.includes(file.type)) {
        alert(`El archivo ${file.name} no es un tipo permitido. Solo se aceptan PDF, JPG y PNG.`);
        return false;
    }

    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    // Asegurarnos de que los elementos existen antes de agregar eventos
    const form = document.getElementById('formAdmision');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const documentosContainer = document.getElementById('documentos-container');

    if (form) {
        form.addEventListener('submit', function(e) {
            if (pasoActual !== totalPasos) {
                e.preventDefault();
            }
        });
    }

    // Inicializar el primer paso
    mostrarPaso(1);
});

function mostrarPaso(paso) {
    const pasos = document.getElementsByClassName('step');
    // Ocultar todos los pasos
    for (let i = 0; i < pasos.length; i++) {
        pasos[i].style.display = 'none';
    }
    // Mostrar el paso actual
    if (pasos[paso - 1]) {
        pasos[paso - 1].style.display = 'block';
    }
    
    // Actualizar barra de progreso
    const progreso = (paso / totalPasos) * 100;
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.style.width = progreso + '%';
        progressBar.setAttribute('aria-valuenow', progreso);
    }
}

function navegarPaso(n) {
    const pasos = document.getElementsByClassName('step');
    if (!pasos.length) return;
    
    // Ocultar paso actual
    if (pasos[pasoActual-1]) {
        pasos[pasoActual-1].style.display = 'none';
    }
    
    // Calcular nuevo paso
    pasoActual += n;
    
    // Si llegamos al paso de documentos, cargar la interfaz
    if (pasoActual === 3 && document.getElementById('documentos-container')) {
        cargarInterfazDocumentos();
    }
    
    // Actualizar botones
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    if (prevBtn) prevBtn.style.display = pasoActual == 1 ? 'none' : 'block';
    if (nextBtn) nextBtn.style.display = pasoActual == totalPasos ? 'none' : 'block';
    if (submitBtn) submitBtn.style.display = pasoActual == totalPasos ? 'block' : 'none';
    
    // Mostrar paso actual
    mostrarPaso(pasoActual);
}

function cargarInterfazDocumentos() {
    fetch('form/documentos_admision.php')  // Volvemos a la ruta original
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error del servidor: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            if (!html.trim()) {
                throw new Error('La respuesta está vacía');
            }
            
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            const contenido = doc.querySelector('.documento-upload-container') || 
                            doc.querySelector('.documento-lista') ||
                            doc.querySelector('.table-responsive');
            
            const documentosContainer = document.getElementById('documentos-container');
            if (contenido && documentosContainer) {
                documentosContainer.innerHTML = contenido.innerHTML;
            } else {
                throw new Error('No se encontró el contenido de documentos en la respuesta');
            }
        })
        .catch(error => {
            console.error('Error al cargar la interfaz de documentos:', error);
            const documentosContainer = document.getElementById('documentos-container');
            if (documentosContainer) {
                documentosContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Error al cargar la interfaz de documentos: ${error.message}
                    </div>`;
            }
        });
}

function toggleDocumentosList() {
    const lista = document.getElementById('documentosList');
    if (lista.style.display === 'none') {
        lista.style.display = 'block';
    } else {
        lista.style.display = 'none';
    }
}
</script>

//?php include 'includes/footer.php'; ?> 