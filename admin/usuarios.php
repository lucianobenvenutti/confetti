<?php
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion([ROL_DUENO]);

$usuarios = $pdo->query(
    "SELECT id, nombre, usuario, rol, activo, creado_en FROM usuarios ORDER BY activo DESC, rol ASC, nombre ASC"
)->fetchAll();

$mensaje = $_GET['msg'] ?? null;
$tipoMensaje = $_GET['tipo'] ?? 'error';
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
<title>Confetti — Usuarios</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FBF2E4; color:#3F2B1F; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#7A614E; margin-bottom:18px; }
  .nav-admin a{ color:#7A614E; text-decoration:none; }
  .nav-admin a:hover{ color:#3F2B1F; text-decoration:underline; }
  .cabecera{ display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:22px; }
  h1{ font-size:1.4rem; margin:0; }
  p.sub{ color:#7A614E; margin:4px 0 0; max-width:620px; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.95rem; max-width:720px; }
  .aviso.ok{ background:#EDF3E4; color:#5F8A4B; border:1px solid #D3E3C2; }
  .aviso.error{ background:#FBEAE3; color:#C8402F; border:1px solid #F0CBBF; }
  .btn{ font-family:inherit; font-weight:600; font-size:0.88rem; border:none; border-radius:9px; padding:10px 16px; cursor:pointer; text-decoration:none; display:inline-block; background:#6B4026; color:#fff; }
  table{ width:100%; max-width:860px; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; }
  th, td{ text-align:left; padding:13px 16px; border-bottom:1px solid rgba(63,43,31,0.08); font-size:0.9rem; vertical-align:middle; }
  th{ font-size:0.78rem; color:#7A614E; font-weight:600; }
  tr.inactivo{ opacity:.5; }
  .rol{ font-size:0.75rem; font-weight:700; padding:3px 10px; border-radius:999px; }
  .rol.dueno{ background:#EFE3D6; color:#8A6240; }
  .rol.empleado{ background:#EDF3E4; color:#5F8A4B; }
  a.editar{ color:#6B4026; text-decoration:none; font-size:0.85rem; }
  .yo{ font-size:0.75rem; color:#7A614E; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · Usuarios <?= barraSesionHtml() ?></div>

<div class="cabecera">
  <div>
    <h1>Usuarios</h1>
    <p class="sub"><strong>Dueño:</strong> puede todo. <strong>Empleado:</strong> solo usa el mostrador y los pedidos.
       Para sacarle el acceso a alguien, desactivalo (no se borra, así queda el registro de lo que vendió).</p>
  </div>
  <a class="btn" href="usuario_form.php">+ Nuevo usuario</a>
</div>

<?php if ($mensaje): ?>
  <div class="aviso <?= $tipoMensaje === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<table>
  <thead><tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Estado</th><th>Alta</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($usuarios as $u): ?>
      <tr class="<?= $u['activo'] ? '' : 'inactivo' ?>">
        <td><?= htmlspecialchars($u['nombre']) ?> <?= (int) $u['id'] === (int) $usuarioActual['id'] ? '<span class="yo">(vos)</span>' : '' ?></td>
        <td><?= htmlspecialchars($u['usuario']) ?></td>
        <td><span class="rol <?= $u['rol'] === ROL_DUENO ? 'dueno' : 'empleado' ?>"><?= htmlspecialchars($u['rol']) ?></span></td>
        <td><?= $u['activo'] ? 'Activo' : 'Desactivado' ?></td>
        <td><?= date('d/m/Y', strtotime($u['creado_en'])) ?></td>
        <td><a class="editar" href="usuario_form.php?id=<?= $u['id'] ?>">Editar</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

</body>
</html>
