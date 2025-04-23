-- Crear tipo ENUM para el estado de la solicitud
CREATE TYPE estado_solicitud AS ENUM ('pendiente', 'aprobada', 'rechazada');

-- Tabla para documentos requeridos en admisión
CREATE TABLE documentos_requerido_admision (
    id_documento_requerido SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    es_obligatorio BOOLEAN DEFAULT true,
    activo BOOLEAN DEFAULT true,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar documentos requeridos iniciales
INSERT INTO documentos_requerido_admision (nombre, descripcion, es_obligatorio) VALUES
('Acta de Nacimiento', 'Documento oficial que certifica el nacimiento', true),
('Cédula', 'Documento de identidad', true),
('Record de Notas', 'Historial académico del estudiante', true),
('Foto 2x2', 'Fotografía reciente tamaño 2x2', true),
('Certificado de Buena Conducta', 'Certificado de comportamiento de la escuela anterior', true),
('Certificado Médico', 'Certificado de salud actual', true),
('Tipificación Sanguínea', 'Documento que indica el tipo de sangre', true);

-- Tabla para las solicitudes de admisión
CREATE TABLE solicitud_admision (
    id_solicitud SERIAL PRIMARY KEY,
    -- Datos del estudiante
    nombre_estudiante VARCHAR(100) NOT NULL,
    apellido_estudiante VARCHAR(100) NOT NULL,
    direccion_estudiante TEXT NOT NULL,
    grado_cursar VARCHAR(50) NOT NULL,
    especialidad VARCHAR(50) NOT NULL,
    -- Datos del primer tutor
    nombre_tutor1 VARCHAR(100) NOT NULL,
    apellido_tutor1 VARCHAR(100) NOT NULL,
    direccion_tutor1 TEXT NOT NULL,
    telefono_tutor1 VARCHAR(20) NOT NULL,
    correo_tutor1 VARCHAR(100) NOT NULL,
    relacion_tutor1 VARCHAR(50) NOT NULL,
    -- Datos del segundo tutor (opcionales)
    nombre_tutor2 VARCHAR(100),
    apellido_tutor2 VARCHAR(100),
    direccion_tutor2 TEXT,
    telefono_tutor2 VARCHAR(20),
    correo_tutor2 VARCHAR(100),
    relacion_tutor2 VARCHAR(50),
    -- Metadatos
    estado estado_solicitud DEFAULT 'pendiente',
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla para los documentos de la solicitud
CREATE TABLE solicitud_admision_documento (
    id_documento SERIAL PRIMARY KEY,
    id_solicitud INTEGER NOT NULL,
    url_documento TEXT NOT NULL,
    fecha_carga TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_solicitud) REFERENCES solicitud_admision(id_solicitud) ON DELETE CASCADE,
    FOREIGN KEY (id_documento_requerido) REFERENCES documentos_requerido_admision(id_documento_requerido)
); 