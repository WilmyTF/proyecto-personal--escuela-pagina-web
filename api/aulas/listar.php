<?php
require_once '../../includes/conexion.php';
verificarConexion();

header('Content-Type: application/json');

try {
    $stmt = $conn->query("SELECT * FROM aulas ORDER BY id ASC");
    $aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($aulas);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 