-- =========================================================
-- Confetti — Datos de prueba
-- Importar DESPUÉS de schema_confetti.sql
-- =========================================================

USE confetti;

-- ---------------------------------------------------------
-- Categorías (mismos colores que ya usa index.html)
-- ---------------------------------------------------------
INSERT INTO categorias (nombre, color_acento, orden) VALUES
  ('Copetín',      '#E8483C', 1),
  ('Panificados',  '#F5941F', 2),
  ('Repostería',   '#1E9C7C', 3),
  ('Fiambrería',   '#6E4E9E', 4),
  ('Bebidas',      '#1B4A9C', 5);

-- ---------------------------------------------------------
-- Productos
-- ---------------------------------------------------------
INSERT INTO productos (categoria_id, nombre, descripcion, tipo_venta, precio, stock_actual, stock_minimo, sin_tacc, destacado) VALUES
  ((SELECT id FROM categorias WHERE nombre='Copetín'),
   'Empanaditas de jamón y queso', 'Docena, recién horneadas', 'unidad', 4200.00, 15, 3, 0, 1),

  ((SELECT id FROM categorias WHERE nombre='Panificados'),
   'Facturas surtidas', 'Por unidad, mínimo 6', 'unidad', 650.00, 120, 24, 0, 1),

  ((SELECT id FROM categorias WHERE nombre='Panificados'),
   'Grisines caseros', 'Paquete casero', 'unidad', 900.00, 40, 8, 1, 0),

  ((SELECT id FROM categorias WHERE nombre='Repostería'),
   'Torta individual de dulce de leche', 'Porción generosa', 'unidad', 1800.00, 20, 4, 0, 1),

  ((SELECT id FROM categorias WHERE nombre='Repostería'),
   'Alfajores de maicena', 'Rellenos de dulce de leche', 'unidad', 800.00, 60, 12, 0, 0),

  ((SELECT id FROM categorias WHERE nombre='Fiambrería'),
   'Queso cremoso', 'Se pide por gramos', 'peso', 3600.00, 8.500, 1.000, 0, 1),

  ((SELECT id FROM categorias WHERE nombre='Fiambrería'),
   'Jamón crudo', 'Se pide por gramos', 'peso', 10500.00, 4.200, 1.000, 0, 0),

  ((SELECT id FROM categorias WHERE nombre='Bebidas'),
   'Gaseosa 500ml', 'Línea cola y lima-limón', 'unidad', 1200.00, 30, 6, 0, 0);

-- ---------------------------------------------------------
-- Un combo fijo de ejemplo (el que se ve en el panel "Armá tu picada")
-- ---------------------------------------------------------
INSERT INTO combos (nombre, descripcion, precio) VALUES
  ('Picada Confetti para 2', 'Fiambres, quesos, pan y algo dulce para cerrar', 6040.00);

INSERT INTO combo_productos (combo_id, producto_id, cantidad)
SELECT (SELECT id FROM combos WHERE nombre='Picada Confetti para 2'), id, 0.200
FROM productos WHERE nombre='Jamón crudo';

INSERT INTO combo_productos (combo_id, producto_id, cantidad)
SELECT (SELECT id FROM combos WHERE nombre='Picada Confetti para 2'), id, 0.200
FROM productos WHERE nombre='Queso cremoso';

INSERT INTO combo_productos (combo_id, producto_id, cantidad)
SELECT (SELECT id FROM combos WHERE nombre='Picada Confetti para 2'), id, 1
FROM productos WHERE nombre='Grisines caseros';

INSERT INTO combo_productos (combo_id, producto_id, cantidad)
SELECT (SELECT id FROM combos WHERE nombre='Picada Confetti para 2'), id, 2
FROM productos WHERE nombre='Alfajores de maicena';
