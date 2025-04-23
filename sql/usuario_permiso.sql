-- Crear tabla de permisos si no existe
CREATE TABLE IF NOT EXISTS public.permisos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla de relación usuario-permiso si no existe
CREATE TABLE IF NOT EXISTS public.usuario_permiso (
    id_usuarios INTEGER NOT NULL,
    id_permisos INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_usuario_permiso PRIMARY KEY (id_usuarios, id_permisos),
    CONSTRAINT fk_usuario_permiso_usuario FOREIGN KEY (id_usuarios) REFERENCES public.usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_usuario_permiso_permiso FOREIGN KEY (id_permisos) REFERENCES public.permisos(id) ON DELETE CASCADE
);

-- Insertar permisos básicos si no existen
INSERT INTO public.permisos (id, nombre, descripcion)
VALUES 
    (1, 'Estudiante', 'Permiso para estudiantes'),
    (2, 'Profesor', 'Permiso para profesores'),
    (3, 'Empleado', 'Permiso para empleados'),
    (4, 'Administrador', 'Permiso para administradores')
ON CONFLICT (id) DO NOTHING;

-- Crear índices para mejorar el rendimiento
CREATE INDEX IF NOT EXISTS idx_usuario_permiso_usuario_id ON public.usuario_permiso(id_usuarios);
CREATE INDEX IF NOT EXISTS idx_usuario_permiso_permiso_id ON public.usuario_permiso(id_permisos); 