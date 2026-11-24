-- =========================================================
-- Confetti — Migración: forma de pago, datos de transferencia y comprobantes
-- Ejecutar UNA sola vez, en la base que ya tenés (elegila antes en phpMyAdmin).
-- =========================================================

-- Cómo eligió pagar el cliente y, si pagó por transferencia, el archivo del comprobante
-- (el archivo se guarda en la carpeta "comprobantes/" del servidor, no en la base)
ALTER TABLE pedidos
  ADD COLUMN metodo_pago ENUM('local','transferencia') NOT NULL DEFAULT 'local' AFTER notas,
  ADD COLUMN comprobante VARCHAR(80) NULL AFTER metodo_pago;

-- Ajustes editables desde el panel (por ahora: los datos para transferir)
CREATE TABLE IF NOT EXISTS configuracion (
  clave  VARCHAR(40)  NOT NULL PRIMARY KEY,
  valor  VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB;
