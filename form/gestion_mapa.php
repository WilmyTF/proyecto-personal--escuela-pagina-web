<?php
// Iniciar la sesión y verificar si el usuario está autenticado como empleado/admin
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'empleado') {
    // Redirigir si no es un empleado/admin autenticado
    header("Location: ../login.php");
    exit;
}

require_once 'components/Mapa.php';
require_once '../includes/mapa_interactivo.php';
require_once '../includes/conexion.php';

class GestionMapa {
    private $db;
    private $mapa;
    private $mapaInteractivo;
    private $cambiosSinGuardar = false;
    
    public function __construct($conexion = null) {
        $this->db = $conexion;
        $this->mapa = new Mapa($conexion);
        $this->mapaInteractivo = new MapaInteractivo();
    }

    private function obtenerTiposArea() {
        $tipos = array();
        
        if ($this->db instanceof PDO) {
            try {
            $query = "SELECT id, nombre, descripcion FROM tipos_area WHERE activo = true ORDER BY nombre";
                $stmt = $this->db->query($query);
                $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC); 
                
            } catch (PDOException $e) {
                error_log("[GestionMapa] Error PDO al obtener tipos de área: " . $e->getMessage());
            }
        } else {
            error_log("[GestionMapa] Error: La conexión DB no es un objeto PDO válido en obtenerTiposArea.");
        }
        
        return $tipos;
    }

    private function obtenerSubdivisiones($areaId) {
        $subdivisiones = array();
        
        if ($this->db instanceof PDO) {
            try {
            $query = "SELECT id, nombre, tipo_id, svg_id, data_id, color, aula_id 
                      FROM subdivisiones_area 
                          WHERE area_id = :area_id 
                      ORDER BY nombre";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':area_id', $areaId, PDO::PARAM_INT);
                $stmt->execute();
                $subdivisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("[GestionMapa] Error PDO en obtenerSubdivisiones: " . $e->getMessage());
            }
        } else {
            $subdivisiones = array(
                array('id' => 1, 'nombre' => 'Subdivisión 1', 'tipo_id' => 1, 'svg_id' => 'sub1', 'data_id' => 'data-sub1', 'color' => '#FF5733', 'aula_id' => null),
                array('id' => 2, 'nombre' => 'Subdivisión 2', 'tipo_id' => 1, 'svg_id' => 'sub2', 'data_id' => 'data-sub2', 'color' => '#33FF57', 'aula_id' => null)
            );
        }
        
        return $subdivisiones;
    }
    
    private function verificarYRegistrarSubdivisiones($areaId) {
        if (!$this->db) {
            return false;
        }
        
        $subdivisionesDB = $this->obtenerSubdivisiones($areaId);
        $subdivisionesDBIds = array();
        
        foreach ($subdivisionesDB as $sub) {
            $subdivisionesDBIds[] = $sub['data_id'];
        }
        
        $subdivisionesSVG = $this->obtenerSubdivisionesSVG($areaId);
        
        $subdivisionesFaltantes = array();
        
        foreach ($subdivisionesSVG as $sub) {
            if (!in_array($sub['data_id'], $subdivisionesDBIds)) {
                $subdivisionesFaltantes[] = $sub;
            }
        }
        
        if (count($subdivisionesFaltantes) > 0) {
            return $this->registrarSubdivisionesFaltantes($areaId, $subdivisionesFaltantes);
        }
        
        return true;
    }
    
    private function obtenerSubdivisionesSVG($areaId) {
        $subdivisiones = array();
        
        if ($this->db instanceof PDO) {
            try {
                $query = "SELECT svg_id FROM areas_mapa WHERE id = :area_id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':area_id', $areaId, PDO::PARAM_INT);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $svgId = $row['svg_id'];
            
            $subdivisiones = array(
                array(
                    'nombre' => 'Subdivisión ' . $areaId . '-1',
                    'tipo_id' => 1,
                    'svg_id' => 'sub-' . $areaId . '-1',
                    'data_id' => 'data-sub-' . $areaId . '-1',
                    'color' => '#FF5733',
                    'aula_id' => null
                ),
                array(
                    'nombre' => 'Subdivisión ' . $areaId . '-2',
                    'tipo_id' => 1,
                    'svg_id' => 'sub-' . $areaId . '-2',
                    'data_id' => 'data-sub-' . $areaId . '-2',
                    'color' => '#33FF57',
                    'aula_id' => null
                )
            );
                }
            } catch (PDOException $e) {
                error_log("[GestionMapa] Error PDO en obtenerSubdivisionesSVG: " . $e->getMessage());
            }
        }
        
        return $subdivisiones;
    }
    
    private function registrarSubdivisionesFaltantes($areaId, $subdivisiones) {
        if (!($this->db instanceof PDO)) {
            error_log("[GestionMapa] Intento de registrar subdivisiones sin conexión PDO válida.");
            return false;
        }
        
        $exito = true;
            $query = "INSERT INTO subdivisiones_area (area_id, nombre, tipo_id, svg_id, data_id, color, aula_id, fecha_creacion) 
                  VALUES (:area_id, :nombre, :tipo_id, :svg_id, :data_id, :color, :aula_id, NOW())";
                  
        try {
            $stmt = $this->db->prepare($query);
            
            foreach ($subdivisiones as $sub) {
                 $stmt->bindParam(':area_id', $areaId, PDO::PARAM_INT);
                 $stmt->bindParam(':nombre', $sub['nombre'], PDO::PARAM_STR);
                 $stmt->bindParam(':tipo_id', $sub['tipo_id'], PDO::PARAM_INT);
                 $stmt->bindParam(':svg_id', $sub['svg_id'], PDO::PARAM_STR);
                 $stmt->bindParam(':data_id', $sub['data_id'], PDO::PARAM_STR);
                 $stmt->bindParam(':color', $sub['color'], PDO::PARAM_STR);
                 $stmt->bindParam(':aula_id', $sub['aula_id'], PDO::PARAM_INT);
                
                 if (!$stmt->execute()) {
                $exito = false;
                     error_log("[GestionMapa] Error PDO al registrar subdivisión: " . implode(" ", $stmt->errorInfo()));
            }
            }
        } catch (PDOException $e) {
            error_log("[GestionMapa] Excepción PDO en registrarSubdivisionesFaltantes: " . $e->getMessage());
            $exito = false;
        }
        
        return $exito;
    }

    public function mostrarFormulario() {
        $tiposArea = $this->obtenerTiposArea();
        ?>
        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <link rel="stylesheet" href="../css/mapa_interactivo.css">
        <style>
        .contenedor-principal {
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Arial, sans-serif;
            overflow: hidden;
        }

        .barra-superior {
            display: flex;
            gap: 20px;
            padding: 20px;
            background-color: white;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }

        .menu-toggle {
            position: absolute;
            left: 20px;
        }

        .campo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .campo input, .campo select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            outline: none;
            transition: border-color 0.3s;
        }

        .campo input:focus, .campo select:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 0 2px rgba(76,175,80,0.1);
        }

        .campo label {
            font-weight: 500;
            color: #333;
        }

        .contenido-principal {
            display: flex;
            flex: 1;
            flex-direction: row;
            min-height: 0;
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

        .area-mapa img {
            width: 100%;
            height: 100%;
            object-fit: fill;
            display: block;
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
        }

        .panel-derecho {
            width: 300px;
            min-width: 300px;
            background-color: white;
            padding: 20px;
            order: 2;
            display: flex;
            flex-direction: column;
            box-shadow: -2px 0 4px rgba(0,0,0,0.1);
            overflow-y: auto;
        }

        .panel-derecho h2 {
            margin: 0 0 20px 0;
            padding-bottom: 15px;
            border-bottom: 2px solid #4CAF50;
            color: #333;
            font-size: 1.5rem;
        }

        .panel-derecho h3 {
            color: #555;
            margin: 15px 0 10px 0;
            font-size: 1.1rem;
        }

        .seccion-panel {
            margin-bottom: 20px;
            display: none;
        }

        .seccion-panel.activo {
            display: block;
        }

        .lista-responsables,
        .lista-personal {
            margin-bottom: 15px;
        }

        .lista-responsables textarea,
        .lista-personal textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            margin-bottom: 10px;
            font-family: inherit;
            font-size: 0.9rem;
        }

        .lista-responsables textarea:focus,
        .lista-personal textarea:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 0 2px rgba(76,175,80,0.1);
        }

        .botones-acciones {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-eliminar,
        .btn-anadir {
            flex: 1;
            padding: 8px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .btn-eliminar {
            background-color: #dc3545;
        }

        .btn-anadir {
            background-color: #28a745;
        }

        .btn-eliminar:hover,
        .btn-anadir:hover {
            opacity: 0.9;
        }

        .botones-panel {
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }

        .btn-reporte,
        .btn-guardar {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .btn-reporte {
            background-color: #dc3545;
            color: white;
        }

        .btn-guardar {
            background-color: #0d6efd;
            color: white;
        }

        .btn-reporte:hover,
        .btn-guardar:hover {
            opacity: 0.9;
        }

        .btn-reporte i,
        .btn-guardar i {
            margin-right: 8px;
        }

        .info-planta {
            display: flex;
            align-items: center;
        }

        .btn-menu {
            background-color: #0d6efd;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-menu:hover {
            opacity: 0.9;
        }

        .btn-menu i {
            font-size: 1.2rem;
        }

        .area-interactiva {
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .area-interactiva:hover {
            opacity: 0.8;
            stroke: #ffffff;
            stroke-width: 2;
        }

        .tooltip {
            position: absolute;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            pointer-events: none;
            z-index: 1000;
        }

        .mensaje-seleccion {
            text-align: center;
            color: #666;
            padding: 20px;
        }


        .btn-editar-horario {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }
        
        .btn-editar-horario:hover {
            background-color: #3e8e41;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .btn-editar-horario i {
            margin-right: 8px;
        }

        .area-interactiva.seleccionada {
            stroke: #0d6efd;
            stroke-width: 3;
            opacity: 0.9;
        }

       
        .modal-confirmacion {
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
        
        .modal-confirmacion-contenido {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .modal-confirmacion-encabezado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-confirmacion-encabezado h3 {
            margin: 0;
            color: #333;
        }
        
        .modal-confirmacion-cuerpo {
            margin-bottom: 20px;
        }
        
        .modal-confirmacion-pie {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn-confirmar {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-cancelar {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-cerrar-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
      
        .lista-subdivisiones {
            margin-top: 15px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .subdivision-item {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .subdivision-item:last-child {
            border-bottom: none;
        }
        
        .subdivision-item:hover {
            background-color: #f5f5f5;
        }
        
        .subdivision-item.seleccionada {
            background-color: #e3f2fd;
            border-left: 3px solid #2196F3;
        }

      
        .mensaje-registro {
            background-color: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            display: none;
        }
        
        .mensaje-registro.exito {
            background-color: #e8f5e9;
            border-left-color: #4CAF50;
        }
        
        .mensaje-registro.error {
            background-color: #ffebee;
            border-left-color: #f44336;
        }

     
        .btn-accion-panel {
            background-color: #6c757d; 
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background-color 0.3s;
            vertical-align: middle; 
        }
        .btn-accion-panel:hover {
            background-color: #5a6268;
        }
        .btn-accion-panel i {
            margin-right: 5px;
        }

    
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
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-encabezado h3 {
            margin: 0;
            color: #333;
            font-size: 1.3em;
        }
        
        .modal-cuerpo {
            margin-bottom: 20px;
            overflow-y: auto; 
            flex-grow: 1;
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
        .btn-cerrar-modal:hover {
            color: #333;
        }

        
        #filtro-lista-elementos ul {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }
        #filtro-lista-elementos .area-item {
            font-weight: bold;
            margin-top: 10px;
            padding: 5px;
            background-color: #f8f9fa;
            border-radius: 3px;
        }
        #filtro-lista-elementos .subdivision-item {
            padding: 3px 5px 3px 25px; 
            font-size: 0.95em;
            border-bottom: 1px dashed #eee;
        }
        #filtro-lista-elementos .subdivision-item:last-child {
            border-bottom: none;
        }
        #filtro-lista-elementos .no-items {
            color: #888;
            font-style: italic;
        }
        
      
        #filtro-lista-elementos li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-right: 5px; 
        }
        
        #filtro-lista-elementos .item-actions {
            display: flex;
            gap: 5px;
            
            flex-shrink: 0;
        }
        
        .btn-lista-accion {
            background: none;
            border: 1px solid #ccc;
            color: #555;
            padding: 2px 6px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8em;
            line-height: 1;
            transition: all 0.2s ease;
        }
        
        .btn-lista-accion:hover {
            background-color: #eee;
            color: #000;
            border-color: #aaa;
        }
        
        .btn-lista-accion i {
            
        }
        
       
        #filtro-lista-elementos li span {
            flex-grow: 1;
            margin-right: 10px;
        }

      
        .mapa-enfocado .area-interactiva:not(.enfocado):not(.contexto-enfocado) {
            opacity: 0.25; 
            pointer-events: none; 
            transition: opacity 0.3s ease-in-out;
        }

        .mapa-enfocado .area-interactiva.enfocado.area-padre,
        .mapa-enfocado .area-interactiva.enfocado:not(.area-padre) { 
            opacity: 1;
            stroke: #e91e63; 
            stroke-width: 3px; 
            stroke-dasharray: none; 
            pointer-events: auto;
        }

        .mapa-enfocado .area-interactiva.contexto-enfocado { 
            opacity: 1; 
            stroke: #ff9800; 
            stroke-width: 1.5px;
            pointer-events: auto;
        }

   
        .mapa-container.mapa-enfocado {
          
        }


        </style>

        <!-- Modal de confirmación para eliminar -->
        <div id="modal-confirmar-eliminar" class="modal-confirmacion">
            <div class="modal-confirmacion-contenido">
                <div class="modal-confirmacion-encabezado">
                    <h3>Confirmar Eliminación</h3>
                    <button class="btn-cerrar-modal" id="btn-cerrar-modal-eliminar">&times;</button>
                </div>
                <div class="modal-confirmacion-cuerpo">
                    <p id="mensaje-confirmar-eliminar">¿Está seguro que desea eliminar este elemento?</p>
                </div>
                <div class="modal-confirmacion-pie">
                    <button class="btn-cancelar" id="btn-cancelar-eliminar">Cancelar</button>
                    <button class="btn-confirmar" id="btn-confirmar-eliminar">Eliminar</button>
                </div>
            </div>
        </div>

        <div class="contenedor-principal">
    
            <div class="barra-superior">
                <div class="menu-toggle">
                    
                    </button>
                </div>
                <div class="campo">
                    <label>Planta Id:</label>
                    <input type="text" name="planta_id" id="planta_id" readonly>
                </div>
                <div class="campo">
                    <label>Nombre:</label>
                    <input type="text" name="nombre" id="nombre_planta">
                </div>
                <div class="campo">
                    <label>Tipo:</label>
                    <select name="tipo" id="tipo_planta">
                        <option value="">Seleccionar</option>
                        <?php foreach ($tiposArea as $tipo): ?>
                            <option value="<?php echo $tipo['id']; ?>"><?php echo htmlspecialchars($tipo['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="info-planta">
                    <button class="btn-editar" onclick="abrirModal()">Editar tipo</button>
                </div>
            </div>

            <div class="contenido-principal">
                
                <?php $this->mapa->renderizar($tiposArea); ?>

              
                <div class="panel-derecho">
                    <h2>Información de Planta</h2>
                    <button id="btn-abrir-filtro" class="btn-accion-panel" style="margin-left: 15px;">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>

                  
                    <div id="panel-edicion-planta" class="seccion-panel" style="display: none;">
                        <h3>Propiedades del Elemento</h3>
                        <div class="campo">
                            <label for="edit-color-elemento">Color:</label>
                            <input type="color" id="edit-color-elemento" name="edit-color-elemento">
                        </div>
                        <div class="botones-panel" style="margin-top: 20px;">
                             <button id="btn-guardar-todo-edicion" class="btn-guardar" style="display: none; background-color: #0d6efd;" title="Guardar todos los cambios pendientes en la base de datos">
                                 <i class="fas fa-save"></i> Guardar Cambios
                             </button>
                             <button id="btn-eliminar-elemento" class="btn-eliminar" style="display: none; background-color: #dc3545;" title="Eliminar el elemento seleccionado">
                                 <i class="fas fa-trash"></i> Eliminar
                             </button>
                         </div>
                    </div>
                    
 
                    <div id="mensaje-registro" class="mensaje-registro">
                        <p id="mensaje-registro-texto"></p>
                    </div>
                    
                    
                    <div id="mensaje-seleccion" class="mensaje-seleccion">
                        <p>Seleccione un área en el mapa para ver su información</p>
                    </div>

                
                    <div id="panel-edicion" class="seccion-panel">
                        <h3 id="titulo-area">Área seleccionada</h3>
                 
                        <div class="seccion-panel subdivisiones" style="display: none;"> 
                            <h3>Subdivisiones</h3>
                            <div id="lista-subdivisiones" class="lista-subdivisiones">
                                <p class="mensaje-seleccion">Seleccione un área para ver sus subdivisiones</p>
                            </div>
                        </div>
                        
                        <div class="seccion-panel responsables">
                            <h3>Responsables del Área</h3>
                            <div class="lista-responsables">
                                <textarea rows="6" id="responsables-area" placeholder="Ingrese los responsables del área"></textarea>
                            <div class="botones-acciones">
                                <button class="btn-eliminar">Eliminar</button>
                                <button class="btn-anadir">+ Añadir</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="seccion-panel personal">
                            <h3>Personal</h3>
                            <div class="lista-personal">
                                <textarea rows="6" id="personal-area" placeholder="Ingrese el personal del área"></textarea>
                            <div class="botones-acciones">
                                <button class="btn-eliminar">Eliminar</button>
                                <button class="btn-anadir">+ Añadir</button>
                                </div>
                            </div>
                        </div>

                      
                        <div id="seccion-horario" class="seccion-panel" style="display: none;">
                            <h3>Horario del Aula</h3>
                            <div class="botones-acciones">
                                <button id="btn-editar-horario" class="btn-editar-horario">
                                    <i class="fas fa-calendar-alt"></i> Editar Horario
                                </button>
                            </div>
                        </div>

                        <div class="botones-panel">
   
                            <button class="btn-guardar" onclick="guardarCambios()">
                                <i class="fas fa-save"></i>
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        

        <div id="modal-filtro" class="modal-base" style="display: none;">
            <div class="modal-contenido-amplio">
                <div class="modal-encabezado">
                    <h3>Filtro de Áreas y Subdivisiones</h3>
                    <button class="btn-cerrar-modal" id="btn-cerrar-filtro-modal">&times;</button>
                </div>
                <div class="modal-cuerpo" id="filtro-lista-elementos">

                    <p>Cargando lista...</p>
                </div>
            </div>
        </div>
        
     
        <div id="modalConfirmacionGuardar" class="modal-confirmacion">
            <div class="modal-confirmacion-contenido">
                <div class="modal-confirmacion-encabezado">
                    <h3>Confirmar Guardado</h3>
                    <button class="btn-cerrar-modal">&times;</button>
                </div>
                <div class="modal-confirmacion-cuerpo">
                    <p>¿Está seguro de que desea guardar los cambios realizados?</p>
                </div>
                <div class="modal-confirmacion-pie">
                    <button class="btn-cancelar" onclick="cerrarModalConfirmacionGuardar()">Cancelar</button>
                    <button class="btn-confirmar" onclick="confirmarGuardar()">Guardar</button>
                </div>
            </div>
        </div>
        
     
        <div id="modalAdvertenciaCambios" class="modal-confirmacion">
            <div class="modal-confirmacion-contenido">
                <div class="modal-confirmacion-encabezado">
                    <h3>Cambios sin Guardar</h3>
                    <button class="btn-cerrar-modal">&times;</button>
                </div>
                <div class="modal-confirmacion-cuerpo">
                    <p>Tiene cambios sin guardar. ¿Desea continuar sin guardar los cambios?</p>
                </div>
                <div class="modal-confirmacion-pie">
                    <button class="btn-cancelar" onclick="cancelarCambioArea()">Cancelar</button>
                    <button class="btn-confirmar" onclick="continuarSinGuardar()">Continuar</button>
                </div>
            </div>
        </div>
        
        <script src="../js/mapa_interactivo.js"></script>
        <script>
    
        let areaSeleccionadaActual = null;
        let cambiosSinGuardar = false;
        let areaSeleccionadaId = null;
        let subdivisionesArea = [];
        
    
        document.addEventListener('DOMContentLoaded', function() {
           
            
        
            document.addEventListener('areaSeleccionada', function(e) {
          
                if (cambiosSinGuardar && areaSeleccionadaId !== null) {
                 
                    const nuevaAreaId = e.detail.id;
                    
                   
                    mostrarModalAdvertenciaCambios(nuevaAreaId);
                    return;
                }
                
   
                seleccionarArea(e.detail);
            });
            
       
            document.getElementById('nombre_planta').addEventListener('input', function() {
                cambiosSinGuardar = true;
            });
            
         
            document.getElementById('tipo_planta').addEventListener('change', function() {
                const seccionHorario = document.getElementById('seccion-horario');
                const tituloArea = document.getElementById('titulo-area');
                
        
                cambiosSinGuardar = true;
                
     
                if (tituloArea.textContent !== 'Área seleccionada' && 
                    this.value === 'aula' && 
                    document.getElementById('planta_id').value.includes('Sub')) {
                    seccionHorario.style.display = 'block';
                } else {
                    seccionHorario.style.display = 'none';
                }
            });
            
           
            document.querySelectorAll('.btn-anadir').forEach(btn => {
                btn.addEventListener('click', function() {
                    cambiosSinGuardar = true;
                });
            });
            
            document.querySelectorAll('.btn-eliminar').forEach(btn => {
                btn.addEventListener('click', function() {
                    cambiosSinGuardar = true;
                });
            });
            
      
            document.querySelectorAll('textarea').forEach(textarea => {
                textarea.addEventListener('input', function() {
                    cambiosSinGuardar = true;
                });
            });
        });
        
        function seleccionarArea(detalleArea) {
            const mensajeSeleccion = document.getElementById('mensaje-seleccion');
            const panelEdicion = document.getElementById('panel-edicion');
            const tituloArea = document.getElementById('titulo-area');
            const seccionHorario = document.getElementById('seccion-horario');
            const tipoSelect = document.getElementById('tipo_planta');
            
   
            document.getElementById('planta_id').value = detalleArea.id;
   
            
        
            areaSeleccionadaId = detalleArea.id;
            
           
            if(tipoSelect) tipoSelect.value = '';

            if (areaSeleccionadaActual) {
                areaSeleccionadaActual.classList.remove('seleccionada');
            }

           
            const nuevaAreaSeleccionada = document.querySelector(`.area-interactiva[data-id="${detalleArea.id}"]`); 
            if (nuevaAreaSeleccionada) {
                nuevaAreaSeleccionada.classList.add('seleccionada');
                areaSeleccionadaActual = nuevaAreaSeleccionada;
            }

         

            if(tituloArea) tituloArea.textContent = 'Cargando...'; 

   
            cargarDatosSubdivision(detalleArea.id);
            
            
            cambiosSinGuardar = false;
        }
        
        function cargarDatosSubdivision(dataId) {
            console.log("Iniciando carga de datos para dataId:", dataId);
            const mensajeRegistro = document.getElementById('mensaje-registro');
            const mensajeRegistroTexto = document.getElementById('mensaje-registro-texto');
            const panelEdicion = document.getElementById('panel-edicion');
            const mensajeSeleccion = document.getElementById('mensaje-seleccion');

            
            if (panelEdicion) panelEdicion.style.display = 'none'; 
            if (mensajeSeleccion) mensajeSeleccion.style.display = 'none';
            if (mensajeRegistro && mensajeRegistroTexto) {
            mensajeRegistroTexto.textContent = 'Cargando datos de la subdivisión...';
            mensajeRegistro.className = 'mensaje-registro';
            mensajeRegistro.style.display = 'block';
            } else {
                console.error("Elementos de mensaje de registro no encontrados.");
            }
            
          
            const formData = new FormData();
            formData.append('accion', 'obtener_subdivision');
            formData.append('data_id', dataId);
            
            fetch('../ajax/mapa_interactivo_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Datos recibidos:", data); 

                if (data.exito && data.datos) {
                    const sub = data.datos;
                    
                    
                    if (panelEdicion) {
                       
                        panelEdicion.style.display = 'block';
                        console.log("Panel de edición visible.");

                       
                        const tituloArea = document.getElementById('titulo-area');
                        if (tituloArea) {
                            tituloArea.textContent = sub.nombre || 'Área seleccionada';
                        } else { console.error("Elemento titulo-area no encontrado."); }
                        
                       
                        const nombrePlantaInput = document.getElementById('nombre_planta');
                        if (nombrePlantaInput) {
                            nombrePlantaInput.value = sub.nombre || ''; // <<--- Asegurar esta línea
                            
                            // CAMBIO: verificar si estamos en modo edición y deshabilitar el campo de nombre si no lo estamos
                            if (window.mapaInteractivo && typeof window.mapaInteractivo.estaEnModoEdicion === 'function') {
                                const esModoEdicion = window.mapaInteractivo.estaEnModoEdicion();
                                nombrePlantaInput.readOnly = !esModoEdicion;
                                console.log(`Campo de nombre ${esModoEdicion ? 'habilitado' : 'deshabilitado'} según modo edición`);
                            } else {
                                
                                nombrePlantaInput.readOnly = true;
                                console.warn("No se pudo determinar el modo de edición. Campo de nombre deshabilitado por seguridad.");
                            }
                        } else { console.error("Elemento nombre_planta no encontrado."); }

                        
                        const tipoSelect = document.getElementById('tipo_planta');
                        if (tipoSelect) {
                          
                            tipoSelect.querySelectorAll('option[data-dynamically-added="inactive"]').forEach(opt => opt.remove());

                            const tipoIdSubdivision = sub.tipo_id !== undefined && sub.tipo_id !== null ? sub.tipo_id.toString() : '';
                            const tipoActivo = sub.tipo_activo !== undefined ? sub.tipo_activo : true; 
                            const tipoNombre = sub.tipo_nombre || 'Desconocido';

                          
                            if (!tipoActivo && tipoIdSubdivision !== '') {
                                let opcionActivaExiste = false;
                                
                                for (let i = 0; i < tipoSelect.options.length; i++) {
                                    
                                    if (!tipoSelect.options[i].dataset.dynamicallyAdded && tipoSelect.options[i].value === tipoIdSubdivision) {
                                        opcionActivaExiste = true;
                                        break;
                                    }
                                }

                                
                                if (!opcionActivaExiste) {
                                    console.log(`Tipo ${tipoIdSubdivision} (${tipoNombre}) está inactivo. Añadiendo opción temporal.`);
                                    const optionInactiva = document.createElement('option');
                                    optionInactiva.value = tipoIdSubdivision;
                                    optionInactiva.textContent = `${tipoNombre} (Inactivo)`;
                                    optionInactiva.disabled = true; 
                                    optionInactiva.dataset.dynamicallyAdded = 'inactive'; 
                                    tipoSelect.appendChild(optionInactiva);
                                }
                            }

                           
                            tipoSelect.value = tipoIdSubdivision;
                            
                            
                            if (tipoSelect.value !== tipoIdSubdivision) {
                                
                                if (!tipoActivo && tipoIdSubdivision !== '') {
                                    console.warn(`El tipo ${tipoNombre} está inactivo y no se puede seleccionar directamente.`);
                                }
                              
                                else if (tipoIdSubdivision !== '') {
                                     console.warn(`No se pudo seleccionar el tipo ID ${tipoIdSubdivision}. ¿Existe en la lista de tipos activos?`);
                                     tipoSelect.value = ''; 
                                }
                                
                            }

                         
                            if (window.mapaInteractivo && typeof window.mapaInteractivo.estaEnModoEdicion === 'function') {
                                const esModoEdicion = window.mapaInteractivo.estaEnModoEdicion();
                                tipoSelect.disabled = !esModoEdicion;
                                console.log(`Selector de tipo ${esModoEdicion ? 'habilitado' : 'deshabilitado'} según modo edición`);
                            } else {
                            
                                tipoSelect.disabled = true;
                                console.warn("No se pudo determinar el modo de edición. Selector de tipo deshabilitado por seguridad.");
                            }

                        } else { console.error("Elemento tipo_planta no encontrado."); }
                    
                      
                        const seccionResponsables = panelEdicion.querySelector('.seccion-panel.responsables');
                        const seccionPersonal = panelEdicion.querySelector('.seccion-panel.personal');
                        if (seccionResponsables) seccionResponsables.style.display = 'block'; else console.error("Sección responsables no encontrada.");
                        if (seccionPersonal) seccionPersonal.style.display = 'block'; else console.error("Sección personal no encontrada.");

                      
                        const responsablesArea = document.getElementById('responsables-area');
                        if (responsablesArea) {
                    if (sub.responsables && sub.responsables.length > 0) {
                        const responsablesText = sub.responsables.map(r => 
                                    `${r.nombre || ''} ${r.apellido || ''} (${r.cargo || 'N/A'})`
                                ).join('\\n');
                                responsablesArea.value = responsablesText;
                                console.log("Responsables actualizados.");
                    } else {
                                responsablesArea.value = '';
                                console.log("No hay responsables para mostrar.");
                    }
                        } else { console.error("Elemento responsables-area no encontrado."); }
                    
             
                        const personalArea = document.getElementById('personal-area');
                        if (personalArea) {
                    if (sub.personal && sub.personal.length > 0) {
                        const personalText = sub.personal.map(p => 
                                    `${p.nombre || ''} ${p.apellido || ''} (${p.cargo || 'N/A'})`
                                ).join('\\n');
                                personalArea.value = personalText;
                                console.log("Personal actualizado.");
                    } else {
                                personalArea.value = '';
                                console.log("No hay personal para mostrar.");
                    }
                        } else { console.error("Elemento personal-area no encontrado."); }
                    
                   
                    const seccionHorario = document.getElementById('seccion-horario');
                        if (seccionHorario) {
                           
                            if (sub.tipo_id == 1 || sub.tipo_nombre?.toLowerCase() === 'aula') { 
                        seccionHorario.style.display = 'block';
                                const btnHorario = document.getElementById('btn-editar-horario');
                                if (btnHorario) {
                                    btnHorario.onclick = function() {
                            abrirModalHorario(sub.id, sub.nombre);
                        };
                                } else { console.error("Botón editar horario no encontrado."); }
                                console.log("Sección horario visible.");
                    } else {
                        seccionHorario.style.display = 'none';
                                console.log("Sección horario oculta.");
                            }
                        } else { console.error("Elemento seccion-horario no encontrado."); }
                        
                  

                    } else {
                         console.error("El panel de edición (panel-edicion) no se encontró en el DOM.");
                    }

                 
                    if (mensajeRegistro && mensajeRegistroTexto) {
                    mensajeRegistroTexto.textContent = 'Datos cargados correctamente';
                    mensajeRegistro.className = 'mensaje-registro exito';
                    setTimeout(() => {
                        mensajeRegistro.style.display = 'none';
                    }, 3000);
                    }
                } else {
                    console.error('Error en la respuesta AJAX:', data.mensaje || 'No se recibieron datos válidos.');
                    if (mensajeRegistro && mensajeRegistroTexto) {
                        mensajeRegistroTexto.textContent = 'Error: ' + (data.mensaje || 'No se pudieron cargar los datos.');
                    mensajeRegistro.className = 'mensaje-registro error';
                      
                    }
                 
                     if(mensajeSeleccion) mensajeSeleccion.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error en la solicitud fetch:', error);
                 if (mensajeRegistro && mensajeRegistroTexto) {
                    mensajeRegistroTexto.textContent = 'Error de conexión al cargar los datos.';
                mensajeRegistro.className = 'mensaje-registro error';
                 }
              
                 if(mensajeSeleccion) mensajeSeleccion.style.display = 'block';
            });
        }

        function guardarCambios() {
           
            if (!cambiosSinGuardar) {
                alert("No hay cambios para guardar.");
                return;
            }
          
            document.getElementById('modalConfirmacionGuardar').style.display = 'flex';
        }
        
        function confirmarGuardar() {
            console.log('Intentando guardar cambios...');
            const dataId = document.getElementById('planta_id').value; 
            const nuevoNombre = document.getElementById('nombre_planta').value;
            const nuevoTipoId = document.getElementById('tipo_planta').value;

     
            if (!dataId) {
                alert("No se ha seleccionado ninguna subdivisión para guardar.");
             cerrarModalConfirmacionGuardar();
                return;
            }
            if (!nuevoNombre.trim()) {
                alert("El nombre no puede estar vacío.");
 
                return;
            }
            if (!nuevoTipoId) {
                 console.warn("El tipo no está seleccionado, se guardará sin tipo o con el valor por defecto del backend.");

             }

            const formData = new FormData();
            formData.append('accion', 'actualizar_subdivision_info'); 
            formData.append('data_id', dataId);
            formData.append('nombre', nuevoNombre);
            formData.append('tipo_id', nuevoTipoId); 

            console.log('Enviando datos para guardar:', { data_id: dataId, nombre: nuevoNombre, tipo_id: nuevoTipoId });

  
            fetch('../ajax/mapa_interactivo_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                  
                     return response.text().then(text => { 
                         throw new Error(`Error HTTP ${response.status}: ${text || response.statusText}`); 
                     });
                 }
                 return response.json();
             })
            .then(data => {
                console.log("Respuesta del servidor al guardar:", data);
                if (data.exito) {
         
                    alert('Cambios guardados correctamente.');
            
            cambiosSinGuardar = false;

     
                    const elementoSubdivisionSVG = document.querySelector(`path.area-interactiva[data-id="${dataId}"]`); 
                    if (elementoSubdivisionSVG) {
                        let tituloSvg = elementoSubdivisionSVG.querySelector('title');
                        if (tituloSvg) {
                            tituloSvg.textContent = nuevoNombre;
                            console.log(`Tooltip SVG para ${dataId} actualizado a: ${nuevoNombre}`);
                        } else {
                          
                            console.warn(`Elemento <title> no encontrado para subdivisión SVG ${dataId}, creando uno nuevo.`);
                            tituloSvg = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                            tituloSvg.textContent = nuevoNombre;
                            elementoSubdivisionSVG.appendChild(tituloSvg);
                        }
                    } else {
                         console.warn(`Elemento SVG path.area-interactiva con data-id ${dataId} no encontrado para actualizar tooltip.`);
                    }

                  
                    const tituloPanel = document.getElementById('titulo-area');
                    if(tituloPanel) tituloPanel.textContent = nuevoNombre;
                    
                  
                    const nombrePlantaInput = document.getElementById('nombre_planta');
                    if(nombrePlantaInput) nombrePlantaInput.value = nuevoNombre;

                } else {
                 
                    alert('Error al guardar los cambios: ' + (data.mensaje || 'Error desconocido.'));
                }
            })
            .catch(error => {
                console.error('Error en la solicitud fetch para guardar:', error);
                alert('Error de conexión al intentar guardar los cambios. Detalles: ' + error.message);
            })
            .finally(() => {
     
                 cerrarModalConfirmacionGuardar();
             });
        }
        
        function cerrarModalConfirmacionGuardar() {
            document.getElementById('modalConfirmacionGuardar').style.display = 'none';
        }
        
        function mostrarModalAdvertenciaCambios(nuevaAreaId) {

            const modal = document.getElementById('modalAdvertenciaCambios');
            modal.setAttribute('data-nueva-area-id', nuevaAreaId);
            
      
            modal.style.display = 'flex';

         
            modal.onclick = function(event) {
                if (event.target === modal) {
                    cancelarCambioArea(); 
                }
            };

   
             const btnCancelar = modal.querySelector('.btn-cancelar');
             const btnConfirmar = modal.querySelector('.btn-confirmar');
             const btnCerrar = modal.querySelector('.btn-cerrar-modal'); 

             if(btnCancelar) btnCancelar.onclick = cancelarCambioArea;
             if(btnConfirmar) btnConfirmar.onclick = continuarSinGuardar;
             if(btnCerrar) btnCerrar.onclick = cancelarCambioArea; 
        }
        
        function cancelarCambioArea() {
            // Cerrar el modal
            const modal = document.getElementById('modalAdvertenciaCambios');
            if (modal) {
                 modal.style.display = 'none';
                 modal.onclick = null; 
            }
        }
        
        function continuarSinGuardar() {
            const modal = document.getElementById('modalAdvertenciaCambios');
            const nuevaAreaId = modal.getAttribute('data-nueva-area-id');
            
            if (!nuevaAreaId) {
                console.error("No se pudo obtener el ID de la nueva área desde el modal.");
                cancelarCambioArea(); 
                return;
            }

           
            cambiosSinGuardar = false;
            
           
            cancelarCambioArea();
            
       
            const nuevoElementoArea = document.querySelector(`[data-id="${nuevaAreaId}"]`);
            let nuevoNombreArea = 'Área ' + nuevaAreaId; 
            if (nuevoElementoArea) {
          
                const tituloSvg = nuevoElementoArea.querySelector('title');
                 if (tituloSvg) {
                     nuevoNombreArea = tituloSvg.textContent;
                 } else if (nuevoElementoArea.hasAttribute('title')) {
                     nuevoNombreArea = nuevoElementoArea.getAttribute('title');
                 } else {
                
                    console.warn("No se encontró título para el área", nuevaAreaId);
                 }
            }
            
        
            const detalleNuevaArea = {
                id: nuevaAreaId,
                nombre: nuevoNombreArea
                
            };
            
            console.log("Continuando sin guardar, seleccionando:", detalleNuevaArea);
            
         
            seleccionarArea(detalleNuevaArea);
        }

        async function abrirModalHorario(id, nombre) {
            try {
                const modalExistente = document.getElementById('modalHorario');
                if (modalExistente) {
                    modalExistente.remove();
                }

                const modalHTML = `
                <div id="modalHorario" class="modal">
                    <div class="modal-contenido" style="width: 90%; height: 90%; max-width: 1200px; padding: 0;">
                        <div class="modal-encabezado" style="padding: 15px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
                            <h2>Horario de ${nombre}</h2>
                            <button class="btn-cerrar" style="font-size: 24px; background: none; border: none; cursor: pointer;">&times;</button>
                        </div>
                        <div class="modal-cuerpo" style="height: calc(100% - 60px); padding: 0;">
                            <iframe src="../form/gestion/editar_horario.php?periodo=2025-1&curso=${encodeURIComponent(nombre)}&iframe=true" 
                                    style="width: 100%; height: 100%; border: none;"></iframe>
                        </div>
                    </div>
                </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                
                const modal = document.getElementById('modalHorario');
                if (modal) {
                    modal.style.display = 'flex';
                    modal.querySelector('.btn-cerrar').onclick = cerrarModalHorario;
                    modal.onclick = e => {
                        if (e.target === modal) cerrarModalHorario();
                    };
                }
            } catch (error) {
                console.error('Error al cargar el modal de horario:', error);
            }
        }

        function cerrarModalHorario() {
            const modal = document.getElementById('modalHorario');
            if (modal) {
                modal.style.display = 'none';
                modal.remove();
            }
        }

        async function abrirModal() {
            try {

                const modalExistente = document.getElementById('modalTipos');
                if (modalExistente) {
                    modalExistente.remove();
                }
                 const modalNuevoTipoExistente = document.getElementById('modalNuevoTipo');
                 if (modalNuevoTipoExistente) {
                     modalNuevoTipoExistente.remove();
                }

                const response = await fetch('editar_tipoplanta.php'); // Ruta corregida
                if (!response.ok) {
                    throw new Error(`Error al cargar el modal: ${response.status} ${response.statusText}`);
                }
                const html = await response.text();
                document.body.insertAdjacentHTML('beforeend', html);
                
                const modal = document.getElementById('modalTipos');
                const modalNuevoTipo = document.getElementById('modalNuevoTipo');
                
                if (modal) {
                    modal.style.display = 'flex';
                    
             
                    modal.querySelector('#btnCerrarModalTipos')?.addEventListener('click', cerrarModal); 
                    modal.querySelector('#btnAnadirTipo')?.addEventListener('click', abrirModalNuevoTipo);
                    modal.querySelector('#btnEditarTipo')?.addEventListener('click', abrirModalEditarTipo); 
                    modal.querySelector('#btnHabilitarTipo')?.addEventListener('click', habilitarTiposSeleccionados);
                    modal.querySelector('#btnInhabilitarTipo')?.addEventListener('click', inhabilitarTiposSeleccionados);
   
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            cerrarModal(); 
                        }
                    });

               
                    if (typeof inicializarLogicaModalTipos === 'function') {
                        inicializarLogicaModalTipos();
                    } else {
                        console.error("La función inicializarLogicaModalTipos no está definida.");
                    }

                }
                
                if (modalNuevoTipo) {
                   
                     modalNuevoTipo.querySelector('#btnCerrarModalNuevoTipo')?.addEventListener('click', cerrarModalNuevoTipo);
                     modalNuevoTipo.querySelector('#btnCancelarNuevoTipo')?.addEventListener('click', cerrarModalNuevoTipo);
                     modalNuevoTipo.querySelector('#btnGuardarNuevoTipo')?.addEventListener('click', guardarNuevoTipo);
                }
                
            } catch (error) {
                console.error('Error al cargar o inicializar el modal de tipos:', error);
                alert('No se pudo abrir el editor de tipos.');
            }
        }

        function cerrarModal() {
            const modal = document.getElementById('modalTipos');
            if (modal) {
                modal.remove();
            }
            const modalNuevoTipo = document.getElementById('modalNuevoTipo');
            if (modalNuevoTipo) {
                modalNuevoTipo.remove();
            }
        }

        async function abrirModalReporte() {
            try {
                const modalExistente = document.getElementById('modalReportes');
                if (modalExistente) {
                    modalExistente.remove();
                }

                const response = await fetch('ver_reporteplanta.php');
                const html = await response.text();
                document.body.insertAdjacentHTML('beforeend', html);
                
                const modal = document.getElementById('modalReportes');
                if (modal) {
                    modal.style.display = 'flex';
                    modal.querySelector('.btn-cerrar').onclick = cerrarModalReporte;
                    modal.onclick = e => {
                        if (e.target === modal) cerrarModalReporte();
                    };
                }
            } catch (error) {
                console.error('Error al cargar el modal de reportes:', error);
            }
        }

        function cerrarModalReporte() {
            const modal = document.getElementById('modalReportes');
            if (modal) {
                modal.style.display = 'none';
                modal.remove();
            }
        }

        // Cerrar modales con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
                cerrarModalReporte();
                cerrarModalHorario();
                cerrarModalConfirmacionGuardar();
                // Cerrar modal de advertencia
                const modalAdvertencia = document.getElementById('modalAdvertenciaCambios');
                if (modalAdvertencia && modalAdvertencia.style.display === 'flex') {
                    cancelarCambioArea();
                }
                // Cerrar modal de filtro
                const modalFiltro = document.getElementById('modal-filtro');
                if (modalFiltro && modalFiltro.style.display === 'flex') {
                    window.mapaInteractivo.cerrarModalFiltro(); 
                }
            }
        });

       
        
 
        function inicializarLogicaModalTipos() {
            console.log("Inicializando lógica del modal..."); 
            const modal = document.getElementById('modalTipos');
            if (!modal) {
                console.error("Modal #modalTipos no encontrado al inicializar.");
                return; 
            }

            const checkboxes = modal.querySelectorAll('.checkbox-fila-tipo'); 
            const btnAnadir = modal.querySelector('#btnAnadirTipo');
            const btnEditar = modal.querySelector('#btnEditarTipo');
            const btnHabilitar = modal.querySelector('#btnHabilitarTipo');
            const btnInhabilitar = modal.querySelector('#btnInhabilitarTipo');
    
            function actualizarVisibilidadBotones() {
                console.log("Actualizando visibilidad de botones..."); 
                const seleccionados = modal.querySelectorAll('.checkbox-fila-tipo:checked');
                const numSeleccionados = seleccionados.length;
                let todosActivos = numSeleccionados > 0; 
                let todosInactivos = numSeleccionados > 0;

                if (numSeleccionados > 0) {
                    seleccionados.forEach((cb, index) => { 
                        const fila = cb.closest('tr');
                        if (!fila || fila.cells.length < 4) {
                            console.error(`Error: No se encontró fila o celdas insuficientes para checkbox ${index}`);
                            return; 
                        }
                        const estadoTexto = fila.cells[3].textContent.trim().toLowerCase(); 
                        console.log(` Fila ${index+1} (${cb.dataset.id}): Estado leído: '${estadoTexto}'`); 
                        if (estadoTexto !== 'activo') {
                            todosActivos = false;
                        }
                        if (estadoTexto !== 'inactivo') {
                            todosInactivos = false;
                        }
                    });
                } else {
                    todosActivos = false;
                    todosInactivos = false;
                }
                
                console.log(` Resumen -> Seleccionados: ${numSeleccionados}, Todos Activos: ${todosActivos}, Todos Inactivos: ${todosInactivos}`); 

                const displayAnadir = (numSeleccionados === 0) ? 'inline-block' : 'none';
                const displayEditar = (numSeleccionados === 1) ? 'inline-block' : 'none';
                const displayHabilitar = (numSeleccionados > 0 && todosInactivos) ? 'inline-block' : 'none';
                const displayInhabilitar = (numSeleccionados > 0 && todosActivos) ? 'inline-block' : 'none';
                
                console.log(` Botones -> Añadir: ${displayAnadir}, Editar: ${displayEditar}, Habilitar: ${displayHabilitar}, Inhabilitar: ${displayInhabilitar}`); 

                if (btnAnadir) btnAnadir.style.display = displayAnadir;
                if (btnEditar) btnEditar.style.display = displayEditar;
                if (btnHabilitar) btnHabilitar.style.display = displayHabilitar;
                if (btnInhabilitar) btnInhabilitar.style.display = displayInhabilitar;
            }
            
        
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    console.log(`Evento change en checkbox ID: ${this.dataset.id}`); 
                    const fila = this.closest('tr');
                    if (fila) {
                        fila.classList.toggle('seleccionada', this.checked);
                    }
                    actualizarVisibilidadBotones(); 
                });
            });
            
   
            actualizarVisibilidadBotones(); 
        }

        function abrirModalNuevoTipo() {
             console.log("Abriendo modal nuevo tipo...");
             const modalNuevo = document.getElementById('modalNuevoTipo');
            if (modalNuevo) {
                document.getElementById('nuevoTipoNombre').value = '';
                document.getElementById('nuevoTipoDescripcion').value = '';
                modalNuevo.style.display = 'flex';
            }
        }

        function cerrarModalNuevoTipo() {
             console.log("Cerrando modal nuevo tipo...");
             const modalNuevo = document.getElementById('modalNuevoTipo');
            if (modalNuevo) {
                modalNuevo.style.display = 'none';
  
            }
        }
        
        // Placeholder para abrir modal de Editar
        function abrirModalEditarTipo() {
            alert('Funcionalidad Editar pendiente.');
             // 1. Obtener el ID del único checkbox seleccionado
             // 2. Hacer fetch para obtener datos actuales del tipo
             // 3. Llenar el modal #modalNuevoTipo con esos datos
             // 4. Cambiar título y texto del botón Guardar a "Guardar Cambios"
             // 5. Añadir un campo oculto o data-attribute con el ID a editar
             // 6. Mostrar el modal #modalNuevoTipo
        }

       
        function habilitarTiposSeleccionados() {
             // alert('Funcionalidad Habilitar pendiente.');
             cambiarEstadoTipos(true);
        }
        
        // Placeholder para inhabilitar
        function inhabilitarTiposSeleccionados() {
             // alert('Funcionalidad Inhabilitar pendiente.');
              cambiarEstadoTipos(false);
        }

        // --- Nueva función AJAX para cambiar estado ---
        function cambiarEstadoTipos(habilitar) {
            const modal = document.getElementById('modalTipos');
            if (!modal) return;

            const seleccionados = modal.querySelectorAll('.checkbox-fila-tipo:checked');
            if (seleccionados.length === 0) {
                alert('Por favor, seleccione al menos un tipo.');
                return;
            }

            const ids = Array.from(seleccionados).map(cb => cb.dataset.id);
            const accion = habilitar ? 'habilitar_tipos' : 'inhabilitar_tipos';
            const confirmMsg = habilitar ? '¿Está seguro de que desea habilitar los tipos seleccionados?' : '¿Está seguro de que desea inhabilitar los tipos seleccionados?';

            if (!confirm(confirmMsg)) {
                return;
            }

            const formData = new FormData();
            formData.append('accion', accion);
            
            ids.forEach(id => formData.append('ids[]', id)); 

            fetch('../ajax/tipos_area_ajax.php', { 
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                alert(data.mensaje);
                if (data.exito) {
                    
                    recargarContenidoModalTipos(); 
                }
            })
            .catch(error => {
                console.error('Error en la solicitud AJAX:', error);
                alert('Ocurrió un error al procesar la solicitud.');
            });
        }

      
        async function recargarContenidoModalTipos() {
            try {
          
                await abrirModal(); 
            } catch (error) {
                console.error("Error al recargar el modal de tipos:", error);
                alert("No se pudo actualizar la lista de tipos.");
            }
        }
        
      
        function guardarNuevoTipo() {
             alert('Funcionalidad Guardar pendiente.');
             // 1. Obtener nombre y descripción
             // 2. Determinar si es nuevo o edición (buscar ID oculto/data-attribute)
             // 3. Hacer llamada AJAX (acción: guardar_tipo_nuevo o guardar_tipo_editado)
             // 4. cerrarModalNuevoTipo();
             // 5. Actualizar tabla o recargar modal/página
        }

        // --- FIN: Lógica movida desde editar_tipoplanta.php ---

        </script>
        <?php
    }
}

$gestionMapa = new GestionMapa($conn ?? null); 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Mapa</title>
    <link rel="stylesheet" href="../css/empleado_dashboard.css">
    <link rel="stylesheet" href="../css/empleado_sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php include '../includes/empleado_sidebar.php'; ?>
        
        <div class="main-content">
            <?php $gestionMapa->mostrarFormulario(); ?>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <h2>Confirmar Cierre de Sesión</h2>
            <p>¿Estás seguro que deseas cerrar la sesión?</p>
            <div class="modal-buttons">
                <button id="confirmLogout" class="btn-confirm">Sí, cerrar sesión</button>
                <button id="cancelLogout" class="btn-cancel">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
    const modal = document.getElementById('logoutModal');
    const logoutLink = document.querySelector('.logout a');
    const confirmBtn = document.getElementById('confirmLogout');
    const cancelBtn = document.getElementById('cancelLogout');

    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            modal.style.display = 'flex';
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            window.location.href = '../logout.php';
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    const sidebarToggle = document.getElementById('sidebarToggle');
    const container = document.querySelector('.container');
    const sidebar = document.querySelector('.sidebar');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            container.classList.toggle('sidebar-collapsed');
            const isCollapsed = container.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
    }

    window.addEventListener('load', () => {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (isCollapsed) {
            container.classList.add('sidebar-collapsed');
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const menuSections = document.querySelectorAll('.menu-section');
        
        menuSections.forEach(section => {
            const sectionTitle = section.querySelector('.section-title');
            
            if (sectionTitle) {
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
            }
        });
        

        const employeeName = document.querySelector('.employee-name');
        if (employeeName) {
            employeeName.textContent = "<?php echo isset($_SESSION['nombre']) && isset($_SESSION['apellido']) ? $_SESSION['nombre'] . ' ' . $_SESSION['apellido'] : 'Usuario'; ?>";
        }
    });
    </script>

    <?php include '../includes/chatbot.php'; ?>
</body>
</html>
