<?php
function requireRol(array $rolesPermitidos, string $redirectUrl = "../publico/login.php"): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $rolActual = $_SESSION['tipo_nav'] ?? 'publico';
    if (!in_array($rolActual, $rolesPermitidos, true)) {
        header("Location: " . $redirectUrl);
        exit;
    }
}