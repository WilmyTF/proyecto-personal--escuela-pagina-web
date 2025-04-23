-- Script para crear las tablas necesarias para el sistema de auditoría
-- Autor: Sistema de Gestión Educativa
-- Fecha: 2023

-- Crear secuencias para las tablas
CREATE SEQUENCE IF NOT EXISTS public.logs_acceso_id_seq
    INCREMENT BY 1
    MINVALUE 1
    MAXVALUE 2147483647
    START WITH 1
    CACHE 1
    NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.auditoria_sistema_id_seq
    INCREMENT BY 1
    MINVALUE 1
    MAXVALUE 2147483647
    START WITH 1
    CACHE 1
    NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.inicializadores_mapa_id_seq
    INCREMENT BY 1
    MINVALUE 1
    MAXVALUE 2147483647
    START WITH 1
    CACHE 1
    NO CYCLE;

-- Tabla de logs de acceso (inicios y cierres de sesión)
CREATE TABLE IF NOT EXISTS public.logs_acceso (
    id integer NOT NULL DEFAULT nextval('public.logs_acceso_id_seq'::regclass),
    usuario_id integer,
    fecha timestamp DEFAULT CURRENT_TIMESTAMP,
    ip character varying(50),
    accion text,
    CONSTRAINT logs_acceso_pkey PRIMARY KEY (id)
);

COMMENT ON TABLE public.logs_acceso IS 'Tabla que almacena registros de acceso de los usuarios (login/logout)';

-- Tabla de auditoría del sistema (cambios en entidades, operaciones importantes)
CREATE TABLE IF NOT EXISTS public.auditoria_sistema (
    id integer NOT NULL DEFAULT nextval('public.auditoria_sistema_id_seq'::regclass),
    usuario_id integer,
    tipo_accion character varying(100),
    fecha timestamp DEFAULT CURRENT_TIMESTAMP,
    descripcion text,
    CONSTRAINT auditoria_sistema_pkey PRIMARY KEY (id)
);

COMMENT ON TABLE public.auditoria_sistema IS 'Tabla que almacena eventos de auditoría del sistema';

-- Tabla de inicializadores del mapa
CREATE TABLE IF NOT EXISTS public.inicializadores_mapa (
    id integer NOT NULL DEFAULT nextval('public.inicializadores_mapa_id_seq'::regclass),
    nombre character varying(100) NOT NULL,
    descripcion text,
    parametros text,
    activo boolean DEFAULT true,
    fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_ejecucion timestamp,
    usuario_creador_id integer,
    CONSTRAINT inicializadores_mapa_pkey PRIMARY KEY (id)
);

COMMENT ON TABLE public.inicializadores_mapa IS 'Tabla que almacena los inicializadores disponibles para el mapa interactivo';

-- Añadir restricciones de clave foránea

-- Relación con la tabla de usuarios




ALTER TABLE public.inicializadores_mapa
    ADD CONSTRAINT inicializadores_mapa_usuario_creador_id_fkey FOREIGN KEY (usuario_creador_id)
    REFERENCES public.usuarios (id) MATCH SIMPLE
    ON DELETE NO ACTION ON UPDATE NO ACTION;

-- Índices para mejorar el rendimiento de las consultas
CREATE INDEX idx_logs_acceso_usuario_id ON public.logs_acceso(usuario_id);
CREATE INDEX idx_logs_acceso_fecha ON public.logs_acceso(fecha);
CREATE INDEX idx_auditoria_sistema_usuario_id ON public.auditoria_sistema(usuario_id);
CREATE INDEX idx_auditoria_sistema_fecha ON public.auditoria_sistema(fecha);
CREATE INDEX idx_auditoria_sistema_tipo_accion ON public.auditoria_sistema(tipo_accion);
CREATE INDEX idx_inicializadores_mapa_activo ON public.inicializadores_mapa(activo);

-- Permisos
ALTER TABLE public.logs_acceso OWNER TO postgres;
ALTER TABLE public.auditoria_sistema OWNER TO postgres;
ALTER TABLE public.inicializadores_mapa OWNER TO postgres;

-- Insertar algunos datos de ejemplo para inicializadores
INSERT INTO public.inicializadores_mapa (nombre, descripcion, parametros, activo, usuario_creador_id)
VALUES 
('Inicializador de Áreas Básicas', 'Crea las áreas básicas del mapa interactivo del centro educativo', 
'{"areas": ["Administración", "Aulas", "Biblioteca", "Cafetería", "Laboratorios"]}', 
true, 1),

('Inicializador de Laboratorios', 'Crea los laboratorios específicos en el área correspondiente', 
'{"area_padre": "Laboratorios", "laboratorios": ["Laboratorio de Informática", "Laboratorio de Ciencias", "Laboratorio de Idiomas"]}', 
true, 1),

('Inicializador de Estado Inicial', 'Restablece el mapa a su estado inicial con todas las áreas por defecto', 
'{"borrar_existente": true, "incluir_personal": false}', 
false, 1);

-- Crear una función para registrar automáticamente cambios en usuarios
CREATE OR REPLACE FUNCTION fn_registrar_cambio_usuario()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO public.auditoria_sistema (usuario_id, tipo_accion, descripcion)
    VALUES (
        COALESCE(NEW.id, OLD.id), 
        CASE
            WHEN TG_OP = 'INSERT' THEN 'Creación Usuario'
            WHEN TG_OP = 'UPDATE' THEN 'Modificación Usuario'
            WHEN TG_OP = 'DELETE' THEN 'Eliminación Usuario'
        END,
        CASE
            WHEN TG_OP = 'INSERT' THEN 'Se creó el usuario ' || NEW.nombre || ' ' || NEW.apellido
            WHEN TG_OP = 'UPDATE' THEN 'Se modificó el usuario ' || OLD.nombre || ' ' || OLD.apellido
            WHEN TG_OP = 'DELETE' THEN 'Se eliminó el usuario ' || OLD.nombre || ' ' || OLD.apellido
        END
    );
    
    IF TG_OP = 'INSERT' OR TG_OP = 'UPDATE' THEN
        RETURN NEW;
    ELSE
        RETURN OLD;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- Crear un trigger para la tabla usuarios
DROP TRIGGER IF EXISTS trg_usuarios_auditoria ON public.usuarios;
CREATE TRIGGER trg_usuarios_auditoria
AFTER INSERT OR UPDATE OR DELETE ON public.usuarios
FOR EACH ROW EXECUTE FUNCTION fn_registrar_cambio_usuario();

-- Crear una función para registrar automáticamente cambios en el mapa
CREATE OR REPLACE FUNCTION fn_registrar_cambio_mapa()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO public.auditoria_sistema (usuario_id, tipo_accion, descripcion)
    VALUES (
        COALESCE(current_setting('app.current_user_id', true)::integer, 1), 
        'Cambio Mapa',
        CASE
            WHEN TG_OP = 'INSERT' THEN 'Se creó un área nueva en el mapa: ' || NEW.nombre
            WHEN TG_OP = 'UPDATE' THEN 'Se modificó el área "' || OLD.nombre || '" en el mapa'
            WHEN TG_OP = 'DELETE' THEN 'Se eliminó el área "' || OLD.nombre || '" del mapa'
        END
    );
    
    IF TG_OP = 'INSERT' OR TG_OP = 'UPDATE' THEN
        RETURN NEW;
    ELSE
        RETURN OLD;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- Crear un trigger para la tabla areas_mapa
DROP TRIGGER IF EXISTS trg_areas_mapa_auditoria ON public.areas_mapa;
CREATE TRIGGER trg_areas_mapa_auditoria
AFTER INSERT OR UPDATE OR DELETE ON public.areas_mapa
FOR EACH ROW EXECUTE FUNCTION fn_registrar_cambio_mapa(); 