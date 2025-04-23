<?php
class Mapa {
    private $db;
    
    public function __construct($conexion) {
        $this->db = $conexion;
    }

    public function renderizar($tiposArea) {
        $this->renderizarMapa();
        $this->incluirScripts($tiposArea);
    }

    private function renderizarMapa() {
        ?>
        <div class="area-mapa">
            <div class="mapa-controls">
                <div class="zoom-controls">
                    <button class="zoom-btn" id="zoom-in">+</button>
                    <button class="zoom-btn" id="zoom-out">-</button>
                    <button class="zoom-btn" id="zoom-reset">↺</button>
                </div>
                <div class="edit-controls">
                    <button class="edit-btn" id="toggle-edit-mode">
                        <i class="fas fa-edit"></i> Modo Edición
                    </button>
                    <button class="edit-btn" id="toggle-areas-padre" style="display: none;">
                        <i class="fas fa-layer-group"></i> Mostrar Áreas
                    </button>
                    <button class="edit-btn" id="toggle-mover-areas" style="display: none;">
                        <i class="fas fa-arrows-alt"></i> Mover Áreas
                    </button>
                    <button class="edit-btn" id="btn-undo-edit" style="display: none; background-color: #ffc107;">
                        <i class="fas fa-undo"></i> Deshacer
                    </button>
                    <button class="edit-btn" id="btn-agregar-area" style="display: none; background-color: #2196F3;">
                        <i class="fas fa-plus"></i> Agregar Área
                    </button>
                </div>
            </div>
            <div id="mapa-container">
                <svg id="mapa-svg" viewBox="0 0 1000 1000" preserveAspectRatio="xMidYMid meet">
                    <!-- Las áreas se cargarán dinámicamente aquí -->
                </svg>
            </div>
            <div id="zoom-level" class="zoom-level oculto">100%</div>
            <div id="mapa-preview"></div>
        </div>

        <!-- Modal para Agregar Área -->
        <div id="modal-agregar-area" class="modal-base" style="display: none;">
            <div class="modal-contenido-amplio">
                <div class="modal-encabezado">
                    <h3>Agregar Nueva Área</h3>
                    <button class="btn-cerrar-modal" id="btn-cerrar-modal-area">&times;</button>
                </div>
                <div class="modal-cuerpo">
                    <form id="form-agregar-area">
                        <div class="campo-form">
                            <label for="nombre-area">Nombre del Área:</label>
                            <input type="text" id="nombre-area" name="nombre-area" required>
                        </div>
                        <div class="campo-form">
                            <label for="tipo-area">Tipo de Área:</label>
                            <select id="tipo-area" name="tipo-area" required>
                                <option value="">Seleccione un tipo</option>
                                <option value="parqueo">Parqueo</option>
                                <option value="cancha">Cancha</option>
                                <option value="seccion">Sección</option>
                                <option value="temporal">Temporal</option>
                                <option value="otros">Otros</option>
                            </select>
                        </div>
                        <div class="campo-form" id="campo-tipo-area-otro" style="display: none;">
                            <label for="tipo-area-otro">Especifique el tipo:</label>
                            <input type="text" id="tipo-area-otro" name="tipo-area-otro">
                        </div>
                        <div class="campo-form">
                            <label for="color-area">Color:</label>
                            <input type="color" id="color-area" name="color-area" value="#D3D3D3">
                        </div>
                        <div class="campo-form">
                            <label>Posición en el mapa:</label>
                            <div class="coordenadas-form">
                                <div>
                                    <label for="pos-x">X:</label>
                                    <input type="number" id="pos-x" name="pos-x" required>
                                </div>
                                <div>
                                    <label for="pos-y">Y:</label>
                                    <input type="number" id="pos-y" name="pos-y" required>
                                </div>
                            </div>
                        </div>
                        <div class="campo-form">
                            <label>Dimensiones:</label>
                            <div class="dimensiones-form">
                                <div>
                                    <label for="ancho">Ancho:</label>
                                    <input type="number" id="ancho" name="ancho" required min="10">
                                </div>
                                <div>
                                    <label for="alto">Alto:</label>
                                    <input type="number" id="alto" name="alto" required min="10">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-pie">
                    <button class="btn-cancelar" id="btn-cancelar-area">Cancelar</button>
                    <button class="btn-confirmar" id="btn-guardar-area">Guardar Área</button>
                </div>
            </div>
        </div>

        <style>
        .mapa-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }

        .edit-controls {
            display: flex;
            gap: 5px;
        }

        .edit-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s;
        }

        .edit-btn:hover {
            background-color: #45a049;
        }

        .edit-btn.active {
            background-color: #f44336;
        }

        .edit-btn.active:hover {
            background-color: #da190b;
        }

        .area-interactiva.editable {
            cursor: move;
            transition: all 0.3s ease;
        }

        .area-interactiva.editable:hover {
            opacity: 0.8;
            filter: brightness(1.1);
        }

        .area-interactiva.editando {
            stroke: #ff4081;
            stroke-width: 2px;
            stroke-dasharray: 5,5;
            animation: dash 20s linear infinite;
        }

        @keyframes dash {
            to {
                stroke-dashoffset: 1000;
            }
        }

        .area-padre {
            opacity: 0.3;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        
        .area-padre.visible {
            display: block !important;
            opacity: 0.5;
            pointer-events: all;
        }
        
        .area-padre.movible {
            cursor: move;
            opacity: 0.7;
        }
        
        .area-padre.movible:hover {
            opacity: 0.9;
        }
        
        #toggle-areas-padre {
            background-color: #2196F3;
        }
        
        #toggle-areas-padre.active {
            background-color: #1976D2;
        }

        #toggle-mover-areas {
            background-color: #FF9800;
            display: none;
        }
        
        #toggle-mover-areas.active {
            background-color: #F57C00;
        }

        .subdivision-grupo {
            transition: transform 0.2s ease;
        }

        .zoom-controls {
            display: flex;
            gap: 5px;
        }
        
        .zoom-btn {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .zoom-btn:hover {
            background-color: #f5f5f5;
            border-color: #aaa;
        }
        
        .zoom-level {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            transition: opacity 0.3s;
        }
        
        .zoom-level.oculto {
            opacity: 0;
        }
        
        .area-mapa {
            flex: 1;
            background-color: #4CAF50;
            position: relative;
            order: 1;
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: grab;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        .area-mapa #mapa-container {
            width: 100%;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .area-mapa #mapa-svg {
            width: 100%;
            height: 100%;
            transform-origin: center;
            transition: transform 0.1s ease-out;
            position: absolute;
            top: 0;
            left: 0;
        }

        /* Estilos para el modal de agregar área */
        .modal-base {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1050;
            justify-content: center;
            align-items: center;
        }

        .modal-contenido-amplio {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            width: 600px;
            max-width: 90%;
            max-height: 80vh;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
        }

        .modal-encabezado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .modal-encabezado h3 {
            margin: 0;
            color: #333;
            font-size: 1.3em;
        }

        .btn-cerrar-modal {
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: #666;
            padding: 0 5px;
            line-height: 1;
        }

        .modal-cuerpo {
            flex-grow: 1;
            overflow-y: auto;
            padding-right: 10px;
        }

        .campo-form {
            margin-bottom: 20px;
        }

        .campo-form label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .campo-form input[type="text"],
        .campo-form input[type="number"],
        .campo-form select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .campo-form input[type="color"] {
            width: 100%;
            height: 40px;
            padding: 2px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .coordenadas-form,
        .dimensiones-form {
            display: flex;
            gap: 20px;
        }

        .coordenadas-form > div,
        .dimensiones-form > div {
            flex: 1;
        }

        .modal-pie {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-cancelar,
        .btn-confirmar {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .btn-cancelar {
            background-color: #dc3545;
            color: white;
        }

        .btn-confirmar {
            background-color: #28a745;
            color: white;
        }

        .btn-cancelar:hover {
            background-color: #c82333;
        }

        .btn-confirmar:hover {
            background-color: #218838;
        }
        </style>
        <?php
    }

    private function incluirScripts($tiposArea) {
        ?>
        <!-- Incluir SVG.js -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/svg.js/3.1.2/svg.min.js"></script>
        <!-- Incluir plugin SVG.draggable.js -->
        <script src="https://cdn.jsdelivr.net/npm/@svgdotjs/svg.draggable.js@3.0.3/dist/svg.draggable.min.js"></script>
        <script>
        // Definir los tipos de área localmente en el script
        const tiposAreaDisponibles = <?php echo json_encode($tiposArea); ?>;

        // Inicializar la instancia explícitamente
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Verificando inicialización de MapaInteractivo...');
            
            // Asegurarse de que SVG.js esté cargado
            if (typeof SVG === 'undefined') {
                console.error('SVG.js no está cargado correctamente.');
                return;
            }

            // Solo inicializar si no existe la instancia
            if (typeof MapaInteractivo !== 'undefined' && !window.mapaInteractivo) {
                window.mapaInteractivo = new MapaInteractivo();
                console.log('Mapa interactivo inicializado correctamente');
            } else if (window.mapaInteractivo) {
                console.log('Mapa interactivo ya existe, no es necesario reinicializar');
            } else {
                console.error('La clase MapaInteractivo no está definida.');
            }
        });
        </script>
        <?php
    }
}
?> 