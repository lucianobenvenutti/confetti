<?php
require 'conexion.php';

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: cuentas.php?msg=' . urlencode('Cuenta inválida.') . '&tipo=error');
    exit;
}

$stmt = $pdo->prepare('SELECT nombre FROM cuentas_corrientes WHERE id = ?');
$stmt->execute([$id]);
$nombre = $stmt->fetchColumn();

if ($nombre === false) {
    header('Location: cuentas.php?msg=' . urlencode('Esa cuenta ya no existe.') . '&tipo=error');
    exit;
}

// Los movimientos se van con la cuenta (ON DELETE CASCADE).
// Las ventas del mostrador que se cargaron a esta cuenta NO se borran: siguen en Ventas.
$pdo->prepare('DELETE FROM cuentas_corrientes WHERE id = ?')->execute([$id]);

header('Location: cuentas.php?msg=' . urlencode('Cuenta de ' . $nombre . ' eliminada.') . '&tipo=ok');
