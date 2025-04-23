-- Script para inicializar permisos y usuarios en PostgreSQL

-- Crear los permisos (sin especificar IDs, ya que es una columna GENERATED ALWAYS)
INSERT INTO "Permisos" (nombre_permiso) VALUES 
('Estudiante'),
('Profesor'),
('Empleado'),
('Administrador')
ON CONFLICT DO NOTHING;

-- Crear usuarios de prueba con contraseñas en texto plano para facilitar el trabajo
INSERT INTO usuarios (nombre, apellido, email, "contraseña", "Estado") VALUES 
('Prueba', 'Estudiante', 'estudiante@test.com', 'password123', 1),
('Prueba', 'Profesor', 'profesor@test.com', 'password123', 1),
('Prueba', 'Empleado', 'empleado@test.com', 'password123', 1)
ON CONFLICT (email) DO UPDATE SET "Estado" = 1, "contraseña" = EXCLUDED."contraseña";

-- Asignar permisos con subconsultas
-- Para el estudiante
INSERT INTO "usuario-permiso" ("id_Permisos", id_usuarios)
SELECT p.id, u.id 
FROM "Permisos" p, usuarios u 
WHERE p.nombre_permiso = 'Estudiante' AND u.email = 'estudiante@test.com'
ON CONFLICT DO NOTHING;

-- Para el profesor
INSERT INTO "usuario-permiso" ("id_Permisos", id_usuarios)
SELECT p.id, u.id 
FROM "Permisos" p, usuarios u 
WHERE p.nombre_permiso = 'Profesor' AND u.email = 'profesor@test.com'
ON CONFLICT DO NOTHING;

-- Para el empleado (permisos de empleado y administrador)
INSERT INTO "usuario-permiso" ("id_Permisos", id_usuarios)
SELECT p.id, u.id 
FROM "Permisos" p, usuarios u 
WHERE p.nombre_permiso = 'Empleado' AND u.email = 'empleado@test.com'
ON CONFLICT DO NOTHING;

INSERT INTO "usuario-permiso" ("id_Permisos", id_usuarios)
SELECT p.id, u.id 
FROM "Permisos" p, usuarios u 
WHERE p.nombre_permiso = 'Administrador' AND u.email = 'empleado@test.com'
ON CONFLICT DO NOTHING;

-- Crear registros en las tablas específicas
-- Estudiante
INSERT INTO estudiantes (usuario_id, matricula, estado)
SELECT id, 'EST-' || id, 'Activo' 
FROM usuarios 
WHERE email = 'estudiante@test.com'
ON CONFLICT (matricula) DO UPDATE SET estado = 'Activo';

-- Docente
INSERT INTO docentes (usuario_id, especialidad, estado)
SELECT id, 'Matemáticas', 'Activo' 
FROM usuarios 
WHERE email = 'profesor@test.com'
ON CONFLICT DO NOTHING;

-- Empleado
INSERT INTO empleados (usuario_id, departamento, cargo, estado)
SELECT id, 'Administración', 'Director', 1 
FROM usuarios 
WHERE email = 'empleado@test.com'
ON CONFLICT DO NOTHING;

-- Crear relación empleado-usuario
INSERT INTO empleado_usuario (id_empleados, id_usuarios)
SELECT e.id, e.usuario_id
FROM empleados e
JOIN usuarios u ON e.usuario_id = u.id
WHERE u.email = 'empleado@test.com'
ON CONFLICT DO NOTHING;

-- Crear cargos (si no existen ya)
INSERT INTO cargo (nombre, estado)
VALUES 
('Director', 1),
('Profesor', 1),
('Administrativo', 1)
ON CONFLICT DO NOTHING;

-- Asignar cargo al empleado
INSERT INTO empleado_cargo (id_cargo, id_usuarios)
SELECT (SELECT id FROM cargo WHERE nombre = 'Director'), u.id
FROM usuarios u
WHERE u.email = 'empleado@test.com'
ON CONFLICT DO NOTHING;

-- Asignar cargo al profesor
INSERT INTO empleado_cargo (id_cargo, id_usuarios)
SELECT (SELECT id FROM cargo WHERE nombre = 'Profesor'), u.id
FROM usuarios u
WHERE u.email = 'profesor@test.com'
ON CONFLICT DO NOTHING;

-- Crear un curso para el profesor
INSERT INTO cursos (nombre, descripcion, docente_id, cupo_maximo)
SELECT 'Matemáticas Básicas', 'Curso introductorio de matemáticas', d.id, 30
FROM docentes d
JOIN usuarios u ON d.usuario_id = u.id
WHERE u.email = 'profesor@test.com'
ON CONFLICT DO NOTHING;

-- Crear una asignatura para el curso
INSERT INTO asignaturas (id, nombre, descripcion, curso_id)
SELECT 'MAT101', 'Álgebra', 'Fundamentos de álgebra', c.id
FROM cursos c
JOIN docentes d ON c.docente_id = d.id
JOIN usuarios u ON d.usuario_id = u.id
WHERE u.email = 'profesor@test.com' AND c.nombre = 'Matemáticas Básicas'
ON CONFLICT DO NOTHING;

-- Crear un período académico
INSERT INTO "Periodo" ("Periodo_id", "Inicio", "Inicio_programado", "Fin", "Fin_programado")
VALUES ('2023-2024', '2023-09-01', '2023-09-01', '2024-06-30', 1)
ON CONFLICT DO NOTHING;

-- Inscribir al estudiante en el período
INSERT INTO inscripciones ("Periodo_id_Periodo", id_usuarios, curso_id, fecha_inscripcion, estado)
SELECT p."Periodo_id", u.id, c.id, CURRENT_DATE, 'Activo'
FROM "Periodo" p, usuarios u, cursos c
JOIN docentes d ON c.docente_id = d.id
JOIN usuarios ud ON d.usuario_id = ud.id
WHERE p."Periodo_id" = '2023-2024' AND u.email = 'estudiante@test.com' AND ud.email = 'profesor@test.com'
ON CONFLICT DO NOTHING;

-- Agregar una calificación para el estudiante
INSERT INTO calificaciones (matricula_estudiantes, id_asignaturas, "Periodo_id_Periodo", nota, periodo)
SELECT e.matricula, a.id, p."Periodo_id", 8.5, '1er Trimestre'
FROM estudiantes e
JOIN usuarios u ON e.usuario_id = u.id
JOIN asignaturas a ON a.id = 'MAT101'
JOIN "Periodo" p ON p."Periodo_id" = '2023-2024'
WHERE u.email = 'estudiante@test.com'
ON CONFLICT DO NOTHING; 