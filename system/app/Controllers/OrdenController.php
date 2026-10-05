<?php
header('Access-Control-Allow-Origin: *');
class Orden extends Controllers{
    private $db; //para inicializar la base de datos
    public function __construct(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Validar sesión de manera más robusta
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        //invocar para que se ejecute el metodo de la herencia
        parent::__construct();
        $this->ordenModel = new OrdenModel();

    }
    /*manejo de sesiones activas*/
	function getActiveSession(){
		$reuest = $this->model->getActiveSession($_SESSION['idUser']);
	}
    public function validateSession() {
        // Verificar si la sesión está iniciada y es válida
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
    /*fin manejo de sesiones activas*/
    /**inicio de manejo de errores en cada controlador debe estar */
	private function handleDatabaseError($error) {
        // Log del error
        error_log("Error de BD en controlador User: " . $error);
        // Puedes elegir cómo manejar el error:
        // 1. Redirigir a una página de error
        // 2. Mostrar un mensaje JSON (para APIs)
        // 3. Guardar en variable para mostrar en vista
        // Para métodos que devuelven JSON:
        if ($this->isAja|xRequest()) {
            $arrResponse = [
                'success' => false,
                'message' => 'Error de conexión a la base de datos',
                'error' => $error
            ];
            header('Content-Type: application/json');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        } else {
            // Para vistas HTML, podrías guardar el error para mostrarlo
            $_SESSION['error_message'] = "Error de base de datos: " . $error;
        }
    }
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
	/**fin de manejo de errores en cada controlador debe estar*/
    public function orden(){
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        $data = [
            'page_tag' => "Gestión de Órdenes",
            'page_title' => "Sistema de Órdenes",
            'page_name' => "almacen",
            'page_link' => "despacho",
            'page_functions' => "function.ordenes.js"
        ];
        $this->views->getViews($this, "orden", $data);
    }

    public function getInitialData() {
        $arrResponse = ['success' => false, 'message' => 'No se pudieron cargar los datos iniciales.'];
        try {
            $data = [
                'flota' => $this->ordenModel->selectListFlota(),
                'operadores' => $this->ordenModel->selectListOper(),
                'mecanicos' => $this->ordenModel->selectListMec(),
                'despachadores' => $this->ordenModel->selectListDesp(),
                'articulos' => $this->ordenModel->selectListArt()
            ];
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
            $arrResponse['message'] = $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }



    public function getListFlota(){
        try {
            $arrData = $this->ordenModel->selectListFlota();
            $htmlOptions = '<option value="0">Seleccione una unidad</option>';
            if (!empty($arrData)) {
                foreach ($arrData as $flota) {
                    $htmlOptions .= '<option value="' . $flota['id_flota'] . '">' . $flota['id_unidad'] . ' - ' . $flota['modelo_unidad'] . '</option>';
                }
            }
            $arrResponse = ['success' => true, 'data' => $arrData];
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    public function getUnidad($idUnidad){
        $arrResponse = ['success' => false, 'message' => 'Error al obtener datos de la unidad'];
        try {
            $idUnidad = intval($idUnidad);
            $arrData = $this->ordenModel->selectUnidad($idUnidad);
            if (!empty($arrData)) {
                $arrResponse = ['success' => true, 'message' => 'Datos cargados', 'data' => $arrData];
            } else { $arrResponse['message'] = 'Unidad no encontrada.'; }
        } catch (Exception $e) {
            $arrResponse['msg'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getListOper(){
        try {
            $arrData = $this->ordenModel->selectListOper();            
            $arrResponse = ['success' => true, 'data' => $arrData];
            header('Content-Type: application/json');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    public function getListMec(){
        try {
            $arrData = $this->ordenModel->selectListMec();
            $arrResponse = ['success' => true, 'data' => $arrData];
            header('Content-Type: application/json');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    public function getListDesp(){
        try {
            $arrData = $this->ordenModel->selectListDesp();
            $arrResponse = ['success' => true, 'data' => $arrData];
            header('Content-Type: application/json');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    public function getPersonal($idPersonal){
        $arrResponse = ['success' => false, 'message' => 'Error al obtener datos del personal'];
        try {
            $idPersonal = intval($idPersonal);
            $arrData = $this->ordenModel->selectPersonal($idPersonal);
            if (!empty($arrData)) {
                $arrResponse = ['success' => true, 'message' => 'Datos cargados', 'data' => $arrData];
            } else { $arrResponse['message'] = 'Personal no encontrado.'; }
        } catch (Exception $e) {
            $arrResponse['msg'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getListArt(){
        try {
            $arrData = $this->ordenModel->selectListArt();
            $arrResponse = ['success' => true, 'data' => $arrData];
            header('Content-Type: application/json');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    public function getArt($idArt){
        $arrResponse = ['success' => false, 'message' => 'Error al obtener datos del artículo'];
        try {
            $idArt = intval($idArt);
            $arrData = $this->ordenModel->selectArt($idArt);
            if (!empty($arrData)) {
                $arrResponse = ['success' => true, 'message' => 'Datos cargados', 'data' => $arrData];
            } else { $arrResponse['message'] = 'Artículo no encontrado.'; }
        } catch (Exception $e) {
            $arrResponse['msg'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getOrden($idDespacho){
        $arrResponse = ['success' => false, 'message' => 'Error al obtener datos de la orden'];
        try {
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

    public function setOrdenD(){
        $arrResponse = ['success' => false, 'message' => 'Error al registrar la orden'];
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $idDespacho = intval($_POST['idDespacho'] ?? 0);
            $intUnidad = intval($_POST['listUnidad']);
            $intIdUser = $_SESSION['idUser'];
            $srtObs = !empty($_POST['txtObs']) ? strtoupper(strClean($_POST['txtObs'])) : '';
            // Se elimina strClean para no corromper el formato de fecha YYYY-MM-DD que necesita la tabla de compras pendientes
            $strDate = !empty($_POST['strDate']) ? $_POST['strDate'] : date('Y-m-d');

            $idOper = intval($_POST['listOperador']);
            $idMec = intval($_POST['listMecanico']);
            $idDesp = intval($_POST['listDespachador']);

            // Validar que todos los IDs sean válidos
            if ($intUnidad <= 0 || $idOper <= 0 || $idMec <= 0 || $idDesp <= 0) {
                throw new Exception('Debe seleccionar Unidad, Operador, Mecánico y Despachador.');
            }

            // Obtener los nombres completos del personal
            $operadorData = $this->ordenModel->selectPersonal($idOper);
            $mecanicoData = $this->ordenModel->selectPersonal($idMec);
            $despachadorData = $this->ordenModel->selectPersonal($idDesp);

            $strOper = !empty($operadorData) ? strtoupper($operadorData['personal_nombre'] . ' ' . $operadorData['personal_apellido']) : 'N/A';
            $strMec = !empty($mecanicoData) ? strtoupper($mecanicoData['personal_nombre'] . ' ' . $mecanicoData['personal_apellido']) : 'N/A';
            $strDesp = !empty($despachadorData) ? strtoupper($despachadorData['personal_nombre'] . ' ' . $despachadorData['personal_apellido']) : 'N/A';

            if ($idDespacho > 0) {
                // Actualizar Orden
                // 1. Actualizar cabecera
                $this->ordenModel->updateDespacho($idDespacho, $intUnidad, $strOper, $strMec, $strDesp, $srtObs, $strDate);
                
                // 2. Revertir stock de artículos anteriores y limpiar detalles
                $this->ordenModel->revertirYLimpiar($idDespacho);
                
                $msg = 'Orden actualizada correctamente';
            } else {
                // Crear Orden
                $idDespacho = $this->ordenModel->insertDespacho($intUnidad, $strOper, $strMec, $strDesp, $intIdUser, $srtObs, $strDate);
                $msg = 'Orden registrada correctamente con ID: ' . $idDespacho;
            }

            if ($idDespacho > 0) {
                if (!empty($_POST['cod']) && is_array($_POST['cod'])) {
                    foreach ($_POST['cod'] as $index => $idArticulo) {
                        $intCant = floatval($_POST['cantidad'][$index]);
                        // Insertar relación y descontar stock (funciona igual para create y update tras limpiar)
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

    public function getOrdenes() {
        try {
            // Obtener todos los datos de las órdenes desde el modelo
            $ordenesData = $this->ordenModel->selectOrdenes();
    
            $arrResponse = [
                // DataTables en modo cliente espera los datos en la clave "data"
                "data" => $ordenesData
            ];
        } catch (Exception $e) {
            $arrResponse = ["data" => [], "error" => "Error al cargar las órdenes: " . $e->getMessage()];
        }

        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getOrdenDetalle($idDespacho){
    $arrResponse = ['status' => false, 'msg' => 'Error al obtener detalles de la orden'];
        try {
            $idDespacho = intval($idDespacho);
            // Obtener información básica de la orden
            $orden = $this->ordenModel->selectDepacho($idDespacho);
            
            if (!empty($orden)) {
                // Obtener artículos de la orden
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
            $arrResponse = ['success' => false, 'message' => 'Error en la búsqueda: ' . $e->getMessage()];
            header('Content-Type: application/json');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function delOrden(){
        $arrResponse = ['success' => false, 'message' => 'Error al eliminar la orden'];
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $arrResponse['message'] = 'Método no permitido';
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
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

            // Cambiar estado de la orden y registrar en historial
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


    public function reporteDesp($idDespacho){
        try {
            $arrData = $this->ordenModel->selectDepacho($idDespacho);
            $arrArticulos = $this->ordenModel->getListArtDesp($idDespacho);
            
            // Guardar datos en sesión para el PDF
            $_SESSION['reporte_despacho'] = [
                'orden' => $arrData,
                'articulos' => $arrArticulos
            ];
            
            echo json_encode(['success' => true, 'message' => 'Reporte generado correctamente']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al generar reporte: ' . $e->getMessage()]);
        }
        die();
    }

    public function getMonthlyStats() {
        $arrResponse = ['success' => false, 'message' => 'No se pudieron cargar las estadísticas.'];
        try {
            $stats = $this->ordenModel->getMonthlyOrderCount();
            $target = 120; // Meta mensual, se puede hacer configurable después
            $currentCount = $stats['total_ordenes'] ?? 0;
            $percentage = ($target > 0) ? round(($currentCount / $target) * 100) : 0;
    
            $data = [
                'current_orders' => $currentCount,
                'target_orders' => $target,
                'percentage' => $percentage > 100 ? 100 : $percentage // Cap at 100%
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