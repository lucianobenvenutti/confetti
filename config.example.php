<?php
/**
 * Datos de conexión a la base de datos — UNA sola vez, para todo el sistema.
 *
 * Cómo usar:
 *   1. Copiá este archivo con el nombre  config.php  (en esta misma carpeta).
 *   2. Completá los datos de tu base (en Hostinger: panel → Bases de datos).
 *   3. config.php NO se sube a GitHub (está en .gitignore): así tu clave no queda
 *      guardada en el repositorio.
 *
 * Si config.php no existe, se usan los datos de XAMPP (root, sin clave), así en tu
 * compu sigue funcionando sin hacer nada.
 */
return [
    'host'      => 'localhost',
    'nombre_bd' => 'confetti',
    'usuario'   => 'root',
    'clave'     => '',
    // true solo mientras probás en tu compu: muestra el detalle de los errores de conexión.
    // En el sitio online dejalo en false.
    'debug'     => false,

    // Clave de un solo uso para crear al primer dueño en admin/configurar_inicial.php.
    // Una frase larga tuya (12 caracteres o más). No hace falta recordarla: la leés acá.
    'clave_instalacion' => '',

    // Clave del cron que libera pedidos vencidos (cron/vencer_pedidos.php?clave=...).
    'clave_cron' => '',
];
