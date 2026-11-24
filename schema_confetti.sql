-- =========================================================
-- Confetti — Esquema de base de datos (MySQL)
-- Snack, Panadería y Repostería — sistema de pedidos + stock
-- =========================================================

CREATE DATABASE IF NOT EXISTS confetti
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE confetti;

-- ---------------------------------------------------------
-- Usuarios del panel (dueño / empleados que usan el mostrador)
-- ---------------------------------------------------------
CREATE TABLE usuarios (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre         VARCHAR(80)  NOT NULL,
  usuario        VARCHAR(60)  NOT NULL UNIQUE,
  password_hash  VARCHAR(255) NOT NULL,
  rol            ENUM('dueño','empleado') NOT NULL DEFAULT 'empleado',
  activo         TINYINT(1) NOT NULL DEFAULT 1,
  creado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Categorías (Copetín, Panificados, Repostería, Fiambrería...)
-- ---------------------------------------------------------
CREATE TABLE categorias (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(60) NOT NULL,
  color_acento  VARCHAR(7)  NULL,      -- hex, ej: '#E8483C', para mantener la identidad visual
  orden         INT UNSIGNED NOT NULL DEFAULT 0,
  activo        TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Proveedores (a quién pedirle cuando algo se queda sin stock)
-- ---------------------------------------------------------
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

-- ---------------------------------------------------------
-- Productos
-- ---------------------------------------------------------
CREATE TABLE productos (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_id   INT UNSIGNED NOT NULL,
  proveedor_id   INT UNSIGNED NULL,                -- a quién comprarle cuando se acaba
  nombre         VARCHAR(120) NOT NULL,
  descripcion    VARCHAR(255) NULL,
  tipo_venta     ENUM('unidad','peso') NOT NULL,   -- 'peso' = se vende por kg
  precio         DECIMAL(10,2) NOT NULL,           -- precio por unidad o por kg, según tipo_venta
  precio_compra  DECIMAL(10,2) NULL,               -- lo que pagó el local (solo lo ve el dueño)
  stock_actual   DECIMAL(10,3) NOT NULL DEFAULT 0, -- unidades o kg, según tipo_venta
  stock_minimo   DECIMAL(10,3) NOT NULL DEFAULT 0, -- umbral para alerta de bajo stock
  imagen         VARCHAR(255) NULL,
  codigo_barras  VARCHAR(64) NULL UNIQUE,          -- código de fábrica o inventado por el local
  sin_tacc       TINYINT(1) NOT NULL DEFAULT 0,
  destacado      TINYINT(1) NOT NULL DEFAULT 0,    -- para "lo más pedido" / vidriera
  activo         TINYINT(1) NOT NULL DEFAULT 1,
  creado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id),
  FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_productos_categoria ON productos(categoria_id);
CREATE INDEX idx_productos_proveedor ON productos(proveedor_id);

-- ---------------------------------------------------------
-- Combos fijos (armados por el dueño, precio especial)
-- ---------------------------------------------------------
CREATE TABLE combos (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(120) NOT NULL,
  descripcion  VARCHAR(255) NULL,
  precio       DECIMAL(10,2) NOT NULL,
  imagen       VARCHAR(255) NULL,
  activo       TINYINT(1) NOT NULL DEFAULT 1,
  creado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Qué productos y en qué cantidad componen cada combo fijo
CREATE TABLE combo_productos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  combo_id    INT UNSIGNED NOT NULL,
  producto_id INT UNSIGNED NOT NULL,
  cantidad    DECIMAL(10,3) NOT NULL,  -- unidades o kg, según el producto
  FOREIGN KEY (combo_id) REFERENCES combos(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Pedidos (tanto web como venta mostrador pasan por acá)
-- ---------------------------------------------------------
CREATE TABLE pedidos (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo            VARCHAR(12) NOT NULL UNIQUE,  -- código corto para seguimiento (ej: CF-8X2K)
  nombre_cliente    VARCHAR(120) NOT NULL,
  telefono_cliente  VARCHAR(30)  NOT NULL,
  origen            ENUM('web','mostrador') NOT NULL DEFAULT 'web',
  estado            ENUM('pendiente','en_preparacion','listo','entregado','cancelado')
                     NOT NULL DEFAULT 'pendiente',
  total             DECIMAL(10,2) NOT NULL DEFAULT 0,
  notas             VARCHAR(255) NULL,
  metodo_pago       ENUM('local','transferencia') NOT NULL DEFAULT 'local',  -- cómo eligió pagar el cliente
  comprobante       VARCHAR(80) NULL,    -- archivo del comprobante de transferencia (carpeta comprobantes/)
  usuario_id        INT UNSIGNED NULL,   -- quién cargó la venta si fue por mostrador
  creado_en         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  vence_en          TIMESTAMP NULL,      -- creado_en + 24h; NULL para ventas de mostrador (no aplica)
  actualizado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE INDEX idx_pedidos_estado ON pedidos(estado);
CREATE INDEX idx_pedidos_vence ON pedidos(vence_en);

-- ---------------------------------------------------------
-- Ítems de cada pedido: un producto suelto O un combo fijo
-- ---------------------------------------------------------
CREATE TABLE pedido_items (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id        INT UNSIGNED NOT NULL,
  producto_id      INT UNSIGNED NULL,
  combo_id         INT UNSIGNED NULL,
  cantidad         DECIMAL(10,3) NOT NULL,  -- unidades, kg, o "combos" (normalmente 1)
  precio_unitario  DECIMAL(10,2) NOT NULL,  -- precio al momento de la venta (no recalcula si cambia después)
  subtotal         DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id),
  FOREIGN KEY (combo_id) REFERENCES combos(id),
  CONSTRAINT chk_item_tipo CHECK (
    (producto_id IS NOT NULL AND combo_id IS NULL) OR
    (producto_id IS NULL AND combo_id IS NOT NULL)
  )
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Bitácora de movimientos de stock (auditoría + reportes)
-- ---------------------------------------------------------
CREATE TABLE movimientos_stock (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id  INT UNSIGNED NOT NULL,
  tipo         ENUM(
                 'reserva',                 -- se descuenta al confirmar un pedido
                 'liberacion_vencimiento',   -- pedido no retirado en 24hs, vuelve al stock
                 'liberacion_cancelacion',   -- el dueño canceló el pedido a mano
                 'venta_mostrador',          -- venta cargada directo en el mostrador
                 'ajuste_manual',            -- corrección manual del dueño
                 'entrada_stock'             -- reposición / ingreso de mercadería
               ) NOT NULL,
  cantidad     DECIMAL(10,3) NOT NULL,   -- positivo (entra) o negativo (sale)
  pedido_id    INT UNSIGNED NULL,
  usuario_id   INT UNSIGNED NULL,
  creado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (producto_id) REFERENCES productos(id),
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE INDEX idx_movimientos_producto ON movimientos_stock(producto_id);
CREATE INDEX idx_movimientos_fecha ON movimientos_stock(creado_en);

-- ---------------------------------------------------------
-- Horarios de atención (para el cartel de "abierto ahora" en la web)
-- ---------------------------------------------------------
CREATE TABLE horarios_atencion (
  dia      TINYINT UNSIGNED PRIMARY KEY, -- 0=Lunes, 1=Martes ... 6=Domingo
  abre     TIME NULL,                    -- turno 1 (mañana): apertura
  cierra   TIME NULL,                    -- turno 1 (mañana): cierre
  abre2    TIME NULL,                    -- turno 2 (tarde): apertura
  cierra2  TIME NULL,                    -- turno 2 (tarde): cierre
  cerrado  TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

INSERT INTO horarios_atencion (dia, abre, cierra, cerrado) VALUES
  (0, '09:00', '21:00', 0), (1, '09:00', '21:00', 0), (2, '09:00', '21:00', 0),
  (3, '09:00', '21:00', 0), (4, '09:00', '21:00', 0), (5, '09:00', '21:00', 0),
  (6, NULL, NULL, 1);

-- ---------------------------------------------------------
-- Cuentas corrientes (fiado). El saldo se calcula: cargos - pagos.
-- ---------------------------------------------------------
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
  tipo       ENUM('cargo','pago') NOT NULL,
  monto      DECIMAL(10,2) NOT NULL,
  pedido_id  INT UNSIGNED NULL,
  nota       VARCHAR(255) NULL,
  creado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (cuenta_id) REFERENCES cuentas_corrientes(id) ON DELETE CASCADE,
  FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_cuenta_mov_cuenta ON cuenta_movimientos(cuenta_id);

-- ---------------------------------------------------------
-- Intentos fallidos de ingreso al panel (para frenar adivinadores de contraseñas)
-- ---------------------------------------------------------
CREATE TABLE login_intentos (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario    VARCHAR(60) NOT NULL,
  ip         VARCHAR(45) NOT NULL,
  creado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_usuario (usuario, creado_en),
  INDEX idx_login_ip (ip, creado_en)
) ENGINE=InnoDB;


-- Frenos contra abuso en la parte pública (pedidos / seguimiento) por IP
CREATE TABLE api_limites (
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

-- ---------------------------------------------------------
-- Ajustes editables desde el panel (datos para transferir)
-- ---------------------------------------------------------
CREATE TABLE configuracion (
  clave  VARCHAR(40)  NOT NULL PRIMARY KEY,
  valor  VARCHAR(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB;
