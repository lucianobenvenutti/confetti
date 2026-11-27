<?php
require 'conexion.php';
require __DIR__ . '/auth.php';
$usuarioActual = exigirSesion(ROLES_TODOS);
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
<title>Confetti — Mostrador</title>
<style>
  :root{
    --paper:#FBF2E4; --paper-dim:#F4E4CC; --ink:#3F2B1F; --ink-soft:#7A614E;
    --rojo:#A8601F; --alerta:#C8402F; --verde:#5F8A4B; --linea:rgba(63,43,31,0.12);
  }
  *{ box-sizing:border-box; }
  body{
    margin:0;
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
    background:var(--paper);
    color:var(--ink);
  }
  .layout{
    display:grid;
    grid-template-columns:1fr clamp(440px, 36vw, 600px);   /* panel de venta ancho */
    min-height:100vh;
  }
  .panel-productos{ padding:24px; overflow-y:auto; }
  .panel-venta{
    background:#fff;
    border-left:1px solid var(--linea);
    padding:24px;
    display:flex;
    flex-direction:column;
    position:sticky;
    top:0;
    height:100vh;
  }
  h1{ font-size:1.4rem; margin:0 0 4px; }
  p.sub{ color:var(--ink-soft); margin:0 0 18px; font-size:0.9rem; }

  .buscador-row{ margin-bottom:14px; }
  .buscador-row input{
    width:100%;
    padding:12px 14px;
    border-radius:10px;
    border:1px solid var(--linea);
    font-family:inherit;
    font-size:0.95rem;
  }
  .buscador-row input:focus{ outline:2px solid var(--rojo); outline-offset:1px; }

  .chip-row{ display:flex; gap:8px; overflow-x:auto; margin-bottom:18px; padding-bottom:4px; }
  .chip{
    flex:none; padding:8px 16px; border-radius:12px; border:2px solid var(--linea);
    background:#fff; font-size:0.85rem; font-weight:600; cursor:pointer; white-space:nowrap;
  }

  .grid{
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(150px, 1fr));
    gap:8px;
    align-items:start;   /* cada tarjeta mide lo que necesita, sin estirarse */
  }
  .card{
    background:#fff;
    border:1px solid var(--linea);
    border-radius:10px;
    padding:6px 8px 7px;
    cursor:pointer;
    text-align:left;
    display:flex;
    flex-direction:column;
    gap:2px;
    min-width:0;
  }
  .card:hover{ border-color:var(--rojo); }
  .card:disabled{ opacity:.45; cursor:not-allowed; }
  .card.con-foto{ flex-direction:row; align-items:flex-start; gap:8px; }
  .card .textos-card{ display:flex; flex-direction:column; gap:2px; min-width:0; flex:1; }
  .card .mini-foto{
    flex:none; width:42px; height:42px; object-fit:cover; border-radius:7px;
    background:var(--paper-dim);
  }
  .card .nombre{ font-weight:600; font-size:0.86rem; line-height:1.2; overflow-wrap:anywhere; }
  .card .desc{
    color:var(--ink-soft); font-size:0.74rem; line-height:1.25; overflow-wrap:anywhere;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
  }
  .card .precio{ color:var(--ink-soft); font-size:0.8rem; margin-top:1px; }
  .tag-oferta{ background:#FFF1DC; color:#B35F00; font-weight:800; font-size:0.62rem; padding:1px 6px; border-radius:6px; letter-spacing:.03em; }
  .card.stock-bajo{ background:#FBEAE3; border-color:#F0CBBF; }
  .card .badge-bajo{
    align-self:flex-start;
    background:var(--alerta);
    color:#fff;
    font-size:0.65rem;
    font-weight:700;
    padding:2px 7px;
    border-radius:6px;
  }

  .lista-venta{ flex:1; overflow-y:auto; margin:8px 0; }
  .item-venta{
    display:flex; justify-content:space-between; align-items:center;
    padding:5px 0; border-bottom:1px solid var(--linea); font-size:0.9rem; gap:8px;
  }
  .item-venta .info{ display:flex; align-items:center; gap:8px; min-width:0; flex:1; }
  .item-venta .mini-venta{
    flex:none; width:34px; height:34px; border-radius:8px; object-fit:cover;
    background:var(--paper-dim); border:1px solid var(--linea);
  }
  .item-venta .mini-venta.sin-foto{
    display:flex; align-items:center; justify-content:center; font-size:1.05rem;
  }
  .item-venta .textos{ min-width:0; }
  .item-venta .nombre{ font-weight:600; font-size:0.9rem; line-height:1.2; overflow-wrap:anywhere; }
  .item-venta .desc-venta{ color:var(--ink-soft); font-size:0.72rem; line-height:1.15; overflow-wrap:anywhere; }
  .item-venta .detalle{ color:var(--ink-soft); font-size:0.8rem; margin-top:0; }
  .item-venta .btn-quitar{
    border:none; background:none; color:var(--alerta); font-size:1.3rem; line-height:1; cursor:pointer; flex:none; padding:2px 6px;
  }

  /* Cantidad editable de cada producto de la venta: − [número] + */
  .cantidad-ctl{ display:flex; align-items:center; gap:4px; flex:none; }
  .btn-cant{
    width:28px; height:28px; flex:none; border-radius:7px; cursor:pointer;
    border:1px solid var(--linea); background:#fff; color:var(--ink);
    font-size:1.05rem; line-height:1; font-family:inherit; padding:0;
  }
  .btn-cant:hover{ background:var(--paper-dim); }
  .btn-cant:active{ transform:scale(0.94); }
  .btn-cant:disabled{ opacity:0.35; cursor:default; }
  .input-cant{
    width:52px; height:28px; box-sizing:border-box; text-align:center;
    border:1px solid var(--linea); border-radius:7px; font-size:0.9rem; font-weight:600;
    font-family:inherit; color:var(--ink); background:#fff; -moz-appearance:textfield;
  }
  .input-cant::-webkit-outer-spin-button, .input-cant::-webkit-inner-spin-button{ -webkit-appearance:none; margin:0; }
  .input-cant:focus{ outline:2px solid var(--azul, #6B4026); outline-offset:1px; }
  .unidad-cant{ color:var(--ink-soft); font-size:0.85rem; margin-right:2px; }
  .aviso-cant{ color:var(--alerta); font-size:0.75rem; margin-top:2px; }

  /* En pantallas chicas (celular / tablet vertical) el panel de venta pasa abajo */
  @media (max-width: 900px){
    .layout{ grid-template-columns:1fr; }
    /* Celular: el panel de venta queda fijo abajo. Cerrado = solo título, total y "Registrar venta";
       al tocar la barra se abre y muestra la lista, descuento, pago, etc. */
    .panel-productos{ padding-bottom:150px; }
    .panel-venta{
      position:fixed; left:0; right:0; bottom:0; top:auto; height:auto; max-height:88vh;
      border-left:none; border-top:1px solid var(--linea); padding:10px 14px 12px;
      box-shadow:0 -4px 16px rgba(60,35,15,.18); z-index:40; overflow:hidden;
    }
    .venta-cab{ cursor:pointer; display:flex; align-items:center; gap:8px; padding:2px 0 6px; }
    .cab-resumen{ margin-left:auto; font-weight:700; font-size:0.95rem; }
    .cab-flecha{ font-size:0.8rem; color:var(--ink-soft); transition:transform .2s; }
    .panel-venta.abierto .cab-flecha{ transform:rotate(180deg); }
    .panel-venta:not(.abierto) .lista-venta,
    .panel-venta:not(.abierto) .acciones-secundarias,
    .panel-venta:not(.abierto) .cuenta-info,
    .panel-venta:not(.abierto) .resumen,
    .panel-venta:not(.abierto) .total-row{ display:none; }
    .panel-venta.abierto .lista-venta{ min-height:80px; max-height:none; }
    .panel-venta.abierto .cab-resumen{ display:none; }
    body.panel-abierto{ overflow:hidden; }
  }
  @media (min-width: 901px){ .cab-resumen, .cab-flecha{ display:none; } .venta-cab{ display:block; } }
  .vacio{ color:var(--ink-soft); font-size:0.9rem; }

  .acciones-secundarias{
    display:flex; gap:6px; margin-bottom:8px;
  }
  .btn-secundario{
    flex:1;
    padding:6px 8px;
    border-radius:8px;
    border:1px solid var(--linea);
    background:#fff;
    font-size:0.82rem;
    font-weight:600;
    cursor:pointer;
  }
  .btn-secundario.activo{ border-color:var(--rojo); color:var(--rojo); }
  .btn-secundario:disabled{ opacity:.4; cursor:not-allowed; }
  .acciones-secundarias{ flex-wrap:wrap; }
  .btn-cuenta{ flex:1 1 100%; }

  .cuenta-info{
    font-size:0.85rem; background:#EFE3D6; color:#6B4B30; border-radius:10px;
    padding:9px 12px; margin-bottom:10px; display:none;
  }
  .cuenta-info button{ border:none; background:none; color:#6B4B30; text-decoration:underline; cursor:pointer; font-size:0.78rem; margin-left:6px; }

  /* Ventana para elegir / crear la cuenta */
  .modal-cuentas{
    position:fixed; inset:0; background:rgba(63,43,31,0.45); z-index:60;
    display:none; align-items:center; justify-content:center; padding:20px;
  }
  .modal-cuentas.visible{ display:flex; }
  .modal-cuentas .tarjeta{
    background:#fff; border-radius:16px; padding:22px; width:100%; max-width:440px;
    max-height:90vh; display:flex; flex-direction:column; gap:12px;
  }
  .modal-cuentas h2{ font-size:1.1rem; margin:0; }
  .modal-cuentas input[type="text"]{
    width:100%; padding:10px 12px; border-radius:9px; border:1px solid var(--linea);
    font-family:inherit; font-size:0.92rem;
  }
  .lista-cuentas{ overflow-y:auto; max-height:240px; display:flex; flex-direction:column; gap:6px; }
  .fila-cuenta{
    display:flex; justify-content:space-between; align-items:center; gap:10px; text-align:left;
    border:1px solid var(--linea); background:#fff; border-radius:10px; padding:10px 12px; cursor:pointer;
    font-family:inherit; font-size:0.9rem;
  }
  .fila-cuenta:hover{ border-color:#8A6240; }
  .fila-cuenta small{ display:block; color:var(--ink-soft); font-size:0.75rem; }
  .fila-cuenta .saldo{ font-size:0.8rem; font-weight:700; white-space:nowrap; }
  .saldo.debe{ color:var(--alerta); }
  .saldo.favor{ color:var(--verde); }
  .saldo.cero{ color:var(--ink-soft); font-weight:500; }
  .nueva-cuenta{ border-top:1px solid var(--linea); padding-top:12px; display:flex; flex-direction:column; gap:8px; }
  .nueva-cuenta h3{ font-size:0.85rem; margin:0; color:var(--ink-soft); }
  .nueva-cuenta .fila-inputs{ display:flex; gap:8px; }
  .modal-cuentas .btn-crear{
    border:none; background:#8A6240; color:#fff; font-weight:600; border-radius:9px;
    padding:10px 14px; cursor:pointer; font-family:inherit; font-size:0.88rem;
  }
  .modal-cuentas .btn-cerrar{
    border:1px solid var(--linea); background:#fff; border-radius:9px; padding:9px;
    cursor:pointer; font-family:inherit; font-size:0.88rem;
  }
  .picker-error{ color:var(--alerta); font-size:0.82rem; display:none; }

  /* Ventana de descuento */
  .desc-fila{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
  .desc-fila label.op{
    display:flex; align-items:center; gap:6px; border:1px solid var(--linea); border-radius:10px;
    padding:9px 12px; cursor:pointer; font-size:0.9rem; background:#fff; flex:1; min-width:120px;
  }
  .desc-fila label.op:has(input:checked){ border-color:var(--rojo); background:#FBF2E4; font-weight:600; }
  .desc-fila input[type="number"]{
    width:130px; padding:10px 12px; border-radius:9px; border:1px solid var(--linea); font-family:inherit; font-size:1rem;
  }
  .desc-subtitulo{ font-size:0.8rem; color:var(--ink-soft); margin:2px 0 -4px; font-weight:600; }
  .desc-lista{ overflow-y:auto; max-height:220px; display:flex; flex-direction:column; gap:6px; }
  .desc-item{
    display:flex; align-items:center; gap:10px; border:1px solid var(--linea); border-radius:10px;
    padding:8px 12px; cursor:pointer; font-size:0.88rem; background:#fff;
  }
  .desc-item:has(input:checked){ border-color:var(--rojo); background:#FBF2E4; }
  .desc-item input{ width:18px; height:18px; flex:none; }
  .desc-item .txt{ flex:1; min-width:0; }
  .desc-item small{ display:block; color:var(--ink-soft); font-size:0.74rem; }
  .desc-item .monto{ font-weight:700; white-space:nowrap; }
  .desc-atajos{ display:flex; gap:12px; font-size:0.8rem; }
  .desc-atajos button{ border:none; background:none; color:#6B4B30; text-decoration:underline; cursor:pointer; padding:0; font-family:inherit; }
  .desc-vista{ background:#F4E4CC; border-radius:10px; padding:10px 12px; font-size:0.9rem; min-height:20px; }
  .desc-botones{ display:flex; gap:8px; }
  .desc-botones .btn-aplicar{
    flex:1; border:none; background:var(--rojo); color:#fff; font-weight:700; border-radius:10px;
    padding:12px; cursor:pointer; font-family:inherit; font-size:0.95rem;
  }
  .desc-botones .btn-aplicar:disabled{ opacity:.4; cursor:not-allowed; }
  .desc-botones .btn-quitar-desc{
    border:1px solid var(--alerta); color:var(--alerta); background:#fff; border-radius:10px;
    padding:12px 14px; cursor:pointer; font-family:inherit; font-size:0.9rem;
  }

  .resumen{ font-size:0.85rem; margin-bottom:4px; }
  .resumen .fila{ display:flex; justify-content:space-between; padding:1px 0; }
  .resumen .fila.descuento{ color:var(--alerta); }
  .resumen .fila button{ border:none; background:none; color:var(--ink-soft); font-size:0.75rem; cursor:pointer; text-decoration:underline; margin-left:6px; }

  .total-row{
    display:flex; justify-content:space-between; font-weight:700; font-size:1.2rem;
    padding-top:6px; border-top:1px solid var(--linea); margin-bottom:6px;
  }
  .vuelto-info{
    font-size:0.9rem; padding:10px 12px; border-radius:10px; margin-bottom:12px;
  }
  .vuelto-info.ok{ background:#EDF3E4; color:var(--verde); }
  .vuelto-info.falta{ background:#FBEAE3; color:var(--alerta); }

  button.grande{
    width:100%; padding:11px; border:none; border-radius:10px;
    background:var(--rojo); color:#fff; font-weight:700; font-size:1rem; cursor:pointer;
  }
  button.grande:hover{ background:#8A4F1C; }
  button.grande:disabled{ background:#ccc; cursor:not-allowed; }

  .confirmacion{ text-align:center; padding:30px 10px; }
  .confirmacion .check{ font-size:2.4rem; color:var(--verde); }
  .confirmacion .codigo{ font-size:1.3rem; font-weight:700; margin:8px 0; }
  .confirmacion .monto{ color:var(--ink-soft); margin-bottom:6px; }
  .confirmacion .detalle-pago{ color:var(--ink-soft); font-size:0.85rem; margin-bottom:18px; }

  .aviso-error{
    background:#FBEAE3; color:var(--alerta); border:1px solid #F0CBBF;
    border-radius:10px; padding:10px 12px; font-size:0.85rem; margin-bottom:12px; display:none;
  }

  /* Aviso de pedidos web (llegan mientras se atiende el mostrador) */
  .aviso-web{
    display:none; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
    background:#FFF1DC; border:1px solid #F5C98A; color:#8a4b00; border-radius:12px;
    padding:10px 14px; margin-bottom:12px; font-weight:600; font-size:0.92rem;
  }
  .aviso-web.visible{ display:flex; }
  .aviso-web.pulso{ animation:pulsoWeb 1.2s ease-in-out 4; }
  @keyframes pulsoWeb{ 50%{ box-shadow:0 0 0 6px rgba(150,112,15,0.35); } }
  .aviso-web a{
    background:#96700F; color:#fff; text-decoration:none; font-weight:700; font-size:0.85rem;
    padding:8px 14px; border-radius:9px; white-space:nowrap;
  }
  .aviso-web small{ display:block; font-weight:500; opacity:0.85; margin-top:2px; }
  .chip-sonido{
    float:right; border:1px solid #E0D2BF; background:#fff; color:#7A614E; border-radius:999px;
    padding:3px 10px; font-size:0.78rem; cursor:pointer; font-family:inherit;
  }
  .chip-sonido.activo{ background:#EDF3E4; border-color:#D3E3C2; color:#5F8A4B; }
  .toast-web{
    position:fixed; top:16px; right:16px; z-index:60; max-width:340px; display:none;
    background:#fff; border-left:6px solid #96700F; border-radius:12px; padding:14px 16px;
    box-shadow:0 10px 30px rgba(63,43,31,0.25); font-size:0.92rem;
  }
  .toast-web.visible{ display:block; animation:entraToast .35s ease-out; }
  @keyframes entraToast{ from{ transform:translateY(-14px); opacity:0; } }
  .toast-web strong{ display:block; font-size:1rem; margin-bottom:2px; }
  .toast-web .toast-botones{ display:flex; gap:8px; margin-top:10px; }
  .toast-web a, .toast-web button{
    font:inherit; font-weight:700; font-size:0.85rem; border-radius:9px; padding:8px 12px;
    border:0; cursor:pointer; text-decoration:none;
  }
  .toast-web a{ background:#96700F; color:#fff; }
  .toast-web button{ background:#F1E6D6; color:#3F2B1F; }
</style>
</head>
<body>

<div class="layout">
  <div class="panel-productos">
    <div style="overflow:hidden; font-size:0.85rem; color:#7A614E; margin-bottom:12px;">
      <a href="index.php" style="color:#7A614E; text-decoration:none;">← Panel</a> <?= barraSesionHtml() ?>
      <button type="button" class="chip-sonido" id="chip-sonido" title="Sonido cuando llega un pedido web">🔕 Sonido apagado</button>
    </div>
    <div class="aviso-web" id="aviso-web" role="status" aria-live="polite">
      <div><span id="aviso-web-texto"></span><small id="aviso-web-detalle"></small></div>
      <a href="pedidos.php" target="_blank" rel="noopener">Ver pedidos web</a>
    </div>
    <h1>Mostrador rápido</h1>
    <p class="sub">Tocá un producto para sumarlo a la venta actual, o buscalo por nombre.</p>
    <div class="buscador-row">
      <input type="text" id="input-buscar" placeholder="Buscar por nombre o escanear código de barras…" autocomplete="off">
    </div>
    <div class="chip-row" id="chip-row"></div>
    <div class="grid" id="grid-productos"></div>
  </div>

  <div class="panel-venta" id="panel-venta">
    <div class="venta-cab" id="venta-cab" role="button" tabindex="0" aria-expanded="false">
      <h1 style="font-size:1.15rem;margin:0;">Venta actual</h1>
      <span class="cab-resumen" id="cab-resumen"></span>
      <span class="cab-flecha" aria-hidden="true">▲</span>
    </div>
    <div class="aviso-error" id="aviso-error"></div>
    <div class="lista-venta" id="lista-venta">
      <p class="vacio">Todavía no agregaste nada.</p>
    </div>

    <div class="acciones-secundarias">
      <button class="btn-secundario" id="btn-descuento">Descuento</button>
      <button class="btn-secundario" id="btn-pago">Pago con...</button>
      <button class="btn-secundario btn-cuenta" id="btn-cuenta">Cuenta corriente</button>
    </div>

    <div class="cuenta-info" id="cuenta-info"></div>

    <div class="resumen" id="resumen"></div>
    <div class="total-row"><span>Total</span><span id="total-venta">$0</span></div>
    <div id="vuelto-info"></div>

    <button class="grande" id="btn-registrar" disabled>Registrar venta</button>
  </div>
</div>

<div class="toast-web" id="toast-web" role="alert">
  <strong id="toast-web-titulo">Llegó un pedido web</strong>
  <span id="toast-web-detalle"></span>
  <div class="toast-botones">
    <a href="pedidos.php" target="_blank" rel="noopener" id="toast-web-ver">Ver en Pedidos web</a>
    <button type="button" id="toast-web-cerrar">Cerrar</button>
  </div>
</div>

<div class="modal-cuentas" id="modal-descuento">
  <div class="tarjeta">
    <h2>Descuento</h2>

    <div class="desc-fila">
      <label class="op"><input type="radio" name="desc-tipo" value="porcentaje" checked> % Porcentaje</label>
      <label class="op"><input type="radio" name="desc-tipo" value="monto"> $ Monto fijo</label>
    </div>
    <div class="desc-fila">
      <input type="number" id="desc-valor" min="0" step="any" inputmode="decimal" placeholder="Ej: 10">
      <span id="desc-unidad" style="color:var(--ink-soft);">% de descuento</span>
    </div>

    <div class="desc-subtitulo">¿Sobre qué lo aplico?</div>
    <div class="desc-fila">
      <label class="op"><input type="radio" name="desc-alcance" value="todos" checked> A toda la venta</label>
      <label class="op"><input type="radio" name="desc-alcance" value="seleccion"> Solo a algunos productos</label>
    </div>

    <div id="desc-seleccion" style="display:none; flex-direction:column; gap:8px;">
      <div class="desc-atajos"><button type="button" id="desc-todos">Marcar todos</button><button type="button" id="desc-ninguno">Desmarcar todos</button></div>
      <div class="desc-lista" id="desc-lista"></div>
    </div>

    <div class="desc-vista" id="desc-vista"></div>

    <div class="desc-botones">
      <button type="button" class="btn-quitar-desc" id="desc-quitar" style="display:none;">Quitar descuento</button>
      <button type="button" class="btn-aplicar" id="desc-aplicar" disabled>Aplicar descuento</button>
    </div>
    <button type="button" class="btn-cerrar" id="desc-cancelar">Cancelar</button>
  </div>
</div>

<div class="modal-cuentas" id="modal-cuentas">
  <div class="tarjeta">
    <h2>Cargar a cuenta corriente</h2>
    <input type="text" id="buscar-cuenta" placeholder="Buscar por nombre o teléfono…" autocomplete="off">
    <div class="lista-cuentas" id="lista-cuentas"></div>

    <div class="nueva-cuenta">
      <h3>¿No está? Creá una cuenta nueva</h3>
      <div class="fila-inputs">
        <input type="text" id="nueva-cuenta-nombre" placeholder="Nombre">
        <input type="text" id="nueva-cuenta-telefono" placeholder="Teléfono (opcional)">
      </div>
      <button type="button" class="btn-crear" id="btn-crear-cuenta">Crear y usar</button>
      <div class="picker-error" id="picker-error"></div>
    </div>

    <button type="button" class="btn-cerrar" id="btn-cerrar-cuentas">Cancelar</button>
  </div>
</div>

<script>
  const API = '../api/';
  let productosCache = [];
  let categoriaActiva = null;
  let venta = []; // { tipo, id, nombre, precio, tipo_venta, cantidad }
  let descuento = null;   // { tipo: 'porcentaje'|'monto', valor: number, alcance: 'todos'|'seleccion', claves: ['producto:5', ...] }
  let montoPagado = null; // number o null
  let cuentaSeleccionada = null; // { id, nombre, saldo } si la venta va a cuenta corriente
  let cuentasCache = [];

  // Escapa texto que se mete dentro de innerHTML (nombres cargados a mano)
  const esc = s => String(s ?? '').replace(/[&<>"']/g,
    c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function textoBotonRegistrar() {
    return cuentaSeleccionada ? `Cargar a la cuenta de ${cuentaSeleccionada.nombre}` : 'Registrar venta';
  }

  const formatoPrecio = new Intl.NumberFormat('es-AR', {
    style: 'currency', currency: 'ARS', maximumFractionDigits: 0,
  });

  async function pedirJSON(url) {
    const res = await fetch(url);
    return res.json();
  }

  async function cargarCategorias() {
    const categorias = await pedirJSON(API + 'categorias.php');
    const cont = document.getElementById('chip-row');
    cont.innerHTML = '';

    const chipTodos = document.createElement('div');
    chipTodos.className = 'chip';
    chipTodos.textContent = 'Todo';
    chipTodos.style.borderColor = 'var(--ink)';
    chipTodos.addEventListener('click', () => { categoriaActiva = null; marcarChip(chipTodos); renderGrid(); });
    cont.appendChild(chipTodos);

    categorias.forEach(cat => {
      const chip = document.createElement('div');
      chip.className = 'chip';
      chip.textContent = cat.nombre;
      chip.addEventListener('click', () => { categoriaActiva = cat.id; marcarChip(chip); renderGrid(); });
      cont.appendChild(chip);
    });
  }

  function marcarChip(activo) {
    document.querySelectorAll('#chip-row .chip').forEach(c => c.style.borderColor = 'var(--linea)');
    activo.style.borderColor = 'var(--ink)';
  }

  function bloqueMiniFoto(p) {
    if (p.imagen) {
      return `<img class="mini-foto" src="../assets/productos/${encodeURIComponent(p.imagen)}" alt=""
                onerror="this.style.display='none'">`;
    }
    return '';
  }

  // Trae TODOS los productos una sola vez. El filtrado por categoría y por
  // búsqueda se hace después en el navegador, así no hay que ir al servidor
  // cada vez que se toca un chip o se escribe una letra.
  async function cargarProductos() {
    const datos = await pedirJSON('productos_mostrador.php');
    if (!Array.isArray(datos)) {          // sesión vencida u otro error: volver al login
      location.href = 'login.php';
      return;
    }
    productosCache = datos;
    renderGrid();
  }

  function productosFiltrados() {
    const busqueda = document.getElementById('input-buscar').value.trim().toLowerCase();
    return productosCache.filter(p => {
      const pasaCategoria = !categoriaActiva || p.categoria_id === categoriaActiva;
      const pasaBusqueda = !busqueda
        || p.nombre.toLowerCase().includes(busqueda)
        || (p.descripcion && p.descripcion.toLowerCase().includes(busqueda))
        || (p.codigo_barras && p.codigo_barras.toLowerCase().includes(busqueda));
      return pasaCategoria && pasaBusqueda;
    });
  }

  function renderGrid() {
    const cont = document.getElementById('grid-productos');
    const resultados = productosFiltrados();
    cont.innerHTML = '';

    if (!resultados.length) {
      cont.innerHTML = '<p class="vacio">No encontramos ningún producto con ese nombre.</p>';
      return;
    }

    resultados.forEach(p => {
      const btn = document.createElement('button');
      const stockBajo = p.disponible && parseFloat(p.stock_actual) <= parseFloat(p.stock_minimo);
      btn.className = 'card' + (stockBajo ? ' stock-bajo' : '') + (p.imagen ? ' con-foto' : '');
      btn.disabled = !p.disponible;
      const sufijo = p.tipo_venta === 'peso' ? '/kg' : '';
      const precio = p.en_oferta
        ? `<s style="opacity:.6">${formatoPrecio.format(p.precio_normal)}</s> <strong style="color:var(--alerta)">${formatoPrecio.format(p.precio)}${sufijo}</strong> <span class="tag-oferta">OFERTA</span>`
        : `${formatoPrecio.format(p.precio)}${sufijo}`;
      const desc = (p.descripcion || '').trim();
      btn.title = p.nombre + (desc ? ' — ' + desc : '');   // al dejar el mouse encima se ve completa
      btn.innerHTML = `
        ${bloqueMiniFoto(p)}
        <span class="textos-card">
          ${stockBajo ? '<span class="badge-bajo">Stock bajo</span>' : ''}
          <span class="nombre">${esc(p.nombre)}</span>
          ${desc ? `<span class="desc">${esc(desc)}</span>` : ''}
          <span class="precio">${precio}${p.disponible ? '' : ' — sin stock'}</span>
        </span>
      `;
      btn.addEventListener('click', () => agregarProducto(p));
      cont.appendChild(btn);
    });
  }

  const inputBuscar = document.getElementById('input-buscar');

  inputBuscar.addEventListener('input', renderGrid);

  // Un lector de código de barras "tipea" el código rapidísimo y manda Enter solo.
  // Por eso, ante un Enter, primero probamos si el texto matchea EXACTO un código
  // de barras (sin importar la categoría elegida) antes de caer al buscador por nombre.
  inputBuscar.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
    const texto = inputBuscar.value.trim();

    const porCodigo = productosCache.find(p => p.codigo_barras && p.codigo_barras === texto);
    if (porCodigo) {
      if (porCodigo.disponible) {
        agregarProducto(porCodigo);
        inputBuscar.value = '';
        renderGrid();
      } else {
        alert(`"${porCodigo.nombre}" está sin stock.`);
      }
      return;
    }

    const resultados = productosFiltrados();
    if (resultados.length === 1 && resultados[0].disponible) {
      agregarProducto(resultados[0]);
      inputBuscar.value = '';
      renderGrid();
    }
  });

  function agregarProducto(p) {
    if (p.tipo_venta === 'peso') {
      const gramosTexto = prompt(`¿Cuántos gramos de "${p.nombre}"?`, '250');
      const gramos = parseFloat(gramosTexto);
      if (!gramos || gramos <= 0) return;
      venta.push({
        tipo: 'producto', id: p.id, nombre: p.nombre, descripcion: p.descripcion, imagen: p.imagen,
        precio: parseFloat(p.precio), tipo_venta: 'peso', cantidad: gramos / 1000,
      });
    } else {
      const existente = venta.find(i => i.tipo === 'producto' && i.id === p.id);
      if (existente) {
        fijarCantidad(venta.indexOf(existente), existente.cantidad + 1);
        return;
      } else {
        venta.push({
          tipo: 'producto', id: p.id, nombre: p.nombre, descripcion: p.descripcion, imagen: p.imagen,
          precio: parseFloat(p.precio), tipo_venta: 'unidad', cantidad: 1,
        });
      }
    }
    renderVenta();
  }

  function quitarItem(index) {
    venta.splice(index, 1);
    renderVenta();
  }

  // Cambia la cantidad de una línea de la venta. Unidades: número entero; peso: gramos.
  // Nunca deja pasar más de lo que hay en stock (el servidor igual lo vuelve a controlar al registrar).
  function fijarCantidad(index, valor) {
    const item = venta[index];
    if (!item) return;
    const esPeso = item.tipo_venta === 'peso';
    const minimo = esPeso ? 10 : 1;             // 10 g / 1 unidad
    const tope = esPeso ? 100000 : 9999;
    item.aviso = '';

    if (!isFinite(valor)) { renderVenta(); return; }     // campo vacío o inválido: queda como estaba
    valor = esPeso ? Math.round(valor) : Math.floor(valor);
    if (valor < minimo) valor = minimo;
    if (valor > tope) valor = tope;

    const p = productosCache.find(x => x.id === item.id);
    if (p && p.stock_actual !== undefined && p.stock_actual !== null) {
      const stock = parseFloat(p.stock_actual);
      const maxDisponible = esPeso ? Math.floor(stock * 1000) : Math.floor(stock);
      if (valor > maxDisponible) {
        valor = Math.max(minimo, maxDisponible);
        item.aviso = esPeso ? `Solo hay ${maxDisponible} g en stock` : `Solo hay ${maxDisponible} en stock`;
      }
    }
    item.cantidad = esPeso ? valor / 1000 : valor;
    renderVenta();
  }

  function etiquetaCantidad(item) {
    if (item.tipo_venta === 'peso') {
      return item.cantidad < 1 ? `${Math.round(item.cantidad * 1000)}g` : `${item.cantidad}kg`;
    }
    return `x${item.cantidad}`;
  }

  function calcularSubtotal() {
    return venta.reduce((acc, i) => acc + i.precio * i.cantidad, 0);
  }

  // Un renglón por producto (aunque se haya cargado en varias tandas de peso)
  function gruposVenta() {
    const m = new Map();
    venta.forEach(i => {
      const clave = `${i.tipo}:${i.id}`;
      const g = m.get(clave) || { clave, nombre: i.nombre, descripcion: i.descripcion || '', subtotal: 0 };
      g.subtotal += i.precio * i.cantidad;
      m.set(clave, g);
    });
    return [...m.values()];
  }

  // Sobre cuánta plata se aplica el descuento: toda la venta o solo lo elegido
  function baseDescuento(subtotal, d = descuento) {
    if (!d) return 0;
    if (d.alcance !== 'seleccion') return subtotal;
    const elegidas = new Set(d.claves || []);
    return gruposVenta().filter(g => elegidas.has(g.clave)).reduce((a, g) => a + g.subtotal, 0);
  }

  function montoDescuentoDe(d, subtotal) {
    if (!d) return 0;
    const base = baseDescuento(subtotal, d);
    if (d.tipo === 'porcentaje') return base * Math.min(Math.max(d.valor, 0), 100) / 100;
    return Math.min(Math.max(d.valor, 0), base);
  }

  function calcularMontoDescuento(subtotal) {
    return montoDescuentoDe(descuento, subtotal);
  }

  function calcularTotal() {
    const subtotal = calcularSubtotal();
    return Math.max(0, subtotal - calcularMontoDescuento(subtotal));
  }

  function renderVenta() {
    const cont = document.getElementById('lista-venta');
    const btnRegistrar = document.getElementById('btn-registrar');

    if (!venta.length) {
      cont.innerHTML = '<p class="vacio">Todavía no agregaste nada.</p>';
    } else {
      cont.innerHTML = '';
      venta.forEach((item, index) => {
        const div = document.createElement('div');
        div.className = 'item-venta';
        const foto = item.imagen
          ? `<img class="mini-venta" src="../assets/productos/${encodeURIComponent(item.imagen)}" alt=""
                  onerror="this.outerHTML='<div class=&quot;mini-venta sin-foto&quot;>🧁</div>'">`
          : '<div class="mini-venta sin-foto">🧁</div>';
        const esPeso = item.tipo_venta === 'peso';
        const valorMostrado = esPeso ? Math.round(item.cantidad * 1000) : item.cantidad;
        const precioUnit = esPeso ? `${formatoPrecio.format(item.precio)}/kg` : `${formatoPrecio.format(item.precio)} c/u`;
        div.innerHTML = `
          <div class="info">
            ${foto}
            <div class="textos">
              <div class="nombre">${esc(item.nombre)}</div>
              ${item.descripcion ? `<div class="desc-venta">${esc(item.descripcion)}</div>` : ''}
              <div class="detalle">${precioUnit} · <strong>${formatoPrecio.format(item.precio * item.cantidad)}</strong></div>
              ${item.aviso ? `<div class="aviso-cant">${esc(item.aviso)}</div>` : ''}
            </div>
          </div>
          <div class="cantidad-ctl">
            <button type="button" class="btn-cant menos" aria-label="Menos">−</button>
            <input type="number" class="input-cant" inputmode="numeric"
                   min="${esPeso ? 10 : 1}" step="${esPeso ? 50 : 1}" value="${valorMostrado}"
                   aria-label="Cantidad${esPeso ? ' en gramos' : ''}">
            ${esPeso ? '<span class="unidad-cant">g</span>' : ''}
            <button type="button" class="btn-cant mas" aria-label="Más">+</button>
          </div>
          <button type="button" class="btn-quitar" aria-label="Quitar" title="Quitar">×</button>
        `;
        const paso = esPeso ? 50 : 1;
        const input = div.querySelector('.input-cant');
        const actual = () => esPeso ? Math.round(item.cantidad * 1000) : item.cantidad;
        div.querySelector('.btn-cant.menos').addEventListener('click', () => fijarCantidad(index, actual() - paso));
        div.querySelector('.btn-cant.mas').addEventListener('click', () => fijarCantidad(index, actual() + paso));
        if (actual() - paso < (esPeso ? 10 : 1)) div.querySelector('.btn-cant.menos').disabled = true;
        // Escribir el número: se aplica al apretar Enter o al salir del campo
        input.addEventListener('focus', () => input.select());
        input.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') { e.preventDefault(); input.blur(); }
        });
        input.addEventListener('change', () => {
          fijarCantidad(index, parseFloat(input.value));
          document.getElementById('input-buscar').focus();   // listo para escanear el próximo producto
        });
        div.querySelector('.btn-quitar').addEventListener('click', () => quitarItem(index));
        cont.appendChild(div);
      });
    }

    const subtotal = calcularSubtotal();
    const montoDescuento = calcularMontoDescuento(subtotal);
    const total = calcularTotal();

    // Resumen: subtotal + línea de descuento (si hay) — solo si hay algo que desglosar
    const resumenCont = document.getElementById('resumen');
    resumenCont.innerHTML = '';
    if (descuento && montoDescuento > 0) {
      resumenCont.innerHTML = `
        <div class="fila"><span>Subtotal</span><span>${formatoPrecio.format(subtotal)}</span></div>
        <div class="fila descuento">
          <span>Descuento ${descuento.tipo === 'porcentaje' ? `(${descuento.valor}%)` : ''}${textoAlcance()}
            <button id="btn-quitar-descuento">quitar</button>
          </span>
          <span>-${formatoPrecio.format(montoDescuento)}</span>
        </div>
      `;
      document.getElementById('btn-quitar-descuento').addEventListener('click', () => {
        descuento = null;
        renderVenta();
      });
    }

    document.getElementById('total-venta').textContent = formatoPrecio.format(total);
    {
      const unidades = venta.length;
      document.getElementById('cab-resumen').textContent = unidades
        ? `${unidades} ${unidades === 1 ? 'producto' : 'productos'} · ${formatoPrecio.format(total)}`
        : 'Vacía';
    }

    // Vuelto (si se cargó con cuánto paga)
    const vueltoCont = document.getElementById('vuelto-info');
    if (montoPagado !== null && venta.length) {
      const vuelto = montoPagado - total;
      if (vuelto >= 0) {
        vueltoCont.className = 'vuelto-info ok';
        vueltoCont.innerHTML = `Pagó ${formatoPrecio.format(montoPagado)} · Vuelto: <strong>${formatoPrecio.format(vuelto)}</strong>`;
      } else {
        vueltoCont.className = 'vuelto-info falta';
        vueltoCont.innerHTML = `Pagó ${formatoPrecio.format(montoPagado)} · Todavía faltan ${formatoPrecio.format(-vuelto)}`;
      }
    } else {
      vueltoCont.innerHTML = '';
      vueltoCont.className = '';
    }

    document.getElementById('btn-descuento').classList.toggle('activo', !!descuento);
    document.getElementById('btn-pago').classList.toggle('activo', montoPagado !== null);

    // Cuenta corriente: si hay una elegida no tiene sentido calcular vuelto
    const infoCuenta = document.getElementById('cuenta-info');
    document.getElementById('btn-pago').disabled = !!cuentaSeleccionada;
    document.getElementById('btn-cuenta').classList.toggle('activo', !!cuentaSeleccionada);
    if (cuentaSeleccionada) {
      infoCuenta.style.display = 'block';
      infoCuenta.innerHTML = `Se carga a la cuenta de <strong>${esc(cuentaSeleccionada.nombre)}</strong>`
        + `<button type="button" id="btn-quitar-cuenta">quitar</button>`;
      document.getElementById('btn-quitar-cuenta').addEventListener('click', () => {
        cuentaSeleccionada = null;
        renderVenta();
      });
    } else {
      infoCuenta.style.display = 'none';
      infoCuenta.innerHTML = '';
    }

    btnRegistrar.textContent = textoBotonRegistrar();
    btnRegistrar.disabled = venta.length === 0;
  }

  // ---------------- Ventana de descuento ----------------
  function textoAlcance() {
    if (!descuento || descuento.alcance !== 'seleccion') return '';
    const elegidas = new Set(descuento.claves || []);
    const nombres = gruposVenta().filter(g => elegidas.has(g.clave)).map(g => g.nombre);
    if (!nombres.length) return '';
    return ` · solo ${nombres.length === 1 ? esc(nombres[0]) : nombres.length + ' productos'}`;
  }

  const modalDesc = document.getElementById('modal-descuento');
  const radio = (nombre) => document.querySelector(`input[name="${nombre}"]:checked`).value;

  function descuentoDelFormulario() {
    const valor = parseFloat(document.getElementById('desc-valor').value);
    const tipo = radio('desc-tipo');
    const alcance = radio('desc-alcance');
    const claves = alcance === 'seleccion'
      ? [...document.querySelectorAll('#desc-lista input:checked')].map(c => c.value) : [];
    const valido = isFinite(valor) && valor > 0 && (tipo !== 'porcentaje' || valor <= 100)
                   && (alcance !== 'seleccion' || claves.length > 0);
    return { d: { tipo, valor, alcance, claves }, valido };
  }

  function actualizarVistaDescuento() {
    const { d, valido } = descuentoDelFormulario();
    document.getElementById('desc-unidad').textContent = d.tipo === 'porcentaje' ? '% de descuento' : 'pesos de descuento';
    document.getElementById('desc-seleccion').style.display = d.alcance === 'seleccion' ? 'flex' : 'none';
    const vista = document.getElementById('desc-vista');
    const subtotal = calcularSubtotal();
    if (valido) {
      const monto = montoDescuentoDe(d, subtotal);
      vista.innerHTML = `Descuento: <strong>-${formatoPrecio.format(monto)}</strong> · Total quedaría <strong>${formatoPrecio.format(Math.max(0, subtotal - monto))}</strong>`;
    } else if (d.alcance === 'seleccion' && !d.claves.length) {
      vista.textContent = 'Marcá los productos que llevan el descuento.';
    } else if (d.tipo === 'porcentaje' && d.valor > 100) {
      vista.textContent = 'El porcentaje no puede pasar de 100.';
    } else {
      vista.textContent = 'Escribí cuánto descuento querés dar.';
    }
    document.getElementById('desc-aplicar').disabled = !valido || subtotal <= 0;
  }

  function abrirDescuento() {
    const grupos = gruposVenta();
    const previo = descuento;
    const marcadas = new Set(previo && previo.alcance === 'seleccion' ? previo.claves : []);
    document.querySelector(`input[name="desc-tipo"][value="${previo ? previo.tipo : 'porcentaje'}"]`).checked = true;
    document.querySelector(`input[name="desc-alcance"][value="${previo ? previo.alcance : 'todos'}"]`).checked = true;
    document.getElementById('desc-valor').value = previo ? previo.valor : '';
    document.getElementById('desc-quitar').style.display = previo ? '' : 'none';

    const lista = document.getElementById('desc-lista');
    lista.innerHTML = grupos.length ? grupos.map(g => `
      <label class="desc-item">
        <input type="checkbox" value="${esc(g.clave)}" ${marcadas.has(g.clave) ? 'checked' : ''}>
        <span class="txt">${esc(g.nombre)}${g.descripcion ? `<small>${esc(g.descripcion)}</small>` : ''}</span>
        <span class="monto">${formatoPrecio.format(g.subtotal)}</span>
      </label>`).join('') : '<div class="vacio">Todavía no agregaste productos a la venta.</div>';

    modalDesc.classList.add('visible');
    actualizarVistaDescuento();
    document.getElementById('desc-valor').focus();
  }

  function cerrarDescuento() { modalDesc.classList.remove('visible'); }

  function aplicarDescuento() {
    const { d, valido } = descuentoDelFormulario();
    if (!valido) return;
    descuento = d.alcance === 'seleccion' ? d : { tipo: d.tipo, valor: d.valor, alcance: 'todos', claves: [] };
    cerrarDescuento();
    renderVenta();
  }

  document.getElementById('btn-descuento').addEventListener('click', abrirDescuento);
  document.getElementById('desc-cancelar').addEventListener('click', cerrarDescuento);
  document.getElementById('desc-aplicar').addEventListener('click', aplicarDescuento);
  document.getElementById('desc-quitar').addEventListener('click', () => { descuento = null; cerrarDescuento(); renderVenta(); });
  document.getElementById('desc-todos').addEventListener('click', () => { document.querySelectorAll('#desc-lista input').forEach(c => c.checked = true); actualizarVistaDescuento(); });
  document.getElementById('desc-ninguno').addEventListener('click', () => { document.querySelectorAll('#desc-lista input').forEach(c => c.checked = false); actualizarVistaDescuento(); });
  modalDesc.addEventListener('input', actualizarVistaDescuento);
  modalDesc.addEventListener('change', actualizarVistaDescuento);
  modalDesc.addEventListener('click', (e) => { if (e.target === modalDesc) cerrarDescuento(); });
  // Enter en cualquier campo de la ventana (número, opciones, productos) aplica el descuento si está completo
  modalDesc.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && e.target.tagName !== 'BUTTON') { e.preventDefault(); if (!document.getElementById('desc-aplicar').disabled) aplicarDescuento(); }
  });

  document.getElementById('btn-pago').addEventListener('click', () => {
    const texto = prompt('¿Con cuánto paga? Dejalo vacío para sacar el cálculo de vuelto.', montoPagado ?? '');
    if (texto === null) return;

    const limpio = texto.trim();
    if (limpio === '') {
      montoPagado = null;
      renderVenta();
      return;
    }

    const valor = parseFloat(limpio);
    if (!isNaN(valor) && valor > 0) montoPagado = valor;
    renderVenta();
  });

  document.getElementById('btn-registrar').addEventListener('click', registrarVenta);

  async function registrarVenta() {
    const btn = document.getElementById('btn-registrar');
    const errorBox = document.getElementById('aviso-error');
    errorBox.style.display = 'none';
    btn.disabled = true;
    btn.textContent = 'Registrando…';

    try {
      const items = venta.map(i => ({ tipo: i.tipo, id: i.id, cantidad: i.cantidad }));
      const body = { items };
      if (descuento) body.descuento = descuento;
      if (montoPagado !== null) body.monto_pagado = montoPagado;
      if (cuentaSeleccionada) body.cuenta_id = cuentaSeleccionada.id;

      const res = await fetch('registrar_venta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });
      const data = await res.json();

      if (!res.ok) throw new Error(data.error || 'No se pudo registrar la venta.');

      mostrarConfirmacion(data);
      venta = [];
      cargarProductos(); // refresca stock

    } catch (err) {
      errorBox.textContent = err.message;
      errorBox.style.display = 'block';
      btn.disabled = false;
      btn.textContent = textoBotonRegistrar();
    }
  }

  // Celular: abrir / cerrar el panel de venta fijo abajo
  (function () {
    const panel = document.getElementById('panel-venta');
    const cab = document.getElementById('venta-cab');
    if (!panel || !cab) return;
    const alternar = () => {
      const abierto = panel.classList.toggle('abierto');
      cab.setAttribute('aria-expanded', abierto ? 'true' : 'false');
      document.body.classList.toggle('panel-abierto', abierto && window.matchMedia('(max-width: 900px)').matches);
    };
    cab.addEventListener('click', alternar);
    cab.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); alternar(); } });
  })();

  function mostrarConfirmacion(data) {
    const panel = document.getElementById('panel-venta');
    let detallePago = '';
    if (data.descuento_monto > 0) {
      detallePago += `Descuento aplicado: -${formatoPrecio.format(data.descuento_monto)}<br>`;
    }
    if (data.vuelto !== null && data.vuelto !== undefined) {
      detallePago += `Pagó ${formatoPrecio.format(data.monto_pagado)} · Vuelto ${formatoPrecio.format(data.vuelto)}`;
    }
    if (data.cuenta) {
      const saldo = data.cuenta.saldo;
      const textoSaldo = saldo > 0.004 ? `Ahora debe ${formatoPrecio.format(saldo)}`
        : saldo < -0.004 ? `Saldo a favor ${formatoPrecio.format(-saldo)}` : 'Sin deuda';
      detallePago += `Cargado a la cuenta de <strong>${esc(data.cuenta.nombre)}</strong><br>${textoSaldo}`;
    }

    panel.innerHTML = `
      <div class="confirmacion">
        <div class="check">✓</div>
        <div class="codigo">${data.codigo}</div>
        <div class="monto">${data.cuenta ? 'Venta a cuenta corriente por' : 'Venta registrada por'} ${formatoPrecio.format(data.total)}</div>
        <div class="detalle-pago">${detallePago}</div>
        <button class="grande" id="btn-nueva-venta">Nueva venta</button>
      </div>
    `;
    document.getElementById('btn-nueva-venta').addEventListener('click', () => location.reload());
  }

  // =========================================================
  // Cuenta corriente: elegir o crear la cuenta a la que se carga la venta
  // =========================================================
  function etiquetaSaldo(saldo) {
    const n = parseFloat(saldo);
    if (n > 0.004) return { texto: `Debe ${formatoPrecio.format(n)}`, clase: 'debe' };
    if (n < -0.004) return { texto: `A favor ${formatoPrecio.format(-n)}`, clase: 'favor' };
    return { texto: 'Sin deuda', clase: 'cero' };
  }

  async function abrirCuentas() {
    document.getElementById('modal-cuentas').classList.add('visible');
    document.getElementById('buscar-cuenta').value = '';
    document.getElementById('picker-error').style.display = 'none';
    await cargarCuentas();
    document.getElementById('buscar-cuenta').focus();
  }

  function cerrarCuentas() {
    document.getElementById('modal-cuentas').classList.remove('visible');
  }

  async function cargarCuentas() {
    const lista = document.getElementById('lista-cuentas');
    lista.innerHTML = '<p class="vacio">Cargando…</p>';
    try {
      const res = await fetch('cuentas_api.php?accion=listar');
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'No se pudieron cargar las cuentas.');
      cuentasCache = data;
      renderListaCuentas();
    } catch (err) {
      lista.innerHTML = `<p class="vacio">${esc(err.message)}</p>`;
    }
  }

  function renderListaCuentas() {
    const q = document.getElementById('buscar-cuenta').value.trim().toLowerCase();
    const lista = document.getElementById('lista-cuentas');
    const filtradas = cuentasCache.filter(c =>
      !q || c.nombre.toLowerCase().includes(q) || (c.telefono || '').includes(q));

    if (!filtradas.length) {
      lista.innerHTML = '<p class="vacio">No hay cuentas con ese dato. Podés crear una acá abajo.</p>';
      return;
    }

    lista.innerHTML = '';
    filtradas.forEach(c => {
      const s = etiquetaSaldo(c.saldo);
      const fila = document.createElement('button');
      fila.type = 'button';
      fila.className = 'fila-cuenta';
      fila.innerHTML = `
        <span><strong>${esc(c.nombre)}</strong>${c.telefono ? `<small>${esc(c.telefono)}</small>` : ''}</span>
        <span class="saldo ${s.clase}">${s.texto}</span>`;
      fila.addEventListener('click', () => elegirCuenta(c));
      lista.appendChild(fila);
    });
  }

  function elegirCuenta(c) {
    cuentaSeleccionada = { id: c.id, nombre: c.nombre, saldo: parseFloat(c.saldo) };
    montoPagado = null; // fiado: no se calcula vuelto
    cerrarCuentas();
    renderVenta();
  }

  document.getElementById('btn-cuenta').addEventListener('click', abrirCuentas);
  document.getElementById('btn-cerrar-cuentas').addEventListener('click', cerrarCuentas);
  document.getElementById('buscar-cuenta').addEventListener('input', renderListaCuentas);
  document.getElementById('modal-cuentas').addEventListener('click', (e) => {
    if (e.target.id === 'modal-cuentas') cerrarCuentas();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { cerrarCuentas(); cerrarDescuento(); }
  });

  document.getElementById('btn-crear-cuenta').addEventListener('click', async () => {
    const errorBox = document.getElementById('picker-error');
    const nombre = document.getElementById('nueva-cuenta-nombre').value.trim();
    const telefono = document.getElementById('nueva-cuenta-telefono').value.trim();
    errorBox.style.display = 'none';

    if (!nombre) {
      errorBox.textContent = 'Poné al menos el nombre.';
      errorBox.style.display = 'block';
      return;
    }

    try {
      const res = await fetch('cuentas_api.php?accion=crear', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nombre, telefono }),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'No se pudo crear la cuenta.');

      document.getElementById('nueva-cuenta-nombre').value = '';
      document.getElementById('nueva-cuenta-telefono').value = '';
      elegirCuenta(data);
    } catch (err) {
      errorBox.textContent = err.message;
      errorBox.style.display = 'block';
    }
  });

  cargarCategorias();
  cargarProductos();
  renderVenta();
  // =========================================================
  // Aviso de pedidos web: se consulta cada 15 s. Si llega uno nuevo suena, sale un
  // cartel y queda el contador arriba. "Ver pedidos web" abre Pedidos en OTRA pestaña,
  // así la venta que se está armando acá no se pierde.
  // =========================================================
  (function () {
    const INTERVALO = 15000;
    const banner = document.getElementById('aviso-web');
    const bannerTexto = document.getElementById('aviso-web-texto');
    const bannerDetalle = document.getElementById('aviso-web-detalle');
    const toast = document.getElementById('toast-web');
    const chip = document.getElementById('chip-sonido');
    const tituloOriginal = document.title;
    const plata = (n) => '$' + Number(n).toLocaleString('es-AR', { maximumFractionDigits: 0 });
    const txt = (el, t) => { el.textContent = t; };

    let ultimoId = null;        // null = todavía no hubo una primera consulta
    let sesionVencida = false;
    let audioCtx = null;
    let sonidoElegidoApagado = false;
    let cierreToast = null;

    // ----- Sonido -----
    function tocarAviso() {
      if (!audioCtx) return;
      if (audioCtx.state === 'suspended') audioCtx.resume().catch(() => {});
      const t0 = audioCtx.currentTime;
      [[880, 0], [1175, 0.22], [880, 0.55], [1175, 0.77]].forEach(([f, d]) => {
        const osc = audioCtx.createOscillator(), vol = audioCtx.createGain();
        osc.type = 'sine'; osc.frequency.value = f;
        vol.gain.setValueAtTime(0.0001, t0 + d);
        vol.gain.exponentialRampToValueAtTime(0.35, t0 + d + 0.02);
        vol.gain.exponentialRampToValueAtTime(0.0001, t0 + d + 0.2);
        osc.connect(vol); vol.connect(audioCtx.destination);
        osc.start(t0 + d); osc.stop(t0 + d + 0.22);
      });
    }
    // Si alguien lo apagó a propósito, el navegador se acuerda y no lo vuelve a prender solo
    const CLAVE_APAGADO = 'confetti_sonido_apagado';
    function leerApagado() { try { return localStorage.getItem(CLAVE_APAGADO) === '1'; } catch (e) { return false; } }
    function guardarApagado(v) { try { v ? localStorage.setItem(CLAVE_APAGADO, '1') : localStorage.removeItem(CLAVE_APAGADO); } catch (e) {} }

    function pintarChip() {
      chip.classList.toggle('activo', !!audioCtx);
      if (!audioCtx) chip.textContent = '🔕 Sonido apagado';
      else if (audioCtx.state === 'running') chip.textContent = '🔔 Sonido activado';
      else chip.textContent = '🔔 Sonido listo (se activa al tocar la pantalla)';
    }
    function encenderSonido(conMuestra) {
      const Ctx = window.AudioContext || window.webkitAudioContext;
      if (!Ctx || audioCtx) return;
      audioCtx = new Ctx();
      audioCtx.onstatechange = pintarChip;
      audioCtx.resume().catch(() => {});
      pintarChip();
      if (conMuestra) tocarAviso();
    }
    function apagarSonido() {
      if (audioCtx) { audioCtx.close(); audioCtx = null; }
      pintarChip();
    }
    chip.addEventListener('click', (e) => {
      e.stopPropagation();
      if (audioCtx) { sonidoElegidoApagado = true; guardarApagado(true); apagarSonido(); }
      else { sonidoElegidoApagado = false; guardarApagado(false); encenderSonido(true); }
    });
    // El navegador no deja que una página suene hasta que la persona la toca una vez (regla de
    // Chrome/Edge/Firefox, no se puede saltear). Por eso el sonido arranca "listo" al abrir la
    // página y se activa del todo con el primer toque o tecla, que en el mostrador es inmediato.
    function activarConPrimerToque() {
      if (!sonidoElegidoApagado) {
        if (audioCtx) audioCtx.resume().catch(() => {}); else encenderSonido(false);
      }
      document.removeEventListener('pointerdown', activarConPrimerToque, true);
      document.removeEventListener('keydown', activarConPrimerToque, true);
    }
    document.addEventListener('pointerdown', activarConPrimerToque, true);
    document.addEventListener('keydown', activarConPrimerToque, true);
    sonidoElegidoApagado = leerApagado();
    if (!sonidoElegidoApagado) encenderSonido(false);

    // ----- Cartel -----
    function mostrarToast(nuevos) {
      const p = nuevos[0];
      txt(document.getElementById('toast-web-titulo'),
          nuevos.length === 1 ? '🔔 Llegó un pedido web' : '🔔 Llegaron ' + nuevos.length + ' pedidos web');
      txt(document.getElementById('toast-web-detalle'),
          nuevos.length === 1 ? p.codigo + ' · ' + p.nombre + ' · ' + plata(p.total)
                              : 'El último: ' + p.codigo + ' · ' + p.nombre);
      toast.classList.add('visible');
      clearTimeout(cierreToast);
      cierreToast = setTimeout(() => toast.classList.remove('visible'), 30000);
    }
    document.getElementById('toast-web-cerrar').addEventListener('click', () => toast.classList.remove('visible'));
    document.getElementById('toast-web-ver').addEventListener('click', () => toast.classList.remove('visible'));

    // ----- Contador fijo arriba -----
    function pintar(datos) {
      const n = datos.cantidad;
      banner.classList.toggle('visible', n > 0);
      if (n > 0) {
        txt(bannerTexto, n === 1 ? '🔔 1 pedido web por atender' : '🔔 ' + n + ' pedidos web por atender');
        txt(bannerDetalle, datos.pedidos.slice(0, 3).map(p => p.codigo + ' · ' + p.nombre + ' · ' + plata(p.total)).join('   |   ')
                           + (n > 3 ? '   y ' + (n - 3) + ' más' : ''));
      }
      document.title = n > 0 ? '(' + n + ') Pedido web · ' + tituloOriginal : tituloOriginal;
    }

    async function consultar() {
      if (sesionVencida) return;
      try {
        const res = await fetch('pedidos_web_aviso.php', { cache: 'no-store', credentials: 'same-origin' });
        if (res.status === 401) { sesionVencida = true; return; }
        const datos = await res.json();
        if (!datos || !Array.isArray(datos.pedidos)) return;

        const maxId = datos.pedidos.reduce((m, p) => Math.max(m, p.id), 0);
        if (ultimoId !== null) {
          const nuevos = datos.pedidos.filter(p => p.id > ultimoId);
          if (nuevos.length) {
            mostrarToast(nuevos);
            tocarAviso();
            banner.classList.remove('pulso'); void banner.offsetWidth; banner.classList.add('pulso');
          }
        }
        ultimoId = Math.max(ultimoId || 0, maxId);
        pintar(datos);
      } catch (e) { /* sin conexión: se reintenta en el próximo ciclo */ }
    }

    consultar();
    setInterval(consultar, INTERVALO);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) consultar(); });
  })();
</script>

</body>
</html>
