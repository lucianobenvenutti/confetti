<?php
/**
 * Precio con oferta. Lo usan la tienda (productos.php), los pedidos web (crear_pedido.php),
 * el mostrador (registrar_venta.php) y el panel de ofertas: así TODOS calculan igual.
 *
 * Un producto está "en oferta" si tiene oferta_tipo + oferta_valor válidos:
 *   porcentaje: entre 1 y 90   -> precio * (1 - valor/100), redondeado a pesos enteros
 *   monto:      mayor a 0 y menor al precio -> precio - valor
 * Si el descuento ya no tiene sentido (por ejemplo se bajó el precio y el monto quedó
 * igual o mayor), se ignora y se cobra el precio normal: nunca sale un precio en cero o negativo.
 */

// Devuelve el precio final por unidad (o por kg). Sin oferta válida, devuelve el precio normal.
function precioConOferta(array $p): float {
    $precio = (float) $p['precio'];
    $tipo   = $p['oferta_tipo'] ?? null;
    $valor  = isset($p['oferta_valor']) ? (float) $p['oferta_valor'] : 0.0;

    if ($tipo === 'porcentaje' && $valor >= 1 && $valor <= 90) {
        return max(1.0, round($precio * (1 - $valor / 100)));
    }
    if ($tipo === 'monto' && $valor > 0 && $valor < $precio) {
        return round($precio - $valor);
    }
    return $precio;
}

function estaEnOferta(array $p): bool {
    return precioConOferta($p) < (float) $p['precio'];
}
