-- Script para inicializar usuarios de prueba con sus respectivos permisos
-- Creación de permisos básicos si no existen
INSERT INTO "Permisos" (id, nombre_permiso) 
VALUES 
(1, 'Estudiante'),
(2, 'Profesor'),
(3, 'Empleado'),
(4, 'Administrador')
ON CONFLICT (id) DO UPDATE SET nombre_permiso = EXCLUDED.nombre_permiso;

-- Creación de usuarios de prueba con contraseña = 'password123'
-- Contraseña hasheada: $2y$10$mzttqUJqmE96KENgNbsSwOlC7.75FafYu9m3W8wjNDHCNCNVnshjW
INSERT INTO usuarios (nombre, apellido, email, "contraseña", "Estado") 
VALUES 
('Prueba', 'Estudiante', 'estudiante@test.com', '$2y$10$mzttqUJqmE96KENgNbsSwOlC7.75FafYu9m3W8wjNDHCNCNVnshjW', 1),
('Prueba', 'Profesor', 'profesor@test.com', '$2y$10$mzttqUJqmE96KENgNbsSwOlC7.75FafYu9m3W8wjNDHCNCNVnshjW', 1),
('Prueba', 'Empleado', 'empleado@test.com', '$2y$10$mzttqUJqmE96KENgNbsSwOlC7.75FafYu9m3W8wjNDHCNCNVnshjW', 1);

-- Obtener IDs de usuarios
DO $$ 
DECLARE 
    id_estudiante INTEGER;
    id_profesor INTEGER;
    id_empleado INTEGER;
BEGIN
    -- Obtener IDs de los usuarios recién creados
    SELECT id INTO id_estudiante FROM usuarios WHERE email = 'estudiante@test.com';
    SELECT id INTO id_profesor FROM usuarios WHERE email = 'profesor@test.com';
    SELECT id INTO id_empleado FROM usuarios WHERE email = 'empleado@test.com';

    -- Asignar permisos a los usuarios
    -- Estudiante
    INSERT INTO "usuario-permiso" ("id_Permisos", id_usuarios) 
    VALUES (1, id_estudiante)
    ON CONFLICT DO NOTHING;

    -- Profesor
    INSERT INTO "usuario-permiso" ("id_Permisos", id_usuarios) 
    VALUES (2, id_profesor)
    ON CONFLICT DO NOTHING;

    -- Empleado (con permiso de administrador)
    INSERT INTO "usuario-permiso" ("id_Permisos", id_usuarios) 
    VALUES (3, id_empleado), (4, id_empleado)
    ON CONFLICT DO NOTHING;

    -- Crear registro de estudiante
    INSERT INTO estudiantes (usuario_id, matricula, estado) 
    VALUES (id_estudiante, 'EST-' || id_estudiante, 'Activo')
    ON CONFLICT (matricula) DO NOTHING;

    -- Crear registro de docente
    INSERT INTO docentes (usuario_id, especialidad, estado) 
    VALUES (id_profesor, 'Matemáticas', 'Activo')
    ON CONFLICT DO NOTHING;

    -- Crear registro de empleado
    INSERT INTO empleados (usuario_id, departamento, cargo, estado) 
    VALUES (id_empleado, 'Administración', 'Director', 1)
    ON CONFLICT DO NOTHING;

END $$;

-- Mensaje informativo
SELECT 'Usuarios de prueba creados correctamente:
- Estudiante: estudiante@test.com / password123
- Profesor: profesor@test.com / password123
- Empleado/Admin: empleado@test.com / password123' AS mensaje; 