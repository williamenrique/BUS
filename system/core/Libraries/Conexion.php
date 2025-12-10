<?php
class Conexion {
    private $conect;
    private $conexionError = null;

    public function __construct(){
        $conectString = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8";
        try {
            $this->conect = new PDO($conectString, DB_USER, DB_PASS);
            $this->conect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Guardamos el error sin detener la ejecución
            $this->conexionError = $e;
        }
    }
    public function conect(){
        if ($this->conexionError) {
            // Si hay un error, lanzamos la excepción aquí, donde el controlador puede atraparla
            throw $this->conexionError;
        }
        return $this->conect;
    }
    // Método para verificar si hay error de conexión
    public function hasConnectionError() {
        return $this->conexionError !== null;
    }
    
    // Método para obtener el mensaje de error
    public function getConnectionError() {
        return $this->conexionError ? $this->conexionError->getMessage() : null;
    }
}