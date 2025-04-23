<?php
// Asegurarnos de que no haya output previo
if (ob_get_length()) ob_clean();

// Deshabilitar la visualización de errores PHP
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Establecer el header de Content-Type a application/json
header('Content-Type: application/json');

require_once '../includes/conexion.php';
require_once '../includes/mapa_interactivo.php';

// Verificar la conexión a la base de datos
if (!isset($conexion) || !$conexion) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error: No se pudo establecer la conexión con la base de datos'
    ]);
    exit;
}

try {
    // Inicializar la clase MapaInteractivo
    $mapaInteractivo = new MapaInteractivo();

    // Verificar si se ha enviado una acción
    if (!isset($_POST['accion'])) {
        throw new Exception('No se ha especificado una acción');
    }

    $accion = $_POST['accion'];
    $respuesta = ['exito' => false, 'mensaje' => 'Acción no válida', 'datos' => null];
    
    switch ($accion) {
        case 'verificar_subdivisiones':
            if (isset($_POST['area_id'])) {
                $areaId = $_POST['area_id'];
                
                // Loguear el área que estamos verificando
                error_log("Verificando subdivisiones para area_id: " . $areaId);
                
                // Obtener el área por su data_id
                $area = $mapaInteractivo->obtenerAreaPorDataId($areaId);
                
                if ($area) {
                    error_log("Área encontrada en la base de datos: " . json_encode($area));
                    
                    // Obtener las subdivisiones existentes en la base de datos
                    $subdivisionesDB = $mapaInteractivo->obtenerSubdivisionesArea($area['id']);
                    
                    // Si ya hay subdivisiones registradas, retornar éxito
                    if (!empty($subdivisionesDB)) {
                        $respuesta = [
                            'exito' => true,
                            'mensaje' => 'Todas las subdivisiones ya están registradas.',
                            'datos' => []
                        ];
                    } else {
                        // Si no hay subdivisiones, intentar registrarlas
                        $subdivisionesSVG = $mapaInteractivo->obtenerSubdivisionesSVG($area['id']);
                        
                        if (!empty($subdivisionesSVG)) {
                            $exito = true;
                            foreach ($subdivisionesSVG as $sub) {
                                $resultado = $mapaInteractivo->guardarSubdivision(
                                    $area['id'],
                                    $sub['nombre'],
                                    $sub['svg_id'],
                                    $sub['tipo_id'],
                                    $sub['aula_id'],
                                    $sub['data_id']
                                );
                                
                                if (!$resultado) {
                                    $exito = false;
                                    error_log("Error al guardar subdivisión: " . json_encode($sub));
                                }
                            }
                            
                            $respuesta = [
                                'exito' => $exito,
                                'mensaje' => $exito ? 'Se han registrado las subdivisiones correctamente.' : 'Error al registrar algunas subdivisiones.',
                                'datos' => $subdivisionesSVG
                            ];
                        } else {
                            $respuesta = [
                                'exito' => true,
                                'mensaje' => 'No hay subdivisiones para registrar.',
                                'datos' => []
                            ];
                        }
                    }
                } else {
                    error_log("No se encontró el área con data_id: " . $areaId);
                    $respuesta = [
                        'exito' => false,
                        'mensaje' => 'No se encontró el área especificada (data_id: ' . $areaId . ')',
                        'datos' => null
                    ];
                }
            } else {
                error_log("Falta el parámetro area_id en la solicitud");
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'Se requiere el parámetro area_id',
                    'datos' => null
                ];
            }
            break;
            
        case 'obtener_mapa':
            // Obtener el mapa principal
            $mapas = $mapaInteractivo->obtenerMapas();
            if (!empty($mapas)) {
                $mapaId = $mapas[0]['id']; // Tomamos el primer mapa
                $areas = $mapaInteractivo->obtenerAreasMapa($mapaId);
                
                // Para cada área, obtener sus subdivisiones y asegurarnos de que tenga los datos necesarios
                foreach ($areas as &$area) {
                    // Asegurarnos de que tenga los campos requeridos
                    $area['svg_id'] = $area['svg_id'] ?? 'area-' . $area['id'];
                    $area['path_data'] = $area['path_data'] ?? null;
                    $area['data_id'] = $area['data_id'] ?? $area['svg_id'];
                    $area['nombre'] = $area['nombre'] ?? 'Área ' . $area['id'];
                    $area['color'] = $area['color'] ?? '#D3D3D3';
                    
                    // Obtener subdivisiones y sus datos
                    $subdivisiones = $mapaInteractivo->obtenerSubdivisionesArea($area['id']);
                    
                    // Log para debug
                    error_log("Obteniendo subdivisiones para área {$area['nombre']} (ID: {$area['id']})");
                    error_log("Número de subdivisiones encontradas: " . count($subdivisiones));
                    
                    foreach ($subdivisiones as &$subdivision) {
                        // Asegurarnos de que cada subdivisión tenga los campos requeridos
                        $subdivision['svg_id'] = $subdivision['svg_id'] ?? 'sub-' . $subdivision['id'];
                        $subdivision['path_data'] = $subdivision['path_data'] ?? null;
                        $subdivision['data_id'] = $subdivision['data_id'] ?? $subdivision['svg_id'];
                        $subdivision['nombre'] = $subdivision['nombre'] ?? 'Subdivisión ' . $subdivision['id'];
                        $subdivision['color'] = $subdivision['color'] ?? '#D3D3D3';
                        
                        // Log para debug
                        error_log("Subdivisión procesada: " . json_encode($subdivision));
                    }
                    unset($subdivision); // Importante para evitar referencias
                    
                    $area['subdivisiones'] = $subdivisiones;
                    $area['responsables'] = $mapaInteractivo->obtenerResponsablesArea($area['id']);
                    $area['personal'] = $mapaInteractivo->obtenerPersonalArea($area['id']);
                }
                unset($area); // Importante para evitar referencias
                
                $respuesta = [
                    'exito' => true,
                    'mensaje' => 'Mapa obtenido correctamente',
                    'datos' => [
                        'mapa' => array_merge($mapas[0], ['viewbox' => '0 0 1000 1000']),
                        'areas' => $areas
                    ]
                ];
                
                // Log para debug
                error_log("Respuesta completa: " . json_encode($respuesta));
            } else {
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'No se encontraron mapas',
                    'datos' => null
                ];
            }
            break;
            
        case 'obtener_area':
            if (isset($_POST['data_id'])) {
                $dataId = $_POST['data_id'];
                $area = $mapaInteractivo->obtenerAreaPorDataId($dataId);
                
                if ($area) {
                    $area['subdivisiones'] = $mapaInteractivo->obtenerSubdivisionesArea($area['id']);
                    $area['responsables'] = $mapaInteractivo->obtenerResponsablesArea($area['id']);
                    $area['personal'] = $mapaInteractivo->obtenerPersonalArea($area['id']);
                    
                    $respuesta = [
                        'exito' => true,
                        'mensaje' => 'Área obtenida correctamente',
                        'datos' => $area
                    ];
                } else {
                    $respuesta = [
                        'exito' => false,
                        'mensaje' => 'No se encontró el área',
                        'datos' => null
                    ];
                }
            } else {
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'Se requiere el parámetro data_id',
                    'datos' => null
                ];
            }
            break;
            
        case 'obtener_subdivision':
            if (isset($_POST['data_id'])) {
                $dataId = $_POST['data_id'];
                error_log("Intentando obtener subdivisión con data_id: " . $dataId);
                
                $subdivision = $mapaInteractivo->obtenerSubdivisionPorDataId($dataId);
                error_log("Resultado de la búsqueda: " . json_encode($subdivision));
                
                if ($subdivision) {
                    // Obtener resumen de reportes para esta subdivisión
                    $reportesResumen = $mapaInteractivo->obtenerResumenReportes($dataId);
                    
                    // Añadir resumen de reportes a los datos de la subdivisión
                    $subdivision['reportes_resumen'] = $reportesResumen;
                    
                    $respuesta = [
                        'exito' => true,
                        'mensaje' => 'Subdivisión obtenida correctamente',
                        'datos' => $subdivision
                    ];
                } else {
                    $respuesta = [
                        'exito' => false,
                        'mensaje' => 'No se encontró la subdivisión',
                        'datos' => null
                    ];
                }
            } else {
                error_log("Falta el parámetro data_id en la solicitud");
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'Se requiere el parámetro data_id',
                    'datos' => null
                ];
            }
            break;
            
        case 'guardar_area':
            if (isset($_POST['mapa_id'], $_POST['nombre'], $_POST['tipo'], $_POST['svg_id'], $_POST['data_id'])) {
                $mapaId = $_POST['mapa_id'];
                $nombre = $_POST['nombre'];
                $tipo = $_POST['tipo'];
                $svgId = $_POST['svg_id'];
                $color = isset($_POST['color']) ? $_POST['color'] : null;
                $aulaId = isset($_POST['aula_id']) ? $_POST['aula_id'] : null;
                $dataId = $_POST['data_id'];
                
                $resultado = $mapaInteractivo->guardarArea($mapaId, $nombre, $tipo, $svgId, $color, $aulaId, $dataId);
                
                if ($resultado) {
                    $respuesta = [
                        'exito' => true,
                        'mensaje' => 'Área guardada correctamente',
                        'datos' => ['id' => $resultado]
                    ];
                } else {
                    $respuesta = [
                        'exito' => false,
                        'mensaje' => 'Error al guardar el área',
                        'datos' => null
                    ];
                }
            } else {
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'Faltan parámetros requeridos',
                    'datos' => null
                ];
            }
            break;
            
        case 'guardar_subdivision':
            if (isset($_POST['area_id'], $_POST['nombre'], $_POST['svg_id'], $_POST['tipo'], $_POST['data_id'])) {
                $areaId = $_POST['area_id'];
                $nombre = $_POST['nombre'];
                $svgId = $_POST['svg_id'];
                $tipo = $_POST['tipo'];
                $aulaId = isset($_POST['aula_id']) ? $_POST['aula_id'] : null;
                $dataId = $_POST['data_id'];
                
                $resultado = $mapaInteractivo->guardarSubdivision($areaId, $nombre, $svgId, $tipo, $aulaId, $dataId);
                
                if ($resultado) {
                    $respuesta = [
                        'exito' => true,
                        'mensaje' => 'Subdivisión guardada correctamente',
                        'datos' => ['id' => $resultado]
                    ];
                } else {
                    $respuesta = [
                        'exito' => false,
                        'mensaje' => 'Error al guardar la subdivisión',
                        'datos' => null
                    ];
                }
            } else {
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'Faltan parámetros requeridos',
                    'datos' => null
                ];
            }
            break;
            
        case 'asignar_responsable':
            if (isset($_POST['area_id'], $_POST['usuario_id'], $_POST['cargo'])) {
                $areaId = $_POST['area_id'];
                $usuarioId = $_POST['usuario_id'];
                $cargo = $_POST['cargo'];
                
                $resultado = $mapaInteractivo->asignarResponsable($areaId, $usuarioId, $cargo);
                
                if ($resultado) {
                    $respuesta = [
                        'exito' => true,
                        'mensaje' => 'Responsable asignado correctamente',
                        'datos' => ['id' => $resultado]
                    ];
                } else {
                    $respuesta = [
                        'exito' => false,
                        'mensaje' => 'Error al asignar el responsable',
                        'datos' => null
                    ];
                }
            } else {
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'Faltan parámetros requeridos',
                    'datos' => null
                ];
            }
            break;
            
        case 'asignar_personal':
            if (isset($_POST['area_id'], $_POST['usuario_id'], $_POST['cargo'])) {
                $areaId = $_POST['area_id'];
                $usuarioId = $_POST['usuario_id'];
                $cargo = $_POST['cargo'];
                
                $resultado = $mapaInteractivo->asignarPersonal($areaId, $usuarioId, $cargo);
                
                if ($resultado) {
                    $respuesta = [
                        'exito' => true,
                        'mensaje' => 'Personal asignado correctamente',
                        'datos' => ['id' => $resultado]
                    ];
                } else {
                    $respuesta = [
                        'exito' => false,
                        'mensaje' => 'Error al asignar el personal',
                        'datos' => null
                    ];
                }
            } else {
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'Faltan parámetros requeridos',
                    'datos' => null
                ];
            }
            break;
            
        case 'actualizar_area':
            if (isset($_POST['id'], $_POST['nombre'], $_POST['tipo'], $_POST['svg_id'])) {
                $id = $_POST['id'];
                $nombre = $_POST['nombre'];
                $tipo = $_POST['tipo'];
                $svgId = $_POST['svg_id'];
                $color = isset($_POST['color']) ? $_POST['color'] : null;
                $aulaId = isset($_POST['aula_id']) ? $_POST['aula_id'] : null;
                
                $resultado = $mapaInteractivo->actualizarArea($id, $nombre, $tipo, $svgId, $color, $aulaId);
                
                if ($resultado) {
                    $respuesta = [
                        'exito' => true,
                        'mensaje' => 'Área actualizada correctamente',
                        'datos' => null
                    ];
                } else {
                    $respuesta = [
                        'exito' => false,
                        'mensaje' => 'Error al actualizar el área',
                        'datos' => null
                    ];
                }
            } else {
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'Faltan parámetros requeridos',
                    'datos' => null
                ];
            }
            break;
            
        case 'actualizar_subdivision':
            if (isset($_POST['id'], $_POST['nombre'], $_POST['svg_id'], $_POST['tipo'])) {
                $id = $_POST['id'];
                $nombre = $_POST['nombre'];
                $svgId = $_POST['svg_id'];
                $tipo = $_POST['tipo'];
                $aulaId = isset($_POST['aula_id']) ? $_POST['aula_id'] : null;
                
                $resultado = $mapaInteractivo->actualizarSubdivision($id, $nombre, $svgId, $tipo, $aulaId);
                
                if ($resultado) {
                    $respuesta = [
                        'exito' => true,
                        'mensaje' => 'Subdivisión actualizada correctamente',
                        'datos' => null
                    ];
                } else {
                    $respuesta = [
                        'exito' => false,
                        'mensaje' => 'Error al actualizar la subdivisión',
                        'datos' => null
                    ];
                }
            } else {
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'Faltan parámetros requeridos',
                    'datos' => null
                ];
            }
            break;
            
        case 'actualizar_subdivision_info':
            if (isset($_POST['data_id'], $_POST['nombre'])) { // data_id y nombre son mínimos
                $dataId = $_POST['data_id'];
                $nombre = $_POST['nombre'];
                // tipo_id es opcional, puede llegar vacío o no existir
                $tipoId = isset($_POST['tipo_id']) ? $_POST['tipo_id'] : null; 

                // Llamar a la nueva función en la clase MapaInteractivo
                $resultado = $mapaInteractivo->actualizarSubdivisionInfo($dataId, $nombre, $tipoId);
                
                if ($resultado) {
                    $respuesta = [
                        'exito' => true,
                        'mensaje' => 'Información de subdivisión actualizada correctamente',
                        'datos' => null // O podrías devolver los datos actualizados si es útil
                    ];
                } else {
                    $respuesta = [
                        'exito' => false,
                        'mensaje' => 'Error al actualizar la información de la subdivisión',
                        'datos' => null
                    ];
                }
            } else {
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'Faltan parámetros requeridos (data_id, nombre)',
                    'datos' => null
                ];
            }
            break;
            
        case 'eliminar_area':
            if (isset($_POST['id'])) {
                $id = $_POST['id'];
                
                $resultado = $mapaInteractivo->eliminarArea($id);
                
                if ($resultado) {
                    $respuesta = [
                        'exito' => true,
                        'mensaje' => 'Área eliminada correctamente',
                        'datos' => null
                    ];
                } else {
                    $respuesta = [
                        'exito' => false,
                        'mensaje' => 'Error al eliminar el área',
                        'datos' => null
                    ];
                }
            } else {
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'Se requiere el parámetro id',
                    'datos' => null
                ];
            }
            break;
            
        case 'eliminar_subdivision':
            if (isset($_POST['id'])) {
                $id = $_POST['id'];
                
                $resultado = $mapaInteractivo->eliminarSubdivision($id);
                
                if ($resultado) {
                    $respuesta = [
                        'exito' => true,
                        'mensaje' => 'Subdivisión eliminada correctamente',
                        'datos' => null
                    ];
                } else {
                    $respuesta = [
                        'exito' => false,
                        'mensaje' => 'Error al eliminar la subdivisión',
                        'datos' => null
                    ];
                }
            } else {
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'Se requiere el parámetro id',
                    'datos' => null
                ];
            }
            break;
            
        case 'obtener_preview_subdivision':
            if (isset($_POST['data_id'])) {
                $dataId = $_POST['data_id'];
                $previewData = $mapaInteractivo->obtenerPreviewSubdivision($dataId);
                
                if ($previewData !== null) {
                    $respuesta = [
                        'exito' => true,
                        'mensaje' => 'Preview obtenido correctamente',
                        'datos' => $previewData
                    ];
                } else {
                    $respuesta = [
                        'exito' => false,
                        'mensaje' => 'No se pudo obtener la información de vista previa para la subdivisión.',
                        'datos' => null
                    ];
                }
            } else {
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'Se requiere el parámetro data_id',
                    'datos' => null
                ];
            }
            break;
            
        case 'guardar_cambios_edicion':
            guardarCambiosEdicion();
            return; // Esta función maneja su propia respuesta
            break;
            
        case 'eliminar_elemento':
            if (!isset($_POST['elemento_id']) || !isset($_POST['es_area'])) {
                echo json_encode([
                    'exito' => false,
                    'mensaje' => 'Faltan parámetros requeridos'
                ]);
                exit;
            }

            $elementoId = $_POST['elemento_id'];
            $esArea = $_POST['es_area'] === '1';

            try {
                // Iniciar transacción
                pg_query($conexion, "BEGIN");

                if ($esArea) {
                    // Primero eliminar todas las subdivisiones del área
                    $querySubdivisiones = "DELETE FROM subdivisiones_area WHERE area_id IN (SELECT id FROM areas_mapa WHERE data_id = $1)";
                    $resultSub = pg_query_params($conexion, $querySubdivisiones, array($elementoId));
                    
                    if (!$resultSub) {
                        throw new Exception('Error al eliminar las subdivisiones: ' . pg_last_error($conexion));
                    }

                    // Luego eliminar el área
                    $queryArea = "DELETE FROM areas_mapa WHERE data_id = $1";
                    $resultArea = pg_query_params($conexion, $queryArea, array($elementoId));
                    
                    if (!$resultArea) {
                        throw new Exception('Error al eliminar el área: ' . pg_last_error($conexion));
                    }

                    $mensaje = 'Área y sus subdivisiones eliminadas correctamente';
                } else {
                    // Eliminar solo la subdivisión
                    $querySubdivision = "DELETE FROM subdivisiones_area WHERE data_id = $1";
                    $resultSub = pg_query_params($conexion, $querySubdivision, array($elementoId));
                    
                    if (!$resultSub) {
                        throw new Exception('Error al eliminar la subdivisión: ' . pg_last_error($conexion));
                    }

                    $mensaje = 'Subdivisión eliminada correctamente';
                }

                // Confirmar transacción
                pg_query($conexion, "COMMIT");

                echo json_encode([
                    'exito' => true,
                    'mensaje' => $mensaje
                ]);
            } catch (Exception $e) {
                // Revertir transacción en caso de error
                pg_query($conexion, "ROLLBACK");
                
                echo json_encode([
                    'exito' => false,
                    'mensaje' => 'Error al eliminar: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'obtener_reportes':
            if (isset($_POST['data_id'])) {
                $dataId = $_POST['data_id'];
                $reportes = $mapaInteractivo->obtenerReportesPorDataId($dataId);
                
                // Incluir información sobre el área
                $areaInfo = null;
                // Primero buscar en subdivisiones
                $subdivision = $mapaInteractivo->obtenerSubdivisionPorDataId($dataId);
                if ($subdivision) {
                    $areaInfo = [
                        'id' => $subdivision['id'],
                        'area_id' => $subdivision['area_id'],
                        'nombre' => $subdivision['nombre'],
                        'tipo' => $subdivision['tipo_nombre'] ?? 'Sin tipo'
                    ];
                } else {
                    // Si no es una subdivisión, buscar como área
                    $area = $mapaInteractivo->obtenerAreaPorDataId($dataId);
                    if ($area) {
                        $areaInfo = [
                            'id' => $area['id'],
                            'nombre' => $area['nombre'],
                            'tipo' => 'Área principal'
                        ];
                    }
                }
                
                $respuesta = [
                    'exito' => true,
                    'mensaje' => count($reportes) > 0 ? 'Reportes obtenidos correctamente' : 'No hay reportes para esta área',
                    'datos' => [
                        'reportes' => $reportes,
                        'area' => $areaInfo
                    ]
                ];
            } else {
                $respuesta = [
                    'exito' => false,
                    'mensaje' => 'Se requiere el parámetro data_id',
                    'datos' => null
                ];
            }
            break;
            
        default:
            $respuesta = [
                'exito' => false,
                'mensaje' => 'Acción no reconocida',
                'datos' => null
            ];
            break;
    }
    
    // Devolver la respuesta en formato JSON
    echo json_encode($respuesta);

} catch (Exception $e) {
    // Log del error para debugging
    error_log("Error en mapa_interactivo_ajax.php: " . $e->getMessage());
    
    // Devolver respuesta de error en JSON
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Error en el servidor: ' . $e->getMessage(),
        'datos' => null
    ]);
}

// Función para guardar cambios
function guardarCambiosEdicion() {
    global $conexion;
    
    try {
        // Verificar la conexión
        if (!$conexion) {
            throw new Exception('No hay conexión a la base de datos disponible');
        }

        if (!isset($_POST['cambios'])) {
            throw new Exception('No se recibieron datos de cambios');
        }

        $cambiosJSON = $_POST['cambios'];
        $cambios = json_decode($cambiosJSON, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
        }

        if (!is_array($cambios)) {
            throw new Exception('El formato de los cambios no es válido');
        }

        if (empty($cambios)) {
            echo json_encode(['exito' => true, 'mensaje' => 'No hay cambios para procesar']);
            return;
        }

        // Log para debugging
        error_log("Iniciando transacción con los siguientes cambios: " . print_r($cambios, true));

        // Iniciar transacción
        $result = pg_query($conexion, "BEGIN");
        if (!$result) {
            throw new Exception('Error al iniciar la transacción: ' . pg_last_error($conexion));
        }

        foreach ($cambios as $cambio) {
            if (!isset($cambio['data_id'])) {
                throw new Exception('Falta data_id en uno de los cambios');
            }

            // Log para debugging
            error_log("Procesando cambio para data_id: " . $cambio['data_id']);

            if (isset($cambio['esNuevo']) && $cambio['esNuevo']) {
                // Verificar si es una subdivisión basándonos en la presencia de parent_id
                if (isset($cambio['parent_id'])) {
                    insertarNuevaSubdivision($conexion, $cambio);
                } else {
                    insertarNuevaArea($conexion, $cambio);
                }
            } else {
                actualizarElemento($conexion, $cambio);
            }
        }

        // Confirmar transacción
        $result = pg_query($conexion, "COMMIT");
        if (!$result) {
            throw new Exception('Error al confirmar la transacción: ' . pg_last_error($conexion));
        }

        error_log("Transacción completada exitosamente");
        
        echo json_encode(['exito' => true, 'mensaje' => 'Cambios guardados correctamente']);

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        if (isset($conexion) && $conexion) {
            pg_query($conexion, "ROLLBACK");
        }
        
        // Log del error
        error_log("Error en guardarCambiosEdicion: " . $e->getMessage());
        
        // Devolver respuesta de error
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Error al guardar los cambios: ' . $e->getMessage()
        ]);
    }
}

function insertarNuevaArea($conexion, $cambio) {
    if (!isset($cambio['nombre'], $cambio['color'], $cambio['data_id'], $cambio['path_data'])) {
        throw new Exception('Faltan campos requeridos para insertar nueva área');
    }

    $query = "INSERT INTO areas_mapa (mapa_id, nombre, color, data_id, path_data) 
              VALUES (1, $1, $2, $3, $4)";
    
    $params = [
        $cambio['nombre'],
        $cambio['color'],
        $cambio['data_id'],
        $cambio['path_data']
    ];

    $result = pg_query_params($conexion, $query, $params);
    if (!$result) {
        throw new Exception('Error al insertar nueva área: ' . pg_last_error($conexion));
    }
}

function insertarNuevaSubdivision($conexion, $cambio) {
    if (!isset($cambio['nombre'], $cambio['color'], $cambio['data_id'], $cambio['path_data'], $cambio['parent_id'])) {
        throw new Exception('Faltan campos requeridos para insertar nueva subdivisión');
    }

    // Primero obtener el id del área padre
    $queryArea = "SELECT id FROM areas_mapa WHERE data_id = $1";
    $resultArea = pg_query_params($conexion, $queryArea, [$cambio['parent_id']]);
    if (!$resultArea) {
        throw new Exception('Error al obtener área padre: ' . pg_last_error($conexion));
    }
    
    $areaRow = pg_fetch_assoc($resultArea);
    if (!$areaRow) {
        throw new Exception('Área padre no encontrada para data_id: ' . $cambio['parent_id']);
    }

    $query = "INSERT INTO subdivisiones_area (area_id, nombre, color, data_id, path_data) 
              VALUES ($1, $2, $3, $4, $5)";
    
    $params = [
        $areaRow['id'],
        $cambio['nombre'],
        $cambio['color'],
        $cambio['data_id'],
        $cambio['path_data']
    ];

    $result = pg_query_params($conexion, $query, $params);
    if (!$result) {
        throw new Exception('Error al insertar nueva subdivisión: ' . pg_last_error($conexion));
    }
}

function actualizarElemento($conexion, $cambio) {
    $sets = [];
    $params = [];
    $paramCount = 1;

    // Validar que al menos hay un campo para actualizar
    $camposActualizables = ['color', 'path_data', 'x', 'y', 'width', 'height', 'nombre', 'tipo_id'];
    $hayActualizacion = false;
    foreach ($camposActualizables as $campo) {
        if (isset($cambio[$campo])) {
            $hayActualizacion = true;
            break;
        }
    }

    if (!$hayActualizacion) {
        return; // No hay nada que actualizar
    }

    // Si tenemos x, y, width, height, generar el path_data
    if (isset($cambio['x']) && isset($cambio['y']) && isset($cambio['width']) && isset($cambio['height'])) {
        $x = $cambio['x'];
        $y = $cambio['y'];
        $width = $cambio['width'];
        $height = $cambio['height'];
        $cambio['path_data'] = "M{$x} {$y} h {$width} v {$height} h -{$width} Z";
    }

    if (isset($cambio['color'])) {
        $sets[] = "color = $" . $paramCount++;
        $params[] = $cambio['color'];
    }
    if (isset($cambio['path_data'])) {
        $sets[] = "path_data = $" . $paramCount++;
        $params[] = $cambio['path_data'];
    }
    // Añadir nombre si está presente
    if (isset($cambio['nombre'])) {
        $sets[] = "nombre = $" . $paramCount++;
        $params[] = $cambio['nombre'];
    }
    // Añadir tipo_id si está presente
    if (isset($cambio['tipo_id'])) {
        if ($cambio['tipo_id'] === '' || $cambio['tipo_id'] === null) {
            $sets[] = "tipo_id = NULL";
        } else {
            $sets[] = "tipo_id = $" . $paramCount++;
            $params[] = $cambio['tipo_id'];
        }
    }

    if (empty($sets)) {
        return; // No hay nada que actualizar
    }

    // Añadir data_id como último parámetro
    $params[] = $cambio['data_id'];

    // Primero intentar actualizar en subdivisiones_area
    $querySub = "UPDATE subdivisiones_area SET " . implode(", ", $sets) . " WHERE data_id = $" . $paramCount;
    $resultSub = pg_query_params($conexion, $querySub, $params);
    
    if (!$resultSub) {
        throw new Exception('Error al actualizar en subdivisiones_area: ' . pg_last_error($conexion));
    }
    
    // Si no se actualizó ninguna fila en subdivisiones_area, intentar en areas_mapa
    if (pg_affected_rows($resultSub) === 0) {
        $queryArea = "UPDATE areas_mapa SET " . implode(", ", $sets) . " WHERE data_id = $" . $paramCount;
        $resultArea = pg_query_params($conexion, $queryArea, $params);
        
        if (!$resultArea) {
            throw new Exception('Error al actualizar en areas_mapa: ' . pg_last_error($conexion));
        }
        
        if (pg_affected_rows($resultArea) === 0) {
            throw new Exception('No se encontró el elemento para actualizar con data_id: ' . $cambio['data_id']);
        }
    }
}
?> 