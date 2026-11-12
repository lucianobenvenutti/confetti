<?php
/**
 * Crea al PRIMER usuario (dueño). Se bloquea solo apenas existe cualquier usuario.
 * Además pide la clave de instalación (CLAVE_INSTALACION en auth.php), para que nadie
 * pueda adueñarse del panel si lo publicás antes de crear tu usuario.
 */
require 'conexion.php';
require __DIR__ . '/auth.php';

cabecerasSeguras();
iniciarSesionSegura();
verificarOrigen();
session_write_close();

$estado = 'formulario';   // formulario | ya_configurado | falta_clave | error_tabla
$error = null;
$valores = ['nombre' => '', 'usuario' => ''];

try {
    $yaHay = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0;
} catch (PDOException $e) {
    $yaHay = false;
    $estado = 'error_tabla';
}

if ($yaHay) {
    $estado = 'ya_configurado';
} elseif ($estado === 'formulario' && (CLAVE_INSTALACION === 'cambiar-esto-por-una-clave-larga-y-secreta' || strlen(CLAVE_INSTALACION) < 12)) {
    $estado = 'falta_clave';
}

if ($estado === 'formulario' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = ipCliente();
    $valores['nombre'] = substr(trim((string) ($_POST['nombre'] ?? '')), 0, 80);
    $valores['usuario'] = strtolower(substr(trim((string) ($_POST['usuario'] ?? '')), 0, 60));
    $clave = (string) ($_POST['clave'] ?? '');
    $clave2 = (string) ($_POST['clave2'] ?? '');
    $claveInstalacion = (string) ($_POST['clave_instalacion'] ?? '');

    try {
        if (ingresoBloqueado($pdo, '__instalacion__', $ip)) {
            $error = 'Demasiados intentos. Esperá unos 15 minutos y probá de nuevo.';
        } elseif (!hash_equals(CLAVE_INSTALACION, $claveInstalacion)) {
            registrarFallo($pdo, '__instalacion__', $ip);
            $error = 'La clave de instalación no es correcta.';
        } elseif ($valores['nombre'] === '') {
            $error = 'Escribí tu nombre.';
        } elseif (!preg_match('/^[a-z0-9._-]{3,60}$/', $valores['usuario'])) {
            $error = 'El usuario tiene que tener entre 3 y 60 caracteres: letras sin tildes, números, punto, guion o guion bajo.';
        } elseif ($clave !== $clave2) {
            $error = 'Las dos contraseñas no coinciden.';
        } elseif (($problema = validarClaveNueva($clave, $valores['usuario'])) !== null) {
            $error = $problema;
        } else {
            // "Insertar solo si la tabla está vacía", en una única instrucción
            $stmt = $pdo->prepare(
                "INSERT INTO usuarios (nombre, usuario, password_hash, rol, activo)
                 SELECT ?, ?, ?, 'dueño', 1 FROM DUAL
                 WHERE NOT EXISTS (SELECT 1 FROM usuarios)"
            );
            $stmt->execute([$valores['nombre'], $valores['usuario'], password_hash($clave, PASSWORD_DEFAULT)]);

            if ($stmt->rowCount() === 1) {
                limpiarFallos($pdo, '__instalacion__');
                header('Location: login.php?creado=1');
                exit;
            }
            $estado = 'ya_configurado';
        }
    } catch (PDOException $e) {
        $error = 'No se pudo crear el usuario. ¿Se ejecutó migracion_login.sql en la base de datos?';
    }
}
?>
<!DOCTYPE html>
<html lang="es-AR" translate="no">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Confetti — Primera configuración</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; box-sizing:border-box; }
  .tarjeta{ width:100%; max-width:400px; background:#fff; border-radius:18px; padding:30px 28px; box-shadow:0 10px 30px rgba(31,42,68,0.08); box-sizing:border-box; }
  .logo{ display:block; max-width:150px; max-height:64px; margin:0 auto 14px; border-radius:10px; }
  h1{ font-size:1.35rem; margin:0 0 4px; text-align:center; }
  p.sub{ color:#5B6478; margin:0 0 22px; text-align:center; font-size:0.92rem; }
  .aviso{ padding:11px 14px; border-radius:10px; margin-bottom:18px; font-size:0.9rem; }
  .aviso.error{ background:#FBEAE8; color:#C0392B; border:1px solid #f3c9c4; }
  .aviso.ok{ background:#E4F5EE; color:#1E9C7C; border:1px solid #bfe6d7; }
  .aviso.info{ background:#FFF4DE; color:#8a4b00; border:1px solid #f5d9a8; }
  label{ display:block; font-size:0.85rem; font-weight:600; margin-bottom:6px; }
  input{ width:100%; padding:12px 14px; border-radius:10px; border:1px solid rgba(31,42,68,0.18); font-family:inherit; font-size:1rem; box-sizing:border-box; margin-bottom:16px; }
  input:focus{ outline:2px solid #A8E0E8; outline-offset:1px; border-color:#6bbcc8; }
  .campo-clave{ position:relative; }
  .campo-clave input{ padding-right:84px; }
  .campo-clave button{ position:absolute; right:8px; top:8px; background:#EEF4F5; border:none; border-radius:8px; padding:7px 12px; font-size:0.78rem; font-weight:600; color:#1F2A44; cursor:pointer; font-family:inherit; }
  .entrar{ width:100%; border:none; border-radius:10px; padding:13px; background:#E8483C; color:#fff; font-weight:700; font-size:1rem; cursor:pointer; font-family:inherit; }
  .entrar:hover{ background:#c73a2f; }
  a{ color:#1B4A9C; }
</style>
</head>
<body>
<main class="tarjeta">
  <h1>Primera configuración</h1>

  <?php if ($estado === 'ya_configurado'): ?>
    <p class="sub">Ya hay usuarios creados, así que esta pantalla está bloqueada.</p>
    <p style="text-align:center;"><a href="login.php">Ir a ingresar</a></p>

  <?php elseif ($estado === 'error_tabla'): ?>
    <div class="aviso error">No se pudo leer la tabla de usuarios. Revisá que la base de datos esté creada y al día.</div>

  <?php elseif ($estado === 'falta_clave'): ?>
    <p class="sub">Antes de usar esta pantalla hay que elegir una clave de instalación.</p>
    <div class="aviso info">
      Abrí <strong>admin/auth.php</strong> y cambiá el valor de <strong>CLAVE_INSTALACION</strong> por una clave larga y secreta
      (al menos 12 caracteres). Después recargá esta página. La clave solo sirve para crear al primer usuario.
    </div>

  <?php else: ?>
    <p class="sub">Creá el usuario del dueño. Esta pantalla se bloquea sola cuando termines.</p>
    <?php if ($error): ?><div class="aviso error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="post" action="configurar_inicial.php">
      <label for="clave_instalacion">Clave de instalación</label>
      <input id="clave_instalacion" name="clave_instalacion" type="password" required autocomplete="off">

      <label for="nombre">Tu nombre</label>
      <input id="nombre" name="nombre" type="text" maxlength="80" required value="<?= htmlspecialchars($valores['nombre']) ?>">

      <label for="usuario">Usuario para ingresar</label>
      <input id="usuario" name="usuario" type="text" maxlength="60" required autocomplete="off" autocapitalize="none"
             spellcheck="false" value="<?= htmlspecialchars($valores['usuario']) ?>">

      <label for="clave">Contraseña (mínimo 8 caracteres)</label>
      <input id="clave" name="clave" type="password" maxlength="72" required autocomplete="new-password">

      <label for="clave2">Repetir contraseña</label>
      <input id="clave2" name="clave2" type="password" maxlength="72" required autocomplete="new-password">

      <button type="submit" class="entrar">Crear usuario</button>
    </form>
  <?php endif; ?>
</main>

</body>
</html>
