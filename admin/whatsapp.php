<?php
/**
 * Mensaje de WhatsApp de "tu pedido está listo". Lo usan cambiar_estado.php (al marcar listo)
 * y pedidos.php (botón "Avisar al cliente" que queda en cada pedido listo).
 * Sin emojis a propósito: en algunos teléfonos / WhatsApp Web se ven como signos de pregunta.
 */

// LOCAL_DIRECCION: calle, número y ciudad. Vacía = el mensaje sale sin ubicación.
// LOCAL_MAPS_LINK: link de "cómo llegar". Vacío = se arma solo a partir de la dirección.
const LOCAL_DIRECCION = 'Calle 117 N° 644, Avellaneda, Santa Fe';
const LOCAL_MAPS_LINK = 'https://www.google.com/maps/dir/?api=1&destination=-29.1189598,-59.6757768';

function textoUbicacion() {
    if (LOCAL_DIRECCION === '') {
        return '';
    }
    $link = LOCAL_MAPS_LINK !== ''
        ? LOCAL_MAPS_LINK
        : 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode(LOCAL_DIRECCION);

    return "\n\nEstamos en " . LOCAL_DIRECCION . "\nCómo llegar: " . $link;
}

// Si el teléfono no viene con código de país, se lo agregamos (Argentina, celular).
function linkWhatsapp($telefono, $mensaje) {
    $soloNumeros = preg_replace('/\D/', '', $telefono);
    if (substr($soloNumeros, 0, 2) !== '54') {
        $soloNumeros = '549' . $soloNumeros;
    }
    // rawurlencode: espacios como %20 y saltos de línea como %0A (más seguro que "+")
    return 'https://wa.me/' . $soloNumeros . '?text=' . rawurlencode($mensaje);
}

function linkAvisoListo(array $pedido) {
    $primerNombre = explode(' ', trim($pedido['nombre_cliente']))[0];
    $mensaje = "¡Hola {$primerNombre}! Tu pedido {$pedido['codigo']} ya está listo para retirar en Confetti."
             . textoUbicacion()
             . "\n\n¡Te esperamos!";
    return linkWhatsapp($pedido['telefono_cliente'], $mensaje);
}
