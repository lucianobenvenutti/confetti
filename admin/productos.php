<?php
require 'conexion.php';

$productos = $pdo->query(
    "SELECT p.*, c.nombre AS categoria_nombre, prov.nombre AS proveedor_nombre
     FROM productos p
     JOIN categorias c ON c.id = p.categoria_id
     LEFT JOIN proveedores prov ON prov.id = p.proveedor_id
     ORDER BY c.orden ASC, p.nombre ASC"
)->fetchAll();

$mensaje = $_GET['msg'] ?? null;
$tipoMensaje = $_GET['tipo'] ?? 'error';
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Productos</title>
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
  table{ width:100%; max-width:980px; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; }
  th, td{ text-align:left; padding:12px 16px; border-bottom:1px solid rgba(31,42,68,0.08); vertical-align:middle; font-size:0.88rem; }
  th{ font-size:0.78rem; color:#5B6478; font-weight:600; }
  tr.inactivo{ opacity:.5; }
  .miniatura{ width:44px; height:44px; border-radius:9px; object-fit:cover; background:#FFF4DE; display:block; }
  .miniatura-vacia{ width:44px; height:44px; border-radius:9px; background:#FFF4DE; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
  .categoria-chip{ font-size:0.78rem; color:#5B6478; }
  .stock-bajo{ color:#E8483C; font-weight:600; }
  .acciones a, .acciones button{ font-size:0.82rem; margin-right:10px; border:none; background:none; cursor:pointer; padding:0; color:#1B4A9C; text-decoration:none; }
  .acciones button.eliminar{ color:#E8483C; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · Productos · <a href="categorias.php">Categorías</a> · <a href="combos.php">Combos</a> · <a href="proveedores.php">Proveedores</a></div>

<div class="cabecera">
  <div>
    <h1>Productos</h1>
    <p class="sub">Catálogo completo — crear, editar, sacar de circulación o borrar.</p>
  </div>
  <div style="display:flex; gap:10px;">
    <a class="btn" style="background:#6E4E9E; color:#fff;" href="combos.php">🎉 Armar combo</a>
    <a class="btn btn-primario" href="producto_form.php">+ Nuevo producto</a>
  </div>
</div>

<?php if ($mensaje): ?>
  <div class="aviso <?= $tipoMensaje === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>Foto</th>
      <th>Producto</th>
      <th>Código</th>
      <th>Precio</th>
      <th>Stock</th>
      <th>Activo</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($productos as $p):
      $stockBajo = (float) $p['stock_actual'] <= (float) $p['stock_minimo'];
      $precio = $p['tipo_venta'] === 'peso' ? '$' . number_format($p['precio'], 0, ',', '.') . '/kg' : '$' . number_format($p['precio'], 0, ',', '.');
      $stockTexto = $p['tipo_venta'] === 'peso' ? number_format($p['stock_actual'], 3) . 'kg' : (int) $p['stock_actual'];
    ?>
      <tr class="<?= $p['activo'] ? '' : 'inactivo' ?>">
        <td>
          <?php if ($p['imagen']): ?>
            <img class="miniatura" src="../assets/productos/<?= htmlspecialchars($p['imagen']) ?>" alt="">
          <?php else: ?>
            <div class="miniatura-vacia">🍽️</div>
          <?php endif; ?>
        </td>
        <td>
          <div><?= htmlspecialchars($p['nombre']) ?></div>
          <div class="categoria-chip">
            <?= htmlspecialchars($p['categoria_nombre']) ?><?= $p['proveedor_nombre'] ? ' · ' . htmlspecialchars($p['proveedor_nombre']) : '' ?>
          </div>
        </td>
        <td><?= $p['codigo_barras'] ? htmlspecialchars($p['codigo_barras']) : '<span style="color:#aab;">—</span>' ?></td>
        <td><?= $precio ?></td>
        <td class="<?= $stockBajo ? 'stock-bajo' : '' ?>"><?= $stockTexto ?><?= $stockBajo ? ' ⚠' : '' ?></td>
        <td><?= $p['activo'] ? 'Sí' : 'No' ?></td>
        <td class="acciones">
          <a href="producto_form.php?id=<?= $p['id'] ?>">Editar</a>
          <form method="post" action="eliminar_producto.php" style="display:inline;"
                onsubmit="return confirm('¿Eliminar &quot;<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>&quot;?');">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button type="submit" class="eliminar">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

</body>
</html>
