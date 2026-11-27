<?php
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion([ROL_DUENO]);

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
<html lang="es-AR" translate="no">
<head>
<meta charset="UTF-8">
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon-32.png">
<link rel="icon" href="../favicon.ico" sizes="48x48">
<link rel="apple-touch-icon" href="../assets/apple-touch-icon.png">
<meta name="theme-color" content="#F4E4CC">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — <?= $id ? 'Editar' : 'Nueva' ?> cuenta corriente</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FBF2E4; color:#3F2B1F; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#7A614E; margin-bottom:18px; }
  .nav-admin a{ color:#7A614E; text-decoration:none; }
  h1{ font-size:1.4rem; margin:0 0 22px; }
  form{ max-width:440px; background:#fff; border-radius:14px; padding:24px; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.9rem; max-width:440px; background:#FBEAE3; color:#C8402F; border:1px solid #F0CBBF; }
  .campo{ margin-bottom:16px; }
  label{ display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; }
  input[type="text"], textarea{
    width:100%; padding:10px 12px; border-radius:9px; border:1px solid rgba(63,43,31,0.15);
    font-family:inherit; font-size:0.95rem; box-sizing:border-box;
  }
  textarea{ resize:vertical; min-height:56px; }
  .botones{ display:flex; gap:10px; margin-top:22px; }
  button, .btn-cancelar{
    font-family:inherit; font-weight:600; font-size:0.9rem; border-radius:9px; padding:11px 18px; cursor:pointer; text-decoration:none;
  }
  button{ border:none; background:#8A6240; color:#fff; }
  button:hover{ background:#6B4B30; }
  .btn-cancelar{ background:none; border:1px solid rgba(63,43,31,0.2); color:#3F2B1F; }
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
