<?php
class UserModel extends Mysql {
    private $tablaUsuarios = "table_usuarios";
    
    public function __construct(){
        parent::__construct();
    }
    
    /****** subir imagen de perfil *******/
    public function updateImg(int $intIdUser, string $fileBase){
        $sql = "UPDATE {$this->tablaUsuarios} SET usuario_imagen = ? WHERE usuario_id = ?";
        return $this->update($sql, [$fileBase, $intIdUser]);
    }
    
    public function selectUsuario(int $id_usuario) {
        // CORRECCIÓN: Se une con table_personal para tener todos los datos.
        $sql = "SELECT u.*, p.* 
                FROM {$this->tablaUsuarios} u
                INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                WHERE u.usuario_id = ? AND u.usuario_status = 1";
        return $this->select($sql, [$id_usuario]);
    }
    
    /******** actualizar password ********/
    public function updatePassword(int $intIdUser, string $hashedPassword) {
        $sql = "UPDATE {$this->tablaUsuarios} SET usuario_password = ? WHERE usuario_id = ?";
        return $this->update($sql, [$hashedPassword, $intIdUser]);
    }
    
    /******** actualizar datos del usuario ********/
    public function updateUserData(int $id_usuario, array $data): bool {    
        // Los datos personales ahora se actualizan en la tabla 'table_personal'
        $sql = "UPDATE table_personal SET 
                    personal_nombre = ?, 
                    personal_apellido = ?, 
                    personal_email = ?, 
                    personal_tlf = ?, 
                    personal_direccion = ?
                WHERE id_personal = (SELECT usuario_id_personal FROM table_usuarios WHERE usuario_id = ?)";

        $arrData = [
            $data['usuario_nombres'],
            $data['usuario_apellidos'],
            $data['usuario_email'],
            $data['usuario_telefono'],
            $data['usuario_direccion'], 
            $id_usuario
        ];
        return $this->update($sql, $arrData) !== false;
    }
    
    // comprobacion que el email no este ya en uso
    public function checkEmailExists($email, $exclude_user_id = 0) {
        $sql = "SELECT u.usuario_id 
                FROM {$this->tablaUsuarios} u
                INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                WHERE p.personal_email = ? AND u.usuario_id != ? AND u.usuario_status = 1";
        $request = $this->select($sql, [$email, $exclude_user_id]);
        return $request;
    }
    
    // Verificar si identificación existe
    public function checkIdExists(int $id_personal): bool {
        // Ahora verifica si ya existe un usuario para un id_personal
        $sql = "SELECT usuario_id
                FROM table_usuarios
                WHERE usuario_id_personal = ? AND usuario_status != 0";
        $request = $this->select($sql, [$id_personal]);
        return !empty($request);
    }
    
    /******* creacion de usuarios ******/
    // Obtener roles
    public function getRoles() {
        $sql = "SELECT rol_id as id, rol_nombre as nombre 
                FROM table_per_roles 
                WHERE rol_status = 1 
                ORDER BY rol_nombre";
        return $this->select_all($sql);
    }
    
    // Obtener departamentos
    public function getDepartments() {
        $sql = "SELECT departamento_id as id, departamento_nombre as nombre 
                FROM table_departamentos
                WHERE departamento_status = 1 
                ORDER BY departamento_nombre";
        return $this->select_all($sql);
    }
    
    public function createUserWithPersonalUpdate(array $personalData, array $userData) {
        $this->beginTransaction();
        try {
            // 1. Actualizar los datos en la tabla 'table_personal'
            $sql_update_personal = "UPDATE table_personal SET 
                                        personal_nombre = ?, 
                                        personal_apellido = ?, 
                                        personal_email = ?, 
                                        personal_tlf = ?, 
                                        personal_direccion = ?
                                    WHERE id_personal = ?";
            $arr_personal = [
                $personalData['nombre'],
                $personalData['apellido'],
                $personalData['email'],
                $personalData['telefono'],
                $personalData['direccion'],
                $personalData['id_personal']
            ];
            $this->update($sql_update_personal, $arr_personal);

            // 2. Verificar si ya existe un usuario para este id_personal
            $sql_check_user = "SELECT usuario_id FROM table_usuarios WHERE usuario_id_personal = ? AND usuario_status != 0";
            $request_check = $this->select($sql_check_user, [$personalData['id_personal']]);

            if (!empty($request_check)) {
                $this->rollBack(); // Revertir si el usuario ya existe
                return "exist";
            }

            // 3. Insertar el nuevo usuario en 'table_usuarios'
            $sql_insert_user = "INSERT INTO table_usuarios (usuario_password, usuario_rol_id, usuario_departamento_id, usuario_id_personal, usuario_status, usuario_creado) VALUES (?, ?, ?, ?, 1, NOW())";
            $arr_user = [$userData['password'], $userData['rol_id'], $userData['dep_id'], $personalData['id_personal']];
            $userId = $this->insert($sql_insert_user, $arr_user);

            $this->commit();
            return $userId;
        } catch (Exception $e) {
            $this->rollBack();
            error_log("Error en createUserWithPersonalUpdate: " . $e->getMessage());
            return 0;
        }
    }
    
    // Actualizar nick y ruta del usuario
    public function updateUserNickAndPath($userId, $nick, $fileBase) {
        $sql = "UPDATE table_usuarios 
                SET usuario_nick = ?, 
                    usuario_imagen = ?
                WHERE usuario_id = ?";
        
        return $this->update($sql, [$nick, $fileBase.'default.png', $userId]);
    }
    
    /****** mostrar usuarios en tabla y sus funciones ********/
    // Obtener todos los usuarios
    public function getUsuarios() {
        $sql = "SELECT u.usuario_id, u.usuario_nick, u.usuario_status,
                    p.personal_cedula, p.personal_nombre, p.personal_apellido, p.personal_email, p.personal_tlf,
                    r.rol_nombre, d.departamento_nombre
                FROM {$this->tablaUsuarios} u
                INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                INNER JOIN table_per_roles r ON u.usuario_rol_id = r.rol_id
                INNER JOIN table_departamentos d ON u.usuario_departamento_id = d.departamento_id
                WHERE u.usuario_status IN (0, 1)
                ORDER BY u.usuario_id DESC";
        
        return $this->select_all($sql);
    }

    // Obtener un usuario específico
    public function getUsuario($idUsuario) {
        $sql = "SELECT u.*, p.personal_cedula, p.personal_nombre, p.personal_apellido, p.personal_email, p.personal_tlf, p.personal_direccion,
                       r.rol_nombre, d.departamento_nombre
                FROM {$this->tablaUsuarios} u
                INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                INNER JOIN table_per_roles r ON u.usuario_rol_id = r.rol_id
                INNER JOIN table_departamentos d ON u.usuario_departamento_id = d.departamento_id
                WHERE u.usuario_id = ?";
        
        return $this->select($sql, [$idUsuario]);
    }

    /***** Actualizar usuario ******/
    public function updateUsuario($data) {
        // Iniciar transacción para asegurar la integridad de los datos
        $this->beginTransaction();
        try {
            // 1. Actualizar datos en table_usuarios (rol, departamento, estado)
            $sql_user = "UPDATE table_usuarios SET 
                            usuario_rol_id = ?,
                            usuario_departamento_id = ?,
                            usuario_status = ?
                         WHERE usuario_id = ?";
            $arr_user = [
                $data['usuario_rol_id'],
                $data['usuario_departamento_id'],
                $data['usuario_status'],
                $data['usuario_id']
            ];
            $this->update($sql_user, $arr_user);

            // 2. Actualizar datos en table_personal (datos personales)
            $sql_personal = "UPDATE table_personal SET
                                personal_cedula = ?, personal_nombre = ?, personal_apellido = ?,
                                personal_email = ?, personal_tlf = ?, personal_direccion = ?
                             WHERE id_personal = (SELECT usuario_id_personal FROM table_usuarios WHERE usuario_id = ?)";
            $arr_personal = [
                $data['personal_cedula'], $data['personal_nombre'], $data['personal_apellido'],
                $data['personal_email'], $data['personal_tlf'], $data['personal_direccion'],
                $data['usuario_id']
            ];
            $this->update($sql_personal, $arr_personal);

            // Si todo fue bien, confirmar los cambios
            $this->commit();
            return true;
        } catch (Exception $e) {
            // Si algo falla, revertir todos los cambios
            $this->rollBack();
            error_log("Error en updateUsuario: " . $e->getMessage());
            return false;
        }
    }

    // Actualizar estado del usuario
    public function updateStatus($idUsuario, $status) {
        $sql = "UPDATE {$this->tablaUsuarios} SET usuario_status = ? WHERE usuario_id = ?";
        return $this->update($sql, [$status, $idUsuario]);
    }
    /**********
     * metodos para departamentos en usuarios
     */
    public function selectDepartamentos() {
        $sql = "SELECT * FROM table_departamentos WHERE departamento_status != 2"; // 2 = eliminado
        return $this->select_all($sql);
    }

    public function selectDepartamento(int $iddepto) {
        $sql = "SELECT * FROM table_departamentos WHERE departamento_id = ?";
        return $this->select($sql, [$iddepto]);
    }

    public function insertDepartamento(string $nombre, string $descripcion, int $status) {
        $return = 0;
        $sql = "SELECT * FROM table_departamentos WHERE departamento_nombre = ?";
        $request = $this->select_all($sql, [$nombre]);

        if (empty($request)) {
            $query_insert = "INSERT INTO table_departamentos(departamento_nombre, departamento_descripcion, departamento_status) VALUES(?,?,?)";
            $arrData = [$nombre, $descripcion, $status];
            $request_insert = $this->insert($query_insert, $arrData);
            $return = $request_insert;
        } else {
            $return = "exist";
        }
        return $return;
    }

    public function updateDepartamento(int $iddepto, string $nombre, string $descripcion, int $status) {
        $sql = "UPDATE table_departamentos SET departamento_nombre = ?, departamento_descripcion = ?, departamento_status = ? WHERE departamento_id = ?";
        $arrData = [$nombre, $descripcion, $status, $iddepto];
        return $this->update($sql, $arrData);
    }

    public function deleteDepartamento(int $iddepto) {
        $sql_check = "SELECT COUNT(*) as total FROM table_usuarios WHERE usuario_departamento_id = ? AND usuario_status = 1";
        $request_check = $this->select($sql_check, [$iddepto]);

        if(isset($request_check['total']) && $request_check['total'] > 0){
            return 'in_use';
        }

        // Eliminación lógica
        $sql = "UPDATE table_departamentos SET departamento_status = 0 WHERE departamento_id = ?";
        return $this->update($sql, [$iddepto]);
    }

    /*****
	* CRUD de Roles
	*****/
    public function selectRoles() {
        $sql = "SELECT * FROM table_per_roles WHERE rol_status != 2"; // 2 = eliminado
        return $this->select_all($sql);
    }

    public function selectRol(int $idrol) {
        $sql = "SELECT * FROM table_per_roles WHERE rol_id = ?";
        return $this->select($sql, [$idrol]);
    }

    public function insertRol(string $nombre, string $descripcion, int $status) {
        $return = 0;
        $sql = "SELECT * FROM table_per_roles WHERE rol_nombre = ?";
        $request = $this->select_all($sql, [$nombre]);

        if (empty($request)) {
            $query_insert = "INSERT INTO table_per_roles(rol_nombre, rol_descripcion, rol_status) VALUES(?,?,?)";
            $arrData = [$nombre, $descripcion, $status];
            $request_insert = $this->insert($query_insert, $arrData);
            $return = $request_insert;
        } else {
            $return = "exist";
        }
        return $return;
    }

    public function updateRol(int $idrol, string $nombre, string $descripcion, int $status) {
        $sql = "UPDATE table_per_roles SET rol_nombre = ?, rol_descripcion = ?, rol_status = ? WHERE rol_id = ?";
        $arrData = [$nombre, $descripcion, $status, $idrol];
        return $this->update($sql, $arrData);
    }

    public function deleteRol(int $idrol) {
        $sql_check = "SELECT COUNT(*) as total FROM table_usuarios WHERE usuario_rol_id = ? AND usuario_status = 1";
        $request_check = $this->select($sql_check, [$idrol]);
        if(isset($request_check['total']) && $request_check['total'] > 0){
            return 'in_use';
        }
        $sql = "UPDATE table_per_roles SET rol_status = 0 WHERE rol_id = ?";
        return $this->update($sql, [$idrol]);
    }

    /**
     * Obtiene todas las solicitudes de recuperación pendientes.
     * Une la tabla de solicitudes con la de usuarios para obtener todos los detalles.
     * @return array Lista de solicitudes pendientes.
     */
    public function getPendingRecoveryRequests()
    {   
        // CORRECCIÓN: Se ajusta la consulta para usar la estructura correcta de `table_personal`.
        $sql = "SELECT
                    r.id,
                    r.identifier_provided,
                    r.request_date,
                    u.usuario_id,
                    u.usuario_nick,
                    u.usuario_password,
                    p.personal_nombre,
                    p.personal_apellido,
                    p.personal_email,
                    p.personal_tlf,
                    p.personal_cedula,
                    rol.rol_nombre
                FROM table_recovery_requests r
                JOIN table_usuarios u ON r.user_id = u.usuario_id
                JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                JOIN table_per_roles rol ON u.usuario_rol_id = rol.rol_id
                WHERE r.status = 0
                ORDER BY r.request_date ASC";
        $request = $this->select_all($sql);
        return $request;
    }

    /**
     * Elimina una solicitud de recuperación de la base de datos.
     * @param int $requestId El ID de la solicitud a eliminar.
     * @return bool True si se eliminó, false si no.
     */
    public function deleteRecoveryRequest(int $requestId)
    {
        $sql = "DELETE FROM table_recovery_requests WHERE id = ?";
        $arrData = array($requestId);
        $request = $this->delete($sql, $arrData);
        return $request;
    }
}