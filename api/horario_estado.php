<?php
/**
 * GET /api/horario_estado.php
 * Calcula si el local está abierto ahora mismo, con hasta dos turnos por día
 * (por ejemplo mañana 09:00-13:00 y tarde 17:00-22:00).
 *
 * El cálculo se hace acá, no en el navegador, para que no dependa de la
 * hora de la compu o el celular de quien visita la página.
 */

require 'db.php';

// Siempre horario argentino, sin depender de cómo venga configurado el
// servidor (XAMPP y los hostings suelen traer otra zona horaria por defecto).
date_default_timezone_set('America/Argentina/Buenos_Aires');

$nombresDias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

$diaHoy = ((int) date('N')) - 1; // date('N'): 1=Lunes...7=Domingo → acá 0=Lunes...6=Domingo
$horaAhora = date('H:i');

$stmt = $pdo->prepare('SELECT * FROM horarios_atencion WHERE dia = ?');
$stmt->execute([$diaHoy]);
$hoy = $stmt->fetch();

$abierto = false;
$texto = 'Horario no disponible';

if ($hoy) {
    // Un turno cuenta solo si tiene apertura Y cierre cargados
    $turnos = [];
    if (!$hoy['cerrado']) {
        foreach ([['abre', 'cierra'], ['abre2', 'cierra2']] as [$a, $c]) {
            if ($hoy[$a] && $hoy[$c]) {
                $turnos[] = [substr($hoy[$a], 0, 5), substr($hoy[$c], 0, 5)];
            }
        }
        usort($turnos, fn($x, $y) => strcmp($x[0], $y[0]));
    }

    if (!$turnos) {
        $texto = 'Cerrado hoy';
    } else {
        $proximaApertura = null;

        foreach ($turnos as [$abre, $cierra]) {
            if ($horaAhora >= $abre && $horaAhora < $cierra) {
                $abierto = true;
                $texto = 'Abierto ahora · cierra a las ' . $cierra;
                break;
            }
            if ($horaAhora < $abre && $proximaApertura === null) {
                $proximaApertura = $abre;
            }
        }

        if (!$abierto) {
            $texto = $proximaApertura !== null
                ? 'Cerrado · abre hoy a las ' . $proximaApertura
                : 'Cerrado por hoy';
        }
    }
}

echo json_encode([
    'abierto' => $abierto,
    'texto' => $texto,
    'dia' => $nombresDias[$diaHoy],
    'hora_servidor' => $horaAhora, // para poder verificar a mano qué hora está usando
]);
