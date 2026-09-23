<?php
require 'conexion.php';

$productos = $pdo->query(
    'SELECT p.id, p.nombre, p.imagen, c.nombre AS categoria_nombre
     FROM productos p
     JOIN categorias c ON c.id = p.categoria_id
     ORDER BY c.orden ASC, p.nombre ASC'
)->fetchAll();

$mensaje = $_GET['msg'] ?? null;
$tipoMensaje = $_GET['tipo'] ?? 'error';
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Fotos de productos</title>
<style>
  body{
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
    background:#FFFBF3;
    color:#1F2A44;
    margin:0;
    padding:32px 24px 60px;
  }
  h1{ font-size:1.5rem; margin-bottom:4px; }
  p.sub{ color:#5B6478; margin-top:0; margin-bottom:24px; }
  .aviso{
    padding:12px 16px;
    border-radius:10px;
    margin-bottom:20px;
    font-size:0.95rem;
    max-width:640px;
  }
  .aviso.ok{ background:#E4F5EE; color:#1E9C7C; border:1px solid #bfe6d7; }
  .aviso.error{ background:#FBEAE8; color:#E8483C; border:1px solid #f3c9c4; }
  table{
    width:100%;
    max-width:900px;
    border-collapse:collapse;
    background:#fff;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 1px 0 rgba(31,42,68,0.06);
  }
  th, td{
    text-align:left;
    padding:14px 16px;
    border-bottom:1px solid rgba(31,42,68,0.08);
    vertical-align:middle;
  }
  th{
    font-size:0.82rem;
    color:#5B6478;
    font-weight:600;
    text-transform:none;
  }
  .miniatura{
    width:56px; height:56px;
    border-radius:10px;
    object-fit:cover;
    background:#FFF4DE;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1.4rem;
  }
  img.miniatura{ display:block; }
  .categoria{
    font-size:0.8rem;
    color:#5B6478;
  }
  form.subida{
    display:flex;
    align-items:center;
    gap:8px;
  }
  input[type="file"]{
    font-size:0.82rem;
    max-width:160px;
  }
  button{
    font-family:inherit;
    font-weight:600;
    font-size:0.85rem;
    background:#E8483C;
    color:#fff;
    border:none;
    border-radius:8px;
    padding:9px 14px;
    cursor:pointer;
  }
  button:hover{ background:#c73a2f; }
</style>
</head>
<body>

<h1>Fotos de productos</h1>
<p class="sub">Elegí una foto y tocá "Subir" — se reemplaza sola en la página, sin tocar nada más.</p>

<?php if ($mensaje): ?>
  <div class="aviso <?= $tipoMensaje === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<table>
  <thead>
    <tr>
      <th>Foto</th>
      <th>Producto</th>
      <th>Subir nueva foto</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($productos as $p): ?>
      <tr>
        <td>
          <?php if ($p['imagen']): ?>
            <img class="miniatura" src="../assets/productos/<?= htmlspecialchars($p['imagen']) ?>" alt="">
          <?php else: ?>
            <div class="miniatura">🍽️</div>
          <?php endif; ?>
        </td>
        <td>
          <div><?= htmlspecialchars($p['nombre']) ?></div>
          <div class="categoria"><?= htmlspecialchars($p['categoria_nombre']) ?></div>
        </td>
        <td>
          <form class="subida" action="subir_imagen.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
            <input type="file" name="imagen" accept="image/*" required>
            <button type="submit">Subir</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

</body>
</html>