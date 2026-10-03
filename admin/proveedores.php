<?php
require 'conexion.php';

$proveedores = $pdo->query(
    "SELECT pr.*,
            (SELECT COUNT(*) FROM productos p WHERE p.proveedor_id = pr.id) AS cantidad_productos,
            (SELECT COUNT(*) FROM productos p WHERE p.proveedor_id = pr.id AND p.stock_actual <= p.stock_minimo) AS cantidad_bajos
     FROM proveedores pr
     ORDER BY pr.nombre ASC"
)->fetchAll();

$mensaje = $_GET['msg'] ?? null;
$tipoMensaje = $_GET['tipo'] ?? 'error';
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Proveedores</title>
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
  .btn-primario{ background:#1B4A9C; color:#fff; }
  .btn-primario:hover{ background:#153c7e; }
  table{ width:100%; max-width:860px; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; }
  th, td{ text-align:left; padding:13px 16px; border-bottom:1px solid rgba(31,42,68,0.08); vertical-align:middle; font-size:0.9rem; }
  th{ font-size:0.78rem; color:#5B6478; font-weight:600; }
  tr.inactivo{ opacity:.5; }
  a.nombre-proveedor{ color:#1F2A44; text-decoration:none; font-weight:600; }
  a.nombre-proveedor:hover{ text-decoration:underline; }
  .badge-bajos{
    background:#FDEAEA; color:#E8483C; font-size:0.75rem; font-weight:700;
    padding:3px 9px; border-radius:999px; margin-left:8px;
  }
  .acciones a, .acciones button{ font-size:0.82rem; margin-right:10px; border:none; background:none; cursor:pointer; padding:0; color:#1B4A9C; text-decoration:none; }
  .acciones button.eliminar{ color:#E8483C; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · <a href="productos.php">Productos</a> · <a href="categorias.php">Categorías</a> · <a href="combos.php">Combos</a> · Proveedores</div>

<div class="cabecera">
  <div>
    <h1>Proveedores</h1>
    <p class="sub">Tocá un nombre para ver sus productos y qué le falta pedir.</p>
  </div>
  <a class="btn btn-primario" href="proveedor_form.php">+ Nuevo proveedor</a>
</div>

<?php if ($mensaje): ?>
  <div class="aviso <?= $tipoMensaje === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<?php if (!$proveedores): ?>
  <p style="color:#5B6478;">Todavía no cargaste ningún proveedor.</p>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>Proveedor</th>
      <th>Teléfono</th>
      <th>Días de reparto</th>
      <th>Productos</th>
      <th>Activo</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($proveedores as $p): ?>
      <tr class="<?= $p['activo'] ? '' : 'inactivo' ?>">
        <td>
          <a class="nombre-proveedor" href="proveedor_detalle.php?id=<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></a>
          <?php if ($p['cantidad_bajos'] > 0): ?>
            <span class="badge-bajos"><?= (int) $p['cantidad_bajos'] ?> bajo(s)</span>
          <?php endif; ?>
        </td>
        <td><?= $p['telefono'] ? htmlspecialchars($p['telefono']) : '—' ?></td>
        <td><?= $p['dias_reparto'] ? htmlspecialchars($p['dias_reparto']) : '—' ?></td>
        <td><?= (int) $p['cantidad_productos'] ?></td>
        <td><?= $p['activo'] ? 'Sí' : 'No' ?></td>
        <td class="acciones">
          <a href="proveedor_form.php?id=<?= $p['id'] ?>">Editar</a>
          <form method="post" action="eliminar_proveedor.php" style="display:inline;"
                onsubmit="return confirm('¿Eliminar al proveedor &quot;<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>&quot;? Sus productos quedan sin proveedor asignado.');">
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
