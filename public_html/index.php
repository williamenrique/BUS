<?php
// ============================================
// SITGO - Front Controller
// ============================================
session_name('SITGO_SESS');
session_start();

require_once dirname(__DIR__) . '/app/Config/config.php';
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