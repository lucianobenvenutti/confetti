-- =========================================================
-- Confetti — Limpiar los datos de prueba antes de abrir
-- Ejecutar UNA vez, en la base que ya tiene los productos cargados.
-- =========================================================
-- SE BORRA: pedidos, ventas del mostrador, cuentas corrientes (fiados) y
--           registros de intentos de ingreso.
-- SE CONSERVA: productos, categorías, combos, proveedores, horarios,
--              usuarios (dueño y empleados) y el historial de ingresos de mercadería.
-- COMPROBANTES: este script borra los pedidos, pero NO los archivos de comprobantes de prueba.
--              Después de ejecutarlo, vaciá la carpeta comprobantes/ (dejá adentro solo
--              los archivos .htaccess e index.html).
-- DATOS DE PAGO: el alias y CBU cargados en el panel se conservan.
-- STOCK: las reservas y ventas de prueba se devuelven al stock, así cada producto
--        queda con lo que realmente cargaste (ingresos + ajustes manuales).
-- =========================================================
-- Antes de importar: en phpMyAdmin elegí tu base en la columna izquierda (sirve para cualquier nombre).

START TRANSACTION;

-- 1) Devolver al stock lo que se movió con pedidos y ventas de prueba
UPDATE productos p
JOIN (
  SELECT producto_id, SUM(cantidad) AS neto
  FROM movimientos_stock
  WHERE tipo IN ('reserva', 'liberacion_vencimiento', 'liberacion_cancelacion', 'venta_mostrador')
  GROUP BY producto_id
) m ON m.producto_id = p.id
SET p.stock_actual = p.stock_actual - m.neto;

-- 2) Borrar esos movimientos (los de ingreso de mercadería y ajustes se quedan)
DELETE FROM movimientos_stock
WHERE tipo IN ('reserva', 'liberacion_vencimiento', 'liberacion_cancelacion', 'venta_mostrador');

-- 3) Borrar cuentas corrientes de prueba (si ya cargaron fiados REALES, NO ejecutes estas dos líneas)
DELETE FROM cuenta_movimientos;
DELETE FROM cuentas_corrientes;

-- 4) Borrar pedidos de prueba
DELETE FROM pedido_items;
DELETE FROM pedidos;

-- 5) Registros técnicos
DELETE FROM login_intentos;
DELETE FROM api_limites;

ALTER TABLE pedidos AUTO_INCREMENT = 1;

COMMIT;
