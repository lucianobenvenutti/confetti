<?php
/**
 * Análisis de ventas (solo dueño): qué se vende más y menos, en qué mes, día y horario.
 * Mismo criterio que la pantalla "Ventas": se cuentan los pedidos que NO están cancelados.
 * Los gráficos son barras hechas con HTML/CSS (sin librerías externas: carga rápido y no
 * depende de internet).
 */
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion([ROL_DUENO]);

$hoy = $pdo->query('SELECT CURDATE()')->fetchColumn();
$meses = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
$mesesLargo = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$dias = [2 => 'Lunes', 3 => 'Martes', 4 => 'Miércoles', 5 => 'Jueves', 6 => 'Viernes', 7 => 'Sábado', 1 => 'Domingo']; // DAYOFWEEK de MySQL: 1 = domingo

// ---------------------------------------------------------------- filtros
$periodos = [
    '30d'        => 'Últimos 30 días',
    'mes'        => 'Este mes',
    'mes_pasado' => 'Mes pasado',
    '90d'        => 'Últimos 3 meses',
    '12m'        => 'Últimos 12 meses',
];
$periodo = isset($periodos[$_GET['periodo'] ?? '']) ? $_GET['periodo'] : '30d';
$metricas = [
    'plata'    => 'Por plata ($)',
    'veces'    => 'Por veces que se vendió',
    'ganancia' => 'Por ganancia estimada',
];
$metrica = isset($metricas[$_GET['metrica'] ?? '']) ? $_GET['metrica'] : 'plata';

$d = new DateTime($hoy);
switch ($periodo) {
    case 'mes':        $desde = $d->format('Y-m-01'); $hasta = $hoy; break;
    case 'mes_pasado':
        $primero = new DateTime($d->format('Y-m-01'));
        $hasta = (clone $primero)->modify('-1 day')->format('Y-m-d');
        $desde = (new DateTime($hasta))->format('Y-m-01');
        break;
    case '90d':        $desde = (clone $d)->modify('-89 days')->format('Y-m-d'); $hasta = $hoy; break;
    case '12m':        $desde = (clone $d)->modify('-364 days')->format('Y-m-d'); $hasta = $hoy; break;
    default:           $desde = (clone $d)->modify('-29 days')->format('Y-m-d'); $hasta = $hoy;
}
$largo = (int) (new DateTime($desde))->diff(new DateTime($hasta))->days + 1;
$hastaAnt = (new DateTime($desde))->modify('-1 day')->format('Y-m-d');
$desdeAnt = (new DateTime($hastaAnt))->modify('-' . ($largo - 1) . ' days')->format('Y-m-d');

$estadoOk = "p.estado != 'cancelado'";
$enRango = "p.creado_en >= ? AND p.creado_en < DATE_ADD(?, INTERVAL 1 DAY)";

function resumenPeriodo(PDO $pdo, string $a, string $b): array {
    $s = $pdo->prepare("SELECT COUNT(*) AS n, COALESCE(SUM(total),0) AS t FROM pedidos p
                        WHERE p.estado != 'cancelado' AND p.creado_en >= ? AND p.creado_en < DATE_ADD(?, INTERVAL 1 DAY)");
    $s->execute([$a, $b]);
    return $s->fetch();
}
$res = resumenPeriodo($pdo, $desde, $hasta);
$resAnt = resumenPeriodo($pdo, $desdeAnt, $hastaAnt);
$ticket = $res['n'] > 0 ? $res['t'] / $res['n'] : 0;

// ---------------------------------------------------------------- ranking de productos y combos
$filas = [];
$s = $pdo->prepare(
    "SELECT pr.id, pr.nombre, pr.descripcion, pr.stock_actual, pr.tipo_venta,
            COALESCE(SUM(x.subtotal), 0) AS plata,
            COUNT(DISTINCT x.pid) AS veces,
            COALESCE(SUM(x.subtotal - x.cantidad * pr.precio_compra), 0) AS ganancia,
            (pr.precio_compra IS NOT NULL) AS tiene_costo
     FROM productos pr
     LEFT JOIN (
        SELECT i.producto_id, i.subtotal, i.cantidad, p.id AS pid
        FROM pedido_items i JOIN pedidos p ON p.id = i.pedido_id
        WHERE $estadoOk AND $enRango AND i.producto_id IS NOT NULL
     ) x ON x.producto_id = pr.id
     WHERE pr.activo = 1
     GROUP BY pr.id"
);
$s->execute([$desde, $hasta]);
foreach ($s->fetchAll() as $r) {
    $r['tipo'] = 'producto';
    $filas[] = $r;
}
$s = $pdo->prepare(
    "SELECT cb.id, cb.nombre, cb.descripcion,
            COALESCE(SUM(x.subtotal), 0) AS plata, COUNT(DISTINCT x.pid) AS veces
     FROM combos cb
     LEFT JOIN (
        SELECT i.combo_id, i.subtotal, p.id AS pid
        FROM pedido_items i JOIN pedidos p ON p.id = i.pedido_id
        WHERE $estadoOk AND $enRango AND i.combo_id IS NOT NULL
     ) x ON x.combo_id = cb.id
     WHERE cb.activo = 1
     GROUP BY cb.id"
);
$s->execute([$desde, $hasta]);
foreach ($s->fetchAll() as $r) {
    $r['tipo'] = 'combo'; $r['stock_actual'] = null; $r['ganancia'] = 0; $r['tiene_costo'] = 0; $r['tipo_venta'] = 'unidad';
    $filas[] = $r;
}
// Con "ganancia" solo cuentan productos con precio de compra cargado
if ($metrica === 'ganancia') {
    $filas = array_values(array_filter($filas, fn($f) => $f['tipo'] === 'producto' && $f['tiene_costo']));
}
$valor = fn($f) => (float) $f[$metrica];

$conVentas = array_values(array_filter($filas, fn($f) => $valor($f) > 0));
usort($conVentas, fn($a, $b) => $valor($b) <=> $valor($a));
$masVendidos = array_slice($conVentas, 0, 10);

$menos = $filas;
usort($menos, fn($a, $b) => [$valor($a), $a['nombre']] <=> [$valor($b), $b['nombre']]);
$menosVendidos = array_slice($menos, 0, 10);
$sinVentas = array_values(array_filter($filas, fn($f) => (float) $f['veces'] == 0 && ($f['tipo'] === 'combo' || (float) $f['stock_actual'] > 0)));

// ---------------------------------------------------------------- día de la semana y hora
$s = $pdo->prepare("SELECT DAYOFWEEK(p.creado_en) AS d, COUNT(*) AS n, COALESCE(SUM(p.total),0) AS t
                    FROM pedidos p WHERE $estadoOk AND $enRango GROUP BY d");
$s->execute([$desde, $hasta]);
$porDia = [];
foreach ($s->fetchAll() as $r) $porDia[(int) $r['d']] = $r;

$s = $pdo->prepare("SELECT HOUR(p.creado_en) AS h, COUNT(*) AS n, COALESCE(SUM(p.total),0) AS t
                    FROM pedidos p WHERE $estadoOk AND $enRango GROUP BY h");
$s->execute([$desde, $hasta]);
$porHora = [];
foreach ($s->fetchAll() as $r) $porHora[(int) $r['h']] = $r;

// ---------------------------------------------------------------- por categoría
$s = $pdo->prepare(
    "SELECT c.nombre, c.color_acento, COALESCE(SUM(i.subtotal),0) AS plata
     FROM pedido_items i
     JOIN pedidos p ON p.id = i.pedido_id
     JOIN productos pr ON pr.id = i.producto_id
     JOIN categorias c ON c.id = pr.categoria_id
     WHERE $estadoOk AND $enRango
     GROUP BY c.id ORDER BY plata DESC"
);
$s->execute([$desde, $hasta]);
$porCategoria = $s->fetchAll();

// ---------------------------------------------------------------- últimos 12 meses
$inicio12 = (new DateTime($d->format('Y-m-01')))->modify('-11 months')->format('Y-m-d');
$s = $pdo->prepare("SELECT DATE_FORMAT(p.creado_en, '%Y-%m') AS ym, COUNT(*) AS n, COALESCE(SUM(p.total),0) AS t
                    FROM pedidos p WHERE $estadoOk AND p.creado_en >= ? GROUP BY ym");
$s->execute([$inicio12]);
$porMes = [];
foreach ($s->fetchAll() as $r) $porMes[$r['ym']] = $r;

$metricaMes = $metrica === 'veces' ? 'veces' : 'plata';
$s = $pdo->prepare(
    "SELECT DATE_FORMAT(p.creado_en, '%Y-%m') AS ym,
            COALESCE(pr.nombre, CONCAT('Combo ', cb.nombre)) AS nombre,
            SUM(i.subtotal) AS plata, COUNT(DISTINCT p.id) AS veces
     FROM pedido_items i
     JOIN pedidos p ON p.id = i.pedido_id
     LEFT JOIN productos pr ON pr.id = i.producto_id
     LEFT JOIN combos cb ON cb.id = i.combo_id
     WHERE $estadoOk AND p.creado_en >= ?
     GROUP BY ym, i.producto_id, i.combo_id"
);
$s->execute([$inicio12]);
$estrellas = [];
foreach ($s->fetchAll() as $r) $estrellas[$r['ym']][] = $r;
foreach ($estrellas as $ym => &$lista) {
    usort($lista, fn($a, $b) => (float) $b[$metricaMes] <=> (float) $a[$metricaMes]);
    $lista = array_slice($lista, 0, 3);
}
unset($lista);

$mesesLista = [];
$cursor = new DateTime($inicio12);
for ($i = 0; $i < 12; $i++) {
    $ym = $cursor->format('Y-m');
    $mesesLista[] = ['ym' => $ym, 'etq' => $meses[(int) $cursor->format('n')] . ' ' . $cursor->format('y'),
                     'n' => (int) ($porMes[$ym]['n'] ?? 0), 't' => (float) ($porMes[$ym]['t'] ?? 0)];
    $cursor->modify('+1 month');
}

// ---------------------------------------------------------------- helpers de dibujo
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$pesos = fn($n) => '$' . number_format((float) $n, 0, ',', '.');
$fmtValor = function ($f) use ($metrica, $pesos) {
    if ($metrica === 'veces') return (int) $f['veces'] . ($f['veces'] == 1 ? ' venta' : ' ventas');
    return $pesos($f[$metrica]);
};
$nombreFila = fn($f) => ($f['tipo'] === 'combo' ? '🎁 ' : '') . $f['nombre'];

function barrasH(array $items, string $color = '#1B4A9C'): string {
    $max = 0; foreach ($items as $i) $max = max($max, $i['v']);
    $out = '';
    foreach ($items as $i) {
        $ancho = $max > 0 ? max(0.0, $i['v'] / $max * 100) : 0;
        $out .= '<div class="fila-barra"><div class="etq-barra" title="' . htmlspecialchars($i['etq']) . '">' . htmlspecialchars($i['etq'])
              . (!empty($i['sub']) ? '<small>' . htmlspecialchars($i['sub']) . '</small>' : '')
              . '</div><div class="pista"><div class="relleno" style="width:' . round($ancho, 1) . '%;background:' . htmlspecialchars($i['color'] ?? $color) . '"></div></div>'
              . '<div class="val-barra">' . htmlspecialchars($i['txt']) . '</div></div>';
    }
    return $out;
}
function columnasV(array $items, string $color = '#1B4A9C'): string {
    $max = 0; foreach ($items as $i) $max = max($max, $i['v']);
    $out = '<div class="columnas">';
    foreach ($items as $i) {
        $alto = $max > 0 ? $i['v'] / $max * 100 : 0;
        $out .= '<div class="col" title="' . htmlspecialchars($i['txt']) . '"><div class="col-val">' . ($i['v'] > 0 ? htmlspecialchars($i['corto'] ?? '') : '') . '</div>'
              . '<div class="col-pista"><div class="col-relleno' . (!empty($i['resalta']) ? ' resalta' : '') . '" style="height:' . round($alto, 1) . '%;background:' . htmlspecialchars($color) . '"></div></div>'
              . '<div class="col-etq">' . htmlspecialchars($i['etq']) . '</div></div>';
    }
    return $out . '</div>';
}
function corto($n): string {   // 1.250.000 -> 1,3M ; 45.300 -> 45k
    $n = (float) $n;
    if ($n >= 1000000) return rtrim(rtrim(number_format($n / 1000000, 1, ',', ''), '0'), ',') . 'M';
    if ($n >= 1000) return round($n / 1000) . 'k';
    return (string) round($n);
}

// ---------------------------------------------------------------- conclusiones automáticas
$ideas = [];
if ($res['n'] > 0) {
    if ($masVendidos) $ideas[] = ['🏆', '<strong>' . $h($nombreFila($masVendidos[0])) . '</strong> es lo que más se vendió en este período (' . $h($fmtValor($masVendidos[0])) . ').'];

    if ($porDia) {
        $mejor = null; foreach ($porDia as $k => $r) if (!$mejor || $r['t'] > $porDia[$mejor]['t']) $mejor = $k;
        $ideas[] = ['📅', 'El día que más se vende es el <strong>' . $h(mb_strtolower($dias[$mejor])) . '</strong> (' . $h($pesos($porDia[$mejor]['t'])) . ' en total en el período).'];
    }
    if ($porHora) {
        $mh = null; foreach ($porHora as $k => $r) if ($mh === null || $r['n'] > $porHora[$mh]['n']) $mh = $k;
        $ideas[] = ['⏰', 'La hora con más movimiento es alrededor de las <strong>' . $mh . ' hs</strong> (' . (int) $porHora[$mh]['n'] . ' ventas).'];
    }
    if ($resAnt['t'] > 0) {
        $var = ($res['t'] - $resAnt['t']) / $resAnt['t'] * 100;
        $ideas[] = [$var >= 0 ? '📈' : '📉', 'Se vendió <strong>' . ($var >= 0 ? 'un ' . round($var) . '% más' : 'un ' . abs(round($var)) . '% menos') . '</strong> que en el período anterior de la misma duración.'];
    }
    if ($sinVentas) {
        $n = count($sinVentas);
        $nombres = implode(', ', array_map(fn($f) => $nombreFila($f), array_slice($sinVentas, 0, 4)));
        $ideas[] = ['🧊', '<strong>' . $n . ' producto' . ($n == 1 ? '' : 's') . ' con stock no se vendió nada</strong> en este período: ' . $h($nombres) . ($n > 4 ? ' y ' . ($n - 4) . ' más' : '') . '. Capaz conviene ponerlos en oferta o dejar de comprarlos.'];
    }
    if ($res['n'] < 20) $ideas[] = ['ℹ️', 'Todavía hay pocas ventas registradas, así que estas conclusiones son orientativas. Mejoran a medida que pasa el tiempo.'];
}
?>
<!DOCTYPE html>
<html lang="es-AR" translate="no">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon-32.png">
<link rel="icon" href="../favicon.ico" sizes="48x48">
<link rel="apple-touch-icon" href="../assets/apple-touch-icon.png">
<meta name="theme-color" content="#A9E0EA">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Análisis de ventas</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:28px 24px 70px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  .nav-admin a:hover{ color:#1F2A44; text-decoration:underline; }
  h1{ font-size:1.4rem; margin:0 0 4px; }
  h2{ font-size:1.05rem; margin:0 0 4px; }
  p.sub{ color:#5B6478; margin:0 0 18px; max-width:720px; }
  .nota{ color:#5B6478; font-size:0.82rem; margin:0 0 12px; }
  .contenido{ max-width:1000px; }

  .filtros{ display:flex; flex-wrap:wrap; gap:8px; margin-bottom:10px; align-items:center; }
  .tab{ padding:8px 14px; border-radius:9px; font-size:0.85rem; font-weight:600; text-decoration:none; color:#1F2A44; border:1px solid rgba(31,42,68,0.15); background:#fff; }
  .tab.activo{ background:#1B4A9C; color:#fff; border-color:#1B4A9C; }
  .tab.m.activo{ background:#E8483C; border-color:#E8483C; }
  .etiqueta-filtro{ font-size:0.8rem; color:#5B6478; margin-right:4px; }

  .tarjetas{ display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:12px; margin:18px 0; }
  .tarjeta{ background:#fff; border-radius:14px; padding:16px 18px; }
  .tarjeta .e{ font-size:0.78rem; color:#5B6478; margin-bottom:4px; }
  .tarjeta .v{ font-size:1.5rem; font-weight:700; }
  .tarjeta .cmp{ font-size:0.78rem; margin-top:3px; }
  .sube{ color:#1E9C7C; } .baja{ color:#E8483C; } .igual{ color:#5B6478; }

  .ideas{ background:#fff; border:1px solid rgba(31,42,68,0.1); border-radius:14px; padding:6px 18px; margin-bottom:20px; }
  .idea{ display:flex; gap:12px; padding:11px 0; border-bottom:1px solid rgba(31,42,68,0.07); font-size:0.93rem; line-height:1.4; }
  .idea:last-child{ border-bottom:none; }
  .idea .ic{ font-size:1.2rem; flex:none; }

  .grilla{ display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
  @media (max-width:860px){ .grilla{ grid-template-columns:1fr; } }
  .panel{ background:#fff; border-radius:14px; padding:18px 20px 16px; margin-bottom:16px; }
  .grilla .panel{ margin-bottom:0; }
  .vacio{ color:#8891a3; padding:14px 0; font-size:0.9rem; }

  .fila-barra{ display:grid; grid-template-columns:minmax(110px, 38%) 1fr auto; gap:10px; align-items:center; padding:5px 0; font-size:0.86rem; }
  .etq-barra{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .etq-barra small{ display:block; color:#8891a3; font-size:0.72rem; overflow:hidden; text-overflow:ellipsis; }
  .pista{ background:#F2F4F8; border-radius:6px; height:14px; overflow:hidden; }
  .relleno{ height:100%; border-radius:6px; min-width:2px; }
  .val-barra{ font-weight:700; font-size:0.82rem; min-width:64px; text-align:right; white-space:nowrap; }

  .columnas{ display:flex; gap:6px; align-items:flex-end; height:190px; padding-top:6px; overflow-x:auto; }
  .col{ flex:1; min-width:26px; display:flex; flex-direction:column; align-items:center; height:100%; }
  .col-val{ font-size:0.68rem; color:#5B6478; height:16px; white-space:nowrap; }
  .col-pista{ flex:1; width:100%; display:flex; align-items:flex-end; }
  .col-relleno{ width:100%; border-radius:6px 6px 0 0; min-height:2px; opacity:.85; }
  .col-relleno.resalta{ opacity:1; box-shadow:0 0 0 2px rgba(232,72,60,.35); }
  .col-etq{ font-size:0.72rem; color:#5B6478; margin-top:5px; white-space:nowrap; }

  table{ width:100%; border-collapse:collapse; font-size:0.88rem; }
  th, td{ text-align:left; padding:9px 10px; border-bottom:1px solid rgba(31,42,68,0.07); vertical-align:top; }
  th{ font-size:0.76rem; color:#5B6478; text-transform:uppercase; letter-spacing:.03em; }
  .puesto{ color:#8891a3; font-size:0.78rem; }
  .oro{ font-weight:700; }
  .scroll-x{ overflow-x:auto; }
</style>
</head>
<body>
<div class="contenido">

<div class="nav-admin"><a href="index.php">← Panel</a> · <a href="ventas.php">Ventas</a> · Análisis <?= barraSesionHtml() ?></div>

<h1>Análisis de ventas</h1>
<p class="sub">Qué se vende más, qué se vende menos y en qué momento. No incluye pedidos cancelados.
   Período: <strong><?= $h($periodos[$periodo]) ?></strong> (<?= $h(date('d/m/Y', strtotime($desde))) ?> al <?= $h(date('d/m/Y', strtotime($hasta))) ?>).</p>

<div class="filtros">
  <span class="etiqueta-filtro">Período:</span>
  <?php foreach ($periodos as $k => $etq): ?>
    <a class="tab <?= $periodo === $k ? 'activo' : '' ?>" href="?periodo=<?= $k ?>&metrica=<?= $metrica ?>"><?= $h($etq) ?></a>
  <?php endforeach; ?>
</div>
<div class="filtros">
  <span class="etiqueta-filtro">Ordenar productos:</span>
  <?php foreach ($metricas as $k => $etq): ?>
    <a class="tab m <?= $metrica === $k ? 'activo' : '' ?>" href="?periodo=<?= $periodo ?>&metrica=<?= $k ?>"><?= $h($etq) ?></a>
  <?php endforeach; ?>
</div>

<?php
$cmp = function ($act, $ant, $esPlata = true) use ($pesos) {
    if ($ant <= 0) return '<span class="igual">sin datos del período anterior</span>';
    $v = ($act - $ant) / $ant * 100;
    $cls = abs($v) < 1 ? 'igual' : ($v > 0 ? 'sube' : 'baja');
    $flecha = abs($v) < 1 ? '=' : ($v > 0 ? '▲' : '▼');
    return '<span class="' . $cls . '">' . $flecha . ' ' . abs(round($v)) . '% vs. período anterior</span>';
};
?>
<div class="tarjetas">
  <div class="tarjeta"><div class="e">Total vendido</div><div class="v"><?= $h($pesos($res['t'])) ?></div><div class="cmp"><?= $cmp($res['t'], $resAnt['t']) ?></div></div>
  <div class="tarjeta"><div class="e">Cantidad de ventas</div><div class="v"><?= (int) $res['n'] ?></div><div class="cmp"><?= $cmp($res['n'], $resAnt['n']) ?></div></div>
  <div class="tarjeta"><div class="e">Venta promedio</div><div class="v"><?= $h($pesos($ticket)) ?></div><div class="cmp igual">por cada venta</div></div>
</div>

<?php if ($res['n'] == 0): ?>
  <div class="panel"><div class="vacio">Todavía no hay ventas en este período. Probá con otro período arriba.</div></div>
<?php else: ?>

<?php if ($ideas): ?>
<div class="ideas">
  <?php foreach ($ideas as [$ic, $txt]): ?><div class="idea"><span class="ic"><?= $ic ?></span><span><?= $txt ?></span></div><?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($metrica === 'ganancia'): ?>
  <p class="nota">La ganancia estimada es lo que se cobró menos lo que costó (precio de compra). Solo cuentan los productos que tienen el precio de compra cargado.</p>
<?php endif; ?>

<div class="grilla">
  <div class="panel">
    <h2>🏆 Lo más vendido</h2>
    <p class="nota"><?= $h($metricas[$metrica]) ?> · top 10</p>
    <?php if (!$masVendidos): ?><div class="vacio">No hay ventas de productos en este período.</div>
    <?php else: echo barrasH(array_map(fn($f) => ['etq' => $nombreFila($f), 'sub' => $f['descripcion'] ?? '', 'v' => $valor($f), 'txt' => $fmtValor($f)], $masVendidos), '#1E9C7C'); endif; ?>
  </div>
  <div class="panel">
    <h2>🧊 Lo que menos se vende</h2>
    <p class="nota"><?= $h($metricas[$metrica]) ?> · los 10 de más abajo (incluye los que no se vendieron)</p>
    <?php if (!$menosVendidos): ?><div class="vacio">Sin datos.</div>
    <?php else: echo barrasH(array_map(fn($f) => [
        'etq' => $nombreFila($f),
        'sub' => ($f['descripcion'] ?? '') . ($f['stock_actual'] !== null && $f['stock_actual'] > 0 ? ' · stock: ' . rtrim(rtrim(number_format((float) $f['stock_actual'], 3, ',', ''), '0'), ',') : ''),
        'v' => $valor($f), 'txt' => $valor($f) > 0 ? $fmtValor($f) : 'sin ventas', 'color' => '#E8483C',
    ], $menosVendidos)); endif; ?>
  </div>
</div>

<div class="grilla">
  <div class="panel">
    <h2>📅 Por día de la semana</h2>
    <p class="nota">Cuánto se vendió en cada día (suma del período).</p>
    <?php
      $maxDia = 0; foreach ($porDia as $r) $maxDia = max($maxDia, (float) $r['t']);
      $cols = [];
      foreach ($dias as $k => $nombre) {
          $t = (float) ($porDia[$k]['t'] ?? 0); $n = (int) ($porDia[$k]['n'] ?? 0);
          $cols[] = ['etq' => mb_substr($nombre, 0, 3), 'v' => $t, 'corto' => corto($t), 'txt' => "$nombre: " . $pesos($t) . " ($n ventas)", 'resalta' => $maxDia > 0 && $t == $maxDia];
      }
      echo columnasV($cols, '#1B4A9C');
    ?>
  </div>
  <div class="panel">
    <h2>⏰ Por hora del día</h2>
    <p class="nota">Cantidad de ventas según la hora en que se hicieron.</p>
    <?php
      $hs = array_keys($porHora);
      $hMin = min($hs); $hMax = max($hs);
      $maxHora = 0; foreach ($porHora as $r) $maxHora = max($maxHora, (int) $r['n']);
      $cols = [];
      for ($x = $hMin; $x <= $hMax; $x++) {
          $n = (int) ($porHora[$x]['n'] ?? 0);
          $cols[] = ['etq' => $x . 'h', 'v' => $n, 'corto' => (string) $n, 'txt' => "$x hs: $n ventas", 'resalta' => $maxHora > 0 && $n == $maxHora];
      }
      echo columnasV($cols, '#6E4E9E');
    ?>
  </div>
</div>

<?php if ($porCategoria): ?>
<div class="panel">
  <h2>🗂️ Por categoría</h2>
  <p class="nota">Cuánta plata dejó cada categoría en el período (solo productos, sin combos).</p>
  <?php
    $tot = array_sum(array_column($porCategoria, 'plata'));
    echo barrasH(array_map(fn($c) => ['etq' => $c['nombre'], 'v' => (float) $c['plata'],
        'txt' => $pesos($c['plata']) . ($tot > 0 ? ' · ' . round($c['plata'] / $tot * 100) . '%' : ''),
        'color' => $c['color_acento'] ?: '#1B4A9C'], $porCategoria));
  ?>
</div>
<?php endif; ?>

<?php endif; /* hay ventas */ ?>

<div class="panel">
  <h2>📆 Mes a mes (últimos 12 meses)</h2>
  <p class="nota">Total vendido en cada mes.</p>
  <?php
    $maxMes = 0; foreach ($mesesLista as $m) $maxMes = max($maxMes, $m['t']);
    echo columnasV(array_map(fn($m) => ['etq' => $m['etq'], 'v' => $m['t'], 'corto' => corto($m['t']),
        'txt' => $m['etq'] . ': ' . $pesos($m['t']) . ' (' . $m['n'] . ' ventas)', 'resalta' => $maxMes > 0 && $m['t'] == $maxMes], $mesesLista), '#E8483C');
  ?>
</div>

<div class="panel">
  <h2>⭐ Lo más vendido de cada mes</h2>
  <p class="nota">Los 3 que más se vendieron en cada mes (<?= $metricaMes === 'veces' ? 'por veces que se vendió' : 'por plata' ?>).</p>
  <div class="scroll-x">
  <table>
    <thead><tr><th>Mes</th><th>Vendido</th><th>1.º</th><th>2.º</th><th>3.º</th></tr></thead>
    <tbody>
    <?php foreach (array_reverse($mesesLista) as $m): if ($m['n'] == 0) continue; $top = $estrellas[$m['ym']] ?? []; ?>
      <tr>
        <td><strong><?= $h(ucfirst($mesesLargo[(int) substr($m['ym'], 5, 2)]) . ' ' . substr($m['ym'], 0, 4)) ?></strong></td>
        <td><?= $h($pesos($m['t'])) ?><div class="puesto"><?= (int) $m['n'] ?> ventas</div></td>
        <?php for ($i = 0; $i < 3; $i++): ?>
          <td><?php if (isset($top[$i])): ?><span class="<?= $i === 0 ? 'oro' : '' ?>"><?= $h($top[$i]['nombre']) ?></span>
              <div class="puesto"><?= $metricaMes === 'veces' ? (int) $top[$i]['veces'] . ' ventas' : $h($pesos($top[$i]['plata'])) ?></div><?php else: ?>—<?php endif; ?></td>
        <?php endfor; ?>
      </tr>
    <?php endforeach; ?>
    <?php if (!array_filter($mesesLista, fn($m) => $m['n'] > 0)): ?><tr><td colspan="5" class="vacio">Todavía no hay ventas registradas.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

</div>
</body>
</html>
