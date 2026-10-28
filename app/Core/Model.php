<?php
namespace App\Core;

use PDO;
use PDOException;

abstract class Model{

    protected PDO $db;

    public function __construct(){
        $this->db = Database::getConnection();
    }

    /**
     * Ejecuta una query preparada.
     * @throws DatabaseException
     */
    protected function query(string $sql, array $params = []): \PDOStatement{
        try{
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e){
            Logger::exception('QUERY', $e, [
                'sql'    => $sql,
                'params' => $params,
            ]);
            throw new DatabaseException(
                $this->mensajeAmigable($e),
                $e->getCode(),
                $e->getMessage()
            );
        }
    }

    /**
     * Devuelve una sola fila o null.
     * @throws DatabaseException
     */
    protected function fetchOne(string $sql, array $params = []): ?array{
        $row = $this->query($sql, $params)->fetch();
        return $row ?: null;
    }

    /**
     * Devuelve todas las filas.
     * @throws DatabaseException
     */
    protected function fetchAll(string $sql, array $params = []): array{
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Ejecuta un UPDATE/DELETE/INSERT y devuelve si afectó filas.
     * @throws DatabaseException
     */
    protected function execute(string $sql, array $params = []): bool{
        return $this->query($sql, $params)->rowCount() > 0;
    }

    protected function lastInsertId(): string{
        return $this->db->lastInsertId();
    }

    /**
     * Traduce el error SQL a un mensaje amigable para el usuario.
     */
    private function mensajeAmigable(PDOException $e): string{
        $code = (string)$e->getCode();
        $msg  = strtolower($e->getMessage());

        // Errores comunes de MySQL
        return match (true){
            // 23000 - Duplicado / FK
            str_contains($msg, 'duplicate entry')             => 'Ya existe un registro con estos datos.',
            str_contains($msg, 'foreign key constraint fails') => 'No se puede completar: el registro está en uso.',
            str_contains($msg, 'cannot delete or update a parent row') => 'No se puede eliminar: hay registros asociados.',

            // 42S02 - Tabla no existe
            str_contains($msg, "doesn't exist")                => 'Error interno del sistema. Contacta al administrador.',

            // 42S22 - Columna no existe
            str_contains($msg, 'unknown column')               => 'Error interno del sistema. Contacta al administrador.',

            // 22001 - String muy largo
            str_contains($msg, 'data too long')                => 'Uno de los datos es demasiado largo.',

            // 22007 / 22008 - Fecha inválida
            str_contains($msg, 'incorrect date')               => 'La fecha ingresada no es válida.',
            str_contains($msg, 'incorrect datetime')           => 'La fecha ingresada no es válida.',

            // 23000 - NOT NULL
            str_contains($msg, 'cannot be null')               => 'Faltan datos obligatorios.',

            // 1271 - collation
            str_contains($msg, 'illegal mix of collations')    => 'Error de configuración de base de datos.',

            // Default
            default => 'Ocurrió un error al procesar la solicitud.'
        };
    }
}