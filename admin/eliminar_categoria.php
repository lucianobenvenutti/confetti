<?php
require 'conexion.php';

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: categorias.php?msg=' . urlencode('Categoría inválida.') . '&tipo=error');
    exit;
}

try {
    $pdo->prepare('DELETE FROM categorias WHERE id = ?')->execute([$id]);
    $mensaje = 'Categoría eliminada.';
    $tipo = 'ok';
} catch (PDOException $e) {
    // Tiene productos asociados (restricción de clave foránea) — la desactivamos en vez de borrarla
    $pdo->prepare('UPDATE categorias SET activo = 0 WHERE id = ?')->execute([$id]);
    $mensaje = 'Esa categoría tiene productos asociados, así que no se puede borrar del todo. La desactivamos en su lugar.';
    $tipo = 'error';
}

header('Location: categorias.php?msg=' . urlencode($mensaje) . '&tipo=' . $tipo);
