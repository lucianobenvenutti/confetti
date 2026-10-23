<?php
require 'conexion.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$cuenta = ['nombre' => '', 'telefono' => '', 'notas' => ''];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM cuentas_corrientes WHERE id = ?');
    $stmt->execute([$id]);
    $encontrada = $stmt->fetch();
    if (!$encontrada) {
        header('Location: cuentas.php?msg=' . urlencode('Esa cuenta no existe.') . '&tipo=error');
        exit;
    }
    $cuenta = $encontrada;
}

$mensaje = $_GET['msg'] ?? null;
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — <?= $id ? 'Editar' : 'Nueva' ?> cuenta corriente</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  h1{ font-size:1.4rem; margin:0 0 22px; }
  form{ max-width:440px; background:#fff; border-radius:14px; padding:24px; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.9rem; max-width:440px; background:#FBEAE8; color:#E8483C; border:1px solid #f3c9c4; }
  .campo{ margin-bottom:16px; }
  label{ display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; }
  input[type="text"], textarea{
    width:100%; padding:10px 12px; border-radius:9px; border:1px solid rgba(31,42,68,0.15);
    font-family:inherit; font-size:0.95rem; box-sizing:border-box;
  }
  textarea{ resize:vertical; min-height:56px; }
  .botones{ display:flex; gap:10px; margin-top:22px; }
  button, .btn-cancelar{
    font-family:inherit; font-weight:600; font-size:0.9rem; border-radius:9px; padding:11px 18px; cursor:pointer; text-decoration:none;
  }
  button{ border:none; background:#6E4E9E; color:#fff; }
  button:hover{ background:#5a3f82; }
  .btn-cancelar{ background:none; border:1px solid rgba(31,42,68,0.2); color:#1F2A44; }
</style>
</head>
<body>

<div class="nav-admin"><a href="<?= $id ? 'cuenta_detalle.php?id=' . $id : 'cuentas.php' ?>">← <?= $id ? 'Volver a la cuenta' : 'Cuentas corrientes' ?></a></div>
<h1><?= $id ? 'Editar cuenta' : 'Nueva cuenta corriente' ?></h1>

<?php if ($mensaje): ?>
  <div class="aviso"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<form method="post" action="guardar_cuenta.php">
  <?php if ($id): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

  <div class="campo">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" maxlength="120" value="<?= htmlspecialchars($cuenta['nombre']) ?>" required>
  </div>

  <div class="campo">
    <label for="telefono">Teléfono (opcional)</label>
    <input type="text" id="telefono" name="telefono" maxlength="30" value="<?= htmlspecialchars($cuenta['telefono'] ?? '') ?>">
  </div>

  <div class="campo">
    <label for="notas">Notas (opcional)</label>
    <textarea id="notas" name="notas" maxlength="255" placeholder="Ej: paga los viernes"><?= htmlspecialchars($cuenta['notas'] ?? '') ?></textarea>
  </div>

  <div class="botones">
    <button type="submit">Guardar</button>
    <a class="btn-cancelar" href="<?= $id ? 'cuenta_detalle.php?id=' . $id : 'cuentas.php' ?>">Cancelar</a>
  </div>
</form>

</body>
</html>
