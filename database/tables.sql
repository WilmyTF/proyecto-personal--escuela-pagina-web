-- Tabla de relación entre estudiantes y padres
CREATE TABLE IF NOT EXISTS estudiante_padre (
    id INT PRIMARY KEY AUTO_INCREMENT,
    estudiante_id INT NOT NULL,
    padre_id INT NOT NULL,
    es_principal BOOLEAN DEFAULT true,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (padre_id) REFERENCES padres_tutores(id) ON DELETE CASCADE,
    UNIQUE KEY unique_estudiante_padre (estudiante_id, padre_id)
); 