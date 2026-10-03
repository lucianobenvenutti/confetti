<?php
require 'conexion.php';

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: combos.php?msg=' . urlencode('Combo inválido.') . '&tipo=error');
    exit;
}

$stmt = $pdo->prepare('SELECT imagen FROM combos WHERE id = ?');
$stmt->execute([$id]);
$imagen = $stmt->fetchColumn();

try {
    // combo_productos se borra solo (ON DELETE CASCADE); lo que puede frenar
    // el borrado es que el combo ya haya sido parte de algún pedido_items
    $pdo->prepare('DELETE FROM combos WHERE id = ?')->execute([$id]);

    if ($imagen) {
        $ruta = __DIR__ . '/../assets/combos/' . $imagen;
        if (is_file($ruta)) @unlink($ruta);
    }

    $mensaje = 'Combo eliminado.';
    $tipo = 'ok';
} catch (PDOException $e) {
    $pdo->prepare('UPDATE combos SET activo = 0 WHERE id = ?')->execute([$id]);
    $mensaje = 'Ese combo ya fue parte de algún pedido, así que no se puede borrar del todo. Lo desactivamos en su lugar.';
    $tipo = 'error';
}

header('Location: combos.php?msg=' . urlencode($mensaje) . '&tipo=' . $tipo);
