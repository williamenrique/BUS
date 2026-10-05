<?php
header('Access-Control-Allow-Origin: *');
class Orden extends Controllers{
    public function __construct(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        parent::__construct();
        $this->ordenModel = new OrdenModel();
    }

    function getActiveSession(){
        $request = $this->model->getActiveSession($_SESSION['idUser']);
    }

    public function validateSession() {
        if (empty($_SESSION['login']) || empty($_SESSION['idUser'])) {
            return false;
        }
        if (isset($_SESSION['session_id'])) {
            $validSession = validateSessionDB($_SESSION['session_id'], $_SESSION['idUser']);
            if (!$validSession) {
                deleteSession($_SESSION['session_id']);
                return false;
            }
        }
        return true;
    }

    private function handleDatabaseError($error) {
        error_log("Error de BD en Orden: " . $error);
        if ($this->isAjaxRequest()) {
            $arrResponse = ['success' => false, 'message' => 'Error de BD', 'error' => $error];
            header('Content-Type: application/json');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function resolverInstitucion($institucion = 'actual') {
        return ($institucion === 'taller') ? 2 : 1;
    }

    private function obtenerInstitucionDeRequest(): int {
        if (isset($_GET['id_institucion'])) return intval($_GET['id_institucion']);
        if (isset($_POST['id_institucion'])) return intval($_POST['id_institucion']);
        return 1;
    }

    /**************************************************/
    /********* VISTAS *********************************/
    /**************************************************/

    public function orden($institucion = 'actual'){
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        $idInstitucion = $this->resolverInstitucion($institucion);
        $nombreInstitucion = $this->ordenModel->getNombreInstitucion($idInstitucion);
        $this->ordenModel->setInstitucion($idInstitucion);

        $data = [
            'page_tag' => "Gestión de Órdenes",
            'page_title' => "Sistema de Órdenes - " . obtenerIniciales($nombreInstitucion),
            'page_name' => "almacen",
            'page_link' => ($idInstitucion === 2) ? "despacho_taller" : "despacho",
            'page_functions' => "function.ordenes.js",
            'id_institucion' => $idInstitucion,
            'nombre_institucion' => obtenerIniciales($nombreInstitucion),
            'es_taller' => ($idInstitucion === 2)
        ];
        $this->views->getViews($this, "orden", $data);
    }

    public function ordenTaller() {
        $this->orden('taller');
    }

    /**************************************************/
    /********* API: DATOS INICIALES *******************/
    /**************************************************/

    public function getInitialData() {
        $arrResponse = ['success' => false, 'message' => 'No se pudieron cargar los datos iniciales.'];
        try {
            $idInstitucion = $this->obtenerInstitucionDeRequest();
            $this->ordenModel->setInstitucion($idInstitucion);

            $data = [
                'flota' => $this->ordenModel->selectListFlota(),
                'operadores' => $this->ordenModel->selectListOper(),
                'mecanicos' => $this->ordenModel->selectListMec(),
                'despachadores' => $this->ordenModel->selectListDesp(),
                'articulos' => $this->ordenModel->selectListArt()
            ];
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getUnidad($idUnidad){
        $arrResponse = ['success' => false, 'message' => 'Error al obtener datos de la unidad'];
        try {
            $idInstitucion = $this->obtenerInstitucionDeRequest();
            $this->ordenModel->setInstitucion($idInstitucion);

            $idUnidad = intval($idUnidad);
            $arrData = $this->ordenModel->selectUnidad($idUnidad);
            if (!empty($arrData)) {
                $arrResponse = ['success' => true, 'message' => 'Datos cargados', 'data' => $arrData];
            } else { 
                $arrResponse['message'] = 'Unidad no encontrada.'; 
            }
        } catch (Exception $e) {
            $arrResponse['msg'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getArt($idArt){
        $arrResponse = ['success' => false, 'message' => 'Error al obtener datos del artículo'];
        try {
            $idInstitucion = $this->obtenerInstitucionDeRequest();
            $this->ordenModel->setInstitucion($idInstitucion);

            $idArt = intval($idArt);
            $arrData = $this->ordenModel->selectArt($idArt);
            if (!empty($arrData)) {
                $arrResponse = ['success' => true, 'message' => 'Datos cargados', 'data' => $arrData];
            } else { 
                $arrResponse['message'] = 'Artículo no encontrado.'; 
            }
        } catch (Exception $e) {
            $arrResponse['msg'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getPersonal($idPersonal){
        $arrResponse = ['success' => false, 'message' => 'Error al obtener datos del personal'];
        try {
            $idPersonal = intval($idPersonal);
            $arrData = $this->ordenModel->selectPersonal($idPersonal);
            if (!empty($arrData)) {
                $arrResponse = ['success' => true, 'message' => 'Datos cargados', 'data' => $arrData];
            } else { 
                $arrResponse['message'] = 'Personal no encontrado.'; 
            }
        } catch (Exception $e) {
            $arrResponse['msg'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getOrden($idDespacho){
        $arrResponse = ['success' => false, 'message' => 'Error al obtener datos de la orden'];
        try {
            $idInstitucion = $this->obtenerInstitucionDeRequest();
            $this->ordenModel->setInstitucion($idInstitucion);

            $idDespacho = intval($idDespacho);
            if ($idDespacho > 0) {
                $orden = $this->ordenModel->selectOrdenForEdit($idDespacho);
                if (!empty($orden)) {
                    $articulos = $this->ordenModel->getListArtDesp($idDespacho);
                    $arrResponse = ['success' => true, 'data' => ['orden' => $orden, 'articulos' => $articulos]];
                } else {
                    $arrResponse['message'] = 'Orden no encontrada.';
                }
            }
        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**************************************************/
    /********* API: CREAR/ACTUALIZAR ******************/
    /**************************************************/

    public function setOrdenD(){
        $arrResponse = ['success' => false, 'message' => 'Error al registrar la orden'];
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $idInstitucion = intval($_POST['id_institucion'] ?? 1);
            $this->ordenModel->setInstitucion($idInstitucion);

            $idDespacho = intval($_POST['idDespacho'] ?? 0);
            $intUnidad = intval($_POST['listUnidad']);
            $intIdUser = $_SESSION['idUser'];
            $srtObs = !empty($_POST['txtObs']) ? strtoupper(strClean($_POST['txtObs'])) : '';
            $strDate = !empty($_POST['strDate']) ? $_POST['strDate'] : date('Y-m-d');

            $idOper = intval($_POST['listOperador']);
            $idMec = intval($_POST['listMecanico']);
            $idDesp = intval($_POST['listDespachador']);

            if ($intUnidad <= 0 || $idOper <= 0 || $idMec <= 0 || $idDesp <= 0) {
                throw new Exception('Debe seleccionar Unidad, Operador, Mecánico y Despachador.');
            }

            // Las columnas operador, mecanico, despachador (varchar) fueron eliminadas.
            // Solo se guardan los IDs que referencian a table_personal.
            // Los nombres se obtienen mediante JOINs al consultar.

            if ($idDespacho > 0) {
                $this->ordenModel->updateDespacho(
                    $idDespacho, $intUnidad, 
                    $idOper, $idMec, $idDesp,
                    $srtObs, $strDate
                );
                $this->ordenModel->revertirYLimpiar($idDespacho);
                $msg = 'Orden actualizada correctamente';
            } else {
                $idDespacho = $this->ordenModel->insertDespacho(
                    $intUnidad, 
                    $idOper, $idMec, $idDesp,
                    $intIdUser, $srtObs, $strDate
                );
                $msg = 'Orden registrada correctamente con ID: ' . $idDespacho;
            }

            if ($idDespacho > 0) {
                if (!empty($_POST['cod']) && is_array($_POST['cod'])) {
                    foreach ($_POST['cod'] as $index => $idArticulo) {
                        $intCant = floatval($_POST['cantidad'][$index]);
                        $this->ordenModel->insertRDespacho($idDespacho, $idArticulo, $intCant, $intUnidad, $strDate);
                        $this->ordenModel->updateCant($idArticulo, $intCant);
                    }
                }
                $arrResponse = ['success' => true, 'message' => $msg];
            }
        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**************************************************/
    /********* API: LISTADO ***************************/
    /**************************************************/

    public function getOrdenes() {
        try {
            $idInstitucion = $this->obtenerInstitucionDeRequest();
            $this->ordenModel->setInstitucion($idInstitucion);

            $ordenesData = $this->ordenModel->selectOrdenes();
            $arrResponse = ["data" => $ordenesData];
        } catch (Exception $e) {
            $arrResponse = ["data" => [], "error" => "Error: " . $e->getMessage()];
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getOrdenDetalle($idDespacho){
        $arrResponse = ['status' => false, 'msg' => 'Error'];
        try {
            $idInstitucion = $this->obtenerInstitucionDeRequest();
            $this->ordenModel->setInstitucion($idInstitucion);

            $idDespacho = intval($idDespacho);
            $orden = $this->ordenModel->selectDepacho($idDespacho);
            
            if (!empty($orden)) {
                $articulos = $this->ordenModel->getListArtDesp($idDespacho);
                $arrResponse = [
                    'success' => true, 
                    'message' => 'Datos cargados', 
                    'data' => array_merge($orden, ['articulos' => $articulos])
                ];
            }
        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getOrdenesPrint() {
        try {
            $idInstitucion = $this->obtenerInstitucionDeRequest();
            $this->ordenModel->setInstitucion($idInstitucion);

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            $ids = $data['ids'] ?? [];
            
            if (count($ids) !== 2) {
                throw new Exception("Debe seleccionar exactamente 2 órdenes.");
            }

            $ordenes = [];
            foreach ($ids as $id) {
                $orden = $this->ordenModel->selectDepacho($id);
                if ($orden) {
                    $orden['articulos'] = $this->ordenModel->getListArtDesp($id);
                    $ordenes[] = $orden;
                }
            }

            echo json_encode(['success' => true, 'data' => $ordenes]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        die();
    }

    public function getBuscarOrden(){
        try {
            $idInstitucion = $this->obtenerInstitucionDeRequest();
            $this->ordenModel->setInstitucion($idInstitucion);

            $strCod = !empty($_POST['txtCod']) ? $_POST['txtCod'] : '';
            $strFecha = !empty($_POST['txtFecha']) ? $_POST['txtFecha'] : '';
            $strUnidad = !empty($_POST['txtUnidad']) ? $_POST['txtUnidad'] : '';
            $strArt = !empty($_POST['txtArt']) ? $_POST['txtArt'] : '';

            $arrData = $this->ordenModel->getListBuscarOrdenes($strCod, $strFecha, $strUnidad, $strArt);
            $arrResponse = ['success' => true, 'data' => $arrData];
            if(empty($arrData)){
                $arrResponse['message'] = 'No se encontraron resultados.';
            }
            header('Content-Type: application/json');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            header('Content-Type: application/json');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /**************************************************/
    /********* API: ELIMINAR **************************/
    /**************************************************/

    public function delOrden(){
        $arrResponse = ['success' => false, 'message' => 'Error al eliminar la orden'];
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $arrResponse['message'] = 'Método no permitido';
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $idInstitucion = intval($_POST['id_institucion'] ?? 1);
            $this->ordenModel->setInstitucion($idInstitucion);

            $idDesp = intval($_POST['idDesp'] ?? 0);
            $motivo = strClean($_POST['srtText'] ?? '');
            $idUsuario = $_SESSION['idUser'];

            if ($idDesp <= 0 || empty($motivo)) {
                throw new Exception('Datos incompletos para eliminar la orden.');
            }

            $articulos = $this->ordenModel->artDespacho($idDesp);
            foreach ($articulos as $articulo) {
                $nuevaCantidad = $articulo['cant_producto'] + $articulo['cant_despacho'];
                $this->ordenModel->updateCantN($articulo['id_producto'], $nuevaCantidad);
            }

            $success = $this->ordenModel->delDesp($idDesp, $motivo, $idUsuario);

            if ($success) {
                $arrResponse = ['success' => true, 'message' => 'Orden eliminada y stock revertido correctamente'];
            }
        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**************************************************/
    /********* API: STATS *****************************/
    /**************************************************/

    public function getMonthlyStats() {
        $arrResponse = ['success' => false, 'message' => 'No se pudieron cargar las estadísticas.'];
        try {
            $idInstitucion = $this->obtenerInstitucionDeRequest();
            $this->ordenModel->setInstitucion($idInstitucion);

            $stats = $this->ordenModel->getMonthlyOrderCount();
            $target = 120;
            $currentCount = $stats['total_ordenes'] ?? 0;
            $percentage = ($target > 0) ? round(($currentCount / $target) * 100) : 0;
    
            $data = [
                'current_orders' => $currentCount,
                'target_orders' => $target,
                'percentage' => $percentage > 100 ? 100 : $percentage
            ];
            
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
}
?>