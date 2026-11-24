-- =========================================================
-- Confetti — Migración: código de barras en productos
-- Ejecutar UNA sola vez sobre la base que ya tenés cargada.
-- =========================================================

USE confetti;

ALTER TABLE productos
  ADD COLUMN codigo_barras VARCHAR(64) NULL UNIQUE AFTER imagen;
