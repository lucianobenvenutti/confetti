-- =========================================================
-- Confetti — Migración: inicio de sesión del panel
-- Ejecutar UNA sola vez sobre la base que ya tenés cargada.
-- =========================================================
-- Guarda los intentos de ingreso FALLIDOS para poder bloquear a quien
-- pruebe contraseñas una tras otra. (La tabla "usuarios" ya existía.)

USE confetti;

CREATE TABLE login_intentos (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario    VARCHAR(60) NOT NULL,
  ip         VARCHAR(45) NOT NULL,
  creado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_usuario (usuario, creado_en),
  INDEX idx_login_ip (ip, creado_en)
) ENGINE=InnoDB;
