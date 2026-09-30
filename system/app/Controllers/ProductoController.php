<?php
header('Access-Control-Allow-Origin: *');
class Producto extends Controllers{
    private $db;
    public function __construct(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        parent::__construct();
        $this->model = new ProductoModel();
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
        error_log("Error de BD en controlador Producto: " . $error);
        if ($this->isAjaxRequest()) {
            $arrResponse = [
                'success' => false,
                'message' => 'Error de conexión a la base de datos',
                'error' => $error
            ];
            header('Content-Type: application/json');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        } else {
            $_SESSION['error_message'] = "Error de base de datos: " . $error;
        }
    }

    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Determina el ID de institución a partir del parámetro.
     */
    private function resolverInstitucion($institucion = 'actual') {
        return ($institucion === 'taller') ? 2 : 1;
    }

    /**************************************************/
    /********* VISTAS *********************************/
    /**************************************************/

    /**
     * Vista de productos (SSLMTY).
     * URL: producto/producto
     */
    public function producto($institucion = 'actual'){
        $idInstitucion = $this->resolverInstitucion($institucion);
        $nombreInstitucion = $this->model->getNombreInstitucion($idInstitucion);
        $this->model->setInstitucion($idInstitucion);

        $data = [
            'page_tag' => "GESTION PRODUCTOS",
            'page_title' => "Productos - " . $nombreInstitucion,
            'page_name' => "almacen",
            'page_link' => ($idInstitucion === 2) ? "producto_taller" : "producto",
            'page_functions' => "function.producto.js",
            'id_institucion' => $idInstitucion,
            'nombre_institucion' => $nombreInstitucion,
            'es_taller' => ($idInstitucion === 2)
        ];
        $this->views->getViews($this, "producto", $data);
    }

    /**
     * Wrapper para Taller: producto/productoTaller
     */
    public function productoTaller() {
        $this->producto('taller');
    }

    /**
     * Vista de historial (SSLMTY).
     * URL: producto/historial
     */
    public function historial($institucion = 'actual') {
        $idInstitucion = $this->resolverInstitucion($institucion);
        $nombreInstitucion = $this->model->getNombreInstitucion($idInstitucion);
        $this->model->setInstitucion($idInstitucion);

        $data = [
            'page_tag' => "Historial de Productos",
            'page_title' => "Historial - " . $nombreInstitucion,
            'page_name' => "almacen",
            'page_link' => ($idInstitucion === 2) ? "historial_taller" : "historial",
            'page_functions' => "function.producto.js",
            'id_institucion' => $idInstitucion,
            'nombre_institucion' => $nombreInstitucion,
            'es_taller' => ($idInstitucion === 2)
        ];
        $this->views->getViews($this, "historial", $data);
    }

    /**
     * Wrapper para Taller: producto/historialTaller
     */
    public function historialTaller() {
        $this->historial('taller');
    }

    /**
     * Vista de inventario (SSLMTY).
     * URL: producto/inventario
     */
    public function inventario($institucion = 'actual'){
        $idInstitucion = $this->resolverInstitucion($institucion);
        $nombreInstitucion = $this->model->getNombreInstitucion($idInstitucion);
        $this->model->setInstitucion($idInstitucion);

        $data = [
            'page_tag' => "INVENTARIO",
            'page_title' => "Inventario - " . $nombreInstitucion,
            'page_name' => "almacen",
            'page_link' => ($idInstitucion === 2) ? "inventario_taller" : "inventario",
            'page_functions' => "function.producto.js",
            'id_institucion' => $idInstitucion,
            'nombre_institucion' => $nombreInstitucion,
            'es_taller' => ($idInstitucion === 2)
        ];
        $this->views->getViews($this, "inventario", $data);
    }

    /**
     * Wrapper para Taller: producto/inventarioTaller
     */
    public function inventarioTaller() {
        $this->inventario('taller');
    }

    /**************************************************/
    /********* API: PRODUCTOS *************************/
    /**************************************************/

    public function getInitialData() {
        $arrResponse = ['success' => false, 'message' => 'No se pudieron cargar los datos iniciales.'];
        try {
            $idInstitucion = intval($_GET['id_institucion'] ?? 1);
            $this->model->setInstitucion($idInstitucion);

            $data = [
                'enlaces' => $this->model->selectEnlace(),
                'proveedores' => $this->model->selectProvee(),
                'ubicaciones' => $this->model->selectUbic(),
                'productos' => $this->model->selectListProductos()
            ];
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function setProducto(){
        $arrResponse = ['success' => false, 'message' => 'Error al registrar el producto.'];
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $idInstitucion = intval($_POST['id_institucion'] ?? 1);
            $this->model->setInstitucion($idInstitucion);

            $srtArticlo = ucwords(strClean($_POST['txtArticulo']));
            $intModelo = intval($_POST['listEnlace']);
            $intProveedor = intval($_POST['listProveedor']);
            $intUbicacion = intval($_POST['listUbicacion']);
            $srtCant = floatval($_POST['txtCantidad']);
            $intOptionArticulo = intval($_POST['optionsArticulo']);
            $strPresentArticulo = ucwords(strClean($_POST['optionsPresentacion']));

            if (empty($srtArticlo) || $intProveedor == 0 || $intUbicacion == 0 || $intModelo == 0 || empty($srtCant)) {
                throw new Exception('Todos los campos son obligatorios.');
            }

            $request = $this->model->insertProducto($srtArticlo, $intModelo, $intProveedor, $intUbicacion, $srtCant, $intOptionArticulo, $strPresentArticulo);

            if ($request > 0) {
                $arrResponse = ['success' => true, 'message' => 'Producto guardado correctamente con ID: ' . $request];
            } else {
                $arrResponse = ['success' => false, 'message' => '¡Atención! El producto ya existe en esta institución.'];
            }
        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getProductos(){
        try {
            $idInstitucion = intval($_GET['id_institucion'] ?? 1);
            $this->model->setInstitucion($idInstitucion);

            $draw = intval($_POST['draw'] ?? 0);
            $start = intval($_POST['start'] ?? 0);
            $length = intval($_POST['length'] ?? 10);
            $searchValue = $_POST['search']['value'] ?? '';
            $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
            $orderColumnName = $_POST['columns'][$orderColumnIndex]['data'] ?? 'id_producto';
            $orderDir = $_POST['order'][0]['dir'] ?? 'desc';

            $productosData = $this->model->getProductosServerSide($start, $length, $searchValue, $orderColumnName, $orderDir);

            $arrResponse = [
                "draw" => $draw,
                "recordsTotal" => $productosData['total'],
                "recordsFiltered" => $productosData['total_filtered'],
                "data" => $productosData['data']
            ];
        } catch (Exception $e) {
            $arrResponse = ["error" => "Error al cargar productos: " . $e->getMessage()];
        }

        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getProducto(int $idProducto){
        $arrResponse = ['success' => false, 'message' => 'Producto no encontrado.'];
        try {
            $idInstitucion = intval($_GET['id_institucion'] ?? 1);
            $this->model->setInstitucion($idInstitucion);

            $idProducto = intval($idProducto);
            if ($idProducto > 0) {
                $arrData = $this->model->selectProducto($idProducto);
                if (!empty($arrData)) {
                    $arrResponse = ['success' => true, 'data' => $arrData];
                }
            }
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error al obtener el producto: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function updateProducto(){
        $arrResponse = ['success' => false, 'message' => 'Error al actualizar el stock.'];
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $idInstitucion = intval($_POST['id_institucion'] ?? 1);
            $this->model->setInstitucion($idInstitucion);

            $idProducto = intval($_POST['listArticuloExistente']);
            $cantActual = floatval($_POST['txtCantidadActual']);
            $cantNueva = floatval($_POST['txtCantidadMas']);

            if ($idProducto == 0 || empty($cantNueva)) {
                throw new Exception('Debe seleccionar un producto y agregar una cantidad.');
            }
            $total = $cantActual + $cantNueva;
            $this->model->upCantProducto($idProducto, $total);
            $arrResponse = ['success' => true, 'message' => 'Stock actualizado correctamente.'];
        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function delProducto() {
        $arrResponse = ['success' => false, 'message' => 'Error al eliminar el producto.'];
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_producto'])) {
                throw new Exception('Datos incorrectos.');
            }
            $idInstitucion = intval($_POST['id_institucion'] ?? 1);
            $this->model->setInstitucion($idInstitucion);

            $idProducto = intval($_POST['id_producto']);
            $request = $this->model->delProducto($idProducto);
            if ($request) {
                $arrResponse = ['success' => true, 'message' => 'Producto eliminado correctamente.'];
            } else {
                throw new Exception('No se pudo eliminar el producto.');
            }
        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**************************************************/
    /********* API: HISTORIAL *************************/
    /**************************************************/

    public function getHistorySummary() {
        $arrResponse = ['success' => false, 'data' => [], 'message' => 'No se encontró historial.'];
        try {
            $idInstitucion = intval($_GET['id_institucion'] ?? 1);
            $this->model->setInstitucion($idInstitucion);

            $arrData = $this->model->getHistorySummary();
            if (!empty($arrData)) {
                $arrResponse = ['success' => true, 'data' => $arrData];
            }
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error al cargar el historial: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getHistorySummaryByDateRange() {
        $arrResponse = ['success' => false, 'data' => [], 'message' => 'No se encontró historial en el rango especificado.'];
        try {
            $idInstitucion = intval($_POST['id_institucion'] ?? 1);
            $this->model->setInstitucion($idInstitucion);

            $fechaInicio = $_POST['fechaInicio'] ?? null;
            $fechaFin = $_POST['fechaFin'] ?? null;

            if (!$fechaInicio || !$fechaFin) {
                $arrResponse['message'] = 'Fechas de inicio y fin son requeridas.';
            } else {
                $arrData = $this->model->getHistorySummaryByDateRange($fechaInicio, $fechaFin);
                if (!empty($arrData)) {
                    $arrResponse = ['success' => true, 'data' => $arrData];
                }
            }
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error al cargar el historial: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getDetailHistory($idProducto) {
        try {
            $idInstitucion = intval($_POST['id_institucion'] ?? 1);
            $this->model->setInstitucion($idInstitucion);

            $idProducto = intval($idProducto);
            if ($idProducto <= 0) {
                throw new Exception('ID de producto no válido.');
            }

            $postData = json_decode(file_get_contents('php://input'), true);
            $page = isset($postData['page']) ? intval($postData['page']) : 1;
            $perPage = 5;

            $historialData = $this->model->getDetailHistoryPaginated($idProducto, $page, $perPage);

            $arrResponse = [
                'success' => true,
                'data' => $historialData['items'],
                'pagination' => $historialData['pagination']
            ];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => 'Error al obtener el historial: ' . $e->getMessage()];
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**************************************************/
    /********* API: INVENTARIO ************************/
    /**************************************************/

    public function getInventario() {
        try {
            $idInstitucion = intval($_GET['id_institucion'] ?? 1);
            $this->model->setInstitucion($idInstitucion);

            $draw = intval($_POST['draw'] ?? 0);
            $start = intval($_POST['start'] ?? 0);
            $length = intval($_POST['length'] ?? 10);
            $searchValue = $_POST['search']['value'] ?? '';
            $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
            $orderColumnName = $_POST['columns'][$orderColumnIndex]['data'] ?? 'id_producto';
            $orderDir = $_POST['order'][0]['dir'] ?? 'asc';

            $inventarioData = $this->model->getInventario($start, $length, $searchValue, $orderColumnName, $orderDir);

            $arrResponse = [
                "draw" => $draw,
                "recordsTotal" => $inventarioData['total'],
                "recordsFiltered" => $inventarioData['total_filtered'],
                "data" => $inventarioData['data']
            ];
        } catch (Exception $e) {
            $arrResponse = ["error" => "Error al cargar el inventario: " . $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
}