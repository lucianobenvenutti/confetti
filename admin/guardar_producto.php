<?php
require 'conexion.php';

function volverForm($msg, $id = null) {
    $url = 'producto_form.php?msg=' . urlencode($msg);
    if ($id) $url .= '&id=' . $id;
    header('Location: ' . $url);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$categoriaId = filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT);
$proveedorId = filter_input(INPUT_POST, 'proveedor_id', FILTER_VALIDATE_INT) ?: null;
$tipoVenta = in_array($_POST['tipo_venta'] ?? '', ['unidad', 'peso'], true) ? $_POST['tipo_venta'] : 'unidad';
$precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
$stockActual = filter_input(INPUT_POST, 'stock_actual', FILTER_VALIDATE_FLOAT);
$stockMinimo = filter_input(INPUT_POST, 'stock_minimo', FILTER_VALIDATE_FLOAT) ?: 0;
$sinTacc = isset($_POST['sin_tacc']) ? 1 : 0;
$destacado = isset($_POST['destacado']) ? 1 : 0;
$activo = isset($_POST['activo']) ? 1 : 0;
$codigoBarras = trim($_POST['codigo_barras'] ?? '');
$codigoBarras = $codigoBarras === '' ? null : $codigoBarras;

if ($nombre === '') volverForm('El nombre es obligatorio.', $id);
if (!$categoriaId) volverForm('Elegí una categoría.', $id);
if ($precio === false || $precio === null || $precio < 0) volverForm('El precio no es válido.', $id);
if ($stockActual === false || $stockActual === null || $stockActual < 0) volverForm('El stock actual no es válido.', $id);

if ($codigoBarras !== null) {
    $stmt = $pdo->prepare('SELECT id FROM productos WHERE codigo_barras = ? AND id != ?');
    $stmt->execute([$codigoBarras, $id ?: 0]);
    if ($stmt->fetch()) {
        volverForm('Ese código de barras ya lo tiene otro producto.', $id);
    }
}

if ($id) {
    $pdo->prepare(
        'UPDATE productos SET categoria_id=?, proveedor_id=?, nombre=?, descripcion=?, tipo_venta=?, precio=?,
                stock_actual=?, stock_minimo=?, sin_tacc=?, destacado=?, activo=?, codigo_barras=?
         WHERE id=?'
    )->execute([$categoriaId, $proveedorId, $nombre, $descripcion, $tipoVenta, $precio, $stockActual, $stockMinimo, $sinTacc, $destacado, $activo, $codigoBarras, $id]);
    $productoId = $id;
} else {
    $pdo->prepare(
        'INSERT INTO productos (categoria_id, proveedor_id, nombre, descripcion, tipo_venta, precio, stock_actual, stock_minimo, sin_tacc, destacado, activo, codigo_barras)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([$categoriaId, $proveedorId, $nombre, $descripcion, $tipoVenta, $precio, $stockActual, $stockMinimo, $sinTacc, $destacado, $activo, $codigoBarras]);
    $productoId = (int) $pdo->lastInsertId();
}

// --- Foto (opcional): mismo criterio que ya usábamos antes ---
if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['imagen'];
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $extensionesPermitidas, true)) {
        volverForm('Producto guardado, pero la foto no se subió: formato no permitido (usá JPG, PNG o WEBP).', $productoId);
    }
    if ($archivo['size'] > 3 * 1024 * 1024) {
        volverForm('Producto guardado, pero la foto no se subió: pesa más de 3 MB.', $productoId);
    }
    if (@getimagesize($archivo['tmp_name']) === false) {
        volverForm('Producto guardado, pero el archivo no parece ser una imagen válida.', $productoId);
    }

    $carpetaDestino = __DIR__ . '/../assets/productos/';
    if (!is_dir($carpetaDestino)) mkdir($carpetaDestino, 0755, true);

    $stmt = $pdo->prepare('SELECT imagen FROM productos WHERE id = ?');
    $stmt->execute([$productoId]);
    $imagenVieja = $stmt->fetchColumn();

    $nombreNuevo = 'producto_' . $productoId . '.' . $extension;
    $rutaDestino = $carpetaDestino . $nombreNuevo;

    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        if ($imagenVieja && $imagenVieja !== $nombreNuevo) {
            $rutaVieja = $carpetaDestino . $imagenVieja;
            if (is_file($rutaVieja)) @unlink($rutaVieja);
        }
        $pdo->prepare('UPDATE productos SET imagen = ? WHERE id = ?')->execute([$nombreNuevo, $productoId]);
    }
}

header('Location: productos.php?msg=' . urlencode('Producto guardado.') . '&tipo=ok');
