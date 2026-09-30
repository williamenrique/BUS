<?php
header('Access-Control-Allow-Origin: *');
class Menu extends Controllers{
    public function __construct(){
        session_start();
        if (empty($_SESSION['login']) && !isset($_SESSION['srtCodigo'])) {
            header("Location:".base_url().'login');
        }
        parent::__construct();
    }
    
    public function menu(){
        $data = [
            'page_tag' => "Gestión de Menús",
            'page_title' => "Gestión de Menús y Permisos",
            'page_name' => "menu",
            'page_link' => "menu",
            'page_menu_open' => "dashboard",
            'page_link_acitvo' => "link-home",
            'page_functions' => "functionmenu.js"
        ];
        $this->views->getViews($this, "menu", $data);
    }

    // =================================================================
    // PERMISOS DE USUARIOS
    // =================================================================

    public function cargar_usuarios(){
        try {
            $usuarios = $this->model->cargar_usuarios();
            $arrResponse = array('status' => true, 'data' => $usuarios);
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error al cargar usuarios: ' . $e->getMessage());
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function get_user_info($usuario_id){
        try {
            $usuario_info = $this->model->get_user_info($usuario_id);
            $arrResponse = array('status' => true, 'data' => $usuario_info);
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error al cargar información del usuario: ' . $e->getMessage());
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    /**
     * Devuelve el árbol COMPLETO de menús + rutas para la UI de asignación.
     * Ya no hay submenús separados: todo es un árbol.
     */
    public function get_all_menus(){
        try {
            $arbol = $this->model->get_all_menus_for_permissions();
            $arrResponse = array('status' => true, 'data' => $arbol);
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error al cargar menús: ' . $e->getMessage());
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function get_user_permissions($usuario_id){
        try {
            $permisos = $this->model->get_user_permissions($usuario_id);
            $arrResponse = array('status' => true, 'data' => $permisos);
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error al cargar permisos: ' . $e->getMessage());
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    /**
     * Actualiza los permisos de un usuario.
     * Recibe: { user_id, permissions: [menu_id, menu_id, ...] }
     * (El frontend manda solo los IDs de menús marcados)
     */
    public function update_user_permissions(){
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['user_id']) || !isset($data['permissions'])) {
                throw new Exception("Datos incompletos");
            }
            
            $usuario_id = intval($data['user_id']);
            $permisos = $data['permissions'];
            
            if ($usuario_id <= 0 || !is_array($permisos)) {
                throw new Exception("Datos inválidos");
            }
            
            // Los permisos vienen como array de menu_id (int) o array de objetos
            // Normalizamos a un array de IDs
            $menuIds = [];
            foreach ($permisos as $permiso) {
                if (is_array($permiso) && isset($permiso['menu_id'])) {
                    $menuIds[] = intval($permiso['menu_id']);
                } elseif (is_numeric($permiso)) {
                    $menuIds[] = intval($permiso);
                }
            }
            
            $result = $this->model->update_user_permissions($usuario_id, $menuIds);

            if ($result) {
                $arrResponse = array('status' => true, 'msg' => 'Permisos actualizados correctamente');
            } else {
                $arrResponse = array('status' => false, 'msg' => 'Error al actualizar permisos');
            }
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error: ' . $e->getMessage());
        }
        
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    // =================================================================
    // CRUD DE MENÚS
    // =================================================================

    /**
     * Lista todos los menús en formato árbol para la UI.
     */
    public function listar_menus(){
        try {
            $menus = $this->model->getMenusTree();
            $arrResponse = array('status' => true, 'data' => $menus);
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error al cargar menús: ' . $e->getMessage());
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Lista todos los menús en formato plano con rutas (para selectores).
     */
    public function listar_menus_plano(){
        try {
            $menus = $this->model->getMenusConRutas();
            $arrResponse = array('status' => true, 'data' => $menus);
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error al cargar menús: ' . $e->getMessage());
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Crea un menú nuevo.
     * Datos esperados: menu_padre_id, menu_nombre, menu_icono, menu_ruta, menu_orden, menu_scope, rutas[]
     */
    public function crear_menu(){
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if(empty($data['menu_nombre'])){
                $arrResponse = array('status' => false, 'msg' => 'El nombre del menú es obligatorio');
                echo json_encode($arrResponse);
                die();
            }
            
            $result = $this->model->insertMenu($data);
            
            if($result > 0){
                $arrResponse = array('status' => true, 'msg' => 'Menú creado correctamente', 'menu_id' => $result);
            } else {
                $arrResponse = array('status' => false, 'msg' => 'Error al crear menú');
            }
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error: ' . $e->getMessage());
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Actualiza un menú existente.
     */
    public function actualizar_menu(){
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if(empty($data['menu_id']) || empty($data['menu_nombre'])){
                $arrResponse = array('status' => false, 'msg' => 'Datos incompletos');
                echo json_encode($arrResponse);
                die();
            }
            
            $result = $this->model->updateMenu($data);
            
            if($result){
                $arrResponse = array('status' => true, 'msg' => 'Menú actualizado correctamente');
            } else {
                $arrResponse = array('status' => false, 'msg' => 'Error al actualizar menú');
            }
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error: ' . $e->getMessage());
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Elimina (desactiva) un menú.
     */
    public function eliminar_menu(){
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if(empty($data['menu_id'])){
                $arrResponse = array('status' => false, 'msg' => 'ID de menú requerido');
                echo json_encode($arrResponse);
                die();
            }

            $result = $this->model->deleteMenu($data['menu_id']);
            
            if($result){
                $arrResponse = array('status' => true, 'msg' => 'Menú eliminado correctamente');
            } else {
                $arrResponse = array('status' => false, 'msg' => 'Error al eliminar menú');
            }
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error: ' . $e->getMessage());
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Obtiene los detalles de un menú (para edición).
     */
    public function get_menu($menu_id){
        try {
            $menu = $this->model->getMenuById($menu_id);
            if ($menu) {
                $menu['rutas'] = $this->model->getRutasByMenu($menu_id);
                $arrResponse = array('status' => true, 'data' => $menu);
            } else {
                $arrResponse = array('status' => false, 'msg' => 'Menú no encontrado');
            }
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error: ' . $e->getMessage());
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Lista los menús candidatos a ser padre (raíces o cualquier menú existente).
     */
    public function get_menus_padres(){
        try {
            $menus = $this->model->getMenus();
            $arrResponse = array('status' => true, 'data' => $menus);
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error: ' . $e->getMessage());
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    // =================================================================
    // RUTAS (patrones de activación)
    // =================================================================

    /**
     * Agrega un patrón de ruta a un menú.
     */
    public function agregar_ruta(){
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if(empty($data['menu_id']) || empty($data['patron'])){
                $arrResponse = array('status' => false, 'msg' => 'Datos incompletos');
                echo json_encode($arrResponse);
                die();
            }
            
            $result = $this->model->insertRuta($data['menu_id'], $data['patron']);
            
            if($result > 0){
                $arrResponse = array('status' => true, 'msg' => 'Ruta agregada correctamente', 'ruta_id' => $result);
            } else {
                $arrResponse = array('status' => false, 'msg' => 'Error al agregar ruta');
            }
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error: ' . $e->getMessage());
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Elimina un patrón de ruta.
     */
    public function eliminar_ruta(){
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if(empty($data['ruta_id'])){
                $arrResponse = array('status' => false, 'msg' => 'ID de ruta requerido');
                echo json_encode($arrResponse);
                die();
            }
            
            $result = $this->model->eliminarRuta($data['ruta_id']);
            
            if($result){
                $arrResponse = array('status' => true, 'msg' => 'Ruta eliminada correctamente');
            } else {
                $arrResponse = array('status' => false, 'msg' => 'Error al eliminar ruta');
            }
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => 'Error: ' . $e->getMessage());
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
}