<?php
/**
 * GET /api/productos.php
 * GET /api/productos.php?categoria_id=2
 * GET /api/productos.php?destacado=1
 *
 * Devuelve productos activos junto con el nombre y color de su categoría.
 * "disponible" se calcula acá para que el frontend no tenga que decidir
 * cuándo algo está sin stock: true si stock_actual > 0.
 */

require 'db.php';

$sql = 'SELECT
            p.id, p.nombre, p.descripcion, p.tipo_venta, p.precio,
            p.stock_actual, p.stock_minimo, p.imagen, p.sin_tacc, p.destacado, p.codigo_barras,
            c.id AS categoria_id, c.nombre AS categoria_nombre, c.color_acento
        FROM productos p
        JOIN categorias c ON c.id = p.categoria_id
        WHERE p.activo = 1';

$params = [];

if (!empty($_GET['categoria_id'])) {
    $sql .= ' AND p.categoria_id = :categoria_id';
    $params['categoria_id'] = $_GET['categoria_id'];
}

if (!empty($_GET['destacado'])) {
    $sql .= ' AND p.destacado = 1';
}

$sql .= ' ORDER BY c.orden ASC, p.nombre ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

// Agrego el campo calculado "disponible" sin tocar la fila original de la base
foreach ($productos as &$p) {
    $p['disponible'] = (float) $p['stock_actual'] > 0;
}

echo json_encode($productos);
