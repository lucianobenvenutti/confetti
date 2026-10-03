<?php
require 'conexion.php';

function volverForm($msg, $id = null) {
    $url = 'categoria_form.php?msg=' . urlencode($msg);
    if ($id) $url .= '&id=' . $id;
    header('Location: ' . $url);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
$nombre = trim($_POST['nombre'] ?? '');
$colorAcento = trim($_POST['color_acento'] ?? '#E8483C');
$orden = filter_input(INPUT_POST, 'orden', FILTER_VALIDATE_INT) ?: 0;
$activo = isset($_POST['activo']) ? 1 : 0;

if ($nombre === '') {
    volverForm('El nombre es obligatorio.', $id);
}
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $colorAcento)) {
    $colorAcento = '#E8483C';
}

if ($id) {
    $pdo->prepare(
        'UPDATE categorias SET nombre = ?, color_acento = ?, orden = ?, activo = ? WHERE id = ?'
    )->execute([$nombre, $colorAcento, $orden, $activo, $id]);
} else {
    $pdo->prepare(
        'INSERT INTO categorias (nombre, color_acento, orden, activo) VALUES (?, ?, ?, ?)'
    )->execute([$nombre, $colorAcento, $orden, $activo]);
}

header('Location: categorias.php?msg=' . urlencode('Categoría guardada.') . '&tipo=ok');
