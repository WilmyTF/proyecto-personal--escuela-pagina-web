<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    header("Location: ../login.php");
    exit;
}

require_once '../../includes/conexion.php';
verificarConexion();

// Obtener solo las solicitudes pendientes
$query = "SELECT * FROM solicitud_admision WHERE estado = 'pendiente' ORDER BY fecha_solicitud DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripción de Estudiantes</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
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
            cursor: pointer;
            transition: transform 0.2s;
        }
        .solicitud-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
            background-color: #fff3cd;
            color: #856404;
        }

        /* Estilos para el modal */
        .modal {
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
        .acciones-solicitud {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn-inscribir {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-inscribir:hover {
            background-color: #218838;
        }
        .btn-asociar-padre {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-asociar-padre:hover {
            background-color: #138496;
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
            <h1>Inscripción de Estudiantes</h1>
            
            <div class="solicitudes-container">
                <?php if (empty($solicitudes)): ?>
                    <p>No hay solicitudes pendientes para inscripción.</p>
                <?php else: ?>
                    <?php foreach ($solicitudes as $solicitud): ?>
                        <div class="solicitud-card" data-solicitud-id="<?php echo htmlspecialchars($solicitud['id_solicitud'] ?? ''); ?>">
                            <div class="solicitud-header">
                                <h3><?php echo htmlspecialchars(($solicitud['nombre_estudiante'] ?? '') . ' ' . ($solicitud['apellido_estudiante'] ?? '')); ?></h3>
                                <span class="estado-badge">Pendiente</span>
                            </div>
                            <div class="solicitud-info">
                                <div>
                                    <strong>Grado:</strong> <?php echo htmlspecialchars($solicitud['grado_cursar'] ?? 'No especificado'); ?>
                                </div>
                                <div>
                                    <strong>Especialidad:</strong> <?php echo htmlspecialchars($solicitud['especialidad'] ?? 'No especificada'); ?>
                                </div>
                                <div>
                                    <strong>Fecha de Solicitud:</strong> <?php echo $solicitud['fecha_solicitud'] ? date('d/m/Y', strtotime($solicitud['fecha_solicitud'])) : 'No especificada'; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para detalles y acciones -->
    <div id="detallesModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Detalles de la Solicitud</h2>
            <div id="datosEstudiante" class="datos-grid">
                <!-- Los datos se cargarán aquí dinámicamente -->
            </div>
            <div class="acciones-solicitud">
                <button id="btnAsociarPadre" class="btn-asociar-padre">Asociar Padre</button>
                <button id="btnInscribir" class="btn-inscribir">Inscribir</button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('detallesModal');
        const closeModal = document.querySelector('.close-modal');
        const datosEstudiante = document.getElementById('datosEstudiante');
        const btnInscribir = document.getElementById('btnInscribir');
        const btnAsociarPadre = document.getElementById('btnAsociarPadre');

        // Función para cargar datos de la solicitud
        async function cargarDatosSolicitud(solicitudId) {
            try {
                const response = await fetch(`../../api/get_solicitud.php?solicitud_id=${solicitudId}`);
                const datosSolicitud = await response.json();
                
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
                    </div>
                `;
                
                // Asignar el ID de la solicitud a los botones
                btnInscribir.setAttribute('data-solicitud-id', solicitudId);
                btnAsociarPadre.setAttribute('data-solicitud-id', solicitudId);
                
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

        // Manejar el botón de inscribir
        btnInscribir.addEventListener('click', async function() {
            const solicitudId = this.getAttribute('data-solicitud-id');
            if (confirm('¿Está seguro que desea inscribir a este estudiante?')) {
                try {
                    const response = await fetch('../../api/inscribir_estudiante.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ solicitud_id: solicitudId })
                    });
                    
                    const resultado = await response.json();
                    
                    if (resultado.success) {
                        alert('Estudiante inscrito correctamente');
                        // Recargar la página para actualizar la lista
                        window.location.reload();
                    } else {
                        alert('Error al inscribir al estudiante: ' + (resultado.message || 'Error desconocido'));
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error al procesar la solicitud');
                }
            }
        });

        // Manejar el botón de asociar padre
        btnAsociarPadre.addEventListener('click', function() {
            const solicitudId = this.getAttribute('data-solicitud-id');
            // Redirigir a la página de asociación de padre
            window.location.href = `asociar_padre.php?solicitud_id=${solicitudId}`;
        });
    });
    </script>
</body>
</html> 