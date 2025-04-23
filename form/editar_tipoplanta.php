<?php
require_once '../includes/conexion.php'; // Incluir conexión PDO ($conn)

class EditarTipoPlanta {
    private $db; // Debe ser PDO
    
    public function __construct($conexion) {
        // Asegurarse de recibir un objeto PDO
        if ($conexion instanceof PDO) {
            $this->db = $conexion;
        } else {
            error_log("Error: EditarTipoPlanta no recibió una conexión PDO válida.");
            $this->db = null;
        }
    }

    private function obtenerTipos() {
        if (!$this->db) {
            return []; // No hay conexión
        }
        try {
            // Obtener todos los tipos, activos e inactivos, para mostrarlos
            $stmt = $this->db->query("SELECT id, nombre, activo, fecha_creacion FROM tipos_area ORDER BY nombre");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error PDO al obtener tipos: " . $e->getMessage());
            return [];
        }
    }

    public function mostrarFormulario() {
        $tipos = $this->obtenerTipos(); // Obtener los datos reales

        ?>
        <style>
            .modal-tipos {
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

            .contenedor-tipos {
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

            .tabla-tipos {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }

            .tabla-tipos th, 
            .tabla-tipos td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }

            .tabla-tipos th {
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

            .btn-anadir, .btn-inhabilitar {
                padding: 8px 16px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 500;
            }

            .btn-anadir {
                background-color: #28a745;
                color: white;
            }

            .btn-inhabilitar {
                background-color: #dc3545;
                color: white;
            }

            /* Estilos para los checkboxes */
            .checkbox-personalizado {
                width: 18px;
                height: 18px;
                cursor: pointer;
            }

            .tabla-tipos thead th:first-child,
            .tabla-tipos tbody td:first-child {
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

            /* Estilo para filas seleccionadas */
            .tabla-tipos tbody tr.seleccionada {
                background-color: #e8f5e9;
            }

            .tabla-tipos tbody tr:hover {
                background-color: #f5f5f5;
            }
        </style>

        <div id="modalTipos" class="modal-tipos">
            <div class="contenedor-tipos">
                <button class="btn-cerrar" id="btnCerrarModalTipos">&times;</button>
                <h2>Tipos de Planta</h2>
                
                <table class="tabla-tipos">
                    <thead>
                        <tr>
                            <th></th>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tipos)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: #666;">No se encontraron tipos de área.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tipos as $tipo): ?>
                                <tr data-tipo-id="<?php echo $tipo['id']; ?>" class="<?php echo $tipo['activo'] ? '' : 'fila-inactiva'; // Opcional: clase para estilo inactivo ?>">
                                    <td>
                                        <input type="checkbox" class="checkbox-personalizado checkbox-fila-tipo" data-id="<?php echo $tipo['id']; ?>"> 
                                    </td>
                                    <td><?php echo htmlspecialchars($tipo['id']); ?></td>
                                    <td><?php echo htmlspecialchars($tipo['nombre']); ?></td>
                                    <td><?php echo $tipo['activo'] ? 'Activo' : 'Inactivo'; ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($tipo['fecha_creacion']))); ?></td> 
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="botones-accion">
                    <button id="btnAnadirTipo" class="btn-anadir">+ Añadir</button> 
                    <button id="btnEditarTipo" class="btn-primary" style="display: none;">Editar</button>
                    <button id="btnHabilitarTipo" class="btn-success" style="display: none;">Habilitar</button>
                    <button id="btnInhabilitarTipo" class="btn-inhabilitar">Inhabilitar</button> 
                </div>
            </div>
        </div>

        <!-- Modal para Añadir Nuevo Tipo -->
        <div id="modalNuevoTipo" class="modal-tipos" style="display: none;">
            <div class="contenedor-tipos" style="max-width: 500px;">
                 <button class="btn-cerrar" id="btnCerrarModalNuevoTipo">&times;</button>
                 <h2>Añadir Nuevo Tipo de Planta</h2>
                 <div style="margin-top: 20px;">
                     <div class="form-group" style="margin-bottom: 15px;">
                         <label for="nuevoTipoNombre">Nombre:</label>
                         <input type="text" id="nuevoTipoNombre" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                     </div>
                     <div class="form-group">
                         <label for="nuevoTipoDescripcion">Descripción:</label>
                         <textarea id="nuevoTipoDescripcion" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                     </div>
                 </div>
                 <div class="botones-accion">
                     <button class="btn-secondary" id="btnCancelarNuevoTipo">Cancelar</button>
                     <button class="btn-anadir" id="btnGuardarNuevoTipo">Guardar Tipo</button>
                 </div>
            </div>
        </div>

        <?php
    }
}

// Crear instancia con la conexión PDO y mostrar el formulario
$editarTipo = new EditarTipoPlanta($conn ?? null); // Usar $conn de conexion.php
$editarTipo->mostrarFormulario();
?> 