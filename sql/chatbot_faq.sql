-- Crear tabla para las preguntas y respuestas frecuentes del chatbot
CREATE TABLE IF NOT EXISTS respuestas_chatbot (
    id SERIAL PRIMARY KEY,
    pregunta TEXT NOT NULL,
    respuesta TEXT NOT NULL,
    categoria VARCHAR(50),
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar algunas preguntas y respuestas frecuentes
INSERT INTO respuestas_chatbot (pregunta, respuesta, categoria) VALUES
('¿Cómo puedo ver mi horario de clases?', 'Puedes consultar tu horario de clases en la sección "Horarios" del menú principal.', 'Horarios'),
('¿Dónde encuentro mis calificaciones?', 'Tus calificaciones están disponibles en la sección "Calificaciones" del menú de estudiante.', 'Calificaciones'),
('¿Cómo solicito un certificado?', 'Para solicitar documentos académicos, dirígete a la sección "Solicitudes de Documentos".', 'Documentos'),
('¿Cuándo es el próximo pago de mensualidad?', 'Los pagos de mensualidad deben realizarse antes del día 5 de cada mes.', 'Pagos'),
('¿Hay algún evento próximo en la escuela?', 'Puedes consultar los próximos eventos escolares en la sección "Eventos".', 'Eventos'),
('¿Cuál es el proceso de inscripción?', 'El proceso de inscripción para nuevos estudiantes comienza en el mes de junio. Para más información, contacta a la administración.', 'Inscripción'),
('¿Cómo contacto a un profesor?', 'Puedes contactar a tus profesores a través de la plataforma educativa o en sus horarios de atención.', 'Contacto'),
('¿Dónde puedo ver el calendario escolar?', 'El calendario escolar está disponible en la sección "Calendario" del menú principal.', 'Calendario'),
('¿Cómo reporto un problema técnico?', 'Para reportar problemas técnicos, utiliza la sección "Soporte Técnico" o contacta al administrador del sistema.', 'Soporte'),
('¿Cuáles son los requisitos para graduación?', 'Los requisitos para graduación incluyen completar todos los créditos requeridos y tener un promedio mínimo de 7.0.', 'Graduación'); 