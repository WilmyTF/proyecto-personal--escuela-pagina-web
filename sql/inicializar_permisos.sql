-- Inicialización de permisos y usuarios para el sistema escolar
-- Creación de permisos básicos
INSERT INTO "Permisos" (nombre_permiso) VALUES 
('Estudiante'),
('Profesor'),
('Empleado'),
('Administrador')
ON CONFLICT (id) DO UPDATE SET nombre_permiso = EXCLUDED.nombre_permiso;

-- Crear usuarios de prueba
INSERT INTO usuarios (nombre, apellido, email, "contraseña", "Estado") VALUES 
('Prueba', 'Estudiante', 'estudiante@test.com', '$2y$10$mzttqUJqmE96KENgNbsSwOlC7.75FafYu9m3W8wjNDHCNCNVnshjW', 1),
('Prueba', 'Profesor', 'profesor@test.com', '$2y$10$mzttqUJqmE96KENgNbsSwOlC7.75FafYu9m3W8wjNDHCNCNVnshjW', 1),
('Prueba', 'Empleado', 'empleado@test.com', '$2y$10$mzttqUJqmE96KENgNbsSwOlC7.75FafYu9m3W8wjNDHCNCNVnshjW', 1)
ON CONFLICT (email) DO UPDATE SET "Estado" = 1;

-- Crear y asignar permisos a usuarios individuales
-- Para el estudiante
INSERT INTO "usuario-permiso" ("id_Permisos", id_usuarios)
SELECT 1, id FROM usuarios WHERE email = 'estudiante@test.com'
ON CONFLICT DO NOTHING;

-- Para el profesor
INSERT INTO "usuario-permiso" ("id_Permisos", id_usuarios)
SELECT 2, id FROM usuarios WHERE email = 'profesor@test.com'
ON CONFLICT DO NOTHING;

-- Para el empleado (con permiso de administrador)
INSERT INTO "usuario-permiso" ("id_Permisos", id_usuarios)
SELECT 3, id FROM usuarios WHERE email = 'empleado@test.com'
ON CONFLICT DO NOTHING;

INSERT INTO "usuario-permiso" ("id_Permisos", id_usuarios)
SELECT 4, id FROM usuarios WHERE email = 'empleado@test.com'
ON CONFLICT DO NOTHING;

-- Crear registro de estudiante
INSERT INTO estudiantes (usuario_id, matricula, estado)
SELECT id, 'EST-' || id, 'Activo' FROM usuarios WHERE email = 'estudiante@test.com'
ON CONFLICT (matricula) DO NOTHING;

-- Crear registro de docente
INSERT INTO docentes (usuario_id, especialidad, estado)
SELECT id, 'Matemáticas', 'Activo' FROM usuarios WHERE email = 'profesor@test.com'
ON CONFLICT DO NOTHING;

-- Crear registro de empleado
INSERT INTO empleados (usuario_id, departamento, cargo, estado)
SELECT id, 'Administración', 'Director', 1 FROM usuarios WHERE email = 'empleado@test.com'
ON CONFLICT DO NOTHING;

-- Mensaje para el usuario
SELECT 'Usuarios de prueba creados correctamente:
- Estudiante: estudiante@test.com / password123
- Profesor: profesor@test.com / password123
- Empleado/Admin: empleado@test.com / password123' AS mensaje; 