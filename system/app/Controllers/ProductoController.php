<?php
header('Access-Control-Allow-Origin: *');
class Producto extends Controllers{
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
        $this->model = new ProductoModel(); // Usar el modelo autocargado

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
    /**************************************************/
    /**************** TODO: GESTIÓN DE PRODUCTOS ******/
    /**************************************************/

    /**
     * Muestra la vista principal para la gestión de productos.
     */
    public function producto(){
        $data = [
            'page_tag' => "GESTION PRODUCTOS",
            'page_title' => "Pagina Principal",
            'page_name' => "almacen",
            'page_link' => "producto",
            'page_functions' => "function.producto.js",
            'page_extra_scripts' => ["DataTableRefactor.js"] // Agregar script de tablas dinámicas
        ];
        $this->views->getViews($this, "producto", $data);
    }

    /**
     * Obtiene los datos iniciales para los selects de los formularios.
     */
    public function getInitialData() {
        $arrResponse = ['success' => false, 'message' => 'No se pudieron cargar los datos iniciales.'];
        try {
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

    /**
     * Registra un nuevo producto en la base de datos.
     * Recibe los datos del producto por POST.
     */
    public function setProducto(){
        $arrResponse = ['success' => false, 'message' => 'Error al registrar el producto.'];
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $srtArticlo = strtoupper(strClean($_POST['txtArticulo']));
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
                $arrResponse = ['success' => false, 'message' => '¡Atención! El producto ya existe.'];
            }
        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Obtiene la lista de todos los productos para la DataTable.
     * @return string JSON con la lista de productos.
     */
    public function getProductos(){
        $arrResponse = array('success' => false, 'data' => array());
        try {
            $productos = $this->model->selectInventarioAll();
            $arrResponse = [
                'success' => true,
                'data' => $productos
            ];
        } catch (Exception $e) {
            $arrResponse = [
                'success' => false,
                'message' => 'Error al cargar los productos: ' . $e->getMessage()
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Obtiene los datos de un producto específico por su ID.
     * @param int $idProducto El ID del producto a consultar.
     * @return string JSON con los datos del producto.
     */
    public function getProducto(int $idProducto){
        $arrResponse = ['success' => false, 'message' => 'Producto no encontrado.'];
        try {
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

    /**
     * Actualiza la cantidad de stock de un producto existente.
     * Recibe los datos por POST.
     */
    public function updateProducto(){
        $arrResponse = ['success' => false, 'message' => 'Error al actualizar el stock.'];
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            $idProducto = intval($_POST['listArticuloExistente']);
            $cantActual = floatval($_POST['txtCantidadActual']);
            $cantNueva = floatval($_POST['txtCantidadMas']);

            if ($idProducto == 0 || empty($cantNueva)) {
                throw new Exception('Debe seleccionar un producto y agregar una cantidad.');
            }
            $total = $cantActual + $cantNueva;
            $request = $this->model->upCantProducto($idProducto, $total);
            $arrResponse = ['success' => true, 'message' => 'Stock actualizado correctamente.'];

        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Realiza una eliminación lógica de un producto.
     * Recibe el ID del producto por POST.
     */
    public function delProducto() {
        $arrResponse = ['success' => false, 'message' => 'Error al eliminar el producto.'];
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id_producto'])) {
                throw new Exception('Datos incorrectos.');
            }
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
    /************** TODO: HISTORIAL DE PRODUCTOS ******/
    /**************************************************/

    /**
     * Muestra la vista para el historial de movimientos de productos.
     */
    public function historial() {
        $data = [
            'page_tag' => "Historial de Productos",
            'page_title' => "Historial de Productos",
            'page_name' => "almacen",
            'page_link' => "historial",
            'page_functions' => "function.producto.js"
        ];
        $this->views->getViews($this, "historial", $data);
    }

    /**
     * Obtiene el resumen del historial de todos los productos para la tabla.
     * @return string JSON con el resumen del historial.
     */
    public function getHistorySummary() {
        $arrResponse = ['success' => false, 'data' => [], 'message' => 'No se encontró historial.'];
        try {
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

    /**
     * Obtiene el historial detallado de un producto específico para el timeline.
     * @param int $idProducto El ID del producto.
     * @return string JSON con el historial detallado.
     */
    public function getDetailHistory($idProducto) {
        try {
            $idProducto = intval($idProducto);
            if ($idProducto <= 0) {
                throw new Exception('ID de producto no válido.');
            }

            // Obtener la página solicitada del cuerpo de la solicitud POST
            $postData = json_decode(file_get_contents('php://input'), true);
            $page = isset($postData['page']) ? intval($postData['page']) : 1;
            $perPage = 5; // 5 eventos por página

            // Obtener los datos paginados desde el modelo
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
    /*********** TODO: vista de inventario*******/
    /**************************************************/

    /**
     * Muestra la vista principal para inventario de productos.
     */
    public function inventario(){
        $data = [
            'page_tag' => "GESTION PRODUCTOS",
            'page_title' => "Pagina Principal",
            'page_name' => "almacen",
            'page_link' => "inventario",
            'page_functions' => "function.producto.js",
            'page_extra_scripts' => ["DataTableRefactor.js"] // Agregar script de tablas dinámicas
        ];
        $this->views->getViews($this, "inventario", $data);
    }

    /**
     * Obtiene la lista de productos para la tabla de inventario.
     */
    public function getInventario() {
        $arrResponse = array('success' => false, 'data' => array());
        try {
            $inventario = $this->model->selectInventarioAll();
            $arrResponse = [
                'success' => true,
                'data' => $inventario
            ];
        } catch (Exception $e) {
            $arrResponse = [
                'success' => false,
                'message' => 'Error al cargar el inventario: ' . $e->getMessage()
            ];
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
}