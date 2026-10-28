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
        Logger::exception($contexto, $e);
    }
}