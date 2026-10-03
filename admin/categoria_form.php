<?php
require 'conexion.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$categoria = ['nombre' => '', 'color_acento' => '#E8483C', 'orden' => 0, 'activo' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM categorias WHERE id = ?');
    $stmt->execute([$id]);
    $encontrada = $stmt->fetch();
    if (!$encontrada) {
        header('Location: categorias.php?msg=' . urlencode('Esa categoría no existe.') . '&tipo=error');
        exit;
    }
    $categoria = $encontrada;
}

$mensaje = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — <?= $id ? 'Editar' : 'Nueva' ?> categoría</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  h1{ font-size:1.4rem; margin:0 0 22px; }
  form{ max-width:420px; background:#fff; border-radius:14px; padding:24px; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.9rem; max-width:420px; background:#FBEAE8; color:#E8483C; border:1px solid #f3c9c4; }
  .campo{ margin-bottom:16px; }
  label{ display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; }
  input[type="text"], input[type="number"]{
    width:100%; padding:10px 12px; border-radius:9px; border:1px solid rgba(31,42,68,0.15); font-family:inherit; font-size:0.95rem;
  }
  input[type="color"]{ width:60px; height:38px; border:1px solid rgba(31,42,68,0.15); border-radius:9px; padding:2px; }
  .fila-check{ display:flex; align-items:center; gap:8px; }
  .fila-check label{ margin:0; font-weight:500; }
  .botones{ display:flex; gap:10px; margin-top:22px; }
  button, .btn-cancelar{
    font-family:inherit; font-weight:600; font-size:0.9rem; border-radius:9px; padding:11px 18px; cursor:pointer; text-decoration:none;
  }
  button{ border:none; background:#E8483C; color:#fff; }
  button:hover{ background:#c73a2f; }
  .btn-cancelar{ background:none; border:1px solid rgba(31,42,68,0.2); color:#1F2A44; }
</style>
</head>
<body>

<div class="nav-admin"><a href="categorias.php">← Categorías</a></div>
<h1><?= $id ? 'Editar categoría' : 'Nueva categoría' ?></h1>

<?php if ($mensaje): ?>
  <div class="aviso"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<form method="post" action="guardar_categoria.php">
  <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

  <div class="campo">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($categoria['nombre']) ?>" required>
  </div>

  <div class="campo">
    <label for="color_acento">Color</label>
    <input type="color" id="color_acento" name="color_acento" value="<?= htmlspecialchars($categoria['color_acento']) ?>">
  </div>

  <div class="campo">
    <label for="orden">Orden (menor número aparece primero)</label>
    <input type="number" id="orden" name="orden" value="<?= (int) $categoria['orden'] ?>" min="0">
  </div>

  <div class="campo fila-check">
    <input type="checkbox" id="activo" name="activo" <?= $categoria['activo'] ? 'checked' : '' ?>>
    <label for="activo">Activa (visible en la página)</label>
  </div>

  <div class="botones">
    <button type="submit">Guardar</button>
    <a class="btn-cancelar" href="categorias.php">Cancelar</a>
  </div>
</form>

</body>
</html>
