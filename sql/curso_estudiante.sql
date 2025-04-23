-- Crear tabla de relación entre cursos y estudiantes
CREATE TABLE IF NOT EXISTS public.curso_estudiante (
    curso_id INTEGER NOT NULL,
    usuario_id INTEGER NOT NULL,
    fecha_inscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado SMALLINT DEFAULT 1,
    CONSTRAINT pk_curso_estudiante PRIMARY KEY (curso_id, usuario_id),
    CONSTRAINT fk_curso_estudiante_curso FOREIGN KEY (curso_id) REFERENCES public.cursos(id) ON DELETE CASCADE,
    CONSTRAINT fk_curso_estudiante_usuario FOREIGN KEY (usuario_id) REFERENCES public.usuarios(id) ON DELETE CASCADE
);

-- Crear índices para mejorar el rendimiento
CREATE INDEX IF NOT EXISTS idx_curso_estudiante_curso_id ON public.curso_estudiante(curso_id);
CREATE INDEX IF NOT EXISTS idx_curso_estudiante_usuario_id ON public.curso_estudiante(usuario_id); 