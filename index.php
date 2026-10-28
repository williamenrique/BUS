<?php
// ============================================
// SITGO - Front Controller
// ============================================

// ---------- SESIÓN (necesaria para leer el admin panel) ----------
session_name('SITGO_SESS');
session_start();

// ---------- CONTROL DE MODO ----------
$modoConfig = require __DIR__ . '/modo.php';
$modo       = $modoConfig['modo'] ?? 'produccion';

// ¿Es admin del panel? (sesión creada por app/Views/modo/admin.php)
$esAdmin = !empty($_SESSION['admin_panel']['id']);

// Si el modo NO es producción y NO eres admin → servir la vista de modo
if ($modo !== 'produccion' && !$esAdmin) {
    http_response_code(503);
    header('Retry-After: 3600');
    header('Content-Type: text/html; charset=UTF-8');

    $vistaModo = __DIR__ . '/app/Views/modo/';

    if ($modo === 'suspendido') {
        readfile($vistaModo . 'ver.html');
    } else {
        readfile($vistaModo . 'maintenance.html');
    }
    exit;
}
// ---------- FIN CONTROL DE MODO ----------

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