-- =========================================================
-- Confetti — Migración: precio de compra de cada producto
-- Ejecutar UNA sola vez, en la base que ya tenés (elegila antes en phpMyAdmin).
-- =========================================================
-- Lo que pagó el local por el producto (por unidad o por kg, igual que el precio de venta).
-- Es solo para el dueño: no se muestra en la tienda ni en el mostrador.
-- Los productos que ya estaban cargados quedan con el precio de compra vacío.

ALTER TABLE productos
  ADD COLUMN precio_compra DECIMAL(10,2) NULL AFTER precio;
