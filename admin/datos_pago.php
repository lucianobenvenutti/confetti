<?php
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion([ROL_DUENO]);

$claves = ['transf_titular', 'transf_banco', 'transf_alias', 'transf_cbu', 'transf_nota'];

$mensaje = null;
$tipoMensaje = 'error';

// Lo que hay guardado (la tabla puede no existir si falta correr migracion_pagos.sql)
$tablaOk = true;
$datos = array_fill_keys($claves, '');
try {
    foreach ($pdo->query("SELECT clave, valor FROM configuracion")->fetchAll() as $f) {
        if (array_key_exists($f['clave'], $datos)) $datos[$f['clave']] = $f['valor'];
    }
} catch (PDOException $e) {
    $tablaOk = false;
}

if ($tablaOk && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo = [];
    foreach ($claves as $k) {
        $v = trim((string) ($_POST[$k] ?? ''));
        $v = preg_replace('/[\x00-\x1F\x7F]/', '', $v);
        $nuevo[$k] = $v;
    }
    $nuevo['transf_cbu'] = preg_replace('/\s+/', '', $nuevo['transf_cbu']);

    if ($nuevo['transf_alias'] === '' && $nuevo['transf_cbu'] === '') {
        // Los dos vacíos = no ofrecer transferencia en la tienda (es válido)
        $ok = true;
    } elseif ($nuevo['transf_alias'] !== '' && !preg_match('/^[A-Za-z0-9.\-]{6,20}$/', $nuevo['transf_alias'])) {
        $mensaje = 'El alias no es válido: tiene entre 6 y 20 caracteres, solo letras, números, punto o guion.';
        $ok = false;
    } elseif ($nuevo['transf_cbu'] !== '' && !preg_match('/^\d{22}$/', $nuevo['transf_cbu'])) {
        $mensaje = 'El CBU/CVU tiene que tener 22 números.';
        $ok = false;
    } elseif (preg_match_all('/./su', $nuevo['transf_titular']) > 80 || preg_match_all('/./su', $nuevo['transf_banco']) > 60 || preg_match_all('/./su', $nuevo['transf_nota']) > 200) {
        $mensaje = 'Alguno de los textos es demasiado largo.';
        $ok = false;
    } else {
        $ok = true;
    }

    if ($ok) {
        $stmt = $pdo->prepare('INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
        foreach ($nuevo as $k => $v) $stmt->execute([$k, $v]);
        $datos = $nuevo;
        $mensaje = ($nuevo['transf_alias'] === '' && $nuevo['transf_cbu'] === '')
            ? 'Guardado. Sin alias ni CBU, la tienda NO ofrece pagar por transferencia.'
            : 'Guardado. Los clientes ya ven estos datos al elegir pagar por transferencia.';
        $tipoMensaje = 'ok';
    } else {
        $datos = $nuevo;   // que no pierda lo que escribió
    }
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
<title>Confetti — Datos de pago</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  .nav-admin a:hover{ color:#1F2A44; text-decoration:underline; }
  h1{ font-size:1.4rem; margin:0 0 4px; }
  p.sub{ color:#5B6478; margin:0 0 22px; max-width:560px; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.95rem; max-width:560px; }
  .aviso.ok{ background:#E4F5EE; color:#1E9C7C; border:1px solid #bfe6d7; }
  .aviso.error{ background:#FBEAE8; color:#E8483C; border:1px solid #f3c9c4; }
  form{ max-width:560px; background:#fff; border-radius:14px; padding:24px; border:1px solid rgba(31,42,68,0.1); }
  .campo{ margin-bottom:16px; }
  label{ display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; }
  input[type=text], textarea{ width:100%; box-sizing:border-box; padding:11px 12px; border-radius:10px; border:1px solid rgba(31,42,68,0.2); font-family:inherit; font-size:0.95rem; }
  .ayuda{ font-size:0.78rem; color:#5B6478; margin-top:4px; }
  button{ font-family:inherit; font-weight:600; font-size:0.95rem; border:none; border-radius:10px; padding:12px 22px; cursor:pointer; background:#1B4A9C; color:#fff; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · Datos de pago <?= barraSesionHtml() ?></div>

<h1>Datos de pago</h1>
<p class="sub">Estos son los datos que ve el cliente cuando elige <strong>pagar por transferencia</strong> en el carrito.
   Si dejás el alias y el CBU vacíos, la tienda solo ofrece pagar en el local.</p>

<?php if (!$tablaOk): ?>
  <div class="aviso error">Falta una actualización de la base de datos: ejecutá <strong>migracion_pagos.sql</strong> en phpMyAdmin y recargá esta página.</div>
<?php else: ?>
  <?php if ($mensaje): ?><div class="aviso <?= $tipoMensaje === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>

  <form method="post" autocomplete="off">
    <div class="campo">
      <label for="transf_titular">Titular de la cuenta</label>
      <input type="text" id="transf_titular" name="transf_titular" maxlength="80" value="<?= htmlspecialchars($datos['transf_titular']) ?>" placeholder="Nombre y apellido o razón social">
    </div>
    <div class="campo">
      <label for="transf_banco">Banco o billetera (opcional)</label>
      <input type="text" id="transf_banco" name="transf_banco" maxlength="60" value="<?= htmlspecialchars($datos['transf_banco']) ?>" placeholder="Ej: Banco Santa Fe, Mercado Pago">
    </div>
    <div class="campo">
      <label for="transf_alias">Alias</label>
      <input type="text" id="transf_alias" name="transf_alias" maxlength="20" value="<?= htmlspecialchars($datos['transf_alias']) ?>" placeholder="Ej: confetti.snack">
      <div class="ayuda">Entre 6 y 20 caracteres: letras, números, punto o guion.</div>
    </div>
    <div class="campo">
      <label for="transf_cbu">CBU / CVU (opcional si cargaste el alias)</label>
      <input type="text" id="transf_cbu" name="transf_cbu" maxlength="30" inputmode="numeric" value="<?= htmlspecialchars($datos['transf_cbu']) ?>" placeholder="22 números">
    </div>
    <div class="campo">
      <label for="transf_nota">Aviso para el cliente (opcional)</label>
      <textarea id="transf_nota" name="transf_nota" rows="2" maxlength="200" placeholder="Ej: Transferí el total del pedido y subí el comprobante."><?= htmlspecialchars($datos['transf_nota']) ?></textarea>
    </div>
    <button type="submit">Guardar</button>
  </form>
<?php endif; ?>

</body>
</html>
