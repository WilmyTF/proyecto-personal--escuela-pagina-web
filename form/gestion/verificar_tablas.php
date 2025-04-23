<?php
require_once '../../includes/conexion.php';

try {
    verificarConexion();
    
    // Verificar si las tablas existen
    $query = "
        SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'empleados'
        );";
    $result = pg_query($conexion, $query);
    $existe_empleados = pg_fetch_result($result, 0, 0);

    if ($existe_empleados === 'f') {
        // Crear tabla empleados
        $query = "
            CREATE TABLE empleados (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(100),
                apellido VARCHAR(100),
                departamento VARCHAR(100),
                cargo VARCHAR(100),
                horario TEXT,
                estado VARCHAR(20) DEFAULT 'Activo'
            );";
        pg_query($conexion, $query);
        echo "Tabla empleados creada.<br>";
    }

    $query = "
        SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'docentes'
        );";
    $result = pg_query($conexion, $query);
    $existe_docentes = pg_fetch_result($result, 0, 0);

    if ($existe_docentes === 'f') {
        // Crear tabla docentes
        $query = "
            CREATE TABLE docentes (
                id SERIAL PRIMARY KEY,
                nombre VARCHAR(100),
                apellido VARCHAR(100),
                especialidad VARCHAR(100),
                horario TEXT,
                estado VARCHAR(20) DEFAULT 'Activo'
            );";
        pg_query($conexion, $query);
        echo "Tabla docentes creada.<br>";
    }

    // Insertar algunos datos de prueba si las tablas están vacías
    $query = "SELECT COUNT(*) FROM empleados;";
    $result = pg_query($conexion, $query);
    $count = pg_fetch_result($result, 0, 0);

    if ($count == 0) {
        $query = "
            INSERT INTO empleados (nombre, apellido, departamento, cargo, horario, estado)
            VALUES 
            ('Juan', 'Pérez', 'Administración', 'Director', '8:00-16:00', 'Activo'),
            ('María', 'García', 'Conserjería', 'Conserje', '7:00-15:00', 'Activo');";
        pg_query($conexion, $query);
        echo "Datos de prueba insertados en empleados.<br>";
    }

    $query = "SELECT COUNT(*) FROM docentes;";
    $result = pg_query($conexion, $query);
    $count = pg_fetch_result($result, 0, 0);

    if ($count == 0) {
        $query = "
            INSERT INTO docentes (nombre, apellido, especialidad, horario, estado)
            VALUES 
            ('Carlos', 'López', 'Matemáticas', '8:00-14:00', 'Activo'),
            ('Ana', 'Martínez', 'Ciencias', '9:00-15:00', 'Activo');";
        pg_query($conexion, $query);
        echo "Datos de prueba insertados en docentes.<br>";
    }

    echo "Verificación y creación de tablas completada.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    error_log("Error en verificar_tablas.php: " . $e->getMessage());
} 