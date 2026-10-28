<?php
// ============================================
// SITGO - Configuración Global (WEB / PRODUCCIÓN)
// ============================================

// Overrides locales opcionales (en la web normalmente NO existe)
$__sitgo_local = __DIR__ . '/config.local.php';
if (file_exists($__sitgo_local)) {
    require_once $__sitgo_local;
}

// ============================================
// Detección de protocolo (soporta proxy / CDN)
// ============================================
$isHttps = (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
    (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
);
$protocol = $isHttps ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Detección automática de URLROOT
$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$root       = ($scriptName === '/' || $scriptName === '\\' || $scriptName === '.') ? '' : $scriptName;
$root       = rtrim($root, '/public_html');

if (!defined('URLROOT'))    define('URLROOT',    $protocol . '://' . $host . $root);
if (!defined('BASE_URL'))   define('BASE_URL',   URLROOT . '/');
if (!defined('ASSETS_URL')) define('ASSETS_URL', URLROOT . '/');

// ============================================
// Entorno
// ============================================
$isLocal = (
    str_contains($host, 'localhost') ||
    str_contains($host, '127.0.0.1') ||
    str_ends_with($host, '.test') ||
    str_ends_with($host, '.local') ||
    preg_match('/^192\.168\.|^10\.|^172\./', $host)
);
if (!defined('ENVIRONMENT')) define('ENVIRONMENT', $isLocal ? 'development' : 'production');

// ============================================
// Rutas físicas
// ============================================
if (!defined('APPROOT'))    define('APPROOT',    dirname(__DIR__));
if (!defined('ROOT_PATH'))  define('ROOT_PATH',  dirname(APPROOT) . DIRECTORY_SEPARATOR);
if (!defined('APP_PATH'))   define('APP_PATH',   APPROOT . DIRECTORY_SEPARATOR);
if (!defined('VIEWS_PATH')) define('VIEWS_PATH', APP_PATH . 'Views' . DIRECTORY_SEPARATOR);
if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', ROOT_PATH);   // ← WEB: raíz ya es pública
if (!defined('ASSETS_PATH')) define('ASSETS_PATH', ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR);   // ← NUEVO

// ============================================
// Base de datos · PRODUCCIÓN
// ============================================
if (!defined('DB_HOST'))    define('DB_HOST',    getenv('SITGO_DB_HOST') ?: 'localhost');
if (!defined('DB_PORT'))    define('DB_PORT',    getenv('SITGO_DB_PORT') ?: '3306');
if (!defined('DB_NAME'))    define('DB_NAME',    getenv('SITGO_DB_NAME') ?: 'busyarac_data');
if (!defined('DB_USER'))    define('DB_USER',    getenv('SITGO_DB_USER') ?: 'busyarac_data');
if (!defined('DB_PASS'))    define('DB_PASS',    getenv('SITGO_DB_PASS') ?: '+bus2026+');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// ============================================
// Sesión
// ============================================
if (!defined('SESSION_NAME'))     define('SESSION_NAME',     'SITGO_SESS');
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 3600 * 8);
if (!defined('SINGLE_SESSION'))   define('SINGLE_SESSION',   true);

// ============================================
// Legacy AES
// ============================================
if (!defined('LEGACY_METHOD'))     define('LEGACY_METHOD',     'AES-256-CBC');
if (!defined('LEGACY_SECRET_KEY')) define('LEGACY_SECRET_KEY', getenv('SITGO_LEGACY_KEY') ?: '$busYaracuy');
if (!defined('LEGACY_SECRET_IV'))  define('LEGACY_SECRET_IV',  getenv('SITGO_LEGACY_IV')  ?: '101712');

// ============================================
// Multi-institución
// ============================================
if (!defined('DEFAULT_INSTITUCION')) define('DEFAULT_INSTITUCION', 1);
if (!defined('MULTI_INSTITUCION'))   define('MULTI_INSTITUCION',   true);

// ============================================
// Departamentos
// ============================================
if (!defined('DEPARTAMENTO_SISTEMA'))        define('DEPARTAMENTO_SISTEMA',        1);
if (!defined('DEPARTAMENTO_ESTACION'))       define('DEPARTAMENTO_ESTACION',       2);
if (!defined('DEPARTAMENTO_ADMINISTRACION')) define('DEPARTAMENTO_ADMINISTRACION', 3);
if (!defined('DEPARTAMENTO_ALMACEN'))        define('DEPARTAMENTO_ALMACEN',        4);
if (!defined('DEPARTAMENTO_BIENES'))         define('DEPARTAMENTO_BIENES',         5);
if (!defined('DEPARTAMENTO_OPERACIONES'))    define('DEPARTAMENTO_OPERACIONES',    6);
if (!defined('DEPARTAMENTO_COMPRAS'))        define('DEPARTAMENTO_COMPRAS',        7);
if (!defined('DEPARTAMENTO_TALLER'))         define('DEPARTAMENTO_TALLER',         8);
if (!defined('DEPARTAMENTO_TESORERIA'))      define('DEPARTAMENTO_TESORERIA',      9);

if (!defined('PASSWORD_DEFAULT_RESET')) define('PASSWORD_DEFAULT_RESET', '123456');

// ============================================
// Nombres / versión
// ============================================
if (!defined('SITENAME'))            define('SITENAME',            'SITGO');
if (!defined('SITGO_APP_NAME'))      define('SITGO_APP_NAME',      'SITGO');
if (!defined('SITGO_APP_FULLNAME'))  define('SITGO_APP_FULLNAME',  'Sistema Integral de Transporte y Gestión Operativa');
if (!defined('SITGO_VERSION'))       define('SITGO_VERSION',       '3.0.0');

// ============================================
// Zona horaria
// ============================================
date_default_timezone_set('America/Caracas');

// ============================================
// Errores · development vs production
// ============================================
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);

    $__logDir = ROOT_PATH . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
    if (!is_dir($__logDir)) {
        @mkdir($__logDir, 0755, true);
    }
    ini_set('error_log', $__logDir . 'php-errors.log');
}