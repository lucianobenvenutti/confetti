<?php
require 'conexion.php';

function volverForm($msg, $id = null) {
    $url = 'combo_form.php?msg=' . urlencode($msg);
    if ($id) $url .= '&id=' . $id;
    header('Location: ' . $url);
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
$activo = isset($_POST['activo']) ? 1 : 0;

$checks = $_POST['producto_check'] ?? [];      // [producto_id => "on"]
$cantidades = $_POST['producto_cantidad'] ?? []; // [producto_id => cantidad]

if ($nombre === '') volverForm('El nombre es obligatorio.', $id);
if ($precio === false || $precio === null || $precio < 0) volverForm('El precio no es válido.', $id);

$seleccionados = []; // producto_id => cantidad, solo los tildados y con cantidad > 0
foreach ($checks as $productoId => $marcado) {
    $cantidad = (float) ($cantidades[$productoId] ?? 0);
    if ($cantidad > 0) {
        $seleccionados[(int) $productoId] = $cantidad;
    }
}

if (!$seleccionados) {
    volverForm('Elegí al menos un producto para el combo.', $id);
}

$pdo->beginTransaction();
try {
    if ($id) {
        $pdo->prepare('UPDATE combos SET nombre=?, descripcion=?, precio=?, activo=? WHERE id=?')
            ->execute([$nombre, $descripcion, $precio, $activo, $id]);
        $comboId = $id;

        // Reemplazo la composición completa: más simple y seguro que ir comparando diferencias
        $pdo->prepare('DELETE FROM combo_productos WHERE combo_id = ?')->execute([$comboId]);
    } else {
        $pdo->prepare('INSERT INTO combos (nombre, descripcion, precio, activo) VALUES (?, ?, ?, ?)')
            ->execute([$nombre, $descripcion, $precio, $activo]);
        $comboId = (int) $pdo->lastInsertId();
    }

    $stmtItem = $pdo->prepare('INSERT INTO combo_productos (combo_id, producto_id, cantidad) VALUES (?, ?, ?)');
    foreach ($seleccionados as $productoId => $cantidad) {
        $stmtItem->execute([$comboId, $productoId, $cantidad]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    volverForm('No se pudo guardar el combo: ' . $e->getMessage(), $id);
}

// --- Foto (opcional), mismo criterio que en productos ---
if (!empty($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['imagen'];
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $extensionesPermitidas, true)) {
        volverForm('Combo guardado, pero la foto no se subió: formato no permitido (usá JPG, PNG o WEBP).', $comboId);
    }
    if ($archivo['size'] > 3 * 1024 * 1024) {
        volverForm('Combo guardado, pero la foto no se subió: pesa más de 3 MB.', $comboId);
    }
    if (@getimagesize($archivo['tmp_name']) === false) {
        volverForm('Combo guardado, pero el archivo no parece ser una imagen válida.', $comboId);
    }

    $carpetaDestino = __DIR__ . '/../assets/combos/';
    if (!is_dir($carpetaDestino)) mkdir($carpetaDestino, 0755, true);

    $stmt = $pdo->prepare('SELECT imagen FROM combos WHERE id = ?');
    $stmt->execute([$comboId]);
    $imagenVieja = $stmt->fetchColumn();

    $nombreNuevo = 'combo_' . $comboId . '.' . $extension;
    $rutaDestino = $carpetaDestino . $nombreNuevo;

    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        if ($imagenVieja && $imagenVieja !== $nombreNuevo) {
            $rutaVieja = $carpetaDestino . $imagenVieja;
            if (is_file($rutaVieja)) @unlink($rutaVieja);
        }
        $pdo->prepare('UPDATE combos SET imagen = ? WHERE id = ?')->execute([$nombreNuevo, $comboId]);
    }
}

header('Location: combos.php?msg=' . urlencode('Combo guardado.') . '&tipo=ok');
