cREATE TABLE IF NOT EXISTS estados_reporte (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    color VARCHAR(7) -- Para almacenar códigos de color hex (ej: #FF0000)
);

-- Tabla para los tipos de reporte
CREATE TABLE IF NOT EXISTS tipos_reporte (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT
);

-- Tabla principal de reportes
CREATE TABLE IF NOT EXISTS reportes (
    id VARCHAR(20) PRIMARY KEY, -- ID generado por la aplicación
    fecha_creacion TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    tipo_id INTEGER NOT NULL REFERENCES tipos_reporte(id),
    estado_id INTEGER NOT NULL REFERENCES estados_reporte(id),
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    usuario_id INTEGER NOT NULL, -- Referencia al usuario que creó el reporte
    fecha_actualizacion TIMESTAMP WITH TIME ZONE,
    area_id INTEGER, -- Referencia a public.subdivisiones_area
    data_id VARCHAR(50) -- Almacena el data_id del área seleccionada
);

-- Tabla para imágenes de los reportes
CREATE TABLE IF NOT EXISTS imagenes_reporte (
    id SERIAL PRIMARY KEY,
    reporte_id VARCHAR(20) NOT NULL REFERENCES reportes(id) ON DELETE CASCADE,
    ruta_imagen text,
    fecha_subida TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para el historial de cambios en los reportes
CREATE TABLE IF NOT EXISTS historial_reportes (
    id SERIAL PRIMARY KEY,
    reporte_id VARCHAR(20) NOT NULL REFERENCES reportes(id) ON DELETE CASCADE,
    usuario_id INTEGER NOT NULL, -- Usuario que hizo el cambio
    fecha_cambio TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    estado_anterior INTEGER REFERENCES estados_reporte(id),
    estado_nuevo INTEGER REFERENCES estados_reporte(id),
    comentario TEXT
);

-- Tabla para comentarios en los reportes
CREATE TABLE IF NOT EXISTS comentarios_reporte (
    id SERIAL PRIMARY KEY,
    reporte_id VARCHAR(20) NOT NULL REFERENCES reportes(id) ON DELETE CASCADE,
    usuario_id INTEGER NOT NULL,
    comentario TEXT NOT NULL,
    fecha_comentario TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para etiquetas de reportes
CREATE TABLE IF NOT EXISTS etiquetas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(7) -- Código de color hex
);

-- Tabla de relación entre reportes y etiquetas (muchos a muchos)
CREATE TABLE IF NOT EXISTS reportes_etiquetas (
    reporte_id VARCHAR(20) NOT NULL REFERENCES reportes(id) ON DELETE CASCADE,
    etiqueta_id INTEGER NOT NULL REFERENCES etiquetas(id) ON DELETE CASCADE,
    PRIMARY KEY (reporte_id, etiqueta_id)
);

-- Insertar estados de reporte predefinidos
INSERT INTO estados_reporte (nombre, descripcion, color) VALUES
('Nuevo', 'Reporte recién creado', '#3498db'),
('En revisión', 'El reporte está siendo analizado', '#f39c12'),
('En proceso', 'Se está trabajando en el reporte', '#2ecc71'),
('Pendiente', 'En espera de información adicional', '#e74c3c'),
('Resuelto', 'El problema ha sido resuelto', '#27ae60'),
('Cerrado', 'Reporte finalizado', '#7f8c8d')
ON CONFLICT (nombre) DO NOTHING;

-- Insertar tipos de reporte predefinidos
INSERT INTO tipos_reporte (nombre, descripcion) VALUES
('Incidente', 'Problema que afecta operaciones normales'),
('Solicitud', 'Petición de servicio o información'),
('Mejora', 'Sugerencia para mejorar procesos o sistemas'),
('Mantenimiento', 'Tareas de mantenimiento preventivo o correctivo'),
('Auditoría', 'Revisión de cumplimiento de procesos')
ON CONFLICT (nombre) DO NOTHING;

-- Índices para mejorar el rendimiento
CREATE INDEX IF NOT EXISTS idx_reportes_tipo ON reportes(tipo_id);
CREATE INDEX IF NOT EXISTS idx_reportes_estado ON reportes(estado_id);
CREATE INDEX IF NOT EXISTS idx_reportes_fecha ON reportes(fecha_creacion);
CREATE INDEX IF NOT EXISTS idx_reportes_area ON reportes(area_id);
CREATE INDEX IF NOT EXISTS idx_reportes_area_data ON reportes(area_id, data_id);
CREATE INDEX IF NOT EXISTS idx_imagenes_reporte ON imagenes_reporte(reporte_id);
CREATE INDEX IF NOT EXISTS idx_historial_reporte ON historial_reportes(reporte_id);
CREATE INDEX IF NOT EXISTS idx_comentarios_reporte ON comentarios_reporte(reporte_id);

-- Función para actualizar la fecha de actualización automáticamente
CREATE OR REPLACE FUNCTION update_fecha_actualizacion()
RETURNS TRIGGER AS $$
BEGIN
    NEW.fecha_actualizacion = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger para actualizar la fecha de actualización
CREATE TRIGGER trigger_update_fecha_actualizacion
BEFORE UPDATE ON reportes
FOR EACH ROW
EXECUTE FUNCTION update_fecha_actualizacion();

-- Función para registrar cambios de estado en el historial
CREATE OR REPLACE FUNCTION registrar_cambio_estado()
RETURNS TRIGGER AS $$
BEGIN
    IF OLD.estado_id <> NEW.estado_id THEN
        INSERT INTO historial_reportes (reporte_id, usuario_id, estado_anterior, estado_nuevo, comentario)
        VALUES (NEW.id, NEW.usuario_id, OLD.estado_id, NEW.estado_id, 'Cambio de estado automático');
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger para registrar cambios de estado
CREATE TRIGGER trigger_registrar_cambio_estado
AFTER UPDATE OF estado_id ON reportes
FOR EACH ROW
EXECUTE FUNCTION registrar_cambio_estado();

-- Comentarios sobre el funcionamiento del sistema de reportes:
/*
1. ID de reportes: Se generará por la aplicación, no secuencialmente por la base de datos.
2. Estados: Los reportes pueden pasar por diferentes estados predefinidos.
3. Tipos: Clasificación de reportes según su naturaleza.
4. Imágenes: Cada reporte puede tener múltiples imágenes adjuntas.
5. Historial: Se registra automáticamente cada cambio de estado.
6. Comentarios: Los usuarios pueden añadir comentarios a los reportes.
7. Etiquetas: Permiten categorizar los reportes de manera flexible.
8. Triggers: Actualizan fechas y registran cambios automáticamente.
9. Áreas: Los reportes pueden asociarse opcionalmente a un área específica de la tabla subdivisiones_area.
*/