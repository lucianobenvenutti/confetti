<?php
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion(ROLES_TODOS);
require __DIR__ . '/../api/vencimientos.php';

// Lo primero: cancelar (y devolver el stock de) lo que ya pasó las 24hs.
$vencidosAhora = liberarPedidosVencidos($pdo);

// Un pedido activo vive hasta 24hs, así que puede ser de ayer y seguir en juego:
// se muestran todos los activos (sin importar el día) más lo resuelto hoy.
// Cuánto pasó y cuánto queda lo calcula MySQL, en segundos reales: no depende
// de la zona horaria de nadie.
$pedidos = $pdo->query(
    "SELECT id, codigo, nombre_cliente, telefono_cliente, origen, estado, total, notas, creado_en,
            UNIX_TIMESTAMP() - UNIX_TIMESTAMP(creado_en) AS seg_transcurridos,
            UNIX_TIMESTAMP(vence_en) - UNIX_TIMESTAMP() AS seg_restantes
     FROM pedidos
     WHERE estado IN ('pendiente','en_preparacion','listo')
        OR DATE(creado_en) = CURDATE()
     ORDER BY FIELD(estado, 'pendiente','en_preparacion','listo','entregado','cancelado'),
              creado_en ASC"
)->fetchAll();

// Traigo los ítems de todos los pedidos de hoy en una sola consulta y los agrupo en PHP
$itemsPorPedido = [];
if ($pedidos) {
    $ids = array_column($pedidos, 'id');
    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare(
        "SELECT pi.pedido_id, pi.cantidad, pi.subtotal,
                COALESCE(pr.nombre, co.nombre) AS nombre_item,
                pr.tipo_venta
         FROM pedido_items pi
         LEFT JOIN productos pr ON pr.id = pi.producto_id
         LEFT JOIN combos co ON co.id = pi.combo_id
         WHERE pi.pedido_id IN ($marcadores)"
    );
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $item) {
        $itemsPorPedido[$item['pedido_id']][] = $item;
    }
}

$mensaje = $_GET['msg'] ?? null;
$tipoMensaje = $_GET['tipo'] ?? 'error';
$linkWhatsapp = $_GET['whatsapp'] ?? null;

$etiquetasEstado = [
    'pendiente'      => 'Pendiente',
    'en_preparacion' => 'En preparación',
    'listo'          => 'Listo para retirar',
    'entregado'      => 'Entregado',
    'cancelado'      => 'Cancelado',
];

// 3725 segundos -> "1 h 2 min"
function duracion($segundos) {
    $min = intdiv(max(0, (int) $segundos), 60);
    if ($min < 1) return 'menos de 1 min';
    $h = intdiv($min, 60);
    $m = $min % 60;
    if ($h >= 24) return intdiv($h, 24) . ' d ' . ($h % 24) . ' h';
    if ($h > 0) return $h . ' h' . ($m ? " $m min" : '');
    return $m . ' min';
}

function etiquetaCantidad($item) {
    if ($item['tipo_venta'] === 'peso') {
        $kg = (float) $item['cantidad'];
        return $kg < 1 ? round($kg * 1000) . 'g' : $kg . 'kg';
    }
    return 'x' . (int) $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es-AR" translate="no">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Pedidos</title>
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

  .lista{
    display:flex;
    flex-direction:column;
    gap:14px;
    max-width:760px;
  }
  .pedido{
    background:#fff;
    border-radius:14px;
    border:1px solid rgba(31,42,68,0.1);
    border-left:5px solid var(--acento, #5B6478);
    padding:18px 20px;
  }
  .pedido-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
    flex-wrap:wrap;
  }
  .pedido-head .codigo{
    font-weight:700;
    font-size:1.02rem;
  }
  .pedido-head .cliente{
    color:#5B6478;
    font-size:0.88rem;
    margin-top:2px;
  }
  .badges{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
  }
  .badge{
    font-size:0.76rem;
    font-weight:600;
    padding:4px 10px;
    border-radius:999px;
    background:#FFF4DE;
    color:#1F2A44;
    white-space:nowrap;
  }
  .badge.origen{ background:#EFEAF7; color:#6E4E9E; }

  .items{
    margin:14px 0 10px;
    padding-top:12px;
    border-top:1px dashed rgba(31,42,68,0.14);
    font-size:0.9rem;
  }
  .items div{
    display:flex;
    justify-content:space-between;
    padding:3px 0;
    color:#3a4256;
  }
  .total-row{
    display:flex;
    justify-content:space-between;
    font-weight:700;
    padding-top:8px;
    border-top:1px solid rgba(31,42,68,0.1);
    margin-top:4px;
  }

  .acciones{
    margin-top:14px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }
  button{
    font-family:inherit;
    font-weight:600;
    font-size:0.85rem;
    border:none;
    border-radius:8px;
    padding:9px 16px;
    cursor:pointer;
  }
  .btn-avanzar{ background:#1E9C7C; color:#fff; }
  .btn-avanzar:hover{ background:#178067; }
  .btn-cancelar{ background:transparent; color:#E8483C; border:1px solid #E8483C; }
  .btn-cancelar:hover{ background:#FBEAE8; }
  .sin-pedidos{ color:#5B6478; }

  .cuenta-atras{
    margin-top:12px;
    display:inline-block;
    background:#F2F4F8;
    color:#5B6478;
    font-size:0.82rem;
    padding:6px 12px;
    border-radius:9px;
  }
  .cuenta-atras .cuenta-valor{
    font-weight:700;
    color:#1F2A44;
    font-variant-numeric:tabular-nums;
  }
  .cuenta-atras.urgente{ background:#FFF1DC; }
  .cuenta-atras.urgente .cuenta-valor{ color:#C26A00; }
  .cuenta-atras.vencido{ background:#FBEAE8; color:#E8483C; font-weight:600; }

  /* Lo que el cliente pidió aclarar: tiene que verse a simple vista */
  .comentario-cliente{
    margin-top:12px;
    background:#FFF8E1;
    border-left:4px solid #F5941F;
    border-radius:8px;
    padding:10px 14px;
    font-size:0.92rem;
    line-height:1.45;
    overflow-wrap:anywhere;
  }
  .comentario-cliente .etiqueta-comentario{
    display:block;
    font-size:0.74rem;
    font-weight:700;
    color:#8a4b00;
    margin-bottom:3px;
  }

  /* Actualización automática y aviso de pedidos nuevos */
  .barra-vivo{ display:flex; align-items:center; gap:14px; flex-wrap:wrap; margin-bottom:18px; }
  .btn-sonido{
    font-family:inherit; font-weight:600; font-size:0.82rem; background:#fff; color:#1F2A44;
    border:1px solid rgba(31,42,68,0.2); border-radius:9px; padding:8px 14px; cursor:pointer;
  }
  .btn-sonido.activo{ background:#E4F5EE; border-color:#bfe6d7; color:#1E9C7C; }
  .estado-vivo{ font-size:0.8rem; color:#5B6478; }
  .estado-vivo.sin-conexion{ color:#E8483C; font-weight:600; }

  .alerta-nuevo{
    display:none; position:sticky; top:10px; z-index:20; max-width:760px; box-sizing:border-box;
    background:#FFF1DC; border:1px solid #f5c98a; color:#8a4b00; border-radius:12px;
    padding:12px 16px; margin-bottom:16px; font-weight:700;
    align-items:center; justify-content:space-between; gap:12px;
    box-shadow:0 6px 18px rgba(31,42,68,0.12);
  }
  .alerta-nuevo.visible{ display:flex; }
  .alerta-nuevo button{
    font-family:inherit; font-weight:600; font-size:0.82rem; background:#8a4b00; color:#fff;
    border:none; border-radius:8px; padding:7px 14px; cursor:pointer;
  }
  .pedido.nuevo{ box-shadow:0 0 0 3px rgba(245,148,31,0.45); animation:pulso 1.6s ease-in-out 3; }
  @keyframes pulso{ 50%{ box-shadow:0 0 0 8px rgba(245,148,31,0.2); } }
</style>
</head>
<body>

<div class="nav-admin" style="font-size:0.85rem; color:#5B6478; margin-bottom:18px;">
  <a href="index.php" style="color:#5B6478; text-decoration:none;">← Panel</a> ·
  Pedidos web ·
  <a href="mostrador.php" style="color:#5B6478; text-decoration:none;">Mostrador</a>
  <?php if (esDueno($usuarioActual)): ?>
    · <a href="ventas.php" style="color:#5B6478; text-decoration:none;">Ventas</a> ·
    <a href="horarios.php" style="color:#5B6478; text-decoration:none;">Horario</a> ·
    <a href="cuentas.php" style="color:#5B6478; text-decoration:none;">Cuentas</a>
  <?php endif; ?>
  <?= barraSesionHtml() ?>
</div>

<h1>Pedidos</h1>
<p class="sub">Los pedidos activos (de cualquier día) y lo resuelto hoy. Cada pedido web se cancela solo a las 24hs de hecho y su stock vuelve al catálogo.</p>

<div class="barra-vivo">
  <button type="button" class="btn-sonido" id="btn-sonido">🔕 Activar sonido de avisos</button>
  <span class="estado-vivo" id="estado-vivo">Se actualiza solo cada 30 segundos</span>
</div>

<div class="alerta-nuevo" id="alerta-nuevo" role="status" aria-live="polite">
  <span id="alerta-texto"></span>
  <button type="button" id="btn-visto">Entendido</button>
</div>

<div id="avisos">
<?php if ($mensaje): ?>
  <div class="aviso <?= $tipoMensaje === 'ok' ? 'ok' : 'error' ?>"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<?php if ($vencidosAhora): ?>
  <div class="aviso error aviso-vencidos">
    Se cancelaron automáticamente <?= count($vencidosAhora) ?> pedido(s) por vencer las 24hs y su stock volvió al catálogo:
    <?= htmlspecialchars(implode(', ', $vencidosAhora)) ?>
  </div>
<?php endif; ?>

<?php if ($linkWhatsapp): ?>
  <div class="aviso ok" style="display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;">
    <span>Avisale al cliente que ya puede pasar a retirarlo.</span>
    <a href="<?= htmlspecialchars($linkWhatsapp) ?>" target="_blank"
       style="background:#1E9C7C; color:#fff; font-weight:700; padding:9px 16px; border-radius:9px; text-decoration:none; white-space:nowrap;">
      📲 Avisar por WhatsApp
    </a>
  </div>
<?php endif; ?>
</div>

<div class="lista" id="lista">
  <?php if (!$pedidos): ?>
    <p class="sin-pedidos">Todavía no hay pedidos cargados hoy.</p>
  <?php endif; ?>
  <?php foreach ($pedidos as $p):
    $acentos = [
      'pendiente' => '#F5941F', 'en_preparacion' => '#1B4A9C',
      'listo' => '#1E9C7C', 'entregado' => '#8891a3', 'cancelado' => '#E8483C',
    ];
  ?>
    <div class="pedido" data-id="<?= $p['id'] ?>" data-origen="<?= $p['origen'] ?>" data-estado="<?= $p['estado'] ?>"
         style="--acento: <?= $acentos[$p['estado']] ?>;">
      <div class="pedido-head">
        <div>
          <div class="codigo"><?= htmlspecialchars($p['codigo']) ?></div>
          <div class="cliente"><?= htmlspecialchars($p['nombre_cliente']) ?> · <?= htmlspecialchars($p['telefono_cliente']) ?></div>
        </div>
        <div class="badges">
          <span class="badge origen"><?= $p['origen'] === 'web' ? 'Pedido web' : 'Mostrador' ?></span>
          <span class="badge"><?= $etiquetasEstado[$p['estado']] ?></span>
          <span class="badge">Hace <?= duracion($p['seg_transcurridos']) ?></span>
        </div>
      </div>

      <?php if ($p['seg_restantes'] !== null && in_array($p['estado'], ['pendiente', 'en_preparacion', 'listo'], true)): ?>
        <div class="cuenta-atras" data-seg="<?= (int) $p['seg_restantes'] ?>">
          Se cancela solo en <span class="cuenta-valor"><?= duracion($p['seg_restantes']) ?></span>
        </div>
      <?php endif; ?>

      <?php if ($p['origen'] === 'web' && !empty($p['notas'])): ?>
        <div class="comentario-cliente">
          <span class="etiqueta-comentario">💬 Comentario del cliente</span>
          <?= nl2br(htmlspecialchars($p['notas'])) ?>
        </div>
      <?php endif; ?>

      <div class="items">
        <?php foreach ($itemsPorPedido[$p['id']] ?? [] as $item): ?>
          <div>
            <span><?= htmlspecialchars($item['nombre_item']) ?> (<?= etiquetaCantidad($item) ?>)</span>
            <span>$<?= number_format($item['subtotal'], 0, ',', '.') ?></span>
          </div>
        <?php endforeach; ?>
        <div class="total-row">
          <span>Total</span>
          <span>$<?= number_format($p['total'], 0, ',', '.') ?></span>
        </div>
      </div>

      <?php if (in_array($p['estado'], ['pendiente', 'en_preparacion', 'listo'], true)): ?>
        <div class="acciones">
          <form method="post" action="cambiar_estado.php" style="display:inline;">
            <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
            <input type="hidden" name="accion" value="avanzar">
            <button type="submit" class="btn-avanzar">
              <?php
                echo match ($p['estado']) {
                  'pendiente' => 'Empezar a preparar',
                  'en_preparacion' => 'Marcar listo',
                  'listo' => 'Marcar entregado',
                };
              ?>
            </button>
          </form>
          <?php if ($p['estado'] !== 'listo'): ?>
            <form method="post" action="cambiar_estado.php" style="display:inline;"
                  onsubmit="return confirm('¿Cancelar este pedido y liberar su stock reservado?');">
              <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
              <input type="hidden" name="accion" value="cancelar">
              <button type="submit" class="btn-cancelar">Cancelar pedido</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<script>
  // =========================================================
  // Cuenta atrás de cada pedido
  // El servidor manda los segundos que faltaban al armar la lista (calculados
  // por MySQL); acá solo se restan los que pasaron desde entonces. No se usa
  // ninguna hora ni zona horaria del navegador.
  // =========================================================
  let cargadoEn = Date.now();
  const dos = n => String(n).padStart(2, '0');

  function actualizarCuentas() {
    const pasados = Math.floor((Date.now() - cargadoEn) / 1000);
    document.querySelectorAll('.cuenta-atras').forEach(el => {
      const restante = parseInt(el.dataset.seg, 10) - pasados;

      if (restante <= 0) {
        el.classList.add('vencido');
        el.textContent = 'Plazo vencido: se cancela en la próxima actualización';
        return;
      }

      const h = Math.floor(restante / 3600);
      const m = Math.floor((restante % 3600) / 60);
      const s = restante % 60;
      el.querySelector('.cuenta-valor').textContent = `${dos(h)}:${dos(m)}:${dos(s)}`;
      el.classList.toggle('urgente', restante < 3 * 3600);
    });
  }

  actualizarCuentas();
  setInterval(actualizarCuentas, 1000);

  // =========================================================
  // Actualización automática + aviso de pedidos nuevos
  // Cada 30 segundos se vuelve a pedir esta misma pantalla y se reemplaza solo
  // la lista, sin recargar: así no se pierde el sonido activado ni lo que se
  // esté haciendo. Los botones de cada pedido también funcionan sin recargar.
  // =========================================================
  const INTERVALO_MS = 30000;
  const lista = document.getElementById('lista');
  const avisos = document.getElementById('avisos');
  const alertaNuevo = document.getElementById('alerta-nuevo');
  const alertaTexto = document.getElementById('alerta-texto');
  const estadoVivo = document.getElementById('estado-vivo');
  const tituloOriginal = document.title;

  let ultimaActualizacion = Date.now();
  let fallos = 0;
  let consultando = false;
  let sesionVencida = false;
  const nuevosIds = new Set();   // pedidos web que llegaron y todavía no se vieron

  // Los ids siempre crecen: un pedido con id mayor a todos los vistos es nuevo.
  let ultimoWebVisto = 0;
  document.querySelectorAll('.pedido[data-origen="web"]').forEach(c => {
    ultimoWebVisto = Math.max(ultimoWebVisto, parseInt(c.dataset.id, 10));
  });

  // ----- Sonido -----
  // Los navegadores no dejan sonar nada hasta que la persona toca la página una vez:
  // por eso el botón. Después de tocarlo, suena solo cada vez que llega un pedido.
  let audioCtx = null;
  const btnSonido = document.getElementById('btn-sonido');

  function tocarAviso() {
    if (!audioCtx) return;
    if (audioCtx.state === 'suspended') audioCtx.resume().catch(() => {});
    const t0 = audioCtx.currentTime;
    [[880, 0], [1175, 0.22], [880, 0.55], [1175, 0.77]].forEach(([frecuencia, demora]) => {
      const osc = audioCtx.createOscillator();
      const volumen = audioCtx.createGain();
      osc.type = 'sine';
      osc.frequency.value = frecuencia;
      volumen.gain.setValueAtTime(0.0001, t0 + demora);
      volumen.gain.exponentialRampToValueAtTime(0.35, t0 + demora + 0.02);
      volumen.gain.exponentialRampToValueAtTime(0.0001, t0 + demora + 0.2);
      osc.connect(volumen);
      volumen.connect(audioCtx.destination);
      osc.start(t0 + demora);
      osc.stop(t0 + demora + 0.22);
    });
  }

  btnSonido.addEventListener('click', () => {
    if (audioCtx) {
      audioCtx.close();
      audioCtx = null;
      btnSonido.textContent = '🔕 Activar sonido de avisos';
      btnSonido.classList.remove('activo');
      return;
    }
    const Contexto = window.AudioContext || window.webkitAudioContext;
    if (!Contexto) {
      alert('Este navegador no permite reproducir sonido. Los avisos van a aparecer igual en pantalla.');
      return;
    }
    audioCtx = new Contexto();
    audioCtx.resume();
    tocarAviso(); // una muestra, para comprobar que se escucha
    btnSonido.textContent = '🔔 Sonido activado';
    btnSonido.classList.add('activo');
  });

  // ----- Aviso en pantalla -----
  function marcarNuevos() {
    lista.querySelectorAll('.pedido').forEach(c => {
      c.classList.toggle('nuevo', nuevosIds.has(parseInt(c.dataset.id, 10)));
    });
  }

  function pintarAlerta() {
    const n = nuevosIds.size;
    alertaNuevo.classList.toggle('visible', n > 0);
    alertaTexto.textContent = n === 1 ? '🔔 Llegó un pedido nuevo' : `🔔 Llegaron ${n} pedidos nuevos`;
    document.title = n > 0 ? `(${n}) Pedido nuevo · ${tituloOriginal}` : tituloOriginal;
  }

  function verNuevos() {
    nuevosIds.clear();
    marcarNuevos();
    pintarAlerta();
  }

  document.getElementById('btn-visto').addEventListener('click', verNuevos);

  // Tocar un pedido nuevo (o actuar sobre él) lo da por visto
  lista.addEventListener('click', (e) => {
    const tarjeta = e.target.closest('.pedido');
    if (tarjeta && nuevosIds.delete(parseInt(tarjeta.dataset.id, 10))) {
      marcarNuevos();
      pintarAlerta();
    }
  });

  // ----- Traer y aplicar la pantalla -----
  async function traerPagina(url, opciones = {}) {
    const res = await fetch(url, { cache: 'no-store', ...opciones });
    // Si la sesión venció, el servidor responde con la pantalla de ingreso en lugar de la lista
    if (res.redirected && /login\.php/.test(res.url)) throw new Error('SESION_VENCIDA');
    const html = await res.text();
    return new DOMParser().parseFromString(html, 'text/html');
  }

  function aplicarPagina(doc, conAvisos) {
    const nueva = doc.getElementById('lista');
    if (!nueva) throw new Error('Respuesta inesperada del servidor');

    // ¿Llegó algún pedido web que no estaba? (si ya lo movieron a "en preparación" desde
    // otro lado, no hace falta avisar: solo avisamos de los que siguen pendientes)
    let llegaron = 0;
    let maximo = ultimoWebVisto;
    nueva.querySelectorAll('.pedido[data-origen="web"]').forEach(c => {
      const id = parseInt(c.dataset.id, 10);
      if (id > ultimoWebVisto && c.dataset.estado === 'pendiente') {
        nuevosIds.add(id);
        llegaron++;
      }
      maximo = Math.max(maximo, id);
    });
    ultimoWebVisto = maximo;

    lista.innerHTML = nueva.innerHTML;
    cargadoEn = Date.now();

    // Si un pedido nuevo ya no está en la lista (se canceló o se resolvió), no lo seguimos avisando
    [...nuevosIds].forEach(id => {
      if (!lista.querySelector(`.pedido[data-id="${id}"]`)) nuevosIds.delete(id);
    });

    marcarNuevos();
    actualizarCuentas();
    pintarAlerta();
    if (llegaron > 0) tocarAviso();

    if (conAvisos) {
      // Respuesta a una acción (ej: "Marcar listo"): trae el botón de WhatsApp, etc.
      const nuevosAvisos = doc.getElementById('avisos');
      if (nuevosAvisos) avisos.innerHTML = nuevosAvisos.innerHTML;
    } else {
      // En una actualización común solo mostramos lo que se canceló solo por vencimiento
      const vencidos = doc.querySelector('#avisos .aviso-vencidos');
      if (vencidos) {
        avisos.querySelectorAll('.aviso-vencidos').forEach(el => el.remove());
        avisos.insertAdjacentHTML('afterbegin', vencidos.outerHTML);
      }
    }
  }

  function mostrarError(texto) {
    const div = document.createElement('div');
    div.className = 'aviso error';
    div.textContent = texto;
    avisos.replaceChildren(div);
  }

  function avisarSesionVencida() {
    sesionVencida = true;
    estadoVivo.innerHTML = '⚠ Tu sesión venció. <a href="login.php">Ingresá de nuevo</a> para seguir viendo los pedidos.';
    estadoVivo.classList.add('sin-conexion');
  }

  async function actualizar() {
    if (consultando || sesionVencida) return;
    consultando = true;
    try {
      aplicarPagina(await traerPagina('pedidos.php'), false);
      fallos = 0;
      ultimaActualizacion = Date.now();
    } catch (err) {
      if (err.message === 'SESION_VENCIDA') avisarSesionVencida();
      else fallos++;
    } finally {
      consultando = false;
      pintarEstado();
    }
  }

  function pintarEstado() {
    if (sesionVencida) return;
    if (fallos >= 2) {
      estadoVivo.textContent = '⚠ Sin conexión: los pedidos no se están actualizando';
      estadoVivo.classList.add('sin-conexion');
      return;
    }
    estadoVivo.classList.remove('sin-conexion');
    const seg = Math.floor((Date.now() - ultimaActualizacion) / 1000);
    estadoVivo.textContent = seg < 5
      ? 'Actualizado recién · se actualiza solo'
      : `Actualizado hace ${seg < 60 ? seg + ' s' : Math.floor(seg / 60) + ' min'} · se actualiza solo`;
  }

  setInterval(actualizar, INTERVALO_MS);
  setInterval(pintarEstado, 1000);

  // Si la pestaña estuvo en segundo plano, el navegador puede haber espaciado las consultas:
  // al volver, se actualiza en el momento.
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) actualizar();
  });

  // ----- Botones de cada pedido, sin recargar la página -----
  lista.addEventListener('submit', async (e) => {
    const form = e.target;
    if (e.defaultPrevented || !form.matches('form[action="cambiar_estado.php"]')) return; // la cancelación pide confirmar antes
    e.preventDefault();

    const boton = form.querySelector('button');
    boton.disabled = true;

    let doc;
    try {
      doc = await traerPagina(form.getAttribute('action'), {
        method: 'POST',
        body: new URLSearchParams(new FormData(form)),
      });
    } catch (err) {
      if (err.message === 'SESION_VENCIDA') {
        window.location.href = 'login.php';   // la acción no se ejecutó: hay que ingresar de nuevo
        return;
      }
      boton.disabled = false;
      mostrarError('No se pudo conectar con el servidor. Revisá la conexión y probá de nuevo.');
      return;
    }

    try {
      aplicarPagina(doc, true);
      ultimaActualizacion = Date.now();
      fallos = 0;
      pintarEstado();
    } catch (err) {
      // El servidor respondió otra cosa. Se recarga para ver cómo quedó el pedido en realidad,
      // y a propósito NO se reenvía la acción (avanzar dos veces saltearía un paso).
      window.location.reload();
    }
  });
</script>

</body>
</html>
