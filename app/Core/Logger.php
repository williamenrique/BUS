<?php
namespace App\Core;

/**
 * Logger centralizado de SITGO.
 * Escribe en storage/logs/app-errors.log con rotación diaria.
 */
class Logger
{
    private static ?string $logDir = null;
    private static ?string $logFile = null;

    /**
     * Inicializa el directorio de logs.
     * Se llama una sola vez (lazy) desde los métodos de escritura.
     */
    private static function init(): void
    {
        if (self::$logDir !== null) return;

        // ROOT_PATH viene de config.php
        $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR;
        self::$logDir  = $root . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
        self::$logFile = self::$logDir . 'app-errors.log';

        if (!is_dir(self::$logDir)) {
            @mkdir(self::$logDir, 0755, true);
        }
    }

    /**
     * Registra un error.
     *
     * @param string $contexto  Ej: 'LOGIN', 'QUERY', 'CONTROLLER', 'APP'
     * @param string $mensaje   Mensaje del error
     * @param array  $extra     Datos adicionales (sql, params, archivo, línea, etc.)
     */
    public static function error(string $contexto, string $mensaje, array $extra = []): void
    {
        self::write('ERROR', $contexto, $mensaje, $extra);
    }

    public static function warning(string $contexto, string $mensaje, array $extra = []): void
    {
        self::write('WARNING', $contexto, $mensaje, $extra);
    }

    public static function info(string $contexto, string $mensaje, array $extra = []): void
    {
        self::write('INFO', $contexto, $mensaje, $extra);
    }

    /**
     * Registra una excepción completa (con archivo, línea y traza).
     */
    public static function exception(string $contexto, \Throwable $e, array $extra = []): void
    {
        $extra = array_merge([
            'archivo' => $e->getFile() . ':' . $e->getLine(),
            'codigo'  => $e->getCode(),
            'traza'   => $e->getTraceAsString(),
        ], $extra);

        self::write('EXCEPTION', $contexto, $e->getMessage(), $extra);
    }

    /**
     * Escritura interna con formato consistente.
     */
    private static function write(string $nivel, string $contexto, string $mensaje, array $extra): void
    {
        self::init();

        $fecha = date('Y-m-d H:i:s');
        $ip    = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $uri   = $_SERVER['REQUEST_URI'] ?? '-';
        $user  = $_SESSION['user']['nick'] ?? ($_SESSION['admin_panel']['nick'] ?? '-');

        $linea  = "============================================================\n";
        $linea .= "[{$fecha}] [{$nivel}] [{$contexto}]\n";
        $linea .= "Usuario: {$user} | IP: {$ip}\n";
        $linea .= "URI: {$uri}\n";
        $linea .= "Mensaje: {$mensaje}\n";

        foreach ($extra as $k => $v) {
            if (is_array($v) || is_object($v)) {
                $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            }
            $linea .= strtoupper($k) . ": " . (string)$v . "\n";
        }

        $linea .= "------------------------------------------------------------\n";

        @file_put_contents(self::$logFile, $linea, FILE_APPEND | LOCK_EX);

        // Rotación simple: si el archivo supera 5 MB, lo rota
        if (@filesize(self::$logFile) > 5 * 1024 * 1024) {
            @rename(self::$logFile, self::$logDir . 'app-errors.' . date('Ymd_His') . '.log');
        }
    }
}