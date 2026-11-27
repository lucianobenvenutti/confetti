<?php
/**
 * Ofertas: el dueño elige productos, elige el descuento (en % o en $) y lo aplica a todos
 * los seleccionados de una vez. En la tienda aparecen en la pestaña "Ofertas" y siguen
 * apareciendo también con su categoría, con el precio anterior tachado.
 */
require 'conexion.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/../api/precios.php';
$usuarioActual = exigirSesion([ROL_DUENO]);

function volverOfertas(string $msg, string $tipo): void {
    header('Location: ofertas.php?msg=' . urlencode($msg) . '&tipo=' . $tipo);
    exit;
}

// ---------------------------------------------------------------- guardar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), fn($n) => $n > 0)));

    if (!$ids) volverOfertas('Marcá al menos un producto.', 'error');
    if (count($ids) > 500) volverOfertas('Son demasiados productos juntos: elegí de a 500 como máximo.', 'error');
    $marcas = implode(',', array_fill(0, count($ids), '?'));

    try {
        if ($accion === 'quitar') {
            $stmt = $pdo->prepare("UPDATE productos SET oferta_tipo = NULL, oferta_valor = NULL WHERE id IN ($marcas)");
            $stmt->execute($ids);
            volverOfertas('Oferta quitada de ' . count($ids) . ' producto(s). Vuelven a su precio normal.', 'ok');
        }

        if ($accion === 'aplicar') {
            $tipo = $_POST['tipo'] ?? '';
            $texto = str_replace(',', '.', trim((string) ($_POST['valor'] ?? '')));
            if (!in_array($tipo, ['porcentaje', 'monto'], true)) volverOfertas('Elegí si el descuento es en % o en $.', 'error');
            if (!is_numeric($texto)) volverOfertas('Escribí el descuento como número.', 'error');
            $valor = round((float) $texto, 2);

            if ($tipo === 'porcentaje' && ($valor < 1 || $valor > 90)) {
                volverOfertas('El porcentaje tiene que estar entre 1 y 90.', 'error');
            }
            if ($tipo === 'monto' && $valor <= 0) {
                volverOfertas('El monto del descuento tiene que ser mayor a cero.', 'error');
            }

            // En "monto", solo se aplica a los productos cuyo precio es mayor al descuento
            // (si no, quedarían gratis o en negativo).
            $extra = $tipo === 'monto' ? ' AND precio > ?' : '';
            $params = array_merge($ids, $tipo === 'monto' ? [$valor] : []);
            $stmt = $pdo->prepare("SELECT id FROM productos WHERE id IN ($marcas)$extra");
            $stmt->execute($params);
            $validos = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if ($validos) {
                $marcasV = implode(',', array_fill(0, count($validos), '?'));
                $pdo->prepare("UPDATE productos SET oferta_tipo = ?, oferta_valor = ? WHERE id IN ($marcasV)")
                    ->execute(array_merge([$tipo, $valor], $validos));
            }
            $aplicados = count($validos);
            $omitidos = count($ids) - $aplicados;

            $cuanto = $tipo === 'porcentaje' ? rtrim(rtrim(number_format($valor, 2, ',', ''), '0'), ',') . '%' : '$' . number_format($valor, 0, ',', '.');
            $msg = "Oferta de $cuanto aplicada a $aplicados producto(s).";
            if ($omitidos > 0) $msg .= " $omitidos no se tocaron porque su precio es menor o igual al descuento.";
            volverOfertas($msg, 'ok');
        }

        volverOfertas('Acción inválida.', 'error');
    } catch (PDOException $e) {
        volverOfertas('No se pudo guardar. ¿Ejecutaste migracion_ofertas.sql en phpMyAdmin?', 'error');
    }
}

// ---------------------------------------------------------------- mostrar
$mensaje = $_GET['msg'] ?? null;
$tipoMensaje = ($_GET['tipo'] ?? '') === 'ok' ? 'ok' : 'error';

$faltaMigracion = false;
$productos = [];
try {
    $productos = $pdo->query(
        'SELECT p.id, p.nombre, p.tipo_venta, p.precio, p.oferta_tipo, p.oferta_valor,
                c.nombre AS categoria
         FROM productos p JOIN categorias c ON c.id = p.categoria_id
         WHERE p.activo = 1
         ORDER BY c.orden ASC, p.nombre ASC'
    )->fetchAll();
} catch (PDOException $e) {
    $faltaMigracion = true;
}

$cantEnOferta = 0;
foreach ($productos as $p) if (estaEnOferta($p)) $cantEnOferta++;
$pesos = fn($n) => '$' . number_format((float) $n, 0, ',', '.');
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
<title>Confetti — Ofertas</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FBF2E4; color:#3F2B1F; margin:0; padding:28px 24px 80px; }
  .nav-admin{ font-size:0.85rem; color:#7A614E; margin-bottom:18px; }
  .nav-admin a{ color:#7A614E; text-decoration:none; }
  .nav-admin a:hover{ color:#3F2B1F; text-decoration:underline; }
  h1{ font-size:1.4rem; margin:0 0 4px; }
  p.sub{ color:#7A614E; margin:0 0 18px; max-width:700px; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:0.95rem; max-width:900px; }
  .aviso.ok{ background:#EDF3E4; color:#5F8A4B; border:1px solid #D3E3C2; }
  .aviso.error{ background:#FBEAE3; color:#C8402F; border:1px solid #F0CBBF; }

  .barra{ position:sticky; top:0; z-index:5; background:#fff; border:1px solid rgba(63,43,31,0.12); border-radius:14px;
          padding:14px 16px; margin-bottom:14px; box-shadow:0 6px 18px rgba(63,43,31,0.08); }
  .barra .fila{ display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
  .barra .titulo{ font-weight:700; }
  .barra select, .barra input[type=text]{ font:inherit; padding:9px 10px; border-radius:9px; border:1px solid rgba(63,43,31,0.25); background:#fff; }
  .barra input.valor{ width:110px; }
  .contador{ font-size:0.85rem; color:#7A614E; }
  button{ font-family:inherit; font-weight:700; font-size:0.92rem; border:none; border-radius:10px; padding:10px 18px; cursor:pointer; }
  .btn-aplicar{ background:#A8601F; color:#fff; }
  .btn-aplicar:disabled, .btn-quitar:disabled{ opacity:.4; cursor:not-allowed; }
  .btn-quitar{ background:#fff; color:#C8402F; border:1px solid #C8402F; }
  .filtros{ display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:12px; }
  .filtros input[type=text], .filtros select{ font:inherit; padding:9px 10px; border-radius:9px; border:1px solid rgba(63,43,31,0.25); background:#fff; }
  .filtros label{ font-size:0.88rem; display:flex; gap:6px; align-items:center; }

  table{ width:100%; max-width:1000px; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; border:1px solid rgba(63,43,31,0.1); }
  th, td{ padding:10px 12px; text-align:left; font-size:0.92rem; border-bottom:1px solid rgba(63,43,31,0.07); }
  th{ background:#F3E8D6; font-size:0.78rem; text-transform:uppercase; letter-spacing:.04em; color:#7A614E; }
  tr.seleccionada{ background:#FFF6E8; }
  td.num, th.num{ text-align:right; white-space:nowrap; }
  .tachado{ text-decoration:line-through; color:#A08A78; }
  .tag{ display:inline-block; background:#FFF1DC; color:#B35F00; font-weight:800; font-size:0.78rem; padding:3px 9px; border-radius:999px; }
  .tag.mal{ background:#FBEAE3; color:#C8402F; }
  .quedaria{ color:#5F8A4B; font-weight:700; }
  .ahorro{ color:#5F8A4B; font-weight:600; }
  .vacio{ padding:24px; color:#7A614E; }
  input[type=checkbox]{ width:18px; height:18px; cursor:pointer; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · Ofertas <?= barraSesionHtml() ?></div>

<h1>Ofertas</h1>
<p class="sub">Marcá los productos, elegí el descuento (en <strong>%</strong> o en <strong>$</strong>) y tocá "Aplicar oferta".
   En la tienda van a aparecer en la pestaña <strong>Ofertas</strong> y también en su categoría de siempre, con el precio anterior tachado y cuánto se ahorra.</p>

<?php if ($mensaje): ?><div class="aviso <?= $tipoMensaje ?>"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>

<?php if ($faltaMigracion): ?>
  <div class="aviso error">Falta una actualización de la base de datos: ejecutá <strong>migracion_ofertas.sql</strong> en phpMyAdmin y recargá esta página.</div>
<?php elseif (!$productos): ?>
  <div class="aviso error">Todavía no hay productos cargados.</div>
<?php else: ?>

<form method="post" id="form-ofertas">
  <div class="barra">
    <div class="fila">
      <span class="titulo">Descuento:</span>
      <select name="tipo" id="tipo">
        <option value="porcentaje">% de descuento</option>
        <option value="monto">$ de descuento</option>
      </select>
      <input type="text" name="valor" id="valor" class="valor" inputmode="decimal" placeholder="Ej: 20" autocomplete="off">
      <button type="submit" name="accion" value="aplicar" class="btn-aplicar" id="btn-aplicar" disabled>Aplicar oferta</button>
      <button type="submit" name="accion" value="quitar" class="btn-quitar" id="btn-quitar" disabled
              onclick="return confirm('¿Quitar la oferta de los productos marcados?');">Quitar oferta</button>
      <span class="contador" id="contador">0 marcados</span>
    </div>
  </div>

  <div class="filtros">
    <input type="text" id="buscar" placeholder="Buscar producto…" autocomplete="off">
    <select id="filtro-cat">
      <option value="">Todas las categorías</option>
      <?php foreach (array_unique(array_column($productos, 'categoria')) as $cat): ?>
        <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
      <?php endforeach; ?>
    </select>
    <label><input type="checkbox" id="solo-ofertas"> Ver solo los que están en oferta (<?= $cantEnOferta ?>)</label>
  </div>

  <table>
    <thead>
      <tr>
        <th><input type="checkbox" id="marcar-todos" title="Marcar todos los que se ven" aria-label="Marcar todos"></th>
        <th>Producto</th>
        <th>Categoría</th>
        <th class="num">Precio normal</th>
        <th>Oferta actual</th>
        <th class="num">Precio con oferta</th>
        <th class="num">Quedaría</th>
      </tr>
    </thead>
    <tbody id="filas">
    <?php foreach ($productos as $p):
        $sufijo = $p['tipo_venta'] === 'peso' ? '/kg' : '';
        $activa = estaEnOferta($p);
        $final = precioConOferta($p);
        $invalida = !$activa && $p['oferta_tipo'] !== null;
        $etq = $p['oferta_tipo'] === 'porcentaje'
            ? rtrim(rtrim(number_format((float) $p['oferta_valor'], 2, ',', ''), '0'), ',') . '% OFF'
            : ($p['oferta_tipo'] === 'monto' ? $pesos($p['oferta_valor']) . ' menos' : '');
    ?>
      <tr data-nombre="<?= htmlspecialchars(mb_strtolower($p['nombre'])) ?>" data-cat="<?= htmlspecialchars($p['categoria']) ?>"
          data-precio="<?= (float) $p['precio'] ?>" data-oferta="<?= $activa ? 1 : 0 ?>" data-sufijo="<?= $sufijo ?>">
        <td><input type="checkbox" name="ids[]" value="<?= (int) $p['id'] ?>" class="marca" aria-label="Elegir <?= htmlspecialchars($p['nombre']) ?>"></td>
        <td><?= htmlspecialchars($p['nombre']) ?></td>
        <td><?= htmlspecialchars($p['categoria']) ?></td>
        <td class="num <?= $activa ? 'tachado' : '' ?>"><?= $pesos($p['precio']) . $sufijo ?></td>
        <td>
          <?php if ($activa): ?><span class="tag"><?= htmlspecialchars($etq) ?></span>
          <?php elseif ($invalida): ?><span class="tag mal" title="El descuento ya no entra en el precio actual: no se aplica">Descuento inválido</span>
          <?php else: ?><span style="color:#A08A78;">—</span><?php endif; ?>
        </td>
        <td class="num"><?php if ($activa): ?><strong><?= $pesos($final) . $sufijo ?></strong>
            <div class="ahorro">ahorra <?= $pesos($p['precio'] - $final) ?></div><?php else: ?>—<?php endif; ?></td>
        <td class="num quedaria"></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <div class="vacio" id="sin-resultados" style="display:none;">No hay productos con ese filtro.</div>
</form>

<script>
  const $ = (s) => document.querySelector(s);
  const filas = [...document.querySelectorAll('#filas tr')];
  const tipo = $('#tipo'), valor = $('#valor');
  const pesos = (n) => '$' + Math.round(n).toLocaleString('es-AR');

  function precioNuevo(precio) {
    const v = parseFloat(String(valor.value).replace(',', '.'));
    if (!isFinite(v)) return null;
    if (tipo.value === 'porcentaje') return (v >= 1 && v <= 90) ? Math.max(1, Math.round(precio * (1 - v / 100))) : null;
    return (v > 0 && v < precio) ? Math.round(precio - v) : null;
  }

  function actualizar() {
    const marcadas = filas.filter(f => f.querySelector('.marca').checked);
    const v = parseFloat(String(valor.value).replace(',', '.'));
    const valido = isFinite(v) && (tipo.value === 'porcentaje' ? (v >= 1 && v <= 90) : v > 0);
    $('#contador').textContent = marcadas.length + (marcadas.length === 1 ? ' marcado' : ' marcados');
    $('#btn-aplicar').disabled = !(marcadas.length && valido);
    $('#btn-quitar').disabled = !marcadas.length;
    valor.placeholder = tipo.value === 'porcentaje' ? 'Ej: 20 (entre 1 y 90)' : 'Ej: 300 (pesos)';
    filas.forEach(f => {
      const marcada = f.querySelector('.marca').checked;
      f.classList.toggle('seleccionada', marcada);
      const celda = f.querySelector('.quedaria');
      const precio = parseFloat(f.dataset.precio);
      const nuevo = marcada ? precioNuevo(precio) : null;
      celda.textContent = marcada ? (nuevo === null ? (valido ? 'No aplica' : '') : pesos(nuevo) + f.dataset.sufijo) : '';
    });
  }

  function filtrar() {
    const q = $('#buscar').value.trim().toLowerCase();
    const cat = $('#filtro-cat').value, soloOf = $('#solo-ofertas').checked;
    let visibles = 0;
    filas.forEach(f => {
      const ok = (!q || f.dataset.nombre.includes(q)) && (!cat || f.dataset.cat === cat) && (!soloOf || f.dataset.oferta === '1');
      f.style.display = ok ? '' : 'none';
      if (ok) visibles++;
    });
    $('#sin-resultados').style.display = visibles ? 'none' : 'block';
  }

  $('#marcar-todos').addEventListener('change', (e) => {
    filas.forEach(f => { if (f.style.display !== 'none') f.querySelector('.marca').checked = e.target.checked; });
    actualizar();
  });
  document.querySelectorAll('.marca').forEach(c => c.addEventListener('change', actualizar));
  [tipo, valor].forEach(el => el.addEventListener('input', actualizar));
  ['#buscar', '#filtro-cat', '#solo-ofertas'].forEach(s => $(s).addEventListener('input', filtrar));
  // Enter en el campo del descuento aplica la oferta (si se puede)
  valor.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); if (!$('#btn-aplicar').disabled) $('#btn-aplicar').click(); } });
  actualizar();
</script>
<?php endif; ?>

</body>
</html>
