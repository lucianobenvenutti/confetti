<?php
require 'conexion.php';

$pedidos = $pdo->query(
    "SELECT id, codigo, nombre_cliente, telefono_cliente, origen, estado, total, creado_en
     FROM pedidos
     WHERE DATE(creado_en) = CURDATE()
     ORDER BY FIELD(estado, 'pendiente','en_preparacion','listo','entregado','cancelado'),
              creado_en ASC"
)->fetchAll();

// Traigo los ítems de todos los pedidos de hoy en una sola consulta y los agrupo en PHP
$itemsPorPedido = [];
if ($pedidos) {
    $ids = array_column($pedidos, 'id');
    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare(
        "SELECT pi.pedido_id, pi.cantidad, pi.subtotal,
                COALESCE(pr.nombre, co.nombre) AS nombre_item,
                pr.tipo_venta
         FROM pedido_items pi
         LEFT JOIN productos pr ON pr.id = pi.producto_id
         LEFT JOIN combos co ON co.id = pi.combo_id
         WHERE pi.pedido_id IN ($marcadores)"
    );
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $item) {
        $itemsPorPedido[$item['pedido_id']][] = $item;
    }
}

$mensaje = $_GET['msg'] ?? null;
$tipoMensaje = $_GET['tipo'] ?? 'error';

$etiquetasEstado = [
    'pendiente'      => 'Pendiente',
    'en_preparacion' => 'En preparación',
    'listo'          => 'Listo para retirar',
    'entregado'      => 'Entregado',
    'cancelado'      => 'Cancelado',
];

function etiquetaCantidad($item) {
    if ($item['tipo_venta'] === 'peso') {
        $kg = (float) $item['cantidad'];
        return $kg < 1 ? round($kg * 1000) . 'g' : $kg . 'kg';
    }
    return 'x' . (int) $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Pedidos de hoy</title>
<style>
  body{
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
    background:#FFFBF3;
    color:#1F2A44;
    margin:0;
    padding:32px 24px 60px;
  }
  h1{ font-size:1.5rem; margin-bottom:4px; }
  p.sub{ color:#5B6478; margin-top:0; margin-bottom:24px; }
  .aviso{
    padding:12px 16px;
    border-radius:10px;
    margin-bottom:20px;
    font-size:0.95rem;
    max-width:640px;
  }
  .aviso.ok{ background:#E4F5EE; color:#1E9C7C; border:1px solid #bfe6d7; }
  .aviso.error{ background:#FBEAE8; color:#E8483C; border:1px solid #f3c9c4; }

  .lista{
    display:flex;
    flex-direction:column;
    gap:14px;
    max-width:760px;
  }
  .pedido{
    background:#fff;
    border-radius:14px;
    border:1px solid rgba(31,42,68,0.1);
    border-left:5px solid var(--acento, #5B6478);
    padding:18px 20px;
  }
  .pedido-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
    flex-wrap:wrap;
  }
  .pedido-head .codigo{
    font-weight:700;
    font-size:1.02rem;
  }
  .pedido-head .cliente{
    color:#5B6478;
    font-size:0.88rem;
    margin-top:2px;
  }
  .badges{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
  }
  .badge{
    font-size:0.76rem;
    font-weight:600;
    padding:4px 10px;
    border-radius:999px;
    background:#FFF4DE;
    color:#1F2A44;
    white-space:nowrap;
  }
  .badge.origen{ background:#EFEAF7; color:#6E4E9E; }

  .items{
    margin:14px 0 10px;
    padding-top:12px;
    border-top:1px dashed rgba(31,42,68,0.14);
    font-size:0.9rem;
  }
  .items div{
    display:flex;
    justify-content:space-between;
    padding:3px 0;
    color:#3a4256;
  }
  .total-row{
    display:flex;
    justify-content:space-between;
    font-weight:700;
    padding-top:8px;
    border-top:1px solid rgba(31,42,68,0.1);
    margin-top:4px;
  }

  .acciones{
    margin-top:14px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }
  button{
    font-family:inherit;
    font-weight:600;
    font-size:0.85rem;
    border:none;
    border-radius:8px;
    padding:9px 16px;
    cursor:pointer;
  }
  .btn-avanzar{ background:#1E9C7C; color:#fff; }
  .btn-avanzar:hover{ background:#178067; }
  .btn-cancelar{ background:transparent; color:#E8483C; border:1px solid #E8483C; }
  .btn-cancelar:hover{ background:#FBEAE8; }
  .sin-pedidos{ color:#5B6478; }
</style>
</head>
<body>

<h1>Pedidos de hoy</h1>
<p class="sub">Se ordenan solos: primero lo pendiente, después en preparación, y al final lo ya resuelto.</p>

<?php if ($mensaje): ?>
  <div class="aviso <?= $tipoMensaje === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<?php if (!$pedidos): ?>
  <p class="sin-pedidos">Todavía no hay pedidos cargados hoy.</p>
<?php endif; ?>

<div class="lista">
  <?php foreach ($pedidos as $p):
    $acentos = [
      'pendiente' => '#F5941F', 'en_preparacion' => '#1B4A9C',
      'listo' => '#1E9C7C', 'entregado' => '#8891a3', 'cancelado' => '#E8483C',
    ];
  ?>
    <div class="pedido" style="--acento: <?= $acentos[$p['estado']] ?>;">
      <div class="pedido-head">
        <div>
          <div class="codigo"><?= htmlspecialchars($p['codigo']) ?></div>
          <div class="cliente"><?= htmlspecialchars($p['nombre_cliente']) ?> · <?= htmlspecialchars($p['telefono_cliente']) ?></div>
        </div>
        <div class="badges">
          <span class="badge origen"><?= $p['origen'] === 'web' ? 'Pedido web' : 'Mostrador' ?></span>
          <span class="badge"><?= $etiquetasEstado[$p['estado']] ?></span>
          <span class="badge"><?= date('H:i', strtotime($p['creado_en'])) ?></span>
        </div>
      </div>

      <div class="items">
        <?php foreach ($itemsPorPedido[$p['id']] ?? [] as $item): ?>
          <div>
            <span><?= htmlspecialchars($item['nombre_item']) ?> (<?= etiquetaCantidad($item) ?>)</span>
            <span>$<?= number_format($item['subtotal'], 0, ',', '.') ?></span>
          </div>
        <?php endforeach; ?>
        <div class="total-row">
          <span>Total</span>
          <span>$<?= number_format($p['total'], 0, ',', '.') ?></span>
        </div>
      </div>

      <?php if (in_array($p['estado'], ['pendiente', 'en_preparacion', 'listo'], true)): ?>
        <div class="acciones">
          <form method="post" action="cambiar_estado.php" style="display:inline;">
            <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
            <input type="hidden" name="accion" value="avanzar">
            <button type="submit" class="btn-avanzar">
              <?php
                echo match ($p['estado']) {
                  'pendiente' => 'Empezar a preparar',
                  'en_preparacion' => 'Marcar listo',
                  'listo' => 'Marcar entregado',
                };
              ?>
            </button>
          </form>
          <?php if ($p['estado'] !== 'listo'): ?>
            <form method="post" action="cambiar_estado.php" style="display:inline;"
                  onsubmit="return confirm('¿Cancelar este pedido y liberar su stock reservado?');">
              <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
              <input type="hidden" name="accion" value="cancelar">
              <button type="submit" class="btn-cancelar">Cancelar pedido</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

</body>
</html>
