<?php
/**
 * GET /api/productos.php
 * GET /api/productos.php?categoria_id=2
 * GET /api/productos.php?destacado=1
 * GET /api/productos.php?oferta=1        -> solo los que están en oferta
 *
 * Devuelve productos activos junto con el nombre y color de su categoría.
 * "disponible" se calcula acá para que el frontend no tenga que decidir
 * cuándo algo está sin stock: true si stock_actual > 0.
 */

require 'db.php';
require __DIR__ . '/vencimientos.php';
require __DIR__ . '/precios.php';

// El catálogo siempre muestra stock al día: antes de leerlo, libero lo que ya venció.
liberarPedidosVencidos($pdo);

$sql = 'SELECT
            p.id, p.nombre, p.descripcion, p.tipo_venta, p.precio,
            p.imagen, p.sin_tacc, p.destacado, p.oferta_tipo, p.oferta_valor,
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

if (!empty($_GET['oferta'])) {
    // Pre-filtro barato; el filtro definitivo (descuento válido) se hace abajo con precioConOferta
    $sql .= ' AND p.oferta_tipo IS NOT NULL AND p.oferta_valor > 0';
}

$sql .= ' ORDER BY c.orden ASC, p.nombre ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll();

// Se publica solo si hay o no hay stock, nunca la cantidad exacta ni el código de barras
$soloOfertas = !empty($_GET['oferta']);
foreach ($productos as $i => &$p) {
    $p['disponible'] = (bool) $p['disponible'];

    // "precio" sigue siendo el precio normal (el que se muestra tachado);
    // "precio_oferta" es el que se cobra, y solo viene si de verdad hay un descuento.
    $final = precioConOferta($p);
    if ($final < (float) $p['precio']) {
        $p['precio_oferta'] = $final;
        $p['ahorro'] = round((float) $p['precio'] - $final);
        $p['descuento_pct'] = (int) round($p['ahorro'] / (float) $p['precio'] * 100);
    } else {
        $p['precio_oferta'] = null;
        $p['ahorro'] = 0;
        $p['descuento_pct'] = 0;
        if ($soloOfertas) unset($productos[$i]);
    }
    unset($p['oferta_tipo'], $p['oferta_valor']);
}
unset($p);
$productos = array_values($productos);

echo json_encode($productos);
