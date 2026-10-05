<?php
class MenuModel extends Mysql {
    public function __construct(){
        parent::__construct();
    }

    public function obtenerMenuUsuario($usuarioNick) {
        $this->usuarioNick = $usuarioNick;
        
        // Consulta corregida para obtener menús y submenús correctamente relacionados
        $sql = "SELECT 
            m.menu_id, m.menu_nombre, m.menu_icono, m.menu_es_desplegable as menu_tiene_submenu, 
            m.menu_link as menu_pagina, s.submenu_id, s.submenu_nombre, s.submenu_pagina, 
            s.submenu_link as submenu_url, s.menu_id as submenu_menu_id
            FROM table_usuarios u 
            INNER JOIN table_men_usuario_menu um ON u.usuario_id = um.usuario_id 
            INNER JOIN table_men_menu m ON um.menu_id = m.menu_id 
            LEFT JOIN table_men_submenu s ON m.menu_id = s.menu_id 
            LEFT JOIN table_men_usuario_submenu us ON u.usuario_id = us.usuario_id AND s.submenu_id = us.submenu_id
            WHERE u.usuario_nick = ?
            AND u.usuario_status = 1 
            AND m.menu_estado = 1 
            AND (s.submenu_estado = 1 OR s.submenu_estado IS NULL)
            AND (us.usuario_id IS NOT NULL OR s.submenu_id IS NULL)
            ORDER BY m.menu_orden ASC, s.submenu_orden ASC";
        
        $request = $this->select_all($sql, [$this->usuarioNick]);
        return $request;
    }

    // Función para obtener todos los menús (para administración)
    public function obtenerTodosMenus() {
        $sql = "SELECT * FROM table_men_menu WHERE menu_estado = 1 ORDER BY menu_orden ASC";
        return $this->select_all($sql);
    }

    // Función para obtener todos los submenús (para administración)
    public function obtenerTodosSubmenus() {
        $sql = "SELECT s.*, m.menu_id, m.menu_nombre 
                FROM table_men_submenu s 
                INNER JOIN table_men_menu m ON s.menu_id = m.menu_id 
                WHERE s.submenu_estado = 1 ORDER BY s.submenu_orden ASC";
        return $this->select_all($sql);
    }

    // Cargar usuarios para asignación
    public function cargar_usuarios() {
        $sql = "SELECT u.usuario_id, u.usuario_nick, p.personal_nombre, p.personal_apellido, 
                r.rol_id, r.rol_nombre 
                FROM table_usuarios u 
                INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                INNER JOIN table_per_roles r ON u.usuario_rol_id = r.rol_id
                WHERE u.usuario_status = 1 
                ORDER BY p.personal_nombre, p.personal_apellido";
        return $this->select_all($sql);
    }
    
    public function get_user_info($usuario_id) {
        $sql = "SELECT u.*, p.personal_nombre, p.personal_apellido, r.rol_nombre, d.departamento_nombre 
                FROM table_usuarios u 
                INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                INNER JOIN table_per_roles r ON u.usuario_rol_id = r.rol_id 
                LEFT JOIN table_departamentos d ON u.usuario_departamento_id = d.departamento_id 
                WHERE u.usuario_id = ?";
        return $this->select($sql, [$usuario_id]);
    }
    
    public function get_all_menus() {
        $sql = "SELECT * FROM table_men_menu WHERE menu_estado = 1 ORDER BY menu_orden";
        return $this->select_all($sql);
    }
    
    public function get_all_submenus() {
        $sql = "SELECT s.*, m.menu_id, m.menu_nombre 
                FROM table_men_submenu s 
                INNER JOIN table_men_menu m ON s.menu_id = m.menu_id 
                WHERE s.submenu_estado = 1 
                ORDER BY m.menu_orden, s.submenu_orden";
        return $this->select_all($sql);
    }
    
    public function get_user_permissions($usuario_id) {
        // Obtener menús asignados directamente al usuario
        $sql_menus = "SELECT m.menu_id, NULL as submenu_id 
                     FROM table_men_usuario_menu um 
                     INNER JOIN table_men_menu m ON um.menu_id = m.menu_id 
                     WHERE um.usuario_id = ? AND m.menu_estado = 1";
        
        $menus = $this->select_all($sql_menus, [$usuario_id]);
        
        // Obtener submenús asignados directamente al usuario con su menú padre
        $sql_submenus = "SELECT s.menu_id, s.submenu_id 
                        FROM table_men_usuario_submenu us 
                        INNER JOIN table_men_submenu s ON us.submenu_id = s.submenu_id 
                        WHERE us.usuario_id = ? AND s.submenu_estado = 1";
        
        $submenus = $this->select_all($sql_submenus, [$usuario_id]);
        
        return array_merge($menus, $submenus);
    }
    
    public function update_user_permissions($usuario_id, $permisos) {
        try {
            if (!is_numeric($usuario_id)) {
                throw new Exception("ID de usuario inválido");
            }

            // Eliminar permisos actuales del usuario
            $sql_delete_menus = "DELETE FROM table_men_usuario_menu WHERE usuario_id = ?";
            $this->delete($sql_delete_menus, [$usuario_id]);
            
            $sql_delete_submenus = "DELETE FROM table_men_usuario_submenu WHERE usuario_id = ?";
            $this->delete($sql_delete_submenus, [$usuario_id]);

            // Insertar nuevos permisos
            foreach ($permisos as $permiso) {
                if (!isset($permiso['menu_id']) || !is_numeric($permiso['menu_id'])) {
                    throw new Exception("Menu ID inválido en los permisos");
                }
                
                $menu_id = intval($permiso['menu_id']);
                
                if (isset($permiso['submenu_id']) && !empty($permiso['submenu_id']) && $permiso['submenu_id'] !== 'null') {
                    // Es un submenú - verificar que pertenezca al menú
                    if (!is_numeric($permiso['submenu_id'])) {
                        throw new Exception("Submenu ID inválido en los permisos");
                    }
                    $submenu_id = intval($permiso['submenu_id']);
                    
                    // Verificar que el submenú pertenezca al menú
                    $sql_verify = "SELECT submenu_id FROM table_men_submenu 
                                  WHERE submenu_id = ? AND menu_id = ? AND submenu_estado = 1";
                    $verify = $this->select($sql_verify, [$submenu_id, $menu_id]);
                    
                    if ($verify) {
                        $sql_insert = "INSERT INTO table_men_usuario_submenu (usuario_id, submenu_id) VALUES (?, ?)";
                        $this->insert($sql_insert, [$usuario_id, $submenu_id]);
                    }
                } else {
                    // Es un menú principal
                    $sql_insert = "INSERT INTO table_men_usuario_menu (usuario_id, menu_id) VALUES (?, ?)";
                    $this->insert($sql_insert, [$usuario_id, $menu_id]);
                }
            }

            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }

    // Gestión de menús
    public function insertMenu($data) {
        $query = "INSERT INTO table_men_menu (menu_nombre, menu_icono, menu_es_desplegable, menu_link, menu_orden) 
                VALUES (?, ?, ?, ?, ?)";
        $arrData = array(
            $data['menu_nombre'], 
            $data['menu_icono'], 
            $data['menu_tiene_submenu'] ? 1 : 0, 
            // CORRECIÓN: Usar menu_link en lugar de menu_pagina
            $data['menu_tiene_submenu'] ? NULL : $data['menu_pagina'], 
            $data['menu_orden']
        );
        return $this->insert($query, $arrData);
    }

    public function updateMenu($data) {
        $query = "UPDATE table_men_menu 
                SET menu_nombre = ?, menu_icono = ?, menu_es_desplegable = ?, 
                    menu_link = ?, menu_orden = ? 
                WHERE menu_id = ?";
        $arrData = array(
            $data['menu_nombre'], 
            $data['menu_icono'], 
            $data['menu_tiene_submenu'] ? 1 : 0, 
            // CORRECIÓN: Usar menu_link en lugar de menu_pagina
            $data['menu_tiene_submenu'] ? NULL : $data['menu_pagina'], 
            $data['menu_orden'],
            $data['menu_id']
        );
        return $this->update($query, $arrData);
    }

    public function deleteMenu($menu_id) {
        $query = "UPDATE table_men_menu SET menu_estado = 0 WHERE menu_id = ?";
        $arrData = array($menu_id);
        return $this->update($query, $arrData);
    }

    public function getMenus() {
        $query = "SELECT * FROM table_men_menu WHERE menu_estado = 1 ORDER BY menu_orden ASC";
        return $this->select_all($query);
    }

    public function insertSubmenu($data) {
        // Insertar en table_men_submenu
        $query = "INSERT INTO table_men_submenu (menu_id, submenu_nombre, submenu_link, submenu_pagina, submenu_orden) 
        VALUES (?, ?, ?, ?, ?)";
        $arrData = array(
            $data['menu_id'],
            $data['submenu_nombre'], 
            $data['submenu_link'],
            $data['submenu_pagina'], 
            $data['submenu_orden']
        );
        $submenu_id = $this->insert($query, $arrData);
        
        return $submenu_id;
    }

    public function getSubmenus() {
        $query = "SELECT s.*, m.menu_id, m.menu_nombre 
                FROM table_men_submenu s 
                INNER JOIN table_men_menu m ON s.menu_id = m.menu_id 
                WHERE s.submenu_estado = 1 AND m.menu_estado = 1 
                ORDER BY m.menu_orden ASC, s.submenu_orden ASC";
        return $this->select_all($query);
    }

    public function getMenuConSubmenus($menu_id) {
        $query = "SELECT m.*, 
                (SELECT COUNT(*) FROM table_men_submenu s 
                WHERE s.menu_id = m.menu_id AND s.submenu_estado = 1) as total_submenus
                FROM table_men_menu m 
                WHERE m.menu_id = ? AND m.menu_estado = 1";
        $arrData = array($menu_id);
        return $this->select($query, $arrData);
    }

    public function getSubmenusPorMenu($menu_id) {
        $query = "SELECT s.* 
                  FROM table_men_submenu s 
                  WHERE s.menu_id = ? AND s.submenu_estado = 1 
                  ORDER BY s.submenu_orden ASC";
        $arrData = array($menu_id);
        return $this->select_all($query, $arrData);
    }

    public function deleteSubmenu($submenu_id) {
        $query = "UPDATE table_men_submenu SET submenu_estado = 0 WHERE submenu_id = ?";
        $arrData = array($submenu_id);
        return $this->update($query, $arrData);
    }

    public function getSubmenusByMenu($menu_id) {
        $query = "SELECT s.* 
                FROM table_men_submenu s 
                WHERE s.menu_id = ? AND s.submenu_estado = 1 
                ORDER BY s.submenu_orden ASC";
        $arrData = array($menu_id);
        return $this->select_all($query, $arrData);
    }

    public function updateSubmenu($data) {
        $query = "UPDATE table_men_submenu 
                SET submenu_nombre = ?, submenu_link = ?, submenu_pagina = ?, submenu_orden = ? 
                WHERE submenu_id = ?";
        $arrData = array(
            $data['submenu_nombre'], 
            $data['submenu_link'],
            $data['submenu_pagina'], 
            $data['submenu_orden'],
            $data['submenu_id']
        );
        return $this->update($query, $arrData);
    }
}