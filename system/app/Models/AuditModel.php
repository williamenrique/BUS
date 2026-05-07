<?php
// c:\xampp\htdocs\BUS\system\app\Models\AuditModel.php

class AuditModel extends Mysql {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Inserta un registro de auditoría en la base de datos.
     *
     * @param int $usuario_id ID del usuario que realiza la acción.
     * @param string $action_type Tipo de acción (ej: 'LOGIN', 'CREATE', 'UPDATE', 'DELETE', 'VIEW').
     * @param string $module Módulo donde ocurrió la acción (ej: 'Login', 'Estacion', 'Almacen').
     * @param string $description Descripción detallada de la acción.
     * @param int|null $reference_id ID del registro afectado, si aplica.
     * @param string $ip_address Dirección IP del cliente.
     * @param string $user_agent User-Agent del cliente.
     * @return bool True si el registro fue exitoso, false en caso contrario.
     */
    public function logAction(int $usuario_id, string $action_type, string $module, string $description, ?int $reference_id, string $ip_address, string $user_agent): bool {
        $sql = "INSERT INTO table_sistema_bitacora (usuario_id, accion_tipo, modulo, descripcion, referencia_id, ip_address, user_agent, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $arrData = [$usuario_id, $action_type, $module, $description, $reference_id, $ip_address, $user_agent];
        $request = $this->insert($sql, $arrData);
        return $request > 0; // insert returns the last inserted ID, so > 0 means success
    }
}