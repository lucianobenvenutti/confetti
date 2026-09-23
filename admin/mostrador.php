<!DOCTYPE html>
<html lang="es-AR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confetti — Mostrador</title>
<style>
  :root{
    --paper:#FFFBF3; --paper-dim:#FFF4DE; --ink:#1F2A44; --ink-soft:#5B6478;
    --rojo:#E8483C; --verde:#1E9C7C; --linea:rgba(31,42,68,0.12);
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
    grid-template-columns:1fr 360px;
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
    gap:12px;
  }
  .card{
    background:#fff;
    border:1px solid var(--linea);
    border-radius:12px;
    padding:10px;
    cursor:pointer;
    text-align:left;
    display:flex;
    flex-direction:column;
    gap:4px;
  }
  .card:hover{ border-color:var(--rojo); }
  .card:disabled{ opacity:.45; cursor:not-allowed; }
  .card .mini-foto{
    width:100%; height:64px; object-fit:cover; border-radius:8px;
    background:var(--paper-dim);
  }
  .card .nombre{ font-weight:600; font-size:0.9rem; }
  .card .precio{ color:var(--ink-soft); font-size:0.82rem; }
  .card.stock-bajo{ background:#FDEAEA; border-color:#f0b7b2; }
  .card .badge-bajo{
    align-self:flex-start;
    background:var(--rojo);
    color:#fff;
    font-size:0.65rem;
    font-weight:700;
    padding:2px 7px;
    border-radius:6px;
  }

  .lista-venta{ flex:1; overflow-y:auto; margin:14px 0; }
  .item-venta{
    display:flex; justify-content:space-between; align-items:center;
    padding:10px 0; border-bottom:1px solid var(--linea); font-size:0.9rem; gap:8px;
  }
  .item-venta .nombre{ font-weight:600; }
  .item-venta .detalle{ color:var(--ink-soft); font-size:0.8rem; }
  .item-venta button{
    border:none; background:none; color:var(--rojo); font-size:0.8rem; cursor:pointer;
  }
  .vacio{ color:var(--ink-soft); font-size:0.9rem; }

  .acciones-secundarias{
    display:flex; gap:8px; margin-bottom:14px;
  }
  .btn-secundario{
    flex:1;
    padding:10px;
    border-radius:9px;
    border:1px solid var(--linea);
    background:#fff;
    font-size:0.82rem;
    font-weight:600;
    cursor:pointer;
  }
  .btn-secundario.activo{ border-color:var(--rojo); color:var(--rojo); }

  .resumen{ font-size:0.88rem; margin-bottom:8px; }
  .resumen .fila{ display:flex; justify-content:space-between; padding:3px 0; }
  .resumen .fila.descuento{ color:var(--rojo); }
  .resumen .fila button{ border:none; background:none; color:var(--ink-soft); font-size:0.75rem; cursor:pointer; text-decoration:underline; margin-left:6px; }

  .total-row{
    display:flex; justify-content:space-between; font-weight:700; font-size:1.2rem;
    padding-top:10px; border-top:1px solid var(--linea); margin-bottom:8px;
  }
  .vuelto-info{
    font-size:0.9rem; padding:10px 12px; border-radius:10px; margin-bottom:12px;
  }
  .vuelto-info.ok{ background:#E4F5EE; color:var(--verde); }
  .vuelto-info.falta{ background:#FBEAE8; color:var(--rojo); }

  button.grande{
    width:100%; padding:14px; border:none; border-radius:10px;
    background:var(--rojo); color:#fff; font-weight:700; font-size:1rem; cursor:pointer;
  }
  button.grande:hover{ background:#c73a2f; }
  button.grande:disabled{ background:#ccc; cursor:not-allowed; }

  .confirmacion{ text-align:center; padding:30px 10px; }
  .confirmacion .check{ font-size:2.4rem; color:var(--verde); }
  .confirmacion .codigo{ font-size:1.3rem; font-weight:700; margin:8px 0; }
  .confirmacion .monto{ color:var(--ink-soft); margin-bottom:6px; }
  .confirmacion .detalle-pago{ color:var(--ink-soft); font-size:0.85rem; margin-bottom:18px; }

  .aviso-error{
    background:#FBEAE8; color:var(--rojo); border:1px solid #f3c9c4;
    border-radius:10px; padding:10px 12px; font-size:0.85rem; margin-bottom:12px; display:none;
  }
</style>
</head>
<body>

<div class="layout">
  <div class="panel-productos">
    <h1>Mostrador rápido</h1>
    <p class="sub">Tocá un producto para sumarlo a la venta actual, o buscalo por nombre.</p>
    <div class="buscador-row">
      <input type="text" id="input-buscar" placeholder="Buscar producto por nombre… (código de barras próximamente)" autocomplete="off">
    </div>
    <div class="chip-row" id="chip-row"></div>
    <div class="grid" id="grid-productos"></div>
  </div>

  <div class="panel-venta" id="panel-venta">
    <h1 style="font-size:1.15rem;">Venta actual</h1>
    <div class="aviso-error" id="aviso-error"></div>
    <div class="lista-venta" id="lista-venta">
      <p class="vacio">Todavía no agregaste nada.</p>
    </div>

    <div class="acciones-secundarias">
      <button class="btn-secundario" id="btn-descuento">Descuento</button>
      <button class="btn-secundario" id="btn-pago">Pago con...</button>
    </div>

    <div class="resumen" id="resumen"></div>
    <div class="total-row"><span>Total</span><span id="total-venta">$0</span></div>
    <div id="vuelto-info"></div>

    <button class="grande" id="btn-registrar" disabled>Registrar venta</button>
  </div>
</div>

<script>
  const API = '../api/';
  let productosCache = [];
  let categoriaActiva = null;
  let venta = []; // { tipo, id, nombre, precio, tipo_venta, cantidad }
  let descuento = null;   // { tipo: 'porcentaje'|'monto', valor: number }
  let montoPagado = null; // number o null

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
      return `<img class="mini-foto" src="../assets/productos/${p.imagen}" alt=""
                onerror="this.style.display='none'">`;
    }
    return '';
  }

  // Trae TODOS los productos una sola vez. El filtrado por categoría y por
  // búsqueda se hace después en el navegador, así no hay que ir al servidor
  // cada vez que se toca un chip o se escribe una letra.
  async function cargarProductos() {
    productosCache = await pedirJSON(API + 'productos.php');
    renderGrid();
  }

  function productosFiltrados() {
    const busqueda = document.getElementById('input-buscar').value.trim().toLowerCase();
    return productosCache.filter(p => {
      const pasaCategoria = !categoriaActiva || p.categoria_id === categoriaActiva;
      const pasaBusqueda = !busqueda || p.nombre.toLowerCase().includes(busqueda);
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
      btn.className = 'card' + (stockBajo ? ' stock-bajo' : '');
      btn.disabled = !p.disponible;
      const precio = p.tipo_venta === 'peso' ? `${formatoPrecio.format(p.precio)}/kg` : formatoPrecio.format(p.precio);
      btn.innerHTML = `
        ${bloqueMiniFoto(p)}
        ${stockBajo ? '<span class="badge-bajo">Stock bajo</span>' : ''}
        <span class="nombre">${p.nombre}</span>
        <span class="precio">${precio}${p.disponible ? '' : ' — sin stock'}</span>
      `;
      btn.addEventListener('click', () => agregarProducto(p));
      cont.appendChild(btn);
    });
  }

  const inputBuscar = document.getElementById('input-buscar');

  inputBuscar.addEventListener('input', renderGrid);

  // Pensado para cuando sumemos código de barras: si escribís (o "escanea" un
  // lector, que tipea rápido y manda Enter) y el resultado es un único
  // producto disponible, lo agrega directo sin tener que tocarlo con el mouse.
  inputBuscar.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
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
        tipo: 'producto', id: p.id, nombre: p.nombre,
        precio: parseFloat(p.precio), tipo_venta: 'peso', cantidad: gramos / 1000,
      });
    } else {
      const existente = venta.find(i => i.tipo === 'producto' && i.id === p.id);
      if (existente) {
        existente.cantidad += 1;
      } else {
        venta.push({
          tipo: 'producto', id: p.id, nombre: p.nombre,
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

  function etiquetaCantidad(item) {
    if (item.tipo_venta === 'peso') {
      return item.cantidad < 1 ? `${Math.round(item.cantidad * 1000)}g` : `${item.cantidad}kg`;
    }
    return `x${item.cantidad}`;
  }

  function calcularSubtotal() {
    return venta.reduce((acc, i) => acc + i.precio * i.cantidad, 0);
  }

  function calcularMontoDescuento(subtotal) {
    if (!descuento) return 0;
    if (descuento.tipo === 'porcentaje') {
      return subtotal * Math.min(Math.max(descuento.valor, 0), 100) / 100;
    }
    return Math.min(Math.max(descuento.valor, 0), subtotal);
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
        div.innerHTML = `
          <div>
            <div class="nombre">${item.nombre}</div>
            <div class="detalle">${etiquetaCantidad(item)} · ${formatoPrecio.format(item.precio * item.cantidad)}</div>
          </div>
          <button aria-label="Quitar">Quitar</button>
        `;
        div.querySelector('button').addEventListener('click', () => quitarItem(index));
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
          <span>Descuento ${descuento.tipo === 'porcentaje' ? `(${descuento.valor}%)` : ''}
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

    btnRegistrar.disabled = venta.length === 0;
  }

  document.getElementById('btn-descuento').addEventListener('click', () => {
    const texto = prompt('Descuento (ej: "10%" para porcentaje, o "500" para un monto fijo en pesos). Dejalo en 0 para sacarlo.', descuento ? (descuento.tipo === 'porcentaje' ? descuento.valor + '%' : descuento.valor) : '');
    if (texto === null) return;

    const limpio = texto.trim();
    if (limpio === '' || parseFloat(limpio) === 0) {
      descuento = null;
      renderVenta();
      return;
    }

    if (limpio.includes('%')) {
      const valor = parseFloat(limpio.replace('%', ''));
      if (!isNaN(valor) && valor > 0) descuento = { tipo: 'porcentaje', valor };
    } else {
      const valor = parseFloat(limpio);
      if (!isNaN(valor) && valor > 0) descuento = { tipo: 'monto', valor };
    }
    renderVenta();
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
      btn.textContent = 'Registrar venta';
    }
  }

  function mostrarConfirmacion(data) {
    const panel = document.getElementById('panel-venta');
    let detallePago = '';
    if (data.descuento_monto > 0) {
      detallePago += `Descuento aplicado: -${formatoPrecio.format(data.descuento_monto)}<br>`;
    }
    if (data.vuelto !== null && data.vuelto !== undefined) {
      detallePago += `Pagó ${formatoPrecio.format(data.monto_pagado)} · Vuelto ${formatoPrecio.format(data.vuelto)}`;
    }

    panel.innerHTML = `
      <div class="confirmacion">
        <div class="check">✓</div>
        <div class="codigo">${data.codigo}</div>
        <div class="monto">Venta registrada por ${formatoPrecio.format(data.total)}</div>
        <div class="detalle-pago">${detallePago}</div>
        <button class="grande" id="btn-nueva-venta">Nueva venta</button>
      </div>
    `;
    document.getElementById('btn-nueva-venta').addEventListener('click', () => location.reload());
  }

  cargarCategorias();
  cargarProductos();
  renderVenta();
</script>

</body>
</html>