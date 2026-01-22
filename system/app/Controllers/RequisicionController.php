<?php
class Requisicion extends Controllers {
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
	//TODO: inicio de vista y carga de datos desde la URL
    public function requisicion($idRequisicionUrl = null){
        // Validar nuevamente la sesión antes de mostrar el home
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        //invocar la vista con views y usamos getView y pasamos parametros esta clase y la vista
		//incluimos un arreglo que contendra toda la informacion que se enviara al home
		$data = [
			'page_tag' =>  "Requisiciones - SITGO",
			'page_id' => 6,
			'page_title' =>  "Gestión de Requisiciones",
			'page_name' => "requisicion",
			'page_link' => "perfil",//activar el menu desplegable o un lin solo
			'page_functions' => "function.requisicion.js",
            'id_requisicion_url' => $idRequisicionUrl // Pasamos el ID de la URL a la vista
		];
		$this->views->getViews($this, "requisicion", $data);
    }

    /**
     * Guarda una nueva requisición enviada desde el formulario.
     */
    public function setRequisicion(){
        if($_POST){
            // dep($_POST); // Descomentar para depurar los datos recibidos

            $idUnidad = intval($_POST['listUnidad']);
            $mecanicoCedula = strClean($_POST['listMecanico']);
            $tipoOrden = strClean($_POST['tipoOrden']);
            $observacion = strtoupper(strClean($_POST['txtObservacion']));
            $articulos = json_decode($_POST['articulos'], true);
            $idUsuario = $_SESSION['userData']['usuario_id'];
            $fecha = date('Y-m-d');

            if($idUnidad <= 0 || empty($mecanicoCedula) || empty($tipoOrden) || empty($articulos)){
                $arrResponse = array("status" => false, "msg" => 'Datos incompletos o inválidos.');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            // Usamos el modelo de Orden que ya tiene el método para la inserción completa
            $request_requisicion = $this->model->insertRequisicionCompleta($idUnidad, $mecanicoCedula, $tipoOrden, $observacion, $idUsuario, $fecha, $articulos);

            if($request_requisicion > 0){
                $arrResponse = array('success' => true, 'id_despacho' => $request_requisicion, 'msg' => 'Requisición creada con éxito.');
            } else {
                $arrResponse = array("success" => false, "msg" => 'No es posible almacenar los datos.');
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /**
     * Obtiene la lista de requisiciones para la DataTable.
     */
    public function getRequisiciones() {
        try {
            $arrData = $this->model->getRequisiciones();

            for ($i = 0; $i < count($arrData); $i++) {
                $userRole = $_SESSION['userData']['rol_nombre'] ?? ''; // 'Encargado'
                $userDepartment = $_SESSION['userData']['departamento_nombre'] ?? ''; // 'Compras'
                $estadoNum = $arrData[$i]['estado_orden'];
                $idDespacho = $arrData[$i]['id_despacho'];

                // Lógica de Estado (Badge o Botón)
                // La condición debe ser: si el rol es 'Encargado' Y el departamento es 'Compras' Y el estado es Pendiente (1)
                if ($userRole === 'Encargado' && $userDepartment === 'Compras' && $estadoNum == 1) {
                    // Si es Compras y está Pendiente, el estado se convierte en un botón
                    $arrData[$i]['estado_orden'] = '<button class="btn btn-warning btn-sm" onClick="fntLoadRequisicionParaAprobar('.$idDespacho.')" title="Cargar para Aprobar">Pendiente</button>';
                } else {
                    // Para todos los demás casos, es un badge normal
                    $badge = '<span class="badge badge-secondary">Desconocido</span>';
                    if ($estadoNum == 1) $badge = '<span class="badge badge-warning">Pendiente</span>';
                    if ($estadoNum == 2) $badge = '<span class="badge badge-info">Aprobada</span>';
                    if ($estadoNum == 3) $badge = '<span class="badge badge-success">Despachada</span>'; // Este estado ya no debería verse aquí
                    if ($estadoNum == 4) $badge = '<span class="badge badge-danger">Rechazada</span>';
                    $arrData[$i]['estado_orden'] = $badge;
                }

                // Botones de acción
                $btnView = '<button class="btn btn-info btn-sm" onClick="fntViewRequisicion('.$idDespacho.')" title="Ver Detalles"><i class="far fa-eye"></i></button>';
                $btnPrint = '<button class="btn btn-secondary btn-sm" onClick="fntImprimirRequisicion('.$idDespacho.')" title="Imprimir Orden"><i class="fas fa-print"></i></button>';
                $btnAprobar = '';
                if ($userRole === 'Encargado' && $userDepartment === 'Compras' && $estadoNum == 1) {
                    $btnAprobar = '<button class="btn btn-primary btn-sm ml-1" onClick="fntLoadRequisicionParaAprobar('.$idDespacho.')" title="Cargar para Aprobar"><i class="fas fa-dolly-flatbed"></i></button>';
                }

                $arrData[$i]['acciones'] = '<div class="text-center d-flex justify-content-center">' . $btnView . '&nbsp;' . $btnPrint . '&nbsp;' . $btnAprobar . '</div>';
            }

            echo json_encode(['data' => $arrData], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            // Manejo de errores
            echo json_encode(['data' => [], 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /**
     * Obtiene los detalles de una requisición específica para mostrar en un modal.
     * @param int $idDespacho El ID del despacho (que es el id_despacho_fk en table_alm_requisicion).
     */
    public function getRequisicionDetails(int $idDespacho) {
        try {
            $requisicionData = $this->model->selectRequisicionById($idDespacho);

            if (empty($requisicionData)) {
                echo json_encode(['success' => false, 'msg' => 'Requisición no encontrada.'], JSON_UNESCAPED_UNICODE);
                die();
            }

            // Formatear el estado para mostrarlo como un badge en la vista
            $estadoTexto = '';
            switch ($requisicionData['status_requisicion']) {
                case 1: $estadoTexto = '<span class="badge badge-warning">Pendiente</span>'; break;
                case 2: $estadoTexto = '<span class="badge badge-info">Aprobada</span>'; break;
                case 3: $estadoTexto = '<span class="badge badge-success">Despachada</span>'; break;
                case 4: $estadoTexto = '<span class="badge badge-danger">Rechazada</span>'; break;
                default: $estadoTexto = '<span class="badge badge-secondary">Desconocido</span>'; break;
            }
            $requisicionData['status_display'] = $estadoTexto;

            // Formatear la fecha si es necesario (el modelo ya la devuelve como fecha_requisicion)
            $requisicionData['fecha_requisicion_formatted'] = date('d-m-Y H:i:s', strtotime($requisicionData['fecha_requisicion']));

            echo json_encode(['success' => true, 'data' => $requisicionData], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            error_log("Error en getRequisicionDetails: " . $e->getMessage());
            echo json_encode(['success' => false, 'msg' => 'Error al obtener detalles de la requisición.'], JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /**
     * Procesa la aprobación de una requisición.
     */
    public function aprobarRequisicion() {
        if ($_POST) {
            $idDespacho = intval($_POST['id_despacho_aprobar']);
            $idUsuarioAprobador = $_SESSION['userData']['usuario_id'];

            if ($idDespacho <= 0) {
                echo json_encode(['success' => false, 'msg' => 'ID de requisición inválido.'], JSON_UNESCAPED_UNICODE);
                die();
            }

            try {
                $request = $this->model->aprobarRequisicion($idDespacho, $idUsuarioAprobador);
                echo json_encode(['success' => true, 'msg' => 'Requisición aprobada con éxito. Notificación enviada a Almacén.'], JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    /**
     * Obtiene los datos para generar el reporte en PDF de una requisición.
     * @param int $idDespacho El ID del despacho que representa la requisición.
     */
    public function generarReporteRequisicion(int $idDespacho) {
        try {
            $idDespacho = intval($idDespacho);
            if ($idDespacho <= 0) {
                throw new Exception("ID de requisición no válido.");
            }
            $reporteData = $this->model->selectRequisicionById($idDespacho);
            $arrResponse = empty($reporteData)
                ? ['success' => false, 'message' => 'No se encontraron datos para el reporte.']
                : ['success' => true, 'data' => $reporteData];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => 'Error al generar el reporte: ' . $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Aquí se agregarán más métodos como getRequisiciones, aprobarRequisicion, etc.

}
?>