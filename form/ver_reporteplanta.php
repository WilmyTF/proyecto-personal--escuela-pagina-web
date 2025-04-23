<?php
class VerReportePlanta {
    private $db;
    
    public function __construct($conexion) {
        $this->db = $conexion;
    }

    public function mostrarFormulario() {
        ?>
        <style>
            .modal-reportes {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 1000;
                justify-content: center;
                align-items: center;
            }

            .contenedor-reportes {
                max-width: 800px;
                background-color: white;
                padding: 25px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                font-family: 'Segoe UI', Arial, sans-serif;
                position: relative;
                animation: aparecer 0.3s ease-out;
            }

            @keyframes aparecer {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .tabla-reportes {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }

            .tabla-reportes th, 
            .tabla-reportes td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }

            .tabla-reportes th {
                background-color: #f8f9fa;
                font-weight: 600;
            }

            .btn-cerrar {
                position: absolute;
                top: 10px;
                right: 10px;
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
            }

            .botones-accion {
                display: flex;
                gap: 10px;
                justify-content: flex-end;
                margin-top: 20px;
            }

            .checkbox-personalizado {
                width: 18px;
                height: 18px;
                cursor: pointer;
            }

            .tabla-reportes thead th:first-child,
            .tabla-reportes tbody td:first-child {
                width: 40px;
                text-align: center;
            }

            .seleccionar-todos-container {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 10px;
            }

            .seleccionar-todos-container label {
                cursor: pointer;
                user-select: none;
                color: #666;
            }

            .tabla-reportes tbody tr.seleccionada {
                background-color: #ffe8e8;
            }

            .tabla-reportes tbody tr:hover {
                background-color: #f5f5f5;
            }

            .estado-critico {
                color: #dc3545;
                font-weight: bold;
            }

            .estado-normal {
                color: #28a745;
            }

            .btn-ver-reporte {
                background-color: #0d6efd;
                color: white;
                border: none;
                padding: 6px 12px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.9rem;
                display: flex;
                align-items: center;
                gap: 5px;
                transition: opacity 0.3s;
            }

            .btn-ver-reporte:hover {
                opacity: 0.9;
            }

            .btn-ver-reporte i {
                font-size: 1rem;
            }

            .tabla-reportes th:last-child,
            .tabla-reportes td:last-child {
                text-align: center;
                width: 120px;
            }
        </style>

        <div id="modalReportes" class="modal-reportes">
            <div class="contenedor-reportes">
                <button class="btn-cerrar">&times;</button>
                <h2>Reportes de Planta</h2>
                
                <div class="seleccionar-todos-container">
                    <input type="checkbox" id="seleccionarTodos" class="checkbox-personalizado">
                    <label for="seleccionarTodos">Seleccionar todos</label>
                </div>

                <table class="tabla-reportes">
                    <thead>
                        <tr>
                            <th></th>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <input type="checkbox" class="checkbox-personalizado checkbox-fila" data-id="1">
                            </td>
                            <td>1</td>
                            <td>2025-02-20</td>
                            <td>Mantenimiento</td>
                            <td><span class="estado-critico">Crítico</span></td>
                            <td>butacas rotas</td>
                        </tr>
                        <tr>
                            <td>
                                <input type="checkbox" class="checkbox-personalizado checkbox-fila" data-id="2">
                            </td>
                            <td>2</td>
                            <td>2025-02-19</td>
                            <td>Revisión</td>
                            <td><span class="estado-normal">Normal</span></td>
                            <td>Revisión de la puerta</td>
                        </tr>
                    </tbody>
                </table>

                <div class="botones-accion">
                    <button class="btn-anadir">+ Nuevo Reporte</button>
                    <button class="btn-ver-reporte">
                        <i class="fas fa-eye"></i>
                        Ver reporte
                    </button>
                </div>
            </div>
        </div>

        <script>
        // Inicializar checkboxes y eventos
        const seleccionarTodos = document.getElementById('seleccionarTodos');
        const checkboxes = document.querySelectorAll('.checkbox-fila');
        
        function actualizarSeleccionarTodos() {
            const totalCheckboxes = checkboxes.length;
            const checkboxesMarcados = document.querySelectorAll('.checkbox-fila:checked').length;
            seleccionarTodos.checked = totalCheckboxes === checkboxesMarcados;
            seleccionarTodos.indeterminate = checkboxesMarcados > 0 && checkboxesMarcados < totalCheckboxes;
        }

        seleccionarTodos.addEventListener('change', function() {
            const isChecked = this.checked;
            checkboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
                const fila = checkbox.closest('tr');
                if (fila) {
                    fila.classList.toggle('seleccionada', isChecked);
                }
            });
        });

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const fila = this.closest('tr');
                if (fila) {
                    fila.classList.toggle('seleccionada', this.checked);
                }
                actualizarSeleccionarTodos();
            });
        });
        </script>
        <?php
    }
}

// Solo mostrar el formulario
$verReporte = new VerReportePlanta(null);
$verReporte->mostrarFormulario();
?> 