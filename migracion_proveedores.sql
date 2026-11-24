-- =========================================================
-- Confetti — Migración: proveedores
-- Ejecutar UNA sola vez sobre la base que ya tenés cargada.
-- =========================================================

USE confetti;

CREATE TABLE proveedores (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(120) NOT NULL,
  telefono      VARCHAR(30)  NULL,
  direccion     VARCHAR(255) NULL,
  dias_reparto  VARCHAR(120) NULL,   -- texto libre, ej: "Lunes y Jueves"
  notas         VARCHAR(255) NULL,
  activo        TINYINT(1) NOT NULL DEFAULT 1,
  creado_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE productos
  ADD COLUMN proveedor_id INT UNSIGNED NULL AFTER categoria_id,
  ADD CONSTRAINT fk_productos_proveedor
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id)
    ON DELETE SET NULL;
-- ON DELETE SET NULL: si borrás un proveedor, los productos no se tocan,
-- simplemente quedan sin proveedor asignado (a diferencia de categorías,
-- acá no tiene sentido bloquear el borrado).
