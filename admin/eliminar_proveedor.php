<?php
require 'conexion.php';

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: proveedores.php?msg=' . urlencode('Proveedor inválido.') . '&tipo=error');
    exit;
}

// La columna productos.proveedor_id tiene ON DELETE SET NULL, así que esto
// nunca debería fallar por clave foránea — los productos simplemente quedan
// sin proveedor asignado. El try/catch queda solo como red de seguridad.
try {
    $pdo->prepare('DELETE FROM proveedores WHERE id = ?')->execute([$id]);
    $mensaje = 'Proveedor eliminado.';
    $tipo = 'ok';
} catch (PDOException $e) {
    $pdo->prepare('UPDATE proveedores SET activo = 0 WHERE id = ?')->execute([$id]);
    $mensaje = 'No se pudo eliminar del todo, así que lo desactivamos.';
    $tipo = 'error';
}

header('Location: proveedores.php?msg=' . urlencode($mensaje) . '&tipo=' . $tipo);
