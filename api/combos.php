<?php
/**
 * GET /api/combos.php
 * Devuelve los combos fijos activos, cada uno con el detalle de qué
 * productos y cantidades lo componen (para mostrarlo como el panel
 * de "armá tu picada" que ya tenemos en el prototipo visual).
 */

require 'db.php';

$combos = $pdo->query(
    'SELECT id, nombre, descripcion, precio, imagen
     FROM combos
     WHERE activo = 1
     ORDER BY nombre ASC'
)->fetchAll();

if ($combos) {
    // Traigo la composición de todos los combos en una sola consulta
    $ids = array_column($combos, 'id');
    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare(
        "SELECT cp.combo_id, cp.cantidad, pr.id AS producto_id, pr.nombre, pr.tipo_venta
         FROM combo_productos cp
         JOIN productos pr ON pr.id = cp.producto_id
         WHERE cp.combo_id IN ($marcadores)"
    );
    $stmt->execute($ids);
    $items = $stmt->fetchAll();

    // Agrupo los ítems bajo cada combo
    foreach ($combos as &$combo) {
        $combo['items'] = array_values(array_filter($items, function ($item) use ($combo) {
            return $item['combo_id'] == $combo['id'];
        }));
    }
}

echo json_encode($combos);
