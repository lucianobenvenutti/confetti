<?php
require 'conexion.php';

function volverForm($msg, $id = null) {
    $url = 'proveedor_form.php?msg=' . urlencode($msg);
    if ($id) $url .= '&id=' . $id;
    header('Location: ' . $url);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
$nombre = trim($_POST['nombre'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');
$diasReparto = trim($_POST['dias_reparto'] ?? '');
$notas = trim($_POST['notas'] ?? '');
$activo = isset($_POST['activo']) ? 1 : 0;

if ($nombre === '') {
    volverForm('El nombre es obligatorio.', $id);
}

if ($id) {
    $pdo->prepare(
        'UPDATE proveedores SET nombre=?, telefono=?, direccion=?, dias_reparto=?, notas=?, activo=? WHERE id=?'
    )->execute([$nombre, $telefono, $direccion, $diasReparto, $notas, $activo, $id]);
} else {
    $pdo->prepare(
        'INSERT INTO proveedores (nombre, telefono, direccion, dias_reparto, notas, activo) VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$nombre, $telefono, $direccion, $diasReparto, $notas, $activo]);
}

header('Location: proveedores.php?msg=' . urlencode('Proveedor guardado.') . '&tipo=ok');
