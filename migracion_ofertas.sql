-- =========================================================
-- Confetti — Migración: ofertas (descuento por producto)
-- Ejecutar UNA sola vez, en la base que ya tenés (elegila antes en phpMyAdmin).
-- =========================================================
-- oferta_tipo  : 'porcentaje' (ej: 20 = 20% menos) o 'monto' (ej: 300 = $300 menos).
-- oferta_valor : el número del descuento. Si ambos están vacíos, el producto no está en oferta.
-- No toca ni borra ningún producto: los que ya estaban quedan sin oferta.

ALTER TABLE productos
  ADD COLUMN oferta_tipo  ENUM('porcentaje','monto') NULL AFTER precio_compra,
  ADD COLUMN oferta_valor DECIMAL(10,2) NULL AFTER oferta_tipo;
