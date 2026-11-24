-- =========================================================
-- Confetti — Pedidos de prueba (para ver el panel de hoy funcionando)
-- Importar DESPUÉS de schema_confetti.sql y seed_confetti.sql
-- =========================================================

USE confetti;

INSERT INTO pedidos (codigo, nombre_cliente, telefono_cliente, origen, estado, total, creado_en, vence_en) VALUES
  ('CF-0001', 'Martina Gómez',  '3482-555123', 'web',       'pendiente',      5700.00, NOW() - INTERVAL 10 MINUTE, NOW() + INTERVAL 23 HOUR + INTERVAL 50 MINUTE),
  ('CF-0002', 'Rodrigo Paz',    '3482-555456', 'web',       'en_preparacion', 6040.00, NOW() - INTERVAL 40 MINUTE, NOW() + INTERVAL 23 HOUR + INTERVAL 20 MINUTE),
  ('CF-0003', 'Cliente mostrador', '-',        'mostrador', 'listo',          6600.00, NOW() - INTERVAL 25 MINUTE, NULL),
  ('CF-0004', 'Julieta Díaz',   '3482-555789', 'web',       'entregado',      5000.00, NOW() - INTERVAL 2 HOUR,    NOW() + INTERVAL 22 HOUR);

-- Pedido 1: Facturas x6 + Torta individual x1
INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
SELECT (SELECT id FROM pedidos WHERE codigo='CF-0001'), id, 6, precio, precio * 6
FROM productos WHERE nombre='Facturas surtidas';

INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
SELECT (SELECT id FROM pedidos WHERE codigo='CF-0001'), id, 1, precio, precio
FROM productos WHERE nombre='Torta individual de dulce de leche';

-- Pedido 2: el combo fijo completo
INSERT INTO pedido_items (pedido_id, combo_id, cantidad, precio_unitario, subtotal)
SELECT (SELECT id FROM pedidos WHERE codigo='CF-0002'), id, 1, precio, precio
FROM combos WHERE nombre='Picada Confetti para 2';

-- Pedido 3: Empanaditas x1 + Gaseosa x2
INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
SELECT (SELECT id FROM pedidos WHERE codigo='CF-0003'), id, 1, precio, precio
FROM productos WHERE nombre='Empanaditas de jamón y queso';

INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
SELECT (SELECT id FROM pedidos WHERE codigo='CF-0003'), id, 2, precio, precio * 2
FROM productos WHERE nombre='Gaseosa 500ml';

-- Pedido 4: Alfajores x4 + Grisines x2
INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
SELECT (SELECT id FROM pedidos WHERE codigo='CF-0004'), id, 4, precio, precio * 4
FROM productos WHERE nombre='Alfajores de maicena';

INSERT INTO pedido_items (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
SELECT (SELECT id FROM pedidos WHERE codigo='CF-0004'), id, 2, precio, precio * 2
FROM productos WHERE nombre='Grisines caseros';
