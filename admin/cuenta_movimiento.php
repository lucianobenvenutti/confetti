<?php
/**
 * POST: registra un pago (resta del saldo) o una compra/cargo (suma al saldo)
 * en una cuenta corriente. El monto va siempre en positivo; el "tipo" decide.
 */

require 'conexion.php';

$cuentaId = filter_input(INPUT_POST, 'cuenta_id', FILTER_VALIDATE_INT);
$tipo = $_POST['tipo'] ?? '';
$monto = filter_input(INPUT_POST, 'monto', FILTER_VALIDATE_FLOAT);
$nota = trim($_POST['nota'] ?? '');

function volver($cuentaId, $msg, $tipoMsg = 'error') {
    $url = $cuentaId
        ? 'cuenta_detalle.php?id=' . $cuentaId . '&msg=' . urlencode($msg) . '&tipo=' . $tipoMsg
        : 'cuentas.php?msg=' . urlencode($msg) . '&tipo=' . $tipoMsg;
    header('Location: ' . $url);
    exit;
}

if (!$cuentaId || !in_array($tipo, ['pago', 'cargo'], true)) {
    volver(null, 'Solicitud inválida.');
}
if ($monto === false || $monto === null || $monto <= 0 || $monto > 99999999.99) {
    volver($cuentaId, 'El monto tiene que ser mayor a cero.');
}
$monto = round($monto, 2);

$stmt = $pdo->prepare('SELECT id FROM cuentas_corrientes WHERE id = ?');
$stmt->execute([$cuentaId]);
if (!$stmt->fetch()) {
    volver(null, 'Esa cuenta no existe.');
}

$pdo->prepare('INSERT INTO cuenta_movimientos (cuenta_id, tipo, monto, nota) VALUES (?, ?, ?, ?)')
    ->execute([$cuentaId, $tipo, $monto, $nota !== '' ? $nota : null]);

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(CASE tipo WHEN 'cargo' THEN monto ELSE -monto END), 0)
     FROM cuenta_movimientos WHERE cuenta_id = ?"
);
$stmt->execute([$cuentaId]);
$saldo = (float) $stmt->fetchColumn();

$pesos = '$' . number_format(abs($saldo), 0, ',', '.');
$estado = $saldo > 0.004 ? "Ahora debe $pesos." : ($saldo < -0.004 ? "Quedó con saldo a favor de $pesos." : 'La cuenta quedó sin deuda.');
$que = $tipo === 'pago' ? 'Pago registrado.' : 'Compra sumada a la cuenta.';

volver($cuentaId, "$que $estado", 'ok');
