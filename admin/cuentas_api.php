<?php
/**
 * Endpoint JSON que usa el mostrador rápido.
 *   GET  cuentas_api.php?accion=listar  -> todas las cuentas con su saldo
 *   POST cuentas_api.php?accion=crear   -> body {"nombre": "...", "telefono": "..."}
 *
 * Saldo > 0: el cliente debe. Saldo < 0: tiene saldo a favor.
 */

require '../api/db.php'; // conexión PDO + hora de MySQL fija + headers JSON

function responder($codigo, $payload) {
    http_response_code($codigo);
    echo json_encode($payload);
    exit;
}

// Cuenta caracteres (no bytes), así una "ñ" o una tilde cuentan como uno.
function largo($texto) {
    return preg_match_all('/./su', $texto);
}

$accion = $_GET['accion'] ?? '';

if ($accion === 'listar' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $cuentas = $pdo->query(
        "SELECT c.id, c.nombre, c.telefono,
                COALESCE(SUM(CASE m.tipo WHEN 'cargo' THEN m.monto WHEN 'pago' THEN -m.monto END), 0) AS saldo
         FROM cuentas_corrientes c
         LEFT JOIN cuenta_movimientos m ON m.cuenta_id = c.id
         GROUP BY c.id, c.nombre, c.telefono
         ORDER BY c.nombre ASC"
    )->fetchAll();

    foreach ($cuentas as &$c) {
        $c['saldo'] = (float) $c['saldo'];
    }
    responder(200, $cuentas);
}

if ($accion === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $nombre = trim($body['nombre'] ?? '');
    $telefono = trim($body['telefono'] ?? '');

    if ($nombre === '') {
        responder(422, ['error' => 'El nombre es obligatorio.']);
    }
    if (largo($nombre) > 120) {
        responder(422, ['error' => 'El nombre es demasiado largo.']);
    }
    if (largo($telefono) > 30) {
        responder(422, ['error' => 'El teléfono es demasiado largo.']);
    }

    $pdo->prepare('INSERT INTO cuentas_corrientes (nombre, telefono) VALUES (?, ?)')
        ->execute([$nombre, $telefono !== '' ? $telefono : null]);

    responder(201, [
        'id' => (int) $pdo->lastInsertId(),
        'nombre' => $nombre,
        'telefono' => $telefono !== '' ? $telefono : null,
        'saldo' => 0.0,
    ]);
}

responder(400, ['error' => 'Acción inválida.']);
