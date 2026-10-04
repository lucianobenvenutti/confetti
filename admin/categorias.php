<?php
require 'conexion.php';

$categorias = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM productos p WHERE p.categoria_id = c.id) AS cantidad_productos
     FROM categorias c
     ORDER BY c.orden ASC, c.nombre ASC"
)->fetchAll();

$mensaje = $_GET['msg'] ?? null;
$tipoMensaje = $_GET['tipo'] ?? 'error';
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Categorías</title>
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
  .btn-primario{ background:#E8483C; color:#fff; }
  .btn-primario:hover{ background:#c73a2f; }
  table{ width:100%; max-width:760px; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; }
  th, td{ text-align:left; padding:13px 16px; border-bottom:1px solid rgba(31,42,68,0.08); vertical-align:middle; font-size:0.92rem; }
  th{ font-size:0.8rem; color:#5B6478; font-weight:600; }
  tr.inactiva{ opacity:.5; }
  .swatch{ width:18px; height:18px; border-radius:5px; display:inline-block; vertical-align:middle; margin-right:8px; }
  .acciones a, .acciones button{ font-size:0.82rem; margin-right:10px; border:none; background:none; cursor:pointer; padding:0; color:#1B4A9C; text-decoration:none; }
  .acciones button.eliminar{ color:#E8483C; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · <a href="productos.php">Productos</a> · Categorías · <a href="combos.php">Combos</a> · <a href="proveedores.php">Proveedores</a> · <a href="ventas.php">Ventas</a></div>

<div class="cabecera">
  <div>
    <h1>Categorías</h1>
    <p class="sub">Copetín, Panificados, Repostería… las que organizan el catálogo.</p>
  </div>
  <a class="btn btn-primario" href="categoria_form.php">+ Nueva categoría</a>
</div>

<?php if ($mensaje): ?>
  <div class="aviso <?= $tipoMensaje === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>Categoría</th>
      <th>Orden</th>
      <th>Productos</th>
      <th>Activa</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($categorias as $c): ?>
      <tr class="<?= $c['activo'] ? '' : 'inactiva' ?>">
        <td>
          <span class="swatch" style="background:<?= htmlspecialchars($c['color_acento'] ?: '#ccc') ?>;"></span>
          <?= htmlspecialchars($c['nombre']) ?>
        </td>
        <td><?= (int) $c['orden'] ?></td>
        <td><?= (int) $c['cantidad_productos'] ?></td>
        <td><?= $c['activo'] ? 'Sí' : 'No' ?></td>
        <td class="acciones">
          <a href="categoria_form.php?id=<?= $c['id'] ?>">Editar</a>
          <form method="post" action="eliminar_categoria.php" style="display:inline;"
                onsubmit="return confirm('¿Eliminar la categoría &quot;<?= htmlspecialchars($c['nombre'], ENT_QUOTES) ?>&quot;?');">
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
