<?php
require 'conexion.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$producto = [
    'nombre' => '', 'descripcion' => '', 'categoria_id' => '', 'proveedor_id' => '', 'tipo_venta' => 'unidad',
    'precio' => '', 'stock_actual' => 0, 'stock_minimo' => 0,
    'sin_tacc' => 0, 'destacado' => 0, 'activo' => 1, 'imagen' => null, 'codigo_barras' => '',
];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = ?');
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) {
        header('Location: productos.php?msg=' . urlencode('Ese producto no existe.') . '&tipo=error');
        exit;
    }
    $producto = $encontrado;
}

$categorias = $pdo->query('SELECT id, nombre, activo FROM categorias ORDER BY orden ASC, nombre ASC')->fetchAll();
$proveedores = $pdo->query('SELECT id, nombre, activo FROM proveedores ORDER BY nombre ASC')->fetchAll();
$mensaje = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — <?= $id ? 'Editar' : 'Nuevo' ?> producto</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  h1{ font-size:1.4rem; margin:0 0 22px; }
  form{ max-width:480px; background:#fff; border-radius:14px; padding:26px; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.9rem; max-width:480px; background:#FBEAE8; color:#E8483C; border:1px solid #f3c9c4; }
  .campo{ margin-bottom:16px; }
  label{ display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; }
  input[type="text"], input[type="number"], select, textarea{
    width:100%; padding:10px 12px; border-radius:9px; border:1px solid rgba(31,42,68,0.15); font-family:inherit; font-size:0.95rem;
  }
  textarea{ resize:vertical; min-height:60px; }
  .dos-columnas{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  .fila-check{ display:flex; align-items:center; gap:8px; margin-bottom:10px; }
  .fila-check label{ margin:0; font-weight:500; }
  .radios{ display:flex; gap:18px; }
  .radios label{ display:flex; align-items:center; gap:6px; font-weight:500; }
  .foto-actual{ display:flex; align-items:center; gap:12px; margin-bottom:10px; }
  .foto-actual img{ width:52px; height:52px; border-radius:9px; object-fit:cover; }
  .botones{ display:flex; gap:10px; margin-top:22px; }
  button, .btn-cancelar{
    font-family:inherit; font-weight:600; font-size:0.9rem; border-radius:9px; padding:11px 18px; cursor:pointer; text-decoration:none;
  }
  button{ border:none; background:#E8483C; color:#fff; }
  button:hover{ background:#c73a2f; }
  .btn-cancelar{ background:none; border:1px solid rgba(31,42,68,0.2); color:#1F2A44; }
</style>
</head>
<body>

<div class="nav-admin"><a href="productos.php">← Productos</a></div>
<h1><?= $id ? 'Editar producto' : 'Nuevo producto' ?></h1>

<?php if ($mensaje): ?>
  <div class="aviso"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<form method="post" action="guardar_producto.php" enctype="multipart/form-data">
  <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

  <div class="campo">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required>
  </div>

  <div class="campo">
    <label for="descripcion">Descripción (opcional)</label>
    <textarea id="descripcion" name="descripcion"><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>
  </div>

  <div class="campo">
    <label for="categoria_id">Categoría</label>
    <select id="categoria_id" name="categoria_id" required>
      <option value="">Elegí una categoría…</option>
      <?php foreach ($categorias as $c): ?>
        <option value="<?= $c['id'] ?>" <?= (string) $producto['categoria_id'] === (string) $c['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['nombre']) ?><?= $c['activo'] ? '' : ' (inactiva)' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="campo">
    <label for="proveedor_id">Proveedor (opcional, para saber a quién pedirle si falta)</label>
    <select id="proveedor_id" name="proveedor_id">
      <option value="">Sin proveedor asignado</option>
      <?php foreach ($proveedores as $prov): ?>
        <option value="<?= $prov['id'] ?>" <?= (string) $producto['proveedor_id'] === (string) $prov['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($prov['nombre']) ?><?= $prov['activo'] ? '' : ' (inactivo)' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <a href="proveedor_form.php" style="font-size:0.78rem; color:#5B6478;" target="_blank">¿No está? Agregalo acá (se abre aparte)</a>
  </div>

  <div class="campo">
    <label>Se vende por</label>
    <div class="radios">
      <label><input type="radio" name="tipo_venta" value="unidad" <?= $producto['tipo_venta'] === 'unidad' ? 'checked' : '' ?>> Unidad</label>
      <label><input type="radio" name="tipo_venta" value="peso" <?= $producto['tipo_venta'] === 'peso' ? 'checked' : '' ?>> Peso (kg)</label>
    </div>
  </div>

  <div class="dos-columnas">
    <div class="campo">
      <label for="precio">Precio <span id="sufijo-precio"></span></label>
      <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?= htmlspecialchars($producto['precio']) ?>" required>
    </div>
    <div class="campo">
      <label for="stock_actual">Stock actual</label>
      <input type="number" id="stock_actual" name="stock_actual" step="0.001" min="0" value="<?= htmlspecialchars($producto['stock_actual']) ?>" required>
    </div>
  </div>

  <div class="campo">
    <label for="stock_minimo">Alertar cuando el stock baje de</label>
    <input type="number" id="stock_minimo" name="stock_minimo" step="0.001" min="0" value="<?= htmlspecialchars($producto['stock_minimo']) ?>">
  </div>

  <div class="fila-check">
    <input type="checkbox" id="sin_tacc" name="sin_tacc" <?= $producto['sin_tacc'] ? 'checked' : '' ?>>
    <label for="sin_tacc">Sin TACC</label>
  </div>
  <div class="fila-check">
    <input type="checkbox" id="destacado" name="destacado" <?= $producto['destacado'] ? 'checked' : '' ?>>
    <label for="destacado">Destacado (aparece en "Lo más pedido")</label>
  </div>
  <div class="fila-check">
    <input type="checkbox" id="activo" name="activo" <?= $producto['activo'] ? 'checked' : '' ?>>
    <label for="activo">Activo (visible en la página)</label>
  </div>

  <div class="campo">
    <label for="codigo_barras">Código de barras (opcional)</label>
    <div style="display:flex; gap:8px;">
      <input type="text" id="codigo_barras" name="codigo_barras" value="<?= htmlspecialchars($producto['codigo_barras'] ?? '') ?>" placeholder="Escaneá el de fábrica o dejá que se invente uno">
      <button type="button" id="btn-generar-codigo" class="btn-cancelar" style="white-space:nowrap;">Generar</button>
    </div>
  </div>

  <div class="campo" style="margin-top:18px;">
    <label for="imagen">Foto <?= $id ? '(dejar vacío para no cambiarla)' : '(opcional)' ?></label>
    <?php if (!empty($producto['imagen'])): ?>
      <div class="foto-actual">
        <img src="../assets/productos/<?= htmlspecialchars($producto['imagen']) ?>" alt="">
        <span style="font-size:0.85rem; color:#5B6478;">Foto actual</span>
      </div>
    <?php endif; ?>
    <input type="file" id="imagen" name="imagen" accept="image/*">
  </div>

  <div class="botones">
    <button type="submit">Guardar</button>
    <a class="btn-cancelar" href="productos.php">Cancelar</a>
  </div>
</form>

<script>
  // Pequeño detalle: aclarar "/kg" al lado del precio cuando el tipo de venta es por peso
  const radios = document.querySelectorAll('input[name="tipo_venta"]');
  const sufijo = document.getElementById('sufijo-precio');
  function actualizarSufijo() {
    const elegido = document.querySelector('input[name="tipo_venta"]:checked');
    sufijo.textContent = elegido && elegido.value === 'peso' ? '(por kg)' : '';
  }
  radios.forEach(r => r.addEventListener('change', actualizarSufijo));
  actualizarSufijo();

  // Genera un código interno simple (no es un EAN real, pero sirve para que
  // el local lo imprima y lo pegue en productos que no traen código de fábrica,
  // como algo casero de panadería).
  document.getElementById('btn-generar-codigo').addEventListener('click', () => {
    let codigo = '2'; // por convención, los códigos de uso interno suelen arrancar con 2
    for (let i = 0; i < 11; i++) codigo += Math.floor(Math.random() * 10);
    document.getElementById('codigo_barras').value = codigo;
  });
</script>

</body>
</html>
