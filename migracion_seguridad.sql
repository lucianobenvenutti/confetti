-- =========================================================
-- Confetti — Migración de seguridad y velocidad
-- Ejecutar UNA sola vez en la base que ya tenés.
-- =========================================================
USE confetti;

-- Frenos contra abuso en la parte pública (pedidos / seguimiento) por IP
CREATE TABLE IF NOT EXISTS api_limites (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  accion     VARCHAR(20) NOT NULL,
  ip         VARCHAR(45) NOT NULL,
  creado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_limites (accion, ip, creado_en)
);

-- Velocidad: la limpieza de pedidos vencidos corre en cada visita al catálogo
-- y filtra por estado + vencimiento a la vez
CREATE INDEX idx_pedidos_estado_vence ON pedidos(estado, vence_en);

-- Velocidad: contar pedidos activos de un mismo teléfono
CREATE INDEX idx_pedidos_telefono ON pedidos(telefono_cliente);
