-- =========================================================
-- Confetti — Migración: horarios con dos turnos por día
-- Ejecutar UNA sola vez, sobre la base que ya tiene horarios_atencion.
-- =========================================================
-- Turno 1 (mañana): columnas "abre" y "cierra" que ya existían.
-- Turno 2 (tarde):  columnas nuevas "abre2" y "cierra2".
-- Un día de corrido usa solo el turno 1 y deja el turno 2 vacío,
-- así que lo que ya cargaste sigue valiendo tal cual.

USE confetti;

ALTER TABLE horarios_atencion
  ADD COLUMN abre2   TIME NULL AFTER cierra,
  ADD COLUMN cierra2 TIME NULL AFTER abre2;
