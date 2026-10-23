<?php
require 'conexion.php';

function volverForm($msg, $id = null) {
    $url = 'cuenta_form.php?msg=' . urlencode($msg);
    if ($id) $url .= '&id=' . $id;
    header('Location: ' . $url);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$notas = trim($_POST['notas'] ?? '');

function largo($texto) {
    return preg_match_all('/./su', $texto);
}

if ($nombre === '') {
    volverForm('El nombre es obligatorio.', $id);
}
if (largo($nombre) > 120) {
    volverForm('El nombre es demasiado largo (máximo 120 caracteres).', $id);
}
if (largo($telefono) > 30) {
    volverForm('El teléfono es demasiado largo (máximo 30 caracteres).', $id);
}
if (largo($notas) > 255) {
    volverForm('Las notas son demasiado largas (máximo 255 caracteres).', $id);
}

$telefono = $telefono !== '' ? $telefono : null;
$notas = $notas !== '' ? $notas : null;

if ($id) {
    $pdo->prepare('UPDATE cuentas_corrientes SET nombre = ?, telefono = ?, notas = ? WHERE id = ?')
        ->execute([$nombre, $telefono, $notas, $id]);
    header('Location: cuenta_detalle.php?id=' . $id . '&msg=' . urlencode('Datos guardados.') . '&tipo=ok');
} else {
    $pdo->prepare('INSERT INTO cuentas_corrientes (nombre, telefono, notas) VALUES (?, ?, ?)')
        ->execute([$nombre, $telefono, $notas]);
    header('Location: cuenta_detalle.php?id=' . $pdo->lastInsertId() . '&msg=' . urlencode('Cuenta creada.') . '&tipo=ok');
}
