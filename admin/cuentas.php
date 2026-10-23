<?php
require 'conexion.php';

// El saldo no se guarda: se calcula sumando los cargos y restando los pagos.
// Saldo > 0: el cliente debe. Saldo < 0: tiene saldo a favor.
$cuentas = $pdo->query(
    "SELECT c.id, c.nombre, c.telefono, c.notas,
            COALESCE(SUM(CASE m.tipo WHEN 'cargo' THEN m.monto WHEN 'pago' THEN -m.monto END), 0) AS saldo,
            UNIX_TIMESTAMP() - UNIX_TIMESTAMP(MAX(m.creado_en)) AS seg_ultimo
     FROM cuentas_corrientes c
     LEFT JOIN cuenta_movimientos m ON m.cuenta_id = c.id
     GROUP BY c.id, c.nombre, c.telefono, c.notas
     ORDER BY saldo DESC, c.nombre ASC"
)->fetchAll();

$totalDeuda = 0;
$conDeuda = 0;
foreach ($cuentas as $c) {
    if ($c['saldo'] > 0) {
        $totalDeuda += $c['saldo'];
        $conDeuda++;
    }
}

$mensaje = $_GET['msg'] ?? null;
$tipoMensaje = $_GET['tipo'] ?? 'error';

function pesos($n) {
    return '$' . number_format(abs($n), 0, ',', '.');
}

function hace($segundos) {
    if ($segundos === null) return 'Sin movimientos';
    $min = intdiv(max(0, (int) $segundos), 60);
    if ($min < 1) return 'Hace instantes';
    $h = intdiv($min, 60);
    if ($h < 1) return "Hace $min min";
    if ($h < 24) return "Hace $h h";
    return 'Hace ' . intdiv($h, 24) . ' d';
}
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Cuentas corrientes</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  .nav-admin a:hover{ color:#1F2A44; text-decoration:underline; }
  .cabecera{ display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:22px; }
  h1{ font-size:1.4rem; margin:0; }
  p.sub{ color:#5B6478; margin:4px 0 0; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.95rem; max-width:720px; }
  .aviso.ok{ background:#E4F5EE; color:#1E9C7C; border:1px solid #bfe6d7; }
  .aviso.error{ background:#FBEAE8; color:#E8483C; border:1px solid #f3c9c4; }
  .btn{ font-family:inherit; font-weight:600; font-size:0.88rem; border:none; border-radius:9px; padding:10px 16px; cursor:pointer; text-decoration:none; display:inline-block; }
  .btn-primario{ background:#6E4E9E; color:#fff; }
  .btn-primario:hover{ background:#5a3f82; }

  .resumen{ display:flex; gap:14px; flex-wrap:wrap; margin-bottom:22px; }
  .tarjeta{ background:#fff; border-radius:14px; padding:16px 20px; min-width:190px; }
  .tarjeta .etiqueta{ font-size:0.78rem; color:#5B6478; margin-bottom:4px; }
  .tarjeta .valor{ font-size:1.5rem; font-weight:700; }
  .tarjeta .valor.rojo{ color:#E8483C; }

  .buscador{ margin-bottom:14px; }
  .buscador input{
    width:100%; max-width:340px; padding:10px 14px; border-radius:10px;
    border:1px solid rgba(31,42,68,0.15); font-family:inherit; font-size:0.92rem;
  }

  table{ width:100%; max-width:900px; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; }
  th, td{ text-align:left; padding:13px 16px; border-bottom:1px solid rgba(31,42,68,0.08); vertical-align:middle; font-size:0.9rem; }
  th{ font-size:0.78rem; color:#5B6478; font-weight:600; }
  a.nombre{ color:#1F2A44; text-decoration:none; font-weight:600; }
  a.nombre:hover{ text-decoration:underline; }
  .tel{ display:block; font-size:0.78rem; color:#5B6478; font-weight:400; }
  .saldo{ font-weight:700; white-space:nowrap; }
  .saldo.debe{ color:#E8483C; }
  .saldo.favor{ color:#1E9C7C; }
  .saldo.cero{ color:#5B6478; font-weight:500; }
  .acciones a, .acciones button{ font-size:0.82rem; margin-right:10px; border:none; background:none; cursor:pointer; padding:0; text-decoration:none; font-family:inherit; }
  .acciones a.cobrar{ color:#1E9C7C; font-weight:600; }
  .acciones a.sumar{ color:#1B4A9C; }
  .acciones button.eliminar{ color:#E8483C; }
  .sin-datos{ color:#5B6478; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · Cuentas corrientes · <a href="mostrador.php">Mostrador</a> · <a href="ventas.php">Ventas</a></div>

<div class="cabecera">
  <div>
    <h1>Cuentas corrientes</h1>
    <p class="sub">Clientes que compran fiado. Las ventas se cargan desde el mostrador.</p>
  </div>
  <a class="btn btn-primario" href="cuenta_form.php">+ Nueva cuenta</a>
</div>

<?php if ($mensaje): ?>
  <div class="aviso <?= $tipoMensaje === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<div class="resumen">
  <div class="tarjeta">
    <div class="etiqueta">Total por cobrar</div>
    <div class="valor <?= $totalDeuda > 0 ? 'rojo' : '' ?>"><?= pesos($totalDeuda) ?></div>
  </div>
  <div class="tarjeta">
    <div class="etiqueta">Cuentas con deuda</div>
    <div class="valor"><?= $conDeuda ?> <span style="font-size:0.9rem; color:#5B6478; font-weight:400;">de <?= count($cuentas) ?></span></div>
  </div>
</div>

<?php if (!$cuentas): ?>
  <p class="sin-datos">Todavía no hay cuentas corrientes. Creá una acá o directo desde el mostrador.</p>
<?php else: ?>
  <div class="buscador">
    <input type="text" id="buscar" placeholder="Buscar por nombre o teléfono…" autocomplete="off">
  </div>

  <table>
    <thead>
      <tr>
        <th>Cliente</th>
        <th>Saldo</th>
        <th>Último movimiento</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody id="filas">
      <?php foreach ($cuentas as $c):
        $saldo = (float) $c['saldo'];
        if ($saldo > 0.004)       { $texto = 'Debe ' . pesos($saldo);     $clase = 'debe'; }
        elseif ($saldo < -0.004)  { $texto = 'A favor ' . pesos($saldo);  $clase = 'favor'; }
        else                      { $texto = 'Sin deuda';                 $clase = 'cero'; }

        $avisoBorrado = $saldo > 0.004
            ? $c['nombre'] . ' debe ' . pesos($saldo) . '. Si eliminás la cuenta se pierde ese registro y su historial. ¿Eliminar igual?'
            : '¿Eliminar la cuenta de ' . $c['nombre'] . ' y todo su historial?';
      ?>
        <tr data-busqueda="<?= htmlspecialchars($c['nombre'] . ' ' . ($c['telefono'] ?? '')) ?>">
          <td>
            <a class="nombre" href="cuenta_detalle.php?id=<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></a>
            <?php if ($c['telefono']): ?><span class="tel"><?= htmlspecialchars($c['telefono']) ?></span><?php endif; ?>
          </td>
          <td><span class="saldo <?= $clase ?>"><?= $texto ?></span></td>
          <td><?= hace($c['seg_ultimo']) ?></td>
          <td class="acciones">
            <a class="cobrar" href="cuenta_detalle.php?id=<?= $c['id'] ?>&accion=pago#form-pago">Cobrar</a>
            <a class="sumar" href="cuenta_detalle.php?id=<?= $c['id'] ?>&accion=cargo#form-cargo">Sumar</a>
            <form method="post" action="eliminar_cuenta.php" style="display:inline;"
                  onsubmit="return confirm(<?= htmlspecialchars(json_encode($avisoBorrado, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>);">
              <input type="hidden" name="id" value="<?= $c['id'] ?>">
              <button type="submit" class="eliminar">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<script>
  const buscar = document.getElementById('buscar');
  if (buscar) {
    buscar.addEventListener('input', () => {
      const q = buscar.value.trim().toLowerCase();
      document.querySelectorAll('#filas tr').forEach(tr => {
        tr.style.display = !q || tr.dataset.busqueda.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }
</script>

</body>
</html>
