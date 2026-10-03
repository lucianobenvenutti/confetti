<?php
require 'conexion.php';

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: productos.php?msg=' . urlencode('Producto inválido.') . '&tipo=error');
    exit;
}

$stmt = $pdo->prepare('SELECT imagen FROM productos WHERE id = ?');
$stmt->execute([$id]);
$imagen = $stmt->fetchColumn();

try {
    $pdo->prepare('DELETE FROM productos WHERE id = ?')->execute([$id]);

    // Si se pudo borrar de la base, borramos también el archivo de la foto
    if ($imagen) {
        $ruta = __DIR__ . '/../assets/productos/' . $imagen;
        if (is_file($ruta)) @unlink($ruta);
    }

    $mensaje = 'Producto eliminado.';
    $tipo = 'ok';
} catch (PDOException $e) {
    // Tiene ventas, pedidos o combos asociados (restricción de clave foránea) — lo desactivamos
    $pdo->prepare('UPDATE productos SET activo = 0 WHERE id = ?')->execute([$id]);
    $mensaje = 'Ese producto tiene ventas o combos asociados, así que no se puede borrar del todo. Lo desactivamos en su lugar (ya no aparece en la página).';
    $tipo = 'error';
}

header('Location: productos.php?msg=' . urlencode($mensaje) . '&tipo=' . $tipo);
