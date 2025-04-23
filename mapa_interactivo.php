<?php
// Incluir archivos necesarios
require_once 'conexion.php';
require_once 'mapa_interactivo.php';

// Inicializar la clase MapaInteractivo
$mapaInteractivo = new MapaInteractivo();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa Interactivo - Escuela</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/mapa_interactivo.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Panel de control -->
            <div class="col-md-3 panel-control">
                <h2>Panel de Control</h2>
                
                <!-- Formulario para crear/editar área -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h4>Área</h4>
                    </div>
                    <div class="card-body">
                        <form id="formArea">
                            <div class="mb-3">
                                <label for="nombreArea" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombreArea" required>
                            </div>
                            <div class="mb-3">
                                <label for="tipoArea" class="form-label">Tipo</label>
                                <select class="form-select" id="tipoArea" required>
                                    <option value="aula">Aula</option>
                                    <option value="oficina">Oficina</option>
                                    <option value="laboratorio">Laboratorio</option>
                                    <option value="biblioteca">Biblioteca</option>
                                    <option value="gimnasio">Gimnasio</option>
                                    <option value="patio">Patio</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="svgIdArea" class="form-label">ID SVG</label>
                                <input type="text" class="form-control" id="svgIdArea" required>
                            </div>
                            <div class="mb-3">
                                <label for="dataIdArea" class="form-label">Data ID</label>
                                <input type="text" class="form-control" id="dataIdArea" required>
                            </div>
                            <div class="mb-3">
                                <label for="colorArea" class="form-label">Color</label>
                                <input type="color" class="form-control" id="colorArea">
                            </div>
                            <div class="mb-3">
                                <label for="aulaIdArea" class="form-label">ID de Aula (opcional)</label>
                                <input type="text" class="form-control" id="aulaIdArea">
                            </div>
                            <button type="button" class="btn btn-primary" id="btnGuardarArea">Guardar Área</button>
                            <button type="button" class="btn btn-danger" id="btnEliminarArea">Eliminar Área</button>
                        </form>
                    </div>
                </div>
                
                <!-- Formulario para crear/editar subdivisión -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h4>Subdivisión</h4>
                    </div>
                    <div class="card-body">
                        <form id="formSubdivision">
                            <div class="mb-3">
                                <label for="nombreSubdivision" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombreSubdivision" required>
                            </div>
                            <div class="mb-3">
                                <label for="tipoSubdivision" class="form-label">Tipo</label>
                                <select class="form-select" id="tipoSubdivision" required>
                                    <option value="escritorio">Escritorio</option>
                                    <option value="estante">Estante</option>
                                    <option value="equipo">Equipo</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="svgIdSubdivision" class="form-label">ID SVG</label>
                                <input type="text" class="form-control" id="svgIdSubdivision" required>
                            </div>
                            <div class="mb-3">
                                <label for="dataIdSubdivision" class="form-label">Data ID</label>
                                <input type="text" class="form-control" id="dataIdSubdivision" required>
                            </div>
                            <div class="mb-3">
                                <label for="aulaIdSubdivision" class="form-label">ID de Aula (opcional)</label>
                                <input type="text" class="form-control" id="aulaIdSubdivision">
                            </div>
                            <button type="button" class="btn btn-primary" id="btnGuardarSubdivision">Guardar Subdivisión</button>
                            <button type="button" class="btn btn-danger" id="btnEliminarSubdivision">Eliminar Subdivisión</button>
                        </form>
                    </div>
                </div>
                
                <!-- Formulario para asignar responsable -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h4>Asignar Responsable</h4>
                    </div>
                    <div class="card-body">
                        <form id="formResponsable">
                            <div class="mb-3">
                                <label for="usuarioIdResponsable" class="form-label">ID de Usuario</label>
                                <input type="text" class="form-control" id="usuarioIdResponsable" required>
                            </div>
                            <div class="mb-3">
                                <label for="cargoResponsable" class="form-label">Cargo</label>
                                <input type="text" class="form-control" id="cargoResponsable" required>
                            </div>
                            <button type="button" class="btn btn-primary" id="btnAsignarResponsable">Asignar Responsable</button>
                        </form>
                    </div>
                </div>
                
                <!-- Formulario para asignar personal -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h4>Asignar Personal</h4>
                    </div>
                    <div class="card-body">
                        <form id="formPersonal">
                            <div class="mb-3">
                                <label for="usuarioIdPersonal" class="form-label">ID de Usuario</label>
                                <input type="text" class="form-control" id="usuarioIdPersonal" required>
                            </div>
                            <div class="mb-3">
                                <label for="cargoPersonal" class="form-label">Cargo</label>
                                <input type="text" class="form-control" id="cargoPersonal" required>
                            </div>
                            <button type="button" class="btn btn-primary" id="btnAsignarPersonal">Asignar Personal</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Contenedor del mapa -->
            <div class="col-md-6">
                <div id="contenedorMapa" class="mapa-container"></div>
            </div>
            
            <!-- Panel de información -->
            <div class="col-md-3 panel-info">
                <h2>Información</h2>
                
                <!-- Información del área seleccionada -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h4>Información del Área</h4>
                    </div>
                    <div class="card-body">
                        <div id="contenedorInfoArea">
                            <p class="text-muted">Seleccione un área para ver su información</p>
                        </div>
                    </div>
                </div>
                
                <!-- Información de la subdivisión seleccionada -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h4>Información de la Subdivisión</h4>
                    </div>
                    <div class="card-body">
                        <div id="contenedorInfoSubdivision">
                            <p class="text-muted">Seleccione una subdivisión para ver su información</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/bootstrap.bundle.min.js"></script>
    <script src="js/mapa_interactivo.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar el mapa interactivo
        const mapaInteractivo = new MapaInteractivo();
        
        // Agregar event listeners a las subdivisiones
        document.querySelectorAll('.subdivision-item').forEach(subdivision => {
            subdivision.addEventListener('click', (event) => {
                mapaInteractivo.manejarClicSubdivision(event);
            });
        });
    });
    </script>
</body>
</html> 