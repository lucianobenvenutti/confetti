<?php
/**
 * Conexión a MySQL vía PDO.
 * En local (XAMPP) estos datos ya funcionan tal cual.
 * Cuando subas esto a Hostinger, cambiá host/nombre_bd/usuario/clave
 * por los datos que te da el panel de Hostinger para tu base MySQL.
 */

// Los datos vienen de config.php (ver config.example.php). Si no existe, XAMPP por defecto.
$cfg = is_file(__DIR__ . '/../config.php') ? require __DIR__ . '/../config.php' : [];
$host      = $cfg['host']      ?? 'localhost';
$nombre_bd = $cfg['nombre_bd'] ?? 'confetti';
$usuario   = $cfg['usuario']   ?? 'root';
$clave     = $cfg['clave']     ?? '';
$debug     = !empty($cfg['debug']);
$charset   = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$nombre_bd;charset=$charset";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $usuario, $clave, $opciones);
    // Fijamos la hora de MySQL en Argentina (UTC-3, sin horario de verano) para esta
    // conexión. Así NOW(), CURDATE() y las fechas de los pedidos son siempre las
    // mismas, sin importar cómo venga configurado el servidor (XAMPP, Hostinger...).
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    error_log('Confetti: no se pudo conectar a la base: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $respuesta = ['error' => 'No se pudo conectar a la base de datos'];
    if ($debug) $respuesta['detalle'] = $e->getMessage();   // solo con debug=true en config.php
    echo json_encode($respuesta);
    exit;
}

// Headers comunes para todos los endpoints que incluyan este archivo.
// Sin "Access-Control-Allow-Origin": la web y la API están en el mismo sitio, así que
// ninguna otra página de internet puede leer ni disparar pedidos desde el navegador de alguien.
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
