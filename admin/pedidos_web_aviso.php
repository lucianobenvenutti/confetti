<?php
/**
 * GET admin/pedidos_web_aviso.php
 * Aviso liviano para el mostrador: cuántos pedidos web están esperando que alguien los atienda
 * (estado "pendiente" y todavía no vencidos) y cuáles son. Solo con sesión iniciada.
 * El detalle completo se ve en Pedidos web.
 */
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion(ROLES_TODOS, true);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$filas = $pdo->query(
    "SELECT id, codigo, nombre_cliente, total, metodo_pago,
            UNIX_TIMESTAMP() - UNIX_TIMESTAMP(creado_en) AS seg
     FROM pedidos
     WHERE origen = 'web' AND estado = 'pendiente' AND vence_en > NOW()
     ORDER BY id DESC
     LIMIT 50"
)->fetchAll();

$pedidos = [];
foreach ($filas as $f) {
    $pedidos[] = [
        'id'     => (int) $f['id'],
        'codigo' => $f['codigo'],
        'nombre' => $f['nombre_cliente'],
        'total'  => (float) $f['total'],
        'pago'   => $f['metodo_pago'],
        'seg'    => (int) $f['seg'],
    ];
}

echo json_encode(['cantidad' => count($pedidos), 'pedidos' => $pedidos]);
