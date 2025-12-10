<?php 
class Mysql extends Conexion {
    private $conexion;
    private $connectionError = null;
    private $lastQueryError = null;
    
    public function __construct() {
        // Llamamos al constructor del padre para establecer la conexión
        parent::__construct(); 
        try {
            // Intentamos obtener la conexión del padre, lo que lanzará la excepción si falló
            $this->conexion = $this->conect(); 
        } catch (PDOException $e) {
            // Guardamos el error para consultarlo después
            $this->connectionError = $e;
        }
    }
    
    // Métodos unificados para manejo de errores
    public function hasError() {
        return $this->connectionError !== null || parent::hasConnectionError();
    }
    
    public function getError() {
        if ($this->connectionError !== null) {
            return $this->connectionError->getMessage();
        }
        return parent::getConnectionError();
    }
    
    public function getLastQueryError() {
        return $this->lastQueryError;
    }
    
    // Métodos originales (mantener compatibilidad)
    public function hasConnectionError() {
        return $this->connectionError !== null;
    }
    
    public function getConnectionError() {
        return $this->connectionError ? $this->connectionError->getMessage() : null;
    }
    
    /**
     * Private method to execute a prepared statement and handle errors.
     * This centralizes the try...catch block for all queries.
     * @param string $query The SQL query string.
     * @param array $arrValues The array of values to bind.
     * @return PDOStatement The executed statement object.
     */
    private function _executeStatement(string $query, array $arrValues = []): PDOStatement {
        // Verificar si hay error de conexión primero
        if ($this->hasError()) {
            $this->handleError('Error de conexión con la base de datos: ' . $this->getError(), []);
        }
        
        try {
            $stmt = $this->conexion->prepare($query);
            $stmt->execute($arrValues);
            return $stmt;
        } catch (PDOException $e) {
            $errorInfo = $stmt->errorInfo();
            $errorCode = isset($errorInfo[1]) ? $errorInfo[1] : null;

            $errorMessage = 'Error en la base de datos.';
            $this->lastQueryError = $e->getMessage();

            // Specific error messages based on MySQL error codes
            switch ($errorCode) {
                case '1054':
                    $errorMessage = 'Error de columna: Una columna no existe.';
                    break;
                case '1366':
                    $errorMessage = 'Error de tipo de dato: Valor incorrecto.';
                    break;
                case '1406':
                    $errorMessage = 'Error de longitud: Valor demasiado largo.';
                    break;
                case '1062':
                    $errorMessage = 'Error de clave duplicada: El valor ya existe.';
                    break;
                case '2002':
                case '2003':
                    $errorMessage = 'Error de conexión: No se puede conectar al servidor de base de datos.';
                    break;
                case '1045':
                    $errorMessage = 'Error de acceso: Acceso denegado para el usuario.';
                    break;
                case '1049':
                    $errorMessage = 'Error de base de datos: La base de datos no existe.';
                    break;
                default:
                    $errorMessage = 'Error en la base de datos: ' . $e->getMessage();
                    break;
            }
            
            // This will stop execution and send a JSON response to the client.
            $this->handleError($errorMessage, $errorInfo);
        }
    }
    
    /**
     * Helper function to send an error response to the client.
     * Since it's within the class, it can be a private or protected method.
     */
    private function handleError($message, $errorInfo) {
        // Log del error
        error_log("Error de BD: " . $message . " - Info: " . print_r($errorInfo, true));
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'error_info' => $errorInfo
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Insertar un registro y retornar el último ID insertado
    public function insert(string $query, array $arrValues): int {
        $stmt = $this->_executeStatement($query, $arrValues);
        return $stmt ? intval($this->conexion->lastInsertId()) : 0;
    }
    
    // Buscar un registro (con parámetros)
    public function select(string $query, array $arrValues = []): ?array {
        $stmt = $this->_executeStatement($query, $arrValues);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data !== false ? $data : null;
    }

    // Buscar varios registros (con parámetros)
    public function select_all(string $query, array $arrValues = []): array {
        $stmt = $this->_executeStatement($query, $arrValues);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data ?: [];
    }

    // Actualizar registros
    public function update(string $query, array $arrValues): bool {
        $stmt = $this->_executeStatement($query, $arrValues);
        return $stmt->rowCount() > 0;
    }

    // Eliminar registros (con parámetros)
    public function delete(string $query, array $arrValues = []): bool {
        $stmt = $this->_executeStatement($query, $arrValues);
        return $stmt->rowCount() > 0;
    }
    // contar registros
    public function contar(string $query, array $arrValues = []): int {
        try {
            $stmt = $this->_executeStatement($query, $arrValues);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return 0;
        }
    }
    
    // Debug de consultas SQL
    public function debugQuery(string $query, array $params = []) {
        $formattedQuery = $query;
        
        foreach ($params as $param) {
            // Reemplazar el primer ? encontrado
            $formattedQuery = preg_replace('/\?/', 
                is_string($param) ? "'" . str_replace("'", "''", $param) . "'" : $param, 
                $formattedQuery, 
                1
            );
        }

        // Mostrar información de depuración
        echo "=== DEBUG QUERY ===\n";
        echo "Consulta formateada: " . $formattedQuery . "\n";
        echo "Parámetros: " . print_r($params, true) . "\n";
        // echo "Conexión: " . ($this->hasError() ? 'ERROR: ' . $this->getError() : 'OK') . "\n";
        echo "===================\n";
        
        // Opcional: ejecutar y mostrar resultados
        try {
            if (!$this->hasError()) {
                $stmt = $this->conexion->prepare($query);
                $stmt->execute($params);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "Resultados: " . print_r($results, true) . "\n";
            }
        } catch (Exception $e) {
            echo "Error al ejecutar: " . $e->getMessage() . "\n";
        }
        
        exit;
    }
    
    // Método para verificar si la conexión está activa
    public function isConnected(): bool {
        try {
            if ($this->conexion) {
                $this->conexion->query("SELECT 1");
                return true;
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Método para comenzar una transacción
    public function beginTransaction(): bool {
        if (!$this->hasError()) {
            return $this->conexion->beginTransaction();
        }
        return false;
    }
    
    // Método para confirmar una transacción
    public function commit(): bool {
        if (!$this->hasError()) {
            return $this->conexion->commit();
        }
        return false;
    }
    
    // Método para revertir una transacción
    public function rollBack(): bool {
        if (!$this->hasError()) {
            return $this->conexion->rollBack();
        }
        return false;
    }
}