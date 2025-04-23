<?php
session_start();

/**
 * Verifica si existe una sesión activa
 * @return bool
 */
function verificarSesion(): bool {
    return isset($_SESSION['empleado_id']) && !empty($_SESSION['empleado_id']);
}

/**
 * Obtiene el ID del empleado de la sesión actual
 * @return int|null
 */
function obtenerEmpleadoId(): ?int {
    return $_SESSION['empleado_id'] ?? null;
} 