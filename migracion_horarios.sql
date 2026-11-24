-- =========================================================
-- Confetti — Migración: horarios de atención
-- Ejecutar UNA sola vez sobre la base que ya tenés cargada.
-- =========================================================

USE confetti;

CREATE TABLE horarios_atencion (
  dia      TINYINT UNSIGNED PRIMARY KEY, -- 0=Lunes, 1=Martes ... 6=Domingo
  abre     TIME NULL,
  cierra   TIME NULL,
  cerrado  TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- Horario de ejemplo para arrancar — se edita desde admin/horarios.php
INSERT INTO horarios_atencion (dia, abre, cierra, cerrado) VALUES
  (0, '09:00', '21:00', 0), -- Lunes
  (1, '09:00', '21:00', 0), -- Martes
  (2, '09:00', '21:00', 0), -- Miércoles
  (3, '09:00', '21:00', 0), -- Jueves
  (4, '09:00', '21:00', 0), -- Viernes
  (5, '09:00', '21:00', 0), -- Sábado
  (6, NULL, NULL, 1);       -- Domingo (cerrado de ejemplo)
