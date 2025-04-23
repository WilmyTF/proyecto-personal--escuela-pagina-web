-- Tabla para asignar docentes a cursos
CREATE TABLE IF NOT EXISTS public.docente_curso (
    id SERIAL PRIMARY KEY,
    docente_id INTEGER NOT NULL,
    curso_id INTEGER NOT NULL,
    periodo_academico VARCHAR(10) NOT NULL, -- ejemplo: "2025-1"
    FOREIGN KEY (docente_id) REFERENCES public.docentes(id),
    FOREIGN KEY (curso_id) REFERENCES public.cursos(id),
    UNIQUE(docente_id, curso_id, periodo_academico)
);

-- Tabla de horarios
CREATE TABLE IF NOT EXISTS public.horarios (
    id SERIAL PRIMARY KEY,
    docente_curso_id INTEGER NOT NULL,
    dia_semana INTEGER NOT NULL CHECK (dia_semana BETWEEN 1 AND 7), -- 1: Lunes, 7: Domingo
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    periodo_academico VARCHAR(10) NOT NULL, -- ejemplo: "2025-1"
    estado VARCHAR(20) DEFAULT 'activo' CHECK (estado IN ('activo', 'inactivo')),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (docente_curso_id) REFERENCES public.docente_curso(id),
    -- Restricción para evitar solapamientos de horarios para un docente y curso
    CONSTRAINT no_solapamiento_horario UNIQUE (docente_curso_id, dia_semana, hora_inicio, hora_fin),
    -- Asegurar que hora_fin sea posterior a hora_inicio
    CONSTRAINT hora_valida CHECK (hora_fin > hora_inicio)
);

-- Índices para mejorar el rendimiento de las consultas
CREATE INDEX idx_horarios_periodo ON public.horarios(periodo_academico);
CREATE INDEX idx_horarios_docente_curso ON public.horarios(docente_curso_id);
CREATE INDEX idx_docente_curso_periodo ON public.docente_curso(periodo_academico);

-- Inserciones de prueba

-- 1. Insertar relación docente-curso
INSERT INTO public.docente_curso (docente_id, curso_id, periodo_academico)
VALUES 
    (1, 1, '2025-1'); -- Docente "prueba docente" con el curso "test"

-- 2. Insertar horarios de prueba
INSERT INTO public.horarios 
    (docente_curso_id, dia_semana, hora_inicio, hora_fin, periodo_academico)
VALUES
    -- Horarios para el curso de test
    ((SELECT id FROM public.docente_curso WHERE docente_id = 1 AND curso_id = 1 AND periodo_academico = '2025-1'),
    1, -- Lunes
    '08:00:00', -- Hora inicio
    '10:00:00', -- Hora fin
    '2025-1'),

    ((SELECT id FROM public.docente_curso WHERE docente_id = 1 AND curso_id = 1 AND periodo_academico = '2025-1'),
    3, -- Miércoles
    '08:00:00', -- Hora inicio
    '10:00:00', -- Hora fin
    '2025-1'); 