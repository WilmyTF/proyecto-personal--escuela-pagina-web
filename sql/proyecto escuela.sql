-- Database generated with pgModeler (PostgreSQL Database Modeler).
-- pgModeler version: 1.1.0-alpha
-- PostgreSQL version: 15.0
-- Project Site: pgmodeler.io
-- Model Author: ---
-- -- object: pg_database_owner | type: ROLE --
-- -- DROP ROLE IF EXISTS pg_database_owner;
-- CREATE ROLE pg_database_owner WITH 
-- 	INHERIT
-- 	 PASSWORD '********';
-- -- ddl-end --
-- 
-- object: "Admin" | type: ROLE --
-- DROP ROLE IF EXISTS "Admin";
CREATE ROLE "Admin" WITH 
	SUPERUSER
	CREATEDB
	CREATEROLE
	INHERIT
	LOGIN
	REPLICATION
	BYPASSRLS
	 PASSWORD '********';
-- ddl-end --


-- Database creation must be performed outside a multi lined SQL file. 
-- These commands were put in this file only as a convenience.
-- 
-- object: gestion_centro_educativo | type: DATABASE --
-- DROP DATABASE IF EXISTS gestion_centro_educativo;
CREATE DATABASE gestion_centro_educativo
	ENCODING = 'UTF8'
	LC_COLLATE = 'Spanish_Mexico.1252'
	LC_CTYPE = 'Spanish_Mexico.1252'
	TABLESPACE = pg_default
	OWNER = postgres;
-- ddl-end --


-- Crear todas las secuencias primero
CREATE SEQUENCE IF NOT EXISTS public.usuarios_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.estudiantes_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.docentes_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.empleados_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.padres_tutores_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.inscripciones_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.cursos_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.horarios_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.calificaciones_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.integraciones_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.aulas_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.mantenimiento_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.inventario_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.facturas_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.proveedores_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

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

CREATE SEQUENCE IF NOT EXISTS public.examenes_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.eventos_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.noticias_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.registros_eventos_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.pagos_estudiantes_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.pagos_docentes_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.pagos_empleados_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.solicitudes_documentos_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

CREATE SEQUENCE IF NOT EXISTS public.solicitudes_reparaciones_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

-- object: public.usuarios | type: TABLE --
-- DROP TABLE IF EXISTS public.usuarios CASCADE;
CREATE TABLE public.usuarios (
	id integer NOT NULL DEFAULT nextval('public.usuarios_id_seq'::regclass),
	nombre character varying(50),
	apellido character varying(50),
	email character varying(100),
	"contraseña" text,
	"Estado" smallint,
	CONSTRAINT usuarios_pkey PRIMARY KEY (id),
	CONSTRAINT usuarios_email_key UNIQUE (email)
);
-- ddl-end --
ALTER TABLE public.usuarios OWNER TO postgres;
-- ddl-end --

-- object: public.estudiantes | type: TABLE --
-- DROP TABLE IF EXISTS public.estudiantes CASCADE;
CREATE TABLE public.estudiantes (
	usuario_id integer,
	nombre character varying(100),
	apellido character varying(100),
	matricula character varying(20) NOT NULL,
	curso_id integer,
	estado character varying(20),
	historial_academico text,
	CONSTRAINT estudiantes_pkey PRIMARY KEY (matricula),
	CONSTRAINT estudiantes_matricula_key UNIQUE (matricula)
);
-- ddl-end --
ALTER TABLE public.estudiantes OWNER TO postgres;
-- ddl-end --

-- object: public.docentes | type: TABLE --
-- DROP TABLE IF EXISTS public.docentes CASCADE;
CREATE TABLE public.docentes (
	id integer NOT NULL DEFAULT nextval('public.docentes_id_seq'::regclass),
	usuario_id integer,
	especialidad character varying(100),
	horario text,
	estado character varying(20),
	CONSTRAINT docentes_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.docentes OWNER TO postgres;
-- ddl-end --

-- object: public.empleados | type: TABLE --
-- DROP TABLE IF EXISTS public.empleados CASCADE;
CREATE TABLE public.empleados (
	id integer NOT NULL DEFAULT nextval('public.empleados_id_seq'::regclass),
	usuario_id integer,
	departamento character varying(100),
	cargo character varying(100),
	horario text,
	estado smallint,
	CONSTRAINT empleados_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.empleados OWNER TO postgres;
-- ddl-end --

-- object: public.padres_tutores | type: TABLE --
-- DROP TABLE IF EXISTS public.padres_tutores CASCADE;
CREATE TABLE public.padres_tutores (
	id integer NOT NULL DEFAULT nextval('public.padres_tutores_id_seq'::regclass),
	usuario_id integer,
	nombre character varying(100),
	apellido character varying(100),
	estudiante_id integer,
	telefono character varying(15),
	direccion text,
	correo character varying(100),
	CONSTRAINT padres_tutores_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.padres_tutores OWNER TO postgres;
-- ddl-end --

-- object: public.inscripciones | type: TABLE --
-- DROP TABLE IF EXISTS public.inscripciones CASCADE;
CREATE TABLE public.inscripciones (
	curso_id integer,
	fecha_inscripcion date,
	estado character varying(20),
	"Periodo_id_Periodo" character varying(20) NOT NULL,
	id_usuarios integer NOT NULL,
	CONSTRAINT inscripciones_pk PRIMARY KEY ("Periodo_id_Periodo",id_usuarios)
);
-- ddl-end --
ALTER TABLE public.inscripciones OWNER TO postgres;
-- ddl-end --

-- object: public.cursos | type: TABLE --
-- DROP TABLE IF EXISTS public.cursos CASCADE;
CREATE TABLE public.cursos (
	id integer NOT NULL DEFAULT nextval('public.cursos_id_seq'::regclass),
	nombre character varying(100),
	descripcion text,
	docente_id integer,
	cupo_maximo integer,
	CONSTRAINT cursos_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.cursos OWNER TO postgres;
-- ddl-end --

-- object: public.asignaturas | type: TABLE --
-- DROP TABLE IF EXISTS public.asignaturas CASCADE;
CREATE TABLE public.asignaturas (
	id character varying(20) NOT NULL,
	nombre character varying(100),
	descripcion text,
	curso_id integer,
	CONSTRAINT asignaturas_pk PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.asignaturas OWNER TO postgres;
-- ddl-end --

-- object: public.horarios | type: TABLE --
-- DROP TABLE IF EXISTS public.horarios CASCADE;
CREATE TABLE public.horarios (
	id integer NOT NULL DEFAULT nextval('public.horarios_id_seq'::regclass),
	curso_id integer,
	dia character varying(15),
	hora_inicio time,
	hora_fin time,
	aula character varying(50),
	CONSTRAINT horarios_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.horarios OWNER TO postgres;
-- ddl-end --

-- object: public.calificaciones | type: TABLE --
-- DROP TABLE IF EXISTS public.calificaciones CASCADE;
CREATE TABLE public.calificaciones (
	nota numeric(4,2),
	periodo character varying(20),
	matricula_estudiantes character varying(20) NOT NULL,
	id_asignaturas character varying(20) NOT NULL,
	"Periodo_id_Periodo" character varying(20) NOT NULL,
	CONSTRAINT calificaciones_pk PRIMARY KEY (matricula_estudiantes,id_asignaturas,"Periodo_id_Periodo")
);
-- ddl-end --
ALTER TABLE public.calificaciones OWNER TO postgres;
-- ddl-end --

-- object: public.integraciones | type: TABLE --
-- DROP TABLE IF EXISTS public.integraciones CASCADE;
CREATE TABLE public.integraciones (
	usuario_id integer,
	plataforma character varying(50),
	cuenta character varying(100),
	link_sesion text,
	fecha timestamp DEFAULT CURRENT_TIMESTAMP

);
-- ddl-end --
ALTER TABLE public.integraciones OWNER TO postgres;
-- ddl-end --

-- object: public.aulas | type: TABLE --
-- DROP TABLE IF EXISTS public.aulas CASCADE;
CREATE TABLE public.aulas (
	id integer NOT NULL DEFAULT nextval('public.aulas_id_seq'::regclass),
	nombre character varying(50),
	capacidad integer,
	tipo character varying(50),
	CONSTRAINT aulas_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.aulas OWNER TO postgres;
-- ddl-end --

-- object: public.mantenimiento | type: TABLE --
-- DROP TABLE IF EXISTS public.mantenimiento CASCADE;
CREATE TABLE public.mantenimiento (
	id integer NOT NULL DEFAULT nextval('public.mantenimiento_id_seq'::regclass),
	aula_id integer,
	fecha date,
	estado character varying(50),
	descripcion text,
	CONSTRAINT mantenimiento_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.mantenimiento OWNER TO postgres;
-- ddl-end --

-- object: public.inventario | type: TABLE --
-- DROP TABLE IF EXISTS public.inventario CASCADE;
CREATE TABLE public.inventario (
	id integer NOT NULL DEFAULT nextval('public.inventario_id_seq'::regclass),
	producto character varying(100),
	cantidad integer,
	ubicacion text,
	estado character varying(50),
	CONSTRAINT inventario_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.inventario OWNER TO postgres;
-- ddl-end --

-- object: public.facturas | type: TABLE --
-- DROP TABLE IF EXISTS public.facturas CASCADE;
CREATE TABLE public.facturas (
	id integer NOT NULL DEFAULT nextval('public.facturas_id_seq'::regclass),
	proveedor_id integer,
	fecha date,
	total numeric(10,2),
	estado character varying(50),
	CONSTRAINT facturas_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.facturas OWNER TO postgres;
-- ddl-end --

-- object: public.proveedores | type: TABLE --
-- DROP TABLE IF EXISTS public.proveedores CASCADE;
CREATE TABLE public.proveedores (
	id integer NOT NULL DEFAULT nextval('public.proveedores_id_seq'::regclass),
	nombre character varying(100),
	contacto character varying(100),
	telefono character varying(20),
	email character varying(100),
	CONSTRAINT proveedores_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.proveedores OWNER TO postgres;
-- ddl-end --

-- object: public.logs_acceso | type: TABLE --
-- DROP TABLE IF EXISTS public.logs_acceso CASCADE;
CREATE TABLE public.logs_acceso (
	id integer NOT NULL DEFAULT nextval('public.logs_acceso_id_seq'::regclass),
	usuario_id integer,
	fecha timestamp DEFAULT CURRENT_TIMESTAMP,
	ip character varying(50),
	accion text,
	CONSTRAINT logs_acceso_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.logs_acceso OWNER TO postgres;
-- ddl-end --

-- object: public.auditoria_sistema | type: TABLE --
-- DROP TABLE IF EXISTS public.auditoria_sistema CASCADE;
CREATE TABLE public.auditoria_sistema (
	id integer NOT NULL DEFAULT nextval('public.auditoria_sistema_id_seq'::regclass),
	usuario_id integer,
	tipo_accion character varying(100),
	fecha timestamp DEFAULT CURRENT_TIMESTAMP,
	descripcion text,
	CONSTRAINT auditoria_sistema_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.auditoria_sistema OWNER TO postgres;
-- ddl-end --

-- object: public.examenes | type: TABLE --
-- DROP TABLE IF EXISTS public.examenes CASCADE;
CREATE TABLE public.examenes (
	id integer NOT NULL DEFAULT nextval('public.examenes_id_seq'::regclass),
	asignatura_id character varying(20),
	nombre character varying(100),
	fecha date,
	duracion integer,
	CONSTRAINT examenes_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.examenes OWNER TO postgres;
-- ddl-end --

-- object: public.eventos | type: TABLE --
-- DROP TABLE IF EXISTS public.eventos CASCADE;
CREATE TABLE public.eventos (
	id integer NOT NULL DEFAULT nextval('public.eventos_id_seq'::regclass),
	nombre character varying(100),
	descripcion text,
	fecha date,
	lugar character varying(100),
	CONSTRAINT eventos_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.eventos OWNER TO postgres;
-- ddl-end --

-- object: public.noticias | type: TABLE --
-- DROP TABLE IF EXISTS public.noticias CASCADE;
CREATE TABLE public.noticias (
	id integer NOT NULL DEFAULT nextval('public.noticias_id_seq'::regclass),
	titulo character varying(100),
	contenido text,
	fecha_publicacion date,
	CONSTRAINT noticias_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.noticias OWNER TO postgres;
-- ddl-end --

-- object: public.registros_eventos | type: TABLE --
-- DROP TABLE IF EXISTS public.registros_eventos CASCADE;
CREATE TABLE public.registros_eventos (
	id integer NOT NULL DEFAULT nextval('public.registros_eventos_id_seq'::regclass),
	usuario_id integer,
	evento_id integer,
	asistencia boolean,
	CONSTRAINT registros_eventos_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.registros_eventos OWNER TO postgres;
-- ddl-end --

-- object: public.pagos_docentes | type: TABLE --
-- DROP TABLE IF EXISTS public.pagos_docentes CASCADE;
CREATE TABLE public.pagos_docentes (
	id integer NOT NULL DEFAULT nextval('public.pagos_docentes_id_seq'::regclass),
	docente_id integer,
	monto numeric(10,2),
	fecha date,
	metodo_pago character varying(50),
	CONSTRAINT pagos_docentes_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.pagos_docentes OWNER TO postgres;
-- ddl-end --

-- object: public.pagos_empleados | type: TABLE --
-- DROP TABLE IF EXISTS public.pagos_empleados CASCADE;
CREATE TABLE public.pagos_empleados (
	id integer NOT NULL DEFAULT nextval('public.pagos_empleados_id_seq'::regclass),
	empleado_id integer,
	monto numeric(10,2),
	fecha date,
	metodo_pago character varying(50),
	CONSTRAINT pagos_empleados_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.pagos_empleados OWNER TO postgres;
-- ddl-end --

-- object: public.solicitudes_documentos | type: TABLE --
-- DROP TABLE IF EXISTS public.solicitudes_documentos CASCADE;
CREATE TABLE public.solicitudes_documentos (
	id integer NOT NULL DEFAULT nextval('public.solicitudes_documentos_id_seq'::regclass),
	estudiante_id integer,
	tipo_documento character varying(100),
	fecha date,
	estado character varying(50),
	CONSTRAINT solicitudes_documentos_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.solicitudes_documentos OWNER TO postgres;
-- ddl-end --

-- object: public.solicitudes_reparaciones | type: TABLE --
-- DROP TABLE IF EXISTS public.solicitudes_reparaciones CASCADE;
CREATE TABLE public.solicitudes_reparaciones (
	id integer NOT NULL DEFAULT nextval('public.solicitudes_reparaciones_id_seq'::regclass),
	usuario_id integer,
	descripcion text,
	fecha date,
	estado character varying(50),
	CONSTRAINT solicitudes_reparaciones_pkey PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.solicitudes_reparaciones OWNER TO postgres;
-- ddl-end --

-- object: public."Padre_estudiante" | type: TABLE --
-- DROP TABLE IF EXISTS public."Padre_estudiante" CASCADE;
CREATE TABLE public."Padre_estudiante" (
	id_padres_tutores integer NOT NULL,
	matricula_estudiantes character varying(20) NOT NULL

);
-- ddl-end --
ALTER TABLE public."Padre_estudiante" OWNER TO postgres;
-- ddl-end --

-- object: padres_tutores_fk | type: CONSTRAINT --
-- ALTER TABLE public."Padre_estudiante" DROP CONSTRAINT IF EXISTS padres_tutores_fk CASCADE;
ALTER TABLE public."Padre_estudiante" ADD CONSTRAINT padres_tutores_fk FOREIGN KEY (id_padres_tutores)
REFERENCES public.padres_tutores (id) MATCH FULL
ON DELETE RESTRICT ON UPDATE CASCADE;
-- ddl-end --

-- object: public.empleado_usuario | type: TABLE --
-- DROP TABLE IF EXISTS public.empleado_usuario CASCADE;
CREATE TABLE public.empleado_usuario (
	id_empleados integer NOT NULL,
	id_usuarios integer NOT NULL,
	CONSTRAINT empleado_usuario_pk PRIMARY KEY (id_empleados,id_usuarios)
);
-- ddl-end --
ALTER TABLE public.empleado_usuario OWNER TO postgres;
-- ddl-end --

-- object: empleados_fk | type: CONSTRAINT --
-- ALTER TABLE public.empleado_usuario DROP CONSTRAINT IF EXISTS empleados_fk CASCADE;
ALTER TABLE public.empleado_usuario ADD CONSTRAINT empleados_fk FOREIGN KEY (id_empleados)
REFERENCES public.empleados (id) MATCH FULL
ON DELETE RESTRICT ON UPDATE CASCADE;
-- ddl-end --

-- object: usuarios_fk | type: CONSTRAINT --
-- ALTER TABLE public.empleado_usuario DROP CONSTRAINT IF EXISTS usuarios_fk CASCADE;
ALTER TABLE public.empleado_usuario ADD CONSTRAINT usuarios_fk FOREIGN KEY (id_usuarios)
REFERENCES public.usuarios (id) MATCH FULL
ON DELETE RESTRICT ON UPDATE CASCADE;
-- ddl-end --

-- object: estudiantes_fk | type: CONSTRAINT --
-- ALTER TABLE public."Padre_estudiante" DROP CONSTRAINT IF EXISTS estudiantes_fk CASCADE;
ALTER TABLE public."Padre_estudiante" ADD CONSTRAINT estudiantes_fk FOREIGN KEY (matricula_estudiantes)
REFERENCES public.estudiantes (matricula) MATCH FULL
ON DELETE RESTRICT ON UPDATE CASCADE;
-- ddl-end --

-- object: estudiantes_fk | type: CONSTRAINT --
-- ALTER TABLE public.calificaciones DROP CONSTRAINT IF EXISTS estudiantes_fk CASCADE;
ALTER TABLE public.calificaciones ADD CONSTRAINT estudiantes_fk FOREIGN KEY (matricula_estudiantes)
REFERENCES public.estudiantes (matricula) MATCH FULL
ON DELETE RESTRICT ON UPDATE CASCADE;
-- ddl-end --

-- object: asignaturas_fk | type: CONSTRAINT --
-- ALTER TABLE public.calificaciones DROP CONSTRAINT IF EXISTS asignaturas_fk CASCADE;
ALTER TABLE public.calificaciones ADD CONSTRAINT asignaturas_fk FOREIGN KEY (id_asignaturas)
REFERENCES public.asignaturas (id) MATCH FULL
ON DELETE RESTRICT ON UPDATE CASCADE;
-- ddl-end --

-- object: public."Periodo" | type: TABLE --
-- DROP TABLE IF EXISTS public."Periodo" CASCADE;
CREATE TABLE public."Periodo" (
	"Periodo_id" character varying(20) NOT NULL,
	"Inicio" date,
	"Inicio_programado" date NOT NULL,
	"Fin" date,
	"Fin_programado" smallint NOT NULL,
	CONSTRAINT "Periodo_pk" PRIMARY KEY ("Periodo_id")
);
-- ddl-end --
ALTER TABLE public."Periodo" OWNER TO postgres;
-- ddl-end --

-- object: "Periodo_fk" | type: CONSTRAINT --
-- ALTER TABLE public.calificaciones DROP CONSTRAINT IF EXISTS "Periodo_fk" CASCADE;
ALTER TABLE public.calificaciones ADD CONSTRAINT "Periodo_fk" FOREIGN KEY ("Periodo_id_Periodo")
REFERENCES public."Periodo" ("Periodo_id") MATCH FULL
ON DELETE RESTRICT ON UPDATE CASCADE;
-- ddl-end --

-- object: "Periodo_fk" | type: CONSTRAINT --
-- ALTER TABLE public.inscripciones DROP CONSTRAINT IF EXISTS "Periodo_fk" CASCADE;
ALTER TABLE public.inscripciones ADD CONSTRAINT "Periodo_fk" FOREIGN KEY ("Periodo_id_Periodo")
REFERENCES public."Periodo" ("Periodo_id") MATCH FULL
ON DELETE RESTRICT ON UPDATE CASCADE;
-- ddl-end --

-- object: usuarios_fk | type: CONSTRAINT --
-- ALTER TABLE public.inscripciones DROP CONSTRAINT IF EXISTS usuarios_fk CASCADE;
ALTER TABLE public.inscripciones ADD CONSTRAINT usuarios_fk FOREIGN KEY (id_usuarios)
REFERENCES public.usuarios (id) MATCH FULL
ON DELETE RESTRICT ON UPDATE CASCADE;
-- ddl-end --

-- object: public."Permisos" | type: TABLE --
-- DROP TABLE IF EXISTS public."Permisos" CASCADE;
CREATE TABLE public."Permisos" (
	id integer NOT NULL GENERATED ALWAYS AS IDENTITY ,
	nombre_permiso character varying(100),
	estado smallint,
	CONSTRAINT "Permisos_pk" PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public."Permisos" OWNER TO postgres;
-- ddl-end --

-- object: public."usuario-permiso" | type: TABLE --
-- DROP TABLE IF EXISTS public."usuario-permiso" CASCADE;
CREATE TABLE public."usuario-permiso" (
	"id_Permisos" integer NOT NULL,
	id_usuarios integer NOT NULL

);
-- ddl-end --
ALTER TABLE public."usuario-permiso" OWNER TO postgres;
-- ddl-end --

-- object: "Permisos_fk" | type: CONSTRAINT --
-- ALTER TABLE public."usuario-permiso" DROP CONSTRAINT IF EXISTS "Permisos_fk" CASCADE;
ALTER TABLE public."usuario-permiso" ADD CONSTRAINT "Permisos_fk" FOREIGN KEY ("id_Permisos")
REFERENCES public."Permisos" (id) MATCH FULL
ON DELETE RESTRICT ON UPDATE CASCADE;
-- ddl-end --

-- object: usuarios_fk | type: CONSTRAINT --
-- ALTER TABLE public."usuario-permiso" DROP CONSTRAINT IF EXISTS usuarios_fk CASCADE;
ALTER TABLE public."usuario-permiso" ADD CONSTRAINT usuarios_fk FOREIGN KEY (id_usuarios)
REFERENCES public.usuarios (id) MATCH FULL
ON DELETE RESTRICT ON UPDATE CASCADE;
-- ddl-end --

-- object: public.cargo | type: TABLE --
-- DROP TABLE IF EXISTS public.cargo CASCADE;
CREATE TABLE public.cargo (
	id integer NOT NULL GENERATED ALWAYS AS IDENTITY ,
	nombre character varying(100),
	estado smallint,
	CONSTRAINT cargo_pk PRIMARY KEY (id)
);
-- ddl-end --
ALTER TABLE public.cargo OWNER TO postgres;
-- ddl-end --

-- object: public.empleado_cargo | type: TABLE --
-- DROP TABLE IF EXISTS public.empleado_cargo CASCADE;
CREATE TABLE public.empleado_cargo (
	id_cargo integer NOT NULL,
	id_usuarios integer

);
-- ddl-end --
ALTER TABLE public.empleado_cargo OWNER TO postgres;
-- ddl-end --

-- object: cargo_fk | type: CONSTRAINT --
-- ALTER TABLE public.empleado_cargo DROP CONSTRAINT IF EXISTS cargo_fk CASCADE;
ALTER TABLE public.empleado_cargo ADD CONSTRAINT cargo_fk FOREIGN KEY (id_cargo)
REFERENCES public.cargo (id) MATCH FULL
ON DELETE RESTRICT ON UPDATE CASCADE;
-- ddl-end --

-- object: usuarios_fk | type: CONSTRAINT --
-- ALTER TABLE public.empleado_cargo DROP CONSTRAINT IF EXISTS usuarios_fk CASCADE;
ALTER TABLE public.empleado_cargo ADD CONSTRAINT usuarios_fk FOREIGN KEY (id_usuarios)
REFERENCES public.usuarios (id) MATCH FULL
ON DELETE SET NULL ON UPDATE CASCADE;
-- ddl-end --

-- object: estudiantes_usuario_id_fkey | type: CONSTRAINT --
-- ALTER TABLE public.estudiantes DROP CONSTRAINT IF EXISTS estudiantes_usuario_id_fkey CASCADE;
ALTER TABLE public.estudiantes ADD CONSTRAINT estudiantes_usuario_id_fkey FOREIGN KEY (usuario_id)
REFERENCES public.usuarios (id) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: docentes_usuario_id_fkey | type: CONSTRAINT --
-- ALTER TABLE public.docentes DROP CONSTRAINT IF EXISTS docentes_usuario_id_fkey CASCADE;
ALTER TABLE public.docentes ADD CONSTRAINT docentes_usuario_id_fkey FOREIGN KEY (usuario_id)
REFERENCES public.usuarios (id) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: cursos_docente_id_fkey | type: CONSTRAINT --
-- ALTER TABLE public.cursos DROP CONSTRAINT IF EXISTS cursos_docente_id_fkey CASCADE;
ALTER TABLE public.cursos ADD CONSTRAINT cursos_docente_id_fkey FOREIGN KEY (docente_id)
REFERENCES public.docentes (id) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: horarios_curso_id_fkey | type: CONSTRAINT --
-- ALTER TABLE public.horarios DROP CONSTRAINT IF EXISTS horarios_curso_id_fkey CASCADE;
ALTER TABLE public.horarios ADD CONSTRAINT horarios_curso_id_fkey FOREIGN KEY (curso_id)
REFERENCES public.cursos (id) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: integraciones_usuario_id_fkey | type: CONSTRAINT --
-- ALTER TABLE public.integraciones DROP CONSTRAINT IF EXISTS integraciones_usuario_id_fkey CASCADE;
ALTER TABLE public.integraciones ADD CONSTRAINT integraciones_usuario_id_fkey FOREIGN KEY (usuario_id)
REFERENCES public.usuarios (id) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: mantenimiento_aula_id_fkey | type: CONSTRAINT --
-- ALTER TABLE public.mantenimiento DROP CONSTRAINT IF EXISTS mantenimiento_aula_id_fkey CASCADE;
ALTER TABLE public.mantenimiento ADD CONSTRAINT mantenimiento_aula_id_fkey FOREIGN KEY (aula_id)
REFERENCES public.aulas (id) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: logs_acceso_usuario_id_fkey | type: CONSTRAINT --
-- ALTER TABLE public.logs_acceso DROP CONSTRAINT IF EXISTS logs_acceso_usuario_id_fkey CASCADE;
ALTER TABLE public.logs_acceso ADD CONSTRAINT logs_acceso_usuario_id_fkey FOREIGN KEY (usuario_id)
REFERENCES public.usuarios (id) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: auditoria_sistema_usuario_id_fkey | type: CONSTRAINT --
-- ALTER TABLE public.auditoria_sistema DROP CONSTRAINT IF EXISTS auditoria_sistema_usuario_id_fkey CASCADE;
ALTER TABLE public.auditoria_sistema ADD CONSTRAINT auditoria_sistema_usuario_id_fkey FOREIGN KEY (usuario_id)
REFERENCES public.usuarios (id) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: examenes_asignatura_id_fkey | type: CONSTRAINT --
-- ALTER TABLE public.examenes DROP CONSTRAINT IF EXISTS examenes_asignatura_id_fkey CASCADE;
ALTER TABLE public.examenes ADD CONSTRAINT examenes_asignatura_id_fkey FOREIGN KEY (asignatura_id)
REFERENCES public.asignaturas (id) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: registros_eventos_usuario_id_fkey | type: CONSTRAINT --
-- ALTER TABLE public.registros_eventos DROP CONSTRAINT IF EXISTS registros_eventos_usuario_id_fkey CASCADE;
ALTER TABLE public.registros_eventos ADD CONSTRAINT registros_eventos_usuario_id_fkey FOREIGN KEY (usuario_id)
REFERENCES public.usuarios (id) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: registros_eventos_evento_id_fkey | type: CONSTRAINT --
-- ALTER TABLE public.registros_eventos DROP CONSTRAINT IF EXISTS registros_eventos_evento_id_fkey CASCADE;
ALTER TABLE public.registros_eventos ADD CONSTRAINT registros_eventos_evento_id_fkey FOREIGN KEY (evento_id)
REFERENCES public.eventos (id) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: pagos_docentes_docente_id_fkey | type: CONSTRAINT --
-- ALTER TABLE public.pagos_docentes DROP CONSTRAINT IF EXISTS pagos_docentes_docente_id_fkey CASCADE;
ALTER TABLE public.pagos_docentes ADD CONSTRAINT pagos_docentes_docente_id_fkey FOREIGN KEY (docente_id)
REFERENCES public.docentes (id) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: pagos_empleados_empleado_id_fkey | type: CONSTRAINT --
-- ALTER TABLE public.pagos_empleados DROP CONSTRAINT IF EXISTS pagos_empleados_empleado_id_fkey CASCADE;
ALTER TABLE public.pagos_empleados ADD CONSTRAINT pagos_empleados_empleado_id_fkey FOREIGN KEY (empleado_id)
REFERENCES public.empleados (id) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: solicitudes_reparaciones_usuario_id_fkey | type: CONSTRAINT --
-- ALTER TABLE public.solicitudes_reparaciones DROP CONSTRAINT IF EXISTS solicitudes_reparaciones_usuario_id_fkey CASCADE;
ALTER TABLE public.solicitudes_reparaciones ADD CONSTRAINT solicitudes_reparaciones_usuario_id_fkey FOREIGN KEY (usuario_id)
REFERENCES public.usuarios (id) MATCH SIMPLE
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: "grant_CU_26541e8cda" | type: PERMISSION --
GRANT CREATE,USAGE
   ON SCHEMA public
   TO pg_database_owner;
-- ddl-end --

-- object: "grant_U_cd8e46e7b6" | type: PERMISSION --
GRANT USAGE
   ON SCHEMA public
   TO PUBLIC;
-- ddl-end --

CREATE SEQUENCE IF NOT EXISTS public.accesos_permisos_id_seq
	INCREMENT BY 1
	MINVALUE 1
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE;

-- Tabla para almacenar los tipos de acceso que tendrá cada permiso
CREATE TABLE IF NOT EXISTS public.accesos_permisos (
    id integer NOT NULL DEFAULT nextval('public.accesos_permisos_id_seq'::regclass),
    id_permiso integer NOT NULL,
    acceso_admin boolean DEFAULT false,
    acceso_reportes boolean DEFAULT false,
    acceso_gestion boolean DEFAULT false,
    acceso_estudiantes boolean DEFAULT false,
    acceso_mapa boolean DEFAULT false,
    CONSTRAINT accesos_permisos_pkey PRIMARY KEY (id),
    CONSTRAINT accesos_permisos_fk FOREIGN KEY (id_permiso)
        REFERENCES public."Permisos" (id) MATCH FULL
        ON UPDATE CASCADE ON DELETE CASCADE
);

-- Comentarios de las columnas
COMMENT ON COLUMN public.accesos_permisos.acceso_admin IS 'Acceso completo a todas las funcionalidades';
COMMENT ON COLUMN public.accesos_permisos.acceso_reportes IS 'Acceso a la sección de reportes';
COMMENT ON COLUMN public.accesos_permisos.acceso_gestion IS 'Acceso a las secciones de gestión';
COMMENT ON COLUMN public.accesos_permisos.acceso_estudiantes IS 'Acceso a la sección de estudiantes';
COMMENT ON COLUMN public.accesos_permisos.acceso_mapa IS 'Acceso al mapa interactivo';
