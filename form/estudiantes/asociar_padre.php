<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    header("Location: ../login.php");
    exit;
}

require_once '../../includes/conexion.php';
verificarConexion();

// Obtener ID de la solicitud
$solicitud_id = $_GET['solicitud_id'] ?? null;
if (!$solicitud_id) {
    header("Location: inscripcion.php");
    exit;
}

// Obtener datos de la solicitud
$stmt = $conn->prepare("SELECT * FROM solicitud_admision WHERE id_solicitud = ?");
$stmt->execute([$solicitud_id]);
$solicitud = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$solicitud) {
    header("Location: inscripcion.php");
    exit;
}

// Obtener lista de todos los padres
$stmt = $conn->prepare("SELECT id, nombre, apellido, telefono, correo FROM padres_tutores ORDER BY apellido, nombre");
$stmt->execute();
$padres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asociar Padre - Inscripción</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .form-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 20px auto;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn-submit {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 20px;
        }
        .btn-submit:hover {
            background-color: #218838;
        }
        .info-estudiante, .info-padre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .padres-list {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin: 20px 0;
            max-height: 300px;
            overflow-y: auto;
        }
        .padre-item {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .padre-item:hover {
            background-color: #f8f9fa;
        }
        .padre-item.selected {
            background-color: #e3f2fd;
            border-color: #2196f3;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            margin-right: 5px;
            border-radius: 4px 4px 0 0;
        }
        .tab.active {
            background: #fff;
            border-bottom: 2px solid #2196f3;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .search-box {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn-volver {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-volver:hover {
            background-color: #5a6268;
            color: white;
            text-decoration: none;
        }
        .btn-volver i {
            font-size: 16px;
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1>Asociar Padre/Tutor</h1>
                <a href="inscripcion.php" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> Volver a Inscripción
                </a>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="info-estudiante">
                    <h2>Datos del Estudiante</h2>
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($solicitud['nombre_estudiante'] . ' ' . $solicitud['apellido_estudiante']); ?></p>
                    <p><strong>Grado:</strong> <?php echo htmlspecialchars($solicitud['grado_cursar']); ?></p>
                    <p><strong>Especialidad:</strong> <?php echo htmlspecialchars($solicitud['especialidad']); ?></p>
                </div>

                <div class="info-padre">
                    <h2>Datos del Tutor Principal</h2>
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($solicitud['nombre_tutor1'] . ' ' . $solicitud['apellido_tutor1']); ?></p>
                    <p><strong>Dirección:</strong> <?php echo htmlspecialchars($solicitud['direccion_tutor1']); ?></p>
                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($solicitud['telefono_tutor1']); ?></p>
                    <p><strong>Correo:</strong> <?php echo htmlspecialchars($solicitud['correo_tutor1']); ?></p>
                    <p><strong>Relación:</strong> <?php echo htmlspecialchars($solicitud['relacion_tutor1']); ?></p>
                </div>
            </div>

            <div class="tabs">
                <button class="tab active" data-tab="existente">Seleccionar Padre Existente</button>
                <button class="tab" data-tab="nuevo">Registrar Nuevo Padre</button>
            </div>

            <div id="tabExistente" class="tab-content active">
                <input type="text" class="search-box" placeholder="Buscar padre por nombre o apellido..." id="searchPadre">
                
                <div class="padres-list">
                    <?php if (empty($padres)): ?>
                        <p>No hay padres registrados en el sistema.</p>
                    <?php else: ?>
                        <?php foreach ($padres as $padre): ?>
                            <div class="padre-item" data-id="<?php echo htmlspecialchars($padre['id']); ?>">
                                <p><strong><?php echo htmlspecialchars($padre['apellido'] . ', ' . $padre['nombre']); ?></strong></p>
                                <p>Teléfono: <?php echo htmlspecialchars($padre['telefono']); ?></p>
                                <p>Correo: <?php echo htmlspecialchars($padre['correo']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <button id="btnAsociarExistente" class="btn-submit" style="display: none;">Asociar Padre Seleccionado</button>
            </div>

            <div id="tabNuevo" class="tab-content">
                <div class="form-container">
                    <form id="formAsociarPadre">
                        <input type="hidden" name="solicitud_id" value="<?php echo htmlspecialchars($solicitud_id); ?>">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nombre">Nombre del Padre/Tutor</label>
                                <input type="text" id="nombre" name="nombre" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="apellido">Apellido del Padre/Tutor</label>
                                <input type="text" id="apellido" name="apellido" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="tel" id="telefono" name="telefono" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="correo">Correo Electrónico</label>
                                <input type="email" id="correo" name="correo" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="direccion">Dirección</label>
                                <input type="text" id="direccion" name="direccion" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-submit">Registrar y Asociar Nuevo Padre</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        const padreItems = document.querySelectorAll('.padre-item');
        const btnAsociarExistente = document.getElementById('btnAsociarExistente');
        const searchBox = document.getElementById('searchPadre');
        const formNuevoPadre = document.getElementById('formAsociarPadre');
        
        // Manejar cambios de pestaña
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById('tab' + this.dataset.tab.charAt(0).toUpperCase() + this.dataset.tab.slice(1))
                    .classList.add('active');
            });
        });

        // Manejar selección de padre existente
        padreItems.forEach(item => {
            item.addEventListener('click', function() {
                padreItems.forEach(i => i.classList.remove('selected'));
                this.classList.add('selected');
                btnAsociarExistente.style.display = 'block';
                btnAsociarExistente.setAttribute('data-padre-id', this.dataset.id);
            });
        });

        // Búsqueda de padres
        searchBox.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            padreItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchTerm) ? 'block' : 'none';
            });
        });

        // Asociar padre existente
        btnAsociarExistente.addEventListener('click', async function() {
            const padreId = this.getAttribute('data-padre-id');
            if (!padreId) return;

            try {
                const response = await fetch('../../api/asociar_padre_existente.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        solicitud_id: '<?php echo $solicitud_id; ?>',
                        padre_id: padreId
                    })
                });
                
                const resultado = await response.json();
                
                if (resultado.success) {
                    alert('Padre asociado correctamente');
                    window.location.href = 'inscripcion.php';
                } else {
                    alert('Error al asociar el padre: ' + (resultado.message || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            }
        });

        // Registrar y asociar nuevo padre
        formNuevoPadre.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('../../api/asociar_padre.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const resultado = await response.json();
                
                if (resultado.success) {
                    alert('Padre registrado correctamente. La asociación se completará al inscribir al estudiante.');
                    window.location.href = 'inscripcion.php';
                } else {
                    alert('Error al registrar el padre: ' + (resultado.message || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            }
        });
    });
    </script>
</body>
</html> 