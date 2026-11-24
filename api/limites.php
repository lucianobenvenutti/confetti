<?php
/**
 * Frenos simples contra abuso en la parte pública (pedidos y consulta de seguimiento).
 * Cuentan usos por dirección IP en la tabla api_limites, con el reloj de MySQL.
 *
 * Si la tabla todavía no existe (falta correr migracion_seguridad.sql), NO frena
 * nada: es mejor que la tienda siga funcionando a que se rompa.
 */

function ipVisitante(): string {
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

/** ¿Esta IP ya hizo $max usos de $accion en los últimos $ventanaSeg segundos? */
function limiteSuperado(PDO $pdo, string $accion, int $max, int $ventanaSeg): bool {
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM api_limites
             WHERE accion = ? AND ip = ? AND creado_en > DATE_SUB(NOW(), INTERVAL ? SECOND)'
        );
        $stmt->execute([$accion, ipVisitante(), $ventanaSeg]);
        return (int) $stmt->fetchColumn() >= $max;
    } catch (PDOException $e) {
        return false;
    }
}

/** Anota un uso. De vez en cuando limpia lo viejo para que la tabla no crezca. */
function registrarUso(PDO $pdo, string $accion): void {
    try {
        $pdo->prepare('INSERT INTO api_limites (accion, ip) VALUES (?, ?)')
            ->execute([$accion, ipVisitante()]);
        if (random_int(1, 100) === 1) {
            $pdo->exec('DELETE FROM api_limites WHERE creado_en < DATE_SUB(NOW(), INTERVAL 1 DAY)');
        }
    } catch (PDOException $e) {
        // sin tabla: ignorar
    }
}

function demasiadosIntentos(string $mensaje): void {
    http_response_code(429);
    header('Retry-After: 600');
    echo json_encode(['error' => $mensaje]);
    exit;
}
