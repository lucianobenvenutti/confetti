<?php
/**
 * GET /api/datos_pago.php
 * Devuelve los datos para pagar por transferencia que cargó el dueño desde el panel
 * ("Datos de pago"). Si no cargó ninguno, devuelve transferencia = null y la tienda
 * simplemente no ofrece esa opción.
 */
require 'db.php';

$datos = null;
try {
    $filas = $pdo->query("SELECT clave, valor FROM configuracion WHERE clave LIKE 'transf\\_%'")->fetchAll();
    $c = [];
    foreach ($filas as $f) $c[$f['clave']] = trim($f['valor']);

    if (($c['transf_alias'] ?? '') !== '' || ($c['transf_cbu'] ?? '') !== '') {
        $datos = [
            'titular' => $c['transf_titular'] ?? '',
            'banco'   => $c['transf_banco'] ?? '',
            'alias'   => $c['transf_alias'] ?? '',
            'cbu'     => $c['transf_cbu'] ?? '',
            'nota'    => $c['transf_nota'] ?? '',
        ];
    }
} catch (PDOException $e) {
    // la tabla todavía no existe (falta correr migracion_pagos.sql): sin transferencia
}

echo json_encode(['transferencia' => $datos]);
