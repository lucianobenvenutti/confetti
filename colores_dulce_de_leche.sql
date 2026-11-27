-- =========================================================
-- Confetti — Colores de las categorías en tonos dulce de leche / chocolate
-- OPCIONAL. Ejecutar en phpMyAdmin, en tu base, si querés que las categorías que ya cargaste
-- (Copetín, Panificados, etc.) usen la paleta cálida de la tienda.
-- Cambia SOLO el color de cada categoría. No toca productos, precios ni stock.
-- Después podés ajustar cualquiera a mano en Panel → Categorías.
-- =========================================================
UPDATE categorias
SET color_acento = ELT(1 + MOD(id, 6), '#A8601F', '#6B4026', '#96700F', '#A04855', '#8A6240', '#5F8A4B');
