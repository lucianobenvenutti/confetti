<?php
/**
 * Conexión a MySQL vía PDO.
 * En local (XAMPP) estos datos ya funcionan tal cual.
 * Cuando subas esto a Hostinger, cambiá host/nombre_bd/usuario/clave
 * por los datos que te da el panel de Hostinger para tu base MySQL.
 */

$host        = 'localhost';
$nombre_bd   = 'confetti';
$usuario     = 'root';
$clave       = '';       // en XAMPP por defecto no tiene clave
$charset     = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$nombre_bd;charset=$charset";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $usuario, $clave, $opciones);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf8');
    echo json_encode([
        'error' => 'No se pudo conectar a la base de datos',
        'detalle' => $e->getMessage(), // sacar esta línea cuando esté en producción
    ]);
    exit;
}

// Headers comunes para todos los endpoints que incluyan este archivo
header('Content-Type: application/json; charset=utf8');
header('Access-Control-Allow-Origin: *'); // durante desarrollo local; ajustar en producción si hace falta
