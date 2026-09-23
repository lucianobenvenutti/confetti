<?php
/**
 * Conexión a MySQL para las páginas del panel de administrador.
 * Separada de api/db.php porque esa devuelve JSON y esto devuelve HTML.
 * Mismos datos de conexión que api/db.php — si cambiás uno, cambiá el otro
 * (cuando pasemos esto a Hostinger conviene unificarlos en un solo lugar).
 */

$host      = 'localhost';
$nombre_bd = 'confetti';
$usuario   = 'root';
$clave     = '';
$charset   = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$nombre_bd;charset=$charset";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $usuario, $clave, $opciones);
} catch (PDOException $e) {
    die('No se pudo conectar a la base de datos: ' . $e->getMessage());
}