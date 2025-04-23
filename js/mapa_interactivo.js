/**
 * Clase para manejar el mapa interactivo
 */
class MapaInteractivo {
    constructor() {
        this.mapas = [];
        this.areas = [];
        this.subdivisiones = [];
        this.responsables = [];
        this.personal = [];
        this.areaSeleccionada = null;
        this.subdivisionSeleccionada = null;
        this.tooltip = null;
        this.editMode = false;
        this.svgCanvas = null;
        this.editableElements = new Map(); // Para mantener referencia a elementos SVG.js
        this.cambiosPendientes = new Map(); // Para almacenar cambios en modo edición
        this.actionHistory = []; // Historial de acciones para deshacer
        this.elementoSeleccionadoParaEdicion = null; // Mantener referencia DOM
        this.elementoSeleccionadoVista = null; // Para rastrear la selección en modo vista
        this.handlerClickNormal = null;
        this.scale = 1;
        this.translateX = 0;
        this.translateY = 0;
        this.elementoEnfocadoId = null; 
        
        // Inicializar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.inicializar());
        } else {
            this.inicializar();
        }
    }
    
    inicializar() {
       
        this.handlerClickNormal = (event) => {
           
            if (this.editMode) return;

            const elemento = event.target.closest('.area-interactiva');

           
            if (!elemento) return;

            const dataId = elemento.getAttribute('data-id');
            const nombre = elemento.getAttribute('data-nombre');

            if (!dataId) return;

            // --- Lógica de selección visual ---
            if (this.elementoSeleccionadoVista && this.elementoSeleccionadoVista !== elemento) {
                this.elementoSeleccionadoVista.classList.remove('seleccionada');
            }
            elemento.classList.add('seleccionada');
            this.elementoSeleccionadoVista = elemento;
            // --- Fin lógica de selección visual ---

            document.dispatchEvent(new CustomEvent('areaSeleccionada', {
                detail: { id: dataId, nombre: nombre }
            }));

            this.cargarDatosSubdivision(dataId);
        };

            this.cargarMapa();
        this.crearTooltip();
            this.inicializarEventos();
        this.inicializarModoEdicion();
    }
    
    crearTooltip() {
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'tooltip';
        this.tooltip.style.display = 'none';
        document.body.appendChild(this.tooltip);
    }

    inicializarEventos() {
        this.inicializarEventosClick();
        this.inicializarEventosTooltip();
        this.inicializarBotones();
        this.inicializarEventosDeseleccion();
    }

    inicializarEventosClick() {
        // Usar la referencia guardada del handler
        document.addEventListener('click', this.handlerClickNormal);
    }

    inicializarEventosTooltip() {
        let tooltipTimeout;
        
        document.addEventListener('mousemove', (event) => {
            const elemento = event.target.closest('.area-interactiva');
            if (!elemento) {
                this.tooltip.style.display = 'none';
                return;
            }

            const dataId = elemento.getAttribute('data-id');
            if (!dataId) return;

            if (tooltipTimeout) clearTimeout(tooltipTimeout);

            tooltipTimeout = setTimeout(() => {
                this.mostrarTooltip(dataId, elemento);
            }, 200);
        });

        document.addEventListener('mouseout', (event) => {
            if (!event.target.closest('.area-interactiva')) {
                this.tooltip.style.display = 'none';
                if (tooltipTimeout) clearTimeout(tooltipTimeout);
            }
        });
    }

    async mostrarTooltip(dataId, elemento) {
        const datos = await this.obtenerDatosTooltip(dataId);
        if (!datos) return;

        const responsablesCount = datos.responsables?.length || 0;
        const personalCount = datos.personal?.length || 0;
        
        this.tooltip.innerHTML = `
            <strong>${datos.nombre || 'Sin nombre'}</strong><br>
            Responsables: ${responsablesCount}<br>
            Personal: ${personalCount}
        `;
        
        const rect = elemento.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
        
        this.tooltip.style.top = (rect.top + scrollTop - this.tooltip.offsetHeight - 10) + 'px';
        this.tooltip.style.left = (rect.left + scrollLeft + (rect.width / 2) - (this.tooltip.offsetWidth / 2)) + 'px';
        this.tooltip.style.display = 'block';
    }

    inicializarBotones() {
        const botones = {
            'btnGuardarArea': () => this.guardarArea(),
            'btnGuardarSubdivision': () => this.guardarSubdivision(),
            'btnAsignarResponsable': () => this.asignarResponsable(),
            'btnAsignarPersonal': () => this.asignarPersonal()
        };

        Object.entries(botones).forEach(([id, handler]) => {
            const button = document.getElementById(id);
            if (button) {
                 button.addEventListener('click', handler.bind(this));
            }
        });
        
        // Listener específico para el botón Filtrar
        const btnAbrirFiltro = document.getElementById('btn-abrir-filtro');
        if (btnAbrirFiltro) {
            btnAbrirFiltro.addEventListener('click', () => this.abrirModalFiltro());
        }
        
        // Listener para cerrar el modal de filtro
        const btnCerrarFiltro = document.getElementById('btn-cerrar-filtro-modal');
        if (btnCerrarFiltro) {
            btnCerrarFiltro.addEventListener('click', () => this.cerrarModalFiltro());
        }
    }
    
    /**
     * Carga el mapa desde el servidor
     */
    cargarMapa() {
        const formData = new FormData();
        formData.append('accion', 'obtener_mapa');
        
        fetch('../ajax/mapa_interactivo_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
                this.mapas = [data.datos.mapa];
                this.areas = data.datos.areas;
                
                // Procesar subdivisiones, responsables y personal
                this.areas.forEach(area => {
                    if (area.subdivisiones) {
                        this.subdivisiones = this.subdivisiones.concat(area.subdivisiones);
                    }
                    
                    if (area.responsables) {
                        this.responsables = this.responsables.concat(area.responsables);
                    }
                    
                    if (area.personal) {
                        this.personal = this.personal.concat(area.personal);
                    }
                });
                
                this.renderizarMapa();
            } else {
                console.error('Error al cargar el mapa:', data.mensaje);
                alert('Error al cargar el mapa: ' + data.mensaje);
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
            alert('Error en la solicitud al cargar el mapa');
        });
    }
    
    /**
     * Renderiza el mapa en el DOM
     */
    renderizarMapa() {
        if (this.mapas.length === 0) {
            console.error("No hay mapas para renderizar");
            return;
        }
        
        const svg = document.getElementById('mapa-svg');
        if (!svg) {
            console.error("No se encontró el elemento SVG para el mapa");
            return;
        }
        
        // Limpiar el SVG
        while (svg.firstChild) {
            svg.removeChild(svg.firstChild);
        }

        // Establecer viewBox
        const mapa = this.mapas[0];
        if (mapa.viewbox) {
            svg.setAttribute('viewBox', mapa.viewbox);
        }

        // Añadir estilos CSS para las áreas y subdivisiones
        const style = document.createElementNS("http://www.w3.org/2000/svg", "style");
        style.textContent = `
            .area-interactiva {
                transition: all 0.3s ease;
                cursor: pointer;
            }
            .area-padre {
                opacity: 0.3;
                pointer-events: none;
                transition: opacity 0.3s ease;
                z-index: 1;
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
            .area-interactiva:not(.area-padre) {
                z-index: 2;
            }
        `;
        svg.appendChild(style);
        
        // Renderizar áreas y subdivisiones
        if (this.areas && Array.isArray(this.areas)) {
            console.log('Total de áreas a renderizar:', this.areas.length);
            
            this.areas.forEach(area => {
                console.log('Procesando área:', area.nombre, area.svg_id, area.data_id);
                
                // Si el área tiene subdivisiones, renderizar primero el área con clase especial
                if (area.subdivisiones && area.subdivisiones.length > 0) {
                    console.log(`Área ${area.nombre} tiene ${area.subdivisiones.length} subdivisiones`);
                    
                    // Renderizar el área pero con clase especial para ocultarla inicialmente
                    const areaElement = this.renderizarElementoSVG(area, svg);
                    if (areaElement) {
                        areaElement.classList.add('area-padre');
                        areaElement.style.display = 'none';
                    }
                    
                    // Renderizar subdivisiones
                    area.subdivisiones.forEach(subdivision => {
                        subdivision.data_id = subdivision.data_id || subdivision.svg_id;
                        const subElement = this.renderizarElementoSVG(subdivision, svg);
                        if (subElement) {
                            subElement.setAttribute('data-parent-id', area.data_id);
                        }
                    });
                    } else {
                    // Si no tiene subdivisiones, renderizar el área normalmente
                    this.renderizarElementoSVG(area, svg);
                }
            });
        }
        
        // Inicializar controles de zoom
        this.inicializarZoom();
    }
    
    /**
     * Renderiza un elemento SVG (área o subdivisión)
     * @param {Object} elemento - El elemento a renderizar
     * @param {SVGElement} contenedor - El contenedor SVG donde se agregará el elemento
     */
    renderizarElementoSVG(elemento, contenedor) {
        const svgNS = 'http://www.w3.org/2000/svg';

        if (!elemento.data_id) {
            console.warn(`Elemento sin data_id`, elemento);
            return null;
        }
        
        let pathData = elemento.path_data;
        if (!pathData) {
            console.warn(`Elemento ${elemento.data_id} no tiene path_data definido. Se usará un rect por defecto.`);
            pathData = 'M10 10 h 50 v 50 h -50 Z';
        }
        
        const elementoSVG = document.createElementNS(svgNS, 'path');
        elementoSVG.setAttribute('d', pathData);
        
        elementoSVG.setAttribute('id', elemento.svg_id || elemento.data_id);
        elementoSVG.setAttribute('data-id', elemento.data_id);
        elementoSVG.setAttribute('data-nombre', elemento.nombre || 'Elemento sin nombre');
        elementoSVG.classList.add('area-interactiva');
        
        elementoSVG.setAttribute('fill', elemento.color || '#D3D3D3');
        elementoSVG.setAttribute('stroke', 'black');
        elementoSVG.setAttribute('stroke-width', '1');
                    
        const titulo = document.createElementNS(svgNS, 'title');
        titulo.textContent = elemento.nombre || 'Elemento sin nombre';
        elementoSVG.appendChild(titulo);
        
        contenedor.appendChild(elementoSVG);
        
        return elementoSVG;
    }
    
    /**
     * Carga los datos de una subdivisión y los muestra
     */
    cargarDatosSubdivision(dataId) {
        console.log("Cargando datos de subdivisión:", dataId);
        
        const formData = new FormData();
        formData.append('accion', 'obtener_subdivision');
        formData.append('data_id', dataId);
        
        fetch('../ajax/mapa_interactivo_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Datos recibidos de la subdivisión:", data);
            
            if (data.exito) {
                this.subdivisionSeleccionada = data.datos;
                this.mostrarDatosSubdivision(data.datos);
            } else {
                console.error("Error al obtener datos:", data.mensaje);
                // Si no se encuentra como subdivisión, intentar como área
                this.cargarDatosArea(dataId);
            }
        })
        .catch(error => {
            console.error("Error al cargar datos:", error);
        });
    }
    
    /**
     * Carga los datos de un área cuando no se encuentra como subdivisión
     */
    cargarDatosArea(dataId) {
        console.log("Intentando cargar como área:", dataId);
        
        const formData = new FormData();
        formData.append('accion', 'obtener_area');
        formData.append('data_id', dataId);
        
        fetch('../ajax/mapa_interactivo_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log("Datos de área recibidos:", data);
            
            if (data.exito) {
                this.areaSeleccionada = data.datos;
                this.mostrarDatosSubdivision(data.datos); // Usar el mismo método para mostrar
            } else {
                console.error("Error al obtener datos del área:", data.mensaje);
            }
        })
        .catch(error => {
            console.error("Error al cargar datos del área:", error);
        });
    }
    
    /**
     * Muestra los datos en el panel derecho
     */
    mostrarDatosSubdivision(datos) {
        console.log("Mostrando datos en panel:", datos);
        
        // Actualizar campos básicos independientemente del modo
        const tituloArea = document.getElementById('titulo-area');
        if (tituloArea) tituloArea.textContent = datos.nombre || 'Área sin nombre';
        
        const nombrePlanta = document.getElementById('nombre_planta');
        if (nombrePlanta) nombrePlanta.value = datos.nombre || '';
        
        const tipoPlanta = document.getElementById('tipo_planta');
        if (tipoPlanta) tipoPlanta.value = datos.tipo_id || '';

        // Solo mostrar datos de responsables y personal si NO estamos en modo edición
        if (!this.editMode) {
            // Actualizar responsables
            const responsablesArea = document.getElementById('responsables-area');
            if (responsablesArea) {
                if (datos.responsables && datos.responsables.length > 0) {
                    const responsablesText = datos.responsables
                        .map(r => `${r.nombre || ''} ${r.apellido || ''} (${r.cargo || 'N/A'})`)
                        .join('\n');
                    responsablesArea.value = responsablesText;
                } else {
                    responsablesArea.value = '';
                }
            }

            // Actualizar personal
            const personalArea = document.getElementById('personal-area');
            if (personalArea) {
                if (datos.personal && datos.personal.length > 0) {
                    const personalText = datos.personal
                        .map(p => `${p.nombre || ''} ${p.apellido || ''} (${p.cargo || 'N/A'})`)
                        .join('\n');
                    personalArea.value = personalText;
                } else {
                    personalArea.value = '';
                }
            }

            // Mostrar/ocultar sección de horario
            const seccionHorario = document.getElementById('seccion-horario');
            if (seccionHorario) {
                const esAula = datos.tipo_nombre?.toLowerCase() === 'aula';
                seccionHorario.style.display = esAula ? 'block' : 'none';
            }
            
            // Actualizar información de reportes si existe
            this.actualizarInfoReportes(datos);
        } else {
            // En modo edición, ocultar secciones de responsables, personal y horario
            const seccionResponsables = document.querySelector('.seccion-panel.responsables');
            const seccionPersonal = document.querySelector('.seccion-panel.personal');
            const seccionHorario = document.getElementById('seccion-horario');
            
            if (seccionResponsables) seccionResponsables.style.display = 'none';
            if (seccionPersonal) seccionPersonal.style.display = 'none';
            if (seccionHorario) seccionHorario.style.display = 'none';
        }
    }
    
    /**
     * Actualiza la información de reportes en el panel
     * @param {Object} datos Datos del área o subdivisión
     */
    actualizarInfoReportes(datos) {
        // Buscar la sección de reportes en el panel
        let seccionReportes = document.getElementById('seccion-reportes');
        
        // Si la sección no existe, crearla
        if (!seccionReportes) {
            const panelEdicion = document.getElementById('panel-edicion');
            if (!panelEdicion) return;
            
            seccionReportes = document.createElement('div');
            seccionReportes.id = 'seccion-reportes';
            seccionReportes.className = 'seccion-panel reportes';
            
            // Insertar antes de la sección de botones
            const botonesPanelSection = panelEdicion.querySelector('.botones-panel');
            if (botonesPanelSection) {
                panelEdicion.insertBefore(seccionReportes, botonesPanelSection);
            } else {
                panelEdicion.appendChild(seccionReportes);
            }
        }
        
        // Obtener el resumen de reportes si existe
        const resumen = datos.reportes_resumen || { total: 0, pendientes: 0, resueltos: 0 };
        
        // Actualizar el contenido
        seccionReportes.innerHTML = `
            <h3>Reportes</h3>
            <div class="reportes-info">
                <div class="reportes-stats">
                    <div class="stat-item">
                        <span class="stat-value">${resumen.total}</span>
                        <span class="stat-label">Total</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">${resumen.pendientes}</span>
                        <span class="stat-label">Pendientes</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">${resumen.resueltos}</span>
                        <span class="stat-label">Resueltos</span>
                    </div>
                </div>
                <button id="btn-ver-reportes" class="btn btn-info btn-block">
                    <i class="fas fa-list"></i> Ver todos los reportes
                </button>
            </div>
        `;
        
        // Mostrar/ocultar la sección según si hay reportes
        seccionReportes.style.display = 'block';
        
        // Añadir evento al botón
        const btnVerReportes = document.getElementById('btn-ver-reportes');
        if (btnVerReportes && datos.data_id) {
            btnVerReportes.onclick = () => cargarReportesArea(datos.data_id);
        }
        
        // Añadir estilos para la sección
        const styleEl = document.createElement('style');
        styleEl.textContent = `
            .reportes-info {
                padding: 10px;
                background-color: #f9f9f9;
                border-radius: 5px;
                margin-bottom: 15px;
            }
            .reportes-stats {
                display: flex;
                justify-content: space-between;
                margin-bottom: 15px;
            }
            .stat-item {
                text-align: center;
                flex: 1;
                padding: 5px;
                border-right: 1px solid #ddd;
            }
            .stat-item:last-child {
                border-right: none;
            }
            .stat-value {
                font-size: 1.5rem;
                font-weight: bold;
                display: block;
            }
            .stat-label {
                font-size: 0.8rem;
                color: #666;
            }
            #btn-ver-reportes {
                width: 100%;
                margin-top: 5px;
            }
        `;
        document.head.appendChild(styleEl);
    }
    
    /**
     * Guarda un área en la base de datos
     */
    guardarArea() {
        const formData = new FormData();
        formData.append('accion', 'guardar_area');
        formData.append('mapa_id', this.mapas[0].id);
        formData.append('nombre', document.getElementById('nombreArea').value);
        formData.append('tipo', document.getElementById('tipoArea').value);
        formData.append('svg_id', document.getElementById('svgIdArea').value);
        formData.append('data_id', document.getElementById('dataIdArea').value);
        
        const color = document.getElementById('colorArea').value;
        if (color) {
            formData.append('color', color);
        }
        
        const aulaId = document.getElementById('aulaIdArea').value;
        if (aulaId) {
            formData.append('aula_id', aulaId);
        }
        
        fetch('../ajax/mapa_interactivo_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
                alert('Área guardada correctamente');
                this.cargarMapa(); // Recargar el mapa para mostrar los cambios
            } else {
                console.error('Error al guardar el área:', data.mensaje);
                alert('Error al guardar el área: ' + data.mensaje);
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
            alert('Error en la solicitud al guardar el área');
        });
    }
    
    /**
     * Guarda una subdivisión en la base de datos
     */
    guardarSubdivision() {
        if (!this.areaSeleccionada) {
            alert('Debe seleccionar un área primero');
            return;
        }
        
        const formData = new FormData();
        formData.append('accion', 'guardar_subdivision');
        formData.append('area_id', this.areaSeleccionada.id);
        formData.append('nombre', document.getElementById('nombreSubdivision').value);
        formData.append('svg_id', document.getElementById('svgIdSubdivision').value);
        formData.append('tipo', document.getElementById('tipoSubdivision').value);
        formData.append('data_id', document.getElementById('dataIdSubdivision').value);
        
        const aulaId = document.getElementById('aulaIdSubdivision').value;
        if (aulaId) {
            formData.append('aula_id', aulaId);
        }
        
        fetch('../ajax/mapa_interactivo_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
                alert('Subdivisión guardada correctamente');
                this.cargarMapa(); // Recargar el mapa para mostrar los cambios
            } else {
                console.error('Error al guardar la subdivisión:', data.mensaje);
                alert('Error al guardar la subdivisión: ' + data.mensaje);
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
            alert('Error en la solicitud al guardar la subdivisión');
        });
    }
    
    /**
     * Asigna un responsable a un área
     */
    asignarResponsable() {
        if (!this.areaSeleccionada) {
            alert('Debe seleccionar un área primero');
            return;
        }
        
        const formData = new FormData();
        formData.append('accion', 'asignar_responsable');
        formData.append('area_id', this.areaSeleccionada.id);
        formData.append('usuario_id', document.getElementById('usuarioIdResponsable').value);
        formData.append('cargo', document.getElementById('cargoResponsable').value);
        
        fetch('../ajax/mapa_interactivo_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
                alert('Responsable asignado correctamente');
                this.seleccionarArea(this.areaSeleccionada.data_id); // Recargar la información del área
            } else {
                console.error('Error al asignar el responsable:', data.mensaje);
                alert('Error al asignar el responsable: ' + data.mensaje);
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
            alert('Error en la solicitud al asignar el responsable');
        });
    }
    
    /**
     * Asigna personal a un área
     */
    asignarPersonal() {
        if (!this.areaSeleccionada) {
            alert('Debe seleccionar un área primero');
            return;
        }
        
        const formData = new FormData();
        formData.append('accion', 'asignar_personal');
        formData.append('area_id', this.areaSeleccionada.id);
        formData.append('usuario_id', document.getElementById('usuarioIdPersonal').value);
        formData.append('cargo', document.getElementById('cargoPersonal').value);
        
        fetch('../ajax/mapa_interactivo_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.exito) {
                alert('Personal asignado correctamente');
                this.seleccionarArea(this.areaSeleccionada.data_id); // Recargar la información del área
            } else {
                console.error('Error al asignar el personal:', data.mensaje);
                alert('Error al asignar el personal: ' + data.mensaje);
            }
        })
        .catch(error => {
            console.error('Error en la solicitud:', error);
            alert('Error en la solicitud al asignar el personal');
        });
    }


    /**
     * Inicializa los controles de zoom del mapa
     * @private
     */
    inicializarZoom() { // Renombrado de inicializarControlZoom
        const zoomIn = document.getElementById('zoom-in');
        const zoomOut = document.getElementById('zoom-out');
        const zoomReset = document.getElementById('zoom-reset');
        const zoomLevel = document.getElementById('zoom-level');
        const svg = document.getElementById('mapa-svg');
        const contenedorMapa = document.getElementById('mapa-container'); // Contenedor para eventos de rueda/arrastre
        
        if (!svg || !zoomIn || !zoomOut || !zoomReset || !zoomLevel || !contenedorMapa) {
            console.warn('No se encontraron todos los elementos necesarios para el zoom y paneo');
            return;
        }

        let scale = 1;
        const ZOOM_STEP = 0.1;
        const MIN_ZOOM = 0.5;
        const MAX_ZOOM = 3;
        let isDragging = false;
        let startX, startY;
        let translateX = 0, translateY = 0; // Variables para el paneo

        // Aplicar transformaciones
        function applyTransform() {
            svg.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
        }

        // Actualizar nivel de zoom visual
        function updateZoomLevelDisplay() {
             zoomLevel.textContent = `${Math.round(scale * 100)}%`;
             zoomLevel.classList.remove('oculto');
             // Ocultar después de un tiempo (opcional)
             /*
             clearTimeout(zoomLevel.timeoutId);
             zoomLevel.timeoutId = setTimeout(() => {
                 zoomLevel.classList.add('oculto');
             }, 1500);
             */
        }

        // Actualizar zoom (escala)
        function updateZoom(newScale, pivotX = null, pivotY = null) {
             const clampedScale = Math.min(Math.max(newScale, MIN_ZOOM), MAX_ZOOM);
             
             if (clampedScale === scale) return; // No hacer nada si no cambia

             if (pivotX !== null && pivotY !== null) {
                 // Calcular el punto en el SVG antes y después del zoom
                 const svgRect = svg.getBoundingClientRect();
                 const svgX = pivotX - svgRect.left;
                 const svgY = pivotY - svgRect.top;

                 const pointX = (svgX - translateX) / scale;
                 const pointY = (svgY - translateY) / scale;

                 // Calcular el nuevo translate para mantener el punto bajo el cursor
                 translateX = svgX - pointX * clampedScale;
                 translateY = svgY - pointY * clampedScale;
             } else {
                 // Si no hay pivote (botones), hacer zoom hacia el centro
                 const svgRect = svg.getBoundingClientRect();
                 const centerX = svgRect.width / 2;
                 const centerY = svgRect.height / 2;
                 
                 translateX -= centerX * (clampedScale / scale - 1);
                 translateY -= centerY * (clampedScale / scale - 1);
             }

             scale = clampedScale;
             applyTransform();
             updateZoomLevelDisplay();
         }

        // Manejar el scroll del mouse para zoom
        contenedorMapa.addEventListener('wheel', (e) => {
            e.preventDefault(); // Prevenir scroll de la página
            const delta = e.deltaY > 0 ? -ZOOM_STEP : ZOOM_STEP; // Invertir para zoom natural
            updateZoom(scale + delta * scale, e.clientX, e.clientY); // Zoom relativo a la escala actual y con pivote
        }, { passive: false }); // Necesario para preventDefault

        // Manejar el arrastre del mapa (paneo)
        contenedorMapa.addEventListener('mousedown', (e) => {
             if (e.button !== 0) return; // Solo botón izquierdo
             isDragging = true;
             startX = e.clientX - translateX;
             startY = e.clientY - translateY;
             contenedorMapa.style.cursor = 'grabbing';
             contenedorMapa.classList.add('dragging'); // Clase opcional para estilos
         });
 
         window.addEventListener('mousemove', (e) => { // Escuchar en window para no perder el rastro si el cursor sale del div
             if (!isDragging) return;
             translateX = e.clientX - startX;
             translateY = e.clientY - startY;
             applyTransform();
         });
 
         window.addEventListener('mouseup', (e) => {
             if (e.button !== 0 || !isDragging) return;
             isDragging = false;
             contenedorMapa.style.cursor = 'grab';
             contenedorMapa.classList.remove('dragging');
         });
 
         // Botones de zoom
         zoomIn.addEventListener('click', () => updateZoom(scale + ZOOM_STEP * scale));
         zoomOut.addEventListener('click', () => updateZoom(scale - ZOOM_STEP * scale));
         zoomReset.addEventListener('click', () => {
             scale = 1;
             translateX = 0;
             translateY = 0;
             applyTransform();
             updateZoomLevelDisplay();
         });
 
         // Inicializar estado visual
         applyTransform();
         contenedorMapa.style.cursor = 'grab';
         zoomLevel.classList.add('oculto'); // Asegurar que inicia oculto
    }

    /**
     * Obtiene los datos para el tooltip
     */
    async obtenerDatosTooltip(dataId) {
        try {
        const formData = new FormData();
        formData.append('accion', 'obtener_subdivision');
        formData.append('data_id', dataId);

            const response = await fetch('../ajax/mapa_interactivo_ajax.php', {
            method: 'POST',
            body: formData
            });

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.exito) {
                return data.datos;
            } else {
                // Si no es una subdivisión, intentar como área
                const formDataArea = new FormData();
                formDataArea.append('accion', 'obtener_area');
                formDataArea.append('data_id', dataId);

                const responseArea = await fetch('../ajax/mapa_interactivo_ajax.php', {
                    method: 'POST',
                    body: formDataArea
                });

                if (!responseArea.ok) {
                    throw new Error(`Error HTTP: ${responseArea.status}`);
                }

                const dataArea = await responseArea.json();
                return dataArea.exito ? dataArea.datos : null;
            }
        } catch (error) {
            console.error("Error al obtener datos para tooltip:", error);
            return null;
        }
    }

    crearElementoArea(area) {
        return null;
    }

    crearElementoSubdivision(subdivision) {
        return null;
    }

    inicializarModoEdicion() {
        const toggleButton = document.getElementById('toggle-edit-mode');
        const btnGuardarTodo = document.getElementById('btn-guardar-todo-edicion');
        const btnUndo = document.getElementById('btn-undo-edit');
        const btnToggleAreas = document.getElementById('toggle-areas-padre');
        const btnMoverAreas = document.getElementById('toggle-mover-areas');
        
        if (toggleButton) {
            toggleButton.addEventListener('click', () => {
                this.toggleEditMode();
            });
        }
        
        if (btnGuardarTodo) {
            btnGuardarTodo.addEventListener('click', () => {
                this.guardarTodosLosCambiosPendientes();
            });
            btnGuardarTodo.style.display = 'none';
        }

        if (btnUndo) {
            btnUndo.addEventListener('click', () => {
                this.undoLastAction();
            });
            btnUndo.style.display = 'none';
        }

        // Añadir evento click para el botón de toggle áreas
        if (btnToggleAreas) {
            btnToggleAreas.addEventListener('click', () => {
                this.toggleAreasPadre();
            });
        }

        // Añadir evento click para el botón de mover áreas
        if (btnMoverAreas) {
            btnMoverAreas.addEventListener('click', () => {
                this.toggleMoverAreas();
            });
        }

        // Inicializar el selector de color
        const colorInput = document.getElementById('edit-color-elemento');
        if (colorInput) {
            colorInput.addEventListener('change', (e) => {
                if (this.elementoSeleccionadoParaEdicion) {
                    const svgElement = this.editableElements.get(this.elementoSeleccionadoParaEdicion.dataset.id);
                    if (svgElement) {
                        svgElement.fill(e.target.value);
                        // Registrar el cambio
                        this.registrarCambioColor(this.elementoSeleccionadoParaEdicion.dataset.id, e.target.value);
                    }
                }
            });
        }
    }

    toggleEditMode() {
        const toggleButton = document.getElementById('toggle-edit-mode');
        
        if (!this.editMode) {
            // Activar modo edición
            this.editMode = true;
            if (toggleButton) {
                toggleButton.classList.add('active');
                toggleButton.innerHTML = '<i class="fas fa-eye"></i> Modo Vista';
            }
            console.log("Modo edición: ACTIVADO");
            console.log("Iniciando modo edición...");
            
            // Desactivar eventos de selección normal antes de activar modo edición
            this.desactivarEventosSeleccionNormal();
            this.activarModoEdicion();
            
            } else {
            // Desactivar modo edición
            this.editMode = false;
            if (toggleButton) {
                toggleButton.classList.remove('active');
                toggleButton.innerHTML = '<i class="fas fa-edit"></i> Modo Edición';
            }
            console.log("Modo edición: DESACTIVADO");
            
            this.desactivarModoEdicion();
            // Reactivar eventos de selección normal
            this.activarEventosSeleccionNormal();
        }
    }

    activarModoEdicion() {
        console.log("Activando modo edición...");
        this.deseleccionarElementoVista();
        this.quitarEnfoqueMapa();
        
        // Inicializar SVG.js si no está inicializado
        if (!this.svgCanvas) {
            try {
                this.svgCanvas = SVG('#mapa-svg');
                console.log("SVG.js canvas inicializado correctamente en activarModoEdicion");
            } catch (error) {
                console.error("Error al inicializar SVG.js en activarModoEdicion:", error);
                return; // Detener si no se puede inicializar SVG.js
            }
        }
        
        const panelEdicion = document.getElementById('panel-edicion-planta');
        const btnGuardarTodo = document.getElementById('btn-guardar-todo-edicion');
        const btnUndo = document.getElementById('btn-undo-edit');
        const btnToggleAreas = document.getElementById('toggle-areas-padre');
        const btnMoverAreas = document.getElementById('toggle-mover-areas');
        const btnAgregarArea = document.getElementById('btn-agregar-area');
        const btnEliminar = document.getElementById('btn-eliminar-elemento');

        // Mostrar panel de edición y botones relevantes
        if (panelEdicion) {
            panelEdicion.style.display = 'block';
        }
        if (btnGuardarTodo) {
            this.actualizarVisibilidadBotonGuardarEdicion();
        }
        if (btnUndo) {
            this.actualizarVisibilidadBotonDeshacer();
        }
        if (btnToggleAreas) {
            btnToggleAreas.style.display = 'inline-flex';
        }
        if (btnMoverAreas) {
            btnMoverAreas.style.display = 'inline-flex';
        }
        if (btnAgregarArea) {
            btnAgregarArea.style.display = 'inline-flex';
        }
        if (btnEliminar) {
            btnEliminar.style.display = 'inline-block';
            if (!btnEliminar.hasListener) {
                btnEliminar.addEventListener('click', () => {
                    console.log('Botón eliminar clickeado desde activarModoEdicion');
                    this.mostrarModalConfirmarEliminar();
                });
                btnEliminar.hasListener = true;
            }
        }

        // Deshabilitar campos superiores hasta que se seleccione un elemento
        document.getElementById('planta_id').value = 'Modo Edición';
        document.getElementById('nombre_planta').value = 'Seleccione elemento...';
        document.getElementById('tipo_planta').value = '';
        document.getElementById('nombre_planta').readOnly = true;
        document.getElementById('tipo_planta').disabled = true;

        // Preparar elementos para edición
        this.prepararElementosParaEdicion();
        
        // Inicializar el historial de acciones
        this.actionHistory = [];
        this.actualizarVisibilidadBotonDeshacer();
        
        console.log("Elementos preparados para edición:", this.editableElements);
    }

    desactivarModoEdicion() {
        console.log("Desactivando modo edición...");
        this.quitarEnfoqueMapa(); 
        
        // Restaurar campos superiores para modo vista
        const nombrePlanta = document.getElementById('nombre_planta');
        const tipoPlanta = document.getElementById('tipo_planta');
        const panelEdicion = document.getElementById('panel-edicion-planta');
        const btnEliminar = document.getElementById('btn-eliminar-elemento');
        
        // Ocultar panel de edición y botón eliminar
        if (panelEdicion) {
            panelEdicion.style.display = 'none';
        }
        if (btnEliminar) {
            btnEliminar.style.display = 'none';
            // Quitar listener si lo tiene
            if (btnEliminar.hasListener) {
                // Nota: Es mejor remover el listener específico, pero esto simplifica
                const newBtn = btnEliminar.cloneNode(true);
                btnEliminar.parentNode.replaceChild(newBtn, btnEliminar);
            }
        }
        
        // Eliminar listeners y deshabilitar los campos
        if (nombrePlanta) {
            nombrePlanta.readOnly = true;
            if (nombrePlanta._inputListener) {
                nombrePlanta.removeEventListener('input', nombrePlanta._inputListener);
                delete nombrePlanta._inputListener;
            }
        }
        if (tipoPlanta) {
            tipoPlanta.disabled = true;
            if (tipoPlanta._changeListener) {
                tipoPlanta.removeEventListener('change', tipoPlanta._changeListener);
                delete tipoPlanta._changeListener;
            }
        }
        
        // Limpiar campos si no hay elemento seleccionado en vista
        if (!this.elementoSeleccionadoVista) {
            document.getElementById('planta_id').value = '';
            document.getElementById('nombre_planta').value = '';
            document.getElementById('tipo_planta').value = '';
        }
        
        this.desactivarMoverAreas();
        
        const btnToggleAreas = document.getElementById('toggle-areas-padre');
        const btnMoverAreas = document.getElementById('toggle-mover-areas');
        const btnUndo = document.getElementById('btn-undo-edit');
        const btnAgregarArea = document.getElementById('btn-agregar-area');
        
        if (btnToggleAreas) {
            btnToggleAreas.style.display = 'none';
            btnToggleAreas.classList.remove('active');
        }
        if (btnMoverAreas) {
            btnMoverAreas.style.display = 'none';
            btnMoverAreas.classList.remove('active');
        }
        if (btnUndo) {
            btnUndo.style.display = 'none';
        }
        if (btnAgregarArea) {
            btnAgregarArea.style.display = 'none';
        }

        this.editableElements.forEach((svgElement, dataId) => {
            const elementoDOM = svgElement.node;
            if (!elementoDOM) { return; }
            try {
                svgElement.draggable(false);
                svgElement.off('dragstart dragend dragmove');
                if (elementoDOM.clickHandlerEdicion) {
                    elementoDOM.removeEventListener('click', elementoDOM.clickHandlerEdicion);
                    delete elementoDOM.clickHandlerEdicion;
                }
                elementoDOM.classList.remove('editable', 'editando', 'movible');
                elementoDOM.style.cursor = 'pointer';
                const grupo = svgElement.remember('grupo');
                if (grupo) {
                    try {
                        grupo.draggable(false);
                        grupo.off('dragstart dragend dragmove');
                        if (grupo.children().length > 0) { grupo.ungroup(); }
                    } catch (error) { /* ignore */ }
                }
            } catch (error) { /* ignore */ }
        });
        
        this.elementoSeleccionadoParaEdicion = null;
        this.editableElements.clear();

        document.querySelectorAll('.area-padre').forEach(area => {
            area.style.display = 'none';
            area.classList.remove('visible', 'movible');
        });

        this.actionHistory = []; // Limpiar historial siempre al salir
        this.activarEventosSeleccionNormal();
    }

    toggleMoverAreas() {
        const btnMoverAreas = document.getElementById('toggle-mover-areas');
        const isMovible = btnMoverAreas.classList.contains('active');

        // Primero, desactivar todos los elementos draggables existentes
        this.editableElements.forEach((svgElement, dataId) => {
            try {
                svgElement.draggable(false);
                svgElement.off('dragstart dragend dragmove');
                const grupo = svgElement.remember('grupo');
                if (grupo) {
                    grupo.draggable(false);
                    grupo.off('dragstart dragend dragmove');
                }
            } catch (error) {
                console.warn(`Error al desactivar draggable para ${dataId}:`, error);
            }
        });
        this.editableElements.clear();

        if (isMovible) {
            this.desactivarMoverAreas();
            // Reinicializar elementos editables normales
            this.inicializarElementosEditables();
            } else {
            this.activarMoverAreas();
        }
    }

    desactivarMoverAreas() {
        const btnMoverAreas = document.getElementById('toggle-mover-areas');
        if (btnMoverAreas) {
            btnMoverAreas.classList.remove('active');
            btnMoverAreas.innerHTML = '<i class="fas fa-arrows-alt"></i> Mover Áreas';
        }

        // Desactivar draggable para todos los grupos y remover listeners de selección de mover
        document.querySelectorAll('.area-padre.movible').forEach(area => {
             area.classList.remove('movible', 'editando');
             area.style.cursor = 'pointer'; // Restaurar cursor
             if (area.clickHandlerMover) {
                 area.removeEventListener('click', area.clickHandlerMover);
                 delete area.clickHandlerMover;
             }
             try {
                const grupo = SVG(area).remember('grupo'); // Intentar obtener el grupo asociado
                if (grupo) {
                    grupo.draggable(false);
                     // Desagrupar CUIDADOSAMENTE
                     // Guardar transformaciones antes de desagrupar si es necesario
                     // let currentTransform = grupo.transform(); 
                     // ... (lógica para aplicar transformaciones a hijos)
                     // Por ahora, solo desagrupamos
                     grupo.each(function() { // Mover elementos de vuelta al canvas principal
                         this.move(this.x() + grupo.x(), this.y() + grupo.y()); // Ajustar posición global
                         this.addTo(grupo.parent());
                     });
                     grupo.remove(); // Eliminar el grupo vacío
                     SVG(area).forget('grupo'); // Limpiar la memoria
                 } else {
                     // Si no hay grupo, intentar desactivar draggable del elemento individual
                     const areaSvg = SVG(area);
                     if(areaSvg) areaSvg.draggable(false);
                 }
            } catch (error) {
                 console.warn(`Error al desactivar/desagrupar área movible ${area.dataset.id}:`, error);
             }
        });

        // Limpiar el mapa de elementos editables (puede que contenga refs a grupos)
        this.editableElements.clear();

        // Limpiar la selección si el elemento seleccionado era un área padre
        if (this.elementoSeleccionadoParaEdicion && this.elementoSeleccionadoParaEdicion.classList.contains('area-padre')) {
             this.deseleccionarElementoEdicion(); // Llama a la función existente
        }

        // Reinicializar elementos editables para el modo normal (subdivisiones)
        this.prepararElementosParaEdicion();
    }

    activarMoverAreas() {
        const btnMoverAreas = document.getElementById('toggle-mover-areas');
        btnMoverAreas.classList.add('active');
        btnMoverAreas.innerHTML = '<i class="fas fa-arrows-alt"></i> Finalizar Mover';

        // Desactivar draggable y listeners de elementos individuales si los hubiera
        this.editableElements.forEach((svgElement, dataId) => {
            try {
                svgElement.draggable(false);
                if (svgElement.node && svgElement.node.clickHandlerEdicion) {
                    svgElement.node.removeEventListener('click', svgElement.node.clickHandlerEdicion);
                }
            } catch (error) {
                console.warn(`Error al desactivar draggable/listener para ${dataId}:`, error);
            }
        });
        this.editableElements.clear();

        // Activar modo movimiento y selección para cada área padre visible
        const areasPadre = document.querySelectorAll('.area-padre.visible');
        areasPadre.forEach(area => {
            area.classList.add('movible');
            
            // **Añadir listener para selección ANTES de hacerla movible**
            area.clickHandlerMover = (event) => {
                event.stopPropagation(); // Prevenir que el clic se propague al fondo
                this.seleccionarAreaPadreEnModoMover(area);
            };
            area.addEventListener('click', area.clickHandlerMover);

            // Hacer el área (y sus subdivisiones agrupadas) movible
            this.hacerAreaMovible(area);
        });
    }

    // *** NUEVA FUNCIÓN ***
    seleccionarAreaPadreEnModoMover(areaElement) {
        if (!areaElement || !areaElement.dataset || !areaElement.dataset.id) {
            console.error("Elemento de área padre inválido para seleccionar.");
            return;
        }
        const dataId = areaElement.dataset.id;
        console.log(`Seleccionando área padre ${dataId} en modo mover.`);

        // Deseleccionar elemento anterior (si existe y es diferente)
        if (this.elementoSeleccionadoParaEdicion && this.elementoSeleccionadoParaEdicion !== areaElement) {
            this.elementoSeleccionadoParaEdicion.classList.remove('editando'); // O una clase específica para selección de área padre
        }

        // Seleccionar el nuevo elemento
        areaElement.classList.add('editando'); // Usar la misma clase por ahora
        this.elementoSeleccionadoParaEdicion = areaElement;

        // Actualizar panel superior (si aplica)
        document.getElementById('planta_id').value = dataId;
        document.getElementById('nombre_planta').value = areaElement.dataset.nombre || dataId;
        document.getElementById('tipo_planta').value = areaElement.dataset.tipo || ''; // Asumiendo que tiene tipo
        document.getElementById('nombre_planta').readOnly = false; // Permitir editar nombre?
        document.getElementById('tipo_planta').disabled = false;  // Permitir editar tipo?

        // Actualizar panel de propiedades (Color)
        const colorInput = document.getElementById('edit-color-elemento');
        if (colorInput) {
            const svgElement = SVG(areaElement); // Obtener objeto SVG
            colorInput.value = svgElement ? svgElement.attr('fill') : '#000000';
        }
        
        // Asegurarse que el panel de edición esté visible
        const panelEdicion = document.getElementById('panel-edicion-planta');
        if (panelEdicion) {
            panelEdicion.style.display = 'block';
        }
        
        // Asegurar que el botón Eliminar esté configurado para este elemento
        const btnEliminar = document.getElementById('btn-eliminar-elemento');
        if (btnEliminar) {
            btnEliminar.style.display = 'inline-block';
            // Reasignar listener para asegurar que usa el elemento correcto
            // Primero clonar para remover listeners antiguos
            const newBtnEliminar = btnEliminar.cloneNode(true);
            btnEliminar.parentNode.replaceChild(newBtnEliminar, btnEliminar);
            newBtnEliminar.hasListener = true; // Marcar como que tiene listener
            newBtnEliminar.addEventListener('click', () => {
                console.log(`Botón eliminar clickeado para ÁREA PADRE ${dataId}`);
                this.mostrarModalConfirmarEliminar(); // La lógica interna ya sabe qué hacer
            });
        } else {
            console.error("Botón Eliminar no encontrado");
        }
        
        // Opcional: Ocultar botón agregar subdivisión/área cuando se selecciona área padre?
        const btnAgregar = document.getElementById('btn-agregar-area');
        if (btnAgregar) {
           btnAgregar.style.display = 'none';
        }
    }

    inicializarElementosEditables() {
        const elementosDOM = document.querySelectorAll('.area-interactiva');
        elementosDOM.forEach(elementoDOM => {
            if (!elementoDOM.classList.contains('area-padre') || elementoDOM.classList.contains('visible')) {
                elementoDOM.classList.add('editable');
                elementoDOM.style.cursor = 'move';

                try {
                    // Convertir el elemento DOM a un elemento SVG.js
                    const svgElement = SVG(elementoDOM);
                    this.editableElements.set(elementoDOM.dataset.id, svgElement);

                    // Hacer el elemento arrastrable
                    if (typeof svgElement.draggable === 'function') {
                        svgElement.draggable()
                            .on('dragstart', () => {
                                if (this.elementoSeleccionadoParaEdicion === elementoDOM) {
                                    elementoDOM.classList.remove('editando');
                                    this.elementoSeleccionadoParaEdicion = null;
                                }
                                this.tooltip.style.display = 'none';
                            })
                            .on('dragend', () => {
                                const bbox = svgElement.bbox();
                                this.guardarPosicionElemento(svgElement);
                            });
                    }
                } catch (error) {
                    console.error(`Error al procesar elemento ${elementoDOM.dataset.id}:`, error);
                }
            }
        });
    }

    hacerAreaMovible(areaPadre) {
        if (!areaPadre || !areaPadre.dataset.id) return;

        const areaId = areaPadre.dataset.id;

        // Encontrar todas las subdivisiones asociadas
        const subdivisiones = Array.from(document.querySelectorAll(`[data-parent-id="${areaId}"]`));
        console.log(`Encontradas ${subdivisiones.length} subdivisiones para el área ${areaId}`);
        
        if (!this.svgCanvas) {
            console.error("SVG Canvas no está inicializado en hacerAreaMovible");
            return;
        }
        
        // Crear un grupo SVG.js y asegurarnos de que está en el canvas correcto
        const grupo = this.svgCanvas.group().addClass('grupo-movible');

        // Añadir el área padre al grupo y mantener su posición original
        const areaSvg = SVG(areaPadre);
        if (!areaSvg) {
            console.error(`No se pudo obtener el objeto SVG para el área padre ${areaId}`);
            return;
        }
        grupo.add(areaSvg);
        areaSvg.remember('grupo', grupo); // Guardar referencia al grupo en el elemento padre

        // Añadir cada subdivisión al grupo manteniendo sus posiciones relativas
        subdivisiones.forEach(sub => {
            const subSvg = SVG(sub);
            if (subSvg) {
            grupo.add(subSvg);
                subSvg.remember('grupo', grupo); // También guardar ref en subdivisión
            } else {
                console.warn(`No se pudo obtener el objeto SVG para la subdivisión ${sub.dataset.id}`);
            }
        });

        // Hacer el GRUPO draggable
        grupo.draggable().on('dragstart', (e) => {
            console.log(`Drag start grupo ${areaId}`);
            // Prevenir que el drag inicie si el click fue en el elemento padre (para permitir selección)
            if (e.detail.event.target === areaSvg.node) {
                 console.log("Drag iniciado en área padre, previniendo para selección");
                 //e.preventDefault(); // Esto podría no funcionar como se espera con svg.draggable
                 // Una alternativa es manejar la selección en 'dragend' si no hubo movimiento significativo
            }
        }).on('dragend', (e) => {
            console.log(`Drag end grupo ${areaId}`);
            const dx = grupo.x(); // Obtener la posición final X del grupo
            const dy = grupo.y(); // Obtener la posición final Y del grupo
            
            // Aquí registramos el cambio de posición para el área padre
            // y potencialmente para cada subdivisión si sus coordenadas son absolutas en la BD
            
            // Ejemplo simple: registrar solo para el área padre (ajustar según necesidad)
            this.registrarCambioPosicion(areaId, { x: dx, y: dy }); 
            
        });
        
        // Añadir el grupo a la lista de elementos editables (como grupo)
        this.editableElements.set(`grupo-${areaId}`, grupo);

        console.log(`Área ${areaId} y sus subdivisiones agrupadas y hechas movibles.`);
    }

    guardarPosicionGrupo(areaId, cambios) {
        // Necesitamos guardar el estado ANTERIOR de todo el grupo
        const estadoAnteriorGrupo = new Map();
        cambios.forEach((cambioActual, elementId) => {
            const svgElement = this.editableElements.get(elementId);
            if (svgElement) {
                const cambiosPendientesActuales = this.cambiosPendientes.get(elementId) || {};
                estadoAnteriorGrupo.set(elementId, {
                    x: cambiosPendientesActuales.x !== undefined ? cambiosPendientesActuales.x : svgElement.x(),
                    y: cambiosPendientesActuales.y !== undefined ? cambiosPendientesActuales.y : svgElement.y(),
                    width: cambiosPendientesActuales.width !== undefined ? cambiosPendientesActuales.width : cambioActual.width,
                    height: cambiosPendientesActuales.height !== undefined ? cambiosPendientesActuales.height : cambioActual.height
                });
            }
        });

        // Registrar acción en el historial
        this.addActionToHistory({
            type: 'groupMove',
            groupId: areaId, // Identificador del grupo
            previousState: estadoAnteriorGrupo, // Map con estados anteriores de cada elemento
            currentState: cambios // Map con estados actuales
        });
        
        // Almacenar los cambios pendientes para cada elemento
        cambios.forEach((cambio, elementId) => {
            const cambioCompleto = {
                ...(this.cambiosPendientes.get(elementId) || {}),
                ...cambio
            };
            this.cambiosPendientes.set(elementId, cambioCompleto);
        });

        this.actualizarVisibilidadBotonGuardarEdicion();
        this.actualizarVisibilidadBotonDeshacer();
    }

    guardarPosicionElemento(svgElement) {
        if (!svgElement || !svgElement.node) {
            console.error("guardarPosicionElemento recibió un elemento inválido");
            return;
        }

        try {
            const bbox = svgElement.bbox();
            const dataId = svgElement.node.dataset.id;
            const elementoDOM = svgElement.node;

            if (!dataId) {
                console.error("No se pudo obtener data-id del elemento SVG");
                return;
            }

            console.log(`Aplicando cambio virtual de posición para ${dataId}:`, bbox);
            
            // Estado ANTERIOR (buscar en cambios pendientes o tomar de atributos si no existe)
            const cambiosActuales = this.cambiosPendientes.get(dataId) || {};
            const estadoAnterior = {
                x: cambiosActuales.x !== undefined ? cambiosActuales.x : bbox.x,
                y: cambiosActuales.y !== undefined ? cambiosActuales.y : bbox.y,
                width: cambiosActuales.width !== undefined ? cambiosActuales.width : bbox.width,
                height: cambiosActuales.height !== undefined ? cambiosActuales.height : bbox.height
            };

            // Generar el nuevo path_data basado en la posición actual
            const pathData = `M${bbox.x} ${bbox.y} h ${bbox.width} v ${bbox.height} h -${bbox.width} Z`;

            // Registrar acción en el historial
            this.addActionToHistory({
                type: 'move',
                elementId: dataId,
                previousState: estadoAnterior,
                currentState: { 
                    x: bbox.x, 
                    y: bbox.y, 
                    width: bbox.width, 
                    height: bbox.height,
                    path_data: pathData
                }
            });
            
            // Almacenar el cambio pendiente
            const cambio = {
                ...(this.cambiosPendientes.get(dataId) || {}),
                x: bbox.x,
                y: bbox.y,
                width: bbox.width,
                height: bbox.height,
                path_data: pathData
            };
            this.cambiosPendientes.set(dataId, cambio);

            this.actualizarVisibilidadBotonGuardarEdicion();
            this.actualizarVisibilidadBotonDeshacer();

        } catch (error) {
            console.error('Error al procesar la nueva posición:', error);
        }
    }

    // Nuevo: Muestra/oculta el botón de guardar edición
    actualizarVisibilidadBotonGuardarEdicion() {
        const btnGuardarTodo = document.getElementById('btn-guardar-todo-edicion');
        if (btnGuardarTodo) {
            btnGuardarTodo.style.display = this.cambiosPendientes.size > 0 ? 'inline-flex' : 'none';
        }
    }

    // Nueva función para añadir acciones al historial
    addActionToHistory(action) {
        this.actionHistory.push(action);
        console.log("Acción añadida al historial:", action, "Historial actual:", this.actionHistory);
    }

    // Nueva función para deshacer la última acción
    undoLastAction() {
        if (this.actionHistory.length === 0) {
            console.log("No hay acciones para deshacer.");
            return;
        }

        const lastAction = this.actionHistory.pop();
        console.log("Deshaciendo acción:", lastAction);

        if (lastAction.type === 'move') {
            const svgElement = this.editableElements.get(lastAction.elementId);
            if (svgElement) {
                const prevState = lastAction.previousState;
                svgElement.move(prevState.x, prevState.y);
                // Actualizar cambios pendientes al estado anterior o eliminar si no hay más cambios
                const currentPending = this.cambiosPendientes.get(lastAction.elementId) || {};
                currentPending.x = prevState.x;
                currentPending.y = prevState.y;
                // Si solo había x, y, width, height, y ahora coinciden con prevState, podríamos eliminar la entrada
                if (Object.keys(currentPending).every(key => ['x','y','width','height'].includes(key) && currentPending[key] === prevState[key])) {
                     this.cambiosPendientes.delete(lastAction.elementId);
                } else {
                     this.cambiosPendientes.set(lastAction.elementId, currentPending);
                }
            } else {
                console.warn(`Elemento ${lastAction.elementId} no encontrado para deshacer movimiento.`);
            }
        } else if (lastAction.type === 'color') {
            const svgElement = this.editableElements.get(lastAction.elementId);
            if (svgElement) {
                const prevState = lastAction.previousState;
                svgElement.fill(prevState.color);
                // Actualizar input de color si es el elemento seleccionado
                if (this.elementoSeleccionadoParaEdicion && this.elementoSeleccionadoParaEdicion.dataset.id === lastAction.elementId) {
                    const colorInput = document.getElementById('edit-color-elemento');
                    if(colorInput) colorInput.value = prevState.color;
                }
                // Actualizar cambios pendientes
                const currentPending = this.cambiosPendientes.get(lastAction.elementId) || {};
                currentPending.color = prevState.color;
                if (Object.keys(currentPending).length === 1 && currentPending.color === prevState.color) { // Si solo quedaba el color
                    this.cambiosPendientes.delete(lastAction.elementId);
                } else {
                    this.cambiosPendientes.set(lastAction.elementId, currentPending);
                }
            } else {
                 console.warn(`Elemento ${lastAction.elementId} no encontrado para deshacer color.`);
            }
        } else if (lastAction.type === 'groupMove') {
            lastAction.previousState.forEach((prevState, elementId) => {
                const svgElement = this.editableElements.get(elementId);
                 if (svgElement) {
                     svgElement.move(prevState.x, prevState.y);
                     // Actualizar cambios pendientes
                     const currentPending = this.cambiosPendientes.get(elementId) || {};
                     currentPending.x = prevState.x;
                     currentPending.y = prevState.y;
                     // Simplificación: asumimos que groupMove solo afecta x, y
                     if (Object.keys(currentPending).every(key => ['x','y','width','height'].includes(key) && currentPending[key] === prevState[key])) {
                         this.cambiosPendientes.delete(elementId);
                     } else {
                         this.cambiosPendientes.set(elementId, currentPending);
                     }
                 } else {
                     console.warn(`Elemento ${elementId} del grupo ${lastAction.groupId} no encontrado para deshacer movimiento.`);
                 }
            });
        } else if (lastAction.type === 'create') {
            const elementId = lastAction.elementId;
            const svgElement = this.editableElements.get(elementId);
            if (svgElement) {
                // Remover del DOM y de elementos editables
                svgElement.remove();
                this.editableElements.delete(elementId);
                // Remover de cambios pendientes
                this.cambiosPendientes.delete(elementId);
                // Si era el seleccionado, deseleccionar
                if (this.elementoSeleccionadoParaEdicion && this.elementoSeleccionadoParaEdicion.dataset.id === elementId) {
                    this.deseleccionarElementoEdicion();
                }
            } else {
                 console.warn(`Elemento ${elementId} no encontrado para deshacer creación.`);
            }
        }

        console.log("Cambios pendientes después de deshacer:", this.cambiosPendientes);
        this.actualizarVisibilidadBotonGuardarEdicion();
        this.actualizarVisibilidadBotonDeshacer();
    }

    // Nueva función para actualizar visibilidad del botón Deshacer
    actualizarVisibilidadBotonDeshacer() {
        const btnUndo = document.getElementById('btn-undo-edit');
        if (btnUndo) {
            btnUndo.style.display = this.actionHistory.length > 0 ? 'inline-flex' : 'none';
        }
    }

    toggleAreasPadre() {
        console.log("Ejecutando toggleAreasPadre");
        const btnToggleAreas = document.getElementById('toggle-areas-padre');
        const btnMoverAreas = document.getElementById('toggle-mover-areas');
        const areasPadre = document.querySelectorAll('.area-padre');
        
        if (!btnToggleAreas) {
            console.error("No se encontró el botón toggle-areas-padre");
            return;
        }

        const isVisible = btnToggleAreas.classList.contains('active');
        console.log("Estado actual de visibilidad:", isVisible);

        if (isVisible) {
            console.log("Ocultando áreas padre");
            // Ocultar áreas padre
            areasPadre.forEach(area => {
                area.style.display = 'none';
                area.classList.remove('visible', 'movible');
            });
            btnToggleAreas.classList.remove('active');
            btnToggleAreas.innerHTML = '<i class="fas fa-layer-group"></i> Mostrar Áreas';
            
            // Ocultar y desactivar botón de mover
            if (btnMoverAreas) {
                btnMoverAreas.style.display = 'none';
                btnMoverAreas.classList.remove('active');
            }
            
            // Desactivar modo mover si estaba activo
            this.desactivarMoverAreas();
        } else {
            console.log("Mostrando áreas padre");
            // Mostrar áreas padre
            areasPadre.forEach(area => {
                area.style.display = 'block';
                area.classList.add('visible');
                // Asegurarse de que el área esté por debajo de las subdivisiones
                area.style.zIndex = '1';
            });
            btnToggleAreas.classList.add('active');
            btnToggleAreas.innerHTML = '<i class="fas fa-layer-group"></i> Ocultar Áreas';
            
            // Mostrar botón de mover
            if (btnMoverAreas) {
                btnMoverAreas.style.display = 'inline-flex';
            }

            // Asegurarse de que las subdivisiones estén por encima
            document.querySelectorAll('.area-interactiva:not(.area-padre)').forEach(subdivision => {
                subdivision.style.zIndex = '2';
            });
        }
        
        console.log("Estado final de visibilidad:", !isVisible);
    }

    activarEventosSeleccionNormal() {
        const elementosDOM = document.querySelectorAll('.area-interactiva');
        elementosDOM.forEach(elementoDOM => {
            // Remover cualquier listener previo de edición
            if (elementoDOM.clickHandlerEdicion) {
                elementoDOM.removeEventListener('click', elementoDOM.clickHandlerEdicion);
                delete elementoDOM.clickHandlerEdicion;
            }
            
            // Añadir el listener de selección normal si no existe
            if (!elementoDOM.clickHandlerNormal) {
                elementoDOM.clickHandlerNormal = (event) => {
                    event.stopPropagation();
                    const dataId = elementoDOM.dataset.id;
                    if (dataId) {
                        this.cargarDatosSubdivision(dataId);
                    }
                };
                elementoDOM.addEventListener('click', elementoDOM.clickHandlerNormal);
            }
        });
    }

    // --- NUEVO: Inicializar eventos para deseleccionar ---
    inicializarEventosDeseleccion() {
        const contenedorMapa = document.getElementById('mapa-container');
        const svgElement = document.getElementById('mapa-svg');

        if (!contenedorMapa || !svgElement) { return; }

        contenedorMapa.addEventListener('click', (event) => {
            // Solo procesar clics directos en el contenedor o el SVG base
            if (event.target === contenedorMapa || event.target === svgElement) {
                console.log("Clic detectado en el fondo del mapa.");
                this.quitarEnfoqueMapa();
                
                if (this.editMode) {
                    this.deseleccionarElementoEdicion();
                    // Mostrar botón de agregar área cuando no hay selección
                    const btnAgregarArea = document.getElementById('btn-agregar-area');
                    if (btnAgregarArea) {
                        btnAgregarArea.innerHTML = '<i class="fas fa-plus"></i> Agregar Área';
                        btnAgregarArea.style.display = 'inline-flex';
                        // Restaurar el manejador de eventos original para agregar área
                        btnAgregarArea.onclick = () => this.abrirModalAgregarArea();
                    }
                } else {
                    this.deseleccionarElementoVista();
                }
            }
        });
    }

    // --- NUEVO: Deseleccionar en modo Edición ---
    deseleccionarElementoEdicion() {
        if (this.elementoSeleccionadoParaEdicion) {
            console.log("Deseleccionando elemento en modo edición:", this.elementoSeleccionadoParaEdicion.dataset.id);
            this.elementoSeleccionadoParaEdicion.classList.remove('editando');
            this.elementoSeleccionadoParaEdicion = null;

            // Limpiar campos superiores
            document.getElementById('planta_id').value = '';
            document.getElementById('nombre_planta').value = '';
            document.getElementById('tipo_planta').value = '';
            document.getElementById('nombre_planta').disabled = true;

            // Ocultar panel de edición (PERO NO el botón eliminar)
            const panelEdicion = document.getElementById('panel-edicion-planta');
            if (panelEdicion) {
                panelEdicion.style.display = 'none';
            }
            /* // Ya no ocultamos el botón eliminar aquí
            const btnEliminar = document.getElementById('btn-eliminar-elemento');
            if (btnEliminar) {
                btnEliminar.style.display = 'none';
            }
            */

            // Limpiar campos de tamaño
            const campos = ['edit-pos-x', 'edit-pos-y', 'edit-width', 'edit-height'];
            campos.forEach(campoId => {
                const campo = document.getElementById(campoId);
                if (campo) campo.value = '';
            });

            // Ocultar sección de tamaño
            const seccionTamaño = document.getElementById('seccion-tamaño');
            if (seccionTamaño) {
                seccionTamaño.style.display = 'none';
            }

            const colorInput = document.getElementById('edit-color-elemento');
            if (colorInput) {
                colorInput.value = '#000000';
            }
        }
    }

    // --- NUEVO: Deseleccionar en modo Vista ---
    deseleccionarElementoVista() {
        if (this.elementoSeleccionadoVista) {
            console.log("Deseleccionando elemento en modo vista:", this.elementoSeleccionadoVista.dataset.id);
            this.elementoSeleccionadoVista.classList.remove('seleccionada');
            this.elementoSeleccionadoVista = null;

            // Limpiar campos superiores
            document.getElementById('planta_id').value = '';
            document.getElementById('nombre_planta').value = '';
            document.getElementById('tipo_planta').value = '';
            document.getElementById('nombre_planta').disabled = true; // Deshabilitar nombre

            const panelEdicion = document.getElementById('panel-edicion');
            const mensajeSeleccion = document.getElementById('mensaje-seleccion');
            if (panelEdicion) panelEdicion.style.display = 'none';
            if (mensajeSeleccion) mensajeSeleccion.style.display = 'block';
        }
    }

    // --- MODIFICADO: Abrir Modal Filtro ---
    abrirModalFiltro() {
        const modal = document.getElementById('modal-filtro');
        const listaContenedor = document.getElementById('filtro-lista-elementos');
        
        if (!modal || !listaContenedor) {
            console.error("Modal de filtro o contenedor de lista no encontrado.");
            return;
        }
        
        // Añadir barra de búsqueda
        let contenidoHTML = `
            <div class="filtro-busqueda">
                <input type="text" id="busqueda-filtro" placeholder="Buscar por nombre..." class="input-busqueda">
                <i class="fas fa-search icono-busqueda"></i>
                </div>
            <div id="lista-elementos-filtrados">
                <ul>`;

        // Primero, procesar las áreas existentes
        if (this.areas && this.areas.length > 0) {
            this.areas.forEach(area => {
                contenidoHTML += this.generarHTMLElementoFiltro(area, false);
                if (area.subdivisiones && area.subdivisiones.length > 0) {
                    area.subdivisiones.forEach(sub => {
                        contenidoHTML += this.generarHTMLElementoFiltro(sub, false, true);
                    });
                } else {
                    contenidoHTML += `<li class="subdivision-item no-items"><em>(Sin subdivisiones registradas)</em></li>`;
                }
            });
        }

        // Luego, procesar las áreas pendientes de guardar
        if (this.cambiosPendientes.size > 0) {
            let hayPendientes = false;
            contenidoHTML += `<li class="area-item separador-pendientes"><strong>Elementos No Guardados</strong></li>`;
            
            this.cambiosPendientes.forEach((cambio, dataId) => {
                if (cambio.esNuevo) {
                    hayPendientes = true;
                    contenidoHTML += this.generarHTMLElementoFiltro({
                        data_id: dataId,
                        nombre: cambio.nombre,
                        tipo: cambio.tipo,
                        tipo_especifico: cambio.tipo_especifico
                    }, true);
                }
            });

            if (!hayPendientes) {
                contenidoHTML += `<li class="no-items"><em>No hay elementos pendientes de guardar</em></li>`;
            }
        }

        contenidoHTML += '</ul></div>';
        
        if (!this.areas || this.areas.length === 0 && this.cambiosPendientes.size === 0) {
            contenidoHTML = '<div class="filtro-busqueda"><input type="text" id="busqueda-filtro" placeholder="Buscar por nombre..." class="input-busqueda"><i class="fas fa-search icono-busqueda"></i></div><div id="lista-elementos-filtrados"><ul><li class="no-items">No hay áreas cargadas en el mapa.</li></ul></div>';
        }
        
        listaContenedor.innerHTML = contenidoHTML;

        // Añadir estilos específicos
        const style = document.createElement('style');
        style.textContent = `
            .filtro-busqueda {
                position: relative;
                margin-bottom: 20px;
            }
            .input-busqueda {
                width: 100%;
                padding: 10px 35px 10px 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
                transition: border-color 0.3s;
            }
            .input-busqueda:focus {
                border-color: #4CAF50;
                outline: none;
                box-shadow: 0 0 0 2px rgba(76,175,80,0.1);
            }
            .icono-busqueda {
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: #666;
            }
            .elemento-oculto {
                display: none !important;
            }
            .separador-pendientes {
                margin-top: 20px;
                padding: 10px;
                background-color: #fff3cd;
                border-left: 4px solid #ffc107;
                color: #856404;
            }
            .elemento-pendiente {
                background-color: #fff3cd !important;
                border-left: 4px solid #ffc107 !important;
            }
            .elemento-pendiente::after {
                content: '(No guardado)';
                font-style: italic;
                color: #856404;
                margin-left: 8px;
                font-size: 0.9em;
            }
            .elemento-pendiente .btn-lista-accion {
                background-color: #ffc107;
                border-color: #ffc107;
                color: #000;
            }
            .elemento-pendiente .btn-lista-accion:hover {
                background-color: #e0a800;
                border-color: #d39e00;
            }
        `;
        document.head.appendChild(style);

        // Inicializar búsqueda
        const inputBusqueda = document.getElementById('busqueda-filtro');
        if (inputBusqueda) {
            inputBusqueda.addEventListener('input', this.filtrarElementos.bind(this));
        }

        // Event Listener con Delegación para los botones
        if (this.handleFiltroItemClick) {
            listaContenedor.removeEventListener('click', this.handleFiltroItemClick);
        }

        this.handleFiltroItemClick = (event) => {
            const target = event.target;
            const enfocarBtn = target.closest('.btn-enfocar-item');
            const editarBtn = target.closest('.btn-editar-item');

            if (enfocarBtn) {
                const dataId = enfocarBtn.dataset.id;
                this.enfocarElementoDesdeFiltro(dataId);
                this.cerrarModalFiltro();
            } else if (editarBtn) {
                const dataId = editarBtn.dataset.id;
                this.editarElementoDesdeFiltro(dataId);
                this.cerrarModalFiltro();
            }
        };
        listaContenedor.addEventListener('click', this.handleFiltroItemClick);

        // Listener para cerrar con doble clic afuera
        if (this.handleFiltroDblClickOutside) {
            modal.removeEventListener('dblclick', this.handleFiltroDblClickOutside);
        }
        this.handleFiltroDblClickOutside = (event) => {
            if (event.target === modal) {
                this.cerrarModalFiltro();
            }
        };
        modal.addEventListener('dblclick', this.handleFiltroDblClickOutside);

        modal.style.display = 'flex';
    }

    filtrarElementos(event) {
        const busqueda = event.target.value.toLowerCase();
        const listaElementos = document.getElementById('lista-elementos-filtrados');
        if (!listaElementos) return;

        const elementos = listaElementos.querySelectorAll('li');
        let algunElementoVisible = false;

        elementos.forEach(elemento => {
            // No filtrar elementos que son mensajes o separadores
            if (elemento.classList.contains('no-items') || elemento.classList.contains('separador-pendientes')) {
                return;
            }

            const texto = elemento.querySelector('span')?.textContent.toLowerCase() || '';
            const coincide = texto.includes(busqueda);

            elemento.classList.toggle('elemento-oculto', !coincide);
            if (coincide) algunElementoVisible = true;
        });

        // Mostrar mensaje si no hay resultados
        let mensajeNoResultados = listaElementos.querySelector('.no-resultados');
        if (!algunElementoVisible) {
            if (!mensajeNoResultados) {
                mensajeNoResultados = document.createElement('li');
                mensajeNoResultados.className = 'no-items no-resultados';
                mensajeNoResultados.innerHTML = '<em>No se encontraron elementos que coincidan con la búsqueda.</em>';
                listaElementos.querySelector('ul').appendChild(mensajeNoResultados);
            }
            mensajeNoResultados.classList.remove('elemento-oculto');
        } else if (mensajeNoResultados) {
            mensajeNoResultados.classList.add('elemento-oculto');
        }
    }

    // Método para generar el HTML de cada elemento en el filtro
    generarHTMLElementoFiltro(elemento, esPendiente = false, esSubdivision = false) {
        const dataId = elemento.data_id;
        const nombre = elemento.nombre || 'Sin nombre';
        const claseBase = esSubdivision ? 'subdivision-item' : 'area-item';
        const clasePendiente = esPendiente ? 'elemento-pendiente' : '';
        
        // Solo mostrar el tipo si es una subdivisión
        let displayText = nombre;
        if (esSubdivision) {
            let tipoTexto = 'Sin tipo';
            
            // Si es un tipo inactivo y tenemos el nombre del tipo
            if (elemento.tipo_nombre) {
                tipoTexto = elemento.tipo_activo === false ? 
                    `${elemento.tipo_nombre} (Inactivo)` : 
                    elemento.tipo_nombre;
            } else if (elemento.tipo_id) {
                // Si solo tenemos el ID, intentar encontrar el nombre en tiposAreaDisponibles
                const tipoEncontrado = tiposAreaDisponibles.find(t => t.id === elemento.tipo_id);
                if (tipoEncontrado) {
                    tipoTexto = tipoEncontrado.nombre;
                }
            }
            
            displayText = `${nombre} - ${tipoTexto}`;
        }
        
        return `
            <li class="${claseBase} ${clasePendiente}" data-id="${dataId}">
                <span>${displayText}</span>
                <div class="item-actions">
                    <button class="btn-lista-accion btn-enfocar-item" data-id="${dataId}" title="Enfocar ${esSubdivision ? 'Subdivisión' : 'Área'}">
                        <i class="fas fa-crosshairs"></i>
                    </button>
                    <button class="btn-lista-accion btn-editar-item" data-id="${dataId}" title="Editar ${esSubdivision ? 'Subdivisión' : 'Área'}">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                </div>
            </li>`;
    }

    // --- MODIFICADO: Cerrar Modal Filtro ---
    cerrarModalFiltro() {
        const modal = document.getElementById('modal-filtro');
        const listaContenedor = document.getElementById('filtro-lista-elementos');

        if (modal) {
            modal.style.display = 'none';
            if (this.handleFiltroDblClickOutside) {
                modal.removeEventListener('dblclick', this.handleFiltroDblClickOutside);
                delete this.handleFiltroDblClickOutside;
            }
             // Eliminar listener de clics en items
            if (listaContenedor && this.handleFiltroItemClick) {
                listaContenedor.removeEventListener('click', this.handleFiltroItemClick);
                delete this.handleFiltroItemClick;
            }
        }
    }

    // --- NUEVO: Enfocar Elemento desde Filtro ---
    enfocarElementoDesdeFiltro(dataId) {
        const elemento = document.querySelector(`[data-id="${dataId}"]`);
        const mapaContainer = document.getElementById('mapa-container');
        if (!elemento || !mapaContainer) {
            console.warn(`Elemento con data-id "${dataId}" o contenedor no encontrado.`);
            return;
        }

        this.quitarEnfoqueMapa(); // Quitar enfoque anterior

        const esAreaPadre = elemento.classList.contains('area-padre');
        const parentId = !esAreaPadre ? elemento.dataset.parentId : null;

        console.log(`Enfocando: ${dataId}, esAreaPadre: ${esAreaPadre}, parentId: ${parentId}`);

        mapaContainer.classList.add('mapa-enfocado');
        this.elementoEnfocadoId = dataId;

        document.querySelectorAll('#mapa-svg .area-interactiva').forEach(el => {
            const elDataId = el.dataset.id;
            const elParentId = el.dataset.parentId;
            el.classList.remove('enfocado', 'contexto-enfocado'); // Limpiar primero

            if (esAreaPadre) {
                if (elDataId === dataId) {
                    el.classList.add('enfocado');
                    el.style.display = 'block';
                } else if (elParentId === dataId) {
                    el.classList.add('contexto-enfocado');
                }
            } else {
                if (elDataId === dataId) {
                    el.classList.add('enfocado');
                } else if (elDataId === parentId) {
                    el.classList.add('contexto-enfocado');
                    el.style.display = 'block';
                } else if (elParentId === parentId) {
                     el.classList.add('contexto-enfocado');
                }
            }
        });

        this.centrarVistaEnElemento(elemento);
    }

    // --- NUEVO: Quitar Enfoque del Mapa ---
    quitarEnfoqueMapa() {
        const mapaContainer = document.getElementById('mapa-container');
        if (mapaContainer) {
            mapaContainer.classList.remove('mapa-enfocado');
        }
        document.querySelectorAll('#mapa-svg .area-interactiva').forEach(el => {
            el.classList.remove('enfocado', 'contexto-enfocado');
            if (el.classList.contains('area-padre')) {
                 const btnToggleAreas = document.getElementById('toggle-areas-padre');
                 const mostrarArea = this.editMode && btnToggleAreas && btnToggleAreas.classList.contains('active');
                 el.style.display = mostrarArea ? 'block' : 'none';
            }
        });
        this.elementoEnfocadoId = null;
        console.log("Enfoque del mapa quitado.");
    }

    // --- NUEVO: Centrar Vista (reemplaza lógica anterior en enfocarElementoDesdeFiltro) ---
    centrarVistaEnElemento(elemento) {
        const elementoSVG = SVG(elemento);
        if (!elementoSVG) return;

        const svg = this.svgCanvas || SVG('#mapa-svg');
        const bbox = elementoSVG.bbox();

        if (!bbox.width || !bbox.height) { return; }

        const elementoCenterX = bbox.x + bbox.width / 2;
        const elementoCenterY = bbox.y + bbox.height / 2;

        const transformMatrix = new DOMMatrix(window.getComputedStyle(svg.node).transform);
        const currentScale = transformMatrix.a;
        const contenedor = document.getElementById('mapa-container');
        const contRect = contenedor.getBoundingClientRect();

        const targetTranslateX = -(elementoCenterX * currentScale - contRect.width / 2);
        const targetTranslateY = -(elementoCenterY * currentScale - contRect.height / 2);

        this.scale = currentScale; // Mantener escala actual
        this.translateX = targetTranslateX;
        this.translateY = targetTranslateY;

        if (typeof this.applyTransform === 'function') {
            this.applyTransform();
        } else {
            console.warn("Función applyTransform no disponible para centrar vista.");
        }
    }
    
    // --- Asegúrate que estas funciones existan y estén descomentadas ---
    applyTransform() {
        const svg = document.getElementById('mapa-svg');
        if (svg) {
            svg.style.transform = `translate(${this.translateX}px, ${this.translateY}px) scale(${this.scale})`;
            this.updateZoomLevelDisplay(); // Si tienes esta función
        }
    }
    
    updateZoomLevelDisplay() {
        const zoomLevel = document.getElementById('zoom-level');
        if(zoomLevel) {
            zoomLevel.textContent = `${Math.round(this.scale * 100)}%`;
            zoomLevel.classList.remove('oculto');
            clearTimeout(zoomLevel.timeoutId);
            zoomLevel.timeoutId = setTimeout(() => {
                zoomLevel.classList.add('oculto');
            }, 1500);
        }
    }
    // ---------------------------------------------------------------------

    // --- NUEVO: Editar Elemento desde Filtro ---
    editarElementoDesdeFiltro(dataId) {
        const elementoDOM = document.querySelector(`[data-id="${dataId}"]`);
        if (!elementoDOM) {
            console.warn(`Elemento con data-id "${dataId}" no encontrado en el mapa.`);
            return;
        }

        if (!this.editMode) {
            this.toggleEditMode();
        }

        // Necesitamos asegurarnos que el elemento está listo para la edición
        // Forzar la preparación si es necesario (o esperar un ciclo de eventos)
        if (!this.editableElements.has(dataId)) {
            console.log(`Elemento ${dataId} no estaba en editableElements, preparando...`);
            this.prepararElementosParaEdicion(); // Esto podría ser costoso si hay muchos elementos
        }

        // Buscar el handler de click y llamarlo
        if (elementoDOM.clickHandlerEdicion) {
             elementoDOM.clickHandlerEdicion({ target: elementoDOM, stopPropagation: () => {} });
        } else {
             console.warn("clickHandlerEdicion no encontrado en el elemento. Intentando selección manual.");
             if (this.elementoSeleccionadoParaEdicion && this.elementoSeleccionadoParaEdicion !== elementoDOM) {
                 this.elementoSeleccionadoParaEdicion.classList.remove('editando');
             }
             elementoDOM.classList.add('editando');
             this.elementoSeleccionadoParaEdicion = elementoDOM;
             const colorInput = document.getElementById('edit-color-elemento');
             const svgElement = SVG(elementoDOM);
             if (colorInput && svgElement) {
                 const currentColor = svgElement.attr('fill') || '#D3D3D3';
                 colorInput.value = currentColor;
                 console.log(`Elemento ${dataId} seleccionado manualmente para edicion. Color: ${currentColor}`);
             }
        }
    }

    // Agregar nuevos métodos para manejar el modal
    abrirModalAgregarArea() {
        const modal = document.getElementById('modal-agregar-area');
        if (!modal) return;

        // Limpiar el formulario
        const form = document.getElementById('form-agregar-area');
        if (form) form.reset();

        // Establecer valores por defecto para las coordenadas y dimensiones
        document.getElementById('pos-x').value = '100';
        document.getElementById('pos-y').value = '100';
        document.getElementById('ancho').value = '100';
        document.getElementById('alto').value = '100';

        // Mostrar el modal
        modal.style.display = 'flex';

        // Agregar listeners si no existen
        this.inicializarListenersModalArea();
    }

    cerrarModalAgregarArea() {
        const modal = document.getElementById('modal-agregar-area');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    inicializarListenersModalArea() {
        // Botón cerrar
        const btnCerrar = document.getElementById('btn-cerrar-modal-area');
        if (btnCerrar && !btnCerrar.hasListener) {
            btnCerrar.addEventListener('click', () => this.cerrarModalAgregarArea());
            btnCerrar.hasListener = true;
        }

        // Botón cancelar
        const btnCancelar = document.getElementById('btn-cancelar-area');
        if (btnCancelar && !btnCancelar.hasListener) {
            btnCancelar.addEventListener('click', () => this.cerrarModalAgregarArea());
            btnCancelar.hasListener = true;
        }

        // Botón guardar
        const btnGuardar = document.getElementById('btn-guardar-area');
        if (btnGuardar && !btnGuardar.hasListener) {
            btnGuardar.addEventListener('click', () => this.guardarNuevaArea());
            btnGuardar.hasListener = true;
        }

        // Selector de tipo de área
        const selectTipoArea = document.getElementById('tipo-area');
        const campoTipoOtro = document.getElementById('campo-tipo-area-otro');
        const inputTipoOtro = document.getElementById('tipo-area-otro');
        if (selectTipoArea && campoTipoOtro && !selectTipoArea.hasChangeListener) {
            selectTipoArea.addEventListener('change', () => {
                if (selectTipoArea.value === 'otros') {
                    campoTipoOtro.style.display = 'block';
                    if(inputTipoOtro) inputTipoOtro.required = true;
                } else {
                    campoTipoOtro.style.display = 'none';
                    if(inputTipoOtro) {
                         inputTipoOtro.value = '';
                         inputTipoOtro.required = false;
                    }
                }
            });
            selectTipoArea.hasChangeListener = true;
        }

        // Cerrar al hacer clic fuera del modal
        const modal = document.getElementById('modal-agregar-area');
        if (modal && !modal.hasListener) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.cerrarModalAgregarArea();
                }
            });
            modal.hasListener = true;
        }
    }

    // Este método se implementará después cuando programemos la funcionalidad completa
    guardarNuevaArea() {
        const form = document.getElementById('form-agregar-area');
        const nombreInput = document.getElementById('nombre-area');
        const tipoSelect = document.getElementById('tipo-area');
        const tipoOtroInput = document.getElementById('tipo-area-otro');
        const colorInput = document.getElementById('color-area');
        const posXInput = document.getElementById('pos-x');
        const posYInput = document.getElementById('pos-y');
        const anchoInput = document.getElementById('ancho');
        const altoInput = document.getElementById('alto');

        // 1. Validación
        let esValido = true;
        let mensajeError = "Por favor, corrija los siguientes errores:\n";

        const x = parseFloat(posXInput.value);
        const y = parseFloat(posYInput.value);
        const width = parseFloat(anchoInput.value);
        const height = parseFloat(altoInput.value);

        if (isNaN(x) || isNaN(y) || isNaN(width) || isNaN(height)) {
            esValido = false;
            mensajeError += "- Las coordenadas y dimensiones deben ser números válidos.\n";
        }
        if (width <= 0 || height <= 0) {
            esValido = false;
            mensajeError += "- El ancho y alto deben ser mayores que 0.\n";
        }

        if (nombreInput.value.trim() === '') {
            esValido = false;
            mensajeError += "- El nombre del área es requerido.\n";
        }
        if (tipoSelect.value === '') {
            esValido = false;
            mensajeError += "- Debe seleccionar un tipo de área.\n";
        }
        if (tipoSelect.value === 'otros' && tipoOtroInput.value.trim() === '') {
            esValido = false;
            mensajeError += "- Debe especificar el tipo de área si selecciona 'Otros'.\n";
        }

        if (!esValido) {
            alert(mensajeError);
            return;
        }

        // 2. Recoger datos y determinar Data ID
        const nombre = nombreInput.value.trim();
        const tipoValue = tipoSelect.value;
        let tipoEspecifico = null;
        
        if (tipoValue === 'otros') {
            tipoEspecifico = tipoOtroInput.value.trim();
        }

        const color = colorInput.value;
        
        let finalDataId;
        const tiposAutoIncrement = ['parqueo', 'cancha', 'seccion'];
        
        if (tiposAutoIncrement.includes(tipoValue)) {
            const prefix = `${tipoValue}-`;
            let maxSuffix = 0;
            const regex = new RegExp(`^${prefix}(\\d+)$`);

            // Recopilar todos los data_id existentes relevantes
            const allDataIds = [];
            // De áreas existentes
            this.areas.forEach(area => {
                if (area.data_id) allDataIds.push(area.data_id);
                // De subdivisiones existentes
                if (area.subdivisiones) {
                    area.subdivisiones.forEach(sub => {
                        if (sub.data_id) allDataIds.push(sub.data_id);
                    });
                }
            });
            // De áreas nuevas pendientes del mismo tipo
            this.cambiosPendientes.forEach((cambio, id) => {
                if (cambio.esNuevo && id.startsWith(prefix)) {
                    allDataIds.push(id);
                }
            });

            console.log(`Buscando sufijo para ${prefix} en IDs:`, allDataIds);

            allDataIds.forEach(id => {
                const match = id.match(regex);
                if (match && match[1]) {
                    const suffix = parseInt(match[1], 10);
                    if (!isNaN(suffix)) {
                        maxSuffix = Math.max(maxSuffix, suffix);
                    }
                }
            });
            
            const newSuffix = maxSuffix + 1;
            finalDataId = `${prefix}${newSuffix}`;
            console.log(`Nuevo data_id generado: ${finalDataId}`);
            
        } else {
            finalDataId = `${tipoValue}_${Date.now()}`;
            console.log(`Usando data_id temporal: ${finalDataId}`);
        }

        const pathData = `M${x} ${y} h ${width} v ${height} h -${width} Z`;

        try {
            const nuevoElementoSVG = this.svgCanvas.path(pathData);
            nuevoElementoSVG.attr({
                fill: color,
                stroke: '#000',
                'stroke-width': 1,
                id: finalDataId,
                'data-id': finalDataId,
                'data-nombre': nombre
            });
            nuevoElementoSVG.addClass('area-interactiva').addClass('editable');

            const titulo = document.createElementNS('http://www.w3.org/2000/svg', 'title');
            titulo.textContent = nombre;
            nuevoElementoSVG.node.appendChild(titulo);

            // 4. Hacer editable y marcar
            this.editableElements.set(finalDataId, nuevoElementoSVG);
            this.hacerElementoDraggableYSeleccionable(nuevoElementoSVG);

            // Deseleccionar anterior si existe
            if (this.elementoSeleccionadoParaEdicion) {
                this.elementoSeleccionadoParaEdicion.classList.remove('editando');
            }
            // Seleccionar nuevo
            nuevoElementoSVG.addClass('editando');
            this.elementoSeleccionadoParaEdicion = nuevoElementoSVG.node;

            // Actualizar panel de edición
            const colorInputPanel = document.getElementById('edit-color-elemento');
            if (colorInputPanel) {
                colorInputPanel.value = color;
            }

            // 5. Almacenar cambio pendiente
            const cambio = {
                esNuevo: true,
                nombre: nombre,
                tipo_valor: tipoValue, // Guardamos el valor del tipo para referencia
                tipo_especifico: tipoEspecifico,
                color: color,
                x: x,
                y: y,
                width: width,
                height: height,
                path_data: pathData
            };
            this.cambiosPendientes.set(finalDataId, cambio);
            
            // 6. Añadir al historial
            this.addActionToHistory({
                type: 'create',
                elementId: finalDataId,
                data: cambio
            });

            console.log("Nueva área creada localmente:", finalDataId, cambio);
            console.log("Cambios pendientes:", this.cambiosPendientes);

            this.actualizarVisibilidadBotonGuardarEdicion();
            this.actualizarVisibilidadBotonDeshacer();

            // 7. Cerrar modal
            this.cerrarModalAgregarArea();
            
        } catch (error) {
            console.error("Error al crear el elemento SVG:", error);
            alert("Error al dibujar la nueva área en el mapa.");
        }
    }
    
    // Nueva función auxiliar para aplicar listeners de edición
    hacerElementoDraggableYSeleccionable(svgElement) {
        const elementoDOM = svgElement.node;
        const dataId = elementoDOM.dataset.id;
        
        elementoDOM.style.cursor = 'move';
        
        if (typeof svgElement.draggable === 'function') {
            svgElement.draggable()
                .on('dragstart', () => {
                    if (this.elementoSeleccionadoParaEdicion === elementoDOM) {
                        elementoDOM.classList.remove('editando');
                        this.elementoSeleccionadoParaEdicion = null;
                    }
                    this.tooltip.style.display = 'none';
                })
                .on('dragend', () => {
                    this.guardarPosicionElemento(svgElement);
                });
        } else {
            console.warn("SVG.draggable no está disponible.");
        }
        
        // Listener para SELECCIONAR en modo edición
        elementoDOM.clickHandlerEdicion = (event) => {
            console.log(`Click en elemento ${elementoDOM.dataset.id}`);
            event.stopPropagation();
        
            // Quitar clase 'editando' del anterior
            if (this.elementoSeleccionadoParaEdicion && this.elementoSeleccionadoParaEdicion !== elementoDOM) {
                this.elementoSeleccionadoParaEdicion.classList.remove('editando');
            }
        
            // Seleccionar el nuevo elemento
            elementoDOM.classList.add('editando');
            this.elementoSeleccionadoParaEdicion = elementoDOM;
        
            // Actualizar panel de propiedades (color)
            const colorInput = document.getElementById('edit-color-elemento');
            if (colorInput) {
                const currentColor = svgElement.attr('fill') || '#D3D3D3';
                colorInput.value = currentColor;
            }

            // Actualizar y habilitar campos superiores
            const plantaId = elementoDOM.dataset.id;
            const nombre = elementoDOM.dataset.nombre;
            const tipo = elementoDOM.dataset.tipo || '';

            document.getElementById('planta_id').value = plantaId;
            document.getElementById('nombre_planta').value = nombre;
            document.getElementById('tipo_planta').value = tipo;
            document.getElementById('nombre_planta').disabled = false; // Habilitar solo el nombre
            document.getElementById('tipo_planta').disabled = false; // Habilitar el selector de tipo en modo edición

            // Botón de eliminar - Mejorar verificación y visibilidad
            const btnEliminar = document.getElementById('btn-eliminar-elemento');
            console.log('Botón eliminar encontrado:', btnEliminar);
            
            if (btnEliminar) {
                console.log('Mostrar botón eliminar para:', plantaId);
                // Forzar visibilidad usando inline-block
                btnEliminar.style.display = 'inline-block'; 
                btnEliminar.style.visibility = 'visible';
                btnEliminar.style.opacity = '1';
                
                // Verificar si ya tiene listener para no duplicarlo
                if (!btnEliminar.hasListener) {
                    console.log('Añadiendo evento click al botón eliminar');
                    btnEliminar.addEventListener('click', () => {
                        console.log('Botón eliminar clickeado');
                        this.mostrarModalConfirmarEliminar();
                    });
                    btnEliminar.hasListener = true;
                }
            }

            // Ocultar botón de agregar área
            const btnAgregarArea = document.getElementById('btn-agregar-area');
            if (btnAgregarArea) {
                btnAgregarArea.style.display = 'none';
            }
        };
        elementoDOM.addEventListener('click', elementoDOM.clickHandlerEdicion);
    }

    prepararElementosParaEdicion() {
        console.log("Iniciando preparación de elementos para edición");
        const elementosDOM = document.querySelectorAll('.area-interactiva');
        console.log(`Encontrados ${elementosDOM.length} elementos interactivos`);
        
        elementosDOM.forEach(elementoDOM => {
            console.log(`Procesando elemento: ${elementoDOM.dataset.id}`);
            
            if (!elementoDOM.classList.contains('area-padre') || elementoDOM.classList.contains('visible')) {
                elementoDOM.classList.add('editable');
                elementoDOM.style.cursor = 'move';

                try {
                    // Convertir el elemento DOM a un elemento SVG.js y guardarlo
                    const svgElement = SVG(elementoDOM);
                    this.editableElements.set(elementoDOM.dataset.id, svgElement);
                    console.log(`Elemento ${elementoDOM.dataset.id} convertido a SVG.js y guardado`);

                    // Hacer el elemento arrastrable
                    if (typeof svgElement.draggable === 'function') {
                        svgElement.draggable()
                            .on('dragstart', () => {
                                console.log(`Iniciando arrastre de ${elementoDOM.dataset.id}`);
                                if (this.elementoSeleccionadoParaEdicion === elementoDOM) {
                                    elementoDOM.classList.remove('editando');
                                    this.elementoSeleccionadoParaEdicion = null;
                                }
                                this.tooltip.style.display = 'none';
                            })
                            .on('dragend', () => {
                                const bbox = svgElement.bbox();
                                this.guardarPosicionElemento(svgElement);
                                this.actualizarCamposTamaño(svgElement);
                            });
                    }

                    // Listener para SELECCIONAR en modo edición
                    elementoDOM.clickHandlerEdicion = (event) => {
                        console.log(`Click en elemento ${elementoDOM.dataset.id}`);
                        event.stopPropagation();
                    
                        // Quitar clase 'editando' del anterior
                        if (this.elementoSeleccionadoParaEdicion && this.elementoSeleccionadoParaEdicion !== elementoDOM) {
                            console.log(`Deseleccionando elemento anterior: ${this.elementoSeleccionadoParaEdicion.dataset.id}`);
                            this.elementoSeleccionadoParaEdicion.classList.remove('editando');
                        }
                    
                        // Seleccionar el nuevo elemento
                        console.log(`Seleccionando nuevo elemento: ${elementoDOM.dataset.id}`);
                        elementoDOM.classList.add('editando');
                        this.elementoSeleccionadoParaEdicion = elementoDOM;
                    
                        // Actualizar panel de propiedades (color)
                        const colorInput = document.getElementById('edit-color-elemento');
                        if (colorInput) {
                            const elementSvg = this.editableElements.get(elementoDOM.dataset.id);
                            if (elementSvg) {
                                const currentColor = elementSvg.attr('fill') || '#D3D3D3';
                                colorInput.value = currentColor;
                                // Actualizar campos de tamaño
                                this.actualizarCamposTamaño(elementSvg);
                            }
                        }

                        // Actualizar y habilitar campos superiores
                        const plantaId = elementoDOM.dataset.id;
                        const nombre = elementoDOM.dataset.nombre;
                        const tipo = elementoDOM.dataset.tipo || '';

                        document.getElementById('planta_id').value = plantaId;
                        document.getElementById('nombre_planta').value = nombre;
                        document.getElementById('tipo_planta').value = tipo;
                        document.getElementById('nombre_planta').disabled = false;
                        document.getElementById('tipo_planta').disabled = false;

                        // Mostrar sección de edición de tamaño
                        const seccionTamaño = document.getElementById('seccion-tamaño');
                        if (seccionTamaño) {
                            seccionTamaño.style.display = 'block';
                        }

                        // Configurar el botón de agregar subdivisión
                        const btnAgregarArea = document.getElementById('btn-agregar-area');
                        if (btnAgregarArea) {
                            btnAgregarArea.innerHTML = '<i class="fas fa-plus"></i> Agregar Subdivisión';
                            btnAgregarArea.style.display = 'inline-flex';

                            if (!elementoDOM.dataset.parentId) {
                                // Si es un área, usar directamente el área actual
                                console.log(`Configurando botón para agregar subdivisión al área ${elementoDOM.dataset.id}`);
                                btnAgregarArea.onclick = () => this.abrirModalAgregarSubdivision(elementoDOM.dataset.id);
                            } else {
                                // Si es una subdivisión, usar sus datos para la configuración
                                const parentId = elementoDOM.dataset.parentId;
                                console.log(`Configurando botón para agregar subdivisión al área padre ${parentId}`);
                                // Crear un objeto con los datos necesarios
                                const areaPadreVirtual = {
                                    dataset: {
                                        id: parentId
                                    }
                                };
                                btnAgregarArea.onclick = () => this.abrirModalAgregarSubdivision(areaPadreVirtual);
                            }
                        }
                    };
                    elementoDOM.addEventListener('click', elementoDOM.clickHandlerEdicion);
                    console.log(`Listeners configurados para elemento ${elementoDOM.dataset.id}`);

                } catch (error) {
                    console.error(`Error al procesar elemento ${elementoDOM.dataset.id}:`, error);
                }
            } else {
                console.log(`Elemento ${elementoDOM.dataset.id} ignorado (área padre no visible)`);
            }
        });

        // Configurar el botón de agregar área inicialmente
        const btnAgregarArea = document.getElementById('btn-agregar-area');
        if (btnAgregarArea) {
            if (!this.elementoSeleccionadoParaEdicion) {
                btnAgregarArea.innerHTML = '<i class="fas fa-plus"></i> Agregar Área';
                btnAgregarArea.style.display = 'inline-flex';
                btnAgregarArea.onclick = () => this.abrirModalAgregarArea();
                console.log("Botón 'Agregar Área' configurado inicialmente");
            } else {
                btnAgregarArea.style.display = 'none';
                console.log("Botón 'Agregar Área' ocultado inicialmente");
            }
        }

        // Configurar eventos para los campos de tamaño
        this.inicializarEventosCamposTamaño();
        console.log("Preparación de elementos para edición completada");
    }

    actualizarCamposTamaño(svgElement) {
        if (!svgElement) return;

        const bbox = svgElement.bbox();
        const campoX = document.getElementById('edit-pos-x');
        const campoY = document.getElementById('edit-pos-y');
        const campoAncho = document.getElementById('edit-width');
        const campoAlto = document.getElementById('edit-height');

        if (campoX) campoX.value = Math.round(bbox.x);
        if (campoY) campoY.value = Math.round(bbox.y);
        if (campoAncho) campoAncho.value = Math.round(bbox.width);
        if (campoAlto) campoAlto.value = Math.round(bbox.height);
    }

    inicializarEventosCamposTamaño() {
        const campos = ['edit-pos-x', 'edit-pos-y', 'edit-width', 'edit-height'];
        
        campos.forEach(campoId => {
            const campo = document.getElementById(campoId);
            if (campo) {
                campo.addEventListener('change', () => {
                    this.aplicarCambiosTamaño();
                });
            }
        });

        // Botón para aplicar cambios
        const btnAplicarTamaño = document.getElementById('btn-aplicar-tamaño');
        if (btnAplicarTamaño) {
            btnAplicarTamaño.addEventListener('click', () => {
                this.aplicarCambiosTamaño();
            });
        }
    }

    aplicarCambiosTamaño() {
        if (!this.elementoSeleccionadoParaEdicion) return;

        const elementoSvg = this.editableElements.get(this.elementoSeleccionadoParaEdicion.dataset.id);
        if (!elementoSvg) return;

        const x = parseFloat(document.getElementById('edit-pos-x').value);
        const y = parseFloat(document.getElementById('edit-pos-y').value);
        const width = parseFloat(document.getElementById('edit-width').value);
        const height = parseFloat(document.getElementById('edit-height').value);

        if (isNaN(x) || isNaN(y) || isNaN(width) || isNaN(height)) {
            alert('Por favor, ingrese valores numéricos válidos para las dimensiones.');
            return;
        }

        if (width <= 0 || height <= 0) {
            alert('El ancho y alto deben ser mayores que 0.');
            return;
        }

        // Guardar estado anterior para el historial
        const bboxAnterior = elementoSvg.bbox();
        const estadoAnterior = {
            x: bboxAnterior.x,
            y: bboxAnterior.y,
            width: bboxAnterior.width,
            height: bboxAnterior.height
        };

        // Aplicar cambios
        const pathData = `M${x} ${y} h ${width} v ${height} h -${width} Z`;
        elementoSvg.plot(pathData);

        // Registrar en el historial
        this.addActionToHistory({
            type: 'resize',
            elementId: this.elementoSeleccionadoParaEdicion.dataset.id,
            previousState: estadoAnterior,
            currentState: { x, y, width, height }
        });

        // Actualizar cambios pendientes
        const cambiosPrevios = this.cambiosPendientes.get(this.elementoSeleccionadoParaEdicion.dataset.id) || {};
        this.cambiosPendientes.set(this.elementoSeleccionadoParaEdicion.dataset.id, {
            ...cambiosPrevios,
            x, y, width, height
        });

        this.actualizarVisibilidadBotonGuardarEdicion();
        this.actualizarVisibilidadBotonDeshacer();
    }

    deseleccionarElementoEdicion() {
        if (this.elementoSeleccionadoParaEdicion) {
            console.log("Deseleccionando elemento en modo edición:", this.elementoSeleccionadoParaEdicion.dataset.id);
            this.elementoSeleccionadoParaEdicion.classList.remove('editando');
            this.elementoSeleccionadoParaEdicion = null;

            // Limpiar campos superiores
            document.getElementById('planta_id').value = '';
            document.getElementById('nombre_planta').value = '';
            document.getElementById('tipo_planta').value = '';
            document.getElementById('nombre_planta').disabled = true;

            // Limpiar campos de tamaño
            const campos = ['edit-pos-x', 'edit-pos-y', 'edit-width', 'edit-height'];
            campos.forEach(campoId => {
                const campo = document.getElementById(campoId);
                if (campo) campo.value = '';
            });

            // Ocultar sección de tamaño
            const seccionTamaño = document.getElementById('seccion-tamaño');
            if (seccionTamaño) {
                seccionTamaño.style.display = 'none';
            }

            const colorInput = document.getElementById('edit-color-elemento');
            if (colorInput) {
                colorInput.value = '#000000';
            }
        }
    }

    abrirModalAgregarSubdivision(elementoPadre) {
        const modal = document.getElementById('modal-agregar-area');
        if (!modal) {
            console.error("No se encontró el modal");
            return;
        }

        // Determinar el ID del área padre
        let areaId;
        if (typeof elementoPadre === 'string') {
            // Si se pasa directamente el ID
            areaId = elementoPadre;
        } else if (elementoPadre.dataset && elementoPadre.dataset.id) {
            // Si es un elemento DOM directo
            areaId = elementoPadre.dataset.id;
        } else if (elementoPadre.id) {
            // Si es un objeto con ID
            areaId = elementoPadre.id;
        } else if (elementoPadre.parentId) {
            // Si es un objeto con parentId
            areaId = elementoPadre.parentId;
        } else {
            console.error("No se pudo determinar el ID del área padre");
            return;
        }

        // Guardar el ID del área padre en el modal
        modal.dataset.padreId = areaId;
        console.log("ID del área padre guardado:", areaId);

        // Actualizar el título del modal
        const modalTitle = modal.querySelector('.modal-encabezado h3');
        if (modalTitle) {
            modalTitle.textContent = 'Agregar Nueva Subdivisión';
        }

        // Intentar obtener las dimensiones del área
        let dimensiones = this.obtenerDimensionesArea(areaId);
        if (!dimensiones) {
            console.error("No se pudieron obtener las dimensiones del área padre");
            return;
        }

        // Establecer valores en el formulario
        document.getElementById('pos-x').value = Math.round(dimensiones.x);
        document.getElementById('pos-y').value = Math.round(dimensiones.y);
        document.getElementById('ancho').value = Math.round(dimensiones.width);
        document.getElementById('alto').value = Math.round(dimensiones.height);
        document.getElementById('nombre-area').value = '';
        document.getElementById('color-area').value = '#D3D3D3';

        // Actualizar el select de tipos
        this.actualizarSelectTipos();

        // Mostrar el modal
        modal.style.display = 'flex';

        // Inicializar listeners
        this.inicializarListenersModalSubdivision();
    }

    obtenerDimensionesArea(areaId) {
        console.log("Obteniendo dimensiones para área:", areaId);
        
        // Primero intentar obtener el elemento SVG del área
        const areaSvg = this.editableElements.get(areaId);
        if (areaSvg) {
            const bbox = areaSvg.bbox();
            console.log("Dimensiones obtenidas del área directamente:", bbox);
            return {
                x: bbox.x + bbox.width * 0.1,
                y: bbox.y + bbox.height * 0.1,
                width: bbox.width * 0.3,
                height: bbox.height * 0.3
            };
        }

        // Si no se encuentra el área, buscar sus subdivisiones
        const subdivisiones = Array.from(document.querySelectorAll(`[data-parent-id="${areaId}"]`));
        if (subdivisiones.length > 0) {
            console.log(`Encontradas ${subdivisiones.length} subdivisiones para el área ${areaId}`);
            
            // Calcular el bounding box que contiene todas las subdivisiones
            let minX = Infinity, minY = Infinity;
            let maxX = -Infinity, maxY = -Infinity;
            
            subdivisiones.forEach(sub => {
                const subSvg = this.editableElements.get(sub.dataset.id);
                if (subSvg) {
                    const bbox = subSvg.bbox();
                    minX = Math.min(minX, bbox.x);
                    minY = Math.min(minY, bbox.y);
                    maxX = Math.max(maxX, bbox.x + bbox.width);
                    maxY = Math.max(maxY, bbox.y + bbox.height);
                }
            });

            if (minX !== Infinity) {
                const width = maxX - minX;
                const height = maxY - minY;
                console.log("Dimensiones calculadas desde subdivisiones:", {
                    x: minX, y: minY, width, height
                });
                return {
                    x: minX + width * 0.1,
                    y: minY + height * 0.1,
                    width: width * 0.3,
                    height: height * 0.3
                };
            }
        }

        // Si todo falla, usar dimensiones por defecto
        console.log("Usando dimensiones por defecto");
        return {
            x: 100,
            y: 100,
            width: 100,
            height: 100
        };
    }

    actualizarSelectTipos() {
        const tipoSelect = document.getElementById('tipo-area');
        const tipoPlantaSelect = document.getElementById('tipo_planta');
        
        if (tipoSelect && tipoPlantaSelect) {
            tipoSelect.innerHTML = '<option value="">Seleccione un tipo</option>';
            
            Array.from(tipoPlantaSelect.options).forEach(option => {
                if (option.value !== '') {
                    const newOption = document.createElement('option');
                    newOption.value = option.value;
                    newOption.textContent = option.textContent;
                    newOption.disabled = option.disabled;
                    if (option.dataset.dynamicallyAdded) {
                        newOption.dataset.dynamicallyAdded = option.dataset.dynamicallyAdded;
                    }
                    tipoSelect.appendChild(newOption);
                }
            });
        }
    }

    inicializarListenersModalSubdivision() {
        // Botón cerrar
        const btnCerrar = document.getElementById('btn-cerrar-modal-area');
        if (btnCerrar && !btnCerrar.hasListener) {
            btnCerrar.addEventListener('click', () => this.cerrarModalAgregarArea());
            btnCerrar.hasListener = true;
        }

        // Botón cancelar
        const btnCancelar = document.getElementById('btn-cancelar-area');
        if (btnCancelar && !btnCancelar.hasListener) {
            btnCancelar.addEventListener('click', () => this.cerrarModalAgregarArea());
            btnCancelar.hasListener = true;
        }

        // Botón guardar
        const btnGuardar = document.getElementById('btn-guardar-area');
        if (btnGuardar && !btnGuardar.hasListener) {
            btnGuardar.addEventListener('click', () => this.guardarNuevaSubdivision());
            btnGuardar.hasListener = true;
        }

        // Cerrar al hacer clic fuera del modal
        const modal = document.getElementById('modal-agregar-area');
        if (modal && !modal.hasListener) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.cerrarModalAgregarArea();
                }
            });
            modal.hasListener = true;
        }
    }

    guardarNuevaSubdivision() {
        const modal = document.getElementById('modal-agregar-area');
        const padreId = modal.dataset.padreId;
        if (!padreId) {
            console.error("No se encontró el ID del elemento padre");
            return;
        }

        const form = document.getElementById('form-agregar-area');
        const nombreInput = document.getElementById('nombre-area');
        const tipoSelect = document.getElementById('tipo-area');
        const tipoOtroInput = document.getElementById('tipo-area-otro');
        const colorInput = document.getElementById('color-area');
        const posXInput = document.getElementById('pos-x');
        const posYInput = document.getElementById('pos-y');
        const anchoInput = document.getElementById('ancho');
        const altoInput = document.getElementById('alto');

        // Validación básica de campos
        let esValido = true;
        let mensajeError = "Por favor, corrija los siguientes errores:\n";

        const x = parseFloat(posXInput.value);
        const y = parseFloat(posYInput.value);
        const width = parseFloat(anchoInput.value);
        const height = parseFloat(altoInput.value);

        // Obtener dimensiones del área padre
        const padreSvg = this.editableElements.get(padreId);
        if (!padreSvg) {
            alert("Error: No se puede encontrar el área padre.");
            return;
        }
        const padreBBox = padreSvg.bbox();

        // Validar que la nueva subdivisión está dentro del área padre
        if (x < padreBBox.x || y < padreBBox.y || 
            x + width > padreBBox.x + padreBBox.width || 
            y + height > padreBBox.y + padreBBox.height) {
            esValido = false;
            mensajeError += "- La subdivisión debe estar completamente dentro del área padre.\n";
        }

        // Validar que la subdivisión no ocupa más del 90% del área padre
        const areaSubdivision = width * height;
        const areaPadre = padreBBox.width * padreBBox.height;
        if (areaSubdivision > areaPadre * 0.9) {
            esValido = false;
            mensajeError += "- La subdivisión no puede ocupar más del 90% del área padre.\n";
        }

        // Validaciones básicas
        if (isNaN(x) || isNaN(y) || isNaN(width) || isNaN(height)) {
            esValido = false;
            mensajeError += "- Las coordenadas y dimensiones deben ser números válidos.\n";
        }
        if (width <= 0 || height <= 0) {
            esValido = false;
            mensajeError += "- El ancho y alto deben ser mayores que 0.\n";
        }
        if (nombreInput.value.trim() === '') {
            esValido = false;
            mensajeError += "- El nombre de la subdivisión es requerido.\n";
        }
        if (tipoSelect.value === '') {
            esValido = false;
            mensajeError += "- Debe seleccionar un tipo.\n";
        }

        if (!esValido) {
            alert(mensajeError);
            return;
        }

        // Generar el nuevo data_id basado en el área padre
        let nuevoDataId;
        // Buscar subdivisiones existentes del padre para determinar el siguiente número
        const subdivisionesExistentes = Array.from(document.querySelectorAll(`[data-parent-id="${padreId}"]`))
            .map(el => el.dataset.id)
            .filter(id => id.startsWith(padreId + '-'))
            .map(id => {
                const match = id.match(/-(\d+)$/);
                return match ? parseInt(match[1]) : 0;
            });

        // También buscar en cambios pendientes
        this.cambiosPendientes.forEach((cambio, id) => {
            if (cambio.parent_id === padreId && id.startsWith(padreId + '-')) {
                const match = id.match(/-(\d+)$/);
                if (match) {
                    subdivisionesExistentes.push(parseInt(match[1]));
                }
            }
        });

        const siguienteNumero = subdivisionesExistentes.length > 0 ? 
            Math.max(...subdivisionesExistentes) + 1 : 1;

        nuevoDataId = `${padreId}-${siguienteNumero}`;

        // Recoger datos
        const nombre = nombreInput.value.trim();
        const tipoValue = tipoSelect.value;
        const color = colorInput.value;
        const pathData = `M${x} ${y} h ${width} v ${height} h -${width} Z`;

        try {
            const nuevoElementoSVG = this.svgCanvas.path(pathData);
            nuevoElementoSVG.attr({
                fill: color,
                stroke: '#000',
                'stroke-width': 1,
                id: nuevoDataId,
                'data-id': nuevoDataId,
                'data-nombre': nombre,
                'data-parent-id': padreId
            });
            nuevoElementoSVG.addClass('area-interactiva').addClass('editable');

            const titulo = document.createElementNS('http://www.w3.org/2000/svg', 'title');
            titulo.textContent = nombre;
            nuevoElementoSVG.node.appendChild(titulo);

            // Hacer editable y marcar
            this.editableElements.set(nuevoDataId, nuevoElementoSVG);
            this.hacerElementoDraggableYSeleccionable(nuevoElementoSVG);

            // Deseleccionar anterior si existe
            if (this.elementoSeleccionadoParaEdicion) {
                this.elementoSeleccionadoParaEdicion.classList.remove('editando');
            }
            // Seleccionar nuevo
            nuevoElementoSVG.addClass('editando');
            this.elementoSeleccionadoParaEdicion = nuevoElementoSVG.node;

            // Actualizar panel de edición
            const colorInputPanel = document.getElementById('edit-color-elemento');
            if (colorInputPanel) {
                colorInputPanel.value = color;
            }

            // Almacenar cambio pendiente
            const cambio = {
                esNuevo: true,
                nombre: nombre,
                tipo_valor: tipoValue,
                color: color,
                x: x,
                y: y,
                width: width,
                height: height,
                path_data: pathData,
                parent_id: padreId
            };
            this.cambiosPendientes.set(nuevoDataId, cambio);

            // Añadir al historial
            this.addActionToHistory({
                type: 'create',
                elementId: nuevoDataId,
                data: cambio
            });

            this.actualizarVisibilidadBotonGuardarEdicion();
            this.actualizarVisibilidadBotonDeshacer();

            // Cerrar modal
            this.cerrarModalAgregarArea();

        } catch (error) {
            console.error("Error al crear la subdivisión:", error);
            alert("Error al crear la subdivisión en el mapa.");
        }
    }

    guardarTodosLosCambiosPendientes() {
        // Convertir el Map de cambios pendientes a un array de objetos
        const cambios = Array.from(this.cambiosPendientes.entries()).map(([dataId, cambio]) => {
            return {
                data_id: dataId,
                ...cambio
            };
        });

        // Añadir cambios de los campos del formulario si hay un elemento seleccionado
        const elementoId = document.getElementById('planta_id').value;
        if (elementoId && (this.elementoSeleccionadoParaEdicion || this.elementoSeleccionadoVista)) {
            // Obtener los valores actuales de nombre y tipo
            const nombreActual = document.getElementById('nombre_planta').value;
            const tipoActual = document.getElementById('tipo_planta').value;
            
            // Verificar si ya hay un cambio pendiente para este elemento
            let cambioExistente = cambios.find(c => c.data_id === elementoId);
            
            if (cambioExistente) {
                // Actualizar el cambio existente
                cambioExistente.nombre = nombreActual;
                cambioExistente.tipo_id = tipoActual;
                console.log(`Actualizado cambio existente para ${elementoId} con nombre=${nombreActual}, tipo_id=${tipoActual}`);
            } else {
                // Crear un nuevo cambio
                const nuevoCambio = {
                    data_id: elementoId,
                    nombre: nombreActual,
                    tipo_id: tipoActual
                };
                cambios.push(nuevoCambio);
                console.log(`Añadido nuevo cambio para ${elementoId} con nombre=${nombreActual}, tipo_id=${tipoActual}`);
            }
        }

        // Si no hay cambios, no hacer nada
        if (cambios.length === 0) {
            alert('No hay cambios pendientes para guardar');
            return;
        }

        console.log('Enviando cambios al servidor:', cambios);

        // Preparar los datos para enviar
        const formData = new FormData();
        formData.append('accion', 'guardar_cambios_edicion');
        formData.append('cambios', JSON.stringify(cambios));

        // Enviar al servidor
        fetch('../ajax/mapa_interactivo_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(async response => {
            const text = await response.text();
            console.log('Respuesta cruda del servidor:', text);
            
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear la respuesta como JSON:', e);
                console.error('Contenido de la respuesta:', text);
                throw new Error('La respuesta del servidor no es JSON válido');
            }
        })
        .then(data => {
            if (data.exito) {
                // Limpiar cambios pendientes
                this.cambiosPendientes.clear();
                // Limpiar historial de acciones
                this.actionHistory = [];
                // Actualizar visibilidad de botones
                this.actualizarVisibilidadBotonGuardarEdicion();
                this.actualizarVisibilidadBotonDeshacer();
                // Mostrar mensaje de éxito
                alert('Cambios guardados correctamente');
                // Recargar el mapa para mostrar los cambios confirmados
                this.cargarMapa();
            } else {
                console.error('Error del servidor:', data);
                alert('Error al guardar los cambios: ' + data.mensaje);
            }
        })
        .catch(error => {
            console.error('Error completo en la solicitud:', error);
            alert('Error al intentar guardar los cambios: ' + error.message);
        });
    }

    // Agregar estos nuevos métodos para manejar la eliminación
    // Asegurar que la instancia global se cree si no existe (redundancia segura)
  

    // Métodos para manejar la eliminación de elementos
    mostrarModalConfirmarEliminar() {
        console.log('Ejecutando mostrarModalConfirmarEliminar()');
        
        if (!this.elementoSeleccionadoParaEdicion) {
            console.error('No hay elemento seleccionado para eliminar');
            return;
        }

        console.log('Elemento seleccionado:', this.elementoSeleccionadoParaEdicion.dataset.id);

        const modal = document.getElementById('modal-confirmar-eliminar');
        const mensaje = document.getElementById('mensaje-confirmar-eliminar');
        const btnCerrarModal = document.getElementById('btn-cerrar-modal-eliminar');
        const btnCancelar = document.getElementById('btn-cancelar-eliminar');
        const btnEliminar = document.getElementById('btn-confirmar-eliminar');
        
        console.log('Elementos del modal encontrados:', {
            modal: !!modal,
            mensaje: !!mensaje,
            btnCerrarModal: !!btnCerrarModal,
            btnCancelar: !!btnCancelar,
            btnEliminar: !!btnEliminar
        });
        
        if (!modal || !mensaje || !btnCerrarModal || !btnCancelar || !btnEliminar) {
            console.error('Faltan elementos del modal de confirmación');
            return;
        }

        const dataId = this.elementoSeleccionadoParaEdicion.dataset.id;
        const esAreaPadre = !this.elementoSeleccionadoParaEdicion.dataset.parentId;

        // Personalizar mensaje según si es área o subdivisión
        if (esAreaPadre) {
            const subdivisiones = document.querySelectorAll(`[data-parent-id="${dataId}"]`);
            mensaje.textContent = `¿Está seguro que desea eliminar esta área? Se eliminarán también ${subdivisiones.length} subdivisiones asociadas.`;
        } else {
            mensaje.textContent = '¿Está seguro que desea eliminar esta subdivisión?';
        }

        // Configurar eventos del modal (solo una vez)
        if (!btnCerrarModal.hasListener) {
            btnCerrarModal.addEventListener('click', () => this.cerrarModalConfirmarEliminar());
            btnCerrarModal.hasListener = true;
        }

        if (!btnCancelar.hasListener) {
            btnCancelar.addEventListener('click', () => this.cerrarModalConfirmarEliminar());
            btnCancelar.hasListener = true;
        }

        if (!btnEliminar.hasListener) {
            btnEliminar.addEventListener('click', () => this.confirmarEliminar());
            btnEliminar.hasListener = true;
        }

        // Eliminar cualquier CSS que pueda estar ocultando el modal
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        
        console.log('Modal mostrado correctamente');
    }

    cerrarModalConfirmarEliminar() {
        const modal = document.getElementById('modal-confirmar-eliminar');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    async confirmarEliminar() {
        if (!this.elementoSeleccionadoParaEdicion) {
            console.error('No hay elemento seleccionado para eliminar');
            this.cerrarModalConfirmarEliminar();
            return;
        }

        const elementId = this.elementoSeleccionadoParaEdicion.dataset.id;
        const esAreaPadre = !this.elementoSeleccionadoParaEdicion.dataset.parentId;

        try {
            // Si el elemento es nuevo (no guardado en BD), solo eliminarlo localmente
            if (this.cambiosPendientes.has(elementId) && this.cambiosPendientes.get(elementId).esNuevo) {
                console.log(`Eliminando elemento nuevo no guardado: ${elementId}`);
                this.eliminarElementoLocal(elementId);
                this.cerrarModalConfirmarEliminar(); // Cerrar modal antes de recargar
                // Recargar después de eliminar localmente un elemento nuevo
                location.reload(); 
                return; // Importante salir aquí para no continuar
            }

            // Eliminar de la base de datos
            const formData = new FormData();
            formData.append('accion', 'eliminar_elemento');
            formData.append('elemento_id', elementId);
            formData.append('es_area', esAreaPadre ? '1' : '0');

            const response = await fetch('../ajax/mapa_interactivo_ajax.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.exito) {
                // No es necesario llamar a eliminarElementoLocal aquí si la página se va a recargar
                // this.eliminarElementoLocal(elementId);
                alert(data.mensaje || 'Elemento eliminado correctamente');
                // Recargar la página después de eliminar exitosamente de la BD
                location.reload(); 
            } else {
                throw new Error(data.mensaje || 'Error al eliminar el elemento');
            }
        } catch (error) {
            console.error('Error al eliminar:', error);
            alert('Error al eliminar el elemento: ' + error.message);
            // Cerrar el modal solo si hubo un error
            this.cerrarModalConfirmarEliminar(); 
        } finally {
            // El modal se cierra en el bloque catch en caso de error, o la página se recarga en caso de éxito
            // Por lo tanto, no se necesita cerrar aquí explícitamente en todos los casos.
            // this.cerrarModalConfirmarEliminar();
        }
    }

    eliminarElementoLocal(elementId) {
        const elemento = this.elementoSeleccionadoParaEdicion;
        const esAreaPadre = !elemento.dataset.parentId;

        // Si es área padre, eliminar también todas las subdivisiones
        if (esAreaPadre) {
            const subdivisiones = document.querySelectorAll(`[data-parent-id="${elementId}"]`);
            subdivisiones.forEach(sub => {
                const svgElement = this.editableElements.get(sub.dataset.id);
                if (svgElement) {
                    svgElement.remove();
                    this.editableElements.delete(sub.dataset.id);
                    this.cambiosPendientes.delete(sub.dataset.id);
                }
            });
        }

        // Eliminar el elemento principal
        const svgElement = this.editableElements.get(elementId);
        if (svgElement) {
            svgElement.remove();
            this.editableElements.delete(elementId);
            this.cambiosPendientes.delete(elementId);
        }

        // Limpiar selección
        this.elementoSeleccionadoParaEdicion = null;
        document.getElementById('planta_id').value = '';
        document.getElementById('nombre_planta').value = '';
        document.getElementById('tipo_planta').value = '';
        
        // Ocultar botón eliminar
        const btnEliminar = document.getElementById('btn-eliminar-elemento');
        if (btnEliminar) {
            btnEliminar.style.display = 'none';
        }
        
        // Mostrar botón agregar área
        const btnAgregarArea = document.getElementById('btn-agregar-area');
        if (btnAgregarArea) {
            btnAgregarArea.style.display = 'inline-flex';
        }

        this.actualizarVisibilidadBotonGuardarEdicion();
    }

    // Método público para verificar si estamos en modo edición
    estaEnModoEdicion() {
        return this.editMode;
    }

    // Registrar el cambio en el nombre para guardarlo más tarde
    registrarCambioNombre(elementoId, nuevoNombre) {
        if (!elementoId || !nuevoNombre) return;
        
        // Añadir o actualizar en cambiosPendientes
        const cambioExistente = this.cambiosPendientes.get(elementoId) || {};
        cambioExistente.nombre = nuevoNombre;
        this.cambiosPendientes.set(elementoId, cambioExistente);
        
        console.log(`Cambio de nombre registrado para ${elementoId}: ${nuevoNombre}`);
        this.actualizarVisibilidadBotonGuardarEdicion();
    }
    
    // Registrar el cambio en el tipo para guardarlo más tarde
    registrarCambioTipo(elementoId, nuevoTipoId) {
        if (!elementoId) return;
        
        // Añadir o actualizar en cambiosPendientes
        const cambioExistente = this.cambiosPendientes.get(elementoId) || {};
        cambioExistente.tipo_id = nuevoTipoId;
        this.cambiosPendientes.set(elementoId, cambioExistente);
        
        console.log(`Cambio de tipo registrado para ${elementoId}: ${nuevoTipoId}`);
        this.actualizarVisibilidadBotonGuardarEdicion();
    }

    /**
     * Carga datos completos de una subdivisión desde el servidor para el modo edición
     */
    cargarDatosCompletosSubdivision(dataId) {
        if (!dataId) return;
        
        const formData = new FormData();
        formData.append('accion', 'obtener_subdivision');
        formData.append('data_id', dataId);
        
        console.log(`Cargando datos completos para subdivisión en modo edición: ${dataId}`);
        
        fetch('../ajax/mapa_interactivo_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log("Respuesta del servidor en modo edición:", data);
            
            if (data.exito && data.datos) {
                const subdivision = data.datos;
                const tipoSelect = document.getElementById('tipo_planta');
                const nombreInput = document.getElementById('nombre_planta');
                const panelEdicion = document.getElementById('panel-edicion-planta');
                const btnEliminar = document.getElementById('btn-eliminar-elemento');
                
                // Solo continuar si realmente estamos en modo edición para evitar conflictos
                if (!this.editMode) {
                    console.log("Ya no estamos en modo edición, cancelando actualización");
                    return;
                }
                
                // Mostrar el panel de edición
                if (panelEdicion) {
                    panelEdicion.style.display = 'block';
                }

                // Mostrar el botón de eliminar
                if (btnEliminar) {
                    btnEliminar.style.display = 'inline-block';
                    btnEliminar.style.visibility = 'visible';
                    btnEliminar.style.opacity = '1';
                    
                    // Asignar el evento click si no lo tiene
                    if (!btnEliminar.hasListener) {
                        btnEliminar.addEventListener('click', () => {
                            console.log('Botón eliminar clickeado');
                            this.mostrarModalConfirmarEliminar();
                        });
                        btnEliminar.hasListener = true;
                    }
                }

                // Actualizar nombre y tipo
                if (nombreInput && subdivision.nombre) {
                    nombreInput.value = subdivision.nombre;
                    nombreInput.readOnly = false;
                }
                
                if (tipoSelect) {
                    // Limpiar opciones inactivas añadidas dinámicamente
                    tipoSelect.querySelectorAll('option[data-dynamically-added="inactive"]').forEach(opt => opt.remove());
                    
                    const tipoIdSubdivision = subdivision.tipo_id !== undefined && subdivision.tipo_id !== null ? 
                        subdivision.tipo_id.toString() : '';
                    const tipoActivo = subdivision.tipo_activo !== undefined ? 
                        subdivision.tipo_activo : true;
                    const tipoNombre = subdivision.tipo_nombre || 'Desconocido';
                    
                    // Si el tipo actual está inactivo, añadir una opción temporal
                    if (!tipoActivo && tipoIdSubdivision !== '') {
                        let opcionActivaExiste = false;
                        // Verificar si ya existe una opción activa con este ID
                        for (let i = 0; i < tipoSelect.options.length; i++) {
                            if (!tipoSelect.options[i].dataset.dynamicallyAdded && 
                                tipoSelect.options[i].value === tipoIdSubdivision) {
                                opcionActivaExiste = true;
                                break;
                            }
                        }
                        
                        // Si no existe una opción activa con este ID, añadimos la versión inactiva
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
                    
                    // Intentar seleccionar el tipo de la subdivisión
                    tipoSelect.value = tipoIdSubdivision;
                    tipoSelect.disabled = false;
                    
                    // Si después de seleccionar, el valor no coincide
                    if (tipoSelect.value !== tipoIdSubdivision) {
                        if (!tipoActivo && tipoIdSubdivision !== '') {
                            console.warn(`El tipo ${tipoNombre} está inactivo y no se puede seleccionar directamente.`);
                        } else if (tipoIdSubdivision !== '') {
                            console.warn(`No se pudo seleccionar el tipo ID ${tipoIdSubdivision}. ¿Existe en la lista de tipos activos?`);
                            tipoSelect.value = '';
                        }
                    }
                }
            } else {
                console.warn(`No se pudieron cargar los datos para: ${dataId}`);
                // Habilitar de todas formas el selector
                const tipoSelect = document.getElementById('tipo_planta');
                if (tipoSelect) {
                    tipoSelect.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error(`Error al cargar datos: ${error}`);
            // En caso de error, habilitar el selector
            const tipoSelect = document.getElementById('tipo_planta');
            if (tipoSelect) {
                tipoSelect.disabled = false;
            }
        });
    }
    
    /**
     * Obtiene datos resumidos para la vista previa de una subdivisión.
     */
    desactivarEventosSeleccionNormal() {
        const elementosDOM = document.querySelectorAll('.area-interactiva');
        elementosDOM.forEach(elementoDOM => {
            // Remover el listener de selección normal si existe
            if (elementoDOM.clickHandlerNormal) {
                elementoDOM.removeEventListener('click', elementoDOM.clickHandlerNormal);
                delete elementoDOM.clickHandlerNormal;
            }
        });
        console.log("Eventos de selección normal desactivados");
    }
} 

// Asegurar que la instancia global se cree si no existe (redundancia segura)
if (!window.mapaInteractivo) {
    console.log("(Fallback) Inicializando MapaInteractivo desde el final del script...");
    window.mapaInteractivo = new MapaInteractivo();
}

/**
 * Carga y muestra los reportes de un área o subdivisión
 * @param {string} dataId El data_id del área o subdivisión
 */
async function cargarReportesArea(dataId) {
    console.log("Cargando reportes para el área/subdivisión con data_id:", dataId);
    
    try {
        const formData = new FormData();
        formData.append('accion', 'obtener_reportes');
        formData.append('data_id', dataId);
        
        const response = await fetch('../ajax/mapa_interactivo_ajax.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        console.log("Datos de reportes recibidos:", data);
        
        if (data.exito) {
            mostrarModalReportes(data.datos);
        } else {
            console.error("Error al obtener reportes:", data.mensaje);
            alert("Error al obtener reportes: " + data.mensaje);
        }
    } catch (error) {
        console.error("Error al cargar reportes:", error);
        alert("Error al cargar reportes: " + error.message);
    }
}

/**
 * Muestra el modal con los reportes
 * @param {Object} datos Los datos de reportes y área recibidos del servidor
 */
function mostrarModalReportes(datos) {
    // Buscar el modal existente o crearlo si no existe
    let modal = document.getElementById('modalReportes');
    
    if (!modal) {
        // Crear modal si no existe
        modal = document.createElement('div');
        modal.id = 'modalReportes';
        modal.className = 'modal';
        document.body.appendChild(modal);
    }
    
    // Información del área
    const areaInfo = datos.area || { nombre: 'Área desconocida', tipo: 'Desconocido' };
    
    // Reportes encontrados
    const reportes = datos.reportes || [];
    
    // Generar HTML para los reportes
    let reportesHTML = '';
    if (reportes.length > 0) {
        reportesHTML = `
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Estado</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        reportes.forEach(reporte => {
            const fecha = new Date(reporte.fecha_creacion).toLocaleDateString();
            reportesHTML += `
                <tr>
                    <td>${reporte.id}</td>
                    <td>${reporte.titulo}</td>
                    <td>${reporte.estado_nombre || 'Sin estado'}</td>
                    <td>${reporte.tipo_nombre || 'Sin tipo'}</td>
                    <td>${fecha}</td>
                    <td>
                        <button class="btn btn-sm btn-info" onclick="verDetalleReporte(${reporte.id})">
                            <i class="fas fa-eye"></i> Ver
                        </button>
                    </td>
                </tr>
            `;
        });
        
        reportesHTML += `
                </tbody>
            </table>
        `;
    } else {
        reportesHTML = '<p class="text-center">No hay reportes registrados para esta área.</p>';
    }
    
    // Contenido del modal
    modal.innerHTML = `
        <div class="modal-contenido">
            <div class="modal-encabezado">
                <h2>Reportes de ${areaInfo.nombre}</h2>
                <button class="btn-cerrar" onclick="cerrarModalReportes()">&times;</button>
            </div>
            <div class="modal-cuerpo">
                <div class="info-area">
                    <p><strong>Área:</strong> ${areaInfo.nombre}</p>
                    <p><strong>Tipo:</strong> ${areaInfo.tipo}</p>
                </div>
                <div class="reportes-container">
                    <h3>Reportes encontrados (${reportes.length})</h3>
                    ${reportesHTML}
                </div>
                <div class="acciones-container text-center mt-4">
                    <button class="btn btn-primary" onclick="nuevoReporte('${areaInfo.id}', '${datos.area ? datos.area.area_id : ''}', '${areaInfo.nombre}')">
                        <i class="fas fa-plus"></i> Nuevo reporte
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Estilos del modal
    modal.style.display = 'flex';
    
    // Cerrar modal al hacer clic fuera
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            cerrarModalReportes();
        }
    });
}

/**
 * Cierra el modal de reportes
 */
function cerrarModalReportes() {
    const modal = document.getElementById('modalReportes');
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Redirige al usuario a la página de detalle de un reporte
 * @param {number} reporteId ID del reporte a ver
 */
function verDetalleReporte(reporteId) {
    window.location.href = `form/reportes/detalle.php?id=${reporteId}`;
}

/**
 * Redirige al usuario a la página para crear un nuevo reporte
 * @param {string} areaId ID del área
 * @param {string} areaPadreId ID del área padre (si es una subdivisión)
 * @param {string} areaNombre Nombre del área
 */
function nuevoReporte(areaId, areaPadreId, areaNombre) {
    const url = `form/reportes/crear.php?area_id=${areaId}${areaPadreId ? '&area_padre_id=' + areaPadreId : ''}&area_nombre=${encodeURIComponent(areaNombre)}`;
    window.location.href = url;
}

// Añadir estilos CSS para el modal de reportes al inicio del archivo o después de la declaración de la clase MapaInteractivo
document.addEventListener('DOMContentLoaded', function() {
    // Añadir estilos para el modal de reportes
    const modalStyles = document.createElement('style');
    modalStyles.textContent = `
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-contenido {
            background-color: white;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .modal-encabezado {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-encabezado h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #212529;
        }
        
        .btn-cerrar {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6c757d;
        }
        
        .modal-cuerpo {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }
        
        .info-area {
            background-color: #e9ecef;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .info-area p {
            margin: 5px 0;
        }
        
        .reportes-container {
            margin-top: 20px;
        }
        
        .reportes-container h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }
        
        .btn-info {
            color: #fff;
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        
        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .text-center {
            text-align: center;
        }
        
        .mt-4 {
            margin-top: 1.5rem;
        }
    `;
    document.head.appendChild(modalStyles);
});