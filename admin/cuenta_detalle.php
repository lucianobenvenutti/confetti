<?php
require 'conexion.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: cuentas.php?msg=' . urlencode('Cuenta inválida.') . '&tipo=error');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM cuentas_corrientes WHERE id = ?');
$stmt->execute([$id]);
$cuenta = $stmt->fetch();

if (!$cuenta) {
    header('Location: cuentas.php?msg=' . urlencode('Esa cuenta no existe.') . '&tipo=error');
    exit;
}

// Historial completo en orden cronológico, para poder ir acumulando el saldo
$stmt = $pdo->prepare(
    "SELECT m.id, m.tipo, m.monto, m.nota, m.creado_en, p.codigo AS codigo_venta
     FROM cuenta_movimientos m
     LEFT JOIN pedidos p ON p.id = m.pedido_id
     WHERE m.cuenta_id = ?
     ORDER BY m.creado_en ASC, m.id ASC"
);
$stmt->execute([$id]);
$movimientos = $stmt->fetchAll();

$saldo = 0.0;
foreach ($movimientos as &$m) {
    $saldo += $m['tipo'] === 'cargo' ? (float) $m['monto'] : -(float) $m['monto'];
    $m['saldo_despues'] = $saldo;
}
unset($m);

$movimientos = array_reverse($movimientos);          // lo más nuevo primero
$hayMas = count($movimientos) > 200;
$movimientos = array_slice($movimientos, 0, 200);

$mensaje = $_GET['msg'] ?? null;
$tipoMensaje = $_GET['tipo'] ?? 'error';
$accion = $_GET['accion'] ?? '';

function pesos($n) {
    return '$' . number_format(abs($n), 0, ',', '.');
}

if ($saldo > 0.004)      { $textoSaldo = 'Debe ' . pesos($saldo);           $claseSaldo = 'debe'; }
elseif ($saldo < -0.004) { $textoSaldo = 'Saldo a favor ' . pesos($saldo);  $claseSaldo = 'favor'; }
else                     { $textoSaldo = 'Sin deuda';                       $claseSaldo = 'cero'; }

$avisoBorrado = $saldo > 0.004
    ? $cuenta['nombre'] . ' debe ' . pesos($saldo) . '. Si eliminás la cuenta se pierde ese registro y su historial. ¿Eliminar igual?'
    : '¿Eliminar la cuenta de ' . $cuenta['nombre'] . ' y todo su historial?';
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — <?= htmlspecialchars($cuenta['nombre']) ?></title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  .nav-admin a:hover{ color:#1F2A44; text-decoration:underline; }
  .cabecera{ display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:14px; margin-bottom:22px; max-width:900px; }
  h1{ font-size:1.5rem; margin:0 0 4px; }
  .datos{ color:#5B6478; font-size:0.9rem; }
  .botones-cabecera{ display:flex; gap:10px; align-items:center; }
  .btn{ font-family:inherit; font-weight:600; font-size:0.85rem; border-radius:9px; padding:9px 15px; cursor:pointer; text-decoration:none; display:inline-block; background:none; border:1px solid rgba(31,42,68,0.2); color:#1F2A44; }
  .btn.eliminar{ color:#E8483C; border-color:#f3c9c4; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.95rem; max-width:900px; }
  .aviso.ok{ background:#E4F5EE; color:#1E9C7C; border:1px solid #bfe6d7; }
  .aviso.error{ background:#FBEAE8; color:#E8483C; border:1px solid #f3c9c4; }

  .saldo-grande{ background:#fff; border-radius:16px; padding:20px 24px; max-width:900px; margin-bottom:20px; }
  .saldo-grande .etiqueta{ font-size:0.8rem; color:#5B6478; margin-bottom:4px; }
  .saldo-grande .valor{ font-size:2rem; font-weight:700; }
  .valor.debe{ color:#E8483C; }
  .valor.favor{ color:#1E9C7C; }
  .valor.cero{ color:#5B6478; }
  .notas{ margin-top:8px; font-size:0.85rem; color:#5B6478; }

  .formularios{ display:grid; grid-template-columns:1fr 1fr; gap:16px; max-width:900px; margin-bottom:28px; }
  .formulario{ background:#fff; border-radius:14px; padding:18px 20px; border-top:4px solid var(--c); }
  .formulario h2{ font-size:1rem; margin:0 0 4px; }
  .formulario p{ font-size:0.8rem; color:#5B6478; margin:0 0 12px; }
  .formulario label{ display:block; font-size:0.78rem; font-weight:600; margin-bottom:4px; }
  .formulario input{
    width:100%; padding:9px 11px; border-radius:9px; border:1px solid rgba(31,42,68,0.15);
    font-family:inherit; font-size:0.92rem; margin-bottom:10px; box-sizing:border-box;
  }
  .formulario button{
    width:100%; border:none; border-radius:9px; padding:11px; color:#fff; font-weight:700;
    font-family:inherit; font-size:0.9rem; cursor:pointer; background:var(--c);
  }
  .formulario.resaltado{ box-shadow:0 0 0 3px rgba(27,74,156,0.18); }

  h2.titulo{ font-size:1.05rem; margin:0 0 12px; }
  table{ width:100%; max-width:900px; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; }
  th, td{ text-align:left; padding:11px 16px; border-bottom:1px solid rgba(31,42,68,0.08); font-size:0.88rem; vertical-align:middle; }
  th{ font-size:0.78rem; color:#5B6478; font-weight:600; }
  td.monto{ font-weight:700; white-space:nowrap; }
  td.monto.cargo{ color:#E8483C; }
  td.monto.pago{ color:#1E9C7C; }
  .chip{ font-size:0.75rem; padding:3px 9px; border-radius:999px; background:#FFF4DE; }
  .chip.pago{ background:#E4F5EE; color:#1E9C7C; }
  .chip.cargo{ background:#FBEAE8; color:#E8483C; }
  .sub-nota{ display:block; font-size:0.78rem; color:#5B6478; }
  .sin-datos{ color:#5B6478; }
  @media (max-width:700px){ .formularios{ grid-template-columns:1fr; } }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · <a href="cuentas.php">Cuentas corrientes</a> · <?= htmlspecialchars($cuenta['nombre']) ?></div>

<div class="cabecera">
  <div>
    <h1><?= htmlspecialchars($cuenta['nombre']) ?></h1>
    <div class="datos"><?= $cuenta['telefono'] ? htmlspecialchars($cuenta['telefono']) : 'Sin teléfono cargado' ?></div>
  </div>
  <div class="botones-cabecera">
    <a class="btn" href="cuenta_form.php?id=<?= $cuenta['id'] ?>">Editar datos</a>
    <form method="post" action="eliminar_cuenta.php" style="margin:0;"
          onsubmit="return confirm(<?= htmlspecialchars(json_encode($avisoBorrado, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>);">
      <input type="hidden" name="id" value="<?= $cuenta['id'] ?>">
      <button type="submit" class="btn eliminar">Eliminar cuenta</button>
    </form>
  </div>
</div>

<?php if ($mensaje): ?>
  <div class="aviso <?= $tipoMensaje === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<div class="saldo-grande">
  <div class="etiqueta">Saldo actual</div>
  <div class="valor <?= $claseSaldo ?>"><?= $textoSaldo ?></div>
  <?php if ($cuenta['notas']): ?><div class="notas"><?= htmlspecialchars($cuenta['notas']) ?></div><?php endif; ?>
</div>

<div class="formularios">
  <form class="formulario <?= $accion === 'pago' ? 'resaltado' : '' ?>" id="form-pago" method="post" action="cuenta_movimiento.php" style="--c:#1E9C7C;">
    <h2>Registrar un pago</h2>
    <p>Lo que el cliente paga se descuenta de su saldo.</p>
    <input type="hidden" name="cuenta_id" value="<?= $cuenta['id'] ?>">
    <input type="hidden" name="tipo" value="pago">
    <label for="monto-pago">Monto ($)</label>
    <input type="number" id="monto-pago" name="monto" step="0.01" min="0.01" max="99999999.99" required>
    <label for="nota-pago">Nota (opcional)</label>
    <input type="text" id="nota-pago" name="nota" maxlength="255" placeholder="Ej: pagó en efectivo">
    <button type="submit">Registrar pago</button>
  </form>

  <form class="formulario <?= $accion === 'cargo' ? 'resaltado' : '' ?>" id="form-cargo" method="post" action="cuenta_movimiento.php" style="--c:#1B4A9C;">
    <h2>Sumar una compra</h2>
    <p>Para algo que se llevó fuera del mostrador. Las ventas cargadas ahí ya suman solas.</p>
    <input type="hidden" name="cuenta_id" value="<?= $cuenta['id'] ?>">
    <input type="hidden" name="tipo" value="cargo">
    <label for="monto-cargo">Monto ($)</label>
    <input type="number" id="monto-cargo" name="monto" step="0.01" min="0.01" max="99999999.99" required>
    <label for="nota-cargo">Nota (opcional)</label>
    <input type="text" id="nota-cargo" name="nota" maxlength="255" placeholder="Ej: torta de cumpleaños">
    <button type="submit">Sumar a la cuenta</button>
  </form>
</div>

<h2 class="titulo">Historial</h2>
<?php if (!$movimientos): ?>
  <p class="sin-datos">Todavía no hay movimientos en esta cuenta.</p>
<?php else: ?>
  <table>
    <thead>
      <tr><th>Fecha</th><th>Detalle</th><th>Monto</th><th>Saldo después</th></tr>
    </thead>
    <tbody>
      <?php foreach ($movimientos as $m):
        $esCargo = $m['tipo'] === 'cargo';
        $despues = (float) $m['saldo_despues'];
      ?>
        <tr>
          <td><?= date('d/m/Y H:i', strtotime($m['creado_en'])) ?></td>
          <td>
            <span class="chip <?= $m['tipo'] ?>"><?= $esCargo ? 'Compra' : 'Pago' ?></span>
            <?php if ($m['codigo_venta']): ?><span class="sub-nota">Venta <?= htmlspecialchars($m['codigo_venta']) ?></span><?php endif; ?>
            <?php if ($m['nota'] && !($esCargo && $m['codigo_venta'])): ?><span class="sub-nota"><?= htmlspecialchars($m['nota']) ?></span><?php endif; ?>
          </td>
          <td class="monto <?= $m['tipo'] ?>"><?= $esCargo ? '+' : '−' ?><?= pesos($m['monto']) ?></td>
          <td><?= $despues > 0.004 ? 'Debe ' . pesos($despues) : ($despues < -0.004 ? 'A favor ' . pesos($despues) : 'Sin deuda') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if ($hayMas): ?><p class="sin-datos" style="font-size:0.82rem;">Se muestran los últimos 200 movimientos.</p><?php endif; ?>
<?php endif; ?>

<script>
  // Si llegan desde "Cobrar" o "Sumar" del listado, el cursor ya queda en el monto
  <?php if ($accion === 'pago'): ?>document.getElementById('monto-pago').focus();<?php endif; ?>
  <?php if ($accion === 'cargo'): ?>document.getElementById('monto-cargo').focus();<?php endif; ?>
</script>

</body>
</html>
