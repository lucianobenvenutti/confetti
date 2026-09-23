<?php
/**
 * POST /admin/registrar_venta.php
 * Body JSON: { "items": [{ "tipo": "producto"|"combo", "id": 3, "cantidad": 2 }, ...] }
 *
 * A diferencia de api/crear_pedido.php, esto NO reserva stock para
 * retirar después — es una venta ya resuelta en el momento. Por eso:
 *  - el pedido queda con estado 'entregado' directo, sin pasar por
 *    pendiente/en_preparacion
 *  - origen = 'mostrador'
 *  - vence_en = NULL (no aplica, no hay nada que "retirar después")
 *  - el movimiento de stock queda como 'venta_mostrador', no 'reserva'
 *    (no hay nada que liberar más adelante si nadie viene a buscarlo)
 */

require '../api/db.php'; // misma conexión PDO que ya usa el resto de la API

$body = json_decode(file_get_contents('php://input'), true);
$items = is_array($body['items'] ?? null) ? $body['items'] : [];
$descuento = is_array($body['descuento'] ?? null) ? $body['descuento'] : null;
$montoPagado = isset($body['monto_pagado']) ? (float) $body['monto_pagado'] : null;

function responder($codigo, $payload) {
    http_response_code($codigo);
    echo json_encode($payload);
    exit;
}

if (!$items) {
    responder(422, ['error' => 'No hay nada cargado para vender.']);
}

function generarCodigo(PDO $pdo): string {
    for ($i = 0; $i < 5; $i++) {
        $codigo = 'CF-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        $stmt = $pdo->prepare('SELECT 1 FROM pedidos WHERE codigo = ?');
        $stmt->execute([$codigo]);
        if (!$stmt->fetch()) return $codigo;
    }
    throw new Exception('No se pudo generar un código único.');
}

function descontarStock(PDO $pdo, int $productoId, float $cantidad, int $pedidoId): array {
    $stmt = $pdo->prepare('SELECT id, nombre, precio, tipo_venta FROM productos WHERE id = ? AND activo = 1');
    $stmt->execute([$productoId]);
    $producto = $stmt->fetch();
    if (!$producto) {
        throw new Exception('Uno de los productos ya no está disponible.');
    }

    $stmt = $pdo->prepare(
        'UPDATE productos SET stock_actual = stock_actual - ?
         WHERE id = ? AND stock_actual >= ?'
    );
    $stmt->execute([$cantidad, $productoId, $cantidad]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("No queda stock suficiente de \"{$producto['nombre']}\".");
    }

    $pdo->prepare(
        "INSERT INTO movimientos_stock (producto_id, tipo, cantidad, pedido_id)
         VALUES (?, 'venta_mostrador', ?, ?)"
    )->execute([$productoId, -$cantidad, $pedidoId]);

    return $producto;
}

try {
    $pdo->beginTransaction();

    $codigo = generarCodigo($pdo);
    $stmt = $pdo->prepare(
        "INSERT INTO pedidos (codigo, nombre_cliente, telefono_cliente, origen, estado, total, vence_en)
         VALUES (?, 'Venta mostrador', '-', 'mostrador', 'entregado', 0, NULL)"
    );
    $stmt->execute([$codigo]);
    $pedidoId = (int) $pdo->lastInsertId();

    $subtotalGeneral = 0;

    foreach ($items as $item) {
        $tipo = $item['tipo'] ?? '';
        $id = (int) ($item['id'] ?? 0);
        $cantidad = (float) ($item['cantidad'] ?? 0);

        if ($cantidad <= 0) {
            throw new Exception('Cantidad inválida en uno de los ítems.');
        }

        if ($tipo === 'producto') {
            $producto = descontarStock($pdo, $id, $cantidad, $pedidoId);
            $subtotal = $producto['precio'] * $cantidad;

            $pdo->prepare(
                'INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$pedidoId, $id, $cantidad, $producto['precio'], $subtotal]);

            $subtotalGeneral += $subtotal;

        } elseif ($tipo === 'combo') {
            $stmt = $pdo->prepare('SELECT id, nombre, precio FROM combos WHERE id = ? AND activo = 1');
            $stmt->execute([$id]);
            $combo = $stmt->fetch();
            if (!$combo) {
                throw new Exception('Ese combo ya no está disponible.');
            }

            $stmt = $pdo->prepare('SELECT producto_id, cantidad FROM combo_productos WHERE combo_id = ?');
            $stmt->execute([$id]);
            foreach ($stmt->fetchAll() as $c) {
                descontarStock($pdo, (int) $c['producto_id'], $c['cantidad'] * $cantidad, $pedidoId);
            }

            $subtotal = $combo['precio'] * $cantidad;
            $pdo->prepare(
                'INSERT INTO pedido_items (pedido_id, combo_id, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$pedidoId, $id, $cantidad, $combo['precio'], $subtotal]);

            $subtotalGeneral += $subtotal;

        } else {
            throw new Exception('Tipo de ítem desconocido.');
        }
    }

    // --- Descuento (recalculado acá, nunca confiando en un total que mande el navegador) ---
    $montoDescuento = 0;
    $descripcionDescuento = null;

    if ($descuento && isset($descuento['tipo'], $descuento['valor'])) {
        $valor = (float) $descuento['valor'];
        if ($descuento['tipo'] === 'porcentaje' && $valor > 0) {
            $valor = min($valor, 100);
            $montoDescuento = round($subtotalGeneral * $valor / 100, 2);
            $descripcionDescuento = "Descuento: {$valor}% (-" . number_format($montoDescuento, 0, ',', '.') . ")";
        } elseif ($descuento['tipo'] === 'monto' && $valor > 0) {
            $montoDescuento = min($valor, $subtotalGeneral);
            $descripcionDescuento = "Descuento: -$" . number_format($montoDescuento, 0, ',', '.');
        }
    }

    $totalFinal = round($subtotalGeneral - $montoDescuento, 2);

    // --- Vuelto (informativo, no afecta el total cobrado) ---
    $vuelto = null;
    $descripcionPago = null;
    if ($montoPagado !== null && $montoPagado > 0) {
        $vuelto = round($montoPagado - $totalFinal, 2);
        $descripcionPago = "Pagó $" . number_format($montoPagado, 0, ',', '.')
            . " · Vuelto $" . number_format($vuelto, 0, ',', '.');
    }

    $notas = implode(' · ', array_filter([$descripcionDescuento, $descripcionPago]));

    $pdo->prepare('UPDATE pedidos SET total = ?, notas = ? WHERE id = ?')
        ->execute([$totalFinal, $notas ?: null, $pedidoId]);

    $pdo->commit();

    responder(201, [
        'ok' => true,
        'codigo' => $codigo,
        'subtotal' => $subtotalGeneral,
        'descuento_monto' => $montoDescuento,
        'total' => $totalFinal,
        'monto_pagado' => $montoPagado,
        'vuelto' => $vuelto,
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    responder(409, ['error' => $e->getMessage()]);
}