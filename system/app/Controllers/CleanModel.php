<?php

class CleanModel extends Mysql {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Busca un usuario administrador por su contraseña encriptada.
     * Devuelve los datos del usuario si la contraseña es correcta y el usuario es administrador.
     * @param string $encryptedPassword La contraseña ya encriptada.
     * @return array|bool Los datos del usuario o false si no se encuentra.
     */
    public function getAdminByPassword(string $encryptedPassword) {
        $sql = "SELECT usuario_id, usuario_rol_id 
                FROM table_usuarios 
                WHERE usuario_password = ? 
                  AND usuario_rol_id = 1 
                  AND usuario_status = 1 
                LIMIT 1";
        $request = $this->select($sql, [$encryptedPassword]);
        return $request;
    }
}