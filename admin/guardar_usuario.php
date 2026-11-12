<?php
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion([ROL_DUENO]);

function volverForm($msg, $id = null) {
    header('Location: usuario_form.php?msg=' . urlencode($msg) . ($id ? '&id=' . $id : ''));
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
$nombre = substr(trim((string) ($_POST['nombre'] ?? '')), 0, 80);
$usuario = strtolower(substr(trim((string) ($_POST['usuario'] ?? '')), 0, 60));
$rol = in_array($_POST['rol'] ?? '', [ROL_DUENO, ROL_EMPLEADO], true) ? $_POST['rol'] : ROL_EMPLEADO;
$activo = isset($_POST['activo']) ? 1 : 0;
$clave = (string) ($_POST['clave'] ?? '');

$esYo = $id && (int) $id === (int) $usuarioActual['id'];
if ($esYo) {
    // Nadie se quita el rol ni se desactiva a sí mismo (se quedaría sin acceso)
    $rol = $usuarioActual['rol'];
    $activo = 1;
    $clave = '';
}

if ($nombre === '') {
    volverForm('Escribí el nombre.', $id);
}
if (!preg_match('/^[a-z0-9._-]{3,60}$/', $usuario)) {
    volverForm('El usuario tiene que tener entre 3 y 60 caracteres: letras sin tildes, números, punto, guion o guion bajo.', $id);
}

$stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ? AND id != ?');
$stmt->execute([$usuario, $id ?: 0]);
if ($stmt->fetch()) {
    volverForm('Ya existe otro usuario con ese nombre de ingreso.', $id);
}

if (!$id) {
    if (($problema = validarClaveNueva($clave, $usuario)) !== null) {
        volverForm($problema);
    }
    $pdo->prepare('INSERT INTO usuarios (nombre, usuario, password_hash, rol, activo) VALUES (?, ?, ?, ?, ?)')
        ->execute([$nombre, $usuario, password_hash($clave, PASSWORD_DEFAULT), $rol, $activo]);
    header('Location: usuarios.php?msg=' . urlencode('Usuario creado.') . '&tipo=ok');
    exit;
}

$stmt = $pdo->prepare('SELECT id, rol, activo FROM usuarios WHERE id = ?');
$stmt->execute([$id]);
$actual = $stmt->fetch();
if (!$actual) {
    header('Location: usuarios.php?msg=' . urlencode('Ese usuario no existe.') . '&tipo=error');
    exit;
}

// Siempre tiene que quedar al menos un dueño activo
if ($actual['rol'] === ROL_DUENO && $actual['activo'] && ($rol !== ROL_DUENO || !$activo)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = ? AND activo = 1 AND id != ?");
    $stmt->execute([ROL_DUENO, $id]);
    if ((int) $stmt->fetchColumn() === 0) {
        volverForm('Tiene que quedar al menos un dueño activo.', $id);
    }
}

if ($clave !== '') {
    if (($problema = validarClaveNueva($clave, $usuario)) !== null) {
        volverForm($problema, $id);
    }
    $pdo->prepare('UPDATE usuarios SET nombre=?, usuario=?, rol=?, activo=?, password_hash=? WHERE id=?')
        ->execute([$nombre, $usuario, $rol, $activo, password_hash($clave, PASSWORD_DEFAULT), $id]);
} else {
    $pdo->prepare('UPDATE usuarios SET nombre=?, usuario=?, rol=?, activo=? WHERE id=?')
        ->execute([$nombre, $usuario, $rol, $activo, $id]);
}

header('Location: usuarios.php?msg=' . urlencode('Usuario guardado.') . '&tipo=ok');
