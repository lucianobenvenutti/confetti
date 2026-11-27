<?php
require 'conexion.php';
require __DIR__ . '/auth.php';

cabecerasSeguras();
iniciarSesionSegura();
verificarOrigen();

// Si ya tiene sesión, directo al panel
if (cargarUsuarioDeSesion($pdo)) {
    session_write_close();
    header('Location: index.php');
    exit;
}

$error = null;
$usuarioIngresado = '';

$hayUsuarios = true;
try {
    $hayUsuarios = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0;
} catch (PDOException $e) {
    $error = 'No se pudo leer la tabla de usuarios. Revisá que la base esté al día.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) {
    $usuarioIngresado = substr(trim((string) ($_POST['usuario'] ?? '')), 0, 60);
    $clave = (string) ($_POST['clave'] ?? '');
    $ip = ipCliente();

    try {
        if (ingresoBloqueado($pdo, $usuarioIngresado, $ip)) {
            $error = 'Demasiados intentos fallidos. Esperá unos 15 minutos y probá de nuevo.';
        } else {
            $stmt = $pdo->prepare('SELECT id, password_hash, activo FROM usuarios WHERE usuario = ?');
            $stmt->execute([$usuarioIngresado]);
            $fila = $stmt->fetch();

            // Siempre se verifica una clave (aunque el usuario no exista) para que tarde lo mismo
            $hash = $fila ? $fila['password_hash'] : HASH_FALSO;
            $claveOk = strlen($clave) <= 1000 && password_verify($clave, $hash);

            if ($fila && $fila['activo'] && $claveOk) {
                if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                    $hash = password_hash($clave, PASSWORD_DEFAULT);
                    $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')->execute([$hash, $fila['id']]);
                }

                limpiarFallos($pdo, $usuarioIngresado);
                $volver = destinoValido($_SESSION['volver'] ?? null) ?? 'index.php';

                abrirSesionDeUsuario((int) $fila['id'], $hash);
                session_write_close();
                header('Location: ' . $volver);
                exit;
            }

            registrarFallo($pdo, $usuarioIngresado, $ip);
            $error = 'Usuario o contraseña incorrectos.';
        }
    } catch (PDOException $e) {
        $error = 'El sistema de ingreso no está listo: falta ejecutar migracion_login.sql en la base de datos.';
    }
}

$aviso = null;
if (isset($_GET['salio']))  $aviso = 'Cerraste la sesión.';
if (isset($_GET['creado'])) $aviso = 'Usuario creado. Ya podés ingresar.';
if (isset($_GET['clave']))  $aviso = 'Contraseña cambiada. Ingresá de nuevo con la nueva.';
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
<meta name="robots" content="noindex, nofollow">
<title>Confetti — Ingresar</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FBF2E4; color:#3F2B1F; margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; box-sizing:border-box; }
  .tarjeta{ width:100%; max-width:400px; background:#fff; border-radius:18px; padding:30px 28px; box-shadow:0 10px 30px rgba(63,43,31,0.08); box-sizing:border-box; }
  .logo{ display:block; max-width:150px; max-height:64px; margin:0 auto 14px; border-radius:10px; }
  h1{ font-size:1.35rem; margin:0 0 4px; text-align:center; }
  p.sub{ color:#7A614E; margin:0 0 22px; text-align:center; font-size:0.92rem; }
  .aviso{ padding:11px 14px; border-radius:10px; margin-bottom:18px; font-size:0.9rem; }
  .aviso.error{ background:#FBEAE3; color:#C0392B; border:1px solid #F0CBBF; }
  .aviso.ok{ background:#EDF3E4; color:#5F8A4B; border:1px solid #D3E3C2; }
  .aviso.info{ background:#F4E4CC; color:#8a4b00; border:1px solid #f5d9a8; }
  label{ display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; }
  input{ width:100%; padding:12px 14px; border-radius:10px; border:1px solid rgba(63,43,31,0.18); font-family:inherit; font-size:1rem; box-sizing:border-box; margin-bottom:16px; }
  input:focus{ outline:2px solid #D9B27A; outline-offset:1px; border-color:#B8732E; }
  .campo-clave{ position:relative; }
  .campo-clave input{ padding-right:84px; }
  .campo-clave button{ position:absolute; right:8px; top:8px; background:#F3EADB; border:none; border-radius:8px; padding:7px 12px; font-size:0.78rem; font-weight:600; color:#3F2B1F; cursor:pointer; font-family:inherit; }
  .entrar{ width:100%; border:none; border-radius:10px; padding:13px; background:#A8601F; color:#fff; font-weight:700; font-size:1rem; cursor:pointer; font-family:inherit; }
  .entrar:hover{ background:#8A4F1C; }
  a{ color:#6B4026; }
</style>
</head>
<body>
<main class="tarjeta">
  <img class="logo" src="../assets/logo-confetti.png" alt="Confetti" onerror="this.style.display='none'">
  <h1>Panel de Confetti</h1>
  <p class="sub">Ingresá con tu usuario y contraseña.</p>

  <?php if ($aviso): ?><div class="aviso ok"><?= htmlspecialchars($aviso) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="aviso error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if (!$hayUsuarios): ?>
    <div class="aviso info">Todavía no hay ningún usuario. <a href="configurar_inicial.php">Crear el primer usuario (dueño)</a>.</div>
  <?php endif; ?>

  <form method="post" action="login.php">
    <label for="usuario">Usuario</label>
    <input id="usuario" name="usuario" type="text" maxlength="60" required autofocus
           autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false"
           value="<?= htmlspecialchars($usuarioIngresado) ?>">

    <label for="clave">Contraseña</label>
    <div class="campo-clave">
      <input id="clave" name="clave" type="password" maxlength="200" required autocomplete="current-password">
      <button type="button" id="ver-clave" aria-label="Mostrar u ocultar la contraseña">Mostrar</button>
    </div>

    <button type="submit" class="entrar">Ingresar</button>
  </form>
</main>
<script>
  const campoClave = document.getElementById('clave');
  const botonVer = document.getElementById('ver-clave');
  botonVer.addEventListener('click', () => {
    const oculta = campoClave.type === 'password';
    campoClave.type = oculta ? 'text' : 'password';
    botonVer.textContent = oculta ? 'Ocultar' : 'Mostrar';
  });
</script>
</body>
</html>
