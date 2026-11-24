<?php
/**
 * Conexión a MySQL para las páginas del panel de administrador.
 * Separada de api/db.php porque esa devuelve JSON y esto devuelve HTML.
 * Mismos datos de conexión que api/db.php — si cambiás uno, cambiá el otro
 * (cuando pasemos esto a Hostinger conviene unificarlos en un solo lugar).
 */

// Los datos vienen de config.php (ver config.example.php). Si no existe, XAMPP por defecto.
$cfg = is_file(__DIR__ . '/../config.php') ? require __DIR__ . '/../config.php' : [];
$host      = $cfg['host']      ?? 'localhost';
$nombre_bd = $cfg['nombre_bd'] ?? 'confetti';
$usuario   = $cfg['usuario']   ?? 'root';
$clave     = $cfg['clave']     ?? '';
$charset   = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$nombre_bd;charset=$charset";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $usuario, $clave, $opciones);
    // Misma hora de MySQL que en api/db.php (Argentina, UTC-3).
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    error_log('Confetti: no se pudo conectar a la base: ' . $e->getMessage());
    http_response_code(500);
    die(!empty($cfg['debug'])
        ? 'No se pudo conectar a la base de datos: ' . htmlspecialchars($e->getMessage())
        : 'No se pudo conectar a la base de datos.');
}
