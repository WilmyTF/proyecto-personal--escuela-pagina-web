<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once 'includes/conexion.php';


function reiniciarTablasYSecuencias($conn) {
   
    $tablas = [
        'personal_area',
        'responsables_area',
        'subdivisiones_area',
        'areas_mapa',
        'mapas',
        'tipos_area',
        '"Permisos"'
    ];
    

    $secuencias = [
        'mapas_id_seq',
        'areas_mapa_id_seq',
        'subdivisiones_area_id_seq',
        'responsables_area_id_seq',
        'personal_area_id_seq',
        'tipos_area_id_seq'
        
    ];
    
    try {
     
        $conn->beginTransaction();
        
   
        $conn->exec('SET session_replication_role = replica;');
        
        
        $conn->exec('TRUNCATE TABLE "usuario-permiso" CASCADE');
        echo "<p class='success'>Tabla usuario-permiso truncada correctamente</p>";
        
    
        foreach ($tablas as $tabla) {
            $conn->exec("TRUNCATE TABLE $tabla CASCADE");
            echo "<p class='success'>Tabla $tabla truncada correctamente</p>";
        }
        
     
        foreach ($secuencias as $secuencia) {
            $conn->exec("ALTER SEQUENCE IF EXISTS $secuencia RESTART WITH 1");
            echo "<p class='success'>Secuencia $secuencia reiniciada correctamente</p>";
        }
        
        $conn->exec('ALTER TABLE "Permisos" ALTER COLUMN id RESTART WITH 1');
        echo "<p class='success'>Secuencia de identidad de la tabla Permisos reiniciada correctamente</p>";
    
        $conn->exec('SET session_replication_role = DEFAULT;');
        

        $conn->commit();
        
        return true;
    } catch (PDOException $e) {
        
        $conn->rollBack();
        echo "<p class='error'>Error al reiniciar tablas y secuencias: " . $e->getMessage() . "</p>";
        return false;
    }
}


try {
    verificarConexion();
    
   
    if (!$conn) {
        throw new Exception("No hay conexión a la base de datos disponible");
    }
    
    echo "<html><head><title>Inicialización del Sistema Escolar</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        h1 { color: #3f51b5; }
        .container { max-width: 800px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .btn { display: inline-block; background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 20px; }
        pre { background-color: #f0f0f0; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .sql-query { font-size: 0.9em; max-height: 100px; overflow-y: auto; margin-bottom: 20px; }
        .info-box { background-color: #e8f4fd; padding: 15px; border-left: 5px solid #3498db; margin-bottom: 20px; }
    </style>";
    echo "</head><body><div class='container'>";
    echo "<h1>Inicialización del Sistema Escolar</h1>";
    
    echo "<div class='info-box'>";
    echo "<h3>¿Qué hace este script?</h3>";
    echo "<p>Este inicializador crea los permisos básicos del sistema y usuarios de prueba con sus respectivos roles:</p>";
    echo "<ul>";
    echo "<li><strong>Estudiante:</strong> Acceso básico al módulo de estudiantes</li>";
    echo "<li><strong>Profesor:</strong> Acceso al módulo de profesores y gestión de clases</li>";
    echo "<li><strong>Empleado/Admin:</strong> Acceso completo a las funciones administrativas</li>";
    echo "</ul>";
    echo "<p>La contraseña para todos los usuarios es: <strong>password123</strong></p>";
    echo "</div>";
 
    echo "<h2>Reiniciando tablas y secuencias</h2>";
    reiniciarTablasYSecuencias($conn);
    

    $sql_files = [
        'sql/inicializador_directo.sql'
    ];
    
    foreach ($sql_files as $sql_file) {
        echo "<h2>Ejecutando: " . htmlspecialchars($sql_file) . "</h2>";
        
        if (!file_exists($sql_file)) {
            echo "<p class='error'>Error: El archivo SQL no existe: " . htmlspecialchars($sql_file) . "</p>";
            continue;
        }
        
  
        $sql = file_get_contents($sql_file);
        
      
        try {
            
            $queries = [];
            $temp_queries = explode(';', $sql);
            foreach ($temp_queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    $queries[] = $query;
                }
            }
            
            foreach ($queries as $query) {
                try {
                    if ($conn) {
                      
                        echo "<div class='sql-query'><pre>" . htmlspecialchars($query) . "</pre></div>";
                        
                        $stmt = $conn->prepare($query);
                        if ($stmt) {
                            $stmt->execute();
                            
                            if (stripos($query, 'SELECT') === 0) {
                             
                                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($result as $row) {
                                    foreach ($row as $key => $value) {
                                        echo "<p class='success'>" . nl2br(htmlspecialchars($value)) . "</p>";
                                    }
                                }
                            } else {
                              
                                $count = $stmt->rowCount();
                                echo "<p class='success'>Consulta ejecutada correctamente. Filas afectadas: $count</p>";
                            }
                        } else {
                            echo "<p class='error'>Error al preparar la consulta.</p>";
                        }
                    } else {
                        throw new Exception("No hay conexión a la base de datos disponible");
                    }
                } catch (PDOException $e) {
                    echo "<p class='error'>Error al ejecutar consulta: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            }
            
            echo "<p class='success'>Archivo SQL procesado correctamente: " . htmlspecialchars($sql_file) . "</p>";
        } catch (Exception $e) {
            echo "<p class='error'>Error al procesar archivo SQL: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    try {
        $stmt = $conn->prepare('UPDATE "Permisos" SET estado = 1 WHERE estado IS NULL OR estado = 0');
        $stmt->execute();
        $count = $stmt->rowCount();
        echo "<p class='success'>Permisos actualizados a estado activo. Filas afectadas: $count</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>Error al actualizar el estado de los permisos: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
  
    echo "<div style='margin-top: 20px; padding: 15px; background-color: #dff0d8; border-radius: 5px;'>";
    echo "<h2 style='color: #3c763d;'>Inicialización Completada</h2>";
    echo "<p>La base de datos ha sido inicializada correctamente con usuarios de prueba:</p>";
    echo "<ul>";
    echo "<li><strong>Estudiante:</strong> estudiante@test.com / password123</li>";
    echo "<li><strong>Profesor:</strong> profesor@test.com / password123</li>";
    echo "<li><strong>Empleado/Admin:</strong> empleado@test.com / password123</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<a href='login.php' class='btn'>Ir a la página de login</a>";
    echo "</div></body></html>";
    
} catch (Exception $e) {
    die("<div style='color: red;'>Error: " . $e->getMessage() . "</div>");
}
?> 