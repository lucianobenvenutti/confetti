<?php
require 'conexion.php';

$stmt = $pdo->query(
    "SELECT COALESCE(SUM(total), 0) AS total_hoy, COUNT(*) AS cantidad_hoy
     FROM pedidos
     WHERE DATE(creado_en) = CURDATE() AND estado != 'cancelado'"
);
$resumenHoy = $stmt->fetch();

$stmt = $pdo->query(
    "SELECT COUNT(*) AS cantidad
     FROM pedidos
     WHERE origen = 'web' AND estado IN ('pendiente', 'en_preparacion')"
);
$pedidosWebActivos = $stmt->fetch()['cantidad'];

// Cuentas corrientes: cuánta plata hay por cobrar (solo saldos que son deuda).
// En try/catch para que el panel siga abriendo aunque todavía no se haya
// corrido la migración de cuentas corrientes.
$deudaTotal = null;
$cuentasConDeuda = 0;
try {
    $fila = $pdo->query(
        "SELECT COALESCE(SUM(saldo), 0) AS total, COUNT(*) AS cuentas
         FROM (
            SELECT SUM(CASE tipo WHEN 'cargo' THEN monto ELSE -monto END) AS saldo
            FROM cuenta_movimientos
            GROUP BY cuenta_id
            HAVING saldo > 0
         ) t"
    )->fetch();
    $deudaTotal = (float) $fila['total'];
    $cuentasConDeuda = (int) $fila['cuentas'];
} catch (PDOException $e) {
    // tabla inexistente: se muestra el acceso igual, sin el número
}
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Panel</title>
<style>
  :root{
    --paper:#FFFBF3; --paper-dim:#FFF4DE; --ink:#1F2A44; --ink-soft:#5B6478;
    --rojo:#E8483C; --naranja:#F5941F; --verde:#1E9C7C; --violeta:#6E4E9E;
    --linea:rgba(31,42,68,0.12);
  }
  *{ box-sizing:border-box; }
  body{
    margin:0;
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
    background:var(--paper);
    color:var(--ink);
    padding:32px 24px 60px;
  }
  h1{ font-size:1.5rem; margin:0 0 4px; }
  p.sub{ color:var(--ink-soft); margin:0 0 28px; }

  .tiles{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
    gap:16px;
    max-width:900px;
  }
  .tile{
    background:#fff;
    border:1px solid var(--linea);
    border-left:5px solid var(--acento, var(--rojo));
    border-radius:16px;
    padding:22px;
    text-decoration:none;
    color:var(--ink);
    display:block;
    transition:transform .12s ease;
  }
  a.tile:hover{ transform:translateY(-2px); }
  .tile .icono{ font-size:1.8rem; margin-bottom:10px; }
  .tile h2{ font-size:1.05rem; margin:0 0 4px; }
  .tile p{ color:var(--ink-soft); font-size:0.85rem; margin:0; }
  .tile .badge{
    display:inline-block;
    margin-top:10px;
    background:var(--paper-dim);
    color:var(--ink);
    font-size:0.78rem;
    font-weight:600;
    padding:4px 10px;
    border-radius:999px;
  }

  .tile.stat{ cursor:default; }
  .tile.stat .monto{
    font-size:1.9rem;
    font-weight:700;
    margin:4px 0 2px;
  }
</style>
</head>
<body>

<h1>Panel de Confetti</h1>
<p class="sub">Accesos rápidos al día a día del local.</p>

<div class="tiles">

  <a class="tile" href="pedidos.php" style="--acento:var(--naranja);">
    <div class="icono">📋</div>
    <h2>Pedidos web</h2>
    <p>Ver y gestionar los pedidos hechos desde la página.</p>
    <?php if ($pedidosWebActivos > 0): ?>
      <span class="badge"><?= $pedidosWebActivos ?> esperando</span>
    <?php endif; ?>
  </a>

  <a class="tile" href="mostrador.php" style="--acento:var(--rojo);">
    <div class="icono">🛒</div>
    <h2>Mostrador rápido</h2>
    <p>Cargar una venta hecha en persona en el local.</p>
  </a>

  <a class="tile" href="productos.php" style="--acento:var(--verde);">
    <div class="icono">🖼️</div>
    <h2>Productos y categorías</h2>
    <p>Crear, editar, dar de baja y subir fotos.</p>
  </a>

  <a class="tile" href="proveedores.php" style="--acento:var(--azul);">
    <div class="icono">🚚</div>
    <h2>Proveedores</h2>
    <p>A quién pedirle cuando algo se queda sin stock.</p>
  </a>

  <div class="tile stat" style="--acento:var(--violeta);">
    <div class="icono">💰</div>
    <h2>Total de hoy</h2>
    <div class="monto">$<?= number_format($resumenHoy['total_hoy'], 0, ',', '.') ?></div>
    <p><?= $resumenHoy['cantidad_hoy'] ?> pedido(s) hoy (web + mostrador)</p>
  </div>

  <a class="tile" href="cuentas.php" style="--acento:var(--violeta);">
    <div class="icono">📒</div>
    <h2>Cuentas corrientes</h2>
    <p>Clientes que compran fiado: cobrar, sumar o eliminar.</p>
    <?php if ($deudaTotal !== null && $deudaTotal > 0): ?>
      <span class="badge">$<?= number_format($deudaTotal, 0, ',', '.') ?> por cobrar · <?= $cuentasConDeuda ?> cuenta(s)</span>
    <?php endif; ?>
  </a>

  <a class="tile" href="ventas.php" style="--acento:var(--azul);">
    <div class="icono">📊</div>
    <h2>Ventas</h2>
    <p>Por día, por mes, o eligiendo un rango de fechas.</p>
  </a>

  <a class="tile" href="horarios.php" style="--acento:var(--naranja);">
    <div class="icono">🕒</div>
    <h2>Horario de atención</h2>
    <p>Define el cartel de "abierto ahora" en la página.</p>
  </a>

</div>

</body>
</html>
