CREATE SEQUENCE IF NOT EXISTS public.mapas_id_seq
    INCREMENT BY 1
    MINVALUE 1
    MAXVALUE 2147483647
    START WITH 1
    CACHE 1
    NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.areas_mapa_id_seq
    INCREMENT BY 1
    MINVALUE 1
    MAXVALUE 2147483647
    START WITH 1
    CACHE 1
    NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.subdivisiones_area_id_seq
    INCREMENT BY 1
    MINVALUE 1
    MAXVALUE 2147483647
    START WITH 1
    CACHE 1
    NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.responsables_area_id_seq
    INCREMENT BY 1
    MINVALUE 1
    MAXVALUE 2147483647
    START WITH 1
    CACHE 1
    NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.personal_area_id_seq
    INCREMENT BY 1
    MINVALUE 1
    MAXVALUE 2147483647
    START WITH 1
    CACHE 1
    NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.tipos_area_id_seq
    INCREMENT BY 1
    MINVALUE 1
    MAXVALUE 2147483647
    START WITH 1
    CACHE 1
    NO CYCLE;

-- Tabla para tipos de áreas
CREATE TABLE public.tipos_area (
    id integer NOT NULL DEFAULT nextval('public.tipos_area_id_seq'::regclass),
    nombre character varying(50) NOT NULL,
    descripcion text,
    activo boolean DEFAULT true,
    fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT tipos_area_pkey PRIMARY KEY (id),
    CONSTRAINT tipos_area_nombre_unique UNIQUE (nombre)
);
ALTER TABLE public.tipos_area OWNER TO postgres;

-- Tabla para mapas
CREATE TABLE public.mapas (
    id integer NOT NULL DEFAULT nextval('public.mapas_id_seq'::regclass),
    nombre character varying(100) NOT NULL,
    descripcion text,
    imagen_url character varying(255),
    fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT mapas_pkey PRIMARY KEY (id)
);
ALTER TABLE public.mapas OWNER TO postgres;

-- Tabla para áreas del mapa
CREATE TABLE public.areas_mapa (
    id integer NOT NULL DEFAULT nextval('public.areas_mapa_id_seq'::regclass),
    mapa_id integer,
    nombre character varying(100) NOT NULL,
    tipo_id integer,
    color character varying(20),
    aula_id integer,
    data_id character varying(50),
    CONSTRAINT areas_mapa_pkey PRIMARY KEY (id)
);
ALTER TABLE public.areas_mapa OWNER TO postgres;

-- Tabla para subdivisiones de áreas
CREATE TABLE public.subdivisiones_area (
    id integer NOT NULL DEFAULT nextval('public.subdivisiones_area_id_seq'::regclass),
    area_id integer,
    nombre character varying(100) NOT NULL,
    tipo_id integer,
    svg_id character varying(100),
    aula_id integer,
    data_id character varying(50),
    color character varying(20),
    path_data text,
    CONSTRAINT subdivisiones_area_pkey PRIMARY KEY (id)
);
ALTER TABLE public.subdivisiones_area OWNER TO postgres;

-- Tabla para responsables de áreas
CREATE TABLE public.responsables_area (
    id integer NOT NULL DEFAULT nextval('public.responsables_area_id_seq'::regclass),
    area_id integer,
    usuario_id integer,
    cargo character varying(100),
    fecha_asignacion timestamp DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT responsables_area_pkey PRIMARY KEY (id)
);
ALTER TABLE public.responsables_area OWNER TO postgres;

-- Tabla para personal asignado a áreas
CREATE TABLE public.personal_area (
    id integer NOT NULL DEFAULT nextval('public.personal_area_id_seq'::regclass),
    area_id integer,
    usuario_id integer,
    cargo character varying(100),
    fecha_asignacion timestamp DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT personal_area_pkey PRIMARY KEY (id)
);
ALTER TABLE public.personal_area OWNER TO postgres;

-- Modificar la tabla aulas para añadir referencia a área_mapa
ALTER TABLE public.aulas ADD COLUMN area_mapa_id integer;

-- Añadir restricciones de clave foránea
ALTER TABLE public.areas_mapa ADD CONSTRAINT areas_mapa_mapa_id_fkey 
    FOREIGN KEY (mapa_id) REFERENCES public.mapas (id) ON DELETE CASCADE;

ALTER TABLE public.areas_mapa ADD CONSTRAINT areas_mapa_aula_id_fkey 
    FOREIGN KEY (aula_id) REFERENCES public.aulas (id) ON DELETE SET NULL;

ALTER TABLE public.areas_mapa ADD CONSTRAINT areas_mapa_tipo_id_fkey 
    FOREIGN KEY (tipo_id) REFERENCES public.tipos_area (id) ON DELETE SET NULL;

ALTER TABLE public.subdivisiones_area ADD CONSTRAINT subdivisiones_area_area_id_fkey 
    FOREIGN KEY (area_id) REFERENCES public.areas_mapa (id) ON DELETE CASCADE;

ALTER TABLE public.subdivisiones_area ADD CONSTRAINT subdivisiones_area_aula_id_fkey 
    FOREIGN KEY (aula_id) REFERENCES public.aulas (id) ON DELETE SET NULL;

ALTER TABLE public.subdivisiones_area ADD CONSTRAINT subdivisiones_area_tipo_id_fkey 
    FOREIGN KEY (tipo_id) REFERENCES public.tipos_area (id) ON DELETE SET NULL;

ALTER TABLE public.responsables_area ADD CONSTRAINT responsables_area_area_id_fkey 
    FOREIGN KEY (area_id) REFERENCES public.areas_mapa (id) ON DELETE CASCADE;

ALTER TABLE public.responsables_area ADD CONSTRAINT responsables_area_usuario_id_fkey 
    FOREIGN KEY (usuario_id) REFERENCES public.usuarios (id) ON DELETE CASCADE;

ALTER TABLE public.personal_area ADD CONSTRAINT personal_area_area_id_fkey 
    FOREIGN KEY (area_id) REFERENCES public.areas_mapa (id) ON DELETE CASCADE;

ALTER TABLE public.personal_area ADD CONSTRAINT personal_area_usuario_id_fkey 
    FOREIGN KEY (usuario_id) REFERENCES public.usuarios (id) ON DELETE CASCADE;

-- Añadir restricción de clave foránea a la tabla aulas
ALTER TABLE public.aulas ADD CONSTRAINT aulas_area_mapa_id_fkey 
    FOREIGN KEY (area_mapa_id) REFERENCES public.areas_mapa (id) ON DELETE SET NULL;

-- Insertar tipos de área predeterminados
INSERT INTO public.tipos_area (nombre, descripcion, activo) 
VALUES 
('aula', 'Espacio para clases y actividades académicas', true),
('oficina', 'Espacio para trabajo administrativo', true),
('laboratorio', 'Espacio para experimentos y prácticas', true),
('almacen', 'Espacio para almacenamiento de materiales', true),
('baño', 'Servicios sanitarios', true),
('parqueo', 'Área para estacionamiento de vehículos', true),
('deporte', 'Área para actividades deportivas', true),
('comedor', 'Área para alimentación', true),
('edificio', 'Estructura principal', true);

-- Insertar datos de ejemplo para el mapa principal
INSERT INTO public.mapas (nombre, descripcion, imagen_url) 
VALUES ('Mapa Principal del Centro Educativo', 'Mapa interactivo del centro educativo con todas las áreas y subdivisiones', 'assets/img/mapa_principal.svg');

-- Insertar algunas áreas de ejemplo (estos datos deberán ajustarse según el mapa real)
INSERT INTO public.areas_mapa (mapa_id, nombre, tipo_id, color, aula_id, data_id) 
VALUES 
(1, 'Parqueo', (SELECT id FROM tipos_area WHERE nombre = 'parqueo'), '#FFD700', NULL, 'parqueo'),
(1, 'Cancha 1', (SELECT id FROM tipos_area WHERE nombre = 'deporte'), '#6A5ACD', NULL, 'cancha-1'),
(1, 'Cancha 2', (SELECT id FROM tipos_area WHERE nombre = 'deporte'), '#6A5ACD', NULL, 'cancha-2'),
(1, 'Sección 1', (SELECT id FROM tipos_area WHERE nombre = 'edificio'), '#D3D3D3', NULL, 'seccion-1'),
(1, 'Sección 2', (SELECT id FROM tipos_area WHERE nombre = 'edificio'), '#D3D3D3', NULL, 'seccion-2'),
(1, 'Sección 3', (SELECT id FROM tipos_area WHERE nombre = 'edificio'), '#D3D3D3', NULL, 'seccion-3'),
(1, 'Sección 4', (SELECT id FROM tipos_area WHERE nombre = 'edificio'), '#D3D3D3', NULL, 'seccion-4'),
(1, 'Sección 5', (SELECT id FROM tipos_area WHERE nombre = 'edificio'), '#D3D3D3', NULL, 'seccion-5'),
(1, 'Sección 6', (SELECT id FROM tipos_area WHERE nombre = 'edificio'), '#D3D3D3', NULL, 'seccion-6'),
(1, 'Comedor', (SELECT id FROM tipos_area WHERE nombre = 'comedor'), '#D3D3D3', NULL, 'seccion-7'),
(1, 'Sección 8', (SELECT id FROM tipos_area WHERE nombre = 'edificio'), '#D3D3D3', NULL, 'seccion-8');

-- Insertar algunas subdivisiones de ejemplo

-- Comentarios sobre las tablas
COMMENT ON TABLE public.mapas IS 'Almacena información sobre los mapas disponibles en el sistema';
COMMENT ON TABLE public.areas_mapa IS 'Almacena las áreas principales del mapa interactivo';
COMMENT ON TABLE public.subdivisiones_area IS 'Almacena las subdivisiones de las áreas principales del mapa';
COMMENT ON TABLE public.responsables_area IS 'Almacena los responsables asignados a cada área del mapa';
COMMENT ON TABLE public.personal_area IS 'Almacena el personal asignado a cada área del mapa';
COMMENT ON TABLE public.tipos_area IS 'Almacena los tipos de áreas disponibles en el sistema'; 

-- Insertar subdivisiones para áreas sin subdivisiones (áreas completas)
INSERT INTO public.subdivisiones_area (area_id, nombre, tipo_id, aula_id, data_id, path_data, color) 
VALUES 
-- Parqueo
(1, 'Parqueo', (SELECT id FROM tipos_area WHERE nombre = 'parqueo'), NULL, 'parqueo', 'M50 50 h200 v150 h-200 Z', '#FFD700'),
-- Cancha 1
(2, 'Cancha 1', (SELECT id FROM tipos_area WHERE nombre = 'deporte'), NULL, 'cancha-1', 'M300 50 h200 v150 h-200 Z', '#6A5ACD'),
-- Cancha 2
(3, 'Cancha 2', (SELECT id FROM tipos_area WHERE nombre = 'deporte'), NULL, 'cancha-2', 'M550 50 h200 v150 h-200 Z', '#6A5ACD'),
-- Comedor
(10, 'Comedor', (SELECT id FROM tipos_area WHERE nombre = 'comedor'), NULL, 'seccion-7', 'M800 50 h150 v200 h-150 Z', '#D3D3D3');

-- Actualizar los path_data para las áreas principales que no tienen subdivisiones
UPDATE public.areas_mapa 
SET path_data = 'M50 50 h200 v150 h-200 Z'
WHERE data_id = 'parqueo';

UPDATE public.areas_mapa 
SET path_data = 'M300 50 h200 v150 h-200 Z'
WHERE data_id = 'cancha-1';

UPDATE public.areas_mapa 
SET path_data = 'M550 50 h200 v150 h-200 Z'
WHERE data_id = 'cancha-2';

UPDATE public.areas_mapa 
SET path_data = 'M800 50 h150 v200 h-150 Z'
WHERE data_id = 'seccion-7';