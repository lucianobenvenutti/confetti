<?php
/**
 * POST /api/crear_pedido.php
 * Body JSON esperado:
 * {
 *   "nombre_cliente": "Martina Gómez",
 *   "telefono_cliente": "3482555123",
 *   "origen": "web",
 *   "items": [
 *     { "tipo": "producto", "id": 3, "cantidad": 2 },
 *     { "tipo": "producto", "id": 6, "cantidad": 0.25 },   // producto por peso, en kg
 *     { "tipo": "combo",    "id": 1, "cantidad": 1 }
 *   ]
 * }
 *
 * La reserva de stock es la parte crítica: cada descuento se hace con
 * UPDATE ... WHERE stock_actual >= cantidad en la misma operación, así
 * si dos personas piden lo último al mismo tiempo, solo una gana la
 * carrera — la otra recibe un error claro en vez de dejar el stock en negativo.
 */

require 'db.php';

$body = json_decode(file_get_contents('php://input'), true);

$nombre   = trim($body['nombre_cliente'] ?? '');
$telefono = trim($body['telefono_cliente'] ?? '');
$origen   = in_array($body['origen'] ?? '', ['web', 'mostrador'], true) ? $body['origen'] : 'web';
$items    = is_array($body['items'] ?? null) ? $body['items'] : [];

if ($nombre === '' || $telefono === '') {
    responder(422, ['error' => 'Falta el nombre o el teléfono.']);
}
if (!$items) {
    responder(422, ['error' => 'El pedido está vacío.']);
}

function responder($codigo, $payload) {
    http_response_code($codigo);
    echo json_encode($payload);
    exit;
}

function generarCodigo(PDO $pdo): string {
    for ($intento = 0; $intento < 5; $intento++) {
        $codigo = 'CF-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        $stmt = $pdo->prepare('SELECT 1 FROM pedidos WHERE codigo = ?');
        $stmt->execute([$codigo]);
        if (!$stmt->fetch()) {
            return $codigo;
        }
    }
    throw new Exception('No se pudo generar un código único.');
}

// Descuenta stock de un producto de forma atómica. Devuelve el producto
// (con nombre y precio) si había stock suficiente, o lanza una excepción si no.
function reservarStock(PDO $pdo, int $productoId, float $cantidad, int $pedidoId): array {
    $stmt = $pdo->prepare('SELECT id, nombre, precio, tipo_venta FROM productos WHERE id = ? AND activo = 1');
    $stmt->execute([$productoId]);
    $producto = $stmt->fetch();

    if (!$producto) {
        throw new Exception("Uno de los productos del pedido ya no está disponible.");
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
         VALUES (?, 'reserva', ?, ?)"
    )->execute([$productoId, -$cantidad, $pedidoId]);

    return $producto;
}

try {
    $pdo->beginTransaction();

    // 1. Creo el pedido "en blanco" para tener un id al que atar los movimientos de stock
    $codigo = generarCodigo($pdo);
    $stmt = $pdo->prepare(
        "INSERT INTO pedidos (codigo, nombre_cliente, telefono_cliente, origen, estado, total, vence_en)
         VALUES (?, ?, ?, ?, 'pendiente', 0, ?)"
    );
    $vence_en = $origen === 'web' ? date('Y-m-d H:i:s', strtotime('+24 hours')) : null;
    $stmt->execute([$codigo, $nombre, $telefono, $origen, $vence_en]);
    $pedidoId = (int) $pdo->lastInsertId();

    $total = 0;
    $detalle = []; // para armar el mensaje de WhatsApp después

    foreach ($items as $item) {
        $tipo = $item['tipo'] ?? '';
        $id = (int) ($item['id'] ?? 0);
        $cantidad = (float) ($item['cantidad'] ?? 0);

        if ($cantidad <= 0) {
            throw new Exception('Cantidad inválida en uno de los ítems.');
        }

        if ($tipo === 'producto') {
            $producto = reservarStock($pdo, $id, $cantidad, $pedidoId);
            $subtotal = $producto['precio'] * $cantidad;

            $pdo->prepare(
                'INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$pedidoId, $id, $cantidad, $producto['precio'], $subtotal]);

            $detalle[] = [
                'nombre' => $producto['nombre'],
                'cantidad' => $cantidad,
                'tipo_venta' => $producto['tipo_venta'],
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;

        } elseif ($tipo === 'combo') {
            $stmt = $pdo->prepare('SELECT id, nombre, precio FROM combos WHERE id = ? AND activo = 1');
            $stmt->execute([$id]);
            $combo = $stmt->fetch();
            if (!$combo) {
                throw new Exception('Ese combo ya no está disponible.');
            }

            $stmt = $pdo->prepare('SELECT producto_id, cantidad FROM combo_productos WHERE combo_id = ?');
            $stmt->execute([$id]);
            $componentes = $stmt->fetchAll();

            // Reservo el stock de CADA producto que compone el combo, multiplicado
            // por cuántos combos se pidieron
            foreach ($componentes as $c) {
                reservarStock($pdo, (int) $c['producto_id'], $c['cantidad'] * $cantidad, $pedidoId);
            }

            $subtotal = $combo['precio'] * $cantidad;
            $pdo->prepare(
                'INSERT INTO pedido_items (pedido_id, combo_id, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([$pedidoId, $id, $cantidad, $combo['precio'], $subtotal]);

            $detalle[] = [
                'nombre' => $combo['nombre'],
                'cantidad' => $cantidad,
                'tipo_venta' => 'unidad',
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;

        } else {
            throw new Exception('Tipo de ítem desconocido.');
        }
    }

    $pdo->prepare('UPDATE pedidos SET total = ? WHERE id = ?')->execute([$total, $pedidoId]);

    $pdo->commit();

    responder(201, [
        'ok' => true,
        'codigo' => $codigo,
        'total' => $total,
        'detalle' => $detalle,
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    responder(409, ['error' => $e->getMessage()]);
}
