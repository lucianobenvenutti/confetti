<?php
require 'conexion.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: proveedores.php?msg=' . urlencode('Proveedor inválido.') . '&tipo=error');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM proveedores WHERE id = ?');
$stmt->execute([$id]);
$proveedor = $stmt->fetch();

if (!$proveedor) {
    header('Location: proveedores.php?msg=' . urlencode('Ese proveedor no existe.') . '&tipo=error');
    exit;
}

// Los productos bajos/sin stock primero, para que sea justo lo primero que se ve
$stmt = $pdo->prepare(
    "SELECT p.*, c.nombre AS categoria_nombre
     FROM productos p
     JOIN categorias c ON c.id = p.categoria_id
     WHERE p.proveedor_id = ?
     ORDER BY (p.stock_actual <= p.stock_minimo) DESC, p.nombre ASC"
);
$stmt->execute([$id]);
$productos = $stmt->fetchAll();

$productosBajos = array_filter($productos, fn($p) => (float) $p['stock_actual'] <= (float) $p['stock_minimo']);
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — <?= htmlspecialchars($proveedor['nombre']) ?></title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  .cabecera{ display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px; margin-bottom:24px; }
  h1{ font-size:1.5rem; margin:0 0 4px; }
  .datos-proveedor{
    background:#fff; border-radius:14px; padding:20px 24px; max-width:900px; margin-bottom:28px;
    display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px;
  }
  .dato{ font-size:0.9rem; }
  .dato .etiqueta{ display:block; font-size:0.75rem; color:#5B6478; text-transform:uppercase; letter-spacing:0.02em; margin-bottom:3px; }
  .btn{ font-family:inherit; font-weight:600; font-size:0.85rem; border:none; border-radius:9px; padding:9px 15px; cursor:pointer; text-decoration:none; display:inline-block; background:none; border:1px solid rgba(31,42,68,0.2); color:#1F2A44; }

  .resumen-bajos{
    background:#FDEAEA; border:1px solid #f0b7b2; border-radius:14px;
    padding:16px 20px; max-width:900px; margin-bottom:24px; color:#E8483C; font-weight:600; font-size:0.92rem;
  }
  .resumen-bajos.ok{ background:#E4F5EE; border-color:#bfe6d7; color:#1E9C7C; }

  table{ width:100%; max-width:900px; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; margin-bottom:10px; }
  th, td{ text-align:left; padding:12px 16px; border-bottom:1px solid rgba(31,42,68,0.08); font-size:0.88rem; vertical-align:middle; }
  th{ font-size:0.78rem; color:#5B6478; font-weight:600; }
  tr.fila-bajo{ background:#FFF8F7; }
  .categoria-chip{ font-size:0.78rem; color:#5B6478; }
  .badge-bajo{
    background:#E8483C; color:#fff; font-size:0.72rem; font-weight:700;
    padding:3px 8px; border-radius:999px; margin-left:8px;
  }
  .sin-productos{ color:#5B6478; }
  a.link-editar{ color:#1B4A9C; font-size:0.82rem; text-decoration:none; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · <a href="proveedores.php">Proveedores</a> · <?= htmlspecialchars($proveedor['nombre']) ?></div>

<div class="cabecera">
  <div>
    <h1><?= htmlspecialchars($proveedor['nombre']) ?></h1>
  </div>
  <a class="btn" href="proveedor_form.php?id=<?= $proveedor['id'] ?>">Editar proveedor</a>
</div>

<div class="datos-proveedor">
  <div class="dato"><span class="etiqueta">Teléfono</span><?= $proveedor['telefono'] ? htmlspecialchars($proveedor['telefono']) : '—' ?></div>
  <div class="dato"><span class="etiqueta">Dirección</span><?= $proveedor['direccion'] ? htmlspecialchars($proveedor['direccion']) : '—' ?></div>
  <div class="dato"><span class="etiqueta">Días de reparto</span><?= $proveedor['dias_reparto'] ? htmlspecialchars($proveedor['dias_reparto']) : '—' ?></div>
  <?php if ($proveedor['notas']): ?>
    <div class="dato" style="grid-column:1 / -1;"><span class="etiqueta">Notas</span><?= htmlspecialchars($proveedor['notas']) ?></div>
  <?php endif; ?>
</div>

<?php if ($productosBajos): ?>
  <div class="resumen-bajos">
    ⚠ Hay <?= count($productosBajos) ?> producto(s) de este proveedor bajos o sin stock — es a quien pedirle.
  </div>
<?php elseif ($productos): ?>
  <div class="resumen-bajos ok">✓ Por ahora ningún producto de este proveedor está bajo de stock.</div>
<?php endif; ?>

<?php if (!$productos): ?>
  <p class="sin-productos">Todavía no tiene productos asociados. Asignalo desde la ficha de cada producto.</p>
<?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Producto</th>
        <th>Stock actual</th>
        <th>Alerta desde</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($productos as $p):
        $bajo = (float) $p['stock_actual'] <= (float) $p['stock_minimo'];
        $stockTexto = $p['tipo_venta'] === 'peso' ? number_format($p['stock_actual'], 3) . 'kg' : (int) $p['stock_actual'];
        $minimoTexto = $p['tipo_venta'] === 'peso' ? number_format($p['stock_minimo'], 3) . 'kg' : (int) $p['stock_minimo'];
      ?>
        <tr class="<?= $bajo ? 'fila-bajo' : '' ?>">
          <td>
            <?= htmlspecialchars($p['nombre']) ?>
            <?php if ($bajo): ?><span class="badge-bajo">Bajo</span><?php endif; ?>
            <div class="categoria-chip"><?= htmlspecialchars($p['categoria_nombre']) ?></div>
          </td>
          <td><?= $stockTexto ?></td>
          <td><?= $minimoTexto ?></td>
          <td><a class="link-editar" href="producto_form.php?id=<?= $p['id'] ?>">Editar</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

</body>
</html>
