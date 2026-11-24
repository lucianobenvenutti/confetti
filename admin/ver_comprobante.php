<?php
/**
 * GET admin/ver_comprobante.php?id=PEDIDO_ID
 * Muestra el comprobante de transferencia de un pedido. Solo con sesión iniciada
 * (dueño o empleado). Los archivos viven en comprobantes/, que está cerrada al público.
 */
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion(ROLES_TODOS);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$nombre = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT comprobante FROM pedidos WHERE id = ?');
    $stmt->execute([$id]);
    $nombre = $stmt->fetchColumn();
}

// El nombre sale de la base, pero igual se valida su forma (nunca puede salirse de la carpeta)
if (!$nombre || !preg_match('/^c_[a-f0-9]{24}\.(jpg|png|webp|pdf)$/', $nombre)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Este pedido no tiene comprobante.';
    exit;
}
$ruta = __DIR__ . '/../comprobantes/' . $nombre;
if (!is_file($ruta)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'El archivo del comprobante no está en el servidor.';
    exit;
}

$tipos = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
$ext = pathinfo($nombre, PATHINFO_EXTENSION);

header('Content-Type: ' . $tipos[$ext]);
header('Content-Length: ' . filesize($ruta));
header('Content-Disposition: inline; filename="comprobante-' . (int) $id . '.' . $ext . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
// El panel manda "no se puede meter en un marco" para todo; acá hace falta la excepción
// (solo para este mismo sitio) porque el visor de Pedidos muestra el PDF dentro de un marco.
header_remove('X-Frame-Options');
header('X-Frame-Options: SAMEORIGIN');
if ($ext === 'pdf') {
    header("Content-Security-Policy: frame-ancestors 'self'");
} else {
    header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; frame-ancestors 'self'");
}
readfile($ruta);
