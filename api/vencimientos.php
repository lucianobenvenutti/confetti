<?php
/**
 * Cancela los pedidos cuyo plazo de 24hs ya venció y devuelve su stock.
 *
 * Todo se mide con el reloj de MySQL: el vencimiento lo escribe MySQL al crear
 * el pedido (DATE_ADD(NOW(), INTERVAL 24 HOUR)) y lo compara MySQL contra su
 * propio NOW(). PHP no calcula ninguna hora, así que no importa en qué zona
 * horaria esté configurado el servidor.
 *
 * Se llama desde varios lugares (panel de pedidos, catálogo público, alta de
 * pedidos y seguimiento), así el stock reservado se libera solo aunque no haya
 * ningún cron configurado. Si no hay nada vencido cuesta una sola consulta.
 *
 * Devuelve los códigos de los pedidos que canceló en esta llamada.
 */

function liberarPedidosVencidos(PDO $pdo): array {
    $cancelados = [];

    $vencidos = $pdo->query(
        "SELECT id, codigo FROM pedidos
         WHERE estado IN ('pendiente','en_preparacion','listo')
           AND vence_en IS NOT NULL
           AND vence_en <= NOW()"
    )->fetchAll();

    foreach ($vencidos as $pedido) {
        $pdo->beginTransaction();
        try {
            // Primero cambio el estado, con la condición puesta en el UPDATE mismo:
            // si dos personas cargan la página a la vez, solo una "gana" el cambio y
            // la otra no hace nada, así el stock nunca se devuelve dos veces.
            $stmt = $pdo->prepare(
                "UPDATE pedidos SET estado = 'cancelado'
                 WHERE id = ? AND estado IN ('pendiente','en_preparacion','listo')"
            );
            $stmt->execute([$pedido['id']]);

            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                continue;
            }

            // Devuelvo exactamente lo que ese pedido había reservado, producto por producto
            $stmt = $pdo->prepare(
                "SELECT producto_id, cantidad FROM movimientos_stock
                 WHERE pedido_id = ? AND tipo = 'reserva'"
            );
            $stmt->execute([$pedido['id']]);

            foreach ($stmt->fetchAll() as $r) {
                $cantidadADevolver = abs((float) $r['cantidad']);

                $pdo->prepare('UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?')
                    ->execute([$cantidadADevolver, $r['producto_id']]);

                $pdo->prepare(
                    "INSERT INTO movimientos_stock (producto_id, tipo, cantidad, pedido_id)
                     VALUES (?, 'liberacion_vencimiento', ?, ?)"
                )->execute([$r['producto_id'], $cantidadADevolver, $pedido['id']]);
            }

            $pdo->commit();
            $cancelados[] = $pedido['codigo'];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Si falla uno seguimos con los demás en vez de cortar todo.
        }
    }

    return $cancelados;
}
