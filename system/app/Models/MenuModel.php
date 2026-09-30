<?php
class MenuModel extends Mysql {
    public function __construct(){
        parent::__construct();
    }

    // =================================================================
    // CONSULTAS PARA EL SIDEBAR (renderizado del menú del usuario)
    // =================================================================

    /**
     * Obtiene el árbol completo de menús asignados a un usuario.
     * Devuelve todas las filas (menu + hijos) en una sola consulta.
     */
    public function obtenerMenuUsuario($usuarioNick) {
        $sql = "SELECT 
                    m.menu_id,
                    m.menu_padre_id,
                    m.menu_nombre,
                    m.menu_icono,
                    m.menu_ruta,
                    m.menu_orden,
                    m.menu_scope,
                    m.menu_es_desplegable
                FROM table_men_menu m
                WHERE m.menu_estado = 1
                  AND m.menu_id IN (
                      SELECT um.menu_id 
                      FROM table_men_usuario_menu um 
                      INNER JOIN table_usuarios u ON um.usuario_id = u.usuario_id
                      WHERE u.usuario_nick = ?
                  )
                ORDER BY 
                    COALESCE(m.menu_padre_id, m.menu_id),
                    m.menu_padre_id IS NOT NULL,
                    m.menu_orden ASC";
        
        return $this->select_all($sql, [$usuarioNick]);
    }

    /**
     * Obtiene los patrones de ruta que activan cada menú del usuario.
     * Devuelve array indexado por menu_id con los patrones.
     */
    public function obtenerRutasPorMenu() {
        $sql = "SELECT menu_id, patron FROM table_men_rutas WHERE activa = 1";
        $filas = $this->select_all($sql);
        $rutas = [];
        foreach ($filas as $fila) {
            $rutas[$fila['menu_id']][] = $fila['patron'];
        }
        return $rutas;
    }

    /**
     * Dado un path actual (ej: "flota/tallerhistorial/123"), devuelve el menu_id
     * más específico que debe activarse.
     * Soporta comodines (*) en los patrones.
     */
    public function resolverMenuActivo($pathActual, $menuIdsPermitidos) {
        if (empty($menuIdsPermitidos)) return null;
        
        $pathActual = trim($pathActual, '/');
        
        // Obtener todos los patrones de los menús permitidos
        $placeholders = implode(',', array_fill(0, count($menuIdsPermitidos), '?'));
        $sql = "SELECT r.menu_id, r.patron 
                FROM table_men_rutas r
                WHERE r.activa = 1 AND r.menu_id IN ($placeholders)";
        $rutas = $this->select_all($sql, $menuIdsPermitidos);
        
        // Buscar el patrón más específico que coincida
        $mejorMatch = null;
        $mejorLongitud = 0;
        
        foreach ($rutas as $ruta) {
            $patron = $ruta['patron'];
            $esMatch = false;
            
            if (strpos($patron, '*') !== false) {
                // Patrón con comodín
                $regex = '/^' . str_replace('\*', '.*', preg_quote($patron, '/')) . '$/';
                $esMatch = preg_match($regex, $pathActual) === 1;
            } else {
                // Coincidencia exacta
                $esMatch = ($patron === $pathActual);
            }
            
            if ($esMatch && strlen($patron) > $mejorLongitud) {
                $mejorMatch = $ruta['menu_id'];
                $mejorLongitud = strlen($patron);
            }
        }
        
        return $mejorMatch;
    }

    // =================================================================
    // CONSULTAS PARA ADMINISTRACIÓN (CRUD de menús)
    // =================================================================

    /**
     * Lista todos los menús (raíz + hijos) para la UI de administración.
     */
    public function getMenus() {
        $sql = "SELECT * FROM table_men_menu 
                WHERE menu_estado = 1 
                ORDER BY 
                    COALESCE(menu_padre_id, menu_id),
                    menu_padre_id IS NOT NULL,
                    menu_orden ASC";
        return $this->select_all($sql);
    }

    /**
     * Lista todos los menús en formato árbol (con hijos anidados).
     */
    public function getMenusTree() {
        $todos = $this->getMenus();
        return $this->construirArbol($todos);
    }

    /**
     * Construye un árbol jerárquico a partir de una lista plana.
     */
    private function construirArbol($items, $padreId = null) {
        $rama = [];
        foreach ($items as $item) {
            if ($item['menu_padre_id'] == $padreId) {
                $hijos = $this->construirArbol($items, $item['menu_id']);
                if (!empty($hijos)) {
                    $item['hijos'] = $hijos;
                }
                $rama[] = $item;
            }
        }
        return $rama;
    }

    /**
     * Obtiene todos los menús con sus rutas para la UI.
     */
    public function getMenusConRutas() {
        $menus = $this->getMenus();
        foreach ($menus as &$menu) {
            $menu['rutas'] = $this->getRutasByMenu($menu['menu_id']);
        }
        return $menus;
    }

    /**
     * Obtiene las rutas asociadas a un menú.
     */
    public function getRutasByMenu($menuId) {
        $sql = "SELECT * FROM table_men_rutas WHERE menu_id = ? ORDER BY ruta_id ASC";
        return $this->select_all($sql, [$menuId]);
    }

    /**
     * Inserta un menú nuevo.
     */
    public function insertMenu($data) {
        $sql = "INSERT INTO table_men_menu 
                    (menu_padre_id, menu_nombre, menu_icono, menu_ruta, menu_orden, menu_estado, menu_scope, menu_es_desplegable) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $arrData = [
            !empty($data['menu_padre_id']) ? intval($data['menu_padre_id']) : null,
            $data['menu_nombre'],
            $data['menu_icono'] ?? null,
            $data['menu_ruta'] ?? null,
            intval($data['menu_orden'] ?? 0),
            intval($data['menu_estado'] ?? 1),
            $data['menu_scope'] ?? 'general',
            0
        ];
        $menuId = $this->insert($sql, $arrData);
        
        // Insertar rutas si vienen
        if ($menuId > 0 && !empty($data['rutas']) && is_array($data['rutas'])) {
            foreach ($data['rutas'] as $patron) {
                $this->insertRuta($menuId, $patron);
            }
        }
        
        // Recalcular es_desplegable del padre
        if (!empty($data['menu_padre_id'])) {
            $this->actualizarEsDesplegable($data['menu_padre_id']);
        }
        
        return $menuId;
    }

    /**
     * Actualiza un menú existente.
     */
    public function updateMenu($data) {
        $sql = "UPDATE table_men_menu 
                SET menu_padre_id = ?,
                    menu_nombre = ?, 
                    menu_icono = ?, 
                    menu_ruta = ?, 
                    menu_orden = ?, 
                    menu_scope = ? 
                WHERE menu_id = ?";
        $arrData = [
            !empty($data['menu_padre_id']) ? intval($data['menu_padre_id']) : null,
            $data['menu_nombre'],
            $data['menu_icono'] ?? null,
            $data['menu_ruta'] ?? null,
            intval($data['menu_orden'] ?? 0),
            $data['menu_scope'] ?? 'general',
            intval($data['menu_id'])
        ];
        $ok = $this->update($sql, $arrData);
        
        // Actualizar rutas si vienen
        if (!empty($data['rutas']) && is_array($data['rutas'])) {
            $this->eliminarRutasByMenu($data['menu_id']);
            foreach ($data['rutas'] as $patron) {
                if (!empty($patron)) {
                    $this->insertRuta($data['menu_id'], $patron);
                }
            }
        }
        
        return $ok;
    }

    /**
     * Elimina (desactiva) un menú.
     */
    public function deleteMenu($menu_id) {
        // Recalcular el padre antes de desactivar
        $menu = $this->getMenuById($menu_id);
        
        $sql = "UPDATE table_men_menu SET menu_estado = 0 WHERE menu_id = ?";
        $ok = $this->update($sql, [intval($menu_id)]);
        
        if ($ok && !empty($menu['menu_padre_id'])) {
            $this->actualizarEsDesplegable($menu['menu_padre_id']);
        }
        
        return $ok;
    }

    /**
     * Obtiene un menú por ID.
     */
    public function getMenuById($menuId) {
        $sql = "SELECT * FROM table_men_menu WHERE menu_id = ?";
        return $this->select($sql, [intval($menuId)]);
    }

    /**
     * Recalcula menu_es_desplegable para un menú dado.
     */
    public function actualizarEsDesplegable($menuId) {
        $sql = "SELECT COUNT(*) as hijos FROM table_men_menu 
                WHERE menu_padre_id = ? AND menu_estado = 1";
        $result = $this->select($sql, [intval($menuId)]);
        $esDesplegable = ($result && $result['hijos'] > 0) ? 1 : 0;
        
        $sqlUpdate = "UPDATE table_men_menu SET menu_es_desplegable = ? WHERE menu_id = ?";
        return $this->update($sqlUpdate, [$esDesplegable, intval($menuId)]);
    }

    // =================================================================
    // RUTAS (patrones que activan un menú)
    // =================================================================

    public function insertRuta($menuId, $patron) {
        $patron = trim($patron);
        if (empty($patron)) return 0;
        
        $sql = "INSERT INTO table_men_rutas (menu_id, patron, activa) VALUES (?, ?, 1)";
        return $this->insert($sql, [intval($menuId), $patron]);
    }

    public function eliminarRutasByMenu($menuId) {
        $sql = "DELETE FROM table_men_rutas WHERE menu_id = ?";
        return $this->delete($sql, [intval($menuId)]);
    }

    public function eliminarRuta($rutaId) {
        $sql = "DELETE FROM table_men_rutas WHERE ruta_id = ?";
        return $this->delete($sql, [intval($rutaId)]);
    }

    // =================================================================
    // PERMISOS DE USUARIO
    // =================================================================

    /**
     * Obtiene todos los menu_id asignados directamente a un usuario.
     */
    public function get_user_permissions($usuario_id) {
        $sql = "SELECT menu_id FROM table_men_usuario_menu WHERE usuario_id = ?";
        $rows = $this->select_all($sql, [intval($usuario_id)]);
        return array_column($rows, 'menu_id');
    }

    /**
     * Actualiza TODOS los permisos de un usuario (reemplazo total).
     */
    public function update_user_permissions($usuario_id, $menuIds) {
        try {
            $usuario_id = intval($usuario_id);
            if ($usuario_id <= 0) throw new Exception("ID de usuario inválido");
            
            // Eliminar permisos actuales
            $sqlDelete = "DELETE FROM table_men_usuario_menu WHERE usuario_id = ?";
            $this->delete($sqlDelete, [$usuario_id]);
            
            // Insertar nuevos (si vienen)
            if (is_array($menuIds)) {
                foreach ($menuIds as $menuId) {
                    $menuId = intval($menuId);
                    if ($menuId <= 0) continue;
                    
                    $sqlInsert = "INSERT INTO table_men_usuario_menu (usuario_id, menu_id) VALUES (?, ?)";
                    $this->insert($sqlInsert, [$usuario_id, $menuId]);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error en update_user_permissions: " . $e->getMessage());
            return false;
        }
    }

    // =================================================================
    // UTILIDADES PARA LA UI DE ADMINISTRACIÓN
    // =================================================================

    /**
     * Carga la lista de usuarios para el selector.
     */
    public function cargar_usuarios() {
        $sql = "SELECT 
                    u.usuario_id, 
                    u.usuario_nick, 
                    p.personal_nombre, 
                    p.personal_apellido, 
                    r.rol_id, 
                    r.rol_nombre 
                FROM table_usuarios u 
                INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                INNER JOIN table_per_roles r ON u.usuario_rol_id = r.rol_id
                WHERE u.usuario_status = 1 
                ORDER BY p.personal_nombre, p.personal_apellido";
        return $this->select_all($sql);
    }

    /**
     * Obtiene la información de un usuario.
     */
    public function get_user_info($usuario_id) {
        $sql = "SELECT 
                    u.*, 
                    p.personal_nombre, 
                    p.personal_apellido, 
                    r.rol_nombre, 
                    d.departamento_nombre 
                FROM table_usuarios u 
                INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                INNER JOIN table_per_roles r ON u.usuario_rol_id = r.rol_id 
                LEFT JOIN table_departamentos d ON u.usuario_departamento_id = d.departamento_id 
                WHERE u.usuario_id = ?";
        return $this->select($sql, [intval($usuario_id)]);
    }

    /**
     * Devuelve todos los menús + rutas para la UI de asignación de permisos.
     */
    public function get_all_menus_for_permissions() {
        $menus = $this->getMenus();
        $arbol = $this->construirArbol($menus);
        return $arbol;
    }
}