<?php
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion([ROL_DUENO]);

$productos = $pdo->query(
    "SELECT p.*, c.nombre AS categoria_nombre, prov.nombre AS proveedor_nombre,
            (SELECT m.cantidad FROM movimientos_stock m
              WHERE m.producto_id = p.id AND m.tipo = 'entrada_stock'
              ORDER BY m.id DESC LIMIT 1) AS ultimo_ingreso,
            (SELECT UNIX_TIMESTAMP() - UNIX_TIMESTAMP(m.creado_en) FROM movimientos_stock m
              WHERE m.producto_id = p.id AND m.tipo = 'entrada_stock'
              ORDER BY m.id DESC LIMIT 1) AS seg_ultimo_ingreso
     FROM productos p
     JOIN categorias c ON c.id = p.categoria_id
     LEFT JOIN proveedores prov ON prov.id = p.proveedor_id
     ORDER BY c.orden ASC, p.nombre ASC"
)->fetchAll();

$mensaje = $_GET['msg'] ?? null;
$tipoMensaje = $_GET['tipo'] ?? 'error';

// segundos -> "hace 5 min" / "hace 3 h" / "hace 2 d" (medido por MySQL, sin zonas horarias)
function hace($segundos) {
    $min = intdiv(max(0, (int) $segundos), 60);
    if ($min < 1) return 'hace instantes';
    $h = intdiv($min, 60);
    if ($h < 1) return "hace $min min";
    if ($h < 24) return "hace $h h";
    return 'hace ' . intdiv($h, 24) . ' d';
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
<meta name="theme-color" content="#F4E4CC">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Productos</title>
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
  .btn-primario{ background:#A8601F; color:#fff; }
  .btn-primario:hover{ background:#8A4F1C; }
  table{ width:100%; max-width:1160px; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; }
  th, td{ text-align:left; padding:12px 16px; border-bottom:1px solid rgba(63,43,31,0.08); vertical-align:middle; font-size:0.88rem; }
  th{ font-size:0.78rem; color:#7A614E; font-weight:600; }
  tr.inactivo{ opacity:.5; }
  .miniatura{ width:44px; height:44px; border-radius:9px; object-fit:cover; background:#F4E4CC; display:block; }
  .miniatura-vacia{ width:44px; height:44px; border-radius:9px; background:#F4E4CC; display:flex; align-items:center; justify-content:center; font-size:1.1rem; }
  .categoria-chip{ font-size:0.78rem; color:#7A614E; }
  .stock-bajo{ color:#C8402F; font-weight:600; }
  .acciones a, .acciones button{ font-size:0.82rem; margin-right:10px; border:none; background:none; cursor:pointer; padding:0; color:#6B4026; text-decoration:none; }
  .acciones button.eliminar{ color:#C8402F; }

  /* Ingreso de mercadería */
  .ingreso{ display:flex; gap:6px; align-items:center; margin:0; }
  .ingreso input{
    width:84px; padding:6px 8px; border-radius:8px; border:1px solid rgba(63,43,31,0.15);
    font-family:inherit; font-size:0.85rem;
  }
  .ingreso button{
    background:#5F8A4B; color:#fff; border:none; border-radius:8px; padding:7px 11px;
    font-weight:600; font-size:0.8rem; cursor:pointer; font-family:inherit;
  }
  .ingreso button:hover{ background:#4A6E3A; }
  .ultimo-ingreso{ font-size:0.72rem; color:#7A614E; margin-top:4px; }
  tr:target{ background:#F4F8EE; }

  /* Buscador */
  .buscador-productos{ display:flex; align-items:center; gap:12px; margin-bottom:14px; flex-wrap:wrap; }
  .buscador-productos input{
    width:100%; max-width:440px; padding:11px 14px; border-radius:10px;
    border:1px solid rgba(63,43,31,0.15); font-family:inherit; font-size:0.92rem; box-sizing:border-box;
  }
  .buscador-productos input:focus{ outline:2px solid #5F8A4B; outline-offset:1px; }
  .contador-busqueda{ font-size:0.82rem; color:#7A614E; }
  #sin-resultados td{ color:#7A614E; text-align:center; padding:26px; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · Productos · <a href="categorias.php">Categorías</a> · <a href="combos.php">Combos</a> · <a href="proveedores.php">Proveedores</a> · <a href="ventas.php">Ventas</a> · <a href="horarios.php">Horario</a> · <a href="cuentas.php">Cuentas</a> <?= barraSesionHtml() ?></div>

<div class="cabecera">
  <div>
    <h1>Productos</h1>
    <p class="sub">Catálogo completo — crear, editar, sacar de circulación o borrar.</p>
  </div>
  <div style="display:flex; gap:10px;">
    <a class="btn" style="background:#8A6240; color:#fff;" href="combos.php">🎉 Armar combo</a>
    <a class="btn btn-primario" href="producto_form.php">+ Nuevo producto</a>
  </div>
</div>

<?php if ($mensaje): ?>
  <div class="aviso <?= $tipoMensaje === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<div class="buscador-productos">
  <input type="search" id="buscar" autocomplete="off"
         placeholder="Buscar por nombre, categoría, proveedor o código de barras…">
  <span class="contador-busqueda" id="contador-busqueda"></span>
</div>

<table>
  <thead>
    <tr>
      <th>Foto</th>
      <th>Producto</th>
      <th>Código</th>
      <th>Precio de compra</th>
      <th>Precio</th>
      <th>Stock</th>
      <th>Ingresó mercadería</th>
      <th>Activo</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody id="filas-productos">
    <?php foreach ($productos as $p):
      $stockBajo = (float) $p['stock_actual'] <= (float) $p['stock_minimo'];
      $precio = $p['tipo_venta'] === 'peso' ? '$' . number_format($p['precio'], 0, ',', '.') . '/kg' : '$' . number_format($p['precio'], 0, ',', '.');
      $fmt = fn($v) => '$' . number_format((float) $v, fmod((float) $v, 1) == 0 ? 0 : 2, ',', '.');
      $precioCompra = $p['precio_compra'] === null
          ? '<span style="color:#aab;" title="Todavía no cargado">—</span>'
          : $fmt($p['precio_compra']) . ($p['tipo_venta'] === 'peso' ? '/kg' : '');
      $stockTexto = $p['tipo_venta'] === 'peso' ? number_format($p['stock_actual'], 3) . 'kg' : (int) $p['stock_actual'];
    ?>
      <tr id="prod-<?= $p['id'] ?>" class="<?= $p['activo'] ? '' : 'inactivo' ?>"
          data-busqueda="<?= htmlspecialchars($p['nombre'] . ' ' . $p['categoria_nombre'] . ' ' . ($p['proveedor_nombre'] ?? '') . ' ' . ($p['codigo_barras'] ?? '')) ?>">
        <td>
          <?php if ($p['imagen']): ?>
            <img class="miniatura" src="../assets/productos/<?= htmlspecialchars($p['imagen']) ?>" alt="">
          <?php else: ?>
            <div class="miniatura-vacia">🍽️</div>
          <?php endif; ?>
        </td>
        <td>
          <div><?= htmlspecialchars($p['nombre']) ?></div>
          <div class="categoria-chip">
            <?= htmlspecialchars($p['categoria_nombre']) ?><?= $p['proveedor_nombre'] ? ' · ' . htmlspecialchars($p['proveedor_nombre']) : '' ?>
          </div>
        </td>
        <td><?= $p['codigo_barras'] ? htmlspecialchars($p['codigo_barras']) : '<span style="color:#aab;">—</span>' ?></td>
        <td><?= $precioCompra ?></td>
        <td><?= $precio ?></td>
        <td class="<?= $stockBajo ? 'stock-bajo' : '' ?>"><?= $stockTexto ?><?= $stockBajo ? ' ⚠' : '' ?></td>
        <td>
          <?php $esPeso = $p['tipo_venta'] === 'peso'; ?>
          <form class="ingreso" method="post" action="ingresar_mercaderia.php">
            <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
            <input type="number" name="cantidad" required
                   min="<?= $esPeso ? '0.001' : '1' ?>" step="<?= $esPeso ? '0.001' : '1' ?>" max="100000"
                   placeholder="<?= $esPeso ? 'kg' : 'cant.' ?>"
                   aria-label="Cantidad que ingresó de <?= htmlspecialchars($p['nombre']) ?>">
            <button type="submit">+ Sumar</button>
          </form>
          <?php if ($p['ultimo_ingreso'] !== null): ?>
            <div class="ultimo-ingreso">
              Último: +<?= $esPeso ? rtrim(rtrim(number_format($p['ultimo_ingreso'], 3, ',', '.'), '0'), ',') . ' kg' : (int) $p['ultimo_ingreso'] ?>
              · <?= hace($p['seg_ultimo_ingreso']) ?>
            </div>
          <?php endif; ?>
        </td>
        <td><?= $p['activo'] ? 'Sí' : 'No' ?></td>
        <td class="acciones">
          <a href="producto_form.php?id=<?= $p['id'] ?>">Editar</a>
          <form method="post" action="eliminar_producto.php" style="display:inline;"
                onsubmit="return confirm('¿Eliminar &quot;<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>&quot;?');">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button type="submit" class="eliminar">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <tr id="sin-resultados" style="display:none;"><td colspan="9">Ningún producto coincide con esa búsqueda.</td></tr>
  </tbody>
</table>

<script>
  // Busca por palabras (todas tienen que aparecer, en cualquier orden) o por código de barras.
  // Ignora mayúsculas y tildes: "jamon" encuentra "Jamón".
  const normalizar = t => t.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
  const buscar = document.getElementById('buscar');
  const contador = document.getElementById('contador-busqueda');
  const sinResultados = document.getElementById('sin-resultados');
  const filas = [...document.querySelectorAll('#filas-productos tr[data-busqueda]')];
  const indice = filas.map(tr => normalizar(tr.dataset.busqueda));

  function palabrasBuscadas() {
    return normalizar(buscar.value).split(/\s+/).filter(Boolean);
  }

  function filtrar() {
    const palabras = palabrasBuscadas();
    let visibles = 0;
    filas.forEach((tr, i) => {
      const coincide = palabras.every(p => indice[i].includes(p));
      tr.style.display = coincide ? '' : 'none';
      if (coincide) visibles++;
    });
    sinResultados.style.display = palabras.length && visibles === 0 ? '' : 'none';
    contador.textContent = palabras.length ? `${visibles} de ${filas.length}` : '';
  }

  buscar.addEventListener('input', filtrar);

  buscar.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      buscar.value = '';
      filtrar();
    }
    // Un lector de código de barras escribe el código y manda Enter. Si quedó un solo
    // producto, el cursor salta directo al casillero de "Ingresó mercadería" de esa fila.
    if (e.key === 'Enter') {
      e.preventDefault();
      const visibles = filas.filter(tr => tr.style.display !== 'none');
      if (palabrasBuscadas().length && visibles.length === 1) {
        const campo = visibles[0].querySelector('.ingreso input[name="cantidad"]');
        if (campo) campo.focus();
      }
    }
  });

  // Listo para escribir o escanear apenas se abre la pantalla (sin mover el scroll)
  buscar.focus({ preventScroll: true });
</script>

</body>
</html>
