<?php
/**
 * auth.php — Inicio de sesión del panel de administración.
 *
 * Cómo se usa (arriba de cada pantalla del panel, después de conexion.php):
 *
 *     require 'conexion.php';
 *     require __DIR__ . '/auth.php';
 *     $usuarioActual = exigirSesion([ROL_DUENO]);                  // solo el dueño
 *     $usuarioActual = exigirSesion(ROLES_TODOS);                  // dueño y empleado
 *     $usuarioActual = exigirSesion(ROLES_TODOS, true);            // igual, pero para endpoints JSON
 *
 * Si no hay sesión, redirige al login (o responde 401 en JSON). Si el rol no alcanza,
 * muestra "sin permiso" (403). Si una pantalla NO llama a exigirSesion(), queda abierta:
 * por eso conviene que cualquier pantalla nueva del panel empiece con esa línea.
 */

// =====================================================================
// CONFIGURACIÓN
// =====================================================================

// Clave de instalación: protege la pantalla que crea al PRIMER dueño (configurar_inicial.php).
// Cambiala por algo largo y secreto ANTES de usarla. Mientras tenga este valor de ejemplo,
// esa pantalla se niega a funcionar. Una vez creado el primer usuario, la pantalla se bloquea sola.
const CLAVE_INSTALACION = 'clave-confetti-2026';

// Segundos sin usar el panel antes de pedir la clave otra vez (12 horas).
// Mientras haya una pantalla abierta consultando (ej: pedidos.php), cuenta como uso.
const SESION_INACTIVIDAD = 43200;

const ROL_DUENO = 'dueño';
const ROL_EMPLEADO = 'empleado';
const ROLES_TODOS = [ROL_DUENO, ROL_EMPLEADO];

// Intentos fallidos permitidos en 15 minutos antes de bloquear el ingreso un rato:
const LIMITE_POR_PAR = 5;        // mismo usuario desde la misma conexión
const LIMITE_POR_USUARIO = 20;   // mismo usuario desde cualquier lado (ataque repartido)
const LIMITE_POR_IP = 30;        // misma conexión probando cualquier usuario

// Hash de una clave al azar. Se usa para tardar lo mismo cuando el usuario no existe,
// así no se puede averiguar qué usuarios existen midiendo cuánto tarda la respuesta.
const HASH_FALSO = '$2y$10$HX.KDEsB5PpflFyR315c9ODQa2Ul.Yqg9.nuek01c2y4Urf.yZyve';

// =====================================================================
// SESIÓN
// =====================================================================

function esHttps(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function iniciarSesionSegura(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Carpeta propia para las sesiones: así otras aplicaciones del mismo servidor
    // no las borran con su limpieza automática (que suele ser de 24 minutos).
    $ruta = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'confetti_sesiones';
    if (!is_dir($ruta)) {
        @mkdir($ruta, 0700, true);
    }
    if (is_dir($ruta) && is_writable($ruta)) {
        session_save_path($ruta);
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string) (SESION_INACTIVIDAD + 3600));

    // La cookie solo viaja hacia la carpeta del panel (no hacia la tienda pública ni la API)
    $carpeta = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

    session_name('confetti_admin');
    session_set_cookie_params([
        'lifetime' => 0,                      // se borra al cerrar el navegador
        'path'     => $carpeta === '' ? '/' : $carpeta,
        'secure'   => esHttps(),              // en https, nunca viaja por conexión sin cifrar
        'httponly' => true,                   // JavaScript no puede leerla
        'samesite' => 'Lax',                  // otros sitios no pueden mandarla en un formulario
    ]);
    session_start();
}

function reabrirSesion(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        iniciarSesionSegura();
    }
}

function cabecerasSeguras(): void {
    header('Cache-Control: no-store, max-age=0');        // el botón "atrás" no muestra el panel tras salir
    header('X-Frame-Options: DENY');                     // nadie puede meter el panel dentro de otra página
    header("Content-Security-Policy: frame-ancestors 'none'");
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

function respuestaJson(int $codigo, array $payload): void {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    header_remove('Access-Control-Allow-Origin');        // los endpoints del panel no son públicos
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Defensa contra pedidos falsificados desde otros sitios (CSRF): en toda petición POST,
 * el sitio de origen tiene que ser este mismo. Se suma a la cookie SameSite=Lax.
 */
function verificarOrigen(bool $json = false): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $origen = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origen === '') {
        $origen = $_SERVER['HTTP_REFERER'] ?? '';
    }
    if ($origen === '') {
        return; // el navegador no mandó ninguno: queda la protección de la cookie SameSite
    }

    $partes = parse_url($origen);
    $destino = strtolower($partes['host'] ?? '');
    if ($destino !== '' && isset($partes['port'])) {
        $destino .= ':' . $partes['port'];
    }

    $propios = [strtolower($_SERVER['HTTP_HOST'] ?? '')];
    $nombre = strtolower($_SERVER['SERVER_NAME'] ?? '');
    if ($nombre !== '') {
        $propios[] = $nombre;
        $propios[] = $nombre . ':' . ($_SERVER['SERVER_PORT'] ?? '');
    }

    if ($destino === '' || !in_array($destino, $propios, true)) {
        if ($json) {
            respuestaJson(403, ['error' => 'Solicitud rechazada: el origen no es este sitio.']);
        }
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Solicitud rechazada: el origen no es este sitio.';
        exit;
    }
}

function huellaClave(string $hash): string {
    // Si cambia la contraseña, cambia la huella y las sesiones que ya estaban abiertas dejan de valer
    return substr(hash('sha256', $hash), 0, 32);
}

/** Devuelve el usuario de la sesión actual, o null si no hay sesión válida. */
function cargarUsuarioDeSesion(PDO $pdo): ?array {
    if (empty($_SESSION['uid'])) {
        return null;
    }
    if (time() - (int) ($_SESSION['ultimo'] ?? 0) > SESION_INACTIVIDAD) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT id, nombre, usuario, rol, activo, password_hash FROM usuarios WHERE id = ?');
    $stmt->execute([$_SESSION['uid']]);
    $u = $stmt->fetch();

    // Se revisa contra la base en cada pedido: si desactivan al usuario o le cambian la clave,
    // la sesión deja de valer al instante.
    if (!$u || !$u['activo']) {
        return null;
    }
    if (!hash_equals((string) ($_SESSION['huella'] ?? ''), huellaClave($u['password_hash']))) {
        return null;
    }

    unset($u['password_hash']);
    return $u;
}

function abrirSesionDeUsuario(int $usuarioId, string $hash): void {
    reabrirSesion();
    session_regenerate_id(true);   // nuevo identificador al ingresar (evita "fijación de sesión")
    $_SESSION = [
        'uid'    => $usuarioId,
        'huella' => huellaClave($hash),
        'ultimo' => time(),
    ];
}

function cerrarSesionActual(): void {
    reabrirSesion();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $p['path'],
            'secure'   => $p['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
}

function destinoValido(?string $destino): ?string {
    // Solo pantallas del propio panel ("pedidos.php", "ventas.php?periodo=mes"): nada de direcciones externas
    if ($destino !== null && preg_match('/^[A-Za-z0-9_]+\.php(\?[A-Za-z0-9_%=&.+\-]*)?$/', $destino)) {
        return $destino;
    }
    return null;
}

function pantallaSinPermiso(array $usuario): void {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    $nombre = htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="es-AR" translate="no"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Sin permiso</title></head>'
       . '<body style="font-family:system-ui,sans-serif;background:#FFFBF3;color:#1F2A44;padding:48px 24px;">'
       . '<div style="max-width:460px;background:#fff;border-radius:14px;padding:26px;">'
       . '<h1 style="font-size:1.3rem;margin:0 0 8px;">No tenés permiso para esta sección</h1>'
       . '<p style="color:#5B6478;margin:0 0 18px;">Tu usuario (' . $nombre . ') no puede entrar acá. Si lo necesitás, pedile al dueño que lo habilite.</p>'
       . '<a href="index.php" style="color:#1B4A9C;">← Volver al panel</a></div></body></html>';
    exit;
}

/**
 * Pide sesión iniciada y un rol permitido. Por defecto, solo el dueño.
 * Devuelve los datos del usuario (id, nombre, usuario, rol).
 */
function exigirSesion(array $roles = [ROL_DUENO], bool $json = false): array {
    global $pdo;

    cabecerasSeguras();
    if ($json) {
        header_remove('Access-Control-Allow-Origin');
    }
    iniciarSesionSegura();
    verificarOrigen($json);

    $usuario = cargarUsuarioDeSesion($pdo);

    if (!$usuario) {
        if ($json) {
            session_write_close();
            respuestaJson(401, ['error' => 'Tu sesión venció. Volvé a ingresar al panel.', 'sesion' => false]);
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $ruta = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
            $consulta = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
            $_SESSION['volver'] = destinoValido($ruta . ($consulta ? '?' . $consulta : ''));
        }
        session_write_close();
        header('Location: login.php');
        exit;
    }

    if (!in_array($usuario['rol'], $roles, true)) {
        session_write_close();
        if ($json) {
            respuestaJson(403, ['error' => 'Tu usuario no tiene permiso para esta acción.']);
        }
        pantallaSinPermiso($usuario);
    }

    $_SESSION['ultimo'] = time();
    session_write_close();   // se libera la sesión enseguida para que las pantallas no se bloqueen entre sí

    $GLOBALS['USUARIO_ACTUAL'] = $usuario;
    return $usuario;
}

function esDueno(?array $usuario): bool {
    return $usuario !== null && $usuario['rol'] === ROL_DUENO;
}

/** Texto "👤 Nombre (rol) · Mi clave · Salir" para poner a la derecha del menú de cada pantalla. */
function barraSesionHtml(): string {
    $u = $GLOBALS['USUARIO_ACTUAL'] ?? null;
    if (!$u) {
        return '';
    }
    $nombre = htmlspecialchars($u['nombre'], ENT_QUOTES, 'UTF-8');
    $rol = htmlspecialchars($u['rol'], ENT_QUOTES, 'UTF-8');
    return '<span style="float:right; font-size:0.82rem;">👤 ' . $nombre . ' (' . $rol . ') · '
         . '<a href="mi_clave.php" style="color:#5B6478; text-decoration:underline;">Mi clave</a> · '
         . '<form method="post" action="logout.php" style="display:inline; margin:0;">'
         . '<button type="submit" style="background:none; border:none; padding:0; font:inherit; color:#5B6478; cursor:pointer; text-decoration:underline;">Salir</button>'
         . '</form></span>';
}

// =====================================================================
// CONTRASEÑAS E INTENTOS FALLIDOS
// =====================================================================

/** Devuelve un mensaje de error si la clave no sirve, o null si está bien. */
function validarClaveNueva(string $clave, string $usuario = ''): ?string {
    if (strlen($clave) < 8) {
        return 'La contraseña tiene que tener al menos 8 caracteres.';
    }
    if (strlen($clave) > 72) {
        return 'La contraseña es demasiado larga (máximo 72 caracteres).';
    }
    if ($usuario !== '' && strtolower($clave) === strtolower($usuario)) {
        return 'La contraseña no puede ser igual al usuario.';
    }
    return null;
}

function ipCliente(): string {
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function claveDeIntentos(string $usuario): string {
    return substr(strtolower(trim($usuario)), 0, 60);
}

function ingresoBloqueado(PDO $pdo, string $usuario, string $ip): bool {
    $usuario = claveDeIntentos($usuario);
    $stmt = $pdo->prepare(
        "SELECT COALESCE(SUM(usuario = ? AND ip = ?), 0) AS par,
                COALESCE(SUM(usuario = ?), 0) AS por_usuario,
                COALESCE(SUM(ip = ?), 0) AS por_ip
         FROM login_intentos
         WHERE creado_en > DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
    );
    $stmt->execute([$usuario, $ip, $usuario, $ip]);
    $f = $stmt->fetch();

    return (int) $f['par'] >= LIMITE_POR_PAR
        || (int) $f['por_usuario'] >= LIMITE_POR_USUARIO
        || (int) $f['por_ip'] >= LIMITE_POR_IP;
}

function registrarFallo(PDO $pdo, string $usuario, string $ip): void {
    $pdo->prepare('INSERT INTO login_intentos (usuario, ip) VALUES (?, ?)')
        ->execute([claveDeIntentos($usuario), $ip]);

    if (random_int(1, 20) === 1) {   // de vez en cuando se limpian los registros viejos
        $pdo->exec('DELETE FROM login_intentos WHERE creado_en < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }
}

function limpiarFallos(PDO $pdo, string $usuario): void {
    $pdo->prepare('DELETE FROM login_intentos WHERE usuario = ?')->execute([claveDeIntentos($usuario)]);
}
