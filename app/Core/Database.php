<?php
namespace App\Core;

use PDO;
use PDOException;

class Database{

    private static ?PDO $instance = null;

    public static function getConnection(): PDO{
        if (self::$instance === null){
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            try{
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e){
                self::logError('CONEXIÓN', $e);
                throw new DatabaseException(
                    'No se pudo conectar a la base de datos.',
                    $e->getCode(),
                    $e->getMessage()
                );
            }
        }
        return self::$instance;
    }

    /**
     * Registra un error de BD en el archivo de log.
     */
    public static function logError(string $contexto, \Throwable $e): void{
        try{
            $dir = ROOT_PATH . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
            if (!is_dir($dir)) @mkdir($dir, 0755, true);

            $file = $dir . 'db-errors.log';
            $line = sprintf(
                "[%s] [%s] %s\n  SQLSTATE: %s\n  Mensaje: %s\n  Archivo: %s:%d\n%s\n",
                date('Y-m-d H:i:s'),
                $contexto,
                $e->getMessage(),
                method_exists($e, 'getSqlState') ? $e->getSqlState() : ($e->getCode() ?? ''),
                method_exists($e, 'getRawMessage') ? $e->getRawMessage() : $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                str_repeat('-', 60)
            );
            @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $ignored){}
    }
}