<?php
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion([ROL_DUENO]);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$u = ['nombre' => '', 'usuario' => '', 'rol' => ROL_EMPLEADO, 'activo' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT id, nombre, usuario, rol, activo FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) {
        header('Location: usuarios.php?msg=' . urlencode('Ese usuario no existe.') . '&tipo=error');
        exit;
    }
    $u = $encontrado;
}

$esYo = $id && (int) $id === (int) $usuarioActual['id'];
$mensaje = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es-AR" translate="no">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — <?= $id ? 'Editar' : 'Nuevo' ?> usuario</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  h1{ font-size:1.4rem; margin:0 0 22px; }
  form{ max-width:440px; background:#fff; border-radius:14px; padding:24px; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.9rem; max-width:440px; background:#FBEAE8; color:#E8483C; border:1px solid #f3c9c4; }
  .campo{ margin-bottom:16px; }
  label{ display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; }
  input[type="text"], input[type="password"], select{ width:100%; padding:10px 12px; border-radius:9px; border:1px solid rgba(31,42,68,0.15); font-family:inherit; font-size:0.95rem; box-sizing:border-box; }
  .ayuda{ font-size:0.78rem; color:#5B6478; margin-top:5px; }
  .fila-check{ display:flex; align-items:center; gap:8px; }
  .fila-check label{ margin:0; font-weight:500; }
  .botones{ display:flex; gap:10px; margin-top:22px; }
  button, .btn-cancelar{ font-family:inherit; font-weight:600; font-size:0.9rem; border-radius:9px; padding:11px 18px; cursor:pointer; text-decoration:none; }
  button{ border:none; background:#1B4A9C; color:#fff; }
  .btn-cancelar{ background:none; border:1px solid rgba(31,42,68,0.2); color:#1F2A44; }
</style>
</head>
<body>

<div class="nav-admin"><a href="usuarios.php">← Usuarios</a></div>
<h1><?= $id ? 'Editar usuario' : 'Nuevo usuario' ?></h1>

<?php if ($mensaje): ?><div class="aviso"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>

<form method="post" action="guardar_usuario.php" autocomplete="off">
  <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

  <div class="campo">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" maxlength="80" required value="<?= htmlspecialchars($u['nombre']) ?>">
  </div>

  <div class="campo">
    <label for="usuario">Usuario para ingresar</label>
    <input type="text" id="usuario" name="usuario" maxlength="60" required autocapitalize="none" spellcheck="false"
           value="<?= htmlspecialchars($u['usuario']) ?>">
    <div class="ayuda">Entre 3 y 60 caracteres: letras sin tildes, números, punto, guion o guion bajo.</div>
  </div>

  <div class="campo">
    <label for="rol">Rol</label>
    <select id="rol" name="rol" <?= $esYo ? 'disabled' : '' ?>>
      <option value="<?= ROL_EMPLEADO ?>" <?= $u['rol'] === ROL_EMPLEADO ? 'selected' : '' ?>>Empleado (mostrador y pedidos)</option>
      <option value="<?= ROL_DUENO ?>" <?= $u['rol'] === ROL_DUENO ? 'selected' : '' ?>>Dueño (todo)</option>
    </select>
    <?php if ($esYo): ?><div class="ayuda">No podés cambiar tu propio rol.</div><?php endif; ?>
  </div>

  <?php if ($esYo): ?>
    <div class="campo"><div class="ayuda">Para cambiar tu contraseña usá <a href="mi_clave.php">Mi clave</a>.</div></div>
  <?php else: ?>
    <div class="campo">
      <label for="clave"><?= $id ? 'Nueva contraseña (dejala vacía para no cambiarla)' : 'Contraseña' ?></label>
      <input type="password" id="clave" name="clave" maxlength="72" autocomplete="new-password" <?= $id ? '' : 'required' ?>>
      <div class="ayuda">Mínimo 8 caracteres. <?= $id ? 'Si la cambiás, esa persona tiene que volver a ingresar.' : '' ?></div>
    </div>

    <div class="campo fila-check">
      <input type="checkbox" id="activo" name="activo" <?= $u['activo'] ? 'checked' : '' ?>>
      <label for="activo">Activo (puede ingresar)</label>
    </div>
  <?php endif; ?>

  <div class="botones">
    <button type="submit">Guardar</button>
    <a class="btn-cancelar" href="usuarios.php">Cancelar</a>
  </div>
</form>

</body>
</html>
