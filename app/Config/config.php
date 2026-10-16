<?php
// ============================================
// SITGO - Configuración Global
// ============================================

// Detección automática de URLROOT (patrón multiservicio)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$root       = ($scriptName === '/' || $scriptName === '\\' || $scriptName === '.') ? '' : $scriptName;
$root       = rtrim($root, '/public_html');

define('URLROOT',    $protocol . '://' . $host . $root);
define('BASE_URL',   URLROOT . '/');
define('ASSETS_URL', URLROOT . '/');

// Entorno
$isLocal = (
    str_contains($host, 'localhost') ||
    str_contains($host, '127.0.0.1') ||
    str_ends_with($host, '.test') ||
    str_ends_with($host, '.local') ||
    preg_match('/^192\.168\.|^10\.|^172\./', $host)
);
define('ENVIRONMENT', $isLocal ? 'development' : 'production');

// Rutas físicas
define('APPROOT',    dirname(__DIR__));                              // .../BUS/app
define('ROOT_PATH',  dirname(APPROOT) . DIRECTORY_SEPARATOR);        // .../BUS/
define('APP_PATH',   APPROOT . DIRECTORY_SEPARATOR);                 // .../BUS/app/
define('VIEWS_PATH', APP_PATH . 'Views' . DIRECTORY_SEPARATOR);
define('PUBLIC_PATH',ROOT_PATH . 'public_html' . DIRECTORY_SEPARATOR);

// Base de datos
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'busyaracuydata');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Sesión
define('SESSION_NAME', 'SITGO_SESS');
define('SESSION_LIFETIME', 3600 * 8);
define('SINGLE_SESSION', true);

// Legacy AES
define('LEGACY_METHOD',     'AES-256-CBC');
define('LEGACY_SECRET_KEY', '$busYaracuy');
define('LEGACY_SECRET_IV',  '101712');

// Multi-tenant
define('DEFAULT_INSTITUCION', 1);
// ============================================
// Recuperación de contraseña
// ============================================
define('DEPARTAMENTO_SISTEMA', 1);   // id del departamento de Sistemas
define('PASSWORD_DEFAULT_RESET', '123456');
// Nombres
define('SITENAME', 'SITGO');
define('SITGO_APP_NAME', 'SITGO');
define('SITGO_APP_FULLNAME', 'Sistema Integral de Transporte y Gestión Operativa');
define('SITGO_VERSION', '1.0.0');

date_default_timezone_set('America/Caracas');

if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}