<?php
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion([ROL_DUENO]);

$combos = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM combo_productos cp WHERE cp.combo_id = c.id) AS cantidad_items
     FROM combos c
     ORDER BY c.nombre ASC"
)->fetchAll();

$mensaje = $_GET['msg'] ?? null;
$tipoMensaje = $_GET['tipo'] ?? 'error';
?>
<!DOCTYPE html>
<html lang="es-AR" translate="no">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon-32.png">
<link rel="icon" href="../favicon.ico" sizes="48x48">
<link rel="apple-touch-icon" href="../assets/apple-touch-icon.png">
<meta name="theme-color" content="#F4E4CC">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Combos y picadas</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FBF2E4; color:#3F2B1F; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#7A614E; margin-bottom:18px; }
  .nav-admin a{ color:#7A614E; text-decoration:none; }
  .nav-admin a:hover{ color:#3F2B1F; text-decoration:underline; }
  .cabecera{ display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:22px; }
  h1{ font-size:1.4rem; margin:0; }
  p.sub{ color:#7A614E; margin:4px 0 0; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.95rem; max-width:640px; }
  .aviso.ok{ background:#EDF3E4; color:#5F8A4B; border:1px solid #D3E3C2; }
  .aviso.error{ background:#FBEAE3; color:#C8402F; border:1px solid #F0CBBF; }
  .btn{ font-family:inherit; font-weight:600; font-size:0.88rem; border:none; border-radius:9px; padding:10px 16px; cursor:pointer; text-decoration:none; display:inline-block; }
  .btn-primario{ background:#8A6240; color:#fff; }
  .btn-primario:hover{ background:#6B4B30; }
  table{ width:100%; max-width:860px; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; }
  th, td{ text-align:left; padding:13px 16px; border-bottom:1px solid rgba(63,43,31,0.08); vertical-align:middle; font-size:0.9rem; }
  th{ font-size:0.78rem; color:#7A614E; font-weight:600; }
  tr.inactivo{ opacity:.5; }
  .miniatura{ width:44px; height:44px; border-radius:9px; object-fit:cover; background:#F4E4CC; display:block; }
  .miniatura-vacia{ width:44px; height:44px; border-radius:9px; background:#F4E4CC; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
  .acciones a, .acciones button{ font-size:0.82rem; margin-right:10px; border:none; background:none; cursor:pointer; padding:0; color:#6B4026; text-decoration:none; }
  .acciones button.eliminar{ color:#C8402F; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · <a href="productos.php">Productos</a> · <a href="categorias.php">Categorías</a> · Combos · <a href="proveedores.php">Proveedores</a> · <a href="ventas.php">Ventas</a> · <a href="horarios.php">Horario</a> · <a href="cuentas.php">Cuentas</a> <?= barraSesionHtml() ?></div>

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
  <p style="color:#7A614E;">Todavía no armaste ningún combo.</p>
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
