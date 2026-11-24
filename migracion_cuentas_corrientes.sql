-- =========================================================
-- Confetti — Migración: cuentas corrientes (fiado)
-- Ejecutar UNA sola vez sobre la base que ya tenés cargada.
-- =========================================================
-- El saldo NO se guarda: se calcula sumando los movimientos
-- (cargos - pagos). Así nunca puede quedar desfasado.
--   saldo > 0  -> el cliente debe esa plata
--   saldo < 0  -> el cliente tiene saldo a favor

USE confetti;

CREATE TABLE cuentas_corrientes (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(120) NOT NULL,
  telefono   VARCHAR(30)  NULL,
  notas      VARCHAR(255) NULL,
  creado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE cuenta_movimientos (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cuenta_id  INT UNSIGNED NOT NULL,
  tipo       ENUM('cargo','pago') NOT NULL,   -- cargo = compró (suma deuda), pago = pagó (resta)
  monto      DECIMAL(10,2) NOT NULL,          -- siempre positivo; el tipo define si suma o resta
  pedido_id  INT UNSIGNED NULL,               -- la venta del mostrador que originó el cargo, si hubo
  nota       VARCHAR(255) NULL,
  creado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cuenta_id) REFERENCES cuentas_corrientes(id) ON DELETE CASCADE,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_cuenta_mov_cuenta ON cuenta_movimientos(cuenta_id);
-- ON DELETE CASCADE: si eliminás una cuenta, se va con su historial de movimientos.
-- Las ventas (pedidos) NO se tocan: quedan en el historial de ventas.
