<?php
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion([ROL_DUENO]);

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
<html lang="es-AR" translate="no">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon-32.png">
<link rel="icon" href="../favicon.ico" sizes="48x48">
<link rel="apple-touch-icon" href="../assets/apple-touch-icon.png">
<meta name="theme-color" content="#F4E4CC">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Proveedores</title>
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
  .btn-primario{ background:#6B4026; color:#fff; }
  .btn-primario:hover{ background:#4F2E1B; }
  table{ width:100%; max-width:860px; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; }
  th, td{ text-align:left; padding:13px 16px; border-bottom:1px solid rgba(63,43,31,0.08); vertical-align:middle; font-size:0.9rem; }
  th{ font-size:0.78rem; color:#7A614E; font-weight:600; }
  tr.inactivo{ opacity:.5; }
  a.nombre-proveedor{ color:#3F2B1F; text-decoration:none; font-weight:600; }
  a.nombre-proveedor:hover{ text-decoration:underline; }
  .badge-bajos{
    background:#FBEAE3; color:#C8402F; font-size:0.75rem; font-weight:700;
    padding:3px 9px; border-radius:999px; margin-left:8px;
  }
  .acciones a, .acciones button{ font-size:0.82rem; margin-right:10px; border:none; background:none; cursor:pointer; padding:0; color:#6B4026; text-decoration:none; }
  .acciones button.eliminar{ color:#C8402F; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · <a href="productos.php">Productos</a> · <a href="categorias.php">Categorías</a> · <a href="combos.php">Combos</a> · Proveedores · <a href="ventas.php">Ventas</a> · <a href="horarios.php">Horario</a> · <a href="cuentas.php">Cuentas</a> <?= barraSesionHtml() ?></div>

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
  <p style="color:#7A614E;">Todavía no cargaste ningún proveedor.</p>
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
