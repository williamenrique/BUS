<?php
class LoginModel extends Mysql {
	public function __construct(){
		parent::__construct();
	}
	public function loginUser(string $identifier, string $password){
		$sql = "SELECT u.usuario_id, u.usuario_status 
				FROM table_usuarios u
				INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
				WHERE (u.usuario_nick = ? OR p.personal_cedula = ? OR p.personal_email = ?) 
				AND u.usuario_password = ? AND u.usuario_status != 0";
		$request = $this->select($sql, [$identifier, $identifier, $identifier, $password]);
		return $request;
	}

	public function sessionLogin(int $intIdUser){
		// CORRECCIÓN: Se ajusta la consulta para reflejar la estructura correcta de la base de datos.
		// Los campos de nombre, apellido, etc., vienen de `table_personal`.
		$sql = "SELECT u.usuario_id, u.usuario_nick, u.usuario_rol_id, r.rol_nombre, u.usuario_departamento_id, u.usuario_estacion_id, u.usuario_imagen, u.usuario_status,
					   p.personal_nombre, p.personal_apellido, p.personal_cedula, p.personal_email, p.personal_tlf,
					   d.departamento_nombre,
					   e.estacion
				FROM table_usuarios u
				INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
				INNER JOIN table_per_roles r ON u.usuario_rol_id = r.rol_id 
				INNER JOIN table_departamentos d ON u.usuario_departamento_id = d.departamento_id 
				LEFT JOIN table_es_estacion e ON u.usuario_estacion_id = e.id_estacion
				WHERE u.usuario_id = ?";
		$request = $this->select($sql, [$intIdUser]);
		$_SESSION['userData'] = $request;
		return $request;
	}

	// verificar sesion abierta
	public function saveSessionInfo($data) {
		$query = "INSERT INTO table_usuario_sessions 
				(session_id, usuario_id, ip_address, usuario_agent, created_at) 
				VALUES (?, ?, ?, ?, ?) 
				ON DUPLICATE KEY UPDATE 
				last_activity = NOW()";
		return $this->insert($query, [
			$data['session_id'], 
			$data['usuario_id'], 
			$data['ip_address'], 
			$data['usuario_agent'], 
			$data['created_at']
		]);
    }
	// probando esta funcion
    public function validateSessionDB($sessionId, $userId) {
    // ELIMINAR el timeout de 30 minutos
		$query = "SELECT * FROM table_usuario_sessions 
				WHERE session_id = ? AND usuario_id = ?";
		return $this->select($query, [$sessionId, $userId]);
	}
	// probando esta funcion
	public function getActiveSession(int $userId = null, string $userNick = null) {
		// Construir la consulta dinámica según los parámetros recibidos
		$where = [];
		$arrData = [];
		if (!empty($userId)) {
			$where[] = 'tsession.usuario_id = ?';
			$arrData[] = $userId;
		}
		if (!empty($userNick)) {
			$where[] = "tuser.usuario_nick = ?";
			$arrData[] = $userNick;
		}
		if (empty($where)) {
			// Si ambos están vacíos, retorna null o false
			return [];
		}
		$whereSql = implode(' OR ', $where);
		// Consulta para encontrar una sesión activa existente para un usuario.
		$query = "SELECT tsession.* , tuser.usuario_nick
				FROM table_usuario_sessions tsession
				JOIN table_usuarios tuser ON tsession.usuario_id = tuser.usuario_id
				WHERE ($whereSql)
				ORDER BY tsession.last_activity DESC
				LIMIT 1";
		return $this->select($query, $arrData);
	}
	// funcion anterior
    public function deleteSession($sessionId) {
		$this->sessionId = $sessionId;
        $query = "DELETE FROM table_usuario_sessions WHERE session_id = ?";
        return $this->delete($query, [$sessionId]);
    }

    public function cleanupExpiredSessions() {
		// En lugar de 1 día, puedes poner 30 días o más
		$query = "DELETE FROM table_usuario_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 2 DAY)";
		return $this->delete($query);
	}
	// funcion anterior
    /**
     * Obtiene todas las sesiones de usuario activas.
     * @return array
     */
    public function getAllActiveSessions() {
        // CORRECCIÓN: Se une con table_personal para obtener los nombres y apellidos.
        $sql = "SELECT
                    s.usuario_id,
                    s.ip_address,
                    s.created_at,
                    u.usuario_nick,
                    u.usuario_imagen, 
                    p.personal_nombre AS usuario_nombres,
                    p.personal_apellido AS usuario_apellidos
                FROM table_usuario_sessions s
                JOIN table_usuarios u ON s.usuario_id = u.usuario_id
                JOIN table_personal p ON u.usuario_id_personal = p.id_personal";
        return $this->select_all($sql);
    }

    /**
     * Busca un usuario por su email o cédula.
     * @param string $identifier El email o la cédula a buscar.
     * @return array|false Los datos del usuario si se encuentra, o false si no.
     */
    public function buscarUsuarioPorIdentificador(string $identifier)
    {
        // CORRECCIÓN: La búsqueda debe hacerse en las tablas correctas.
        $sql = "SELECT u.usuario_id FROM table_usuarios u
                INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                WHERE p.personal_email = ? OR p.personal_cedula = ? OR u.usuario_nick = ?";
        $arrData = [$identifier, $identifier, $identifier];
        $request = $this->select($sql, $arrData);
        return $request;
    }

    /**
     * Crea un nuevo registro en la tabla de solicitudes de recuperación.
     * @param int $userId El ID del usuario que solicita la recuperación.
     * @param string $identifier El dato (email/CI) que proporcionó.
     * @return mixed El ID de la inserción, "exist" si ya hay una, o 0 si falla.
     */
    public function crearSolicitudRecuperacion(int $userId, string $identifier)
    {
        // Primero, verificamos si ya existe una solicitud pendiente para este usuario para no duplicarla.
        $sql_check = "SELECT id FROM table_recovery_requests WHERE user_id = ? AND status = 0";
		$request_check = $this->select($sql_check, [$userId]);
		
        if (!empty($request_check)) {
            return $request_check['id']; // Si ya existe, devuelve el ID de la solicitud pendiente
        } else {
			$sql = "INSERT INTO table_recovery_requests(user_id, identifier_provided) VALUES(?,?)";
            $arrData = array($userId, $identifier);
            return $this->insert($sql, $arrData);
        }
    }

    /**
     * Obtiene una lista de todas las tablas de la base de datos.
     * @return array
     */
    public function getTables(): array {
        $sql = "SHOW TABLES";
        $result = $this->select_all($sql);
        // La consulta devuelve un array de arrays, cada uno con una clave como 'Tables_in_dbname'.
        // Lo aplanamos a un array simple de nombres de tabla.
        return array_map('current', $result);
    }

    /**
     * Obtiene la sentencia `CREATE TABLE` para una tabla específica.
     * @param string $tableName
     * @return array|false
     */
    public function getTableStructure(string $tableName) {
        return $this->select("SHOW CREATE TABLE `{$tableName}`");
    }

    /**
     * Obtiene todos los datos de una tabla específica.
     * @param string $tableName
     * @return array
     */
    public function getTableData(string $tableName): array {
        return $this->select_all("SELECT * FROM `{$tableName}`");
    }

    /**
     * Elimina una lista de tablas de la base de datos.
     * @param array $tablesToDelete - Un array con los nombres de las tablas a eliminar.
     * @return int - El número de tablas eliminadas.
     */
    public function dropTables(array $tablesToDelete): int {
        if (empty($tablesToDelete)) {
            return 0;
        }

        $deletedCount = 0;
        // Desactivar temporalmente la comprobación de claves foráneas para evitar errores de dependencia
        $this->update("SET FOREIGN_KEY_CHECKS = 0;", []);

        foreach ($tablesToDelete as $table) {
            // Se usa `str_replace` para una limpieza básica, aunque la lista de tablas viene del propio sistema.
            $cleanTable = str_replace(['`', ';'], '', $table);
            $this->update("DROP TABLE IF EXISTS `{$cleanTable}`", []);
            $deletedCount++;
        }

        $this->update("SET FOREIGN_KEY_CHECKS = 1;", []);
        return $deletedCount;
    }
}