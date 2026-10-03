<?php
require 'conexion.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$proveedor = ['nombre' => '', 'telefono' => '', 'direccion' => '', 'dias_reparto' => '', 'notas' => '', 'activo' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM proveedores WHERE id = ?');
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) {
        header('Location: proveedores.php?msg=' . urlencode('Ese proveedor no existe.') . '&tipo=error');
        exit;
    }
    $proveedor = $encontrado;
}

$mensaje = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — <?= $id ? 'Editar' : 'Nuevo' ?> proveedor</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  h1{ font-size:1.4rem; margin:0 0 22px; }
  form{ max-width:460px; background:#fff; border-radius:14px; padding:24px; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.9rem; max-width:460px; background:#FBEAE8; color:#E8483C; border:1px solid #f3c9c4; }
  .campo{ margin-bottom:16px; }
  label{ display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; }
  input[type="text"], textarea{
    width:100%; padding:10px 12px; border-radius:9px; border:1px solid rgba(31,42,68,0.15); font-family:inherit; font-size:0.95rem;
  }
  textarea{ resize:vertical; min-height:56px; }
  .fila-check{ display:flex; align-items:center; gap:8px; }
  .fila-check label{ margin:0; font-weight:500; }
  .botones{ display:flex; gap:10px; margin-top:22px; }
  button, .btn-cancelar{
    font-family:inherit; font-weight:600; font-size:0.9rem; border-radius:9px; padding:11px 18px; cursor:pointer; text-decoration:none;
  }
  button{ border:none; background:#1B4A9C; color:#fff; }
  button:hover{ background:#153c7e; }
  .btn-cancelar{ background:none; border:1px solid rgba(31,42,68,0.2); color:#1F2A44; }
</style>
</head>
<body>

<div class="nav-admin"><a href="proveedores.php">← Proveedores</a></div>
<h1><?= $id ? 'Editar proveedor' : 'Nuevo proveedor' ?></h1>

<?php if ($mensaje): ?>
  <div class="aviso"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<form method="post" action="guardar_proveedor.php">
  <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

  <div class="campo">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($proveedor['nombre']) ?>" required>
  </div>

  <div class="campo">
    <label for="telefono">Teléfono</label>
    <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($proveedor['telefono'] ?? '') ?>" placeholder="Con código de área">
  </div>

  <div class="campo">
    <label for="direccion">Dirección</label>
    <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($proveedor['direccion'] ?? '') ?>">
  </div>

  <div class="campo">
    <label for="dias_reparto">Días de reparto</label>
    <input type="text" id="dias_reparto" name="dias_reparto" value="<?= htmlspecialchars($proveedor['dias_reparto'] ?? '') ?>" placeholder="Ej: Lunes y Jueves">
  </div>

  <div class="campo">
    <label for="notas">Notas (opcional)</label>
    <textarea id="notas" name="notas" placeholder="Pedido mínimo, forma de pago, contacto, lo que sea útil recordar"><?= htmlspecialchars($proveedor['notas'] ?? '') ?></textarea>
  </div>

  <div class="campo fila-check">
    <input type="checkbox" id="activo" name="activo" <?= $proveedor['activo'] ? 'checked' : '' ?>>
    <label for="activo">Activo</label>
  </div>

  <div class="botones">
    <button type="submit">Guardar</button>
    <a class="btn-cancelar" href="proveedores.php">Cancelar</a>
  </div>
</form>

</body>
</html>
