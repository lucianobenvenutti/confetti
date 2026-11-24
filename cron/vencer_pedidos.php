<?php
/**
 * cron/vencer_pedidos.php
 *
 * Pensado para correr solo, sin que nadie abra el panel:
 * en Hostinger se configura como Cron Job (cPanel → Cron Jobs) para que
 * pegue contra esta URL cada 15-30 minutos, algo como:
 *   wget -q -O /dev/null "https://tudominio.com/cron/vencer_pedidos.php?clave=TU_CLAVE_SECRETA"
 *
 * En local (XAMPP) no hay cron de verdad, así que mientras probás alcanza
 * con abrir esta URL a mano en el navegador para ver que funcione:
 *   http://localhost/confetti/cron/vencer_pedidos.php?clave=TU_CLAVE_SECRETA
 *
 * IMPORTANTE: cambiá $CLAVE_CRON antes de subir esto a Hostinger. Sin una
 * clave real, cualquiera que encuentre esta URL podría llamarla — no hace
 * daño grave (solo dispara la misma liberación que ya pasa sola a las 24hs),
 * pero mejor no dejarla abierta.
 */

// La clave se escribe en config.php (clave_cron), que NO se sube a GitHub.
$__cfg = is_file(__DIR__ . '/../config.php') ? (require __DIR__ . '/../config.php') : [];
$CLAVE_CRON = (string) ($__cfg['clave_cron'] ?? 'cambiar-esto-por-algo-secreto');

// Mientras la clave siga siendo la de ejemplo (que está publicada en GitHub), no funciona.
if ($CLAVE_CRON === 'cambiar-esto-por-algo-secreto' || !hash_equals($CLAVE_CRON, (string) ($_GET['clave'] ?? ''))) {
    http_response_code(403);
    echo 'Acceso denegado.';
    exit;
}

require __DIR__ . '/../admin/conexion.php';
require __DIR__ . '/../api/vencimientos.php';

header('Content-Type: text/plain; charset=utf-8');

$cancelados = liberarPedidosVencidos($pdo);

if ($cancelados) {
    echo 'Pedidos liberados por vencimiento: ' . implode(', ', $cancelados) . "\n";
} else {
    echo "Sin pedidos vencidos por ahora.\n";
}
