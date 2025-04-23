<?php
require_once 'conexion.php';

class MapaInteractivo {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * Obtiene todos los mapas disponibles
     */
    public function obtenerMapas() {
        if (!$this->conn) return []; // Verificar conexión
        try {
            $stmt = $this->conn->prepare("SELECT * FROM mapas");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener mapas: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene todas las áreas de un mapa
     * @param int $mapaId ID del mapa
     * @return array Array con las áreas del mapa
     */
    public function obtenerAreasMapa($mapaId) {
        if (!($this->conn instanceof PDO)) {
            error_log("Error: No hay conexión PDO válida en obtenerAreasMapa");
            return [];
        }

        try {
            $stmt = $this->conn->prepare("
                SELECT a.*, t.nombre as tipo_nombre 
                FROM areas_mapa a 
                LEFT JOIN tipos_area t ON a.tipo_id = t.id 
                WHERE a.mapa_id = :mapa_id
            ");
            $stmt->bindParam(':mapa_id', $mapaId, PDO::PARAM_INT);
            $stmt->execute();
            $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Para cada área, obtener sus subdivisiones
            foreach ($areas as &$area) {
                // Obtener subdivisiones del área
                $stmtSub = $this->conn->prepare("
                    SELECT s.*, t.nombre as tipo_nombre 
                    FROM subdivisiones_area s
                    LEFT JOIN tipos_area t ON s.tipo_id = t.id 
                    WHERE s.area_id = :area_id
                ");
                $stmtSub->bindParam(':area_id', $area['id'], PDO::PARAM_INT);
                $stmtSub->execute();
                $subdivisiones = $stmtSub->fetchAll(PDO::FETCH_ASSOC);
                
                // Si el área no tiene subdivisiones en la tabla subdivisiones_area,
                // crear una subdivisión que represente el área completa
                if (empty($subdivisiones)) {
                    $subdivisiones = [[
                        'id' => null,
                        'area_id' => $area['id'],
                        'nombre' => $area['nombre'],
                        'tipo_id' => $area['tipo_id'],
                        'tipo_nombre' => $area['tipo_nombre'],
                        'aula_id' => $area['aula_id'],
                        'data_id' => $area['data_id'],
                        'path_data' => $area['path_data'],
                        'color' => $area['color']
                    ]];
                }
                
                // Asignar las subdivisiones al área
                $area['subdivisiones'] = $subdivisiones;
            }
            unset($area); // Evitar referencias
            
            return $areas;
        } catch (PDOException $e) {
            error_log("Error al obtener áreas del mapa: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtiene todas las subdivisiones de un área específica
     */
    public function obtenerSubdivisionesArea($areaId) {
        try {
            // Verificar que tenemos una conexión válida
            if (!($this->conn instanceof PDO)) {
                error_log("Error: No hay conexión PDO válida en obtenerSubdivisionesArea");
                return [];
            }
            
            $query = "
                SELECT 
                    s.id,
                    s.nombre,
                    s.svg_id,
                    s.tipo_id,
                    s.aula_id,
                    s.data_id,
                    s.path_data,
                    s.color,
                    t.nombre as tipo_nombre,
                    t.activo as tipo_activo
                FROM 
                    subdivisiones_area s
                LEFT JOIN 
                    tipos_area t ON s.tipo_id = t.id
                WHERE 
                    s.area_id = :area_id
                ORDER BY 
                    s.id ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':area_id', $areaId, PDO::PARAM_INT);
            $stmt->execute();
            
            $subdivisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generar path_data para las subdivisiones que no lo tienen
            foreach ($subdivisiones as &$subdivision) {
                if (empty($subdivision['path_data'])) {
                    // Extraer el número de sección y subdivisión del svg_id
                    if (preg_match('/seccion(\d+)_sub(\d+)/', $subdivision['svg_id'], $matches)) {
                        $numSeccion = intval($matches[1]);
                        $numSub = intval($matches[2]);
                        
                        // Calcular coordenadas basadas en la sección y número de subdivisión
                        $x = $numSeccion <= 3 ? 100 : 600; // Secciones 1-3 empiezan en x=100, 4-6 en x=600
                        $y = 150 + (($numSeccion - 1) % 3) * 150; // Cada fila aumenta 150 en y
                        
                        // Ajustar el ancho y la posición x según la subdivisión
                        if ($numSub <= 2) {
                            // Las dos primeras subdivisiones son más angostas
                            $width = 25;
                            $x += ($numSub - 1) * 25;
                        } else {
                            // Las siguientes cuatro subdivisiones son más anchas
                            $width = 70;
                            $x += 50 + ($numSub - 3) * 70;
                        }
                        
                        // Generar el path_data
                        $subdivision['path_data'] = "M$x $y h$width v80 h-$width Z";
                        
                        // Actualizar en la base de datos usando data_id en lugar de id
                        if (isset($subdivision['data_id'])) {
                            try {
                                $updateQuery = "UPDATE subdivisiones_area SET path_data = :path_data WHERE data_id = :data_id";
                                $updateStmt = $this->conn->prepare($updateQuery);
                                $updateStmt->execute([
                                    ':path_data' => $subdivision['path_data'],
                                    ':data_id' => $subdivision['data_id']
                                ]);
                            } catch (PDOException $e) {
                                error_log("Error al actualizar path_data: " . $e->getMessage());
                                // Continuar con la siguiente subdivisión
                            }
                        }
                    }
                }
            }
            
            return $subdivisiones;
            
        } catch (PDOException $e) {
            error_log("Error al obtener subdivisiones: " . $e->getMessage());
            return [];
        }
    }
    
  
    public function obtenerResponsablesArea($areaId) {
        if (!$this->conn) return []; 
        try {
            $stmt = $this->conn->prepare("
                SELECT ra.*, u.nombre, u.apellido 
                FROM responsables_area ra
                JOIN usuarios u ON ra.usuario_id = u.id
                WHERE ra.area_id = :area_id
            ");
            $stmt->bindParam(':area_id', $areaId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener responsables del área: " . $e->getMessage());
            return [];
        }
    }
    
  
    public function obtenerPersonalArea($areaId) {
        if (!$this->conn) return []; 
        try {
            $stmt = $this->conn->prepare("
                SELECT pa.*, u.nombre, u.apellido 
                FROM personal_area pa
                JOIN usuarios u ON pa.usuario_id = u.id
                WHERE pa.area_id = :area_id
            ");
            $stmt->bindParam(':area_id', $areaId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener personal del área: " . $e->getMessage());
            return [];
        }
    }
    
  
    public function obtenerAreaPorDataId($dataId) {
        try {
           
            if (!$this->conn) {
                error_log("Error: No hay conexión a la base de datos disponible");
                return null;
            }

            
            if (empty($dataId)) {
                error_log("Error: data_id está vacío");
                return null;
            }

          
            error_log("Buscando área con data_id: " . $dataId);
            
       
            $query = "SELECT * FROM areas_mapa WHERE data_id = :data_id";
            error_log("Ejecutando query: " . $query . " con data_id = " . $dataId);
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':data_id', $dataId, PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                $error = $stmt->errorInfo();
                error_log("Error al ejecutar la consulta: " . json_encode($error));
                return null;
            }
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
           
            if ($resultado) {
                error_log("Área encontrada: " . json_encode($resultado));
            } else {
                error_log("No se encontró área con data_id: " . $dataId);
              
                $checkStmt = $this->conn->query("SELECT COUNT(*) FROM areas_mapa");
                $count = $checkStmt->fetchColumn();
                error_log("Total de áreas en la base de datos: " . $count);
            }
            
            return $resultado;
        } catch (PDOException $e) {
            error_log("Error al obtener área por data_id: " . $e->getMessage());
            error_log("Query: SELECT * FROM areas_mapa WHERE data_id = '" . $dataId . "'");
            error_log("Trace: " . $e->getTraceAsString());
            return null;
        }
    }
    

    public function obtenerAreaPorId($id) {
        if (!$this->conn) return null; // Verificar conexión
        try {
            $stmt = $this->conn->prepare("SELECT * FROM areas_mapa WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener área por ID: " . $e->getMessage());
            return null;
        }
    }
   
    public function obtenerSubdivisionPorDataId($dataId) {
        try {
            
            if (!$this->conn || empty($dataId)) {
                error_log("Error: Conexión BD no disponible o data_id vacío en obtenerSubdivisionPorDataId.");
                return null;
            }

            error_log("Buscando subdivisión con data_id: " . $dataId);

          
            $query = "SELECT s.*, t.nombre as tipo_nombre, t.activo as tipo_activo 
                FROM subdivisiones_area s
                LEFT JOIN tipos_area t ON s.tipo_id = t.id
                WHERE s.data_id = :data_id"; 

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':data_id', $dataId, PDO::PARAM_STR);

            if (!$stmt->execute()) {
                $error = $stmt->errorInfo();
                error_log("Error al ejecutar consulta de subdivisión: " . json_encode($error) . " para data_id: " . $dataId);
                return null;
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Resultado de fetch para subdivisión con data_id " . $dataId . ": " . ($result ? json_encode($result) : 'null'));

            if ($result) {

                if (isset($result['tipo_activo'])) {
                    $result['tipo_activo'] = ($result['tipo_activo'] === 't' || $result['tipo_activo'] === true);
                }
                

                // Nota: Actualmente las tablas responsables/personal se relacionan con areas_mapa (area_id),

                if (!empty($result['area_id'])) { // Solo si hay area_id
                    $result['responsables'] = $this->obtenerResponsablesArea($result['area_id']); 
                    $result['personal'] = $this->obtenerPersonalArea($result['area_id']);       
                } else {
                    $result['responsables'] = [];
                    $result['personal'] = [];
                }

                return $result;
            }

            return null;
        } catch (PDOException $e) {
            error_log("Excepción PDO al obtener subdivisión por data_id (" . $dataId . "): " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Guarda un nuevo mapa
     */
    public function guardarMapa($nombre, $descripcion, $imagenUrl) {
        if (!$this->conn) return false; 
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO mapas (nombre, descripcion, imagen_url)
                VALUES (:nombre, :descripcion, :imagen_url)
                RETURNING id
            ");
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->bindParam(':imagen_url', $imagenUrl, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error al guardar mapa: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Guarda una nueva área
     */
    public function guardarArea($mapaId, $nombre, $tipo, $svgId, $color, $aulaId, $dataId) {
        if (!$this->conn) return false; // Verificar conexión
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO areas_mapa (mapa_id, nombre, tipo, svg_id, color, aula_id, data_id)
                VALUES (:mapa_id, :nombre, :tipo, :svg_id, :color, :aula_id, :data_id)
                RETURNING id
            ");
            $stmt->bindParam(':mapa_id', $mapaId, PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindParam(':svg_id', $svgId, PDO::PARAM_STR);
            $stmt->bindParam(':color', $color, PDO::PARAM_STR);
            $stmt->bindParam(':aula_id', $aulaId, PDO::PARAM_INT);
            $stmt->bindParam(':data_id', $dataId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error al guardar área: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Guarda una nueva subdivisión
     */
    public function guardarSubdivision($areaId, $nombre, $svgId, $tipoId, $aulaId, $dataId, $color = null) {
        if (!$this->conn) return false; // Verificar conexión
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO subdivisiones_area (area_id, nombre, tipo_id, svg_id, color, aula_id, data_id)
                VALUES (:area_id, :nombre, :tipo_id, :svg_id, :color, :aula_id, :data_id)
                RETURNING id
            ");
            $stmt->bindParam(':area_id', $areaId, PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':tipo_id', $tipoId, PDO::PARAM_INT); 
            $stmt->bindParam(':svg_id', $svgId, PDO::PARAM_STR);
            $stmt->bindParam(':color', $color, PDO::PARAM_STR); 
            $stmt->bindParam(':aula_id', $aulaId, PDO::PARAM_INT);
            $stmt->bindParam(':data_id', $dataId, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error al guardar subdivisión: " . $e->getMessage());
            return false;
        }
    }
    
   
    public function asignarResponsable($areaId, $usuarioId, $cargo) {
        if (!$this->conn) return false; 
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO responsables_area (area_id, usuario_id, cargo)
                VALUES (:area_id, :usuario_id, :cargo)
                RETURNING id
            ");
            $stmt->bindParam(':area_id', $areaId, PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindParam(':cargo', $cargo, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error al asignar responsable: " . $e->getMessage());
            return false;
        }
    }
    
 
    public function asignarPersonal($areaId, $usuarioId, $cargo) {
        if (!$this->conn) return false; 
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO personal_area (area_id, usuario_id, cargo)
                VALUES (:area_id, :usuario_id, :cargo)
                RETURNING id
            ");
            $stmt->bindParam(':area_id', $areaId, PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
            $stmt->bindParam(':cargo', $cargo, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error al asignar personal: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza un área existente
     */
    public function actualizarArea($id, $nombre, $tipo, $svgId, $color, $aulaId) {
        if (!$this->conn) return false; 
        try {
            $stmt = $this->conn->prepare("
                UPDATE areas_mapa 
                SET nombre = :nombre, tipo = :tipo, svg_id = :svg_id, color = :color, aula_id = :aula_id
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindParam(':svg_id', $svgId, PDO::PARAM_STR);
            $stmt->bindParam(':color', $color, PDO::PARAM_STR);
            $stmt->bindParam(':aula_id', $aulaId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar área: " . $e->getMessage());
            return false;
        }
    }
    
 
    public function actualizarSubdivision($id, $nombre, $svgId, $tipo, $aulaId) {
        if (!$this->conn) return false; 
        try {
            $stmt = $this->conn->prepare("
                UPDATE subdivisiones_area 
                SET nombre = :nombre, svg_id = :svg_id, tipo = :tipo, aula_id = :aula_id
                WHERE id = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':svg_id', $svgId, PDO::PARAM_STR);
            $stmt->bindParam(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->bindParam(':aula_id', $aulaId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar subdivisión: " . $e->getMessage());
            return false;
        }
    }
    
   
    public function eliminarArea($id) {
        if (!$this->conn) return false; 
        try {
            // Primero eliminamos las subdivisiones
            $stmt = $this->conn->prepare("DELETE FROM subdivisiones_area WHERE area_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Luego eliminamos los responsables y personal
            $stmt = $this->conn->prepare("DELETE FROM responsables_area WHERE area_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $stmt = $this->conn->prepare("DELETE FROM personal_area WHERE area_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Finalmente eliminamos el área
            $stmt = $this->conn->prepare("DELETE FROM areas_mapa WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar área: " . $e->getMessage());
            return false;
        }
    }
    

    public function eliminarSubdivision($id) {
        if (!$this->conn) return false; 
        try {
            $stmt = $this->conn->prepare("DELETE FROM subdivisiones_area WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar subdivisión: " . $e->getMessage());
            return false;
        }
    }
    
  
    public function obtenerSubdivisionesSVG($areaId) {
  
        return []; 
    }
    
 
    public function actualizarSubdivisionInfo($dataId, $nombre, $tipoId) {
        if (!$this->conn) return false; 
        try {
            
            $updates = [];
            $params = [':data_id' => $dataId];
            
            if ($nombre !== null) {
                $updates[] = "nombre = :nombre";
                $params[':nombre'] = $nombre;
            }
            if ($tipoId !== null && $tipoId !== '') { 
                $updates[] = "tipo_id = :tipo_id";
                $params[':tipo_id'] = (int)$tipoId; 
            } elseif ($tipoId === '') {
              
                $updates[] = "tipo_id = NULL"; 
            }

  
            if (empty($updates)) {
                return true; 
            }

            $query = "UPDATE subdivisiones_area SET " . implode(", ", $updates) . " WHERE data_id = :data_id";
            
            $stmt = $this->conn->prepare($query);
            
            // Bindear los parámetros
            foreach ($params as $key => &$val) {
                 // Determinar el tipo de parámetro
                 $paramType = PDO::PARAM_STR; // Por defecto STR
                 if ($key === ':tipo_id') {
                     $paramType = PDO::PARAM_INT;
                 } elseif ($key === ':data_id') {
                     $paramType = PDO::PARAM_STR;
                 } // Añadir más tipos si es necesario
                 
                 $stmt->bindParam($key, $val, $paramType);
            }
            
            $resultado = $stmt->execute();
            error_log("Resultado de actualizarSubdivisionInfo para data_id $dataId: " . ($resultado ? 'Éxito' : 'Fallo') . " Query: " . $query . " Params: " . json_encode($params));
            return $resultado;

        } catch (PDOException $e) {
            error_log("Error al actualizar información de subdivisión (data_id: $dataId): " . $e->getMessage());
            return false;
        }
    }

  
    public function obtenerPreviewSubdivision($dataId) {
        if (!$this->conn) return null;
        try {
            if (!$this->conn || empty($dataId)) {
                return null;
            }

        
            $stmtSub = $this->conn->prepare("SELECT id, area_id, nombre FROM subdivisiones_area WHERE data_id = :data_id");
            $stmtSub->bindParam(':data_id', $dataId, PDO::PARAM_STR);
            $stmtSub->execute();
            $subdivision = $stmtSub->fetch(PDO::FETCH_ASSOC);

            if (!$subdivision) {
                error_log("Preview: Subdivisión no encontrada para data_id: " . $dataId);
                return null; 
            }

            $areaId = $subdivision['area_id'];
            $nombreSubdivision = $subdivision['nombre'];

            $stmtResp = $this->conn->prepare("SELECT COUNT(*) FROM responsables_area WHERE area_id = :area_id");
            $stmtResp->bindParam(':area_id', $areaId, PDO::PARAM_INT);
            $stmtResp->execute();
            $responsablesCount = $stmtResp->fetchColumn();

           
            $stmtPers = $this->conn->prepare("SELECT COUNT(*) FROM personal_area WHERE area_id = :area_id");
            $stmtPers->bindParam(':area_id', $areaId, PDO::PARAM_INT);
            $stmtPers->execute();
            $personalCount = $stmtPers->fetchColumn();

            return [
                'nombre' => $nombreSubdivision,
                'responsables_count' => $responsablesCount,
                'personal_count' => $personalCount
            ];

        } catch (PDOException $e) {
            error_log("Error PDO al obtener preview de subdivisión (data_id: $dataId): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene los reportes asociados a un área o subdivisión por su data_id
     * @param string $dataId 
     * @return array 
     */
    public function obtenerReportesPorDataId($dataId) {
        if (!$this->conn) return []; // Verificar conexión
        
        try {
            // Query para buscar reportes asociados al área/subdivisión con el data_id específico
            $query = "
                SELECT r.*, 
                       a.nombre as area_nombre, 
                       u.nombre as usuario_nombre, 
                       u.apellido as usuario_apellido,
                       e.nombre as estado_nombre,
                       t.nombre as tipo_nombre
                FROM reportes r
                LEFT JOIN subdivisiones_area a ON r.area_id = a.area_id AND r.data_id = a.data_id
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                LEFT JOIN estados_reporte e ON r.estado_id = e.id
                LEFT JOIN tipos_reporte t ON r.tipo_id = t.id
                WHERE r.data_id = :data_id
                ORDER BY r.fecha_creacion DESC
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':data_id', $dataId, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error al obtener reportes por data_id: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene resumen de reportes para un área
     * @param string $dataId 
     * @return array 
     */
    public function obtenerResumenReportes($dataId) {
        if (!$this->conn) return null; // Verificar conexión
        
        try {
            // Obtener conteo total de reportes
            $query = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN e.nombre = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
                    SUM(CASE WHEN e.nombre = 'Resuelto' THEN 1 ELSE 0 END) as resueltos
                FROM reportes r
                LEFT JOIN estados_reporte e ON r.estado_id = e.id
                WHERE r.data_id = :data_id
            ";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':data_id', $dataId, PDO::PARAM_STR);
            $stmt->execute();
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
           
            return [
                'total' => intval($resultado['total'] ?? 0),
                'pendientes' => intval($resultado['pendientes'] ?? 0),
                'resueltos' => intval($resultado['resueltos'] ?? 0)
            ];
            
        } catch (PDOException $e) {
            error_log("Error al obtener resumen de reportes: " . $e->getMessage());
            return null;
        }
    }
}
?> 