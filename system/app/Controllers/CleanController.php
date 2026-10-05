<?php
header('Access-Control-Allow-Origin: *');

// 1. Incluimos manualmente el modelo que vamos a necesitar.
require_once 'system/app/Models/ProductoModel.php';

class Clean extends Controllers{
    private $productoModel; // 2. Propiedad con nombre específico para el modelo de productos.
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
        // 3. Instanciamos el modelo en nuestra propiedad específica.
        $this->productoModel = new ProductoModel();
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
	//TODO: inicio de vista
	public function cleanAlmacen(){
		// Validar nuevamente la sesión antes de mostrar el home
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
		//invocar la vista con views y usamos getView y pasamos parametros esta clase y la vista
		//incluimos un arreglo que contendra toda la informacion que se enviara al home
		$data = [
			'page_tag' => "CLEAN",
			'page_title' => "Pagina Principal",
			'page_name' => "clean",
			'page_link' => "cleanAlmacen",//activar el menu desplegable o un lin solo
			'page_functions' => "function.clean.js"
		];
		$this->views->getViews($this, "cleanAlmacen", $data);
	}
    /**
     * comienzan los metodos para el mantenimiento del almacen
     */
    /**
     * Obtiene los datos iniciales para los selects de los formularios.
     */
    public function getInitialDat() {
        $arrResponse = ['success' => false, 'message' => 'No se pudieron cargar los datos iniciales.'];
        try {
            $data = [
                'enlaces' => $this->productoModel->selectEnlace(),
                'proveedores' => $this->productoModel->selectProvee(),
                'ubicaciones' => $this->productoModel->selectUbic(),
                'productos' => $this->productoModel->selectListProductos()
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
     * Actualiza los detalles completos de un producto, incluyendo el stock.
     * Esto es para la sección de mantenimiento, permitiendo corregir cualquier dato.
     * Recibe los datos por POST.
     */
    public function updateFullProducto() {
        $arrResponse = ['success' => false, 'message' => 'Error al actualizar el stock.'];
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }
            if (!isset($_SESSION['userData']['usuario_rol_id']) || $_SESSION['userData']['usuario_rol_id'] != 1 && $_SESSION['userData']['usuario_rol_id'] != 4) {
                throw new Exception('No tiene permisos para realizar esta acción.');
            }

            $idProducto = intval($_POST['id_producto']);
            if ($idProducto <= 0) {
                throw new Exception('ID de producto no válido.');
            }

            // Recopilar todos los datos del formulario
            $data = [
                'producto' => ucwords(strClean($_POST['txtArticulo'])),
                'id_enlace_producto' => intval($_POST['listEnlace']),
                'id_proveedor' => intval($_POST['listProveedor']),
                'id_ubicacion' => intval($_POST['listUbicacion']),
                'cant_producto' => floatval($_POST['txtCantidad']),
                'present_producto' => strClean($_POST['optionsPresentacion']),
                'tag_producto' => intval($_POST['optionsArticulo'])
            ];

            // Validar campos obligatorios
            if (empty($data['producto']) || $data['id_enlace_producto'] == 0 || $data['id_proveedor'] == 0 || $data['id_ubicacion'] == 0 || empty($data['present_producto']) || $data['tag_producto'] == 0) {
                throw new Exception('Todos los campos marcados con * son obligatorios.');
            }

            $request = $this->productoModel->updateFullProducto($idProducto, $data);

            if ($request === 'exist') {
                throw new Exception('¡Atención! Ya existe un producto con ese nombre y tipo.');
            } elseif ($request) {
                $arrResponse = ['success' => true, 'message' => 'Producto actualizado correctamente.'];
            } else {
                throw new Exception('No se realizó ningún cambio o no se pudo actualizar el producto.');
            }
        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getProductosReporte() {
        $arrData = $this->productoModel->selectProductosReporte();
        if (empty($arrData)) {
            $arrResponse = ['success' => false, 'message' => 'No se encontraron productos para el reporte.'];
        } else {
            $arrResponse = ['success' => true, 'data' => $arrData];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Obtiene los datos de un producto específico por su ID para el formulario de edición.
     * @param int $idProducto El ID del producto a consultar.
     */
    public function getProducto(int $idProducto){
        $arrResponse = ['success' => false, 'message' => 'Producto no encontrado.'];
        try {
            $idProducto = intval($idProducto);
            if ($idProducto <= 0) {
                throw new Exception('ID de producto no válido.');
            }
            $arrData = $this->productoModel->selectProducto($idProducto);
            if (!empty($arrData)) {
                $arrResponse = ['success' => true, 'data' => $arrData];
            }
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error al obtener el producto: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
}