<?php
/**
 * GET /api/categorias.php
 * Devuelve las categorías activas, ordenadas para mostrar como "chips".
 */

require 'db.php';

$stmt = $pdo->query(
    'SELECT id, nombre, color_acento, orden
     FROM categorias
     WHERE activo = 1
     ORDER BY orden ASC, nombre ASC'
);

$categorias = $stmt->fetchAll();

echo json_encode($categorias);
