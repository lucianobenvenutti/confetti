<?php
/**
 * POST /admin/cambiar_estado.php
 * accion = "avanzar"  -> mueve el pedido al siguiente estado lógico
 * accion = "cancelar" -> cancela el pedido y libera el stock que tenía reservado
 *
 * La liberación de stock no adivina nada: busca en movimientos_stock los
 * movimientos de tipo 'reserva' que quedaron atados a este pedido y los
 * revierte. Por eso funciona igual para productos sueltos o para combos:
 * lo que se reservó por producto es lo que se devuelve, sin importar si
 * ese producto llegó al pedido suelto o como parte de un combo.
 */

require 'conexion.php';

function volver($mensaje, $tipo = 'error', $whatsapp = null) {
    $url = 'pedidos.php?msg=' . urlencode($mensaje) . '&tipo=' . $tipo;
    if ($whatsapp) {
        $url .= '&whatsapp=' . urlencode($whatsapp);
    }
    header('Location: ' . $url);
    exit;
}

// Mismo criterio que usa index.html para armar el link de wa.me: si el
// teléfono no viene con código de país, se lo agregamos (Argentina, celular).
function linkWhatsapp($telefono, $mensaje) {
    $soloNumeros = preg_replace('/\D/', '', $telefono);
    if (substr($soloNumeros, 0, 2) !== '54') {
        $soloNumeros = '549' . $soloNumeros;
    }
    return 'https://wa.me/' . $soloNumeros . '?text=' . urlencode($mensaje);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    volver('Acceso inválido.');
}

$pedido_id = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);
$accion    = $_POST['accion'] ?? '';

if (!$pedido_id || !in_array($accion, ['avanzar', 'cancelar'], true)) {
    volver('Solicitud inválida.');
}

$stmt = $pdo->prepare('SELECT id, estado, codigo, origen, nombre_cliente, telefono_cliente FROM pedidos WHERE id = ?');
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch();

if (!$pedido) {
    volver('Ese pedido no existe.');
}

$siguienteEstado = [
    'pendiente'      => 'en_preparacion',
    'en_preparacion' => 'listo',
    'listo'          => 'entregado',
];

if ($accion === 'avanzar') {
    if (!isset($siguienteEstado[$pedido['estado']])) {
        volver('Ese pedido ya no se puede avanzar.');
    }
    $nuevoEstado = $siguienteEstado[$pedido['estado']];

    $pdo->prepare('UPDATE pedidos SET estado = ? WHERE id = ?')
        ->execute([$nuevoEstado, $pedido_id]);

    // Al pasar a "listo" (solo pedidos web, que tienen un teléfono real para avisar)
    // dejamos armado el link de WhatsApp para que el dueño solo tenga que tocar "Enviar".
    if ($nuevoEstado === 'listo' && $pedido['origen'] === 'web') {
        $primerNombre = explode(' ', trim($pedido['nombre_cliente']))[0];
        $mensaje = "¡Hola {$primerNombre}! 🎉 Tu pedido {$pedido['codigo']} ya está listo para retirar en Confetti. ¡Te esperamos!";
        $link = linkWhatsapp($pedido['telefono_cliente'], $mensaje);

        volver('Pedido ' . $pedido['codigo'] . ' marcado como listo.', 'ok', $link);
    }

    volver('Pedido ' . $pedido['codigo'] . ' actualizado.', 'ok');
}

if ($accion === 'cancelar') {
    if (!in_array($pedido['estado'], ['pendiente', 'en_preparacion'], true)) {
        volver('Ese pedido ya no se puede cancelar.');
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "SELECT producto_id, cantidad FROM movimientos_stock
             WHERE pedido_id = ? AND tipo = 'reserva'"
        );
        $stmt->execute([$pedido_id]);
        $reservas = $stmt->fetchAll();

        foreach ($reservas as $r) {
            $cantidadADevolver = abs((float) $r['cantidad']);

            $pdo->prepare('UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?')
                ->execute([$cantidadADevolver, $r['producto_id']]);

            $pdo->prepare(
                "INSERT INTO movimientos_stock (producto_id, tipo, cantidad, pedido_id)
                 VALUES (?, 'liberacion_cancelacion', ?, ?)"
            )->execute([$r['producto_id'], $cantidadADevolver, $pedido_id]);
        }

        $pdo->prepare("UPDATE pedidos SET estado = 'cancelado' WHERE id = ?")
            ->execute([$pedido_id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        volver('No se pudo cancelar el pedido: ' . $e->getMessage());
    }

    volver('Pedido ' . $pedido['codigo'] . ' cancelado y stock liberado.', 'ok');
}
