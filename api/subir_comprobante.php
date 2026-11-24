<?php
/**
 * POST /api/subir_comprobante.php   (multipart/form-data)
 *   codigo:  código del pedido (ej: CF-A1B2C3D4)
 *   archivo: foto (JPG/PNG/WEBP) o PDF del comprobante, hasta 5 MB
 *
 * Solo sirve para pedidos web pagados por transferencia que sigan activos.
 * El archivo se guarda con un nombre al azar en comprobantes/ (carpeta cerrada al público:
 * solo el panel, con sesión, puede mostrarlo). Cuenta como "uso" para el freno por IP.
 */
require 'db.php';
require __DIR__ . '/limites.php';
require __DIR__ . '/../admin/imagenes.php';

const MAX_BYTES = 5 * 1024 * 1024;

function salir($codigo, $payload) {
    http_response_code($codigo);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    salir(405, ['error' => 'Método no permitido.']);
}

if (limiteSuperado($pdo, 'comprobante', 10, 600)) {
    demasiadosIntentos('Demasiados intentos seguidos. Esperá unos minutos.');
}
registrarUso($pdo, 'comprobante');

$codigo = strtoupper(trim((string) ($_POST['codigo'] ?? '')));
if (!preg_match('/^CF-[A-Z0-9]{4,8}$/', $codigo)) {
    salir(422, ['error' => 'Código de pedido inválido.']);
}

// ---- Archivo recibido ----
$archivo = $_FILES['archivo'] ?? null;
if (!$archivo || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
    salir(422, ['error' => 'No se recibió ningún archivo.']);
}
if ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE || $archivo['size'] > MAX_BYTES) {
    salir(413, ['error' => 'El archivo pesa demasiado (máximo 5 MB).']);
}
if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
    salir(422, ['error' => 'No se pudo recibir el archivo. Probá de nuevo.']);
}

// El tipo se decide por el CONTENIDO del archivo, no por su nombre ni por lo que diga el navegador
$cabecera = file_get_contents($archivo['tmp_name'], false, null, 0, 12);
if (strncmp($cabecera, "\xFF\xD8\xFF", 3) === 0) {
    $ext = 'jpg';
} elseif (strncmp($cabecera, "\x89PNG\r\n\x1A\n", 8) === 0) {
    $ext = 'png';
} elseif (strncmp($cabecera, 'RIFF', 4) === 0 && substr($cabecera, 8, 4) === 'WEBP') {
    $ext = 'webp';
} elseif (strncmp($cabecera, '%PDF-', 5) === 0) {
    $ext = 'pdf';
} else {
    salir(415, ['error' => 'Subí una foto (JPG, PNG, WEBP) o un PDF.']);
}

// ---- El pedido tiene que existir, ser web, de transferencia y seguir activo ----
$stmt = $pdo->prepare("SELECT id, metodo_pago, estado, comprobante FROM pedidos WHERE codigo = ? AND origen = 'web'");
$stmt->execute([$codigo]);
$pedido = $stmt->fetch();
if (!$pedido) {
    salir(404, ['error' => 'No encontramos ese pedido.']);
}
if ($pedido['metodo_pago'] !== 'transferencia') {
    salir(409, ['error' => 'Este pedido no es de transferencia.']);
}
if (in_array($pedido['estado'], ['entregado', 'cancelado'], true)) {
    salir(409, ['error' => 'Este pedido ya está cerrado.']);
}

// ---- Guardar ----
$carpeta = __DIR__ . '/../comprobantes';
if (!is_dir($carpeta) && !@mkdir($carpeta, 0755, true)) {
    error_log('Confetti: no se pudo crear la carpeta comprobantes/');
    salir(500, ['error' => 'No se pudo guardar el comprobante. Mandalo por WhatsApp.']);
}
$nombreArchivo = 'c_' . bin2hex(random_bytes(12)) . '.' . $ext;
$destino = $carpeta . '/' . $nombreArchivo;

$ok = $ext === 'pdf'
    ? move_uploaded_file($archivo['tmp_name'], $destino)
    : guardarImagenOptimizada($archivo['tmp_name'], $destino, $ext, 1600);   // se achica y se limpia (sin datos ocultos)
if (!$ok || !is_file($destino)) {
    error_log('Confetti: falló al guardar el comprobante');
    salir(500, ['error' => 'No se pudo guardar el comprobante. Mandalo por WhatsApp.']);
}

$pdo->prepare('UPDATE pedidos SET comprobante = ? WHERE id = ?')->execute([$nombreArchivo, $pedido['id']]);

// Si ya había uno anterior, se reemplaza (borro el viejo)
if (!empty($pedido['comprobante']) && preg_match('/^c_[a-f0-9]{24}\.(jpg|png|webp|pdf)$/', $pedido['comprobante'])) {
    @unlink($carpeta . '/' . $pedido['comprobante']);
}

salir(201, ['ok' => true]);
