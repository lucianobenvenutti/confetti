<?php
/**
 * POST: suma al stock de un producto la mercadería que acaba de entrar.
 *   producto_id  id del producto
 *   cantidad     unidades (o kg, si el producto se vende por peso)
 *
 * Además de sumar, deja un movimiento 'entrada_stock' en la bitácora, para
 * saber después cuándo y cuánto se repuso. Todo va en una sola transacción.
 */

require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion([ROL_DUENO]);

function volver($mensaje, $tipo, $productoId = null) {
    $url = 'productos.php?msg=' . urlencode($mensaje) . '&tipo=' . $tipo;
    if ($productoId) {
        $url .= '#prod-' . $productoId; // vuelve parado en la fila del producto
    }
    header('Location: ' . $url);
    exit;
}

// 12 -> "12"   |   2.5 de un producto por peso -> "2,5 kg"
function formatoCantidad($valor, $tipoVenta) {
    if ($tipoVenta === 'peso') {
        $texto = rtrim(rtrim(number_format((float) $valor, 3, ',', '.'), '0'), ',');
        return $texto . ' kg';
    }
    return (string) (int) round((float) $valor);
}

$productoId = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
$texto = str_replace(',', '.', trim($_POST['cantidad'] ?? ''));

if (!$productoId) {
    volver('Producto inválido.', 'error');
}
if ($texto === '' || !is_numeric($texto)) {
    volver('Ingresá la cantidad que entró, en números.', 'error', $productoId);
}

$cantidad = round((float) $texto, 3);

if ($cantidad <= 0) {
    volver('La cantidad tiene que ser mayor a cero.', 'error', $productoId);
}
if ($cantidad > 100000) {
    volver('Esa cantidad es demasiado grande. Revisá que no sea un error de tipeo.', 'error', $productoId);
}

$pdo->beginTransaction();
try {
    // FOR UPDATE: si en este instante se está vendiendo o editando el mismo
    // producto, espera su turno y no se pisan los números
    $stmt = $pdo->prepare('SELECT id, nombre, tipo_venta FROM productos WHERE id = ? FOR UPDATE');
    $stmt->execute([$productoId]);
    $producto = $stmt->fetch();

    if (!$producto) {
        $pdo->rollBack();
        volver('Ese producto no existe.', 'error');
    }

    if ($producto['tipo_venta'] === 'unidad' && abs($cantidad - round($cantidad)) > 0.0000001) {
        $pdo->rollBack();
        volver('"' . $producto['nombre'] . '" se cuenta por unidad: ingresá un número entero.', 'error', $productoId);
    }

    $pdo->prepare('UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?')
        ->execute([$cantidad, $productoId]);

    $pdo->prepare(
        "INSERT INTO movimientos_stock (producto_id, tipo, cantidad, usuario_id) VALUES (?, 'entrada_stock', ?, ?)"
    )->execute([$productoId, $cantidad, $usuarioActual['id']]);

    $stmt = $pdo->prepare('SELECT stock_actual FROM productos WHERE id = ?');
    $stmt->execute([$productoId]);
    $stockNuevo = $stmt->fetchColumn();

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    volver('No se pudo registrar el ingreso: ' . $e->getMessage(), 'error', $productoId);
}

volver(
    'Se sumaron ' . formatoCantidad($cantidad, $producto['tipo_venta']) . ' a "' . $producto['nombre'] . '". '
    . 'Stock actual: ' . formatoCantidad($stockNuevo, $producto['tipo_venta']) . '.',
    'ok',
    $productoId
);
