<?php
/**
 * POST /admin/subir_imagen.php
 * Recibe una foto para un producto puntual, la guarda en assets/productos/
 * con un nombre fijo (producto_{id}.ext) y actualiza la columna "imagen".
 *
 * Nombrar el archivo con el id del producto evita dos problemas de una:
 * - nombres raros/repetidos que suben distintos empleados
 * - fotos "huérfanas" que nadie sabe a qué producto pertenecen
 */

require 'conexion.php';

function volver($mensaje, $tipo = 'error') {
    header('Location: productos.php?msg=' . urlencode($mensaje) . '&tipo=' . $tipo);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    volver('Acceso inválido.');
}

$producto_id = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
if (!$producto_id) {
    volver('No se indicó el producto.');
}

if (empty($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    volver('No se pudo leer el archivo. Probá de nuevo.');
}

$archivo = $_FILES['imagen'];

// --- Validaciones básicas ---
$extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

if (!in_array($extension, $extensionesPermitidas, true)) {
    volver('Formato no permitido. Usá JPG, PNG o WEBP.');
}

$tamanioMaximo = 3 * 1024 * 1024; // 3 MB
if ($archivo['size'] > $tamanioMaximo) {
    volver('La imagen pesa demasiado (máximo 3 MB).');
}

$infoImagen = @getimagesize($archivo['tmp_name']);
if ($infoImagen === false) {
    volver('El archivo no parece ser una imagen válida.');
}

// Confirmar que el producto existe antes de guardar nada
$stmt = $pdo->prepare('SELECT id, imagen FROM productos WHERE id = ?');
$stmt->execute([$producto_id]);
$producto = $stmt->fetch();

if (!$producto) {
    volver('Ese producto no existe.');
}

// --- Guardar el archivo ---
$carpetaDestino = __DIR__ . '/../assets/productos/';
if (!is_dir($carpetaDestino)) {
    mkdir($carpetaDestino, 0755, true);
}

$nombreNuevo = 'producto_' . $producto_id . '.' . $extension;
$rutaDestino = $carpetaDestino . $nombreNuevo;

if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
    volver('No se pudo guardar la imagen en el servidor.');
}

// Si la foto anterior tenía otra extensión (ej: cambiaron de .png a .jpg),
// borramos el archivo viejo para no dejar basura acumulada.
if (!empty($producto['imagen']) && $producto['imagen'] !== $nombreNuevo) {
    $rutaVieja = $carpetaDestino . $producto['imagen'];
    if (is_file($rutaVieja)) {
        @unlink($rutaVieja);
    }
}

// --- Actualizar la base ---
$stmt = $pdo->prepare('UPDATE productos SET imagen = ? WHERE id = ?');
$stmt->execute([$nombreNuevo, $producto_id]);

volver('Foto actualizada correctamente.', 'ok');