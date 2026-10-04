<?php
require 'conexion.php';

$combos = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM combo_productos cp WHERE cp.combo_id = c.id) AS cantidad_items
     FROM combos c
     ORDER BY c.nombre ASC"
)->fetchAll();

$mensaje = $_GET['msg'] ?? null;
$tipoMensaje = $_GET['tipo'] ?? 'error';
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Combos y picadas</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  .nav-admin a:hover{ color:#1F2A44; text-decoration:underline; }
  .cabecera{ display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:22px; }
  h1{ font-size:1.4rem; margin:0; }
  p.sub{ color:#5B6478; margin:4px 0 0; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.95rem; max-width:640px; }
  .aviso.ok{ background:#E4F5EE; color:#1E9C7C; border:1px solid #bfe6d7; }
  .aviso.error{ background:#FBEAE8; color:#E8483C; border:1px solid #f3c9c4; }
  .btn{ font-family:inherit; font-weight:600; font-size:0.88rem; border:none; border-radius:9px; padding:10px 16px; cursor:pointer; text-decoration:none; display:inline-block; }
  .btn-primario{ background:#6E4E9E; color:#fff; }
  .btn-primario:hover{ background:#5a3f82; }
  table{ width:100%; max-width:860px; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; }
  th, td{ text-align:left; padding:13px 16px; border-bottom:1px solid rgba(31,42,68,0.08); vertical-align:middle; font-size:0.9rem; }
  th{ font-size:0.78rem; color:#5B6478; font-weight:600; }
  tr.inactivo{ opacity:.5; }
  .miniatura{ width:44px; height:44px; border-radius:9px; object-fit:cover; background:#FFF4DE; display:block; }
  .miniatura-vacia{ width:44px; height:44px; border-radius:9px; background:#FFF4DE; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
  .acciones a, .acciones button{ font-size:0.82rem; margin-right:10px; border:none; background:none; cursor:pointer; padding:0; color:#1B4A9C; text-decoration:none; }
  .acciones button.eliminar{ color:#E8483C; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · <a href="productos.php">Productos</a> · <a href="categorias.php">Categorías</a> · Combos · <a href="proveedores.php">Proveedores</a> · <a href="ventas.php">Ventas</a></div>

<div class="cabecera">
  <div>
    <h1>Combos y picadas</h1>
    <p class="sub">Se arman eligiendo productos ya cargados, con un precio especial fijo.</p>
  </div>
  <a class="btn btn-primario" href="combo_form.php">🎉 Armar combo</a>
</div>

<?php if ($mensaje): ?>
  <div class="aviso <?= $tipoMensaje === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<?php if (!$combos): ?>
  <p style="color:#5B6478;">Todavía no armaste ningún combo.</p>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>Foto</th>
      <th>Combo</th>
      <th>Productos</th>
      <th>Precio</th>
      <th>Activo</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($combos as $c): ?>
      <tr class="<?= $c['activo'] ? '' : 'inactivo' ?>">
        <td>
          <?php if ($c['imagen']): ?>
            <img class="miniatura" src="../assets/combos/<?= htmlspecialchars($c['imagen']) ?>" alt="">
          <?php else: ?>
            <div class="miniatura-vacia">🎉</div>
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($c['nombre']) ?></td>
        <td><?= (int) $c['cantidad_items'] ?> producto(s)</td>
        <td>$<?= number_format($c['precio'], 0, ',', '.') ?></td>
        <td><?= $c['activo'] ? 'Sí' : 'No' ?></td>
        <td class="acciones">
          <a href="combo_form.php?id=<?= $c['id'] ?>">Editar</a>
          <form method="post" action="eliminar_combo.php" style="display:inline;"
                onsubmit="return confirm('¿Eliminar el combo &quot;<?= htmlspecialchars($c['nombre'], ENT_QUOTES) ?>&quot;?');">
            <input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button type="submit" class="eliminar">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

</body>
</html>
