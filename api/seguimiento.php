<?php
/**
 * GET /api/seguimiento.php?codigo=CF-A1B2
 * Devuelve el estado de un pedido y sus ítems, sin necesitar login.
 * No devuelve el teléfono del cliente ni datos de otros pedidos.
 */

require 'db.php';

$codigo = strtoupper(trim($_GET['codigo'] ?? ''));

if ($codigo === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Ingresá un código de pedido.']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT codigo, nombre_cliente, origen, estado, total, creado_en, vence_en
     FROM pedidos WHERE codigo = ?'
);
$stmt->execute([$codigo]);
$pedido = $stmt->fetch();

if (!$pedido) {
    http_response_code(404);
    echo json_encode(['error' => 'No encontramos ningún pedido con ese código.']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT pi.cantidad, pi.subtotal,
            COALESCE(pr.nombre, co.nombre) AS nombre_item,
            pr.tipo_venta
     FROM pedido_items pi
     LEFT JOIN productos pr ON pr.id = pi.producto_id
     LEFT JOIN combos co ON co.id = pi.combo_id
     WHERE pi.pedido_id = (SELECT id FROM pedidos WHERE codigo = ?)'
);
$stmt->execute([$codigo]);
$pedido['items'] = $stmt->fetchAll();

echo json_encode($pedido);
