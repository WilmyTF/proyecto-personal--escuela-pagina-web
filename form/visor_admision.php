<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/conexion.php';
verificarConexion();

// Obtener filtros
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$grado = isset($_GET['grado']) ? $_GET['grado'] : '';
$especialidad = isset($_GET['especialidad']) ? $_GET['especialidad'] : '';

// Construir consulta base
$query = "SELECT * FROM solicitud_admision WHERE 1=1";
$params = [];

if ($estado) {
    $query .= " AND estado = ?";
    $params[] = $estado;
}
if ($grado) {
    $query .= " AND grado_cursar = ?";
    $params[] = $grado;
}
if ($especialidad) {
    $query .= " AND especialidad = ?";
    $params[] = $especialidad;
}

$query .= " ORDER BY fecha_solicitud DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Definir valores fijos para los filtros
$grados = ['1ro', '2do', '3ro', '4to', '5to', '6to'];
$especialidades = ['Arte', 'Contabilidad', 'Informática'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Solicitudes de Admisión</title>
    <link rel="stylesheet" href="../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .filtros-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
        .filtro-item select, .filtro-item input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .solicitudes-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .solicitud-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .solicitud-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .solicitud-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        .estado-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .estado-pendiente { background-color: #fff3cd; color: #856404; }
        .estado-aprobada { background-color: #d4edda; color: #155724; }
        .estado-rechazada { background-color: #f8d7da; color: #721c24; }

        /* Estilos funcionales del sidebar */
        .container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            transition: width 0.3s;
        }
        .sidebar-collapsed .sidebar {
            width: 60px;
        }
        .main-content {
            flex: 1;
            padding: 20px;
        }
        .sidebar-toggle {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }

        /* Estilos para el modal de documentos */
        .documentos-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            width: 80%;
            max-width: 800px;
            border-radius: 8px;
            position: relative;
        }
        .close-modal {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
        }
        .documentos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .documento-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: transform 0.2s;
        }
        .documento-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .documento-item img {
            max-width: 150px;
            max-height: 150px;
            object-fit: cover;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .documento-item p {
            margin: 10px 0;
            font-weight: 500;
            color: #2c3e50;
        }
        .btn-ver-doc {
            display: inline-block;
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .btn-ver-doc:hover {
            background-color: #2980b9;
        }
        .no-documentos, .error-documentos {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 10px 0;
        }
        .error-documentos {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .solicitud-card {
            cursor: pointer;
            transition: transform 0.2s;
        }
        .solicitud-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* Estilos para las pestañas del modal */
        .modal-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            color: #666;
        }
        .tab-btn.active {
            color: #3498db;
            border-bottom: 2px solid #3498db;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .datos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .datos-seccion {
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
        }
        .datos-seccion h3 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        /* Estilos para el botón rechazar */
        .acciones-solicitud {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }
        .btn-rechazar {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-rechazar:hover {
            background-color: #c0392b;
        }

        .estado-mensaje {
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
            color: #6c757d;
            font-style: italic;
            text-align: center;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php include '../includes/empleado_sidebar.php'; ?>
        
        <div class="main-content">
            <h1>Visor de Solicitudes de Admisión</h1>
            
            <div class="filtros-container">
                <form method="GET" action="">
                    <div class="filtros-grid">
                        <div class="filtro-item">
                            <label for="estado">Estado:</label>
                            <select name="estado" id="estado">
                                <option value="">Todos</option>
                                <option value="pendiente" <?php echo $estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="aprobada" <?php echo $estado === 'aprobada' ? 'selected' : ''; ?>>Aprobada</option>
                                <option value="rechazada" <?php echo $estado === 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                            </select>
                        </div>
                        
                        <div class="filtro-item">
                            <label for="grado">Grado:</label>
                            <select name="grado" id="grado">
                                <option value="">Todos</option>
                                <?php foreach ($grados as $g): ?>
                                    <option value="<?php echo htmlspecialchars($g); ?>" <?php echo $grado === $g ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($g); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filtro-item">
                            <label for="especialidad">Especialidad:</label>
                            <select name="especialidad" id="especialidad">
                                <option value="">Todas</option>
                                <?php foreach ($especialidades as $e): ?>
                                    <option value="<?php echo htmlspecialchars($e); ?>" <?php echo $especialidad === $e ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($e); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" style="margin-top: 15px; padding: 8px 15px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        Aplicar Filtros
                    </button>
                </form>
            </div>

            <div class="solicitudes-container">
                <?php if (empty($solicitudes)): ?>
                    <p>No se encontraron solicitudes con los filtros seleccionados.</p>
                <?php else: ?>
                    <?php foreach ($solicitudes as $solicitud): ?>
                        <div class="solicitud-card" data-solicitud-id="<?php echo htmlspecialchars($solicitud['id_solicitud'] ?? ''); ?>">
                            <div class="solicitud-header">
                                <h3><?php echo htmlspecialchars(($solicitud['nombre_estudiante'] ?? '') . ' ' . ($solicitud['apellido_estudiante'] ?? '')); ?></h3>
                                <span class="estado-badge estado-<?php echo $solicitud['estado'] ?? 'pendiente'; ?>">
                                    <?php echo ucfirst($solicitud['estado'] ?? 'pendiente'); ?>
                                </span>
                            </div>
                            <div class="solicitud-info">
                                <div>
                                    <strong>Grado:</strong> <?php echo htmlspecialchars($solicitud['grado_cursar'] ?? 'No especificado'); ?>
                                </div>
                                <div>
                                    <strong>Especialidad:</strong> <?php echo htmlspecialchars($solicitud['especialidad'] ?? 'No especificada'); ?>
                                </div>
                                <div>
                                    <strong>Dirección:</strong> <?php echo htmlspecialchars($solicitud['direccion_estudiante'] ?? 'No especificada'); ?>
                                </div>
                                <div>
                                    <strong>Fecha de Solicitud:</strong> <?php echo $solicitud['fecha_solicitud'] ? date('d/m/Y', strtotime($solicitud['fecha_solicitud'])) : 'No especificada'; ?>
                                </div>
                                <div>
                                    <strong>Tutor Principal:</strong> <?php echo htmlspecialchars(($solicitud['nombre_tutor1'] ?? '') . ' ' . ($solicitud['apellido_tutor1'] ?? '')); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para documentos -->
    <div id="documentosModal" class="documentos-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-tabs">
                <button class="tab-btn active" data-tab="datos">Datos Completos</button>
                <button class="tab-btn" data-tab="documentos">Documentos</button>
            </div>
            
            <div id="datosTab" class="tab-content active">
                <h2>Datos del Estudiante</h2>
                <div id="datosEstudiante" class="datos-grid">
                    <!-- Los datos se cargarán aquí dinámicamente -->
                </div>
                <div class="acciones-solicitud">
                    <button id="btnRechazar" class="btn-rechazar">Rechazar</button>
                </div>
            </div>
            
            <div id="documentosTab" class="tab-content">
                <h2>Documentos de la Solicitud</h2>
                <div id="documentosContainer" class="documentos-grid">
                    <!-- Los documentos se cargarán aquí dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const container = document.querySelector('.container');
        const sidebar = document.querySelector('.sidebar');

        sidebarToggle.addEventListener('click', () => {
            container.classList.toggle('sidebar-collapsed');
            const isCollapsed = container.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });

        // Restaurar estado del sidebar
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            container.classList.add('sidebar-collapsed');
        }

        // Manejar secciones del menú
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

        // Funcionalidad para el modal de documentos
        const modal = document.getElementById('documentosModal');
        const closeModal = document.querySelector('.close-modal');
        const documentosContainer = document.getElementById('documentosContainer');
        const datosEstudiante = document.getElementById('datosEstudiante');
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        // Manejar cambios de pestaña
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const tabTarget = this.dataset.tab;
                
                // Desactivar todas las pestañas
                tabBtns.forEach(tb => tb.classList.remove('active'));
                tabContents.forEach(tc => tc.classList.remove('active'));
                
                // Activar la pestaña seleccionada
                this.classList.add('active');
                document.getElementById(tabTarget + 'Tab').classList.add('active');
            });
        });

        // Agregar evento al botón rechazar
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'btnRechazar') {
                const solicitudId = e.target.getAttribute('data-solicitud-id');
                if (solicitudId) {
                    if (confirm('¿Está seguro que desea rechazar esta solicitud?')) {
                        rechazarSolicitud(solicitudId);
                    }
                }
            }
        });

        // Función para rechazar solicitud
        async function rechazarSolicitud(solicitudId) {
            try {
                const response = await fetch('../api/rechazar_solicitud.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ solicitud_id: solicitudId })
                });
                
                const resultado = await response.json();
                
                if (resultado.success) {
                    alert('Solicitud rechazada correctamente');
                    
                    // Actualizar el estado en la tarjeta de solicitud
                    const solicitudCard = document.querySelector(`.solicitud-card[data-solicitud-id="${solicitudId}"]`);
                    if (solicitudCard) {
                        const estadoBadge = solicitudCard.querySelector('.estado-badge');
                        if (estadoBadge) {
                            estadoBadge.className = 'estado-badge estado-rechazada';
                            estadoBadge.textContent = 'Rechazada';
                        }
                    }
                    
                    // Cerrar el modal
                    modal.style.display = 'none';
                } else {
                    alert('Error al rechazar la solicitud: ' + (resultado.message || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            }
        }

        // Función para cargar datos completos y documentos
        async function cargarDatosSolicitud(solicitudId) {
            try {
                // Cargar datos del estudiante
                const responseDatos = await fetch(`../api/get_solicitud.php?solicitud_id=${solicitudId}`);
                const datosSolicitud = await responseDatos.json();
                
                if (datosSolicitud.error) {
                    console.error('Error:', datosSolicitud.error);
                    alert('Error al cargar datos de la solicitud');
                    return;
                }
                
                // Mostrar datos completos
                datosEstudiante.innerHTML = `
                    <div class="datos-seccion">
                        <h3>Datos del Estudiante</h3>
                        <p><strong>Nombre:</strong> ${datosSolicitud.nombre_estudiante || ''} ${datosSolicitud.apellido_estudiante || ''}</p>
                        <p><strong>Dirección:</strong> ${datosSolicitud.direccion_estudiante || 'No especificada'}</p>
                        <p><strong>Grado a Cursar:</strong> ${datosSolicitud.grado_cursar || 'No especificado'}</p>
                        <p><strong>Especialidad:</strong> ${datosSolicitud.especialidad || 'No especificada'}</p>
                    </div>
                    
                    <div class="datos-seccion">
                        <h3>Tutor Principal</h3>
                        <p><strong>Nombre:</strong> ${datosSolicitud.nombre_tutor1 || ''} ${datosSolicitud.apellido_tutor1 || ''}</p>
                        <p><strong>Dirección:</strong> ${datosSolicitud.direccion_tutor1 || 'No especificada'}</p>
                        <p><strong>Teléfono:</strong> ${datosSolicitud.telefono_tutor1 || 'No especificado'}</p>
                        <p><strong>Correo:</strong> ${datosSolicitud.correo_tutor1 || 'No especificado'}</p>
                        <p><strong>Relación:</strong> ${datosSolicitud.relacion_tutor1 || 'No especificada'}</p>
                    </div>
                    
                    ${datosSolicitud.nombre_tutor2 ? `
                    <div class="datos-seccion">
                        <h3>Tutor Secundario</h3>
                        <p><strong>Nombre:</strong> ${datosSolicitud.nombre_tutor2 || ''} ${datosSolicitud.apellido_tutor2 || ''}</p>
                        <p><strong>Dirección:</strong> ${datosSolicitud.direccion_tutor2 || 'No especificada'}</p>
                        <p><strong>Teléfono:</strong> ${datosSolicitud.telefono_tutor2 || 'No especificado'}</p>
                        <p><strong>Correo:</strong> ${datosSolicitud.correo_tutor2 || 'No especificado'}</p>
                        <p><strong>Relación:</strong> ${datosSolicitud.relacion_tutor2 || 'No especificada'}</p>
                    </div>
                    ` : ''}
                    
                    <div class="datos-seccion">
                        <h3>Estado de Solicitud</h3>
                        <p><strong>Estado:</strong> <span class="estado-badge estado-${datosSolicitud.estado || 'pendiente'}">${datosSolicitud.estado ? datosSolicitud.estado.charAt(0).toUpperCase() + datosSolicitud.estado.slice(1) : 'Pendiente'}</span></p>
                        <p><strong>Fecha de Solicitud:</strong> ${datosSolicitud.fecha_solicitud ? new Date(datosSolicitud.fecha_solicitud).toLocaleDateString() : 'No disponible'}</p>
                    </div>
                `;
                
                // Mostrar botón de rechazar solo si el estado es pendiente
                const botonesAccion = document.querySelector('.acciones-solicitud');
                if (botonesAccion) {
                    if (datosSolicitud.estado === 'pendiente' || !datosSolicitud.estado) {
                        botonesAccion.innerHTML = `
                            <button id="btnRechazar" class="btn-rechazar" data-solicitud-id="${solicitudId}">Rechazar</button>
                        `;
                    } else {
                        botonesAccion.innerHTML = `
                            <p class="estado-mensaje">Esta solicitud ya ha sido ${datosSolicitud.estado}</p>
                        `;
                    }
                }
                
                // Cargar documentos
                try {
                    console.log('Cargando documentos para solicitud:', solicitudId);
                    const responseDocumentos = await fetch(`../api/get_documentos.php?solicitud_id=${solicitudId}`);
                    if (!responseDocumentos.ok) {
                        throw new Error(`HTTP error! status: ${responseDocumentos.status}`);
                    }
                    const documentos = await responseDocumentos.json();
                    console.log('Documentos recibidos:', documentos);
                    
                    documentosContainer.innerHTML = '';
                    
                    if (!documentos || documentos.length === 0) {
                        documentosContainer.innerHTML = '<p class="no-documentos">No hay documentos disponibles para esta solicitud.</p>';
                    } else {
                        documentos.forEach(doc => {
                            const docItem = document.createElement('div');
                            docItem.className = 'documento-item';
                            
                            // Verificar el tipo de archivo
                            const extension = doc.url_documento ? doc.url_documento.split('.').pop().toLowerCase() : '';
                            let preview = '';
                            
                            if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                                preview = `<img src="${doc.url_documento}" alt="Documento" onerror="this.src='../assets/icons/file-error.png';">`;
                            } else {
                                preview = `<i class="fas fa-file-alt" style="font-size: 48px;"></i>`;
                            }
                            
                            docItem.innerHTML = `
                                ${preview}
                                <p>${doc.nombre_documento || 'Documento sin nombre'}</p>
                                ${doc.url_documento ? `<a href="${doc.url_documento}" target="_blank" class="btn-ver-doc">Ver documento</a>` : ''}
                            `;
                            
                            documentosContainer.appendChild(docItem);
                        });
                    }
                } catch (error) {
                    console.error('Error al cargar documentos:', error);
                    documentosContainer.innerHTML = '<p class="error-documentos">Error al cargar los documentos. Por favor, intente nuevamente.</p>';
                }
                
                // Mostrar modal
                modal.style.display = 'block';
                
            } catch (error) {
                console.error('Error al cargar datos:', error);
                alert('Error al cargar los datos de la solicitud');
            }
        }

        // Agregar evento click a las tarjetas de solicitud
        document.querySelectorAll('.solicitud-card').forEach(card => {
            card.addEventListener('click', function() {
                const solicitudId = this.dataset.solicitudId;
                cargarDatosSolicitud(solicitudId);
            });
        });

        // Cerrar modal
        closeModal.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html> 