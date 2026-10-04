<?php
require 'conexion.php';

$hoy = date('Y-m-d');
$periodo = $_GET['periodo'] ?? 'hoy';

if ($periodo === 'mes') {
    $desde = date('Y-m-01');
    $hasta = $hoy;
} elseif ($periodo === 'rango' && !empty($_GET['desde']) && !empty($_GET['hasta'])) {
    $desde = $_GET['desde'];
    $hasta = $_GET['hasta'];
    if ($desde > $hasta) { [$desde, $hasta] = [$hasta, $desde]; }
} else {
    $periodo = 'hoy';
    $desde = $hoy;
    $hasta = $hoy;
}

// --- Resumen general (sin contar cancelados) ---
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS total
     FROM pedidos
     WHERE DATE(creado_en) BETWEEN ? AND ? AND estado != 'cancelado'"
);
$stmt->execute([$desde, $hasta]);
$resumen = $stmt->fetch();
$promedio = $resumen['cantidad'] > 0 ? $resumen['total'] / $resumen['cantidad'] : 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE DATE(creado_en) BETWEEN ? AND ? AND estado = 'cancelado'");
$stmt->execute([$desde, $hasta]);
$cancelados = $stmt->fetchColumn();

// --- Por origen (web vs mostrador) ---
$stmt = $pdo->prepare(
    "SELECT origen, COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS total
     FROM pedidos
     WHERE DATE(creado_en) BETWEEN ? AND ? AND estado != 'cancelado'
     GROUP BY origen"
);
$stmt->execute([$desde, $hasta]);
$porOrigen = $stmt->fetchAll();

// --- Por día, solo si el período abarca más de un día ---
$porDia = [];
if ($desde !== $hasta) {
    $stmt = $pdo->prepare(
        "SELECT DATE(creado_en) AS fecha, COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS total
         FROM pedidos
         WHERE DATE(creado_en) BETWEEN ? AND ? AND estado != 'cancelado'
         GROUP BY DATE(creado_en)
         ORDER BY fecha DESC"
    );
    $stmt->execute([$desde, $hasta]);
    $porDia = $stmt->fetchAll();
}

// --- Listado de pedidos del período ---
$stmt = $pdo->prepare(
    "SELECT codigo, nombre_cliente, origen, estado, total, creado_en
     FROM pedidos
     WHERE DATE(creado_en) BETWEEN ? AND ? AND estado != 'cancelado'
     ORDER BY creado_en DESC
     LIMIT 300"
);
$stmt->execute([$desde, $hasta]);
$pedidos = $stmt->fetchAll();

$etiquetasOrigen = ['web' => 'Pedidos web', 'mostrador' => 'Mostrador'];
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Ventas</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  .nav-admin a:hover{ color:#1F2A44; text-decoration:underline; }
  h1{ font-size:1.4rem; margin:0 0 4px; }
  p.sub{ color:#5B6478; margin:0 0 22px; }

  .filtros{ display:flex; align-items:center; gap:10px; margin-bottom:26px; flex-wrap:wrap; }
  .tab{
    padding:9px 16px; border-radius:9px; font-size:0.86rem; font-weight:600;
    text-decoration:none; color:#1F2A44; border:1px solid rgba(31,42,68,0.15); background:#fff;
  }
  .tab.activo{ background:#1B4A9C; color:#fff; border-color:#1B4A9C; }
  .form-rango{ display:flex; align-items:center; gap:8px; }
  .form-rango input[type="date"]{
    padding:8px 10px; border-radius:9px; border:1px solid rgba(31,42,68,0.15); font-family:inherit; font-size:0.85rem;
  }
  .form-rango button{
    font-family:inherit; font-weight:600; font-size:0.85rem; border:none; border-radius:9px;
    padding:9px 16px; cursor:pointer; background:#1F2A44; color:#fff;
  }

  .tarjetas{ display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:14px; max-width:760px; margin-bottom:26px; }
  .tarjeta{ background:#fff; border-radius:14px; padding:18px 20px; }
  .tarjeta .etiqueta{ font-size:0.78rem; color:#5B6478; margin-bottom:4px; }
  .tarjeta .valor{ font-size:1.5rem; font-weight:700; }
  .nota-cancelados{ font-size:0.8rem; color:#5B6478; margin:-14px 0 26px; }

  table{ width:100%; max-width:860px; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; margin-bottom:28px; }
  th, td{ text-align:left; padding:11px 16px; border-bottom:1px solid rgba(31,42,68,0.08); font-size:0.88rem; }
  th{ font-size:0.78rem; color:#5B6478; font-weight:600; }
  .badge-origen{ font-size:0.75rem; padding:3px 9px; border-radius:999px; background:#EFEAF7; color:#6E4E9E; }
  .badge-estado{ font-size:0.75rem; padding:3px 9px; border-radius:999px; background:#FFF4DE; }
  .sin-datos{ color:#5B6478; }

  h2{ font-size:1.05rem; margin:0 0 12px; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · <a href="pedidos.php">Pedidos web</a> · <a href="mostrador.php">Mostrador</a> · Ventas</div>

<h1>Ventas</h1>
<p class="sub">No incluye pedidos cancelados.</p>

<div class="filtros">
  <a class="tab <?= $periodo === 'hoy' ? 'activo' : '' ?>" href="?periodo=hoy">Hoy</a>
  <a class="tab <?= $periodo === 'mes' ? 'activo' : '' ?>" href="?periodo=mes">Este mes</a>
  <form class="form-rango" method="get" action="">
    <input type="hidden" name="periodo" value="rango">
    <input type="date" name="desde" value="<?= $periodo === 'rango' ? htmlspecialchars($desde) : '' ?>" required>
    <span style="color:#5B6478; font-size:0.85rem;">a</span>
    <input type="date" name="hasta" value="<?= $periodo === 'rango' ? htmlspecialchars($hasta) : '' ?>" required>
    <button type="submit">Ver</button>
  </form>
</div>

<div class="tarjetas">
  <div class="tarjeta">
    <div class="etiqueta">Total facturado</div>
    <div class="valor">$<?= number_format($resumen['total'], 0, ',', '.') ?></div>
  </div>
  <div class="tarjeta">
    <div class="etiqueta">Pedidos</div>
    <div class="valor"><?= (int) $resumen['cantidad'] ?></div>
  </div>
  <div class="tarjeta">
    <div class="etiqueta">Ticket promedio</div>
    <div class="valor">$<?= number_format($promedio, 0, ',', '.') ?></div>
  </div>
</div>
<?php if ($cancelados > 0): ?>
  <p class="nota-cancelados">+ <?= $cancelados ?> pedido(s) cancelado(s) en este período (no se cuentan arriba).</p>
<?php endif; ?>

<h2>Por canal</h2>
<table>
  <thead><tr><th>Canal</th><th>Pedidos</th><th>Total</th></tr></thead>
  <tbody>
    <?php if (!$porOrigen): ?>
      <tr><td colspan="3" class="sin-datos">Sin ventas en este período.</td></tr>
    <?php endif; ?>
    <?php foreach ($porOrigen as $o): ?>
      <tr>
        <td><?= $etiquetasOrigen[$o['origen']] ?? htmlspecialchars($o['origen']) ?></td>
        <td><?= (int) $o['cantidad'] ?></td>
        <td>$<?= number_format($o['total'], 0, ',', '.') ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php if ($porDia): ?>
  <h2>Por día</h2>
  <table>
    <thead><tr><th>Fecha</th><th>Pedidos</th><th>Total</th></tr></thead>
    <tbody>
      <?php foreach ($porDia as $d): ?>
        <tr>
          <td><?= date('d/m/Y', strtotime($d['fecha'])) ?></td>
          <td><?= (int) $d['cantidad'] ?></td>
          <td>$<?= number_format($d['total'], 0, ',', '.') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<h2>Detalle de pedidos</h2>
<table>
  <thead><tr><th>Código</th><th>Cliente</th><th>Canal</th><th>Estado</th><th>Fecha</th><th>Total</th></tr></thead>
  <tbody>
    <?php if (!$pedidos): ?>
      <tr><td colspan="6" class="sin-datos">No hay pedidos en este período.</td></tr>
    <?php endif; ?>
    <?php foreach ($pedidos as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['codigo']) ?></td>
        <td><?= htmlspecialchars($p['nombre_cliente']) ?></td>
        <td><span class="badge-origen"><?= $etiquetasOrigen[$p['origen']] ?? $p['origen'] ?></span></td>
        <td><span class="badge-estado"><?= htmlspecialchars($p['estado']) ?></span></td>
        <td><?= date('d/m H:i', strtotime($p['creado_en'])) ?></td>
        <td>$<?= number_format($p['total'], 0, ',', '.') ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php if (count($pedidos) === 300): ?>
  <p class="sin-datos" style="font-size:0.82rem;">Se muestran los últimos 300 pedidos del período — si hay más, acotá el rango de fechas.</p>
<?php endif; ?>

</body>
</html>
