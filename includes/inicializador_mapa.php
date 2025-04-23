<?php

if (realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) {
  
    require_once 'conexion.php';
    require_once 'registrar_auditoria.php';
    
  
    $inicializador_id = isset($_GET['id']) ? intval($_GET['id']) : null;
    $respuesta = ['exito' => false, 'mensaje' => ''];
    
   
    if ($inicializador_id) {
        try {
            if ($conn) {
               
                $stmt = $conn->prepare("SELECT * FROM inicializadores_mapa WHERE id = :id AND activo = TRUE");
                $stmt->bindParam(':id', $inicializador_id, PDO::PARAM_INT);
                $stmt->execute();
                $inicializador = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($inicializador) {
                   
                    $inicializadorMapa = new InicializadorMapa($conn);
                    $resultado = $inicializadorMapa->ejecutarInicializador($inicializador);
                    
                  
                    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 1; 
                    registrarEjecucionInicializador($conn, $usuario_id, $inicializador_id, $resultado ? 'Éxito' : 'Error');
                    
                    $respuesta['exito'] = $resultado;
                    $respuesta['mensaje'] = $resultado ? 
                        "Inicializador '{$inicializador['nombre']}' ejecutado correctamente" : 
                        "Error al ejecutar el inicializador '{$inicializador['nombre']}'";
                } else {
                    $respuesta['mensaje'] = "No se encontró el inicializador o está inactivo";
                }
            } else {
                $respuesta['mensaje'] = "Error: No hay conexión a la base de datos";
            }
        } catch (Exception $e) {
            $respuesta['mensaje'] = "Error: " . $e->getMessage();
        }
    } else {
        $respuesta['mensaje'] = "Error: No se proporcionó un ID de inicializador";
    }
    
   
    header('Content-Type: application/json');
    echo json_encode($respuesta);
    exit;
}

require_once 'conexion.php';

class InicializadorMapa {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Ejecuta un inicializador específico
     * @param array $inicializador 
     * @return boolean 
     */
    public function ejecutarInicializador($inicializador) {
        try {
            // Decodificar parámetros del inicializador
            $parametros = json_decode($inicializador['parametros'], true);
            
            // Determinar qué tipo de inicializador ejecutar según el nombre
            if (strpos(strtolower($inicializador['nombre']), 'area') !== false) {
                return $this->inicializarAreas($parametros);
            } else {
                return false;
            }
        } catch (Exception $e) {
            error_log("Error al ejecutar inicializador: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Inicializa las áreas básicas del mapa
     * @param array $parametros 
     * @return boolean 
     */
    private function inicializarAreas($parametros) {
        try {
           
            $stmt = $this->conn->prepare("SELECT id FROM mapas WHERE nombre LIKE '%Principal%' LIMIT 1");
            $stmt->execute();
            $mapa = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$mapa) {
                
                $stmt = $this->conn->prepare("
                    INSERT INTO mapas (nombre, descripcion) 
                    VALUES ('Mapa Principal del Centro Educativo', 'Mapa principal con todas las áreas')
                    RETURNING id
                ");
                $stmt->execute();
                $mapa = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            $mapaId = $mapa['id'];
            
           
            $stmt = $this->conn->prepare("SELECT id FROM tipos_area WHERE nombre = 'edificio' LIMIT 1");
            $stmt->execute();
            $tipoEdificio = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tipoEdificio) {
                
                $stmt = $this->conn->prepare("
                    INSERT INTO tipos_area (nombre, descripcion, activo) 
                    VALUES ('edificio', 'Estructura principal', true)
                    RETURNING id
                ");
                $stmt->execute();
                $tipoEdificio = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            $tipoId = $tipoEdificio['id'];
            
            
            foreach ($parametros['areas'] as $area) {
               
                $stmt = $this->conn->prepare("
                    SELECT id FROM areas_mapa 
                    WHERE mapa_id = :mapa_id AND nombre = :nombre
                    LIMIT 1
                ");
                $stmt->bindParam(':mapa_id', $mapaId);
                $stmt->bindParam(':nombre', $area);
                $stmt->execute();
                $areaExistente = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$areaExistente) {
                 
                    $dataId = strtolower(str_replace(' ', '-', $area));
                    $color = $this->generarColorAleatorio();
                    
                    $stmt = $this->conn->prepare("
                        INSERT INTO areas_mapa (mapa_id, nombre, tipo_id, data_id, color) 
                        VALUES (:mapa_id, :nombre, :tipo_id, :data_id, :color)
                    ");
                    $stmt->bindParam(':mapa_id', $mapaId);
                    $stmt->bindParam(':nombre', $area);
                    $stmt->bindParam(':tipo_id', $tipoId);
                    $stmt->bindParam(':data_id', $dataId);
                    $stmt->bindParam(':color', $color);
                    $stmt->execute();
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error al inicializar áreas: " . $e->getMessage());
            return false;
        }
    }
    
   
    public function inicializar() {
        try {
            $this->conn->beginTransaction();
            error_log("Inicialización del mapa comenzada - Transacción iniciada");
            
            error_log("Paso 1: Vaciando tablas...");
            $this->vaciarTablas();
            
            error_log("Paso 2: Reiniciando secuencias...");
            $this->reiniciarSecuencias();
            
            $this->verificarSecuencias();
            
            error_log("Paso 3: Insertando datos predeterminados...");
            $this->insertarDatosPredeterminados();
            
            $this->conn->commit();
            error_log("Inicialización completada exitosamente - Transacción confirmada");
            
            return [
                'exito' => true,
                'mensaje' => 'Inicialización completada correctamente'
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            $error_msg = "Error en la inicialización: " . $e->getMessage();
            error_log($error_msg);
            
            if (strpos($e->getMessage(), 'Foreign key violation') !== false) {
                error_log("Error de clave foránea detectado. Verificando tablas y secuencias...");
                $this->diagnosticarProblemaClaveForanea($e->getMessage());
            }
            
            return [
                'exito' => false,
                'mensaje' => 'Error durante la inicialización: ' . $e->getMessage()
            ];
        }
    }
    

    private function vaciarTablas() {
        try {
            
            $this->conn->exec('SET session_replication_role = replica;');
            
   
            $tablas = [
                'personal_area',
                'responsables_area',
                'subdivisiones_area',
                'areas_mapa',
                'mapas',
                'tipos_area'
            ];
            
          
            $columnChecker = $this->conn->prepare("
                SELECT column_name 
                FROM information_schema.columns 
                WHERE table_name = 'subdivisiones_area' AND column_name = 'id'
            ");
            $columnChecker->execute();
            $hasIdColumn = $columnChecker->rowCount() > 0;
            
         
            if (!$hasIdColumn) {
                error_log("La tabla subdivisiones_area no tiene la columna id. Agregándola...");
                
             
                $this->conn->exec("
                    ALTER TABLE subdivisiones_area 
                    ADD COLUMN id serial PRIMARY KEY
                ");
                
                error_log("Columna id añadida correctamente a subdivisiones_area.");
            }
            
          
            foreach ($tablas as $tabla) {
                $this->conn->exec("TRUNCATE TABLE $tabla CASCADE");
                error_log("Tabla $tabla truncada correctamente");
            }
            
         
            $this->conn->exec('SET session_replication_role = DEFAULT;');
        } catch (PDOException $e) {
            error_log("Error al truncar tablas: " . $e->getMessage());
            throw $e;
        }
    }
    
 
    private function reiniciarSecuencias() {
        try {
      
            $secuencias = [
                'mapas_id_seq',
                'areas_mapa_id_seq',
                'subdivisiones_area_id_seq',
                'responsables_area_id_seq',
                'personal_area_id_seq',
                'tipos_area_id_seq'
            ];
            
           
            foreach ($secuencias as $secuencia) {
                
                $checkSeq = $this->conn->prepare("
                    SELECT 1 FROM pg_sequences WHERE sequencename = :secuencia
                ");
                $checkSeq->bindParam(':secuencia', $secuencia);
                $checkSeq->execute();
                
                if ($checkSeq->rowCount() > 0) {
                
                    $this->conn->exec("ALTER SEQUENCE $secuencia RESTART WITH 1");
                    error_log("Secuencia $secuencia reiniciada correctamente");
                } else {
                    error_log("Advertencia: La secuencia $secuencia no existe");
                }
            }
            
            $this->conn->exec("SELECT setval('mapas_id_seq', 1, false)");
            $this->conn->exec("SELECT setval('areas_mapa_id_seq', 1, false)");
            $this->conn->exec("SELECT setval('subdivisiones_area_id_seq', 1, false)");
            $this->conn->exec("SELECT setval('responsables_area_id_seq', 1, false)");
            $this->conn->exec("SELECT setval('personal_area_id_seq', 1, false)");
            $this->conn->exec("SELECT setval('tipos_area_id_seq', 1, false)");
            
        } catch (PDOException $e) {
            error_log("Error al reiniciar secuencias: " . $e->getMessage());
            throw $e;
        }
    }

    private function verificarSecuencias() {
        try {
            $secuencias = [
                'mapas_id_seq',
                'areas_mapa_id_seq',
                'subdivisiones_area_id_seq',
                'responsables_area_id_seq',
                'personal_area_id_seq',
                'tipos_area_id_seq'
            ];
            
            foreach ($secuencias as $secuencia) {
                $query = "SELECT last_value FROM $secuencia";
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                $valor = $stmt->fetchColumn();
                
                error_log("Secuencia $secuencia - valor actual: $valor");
                
                if ($valor > 1) {
                    error_log("ADVERTENCIA: La secuencia $secuencia no se reinició correctamente (valor = $valor)");
                    
                   
                    $this->conn->exec("ALTER SEQUENCE $secuencia RESTART WITH 1");
                    $this->conn->exec("SELECT setval('$secuencia', 1, false)");
               
                    $stmt = $this->conn->prepare($query);
                    $stmt->execute();
                    $nuevoValor = $stmt->fetchColumn();
                    error_log("Secuencia $secuencia reiniciada otra vez - nuevo valor: $nuevoValor");
                }
            }
        } catch (PDOException $e) {
            error_log("Error al verificar secuencias: " . $e->getMessage());
        }
    }
    
 
    private function diagnosticarProblemaClaveForanea($error_message) {
        try {
            preg_match('/\(([^)]+)\)=\(([^)]+)\)/', $error_message, $matches);
            
            if (count($matches) >= 3) {
                $columna = $matches[1];
                $valor = $matches[2];
                
                error_log("Diagnosticando problema de clave foránea: columna=$columna, valor=$valor");
                
                if ($columna === 'area_id') {
                    $stmt = $this->conn->prepare("SELECT COUNT(*) FROM areas_mapa WHERE id = :id");
                    $stmt->bindParam(':id', $valor, PDO::PARAM_INT);
                    $stmt->execute();
                    $count = $stmt->fetchColumn();
                    
                    error_log("Registros en areas_mapa con id=$valor: $count");
                }
            }
        } catch (PDOException $e) {
            error_log("Error durante el diagnóstico: " . $e->getMessage());
        }
    }
    
   
    private function insertarDatosPredeterminados() {
        try {
          
            error_log("Insertando tipos de área...");
            $tiposArea = [
                ['nombre' => 'aula', 'descripcion' => 'Espacio para clases y actividades académicas'],
                ['nombre' => 'oficina', 'descripcion' => 'Espacio para trabajo administrativo'],
                ['nombre' => 'laboratorio', 'descripcion' => 'Espacio para experimentos y prácticas'],
                ['nombre' => 'almacen', 'descripcion' => 'Espacio para almacenamiento de materiales'],
                ['nombre' => 'baño', 'descripcion' => 'Servicios sanitarios'],
                ['nombre' => 'parqueo', 'descripcion' => 'Área para estacionamiento de vehículos'],
                ['nombre' => 'deporte', 'descripcion' => 'Área para actividades deportivas'],
                ['nombre' => 'comedor', 'descripcion' => 'Área para alimentación'],
                ['nombre' => 'edificio', 'descripcion' => 'Estructura principal']
            ];
            
            $stmtTipos = $this->conn->prepare("
                INSERT INTO tipos_area (nombre, descripcion, activo, fecha_creacion) 
                VALUES (:nombre, :descripcion, true, CURRENT_TIMESTAMP)
            ");
            
            foreach ($tiposArea as $tipo) {
                $stmtTipos->execute($tipo);
            }
            
            error_log("Tipos de área insertados correctamente");
           
            $tiposIds = [];
            $consultaTipos = $this->conn->prepare("SELECT id, nombre FROM tipos_area");
            $consultaTipos->execute();
            while ($row = $consultaTipos->fetch(PDO::FETCH_ASSOC)) {
                $tiposIds[$row['nombre']] = $row['id'];
            }
            error_log("IDs de tipos recuperados: " . json_encode($tiposIds));
         
            error_log("Insertando mapa principal...");
            $stmtMapa = $this->conn->prepare("
                INSERT INTO mapas (nombre, descripcion, imagen_url) 
                VALUES (:nombre, :descripcion, :imagen_url)
            ");
            
            $stmtMapa->execute([
                'nombre' => 'Mapa Principal del Centro Educativo',
                'descripcion' => 'Mapa interactivo del centro educativo con todas las áreas y subdivisiones',
                'imagen_url' => 'assets/img/mapa_principal.svg'
            ]);
            
        
            $mapaId = $this->conn->lastInsertId();
            error_log("Mapa principal insertado con ID: $mapaId");
            
        
            error_log("Insertando áreas predeterminadas...");
            $areas = [
                [
                    'nombre' => 'Parqueo 1', 
                    'tipo' => 'parqueo', 
                    'data_id' => 'parqueo',
                    'path_data' => 'M0 800 L200 800 L200 1000 L0 1000 Z',
                    'color' => '#FFD700',
                    'tiene_subdivisiones' => false
                ],
                [
                    'nombre' => 'Parqueo 2', 
                    'tipo' => 'parqueo', 
                    'data_id' => 'parqueo',
                    'path_data' => 'M800 800 L1000 800 L1000 1000 L800 1000 Z',
                    'color' => '#FFD700',
                    'tiene_subdivisiones' => false
                ],
                [
                    'nombre' => 'Cancha 1', 
                    'tipo' => 'deporte', 
                    'data_id' => 'cancha-1',
                    'path_data' => 'M100 30 h140 v80 h-140 Z',
                    'color' => '#6A5ACD',
                    'tiene_subdivisiones' => false
                ],
                [
                    'nombre' => 'Cancha 2', 
                    'tipo' => 'deporte', 
                    'data_id' => 'cancha-2',
                    'path_data' => 'M260 30 h140 v80 h-140 Z',
                    'color' => '#6A5ACD',
                    'tiene_subdivisiones' => false
                ],
                [
                    'nombre' => 'Sección 1', 
                    'tipo' => 'edificio', 
                    'data_id' => 'seccion-1',
                    'path_data' => 'M100 150 h330 v80 h-330 Z',
                    'color' => '#D3D3D3',
                    'tiene_subdivisiones' => true
                ],
                [
                    'nombre' => 'Sección 2', 
                    'tipo' => 'edificio', 
                    'data_id' => 'seccion-2',
                    'path_data' => 'M600 150 h280 v80 h-280 Z',
                    'color' => '#D3D3D3',
                    'tiene_subdivisiones' => true
                ],
                [
                    'nombre' => 'Sección 3', 
                    'tipo' => 'edificio', 
                    'data_id' => 'seccion-3',
                    'path_data' => 'M100 300 h330 v80 h-330 Z',
                    'color' => '#D3D3D3',
                    'tiene_subdivisiones' => true
                ],
                [
                    'nombre' => 'Sección 4', 
                    'tipo' => 'edificio', 
                    'data_id' => 'seccion-4',
                    'path_data' => 'M600 300 h330 v80 h-330 Z',
                    'color' => '#D3D3D3',
                    'tiene_subdivisiones' => true
                ],
                [
                    'nombre' => 'Sección 5', 
                    'tipo' => 'edificio', 
                    'data_id' => 'seccion-5',
                    'path_data' => 'M100 450 h330 v80 h-330 Z',
                    'color' => '#D3D3D3',
                    'tiene_subdivisiones' => true
                ],
                [
                    'nombre' => 'Sección 6', 
                    'tipo' => 'edificio', 
                    'data_id' => 'seccion-6',
                    'path_data' => 'M600 450 h330 v80 h-330 Z',
                    'color' => '#D3D3D3',
                    'tiene_subdivisiones' => true
                ],
                [
                    'nombre' => 'Comedor', 
                    'tipo' => 'comedor', 
                    'data_id' => 'seccion-7',
                    'path_data' => 'M100 600 h300 v360 h-300 Z',
                    'color' => '#D3D3D3',
                    'tiene_subdivisiones' => false
                ],
                [
                    'nombre' => 'Sección 8', 
                    'tipo' => 'edificio', 
                    'data_id' => 'seccion-8',
                    'path_data' => 'M600 600 h325 v80 h-325 Z',
                    'color' => '#D3D3D3',
                    'tiene_subdivisiones' => true
                ]
            ];
            
        
            $stmtAreas = $this->conn->prepare("
                INSERT INTO areas_mapa (mapa_id, nombre, tipo_id, data_id, path_data, color) 
                VALUES (:mapa_id, :nombre, :tipo_id, :data_id, :path_data, :color)
            ");
            
            $seccionIds = [];
            $areasSimples = [];
            
            foreach ($areas as $area) {
                $tipoId = isset($tiposIds[$area['tipo']]) ? $tiposIds[$area['tipo']] : null;
                if (!$tipoId) {
                    error_log("ADVERTENCIA: Tipo '{$area['tipo']}' no encontrado para área '{$area['nombre']}'");
                    continue;
                }
                
                $areaParams = [
                    'mapa_id' => $mapaId,
                    'nombre' => $area['nombre'],
                    'tipo_id' => $tipoId,
                    'data_id' => $area['data_id'],
                    'path_data' => $area['path_data'],
                    'color' => $area['color']
                ];
          
                error_log("Insertando área '{$area['nombre']}' con tipo_id: $tipoId");
                $stmtAreas->execute($areaParams);
                
                $areaId = $this->conn->lastInsertId();
                error_log("Área '{$area['nombre']}' insertada con ID: $areaId");
                
              
                if (strpos($area['nombre'], 'Sección') !== false && $area['tiene_subdivisiones']) {
                    $numero = filter_var($area['nombre'], FILTER_SANITIZE_NUMBER_INT);
                    $seccionIds[$numero] = $areaId;
                    error_log("Guardando Sección $numero con ID $areaId para subdivisiones");
                }
                
               
                if (!$area['tiene_subdivisiones']) {
                    $area['id'] = $areaId;
                    $areasSimples[] = $area;
                }
            }
            
           
            error_log("Resumen de IDs de secciones: " . json_encode($seccionIds));
            
           
            error_log("Preparando subdivisiones...");
            $subdivisiones = [];
            
           
            foreach ($areasSimples as $area) {
                error_log("Agregando subdivisión única para área '{$area['nombre']}' (ID: {$area['id']})");
                $subdivisiones[] = [
                    'area_id' => $area['id'],
                    'nombre' => $area['nombre'],
                    'tipo_id' => $tiposIds[$area['tipo']],
                    'svg_id' => $area['data_id'] . '_completo',
                    'data_id' => $area['data_id'],
                    'path_data' => $area['path_data'],
                    'color' => $area['color']
                ];
            }
            
            // Sección 1
            $seccion1_subs = [
                ['x' => 100, 'y' => 150, 'width' => 25, 'height' => 80, 'num' => 1],
                ['x' => 125, 'y' => 150, 'width' => 25, 'height' => 80, 'num' => 2],
                ['x' => 150, 'y' => 150, 'width' => 70, 'height' => 80, 'num' => 3],
                ['x' => 220, 'y' => 150, 'width' => 70, 'height' => 80, 'num' => 4],
                ['x' => 290, 'y' => 150, 'width' => 70, 'height' => 80, 'num' => 5],
                ['x' => 360, 'y' => 150, 'width' => 70, 'height' => 80, 'num' => 6]
            ];
            
            // Sección 2
            $seccion2_subs = [
                ['x' => 600, 'y' => 150, 'width' => 20, 'height' => 80, 'num' => 1],
                ['x' => 620, 'y' => 150, 'width' => 20, 'height' => 80, 'num' => 2],
                ['x' => 640, 'y' => 150, 'width' => 60, 'height' => 80, 'num' => 3],
                ['x' => 700, 'y' => 150, 'width' => 60, 'height' => 80, 'num' => 4],
                ['x' => 760, 'y' => 150, 'width' => 60, 'height' => 80, 'num' => 5],
                ['x' => 820, 'y' => 150, 'width' => 60, 'height' => 80, 'num' => 6]
            ];
            
            // Sección 3
            $seccion3_subs = [
                ['x' => 100, 'y' => 300, 'width' => 25, 'height' => 80, 'num' => 1],
                ['x' => 125, 'y' => 300, 'width' => 25, 'height' => 80, 'num' => 2],
                ['x' => 150, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 3],
                ['x' => 220, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 4],
                ['x' => 290, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 5],
                ['x' => 360, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 6]
            ];
            
            // Sección 4
            $seccion4_subs = [
                ['x' => 600, 'y' => 300, 'width' => 25, 'height' => 80, 'num' => 1],
                ['x' => 625, 'y' => 300, 'width' => 25, 'height' => 80, 'num' => 2],
                ['x' => 650, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 3],
                ['x' => 720, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 4],
                ['x' => 790, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 5],
                ['x' => 860, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 6]
            ];
            
            // Sección 5
            $seccion5_subs = [
                ['x' => 100, 'y' => 450, 'width' => 25, 'height' => 80, 'num' => 1],
                ['x' => 125, 'y' => 450, 'width' => 25, 'height' => 80, 'num' => 2],
                ['x' => 150, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 3],
                ['x' => 220, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 4],
                ['x' => 290, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 5],
                ['x' => 360, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 6]
            ];
            
            // Sección 6
            $seccion6_subs = [
                ['x' => 600, 'y' => 450, 'width' => 25, 'height' => 80, 'num' => 1],
                ['x' => 625, 'y' => 450, 'width' => 25, 'height' => 80, 'num' => 2],
                ['x' => 650, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 3],
                ['x' => 720, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 4],
                ['x' => 790, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 5],
                ['x' => 860, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 6]
            ];
            
            // Sección 8
            $seccion8_subs = [
                ['x' => 600, 'y' => 600, 'width' => 25, 'height' => 80, 'num' => 1],
                ['x' => 625, 'y' => 600, 'width' => 25, 'height' => 80, 'num' => 2],
                ['x' => 650, 'y' => 600, 'width' => 40, 'height' => 80, 'num' => 3],
                ['x' => 690, 'y' => 600, 'width' => 40, 'height' => 80, 'num' => 4],
                ['x' => 730, 'y' => 600, 'width' => 40, 'height' => 80, 'num' => 5],
                ['x' => 855, 'y' => 600, 'width' => 70, 'height' => 80, 'num' => 7]
            ];
            
            // Subdivisiones especiales de la sección 8-6
            $subSec8_6 = [
                ['x' => 770, 'y' => 600, 'w' => 65, 'h' => 40, 'nombre' => 'A', 'num' => 1],
                ['x' => 835, 'y' => 600, 'w' => 20, 'h' => 40, 'nombre' => 'B', 'num' => 2],
                ['x' => 770, 'y' => 640, 'w' => 34, 'h' => 40, 'nombre' => 'C', 'num' => 3],
                ['x' => 804, 'y' => 640, 'w' => 17, 'h' => 40, 'nombre' => 'D', 'num' => 4],
                ['x' => 821, 'y' => 640, 'w' => 34, 'h' => 40, 'nombre' => 'E', 'num' => 5]
            ];
            
            // Mapear todas las subdivisiones específicas por sección
            $todas_secciones = [
                1 => $seccion1_subs,
                2 => $seccion2_subs, 
                3 => $seccion3_subs,
                4 => $seccion4_subs,
                5 => $seccion5_subs,
                6 => $seccion6_subs,
                8 => $seccion8_subs
            ];
            
    
            error_log("Generando subdivisiones para secciones...");
            foreach ($todas_secciones as $numSeccion => $subSecciones) {
                if (!isset($seccionIds[$numSeccion])) {
                    error_log("ADVERTENCIA: No existe ID para Sección $numSeccion");
                    continue;
                }
                
                $seccionId = $seccionIds[$numSeccion];
                error_log("Procesando subdivisiones para Sección $numSeccion (ID: $seccionId)");
                
                foreach ($subSecciones as $sub) {
                    $subdivisiones[] = [
                        'area_id' => $seccionId,
                        'nombre' => "Sección $numSeccion - Sub {$sub['num']}",
                        'tipo_id' => $tiposIds['aula'],
                        'svg_id' => "seccion{$numSeccion}_sub{$sub['num']}",
                        'data_id' => "seccion-$numSeccion-{$sub['num']}",
                        'path_data' => "M{$sub['x']} {$sub['y']} h{$sub['width']} v{$sub['height']} h-{$sub['width']} Z",
                        'color' => '#D3D3D3'
                    ];
                    
                    error_log("Subdivisión {$sub['num']} para Sección {$numSeccion} creada: x={$sub['x']}, y={$sub['y']}, width={$sub['width']}, height={$sub['height']}");
                }
            }
            
           
            if (isset($seccionIds[8])) {
                $seccionId = $seccionIds[8];
                error_log("Creando subdivisiones especiales para Sección 8-6");
                
                foreach ($subSec8_6 as $coords) {
                    $subdivisiones[] = [
                        'area_id' => $seccionId,
                        'nombre' => "Sección 8 - Sub 6{$coords['nombre']}",
                        'tipo_id' => $tiposIds['aula'],
                        'svg_id' => "seccion8_sub6_" . strtolower($coords['nombre']),
                        'data_id' => "seccion-8-6-{$coords['num']}",
                        'path_data' => "M{$coords['x']} {$coords['y']} h{$coords['w']} v{$coords['h']} h-{$coords['w']} Z",
                        'color' => '#D3D3D3'
                    ];
                    
                    error_log("Subdivisión especial 8-6-{$coords['nombre']} creada: x={$coords['x']}, y={$coords['y']}, w={$coords['w']}, h={$coords['h']}");
                }
            }
            
        
            error_log("Insertando " . count($subdivisiones) . " subdivisiones...");
            
           
            $stmtSub = $this->conn->prepare("
                INSERT INTO subdivisiones_area (area_id, nombre, tipo_id, svg_id, data_id, path_data, color) 
                VALUES (:area_id, :nombre, :tipo_id, :svg_id, :data_id, :path_data, :color)
            ");
            
            $subCount = 0;
            foreach ($subdivisiones as $subdivision) {
                try {
                    
                    $checkAreaStmt = $this->conn->prepare("SELECT COUNT(*) FROM areas_mapa WHERE id = :id");
                    $checkAreaStmt->bindParam(':id', $subdivision['area_id'], PDO::PARAM_INT);
                    $checkAreaStmt->execute();
                    $areaExists = $checkAreaStmt->fetchColumn() > 0;
                    
                    if (!$areaExists) {
                        error_log("ERROR: No se puede insertar subdivisión para área_id={$subdivision['area_id']} porque no existe");
                        continue;
                    }
                    
                    error_log("Insertando subdivisión '{$subdivision['nombre']}' para área_id={$subdivision['area_id']}");
                    $stmtSub->execute($subdivision);
                    $subCount++;
                } catch (PDOException $e) {
                    error_log("Error al insertar subdivisión '{$subdivision['nombre']}': " . $e->getMessage());
                
                }
            }
            
            error_log("Se insertaron $subCount subdivisiones correctamente");
            return true;
        } catch (PDOException $e) {
            error_log("Error crítico al insertar datos predeterminados: " . $e->getMessage());
            throw $e; 
        }
    }
    

   
     
     
     
 
    
    /**
     * Recupera las subdivisiones de las áreas del mapa basadas en el SVG proporcionado
     * @return array Las subdivisiones de las áreas
     */
    public function recuperarSubdivisiones() {
        try {
            // Obtener el ID del tipo de área 'aula'
            $stmt = $this->conn->prepare("SELECT id FROM tipos_area WHERE nombre = 'aula' LIMIT 1");
            $stmt->execute();
            $tipoAulaId = $stmt->fetchColumn();
            
            if (!$tipoAulaId) {
                throw new Exception("No se encontró el tipo de área 'aula'");
            }
            
            // Definir subdivisiones exactas basadas en el SVG proporcionado
            // Sección 1
            $seccion1_subs = [
                ['x' => 100, 'y' => 150, 'width' => 25, 'height' => 80, 'num' => 1],
                ['x' => 125, 'y' => 150, 'width' => 25, 'height' => 80, 'num' => 2],
                ['x' => 150, 'y' => 150, 'width' => 70, 'height' => 80, 'num' => 3],
                ['x' => 220, 'y' => 150, 'width' => 70, 'height' => 80, 'num' => 4],
                ['x' => 290, 'y' => 150, 'width' => 70, 'height' => 80, 'num' => 5],
                ['x' => 360, 'y' => 150, 'width' => 70, 'height' => 80, 'num' => 6]
            ];
            
            // Sección 2
            $seccion2_subs = [
                ['x' => 600, 'y' => 150, 'width' => 20, 'height' => 80, 'num' => 1],
                ['x' => 620, 'y' => 150, 'width' => 20, 'height' => 80, 'num' => 2],
                ['x' => 640, 'y' => 150, 'width' => 60, 'height' => 80, 'num' => 3],
                ['x' => 700, 'y' => 150, 'width' => 60, 'height' => 80, 'num' => 4],
                ['x' => 760, 'y' => 150, 'width' => 60, 'height' => 80, 'num' => 5],
                ['x' => 820, 'y' => 150, 'width' => 60, 'height' => 80, 'num' => 6]
            ];
            
            // Sección 3
            $seccion3_subs = [
                ['x' => 100, 'y' => 300, 'width' => 25, 'height' => 80, 'num' => 1],
                ['x' => 125, 'y' => 300, 'width' => 25, 'height' => 80, 'num' => 2],
                ['x' => 150, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 3],
                ['x' => 220, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 4],
                ['x' => 290, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 5],
                ['x' => 360, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 6]
            ];
            
            // Sección 4
            $seccion4_subs = [
                ['x' => 600, 'y' => 300, 'width' => 25, 'height' => 80, 'num' => 1],
                ['x' => 625, 'y' => 300, 'width' => 25, 'height' => 80, 'num' => 2],
                ['x' => 650, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 3],
                ['x' => 720, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 4],
                ['x' => 790, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 5],
                ['x' => 860, 'y' => 300, 'width' => 70, 'height' => 80, 'num' => 6]
            ];
            
            // Sección 5
            $seccion5_subs = [
                ['x' => 100, 'y' => 450, 'width' => 25, 'height' => 80, 'num' => 1],
                ['x' => 125, 'y' => 450, 'width' => 25, 'height' => 80, 'num' => 2],
                ['x' => 150, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 3],
                ['x' => 220, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 4],
                ['x' => 290, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 5],
                ['x' => 360, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 6]
            ];
            
            // Sección 6
            $seccion6_subs = [
                ['x' => 600, 'y' => 450, 'width' => 25, 'height' => 80, 'num' => 1],
                ['x' => 625, 'y' => 450, 'width' => 25, 'height' => 80, 'num' => 2],
                ['x' => 650, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 3],
                ['x' => 720, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 4],
                ['x' => 790, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 5],
                ['x' => 860, 'y' => 450, 'width' => 70, 'height' => 80, 'num' => 6]
            ];
            
            // Sección 8
            $seccion8_subs = [
                ['x' => 600, 'y' => 600, 'width' => 25, 'height' => 80, 'num' => 1],
                ['x' => 625, 'y' => 600, 'width' => 25, 'height' => 80, 'num' => 2],
                ['x' => 650, 'y' => 600, 'width' => 40, 'height' => 80, 'num' => 3],
                ['x' => 690, 'y' => 600, 'width' => 40, 'height' => 80, 'num' => 4],
                ['x' => 730, 'y' => 600, 'width' => 40, 'height' => 80, 'num' => 5],
                ['x' => 855, 'y' => 600, 'width' => 70, 'height' => 80, 'num' => 7]
            ];
            
            // Subdivisiones especiales de la sección 8-6
            $subSec8_6 = [
                ['x' => 770, 'y' => 600, 'w' => 65, 'h' => 40, 'nombre' => 'A', 'num' => 1],
                ['x' => 835, 'y' => 600, 'w' => 20, 'h' => 40, 'nombre' => 'B', 'num' => 2],
                ['x' => 770, 'y' => 640, 'w' => 34, 'h' => 40, 'nombre' => 'C', 'num' => 3],
                ['x' => 804, 'y' => 640, 'w' => 17, 'h' => 40, 'nombre' => 'D', 'num' => 4],
                ['x' => 821, 'y' => 640, 'w' => 34, 'h' => 40, 'nombre' => 'E', 'num' => 5]
            ];
            
            // Obtener los IDs de las áreas
            $stmt = $this->conn->prepare("SELECT id, nombre FROM areas_mapa WHERE nombre LIKE 'Sección%'");
            $stmt->execute();
            $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $areaIds = [];
            foreach ($areas as $area) {
                $numero = filter_var($area['nombre'], FILTER_SANITIZE_NUMBER_INT);
                $areaIds[$numero] = $area['id'];
            }
            
            // Preparar la consulta para insertar subdivisiones
            $stmt = $this->conn->prepare("
                INSERT INTO subdivisiones_area (area_id, nombre, tipo_id, data_id, color, path_data, svg_id) 
                VALUES (:area_id, :nombre, :tipo_id, :data_id, :color, :path_data, :svg_id)
            ");
            
            // Insertar subdivisiones para cada sección
            $secciones = [
                1 => $seccion1_subs,
                2 => $seccion2_subs,
                3 => $seccion3_subs,
                4 => $seccion4_subs,
                5 => $seccion5_subs,
                6 => $seccion6_subs,
                8 => $seccion8_subs
            ];
            
            foreach ($secciones as $numSeccion => $subs) {
                if (!isset($areaIds[$numSeccion])) {
                    continue;
                }
                
                $areaId = $areaIds[$numSeccion];
                
                foreach ($subs as $sub) {
                    $nombre = "Sección $numSeccion-{$sub['num']}";
                    $dataId = "seccion-$numSeccion-{$sub['num']}";
                    $svgId = "seccion{$numSeccion}_sub{$sub['num']}";
                    $pathData = "M{$sub['x']} {$sub['y']} h{$sub['width']} v{$sub['height']} h-{$sub['width']} Z";
                    
                    $stmt->execute([
                        'area_id' => $areaId,
                        'nombre' => $nombre,
                        'tipo_id' => $tipoAulaId,
                        'data_id' => $dataId,
                        'color' => '#D3D3D3',
                        'path_data' => $pathData,
                        'svg_id' => $svgId
                    ]);
                }
            }
            
            // Insertar subdivisiones especiales de la sección 8-6
            if (isset($areaIds[8])) {
                $areaId = $areaIds[8];
                
                foreach ($subSec8_6 as $sub) {
                    $nombre = "Sección 8-6-{$sub['num']}";
                    $dataId = "seccion-8-6-{$sub['num']}";
                    $svgId = "seccion8_sub6_" . strtolower($sub['nombre']);
                    $pathData = "M{$sub['x']} {$sub['y']} h{$sub['w']} v{$sub['h']} h-{$sub['w']} Z";
                    
                    $stmt->execute([
                        'area_id' => $areaId,
                        'nombre' => $nombre,
                        'tipo_id' => $tipoAulaId,
                        'data_id' => $dataId,
                        'color' => '#D3D3D3',
                        'path_data' => $pathData,
                        'svg_id' => $svgId
                    ]);
                }
            }
            
            return [
                'exito' => true,
                'mensaje' => 'Subdivisiones guardadas correctamente'
            ];
        } catch (Exception $e) {
            error_log("Error al recuperar subdivisiones: " . $e->getMessage());
            return [
                'exito' => false,
                'mensaje' => 'Error al guardar subdivisiones: ' . $e->getMessage()
            ];
        }
    }
} 