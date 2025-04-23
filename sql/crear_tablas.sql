-- Crear tabla de docentes si no existe
CREATE TABLE IF NOT EXISTS docentes (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla de aulas si no existe
CREATE TABLE IF NOT EXISTS aulas (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    capacidad INTEGER NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla de cursos si no existe
CREATE TABLE IF NOT EXISTS cursos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    docente_id INTEGER REFERENCES docentes(id),
    cupo_maximo INTEGER NOT NULL,
    aula_id INTEGER REFERENCES aulas(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar algunos datos de prueba
INSERT INTO docentes (nombre, apellido, email) VALUES
    ('Juan', 'Pérez', 'juan.perez@email.com'),
    ('María', 'García', 'maria.garcia@email.com')
ON CONFLICT (email) DO NOTHING;

INSERT INTO aulas (nombre, capacidad, tipo) VALUES
    ('Aula 101', 30, 'aula'),
    ('Laboratorio 1', 20, 'laboratorio')
ON CONFLICT DO NOTHING;

-- Los IDs pueden variar, así que obtenemos los IDs reales para los inserts
DO $$
DECLARE
    docente_id1 INTEGER;
    docente_id2 INTEGER;
    aula_id1 INTEGER;
    aula_id2 INTEGER;
BEGIN
    SELECT id INTO docente_id1 FROM docentes WHERE email = 'juan.perez@email.com';
    SELECT id INTO docente_id2 FROM docentes WHERE email = 'maria.garcia@email.com';
    SELECT id INTO aula_id1 FROM aulas WHERE nombre = 'Aula 101';
    SELECT id INTO aula_id2 FROM aulas WHERE nombre = 'Laboratorio 1';

    INSERT INTO cursos (nombre, descripcion, docente_id, cupo_maximo, aula_id) VALUES
        ('Matemáticas Básicas', 'Curso introductorio de matemáticas', docente_id1, 25, aula_id1),
        ('Laboratorio de Física', 'Prácticas de física básica', docente_id2, 15, aula_id2)
    ON CONFLICT DO NOTHING;
END $$; 