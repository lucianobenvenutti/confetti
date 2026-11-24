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
require __DIR__ . '/vencimientos.php';

// El catálogo siempre muestra stock al día: antes de leerlo, libero lo que ya venció.
liberarPedidosVencidos($pdo);

$sql = 'SELECT
            p.id, p.nombre, p.descripcion, p.tipo_venta, p.precio,
            p.imagen, p.sin_tacc, p.destacado,
            (p.stock_actual > 0) AS disponible,
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

if (!empty($_GET['buscar'])) {
    $sql .= ' AND p.nombre LIKE :buscar';
    // Los comodines % y _ que escriba el cliente se toman como letras comunes
    $params['buscar'] = '%' . addcslashes(mb_substr((string) $_GET['buscar'], 0, 60), '%_\\') . '%';
}

$sql .= ' ORDER BY c.orden ASC, p.nombre ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

// Se publica solo si hay o no hay stock, nunca la cantidad exacta ni el código de barras
foreach ($productos as &$p) {
    $p['disponible'] = (bool) $p['disponible'];
}
unset($p);

echo json_encode($productos);
