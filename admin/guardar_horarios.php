<?php
require 'conexion.php';

$nombresDias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

// Si algo está mal, mostramos el motivo con un botón "Volver" que usa el historial
// del navegador: así no se pierde lo que ya se había completado en los otros días.
function error($mensaje) {
    echo '<!DOCTYPE html><html lang="es-AR"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
       . '<title>Revisá el horario</title></head>'
       . '<body style="font-family:system-ui,sans-serif;background:#FFFBF3;color:#1F2A44;padding:40px 24px;">'
       . '<div style="max-width:520px;background:#FBEAE8;color:#E8483C;border:1px solid #f3c9c4;border-radius:12px;padding:16px 20px;">'
       . htmlspecialchars($mensaje) . '</div>'
       . '<p><a href="javascript:history.back()" style="color:#1B4A9C;">← Volver y corregir</a></p>'
       . '</body></html>';
    exit;
}

$cerradoPost = $_POST['cerrado'] ?? [];
$filas = [];

foreach (range(0, 6) as $dia) {
    $cerrado = isset($cerradoPost[$dia]) ? 1 : 0;

    $v = [];
    foreach (['abre', 'cierra', 'abre2', 'cierra2'] as $campo) {
        $valor = trim($_POST[$campo][$dia] ?? '');
        $v[$campo] = ($cerrado || $valor === '') ? null : $valor;
    }

    if (!$cerrado) {
        $nombre = $nombresDias[$dia];

        foreach ([['abre', 'cierra', 'de la mañana'], ['abre2', 'cierra2', 'de la tarde']] as [$a, $c, $turno]) {
            if (($v[$a] === null) !== ($v[$c] === null)) {
                error("$nombre: en el turno $turno completá la apertura y el cierre, o dejá los dos vacíos.");
            }
            if ($v[$a] !== null && $v[$c] <= $v[$a]) {
                error("$nombre: en el turno $turno, el cierre tiene que ser después de la apertura.");
            }
        }

        if ($v['cierra'] !== null && $v['abre2'] !== null && $v['abre2'] < $v['cierra']) {
            error("$nombre: la tarde tiene que abrir después del cierre del mediodía.");
        }
    }

    $filas[$dia] = [$v['abre'], $v['cierra'], $v['abre2'], $v['cierra2'], $cerrado];
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'UPDATE horarios_atencion SET abre = ?, cierra = ?, abre2 = ?, cierra2 = ?, cerrado = ? WHERE dia = ?'
    );
    foreach ($filas as $dia => $datos) {
        $stmt->execute([...$datos, $dia]);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error('No se pudo guardar el horario: ' . $e->getMessage());
}

header('Location: horarios.php?msg=' . urlencode('Horario guardado.') . '&tipo=ok');
