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

    /**
     * Muestra la vista para que Almacén gestione los despachos pendientes.
     */
    public function despachosPendientes($idDespachoUrl = null) {
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        $data = [
            'page_tag' => "Despachos Pendientes",
            'page_title' => "Órdenes por Despachar",
            'page_name' => "almacen",
            'page_link' => "despachos",
            'page_functions' => "function.despachos.js",
            'id_despacho_url' => $idDespachoUrl // Pasar el ID a la vista
        ];
        $this->views->getViews($this, "despachos", $data); // Nueva vista
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

    /**
     * Procesa el despacho final de una orden aprobada.
     * Actualiza la orden con el operador y despachador, cambia el estado a 'Despachada'
     * y descuenta los artículos del inventario.
     */
    public function despacharOrden() {
        if ($_POST) {
            try {
                $idDespacho = intval($_POST['id_despacho_completar']);
                $idOperador = intval($_POST['listOperadorDespacho']);
                $idDespachador = intval($_POST['listDespachadorAlmacen']);

                if ($idDespacho <= 0 || $idOperador <= 0 || $idDespachador <= 0) {
                    throw new Exception("Datos incompletos para procesar el despacho.");
                }

                // Obtener los nombres del personal
                $operadorData = $this->ordenModel->selectPersonal($idOperador);
                $despachadorData = $this->ordenModel->selectPersonal($idDespachador);

                $nombreOperador = !empty($operadorData) ? strtoupper($operadorData['personal_nombre'] . ' ' . $operadorData['personal_apellido']) : 'N/A';
                $nombreDespachador = !empty($despachadorData) ? strtoupper($despachadorData['personal_nombre'] . ' ' . $despachadorData['personal_apellido']) : 'N/A';

                // Llamar al método del modelo que realiza toda la lógica de despacho
                $request = $this->ordenModel->procesarDespachoFinal($idDespacho, $nombreOperador, $nombreDespachador);

                $arrResponse = ['success' => true, 'message' => 'Orden despachada con éxito. El inventario ha sido actualizado.'];

            } catch (Exception $e) {
                $arrResponse = ['success' => false, 'message' => 'Error al despachar la orden: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /**
     * Obtiene las órdenes filtradas por un estado específico para las DataTables.
     * Usado por la vista de "Despachos Pendientes" para obtener las órdenes aprobadas (estado 2).
     * @param int $estado El estado de la orden a filtrar.
     */
    public function getOrdenesPorEstado(int $estado) {
        try {
            $arrData = $this->ordenModel->selectOrdenesPorEstado($estado);

            for ($i = 0; $i < count($arrData); $i++) {
                // Añadir el badge de estado
                $estadoBadge = '';
                switch ($arrData[$i]['estado_orden']) {
                    case 2:
                        $estadoBadge = '<span class="badge badge-info">Aprobada</span>';
                        break;
                    case 3:
                        $estadoBadge = '<span class="badge badge-success">Despachada</span>';
                        break;
                    default:
                        $estadoBadge = '<span class="badge badge-secondary">Desconocido</span>';
                        break;
                }
                $arrData[$i]['estado_badge'] = $estadoBadge;

                // Añadir botones de acción para cada orden pendiente de despacho
                $btnView = '<button class="btn btn-info btn-sm" onClick="fntViewOrden('.$arrData[$i]['id_despacho'].')" title="Ver Detalles"><i class="far fa-eye"></i></button>';
                $btnPrint = '<button class="btn btn-secondary btn-sm" onClick="fntImprimirRequisicion('.$arrData[$i]['id_despacho'].')" title="Imprimir Orden"><i class="fas fa-print"></i></button>';
                
                // Solo mostrar el botón de despachar si la orden está aprobada (estado 2)
                if ($arrData[$i]['estado_orden'] == 2) {
                    $btnDespachar = '<button class="btn btn-success btn-sm ml-1" onClick="fntCargarParaDespachar('.$arrData[$i]['id_despacho'].')" title="Completar Despacho"><i class="fas fa-truck"></i></button>';
                } else {
                    $btnDespachar = '';
                }
                
                $arrData[$i]['acciones'] = '<div class="text-center d-flex justify-content-center">' . $btnView . '&nbsp;' . $btnPrint . '&nbsp;' . $btnDespachar . '</div>';
            }

            // DataTables en modo cliente espera los datos en la clave "data"
            $arrResponse = ["data" => $arrData];

        } catch (Exception $e) {
            // En caso de error, devolver un array de datos vacío
            $arrResponse = ["data" => [], "error" => "Error al cargar las órdenes: " . $e->getMessage()];
        }

        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
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

    public function setOrdenD(){
        $arrResponse = ['success' => false, 'message' => 'Error al registrar la orden'];
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $intUnidad = intval($_POST['listUnidad']);
            $intIdUser = $_SESSION['idUser'];
            $srtObs = !empty($_POST['txtObs']) ? strtoupper(strClean($_POST['txtObs'])) : '';
            // Se elimina strClean para no corromper el formato de fecha YYYY-MM-DD que necesita la tabla de compras pendientes
            $strDate = !empty($_POST['strDate']) ? $_POST['strDate'] : date('Y-m-d H:i:s');

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

            $idDespacho = $this->ordenModel->insertDespacho($intUnidad, $strOper, $strMec, $strDesp, $intIdUser, $srtObs, $strDate);

            if ($idDespacho > 0) {
                if (!empty($_POST['cod']) && is_array($_POST['cod'])) {
                    foreach ($_POST['cod'] as $index => $idArticulo) {
                        $intCant = floatval($_POST['cantidad'][$index]);
                        $this->ordenModel->insertRDespacho($idDespacho, $idArticulo, $intCant, $intUnidad, $strDate);
                        $this->ordenModel->updateCant($idArticulo, $intCant);
                    }
                }
                $arrResponse = ['success' => true, 'message' => 'Orden registrada correctamente con ID: ' . $idDespacho];
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

            // Añadir badges de estado y botones de acción dinámicos
            for ($i = 0; $i < count($ordenesData); $i++) { // Loop through each order
                $userRole = $_SESSION['userData']['rol_nombre'] ?? '';
                $userDepartment = $_SESSION['userData']['departamento_nombre'] ?? '';

                $estadoNum = $ordenesData[$i]['estado_orden'];
                $hasOutOfStockItems = $ordenesData[$i]['has_insufficient_stock_items'] ?? false;
                $idDespacho = $ordenesData[$i]['id_despacho'];

                $estadoBadge = '';
                $btnView = '<button class="btn btn-info btn-sm" onClick="fntViewOrden('.$idDespacho.')" title="Ver Detalles"><i class="far fa-eye"></i></button>';
                $btnPrint = '<button class="btn btn-secondary btn-sm" onClick="fntImprimirRequisicion('.$idDespacho.')" title="Imprimir Orden"><i class="fas fa-print"></i></button>';
                $btnDelete = '<button onclick="fntdelDesp('.$idDespacho.')" class="btn btn-danger btn-sm" title="Anular"><i class="fas fa-trash-alt"></i></button>';

                $acciones = $btnView . '&nbsp;' . $btnPrint . '&nbsp;' . $btnDelete; // Default buttons

                switch ($ordenesData[$i]['estado_orden']) {
                    case 1: // Pendiente (Requisición)
                        $estadoBadge = '<span class="badge badge-warning">Requisición</span>';
                        // Only Compras (Encargado) or Administrator can approve/process
                        if ($hasOutOfStockItems) {
                            $estadoBadge = '<span class="badge badge-warning">Requisición <span class="badge badge-danger">Stock Insuficiente</span></span>';
                        }
                        $canProcess = (strtoupper($userRole) === 'ENCARGADO' && strtoupper($userDepartment) === 'COMPRAS') || (strtoupper($userRole) === 'ADMINISTRADOR');
                        if ($canProcess) {
                            $acciones .= '&nbsp;<button class="btn btn-primary btn-sm" onClick="fntAprobarOrden('.$idDespacho.')" title="Aprobar Requisición"><i class="fas fa-check-double"></i></button>';
                            $acciones .= '&nbsp;<button class="btn btn-warning btn-sm" onClick="fntNotificarEnProceso('.$idDespacho.')" title="Notificar a Operaciones: En Proceso"><i class="fas fa-bell"></i></button>';
                        }
                        break;
                    case 2: // Aprobada
                        $estadoBadge = '<span class="badge badge-info">Aprobada</span>';
                        // Only Almacen (Encargado) or Administrator can dispatch
                        $canDispatch = (strtoupper($userDepartment) === 'ALMACEN' && strtoupper($userRole) === 'ENCARGADO') || (strtoupper($userRole) === 'ADMINISTRADOR');
                        if ($canDispatch) {
                            $acciones .= '&nbsp;<button class="btn btn-success btn-sm" onClick="fntCargarParaDespachar('.$ordenesData[$i]['id_despacho'].')" title="Completar Despacho"><i class="fas fa-truck"></i></button>';
                        }
                        break;
                    case 3: // Despachada
                        $estadoBadge = '<span class="badge badge-success">Despachada</span>';
                        break;
                    case 4: // Rechazada
                        $estadoBadge = '<span class="badge badge-danger">Rechazada</span>';
                        break;
                    default:
                        $estadoBadge = '<span class="badge badge-secondary">Desconocido</span>';
                        break;
                }
                $ordenesData[$i]['estado_badge'] = $estadoBadge;
                $ordenesData[$i]['acciones'] = '<div class="text-center d-flex justify-content-center">' . $acciones . '</div>';
            }

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

    public function despachos() {
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        $data = [
            'page_tag' => "Despachos Pendientes",
            'page_title' => "Órdenes por Despachar",
            'page_name' => "almacen",
            'page_link' => "despachos",
            'page_functions' => "function.despachos.js"
        ];
        $this->views->getViews($this, "despachos", $data);
    }

    /**
     * Inserta una notificación para Operaciones indicando que una orden está en proceso.
     * Solo accesible para el departamento de Compras.
     */
    public function notificarEnProceso()
    {
        $arrResponse = ['success' => false, 'message' => 'Error al enviar notificación.'];
        try {
            // Reutilizar la lógica de notificarOperaciones
            // Simular el POST para notificarOperaciones
            $_POST['id_despacho'] = $_POST['id_despacho'] ?? 0;
            $_POST['tipo'] = 'en_proceso'; // Indicar que es una notificación "en proceso"

            // Llamar a la función notificarOperaciones
            // Capturar la salida de notificarOperaciones para devolverla
            ob_start();
            $this->notificarOperaciones();
            $output = ob_get_clean();
            $arrResponse = json_decode($output, true);

            if ($arrResponse['success']) {
                $arrResponse['message'] = 'Operaciones ha sido notificado.';
            } else {
                // Si notificarOperaciones falló, su mensaje ya estará en $arrResponse['message']
                $arrResponse['message'] = $arrResponse['message'] ?? 'Error desconocido al notificar.';
            }
        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Notifica automáticamente a Operaciones cuando Compras aprueba una requisición.
     */
    public function notificarOperaciones() {
        $arrResponse = ['success' => false];
        try {
            $idDespacho = intval($_POST['id_despacho'] ?? 0);
            $tipo       = $_POST['tipo'] ?? 'aprobada';
            if ($idDespacho <= 0) throw new Exception('ID inválido.');

            $nick   = $_SESSION['userData']['usuario_nick'] ?? 'Usuario';
            $orden  = $this->ordenModel->selectDepacho($idDespacho);
            $unidad = $orden['id_unidad'] ?? "#{$idDespacho}";

            if ($tipo === 'aprobada') {
                // Limpiar notificaciones previas de "en proceso" para esta orden
                $sql_clear = "UPDATE table_notificaciones SET leido = 1 WHERE tipo_notificacion = 'orden_en_proceso' AND id_referencia = ? AND leido = 0";
                $this->ordenModel->update($sql_clear, [$idDespacho]);

                $mensaje   = "Requisición #{$idDespacho} (Unidad {$unidad}) fue APROBADA por Compras ({$nick}). Será atendida por Almacén.";
                $tipoNotif = 'orden_aprobada_ops';
            } else {
                $mensaje   = "Orden #{$idDespacho} (Unidad {$unidad}) está en proceso de compra. Notificado por {$nick}.";
                $tipoNotif = 'orden_en_proceso';
            }

            $sql = "INSERT INTO table_notificaciones (tipo_notificacion, id_referencia, mensaje) VALUES (?, ?, ?)";
            $this->ordenModel->insertRaw($sql, [$tipoNotif, $idDespacho, $mensaje]);
            $arrResponse = ['success' => true];

        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Aprueba una orden de requisición.
     * Cambia el estado de la orden a 'Aprobada' (2).
     */
    public function aprobarOrden() {
        $arrResponse = ['success' => false, 'message' => 'Error al aprobar la orden'];
        try {
            $idDespacho = intval($_POST['id_despacho'] ?? 0);
            if ($idDespacho <= 0) {
                throw new Exception('ID de orden inválido.');
            }

            // Verificar que la orden exista y esté en estado pendiente (1)
            $orden = $this->ordenModel->selectDepacho($idDespacho);
            if (empty($orden) || $orden['estado_orden'] != 1) {
                throw new Exception('La orden no existe o no está pendiente de aprobación.');
            }

            // Verificar que todos los artículos tengan stock suficiente
            $sql_check = "SELECT 
                            rd.cantidad_solicitada,
                            rpr.cant_producto,
                            p.producto,
                            (rpr.cant_producto >= rd.cantidad_solicitada) as suficiente
                        FROM table_alm_requisicion_detalle rd
                        JOIN table_alm_requisicion r ON rd.id_requisicion_fk = r.id_requisicion
                        JOIN table_alm_relacion_producto rpr ON rd.id_producto = rpr.id_producto
                        JOIN table_alm_producto p ON rd.id_producto = p.id_producto
                        WHERE r.id_despacho_fk = ?";
            
            $articulos = $this->ordenModel->select_all($sql_check, [$idDespacho]);
            $stockInsuficiente = false;
            $mensajeStock = '';

            foreach ($articulos as $art) {
                if (!$art['suficiente']) {
                    $stockInsuficiente = true;
                    $mensajeStock .= "{$art['producto']}: solicita {$art['cantidad_solicitada']}, disponible {$art['cant_producto']}. ";
                }
            }

            if ($stockInsuficiente) {
                throw new Exception('No se puede aprobar la orden. Stock insuficiente: ' . $mensajeStock);
            }

            // Actualizar el estado de la orden a 'Aprobada' (2)
            $sql_update = "UPDATE table_alm_despacho SET estado_orden = 2, fecha_aprobacion = NOW() WHERE id_despacho = ?";
            $this->ordenModel->update($sql_update, [$idDespacho]);

            // Actualizar el estado de la requisición a 'Aprobada' (2)
            $sql_update_req = "UPDATE table_alm_requisicion SET status_requisicion = 2 WHERE id_despacho_fk = ?";
            $this->ordenModel->update($sql_update_req, [$idDespacho]);

            // Crear notificación para Almacén: orden aprobada para despachar
            $sql_notif = "INSERT INTO table_notificaciones (tipo_notificacion, id_referencia, mensaje) VALUES ('despacho_pendiente', ?, ?)";
            $mensaje = "Requisición #{$idDespacho} aprobada por Compras. Disponible para despacho en Almacén.";
            $this->ordenModel->insert($sql_notif, [$idDespacho, $mensaje]);

            // Notificar a Operaciones que la orden fue aprobada
            $_POST['id_despacho'] = $idDespacho;
            $_POST['tipo'] = 'aprobada';
            $this->notificarOperaciones();

            $arrResponse = ['success' => true, 'message' => 'Orden aprobada correctamente. Almacén ha sido notificado.'];

        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
}
?>