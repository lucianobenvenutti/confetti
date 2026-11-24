<?php
/**
 * GET /api/seguimiento.php?codigo=CF-A1B2
 * Devuelve el estado de un pedido y sus ítems, sin necesitar login.
 * No devuelve el teléfono ni el nombre del cliente, ni datos de otros pedidos.
 */

require 'db.php';
require __DIR__ . '/vencimientos.php';
require __DIR__ . '/limites.php';

// Freno a quien prueba códigos al azar para espiar pedidos ajenos
if (limiteSuperado($pdo, 'seguimiento', 40, 600)) {
    demasiadosIntentos('Demasiadas consultas seguidas. Esperá unos minutos.');
}
registrarUso($pdo, 'seguimiento');

// Si el plazo de este pedido ya pasó, que se vea como cancelado en el momento.
liberarPedidosVencidos($pdo);

$codigo = strtoupper(trim($_GET['codigo'] ?? ''));

if ($codigo === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Ingresá un código de pedido.']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT codigo, origen, estado, total, metodo_pago, (comprobante IS NOT NULL) AS tiene_comprobante, creado_en, vence_en,
            UNIX_TIMESTAMP() - UNIX_TIMESTAMP(creado_en) AS seg_transcurridos,
            UNIX_TIMESTAMP(vence_en) - UNIX_TIMESTAMP() AS seg_restantes
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
