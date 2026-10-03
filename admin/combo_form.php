<?php
require 'conexion.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$combo = ['nombre' => '', 'descripcion' => '', 'precio' => '', 'activo' => 1, 'imagen' => null];
$cantidadesActuales = []; // producto_id => cantidad, para marcar los ya elegidos

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM combos WHERE id = ?');
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) {
        header('Location: combos.php?msg=' . urlencode('Ese combo no existe.') . '&tipo=error');
        exit;
    }
    $combo = $encontrado;

    $stmt = $pdo->prepare('SELECT producto_id, cantidad FROM combo_productos WHERE combo_id = ?');
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $fila) {
        $cantidadesActuales[$fila['producto_id']] = $fila['cantidad'];
    }
}

$productos = $pdo->query(
    "SELECT p.id, p.nombre, p.tipo_venta, cat.nombre AS categoria_nombre
     FROM productos p
     JOIN categorias cat ON cat.id = p.categoria_id
     ORDER BY cat.orden ASC, p.nombre ASC"
)->fetchAll();

// Los agrupo por categoría para que el formulario no sea una lista gigante sin orden
$porCategoria = [];
foreach ($productos as $p) {
    $porCategoria[$p['categoria_nombre']][] = $p;
}

$mensaje = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — <?= $id ? 'Editar' : 'Armar' ?> combo</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  h1{ font-size:1.4rem; margin:0 0 22px; }
  .layout{ display:grid; grid-template-columns:1fr 1fr; gap:20px; max-width:920px; align-items:start; }
  form{ background:#fff; border-radius:14px; padding:24px; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.9rem; max-width:920px; background:#FBEAE8; color:#E8483C; border:1px solid #f3c9c4; }
  .campo{ margin-bottom:16px; }
  label{ display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; }
  input[type="text"], input[type="number"], textarea{
    width:100%; padding:10px 12px; border-radius:9px; border:1px solid rgba(31,42,68,0.15); font-family:inherit; font-size:0.95rem;
  }
  textarea{ resize:vertical; min-height:60px; }
  .fila-check{ display:flex; align-items:center; gap:8px; margin-bottom:10px; }
  .fila-check label{ margin:0; font-weight:500; }
  .foto-actual{ display:flex; align-items:center; gap:12px; margin-bottom:10px; }
  .foto-actual img{ width:52px; height:52px; border-radius:9px; object-fit:cover; }
  .botones{ display:flex; gap:10px; margin-top:22px; }
  button, .btn-cancelar{
    font-family:inherit; font-weight:600; font-size:0.9rem; border-radius:9px; padding:11px 18px; cursor:pointer; text-decoration:none;
  }
  button{ border:none; background:#6E4E9E; color:#fff; }
  button:hover{ background:#5a3f82; }
  .btn-cancelar{ background:none; border:1px solid rgba(31,42,68,0.2); color:#1F2A44; }

  .panel-productos{ background:#fff; border-radius:14px; padding:20px 24px; max-height:640px; overflow-y:auto; }
  .panel-productos h2{ font-size:1rem; margin:0 0 4px; }
  .panel-productos p.ayuda{ color:#5B6478; font-size:0.82rem; margin:0 0 16px; }
  .grupo-categoria{ margin-bottom:16px; }
  .grupo-categoria h3{ font-size:0.82rem; color:#5B6478; margin:0 0 8px; text-transform:uppercase; letter-spacing:0.02em; }
  .fila-producto{
    display:flex; align-items:center; gap:10px; padding:6px 0;
  }
  .fila-producto label{ flex:1; margin:0; font-weight:500; font-size:0.9rem; }
  .fila-producto input[type="number"]{ width:90px; padding:6px 8px; font-size:0.85rem; }
  .fila-producto .unidad{ font-size:0.78rem; color:#5B6478; width:26px; }
</style>
</head>
<body>

<div class="nav-admin"><a href="combos.php">← Combos y picadas</a></div>
<h1><?= $id ? 'Editar combo' : 'Armar combo' ?></h1>

<?php if ($mensaje): ?>
  <div class="aviso"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<form method="post" action="guardar_combo.php" enctype="multipart/form-data">
<div class="layout">

  <div>
    <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

    <div class="campo">
      <label for="nombre">Nombre del combo</label>
      <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($combo['nombre']) ?>" placeholder="Ej: Picada Confetti para 2" required>
    </div>

    <div class="campo">
      <label for="descripcion">Descripción (opcional)</label>
      <textarea id="descripcion" name="descripcion"><?= htmlspecialchars($combo['descripcion'] ?? '') ?></textarea>
    </div>

    <div class="campo">
      <label for="precio">Precio del combo (precio especial, no la suma de los productos)</label>
      <input type="number" id="precio" name="precio" step="0.01" min="0" value="<?= htmlspecialchars($combo['precio']) ?>" required>
    </div>

    <div class="fila-check">
      <input type="checkbox" id="activo" name="activo" <?= $combo['activo'] ? 'checked' : '' ?>>
      <label for="activo">Activo (visible en la página)</label>
    </div>

    <div class="campo" style="margin-top:18px;">
      <label for="imagen">Foto <?= $id ? '(dejar vacío para no cambiarla)' : '(opcional)' ?></label>
      <?php if (!empty($combo['imagen'])): ?>
        <div class="foto-actual">
          <img src="../assets/combos/<?= htmlspecialchars($combo['imagen']) ?>" alt="">
          <span style="font-size:0.85rem; color:#5B6478;">Foto actual</span>
        </div>
      <?php endif; ?>
      <input type="file" id="imagen" name="imagen" accept="image/*">
    </div>

    <div class="botones">
      <button type="submit">Guardar combo</button>
      <a class="btn-cancelar" href="combos.php">Cancelar</a>
    </div>
  </div>

  <div class="panel-productos">
    <h2>¿Qué productos lo componen?</h2>
    <p class="ayuda">Tildá los productos y cargá la cantidad de cada uno (unidades, o kg si se vende por peso).</p>

    <?php foreach ($porCategoria as $nombreCategoria => $lista): ?>
      <div class="grupo-categoria">
        <h3><?= htmlspecialchars($nombreCategoria) ?></h3>
        <?php foreach ($lista as $p):
          $marcado = isset($cantidadesActuales[$p['id']]);
          $valorCantidad = $marcado ? $cantidadesActuales[$p['id']] : ($p['tipo_venta'] === 'peso' ? '0.200' : '1');
        ?>
          <div class="fila-producto">
            <input type="checkbox" name="producto_check[<?= $p['id'] ?>]" id="chk_<?= $p['id'] ?>" <?= $marcado ? 'checked' : '' ?>>
            <label for="chk_<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></label>
            <input type="number" name="producto_cantidad[<?= $p['id'] ?>]"
                   step="<?= $p['tipo_venta'] === 'peso' ? '0.001' : '1' ?>" min="0"
                   value="<?= htmlspecialchars($valorCantidad) ?>">
            <span class="unidad"><?= $p['tipo_venta'] === 'peso' ? 'kg' : 'u.' ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>

</div>
</form>

</body>
</html>
