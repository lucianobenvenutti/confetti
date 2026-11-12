<?php
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion(ROLES_TODOS);

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual = (string) ($_POST['actual'] ?? '');
    $nueva = (string) ($_POST['nueva'] ?? '');
    $nueva2 = (string) ($_POST['nueva2'] ?? '');
    $ip = ipCliente();
    $clave = 'clave#' . $usuarioActual['id'];   // por si alguien con una sesión ajena intenta adivinar la clave actual

    try {
        if (ingresoBloqueado($pdo, $clave, $ip)) {
            $error = 'Demasiados intentos. Esperá unos 15 minutos y probá de nuevo.';
        } else {
            $stmt = $pdo->prepare('SELECT password_hash FROM usuarios WHERE id = ?');
            $stmt->execute([$usuarioActual['id']]);
            $hashActual = (string) $stmt->fetchColumn();

            if (!password_verify($actual, $hashActual)) {
                registrarFallo($pdo, $clave, $ip);
                $error = 'La contraseña actual no es correcta.';
            } elseif ($nueva !== $nueva2) {
                $error = 'Las dos contraseñas nuevas no coinciden.';
            } elseif (($problema = validarClaveNueva($nueva, $usuarioActual['usuario'])) !== null) {
                $error = $problema;
            } else {
                $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);
                $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')->execute([$nuevoHash, $usuarioActual['id']]);
                limpiarFallos($pdo, $clave);

                // Al cambiar la clave se cierran todas las sesiones de este usuario, menos esta
                reabrirSesion();
                session_regenerate_id(true);
                $_SESSION['huella'] = huellaClave($nuevoHash);
                $_SESSION['ultimo'] = time();
                session_write_close();

                header('Location: index.php?clave_cambiada=1');
                exit;
            }
        }
    } catch (PDOException $e) {
        $error = 'No se pudo cambiar la contraseña. ¿Se ejecutó migracion_login.sql en la base de datos?';
    }
}
?>
<!DOCTYPE html>
<html lang="es-AR" translate="no">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Mi clave</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  h1{ font-size:1.4rem; margin:0 0 6px; }
  p.sub{ color:#5B6478; margin:0 0 22px; }
  form{ max-width:420px; background:#fff; border-radius:14px; padding:24px; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.9rem; max-width:420px; background:#FBEAE8; color:#E8483C; border:1px solid #f3c9c4; }
  .campo{ margin-bottom:16px; }
  label{ display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; }
  input{ width:100%; padding:10px 12px; border-radius:9px; border:1px solid rgba(31,42,68,0.15); font-family:inherit; font-size:0.95rem; box-sizing:border-box; }
  .botones{ display:flex; gap:10px; margin-top:22px; }
  button, .btn-cancelar{ font-family:inherit; font-weight:600; font-size:0.9rem; border-radius:9px; padding:11px 18px; cursor:pointer; text-decoration:none; }
  button{ border:none; background:#1B4A9C; color:#fff; }
  .btn-cancelar{ background:none; border:1px solid rgba(31,42,68,0.2); color:#1F2A44; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · Mi clave <?= barraSesionHtml() ?></div>
<h1>Cambiar mi contraseña</h1>
<p class="sub">Si la cambiás, en los otros dispositivos donde estés ingresado se va a pedir la nueva.</p>

<?php if ($error): ?><div class="aviso"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form method="post" action="mi_clave.php" autocomplete="off">
  <div class="campo">
    <label for="actual">Contraseña actual</label>
    <input type="password" id="actual" name="actual" maxlength="200" required autocomplete="current-password">
  </div>
  <div class="campo">
    <label for="nueva">Contraseña nueva (mínimo 8 caracteres)</label>
    <input type="password" id="nueva" name="nueva" maxlength="72" required autocomplete="new-password">
  </div>
  <div class="campo">
    <label for="nueva2">Repetir la contraseña nueva</label>
    <input type="password" id="nueva2" name="nueva2" maxlength="72" required autocomplete="new-password">
  </div>
  <div class="botones">
    <button type="submit">Cambiar contraseña</button>
    <a class="btn-cancelar" href="index.php">Cancelar</a>
  </div>
</form>

</body>
</html>
