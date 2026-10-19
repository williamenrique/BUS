<?php
namespace App\Core;

/**
 * Excepción lanzada cuando ocurre un error de base de datos.
 * Oculta el mensaje SQL real al usuario y lo registra en log.
 */
class DatabaseException extends AppException{

    private string $sqlState;
    private string $rawMessage;

    public function __construct(string $message, string $sqlState = '', string $rawMessage = ''){
        parent::__construct($message);
        $this->sqlState   = $sqlState;
        $this->rawMessage = $rawMessage ?: $message;
    }

    public function getSqlState(): string{
        return $this->sqlState;
    }

    public function getRawMessage(): string{
        return $this->rawMessage;
    }
}