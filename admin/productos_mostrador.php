<?php
/**
 * GET admin/productos_mostrador.php
 * Lista de productos para el mostrador, con stock exacto, mínimo y código de barras.
 * Solo con sesión iniciada (dueño o empleado). La API pública no muestra estos datos.
 */
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion(ROLES_TODOS, true);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$productos = $pdo->query(
    'SELECT p.id, p.nombre, p.descripcion, p.tipo_venta, p.precio,
            p.stock_actual, p.stock_minimo, p.imagen, p.sin_tacc, p.destacado, p.codigo_barras,
            c.id AS categoria_id, c.nombre AS categoria_nombre, c.color_acento
     FROM productos p
     JOIN categorias c ON c.id = p.categoria_id
     WHERE p.activo = 1
     ORDER BY c.orden ASC, p.nombre ASC'
)->fetchAll();

foreach ($productos as &$p) {
    $p['disponible'] = (float) $p['stock_actual'] > 0;
}
unset($p);

echo json_encode($productos);
