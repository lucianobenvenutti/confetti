<?php
require 'conexion.php';

$horarios = $pdo->query('SELECT * FROM horarios_atencion ORDER BY dia ASC')->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);
$nombresDias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

$mensaje = $_GET['msg'] ?? null;
$tipoMensaje = $_GET['tipo'] ?? 'error';

function hora($valor) {
    return $valor ? substr($valor, 0, 5) : '';
}
?>
<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Horario de atención</title>
<style>
  body{ font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background:#FFFBF3; color:#1F2A44; margin:0; padding:32px 24px 60px; }
  .nav-admin{ font-size:0.85rem; color:#5B6478; margin-bottom:18px; }
  .nav-admin a{ color:#5B6478; text-decoration:none; }
  .nav-admin a:hover{ color:#1F2A44; text-decoration:underline; }
  h1{ font-size:1.4rem; margin:0 0 4px; }
  p.sub{ color:#5B6478; margin:0 0 8px; }
  p.ayuda{ color:#5B6478; font-size:0.85rem; margin:0 0 22px; max-width:640px; }
  .aviso{ padding:12px 16px; border-radius:10px; margin-bottom:20px; font-size:0.95rem; max-width:720px; }
  .aviso.ok{ background:#E4F5EE; color:#1E9C7C; border:1px solid #bfe6d7; }
  .aviso.error{ background:#FBEAE8; color:#E8483C; border:1px solid #f3c9c4; }

  form{ max-width:760px; }
  table{ width:100%; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; margin-bottom:22px; }
  th, td{ text-align:left; padding:12px 14px; border-bottom:1px solid rgba(31,42,68,0.08); font-size:0.88rem; vertical-align:middle; }
  th{ font-size:0.78rem; color:#5B6478; font-weight:600; }
  .turno{ display:flex; align-items:center; gap:6px; }
  .turno span{ color:#5B6478; font-size:0.8rem; }
  input[type="time"]{
    padding:7px 8px; border-radius:8px; border:1px solid rgba(31,42,68,0.15); font-family:inherit; font-size:0.86rem;
  }
  input[type="time"]:disabled{ background:#f2f0ea; color:#aab; }
  .fila-cerrado{ display:flex; align-items:center; gap:6px; font-size:0.85rem; }

  button{
    font-family:inherit; font-weight:600; font-size:0.9rem; border:none; border-radius:9px;
    padding:11px 20px; cursor:pointer; background:#1B4A9C; color:#fff;
  }
  button:hover{ background:#153c7e; }
</style>
</head>
<body>

<div class="nav-admin"><a href="index.php">← Panel</a> · Horario de atención</div>

<h1>Horario de atención</h1>
<p class="sub">Esto define el cartel de "abierto ahora" que ve el cliente en la página.</p>
<p class="ayuda">
  Cada día tiene dos turnos: <strong>mañana</strong> (apertura y cierre del mediodía) y <strong>tarde</strong>
  (apertura y cierre de la tarde). Si un día trabajan de corrido, completá solo el turno de la mañana
  (por ejemplo 09:00 a 22:00) y dejá el de la tarde vacío.
</p>

<?php if ($mensaje): ?>
  <div class="aviso <?= $tipoMensaje === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<form method="post" action="guardar_horarios.php">
  <table>
    <thead>
      <tr>
        <th>Día</th>
        <th>Cerrado</th>
        <th>Mañana (abre → cierra)</th>
        <th>Tarde (abre → cierra)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($nombresDias as $dia => $nombre):
        $h = $horarios[$dia] ?? ['abre' => '09:00', 'cierra' => '21:00', 'abre2' => null, 'cierra2' => null, 'cerrado' => 0];
        $dis = $h['cerrado'] ? 'disabled' : '';
      ?>
        <tr>
          <td><?= $nombre ?></td>
          <td>
            <div class="fila-cerrado">
              <input type="checkbox" name="cerrado[<?= $dia ?>]" id="cerrado_<?= $dia ?>"
                     class="chk-cerrado" data-dia="<?= $dia ?>" <?= $h['cerrado'] ? 'checked' : '' ?>>
              <label for="cerrado_<?= $dia ?>">Cerrado</label>
            </div>
          </td>
          <td>
            <div class="turno">
              <input type="time" name="abre[<?= $dia ?>]" class="t-<?= $dia ?>" value="<?= hora($h['abre']) ?>" <?= $dis ?>>
              <span>a</span>
              <input type="time" name="cierra[<?= $dia ?>]" class="t-<?= $dia ?>" value="<?= hora($h['cierra']) ?>" <?= $dis ?>>
            </div>
          </td>
          <td>
            <div class="turno">
              <input type="time" name="abre2[<?= $dia ?>]" class="t-<?= $dia ?>" value="<?= hora($h['abre2'] ?? null) ?>" <?= $dis ?>>
              <span>a</span>
              <input type="time" name="cierra2[<?= $dia ?>]" class="t-<?= $dia ?>" value="<?= hora($h['cierra2'] ?? null) ?>" <?= $dis ?>>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <button type="submit">Guardar horario</button>
</form>

<script>
  document.querySelectorAll('.chk-cerrado').forEach(chk => {
    chk.addEventListener('change', () => {
      document.querySelectorAll('.t-' + chk.dataset.dia).forEach(input => {
        input.disabled = chk.checked;
      });
    });
  });
</script>

</body>
</html>
