<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    header("Location: ../../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Personal</title>
    <link rel="stylesheet" href="../../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../../css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .personal-container {
            padding: 20px;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-size: 16px;
            font-weight: 500;
            color: #666;
            position: relative;
        }

        .tab.active {
            color: #2196F3;
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #2196F3;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        }

        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .data-table th {
            background-color: #f5f5f5;
            font-weight: 600;
        }

        .data-table tr:hover {
            background-color: #f8f8f8;
        }

        .add-button {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-button:hover {
            background-color: #1976D2;
        }

        .btn-edit, .btn-delete {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            margin: 0 3px;
        }

        .btn-edit i {
            color: #2196F3;
        }

        .btn-delete i {
            color: #f44336;
        }

        .btn-edit:hover i {
            color: #1976D2;
        }

        .btn-delete:hover i {
            color: #D32F2F;
        }

        .btn-horario {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: background-color 0.3s;
        }

        .btn-horario:hover {
            background-color: #388E3C;
        }

        /* Estilos para el modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 1000px;
            position: relative;
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            font-weight: bold;
            color: #666;
            cursor: pointer;
        }

        .close-modal:hover {
            color: #000;
        }

        /* Estilos para la tabla de horario */
        .horario-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }

        .horario-table th, .horario-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .horario-table th {
            background-color: #f5f5f5;
        }

        .horario-table td.clase {
            background-color: #e3f2fd;
            font-size: 0.9em;
        }

        .horario-table td.clase:hover {
            background-color: #bbdefb;
        }

        .horario-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .horario-header h3 {
            margin: 0;
            color: #333;
        }

        .horario-header p {
            margin: 5px 0 0 0;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Modal para mostrar el horario -->
    <div id="horarioModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="cerrarModal()">&times;</span>
            <div class="horario-header">
                <h3>Horario del Docente</h3>
                <p id="docente-info"></p>
            </div>
            <div id="horario-container">
                <table class="horario-table">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Lunes</th>
                            <th>Martes</th>
                            <th>Miércoles</th>
                            <th>Jueves</th>
                            <th>Viernes</th>
                            <th>Sábado</th>
                            <th>Domingo</th>
                        </tr>
                    </thead>
                    <tbody id="horario-body">
                        <!-- El horario se cargará aquí dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="container">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php include '../../includes/empleado_sidebar.php'; ?>
        
        <div class="main-content">
            <div class="personal-container">
                <h1>Gestión de Personal</h1>
                
                <div class="tabs">
                    <button class="tab active" onclick="showTab('empleados')">Empleados</button>
                    <button class="tab" onclick="showTab('docentes')">Docentes</button>
                </div>

                <div id="empleados" class="tab-content active">
                    <button class="add-button">
                        <i class="fas fa-plus"></i>
                        Agregar Empleado
                    </button>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Departamento</th>
                                <th>Cargo</th>
                                <th>Horario</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="empleados-table">
                            <!-- Los datos de empleados se cargarán aquí -->
                        </tbody>
                    </table>
                </div>

                <div id="docentes" class="tab-content">
                    <button class="add-button">
                        <i class="fas fa-plus"></i>
                        Agregar Docente
                    </button>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Especialidad</th>
                                <th>Horario</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="docentes-table">
                            <!-- Los datos de docentes se cargarán aquí -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            console.log('Cambiando a la pestaña:', tabName);
            // Ocultar todos los contenidos de las pestañas
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Desactivar todas las pestañas
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Mostrar el contenido de la pestaña seleccionada
            const selectedContent = document.getElementById(tabName);
            if (selectedContent) {
                selectedContent.classList.add('active');
            } else {
                console.error('No se encontró el contenido de la pestaña:', tabName);
            }
            
            // Activar la pestaña seleccionada
            const selectedTab = document.querySelector(`.tab[onclick="showTab('${tabName}')"]`);
            if (selectedTab) {
                selectedTab.classList.add('active');
            } else {
                console.error('No se encontró la pestaña:', tabName);
            }

            // Cargar los datos correspondientes
            cargarDatos(tabName);
        }

        function cargarDatos(tipo) {
            console.log('Cargando datos para:', tipo);
            const tbody = document.getElementById(`${tipo}-table`);
            if (!tbody) {
                console.error('No se encontró la tabla para:', tipo);
                return;
            }

            fetch(`cargar_personal.php?tipo=${tipo}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Respuesta del servidor:', data);
                    
                    if (data.success) {
                        tbody.innerHTML = '';

                        if (data.data.length === 0) {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `<td colspan="8" style="text-align: center;">No hay registros disponibles</td>`;
                            tbody.appendChild(tr);
                            return;
                        }

                        data.data.forEach(item => {
                            const tr = document.createElement('tr');
                            
                            if (tipo === 'empleados') {
                                tr.innerHTML = `
                                    <td>${item.id || ''}</td>
                                    <td>${item.nombre || ''}</td>
                                    <td>${item.apellido || ''}</td>
                                    <td>${item.departamento || ''}</td>
                                    <td>${item.cargo || ''}</td>
                                    <td>${item.horario || ''}</td>
                                    <td>${item.estado || ''}</td>
                                    <td>
                                        <button onclick="editarPersonal('${tipo}', ${item.id})" class="btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="eliminarPersonal('${tipo}', ${item.id})" class="btn-delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                `;
                            } else {
                                tr.innerHTML = `
                                    <td>${item.id || ''}</td>
                                    <td>${item.nombre || ''}</td>
                                    <td>${item.apellido || ''}</td>
                                    <td>${item.especialidad || ''}</td>
                                    <td>
                                        <button class="btn-horario" onclick="verHorario('${tipo}', ${item.id})">
                                            Ver Horario
                                        </button>
                                    </td>
                                    <td>${item.estado || ''}</td>
                                    <td>
                                        <button onclick="editarPersonal('${tipo}', ${item.id})" class="btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="eliminarPersonal('${tipo}', ${item.id})" class="btn-delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                `;
                            }
                            
                            tbody.appendChild(tr);
                        });
                    } else {
                        console.error('Error en la respuesta:', data.message);
                        if (data.debug) {
                            console.log('Información de depuración:', data.debug);
                        }
                        tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: red;">Error al cargar los datos: ${data.message}</td></tr>`;
                    }
                })
                .catch(error => {
                    console.error('Error en la petición:', error);
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: red;">Error al cargar los datos: ${error.message}</td></tr>`;
                });
        }

        function editarPersonal(tipo, id) {
            // Esta función se implementará más adelante
            console.log(`Editar ${tipo} con ID: ${id}`);
        }

        function eliminarPersonal(tipo, id) {
            // Esta función se implementará más adelante
            console.log(`Eliminar ${tipo} con ID: ${id}`);
        }

        function verHorario(tipo, id) {
            const modal = document.getElementById('horarioModal');
            const docenteInfo = document.getElementById('docente-info');
            const horarioBody = document.getElementById('horario-body');

            // Mostrar el modal
            modal.style.display = 'block';

            // Cargar los datos del horario
            fetch(`obtener_horario_docente.php?docente_id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar la información del docente
                        docenteInfo.textContent = `${data.docente.nombre} ${data.docente.apellido} - ${data.docente.especialidad}`;

                        // Crear estructura de horario
                        const horas = generarRangoHoras('07:00', '22:00', 60); // Horario de 7am a 10pm
                        let horarioHTML = '';

                        horas.forEach(hora => {
                            let fila = `<tr><td>${hora}</td>`;
                            
                            // Para cada día de la semana (1-7)
                            for (let dia = 1; dia <= 7; dia++) {
                                const clase = data.horarios.find(h => 
                                    h.dia_semana == dia && 
                                    hora >= h.hora_inicio.substring(0, 5) && 
                                    hora < h.hora_fin.substring(0, 5)
                                );

                                if (clase) {
                                    fila += `<td class="clase">
                                        <strong>${clase.nombre_asignatura}</strong><br>
                                        <small>${clase.hora_inicio.substring(0, 5)} - ${clase.hora_fin.substring(0, 5)}</small>
                                    </td>`;
                                } else {
                                    fila += '<td></td>';
                                }
                            }
                            
                            fila += '</tr>';
                            horarioHTML += fila;
                        });

                        horarioBody.innerHTML = horarioHTML;
                    } else {
                        docenteInfo.textContent = 'Error al cargar el horario';
                        horarioBody.innerHTML = '<tr><td colspan="8">No se pudo cargar el horario</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    docenteInfo.textContent = 'Error al cargar el horario';
                    horarioBody.innerHTML = '<tr><td colspan="8">Error al cargar el horario</td></tr>';
                });
        }

        function cerrarModal() {
            document.getElementById('horarioModal').style.display = 'none';
        }

        function generarRangoHoras(inicio, fin, intervaloMinutos) {
            const horas = [];
            let horaActual = new Date('2000-01-01 ' + inicio);
            const horaFin = new Date('2000-01-01 ' + fin);

            while (horaActual < horaFin) {
                horas.push(horaActual.toTimeString().substring(0, 5));
                horaActual.setMinutes(horaActual.getMinutes() + intervaloMinutos);
            }

            return horas;
        }

        // Cerrar el modal cuando se hace clic fuera de él
        window.onclick = function(event) {
            const modal = document.getElementById('horarioModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Cargar los datos cuando la página esté lista
        document.addEventListener('DOMContentLoaded', function() {
            cargarDatos('empleados'); // Cargar datos de empleados por defecto
        });
    </script>
</body>
</html> 