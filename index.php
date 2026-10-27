<?php
// ============================================
// SITGO - Front Controller
// ============================================

// ---------- CONTROL DE MODO ----------
$modoConfig = require __DIR__ . '/modo.php';
$modo       = $modoConfig['modo'] ?? 'produccion';

// Cookie de admin: si existe, el visitante puede entrar siempre
$esAdmin = isset($_COOKIE['sitgo_admin']) && $_COOKIE['sitgo_admin'] === '1';

// Tokens de activación / desactivación (cámbialos si quieres)
$TOKEN_ADMIN_ON  = 'ADMIN-ON-2026';
$TOKEN_ADMIN_OFF = 'ADMIN-OFF-2026';

// Rutas para activar/desactivar la cookie de admin
if (isset($_GET['admin_on']) && $_GET['admin_on'] === $TOKEN_ADMIN_ON) {
    setcookie('sitgo_admin', '1', [
        'expires'  => time() + (86400 * 30), // 30 días
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    header('Location: ' . (($_SERVER['REQUEST_URI'] ?? '/') ? '/' : '/'));
    exit;
}
if (isset($_GET['admin_off']) && $_GET['admin_off'] === $TOKEN_ADMIN_OFF) {
    setcookie('sitgo_admin', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    header('Location: /');
    exit;
}

// Si el modo NO es producción y NO eres admin, servir la página correspondiente
if ($modo !== 'produccion' && !$esAdmin) {
    http_response_code(503);
    header('Retry-After: 3600');
    header('Content-Type: text/html; charset=UTF-8');

    if ($modo === 'suspendido') {
        readfile(__DIR__ . '/ver.html');
    } else {
        readfile(__DIR__ . '/maintenance.html');
    }
    exit;
}
// ---------- FIN CONTROL DE MODO ----------

session_name('SITGO_SESS');
session_start();

require_once __DIR__ . '/app/Config/config.php';
require_once APP_PATH . 'Helpers/helpers.php';

spl_autoload_register(function ($class) {
    $prefix  = 'App\\';
    $baseDir = APP_PATH;
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require $file;
});

use App\Core\App;
(new App())->run();