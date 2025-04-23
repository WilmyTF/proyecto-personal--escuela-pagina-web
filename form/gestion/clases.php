<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    header("Location: ../../login.php");
    exit;
}

require_once '../../includes/conexion.php';
verificarConexion();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clases</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .main-content {
            padding: 30px;
            background-color: #f5f6fa;
            min-height: 100vh;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #ddd;
            background: white;
            padding: 0 20px;
            border-radius: 8px 8px 0 0;
        }
        
        .tab-button {
            padding: 15px 30px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 16px;
            color: #666;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .tab-button.active {
            color: #2c3e50;
            font-weight: 600;
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #2c3e50;
        }
        
        .tab-content {
            display: none;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .tab-content.active {
            display: block;
        }
        
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 25px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid #eee;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .card h3 {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 1.2em;
        }

        .card p {
            margin: 10px 0;
            color: #666;
            line-height: 1.5;
        }
        
        .actions {
            margin-bottom: 30px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 10px;
            background-color: #2c3e50;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background-color: #34495e;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #95a5a6;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }
        
        .btn-danger {
            background-color: #e74c3c;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            position: relative;
        }

        .modal-content h2 {
            margin: 0 0 25px 0;
            color: #2c3e50;
            font-size: 1.5em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #2c3e50;
            outline: none;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        .error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        
        .info {
            color: #0c5460;
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }

        .card-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .card-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }

        .card-info p {
            margin: 5px 0;
            font-size: 0.9em;
        }

        .select-estudiantes {
            width: 100%;
            min-height: 300px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        .estudiantes-container {
            margin: 20px 0;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            color: #666;
            cursor: pointer;
            padding: 0 5px;
        }

        .close:hover {
            color: #000;
        }

        /* Para evitar que un modal se sobreponga a otro */
        #modalEstudiantes {
            z-index: 1002;
        }
        
        #modalCurso {
            z-index: 1001;
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
            <h1>Gestión de Clases</h1>
            
            <div class="tabs">
                <button class="tab-button active" data-tab="cursos">Cursos</button>
                <button class="tab-button" data-tab="aulas">Aulas</button>
            </div>
            
            <!-- Pestaña de Cursos -->
            <div id="cursos" class="tab-content active">
                <div class="actions">
                    <button class="btn" onclick="mostrarModalCurso()">Agregar Curso</button>
                </div>
                <div class="grid-container" id="cursos-container">
                    <!-- Los cursos se cargarán aquí dinámicamente -->
                </div>
            </div>
            
            <!-- Pestaña de Aulas -->
            <div id="aulas" class="tab-content">
                <div class="actions">
                    <button class="btn" onclick="mostrarModalAula()">Agregar Aula</button>
                </div>
                <div class="grid-container" id="aulas-container">
                    <!-- Las aulas se cargarán aquí dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Cursos -->
    <div id="modalCurso" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Curso</h2>
                <span class="close" onclick="cerrarModal('modalCurso')">&times;</span>
            </div>
            <form id="formCurso">
                <input type="hidden" id="curso_id" name="curso_id">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="descripcion">Descripción:</label>
                    <textarea id="descripcion" name="descripcion"></textarea>
                </div>
                <div class="form-group">
                    <label for="docente_id">ID del Docente:</label>
                    <input type="number" id="docente_id" name="docente_id" required>
                </div>
                <div class="form-group">
                    <label for="cupo_maximo">Cupo Máximo:</label>
                    <input type="number" id="cupo_maximo" name="cupo_maximo" required>
                </div>
                <div class="form-group">
                    <label for="aula_id">ID del Aula:</label>
                    <input type="number" id="aula_id" name="aula_id" required>
                </div>
                <div class="form-group">
                    <button type="button" class="btn btn-secondary" onclick="mostrarModalEstudiantes()">
                        Gestionar Estudiantes
                    </button>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn">Guardar</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalCurso')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Estudiantes -->
    <div id="modalEstudiantes" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Gestionar Estudiantes</h2>
                <span class="close" onclick="cerrarModal('modalEstudiantes')">&times;</span>
            </div>
            <div class="estudiantes-container">
                <select id="estudiantes" multiple class="select-estudiantes">
                    <!-- Los estudiantes se cargarán dinámicamente -->
                </select>
            </div>
            <div class="form-actions">
                <button onclick="guardarEstudiantes()" class="btn">Guardar Cambios</button>
                <button onclick="cerrarModal('modalEstudiantes')" class="btn btn-secondary">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Modal para Aulas -->
    <div id="modalAula" class="modal">
        <div class="modal-content">
            <h2>Aula</h2>
            <form id="formAula">
                <input type="hidden" id="aula_id" name="aula_id">
                <div class="form-group">
                    <label for="nombre_aula">Nombre:</label>
                    <input type="text" id="nombre_aula" name="nombre_aula" required>
                </div>
                <div class="form-group">
                    <label for="capacidad">Capacidad:</label>
                    <input type="number" id="capacidad" name="capacidad" required>
                </div>
                <div class="form-group">
                    <label for="tipo">Tipo:</label>
                    <select id="tipo" name="tipo" required>
                        <option value="aula">Aula Regular</option>
                        <option value="laboratorio">Laboratorio</option>
                        <option value="taller">Taller</option>
                    </select>
                </div>
                <button type="submit" class="btn">Guardar</button>
                <button type="button" class="btn" onclick="cerrarModal('modalAula')">Cancelar</button>
            </form>
        </div>
    </div>

    <!-- Modal de Confirmación para Eliminar -->
    <div id="modalConfirmarEliminar" class="modal">
        <div class="modal-content">
            <h2>Confirmar Eliminación</h2>
            <p>¿Está seguro que desea eliminar este curso?</p>
            <div class="form-actions">
                <button onclick="confirmarEliminarCurso()" class="btn btn-danger">Eliminar</button>
                <button onclick="cerrarModal('modalConfirmarEliminar')" class="btn">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        // Manejo de pestañas
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.getAttribute('data-tab');
                
                // Actualizar botones
                document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                // Actualizar contenido
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                document.getElementById(tabId).classList.add('active');
                
                // Cargar datos
                if (tabId === 'cursos') {
                    cargarCursos();
                } else if (tabId === 'aulas') {
                    cargarAulas();
                }
            });
        });

        // Funciones para modales
        function mostrarModalCurso(id = null) {
            document.getElementById('formCurso').reset();
            document.getElementById('curso_id').value = '';
            const modal = document.getElementById('modalCurso');
            modal.style.display = 'block';
            
            // Cargar lista de estudiantes
            cargarEstudiantes();
            
            if (id) {
                // Cargar datos del curso para edición
                fetch(`../../api/cursos/listar.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            const curso = data.data[0];
                            document.getElementById('curso_id').value = curso.id;
                            document.getElementById('nombre').value = curso.nombre;
                            document.getElementById('descripcion').value = curso.descripcion;
                            document.getElementById('docente_id').value = curso.docente_id;
                            document.getElementById('cupo_maximo').value = curso.cupo_maximo;
                            document.getElementById('aula_id').value = curso.aula_id;
                            
                            // Cargar estudiantes del curso
                            cargarEstudiantesCurso(id);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            }
        }

        function mostrarModalAula(id = null) {
            const modal = document.getElementById('modalAula');
            modal.style.display = 'block';
            if (id) {
                // Cargar datos del aula para edición
                cargarDatosAula(id);
            }
        }

        function cerrarModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Cargar datos
        function cargarCursos() {
            console.log('Iniciando carga de cursos...');
            fetch('../../api/cursos/listar.php')
                .then(response => {
                    console.log('Respuesta recibida:', response);
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(response => {
                    console.log('Datos recibidos:', response);
                    if (!response.success) {
                        throw new Error(response.error || 'Error al cargar los cursos');
                    }
                    
                    const container = document.getElementById('cursos-container');
                    container.innerHTML = '';
                    
                    if (Array.isArray(response.data) && response.data.length > 0) {
                        response.data.forEach(curso => {
                            container.innerHTML += `
                                <div class="card">
                                    <h3>${curso.nombre || 'Sin nombre'}</h3>
                                    <p>${curso.descripcion || 'Sin descripción'}</p>
                                    <div class="card-info">
                                        <p><strong>Docente ID:</strong> ${curso.docente_id || 'No asignado'}</p>
                                        <p><strong>Aula ID:</strong> ${curso.aula_id || 'No asignada'}</p>
                                        <p><strong>Cupo Máximo:</strong> ${curso.cupo_maximo || 0}</p>
                                    </div>
                                    <div class="card-actions">
                                        <button onclick="mostrarModalCurso(${curso.id})" class="btn">Editar</button>
                                        <button onclick="eliminarCurso(${curso.id})" class="btn btn-danger">Eliminar</button>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        container.innerHTML = '<p class="info">No hay cursos registrados</p>';
                    }
                })
                .catch(error => {
                    console.error('Error al cargar cursos:', error);
                    const container = document.getElementById('cursos-container');
                    container.innerHTML = `<p class="error">Error al cargar los cursos: ${error.message}</p>`;
                });
        }

        function cargarAulas() {
            console.log('Iniciando carga de aulas...');
            fetch('../../api/aulas/listar.php')
                .then(response => {
                    console.log('Respuesta recibida:', response);
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Datos recibidos:', data);
                    const container = document.getElementById('aulas-container');
                    container.innerHTML = '';
                    if (Array.isArray(data)) {
                        data.forEach(aula => {
                            container.innerHTML += `
                                <div class="card">
                                    <h3>${aula.nombre}</h3>
                                    <p>Capacidad: ${aula.capacidad}</p>
                                    <p>Tipo: ${aula.tipo}</p>
                                    <div class="card-actions">
                                        <button onclick="mostrarModalAula(${aula.id})" class="btn">Editar</button>
                                        <button onclick="eliminarAula(${aula.id})" class="btn btn-danger">Eliminar</button>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        container.innerHTML = '<p>No se encontraron aulas</p>';
                    }
                })
                .catch(error => {
                    console.error('Error al cargar aulas:', error);
                    const container = document.getElementById('aulas-container');
                    container.innerHTML = `<p class="error">Error al cargar las aulas: ${error.message}</p>`;
                });
        }

        // Manejo de formularios
        document.getElementById('formCurso').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const cursoId = document.getElementById('curso_id').value;
            
            // Agregar los estudiantes seleccionados
            const estudiantesSelect = document.getElementById('estudiantes');
            const estudiantesSeleccionados = Array.from(estudiantesSelect.selectedOptions).map(option => option.value);
            formData.append('estudiantes', JSON.stringify(estudiantesSeleccionados));

            const url = cursoId ? 
                '../../api/cursos/actualizar.php' : 
                '../../api/cursos/crear.php';
            
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const cursoIdFinal = data.id || cursoId;
                    
                    const formDataEstudiantes = new FormData();
                    formDataEstudiantes.append('curso_id', cursoIdFinal);
                    formDataEstudiantes.append('estudiantes', JSON.stringify(estudiantesSeleccionados));

                    return fetch('../../api/cursos/agregar_estudiantes.php', {
                        method: 'POST',
                        body: formDataEstudiantes
                    });
                } else {
                    throw new Error(data.error || 'Error al guardar el curso');
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cerrarModal('modalCurso');
                    cargarCursos();
                } else {
                    throw new Error(data.error || 'Error al actualizar estudiantes');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al guardar los cambios: ' + error.message);
            });
        });

        let cursoIdAEliminar = null;

        function eliminarCurso(id) {
            cursoIdAEliminar = id;
            document.getElementById('modalConfirmarEliminar').style.display = 'block';
        }

        function confirmarEliminarCurso() {
            if (!cursoIdAEliminar) return;

            const formData = new FormData();
            formData.append('id', cursoIdAEliminar);

            fetch('../../api/cursos/eliminar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cerrarModal('modalConfirmarEliminar');
                    cargarCursos(); // Recargar la lista de cursos
                } else {
                    alert('Error al eliminar el curso: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al eliminar el curso');
            });
        }

        function cargarEstudiantes() {
            fetch('../../api/usuarios/listar_estudiantes.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('estudiantes');
                        select.innerHTML = '';
                        
                        data.data.forEach(estudiante => {
                            const option = document.createElement('option');
                            option.value = estudiante.id;
                            option.textContent = `${estudiante.apellido}, ${estudiante.nombre} (${estudiante.email})`;
                            select.appendChild(option);
                        });
                    } else {
                        console.error('Error al cargar estudiantes:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function cargarEstudiantesCurso(cursoId) {
            fetch(`../../api/cursos/obtener_estudiantes.php?curso_id=${cursoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const select = document.getElementById('estudiantes');
                        const estudiantesIds = data.data.map(e => e.id);
                        
                        // Seleccionar los estudiantes que están en el curso
                        Array.from(select.options).forEach(option => {
                            option.selected = estudiantesIds.includes(parseInt(option.value));
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function mostrarModalEstudiantes() {
            const modal = document.getElementById('modalEstudiantes');
            modal.style.display = 'block';
            cargarEstudiantes();
        }

        function guardarEstudiantes() {
            // Los estudiantes seleccionados se guardarán cuando se guarde el curso
            cerrarModal('modalEstudiantes');
        }

        // Cargar datos iniciales
        cargarCursos();
    </script>
</body>
</html> 