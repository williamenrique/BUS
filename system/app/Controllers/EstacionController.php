<?php
header('Access-Control-Allow-Origin: *');
class Estacion extends Controllers{
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
    public function registrar(){
        // Validar nuevamente la sesión antes de mostrar el home
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        $data = [
            'page_tag' => 'E/S VENTA',
            'page_title' => "Pagina Principal",
            'page_name' => "estacion/registrar",
            'page_link' => "registrar-venta",
            'page_functions' => "function.estacion.js"
        ];
        $this->views->getViews($this, "registrar", $data);
    }

    public function initialData() {
        $arrResponse = array('success' => false, 'message' => '');
        try {
            $tiposVehiculo = $this->model->selectTipoVehiculo();
            $tiposPago = $this->model->selectTipoPago();
            // --- Tipos de combustible ---
            $tiposCombustible = $this->model->selectTipoCombustible();

            // Validacion para admin de sistema
            $idEstacion = 0; // Por defecto, sin estación (para admin)
            if ($_SESSION['userData']['departamento_nombre'] != 'SISTEMA') {
                $idEstacion = $_SESSION['userData']['usuario_estacion_id'] ?? 0;
            }

            $tasa = $this->model->getTasa($idEstacion);
            $ultimosTickets = $this->model->getLastTicket($_SESSION['idUser'], date('Y-m-d'), $idEstacion);
            $resumen = $this->model->getDetail($_SESSION['idUser'], date('Y-m-d'), $idEstacion);
            $cierresPendientes = $this->model->getPendingCierres($_SESSION['idUser'], $idEstacion);
			// OBTENER VENTAS PENDIENTES EN VEZ DE CIERRES
            $ventasPendientes = $this->model->getPendingVentas($_SESSION['idUser'], $idEstacion);
            $arrResponse = [
				'success' => true,
                'message' => 'Datos cargados correctamente',
				'ventasPendientes' => $ventasPendientes,
                'tiposVehiculo' => $tiposVehiculo,
                'tiposPago' => $tiposPago,
                'tiposCombustible' => $tiposCombustible,
                'tasa' => $tasa,
                'ultimosTickets' => $ultimosTickets,
                'resumen' => $resumen,
                'cierresPendientes' => $cierresPendientes
            ];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error al cargar datos iniciales: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function updateTasa() {
        $arrResponse = array('success' => false, 'message' => '');
        $data = json_decode(file_get_contents("php://input"), true);
        if (empty($data['tasa'])) {
            $arrResponse['message'] = 'Tasa no especificada.';
        } else {
            $tasa = floatval($data['tasa']);
            $idEstacion = 0;
            if ($_SESSION['userData']['departamento_nombre'] != 'SISTEMA') {
                $idEstacion = $_SESSION['userData']['usuario_estacion_id'] ?? 0;
            }

            // Verificar si el usuario es Administrador (Rol ID 1)
            $isAdmin = (isset($_SESSION['userData']['usuario_rol_id']) && $_SESSION['userData']['usuario_rol_id'] == 1);

            $request = $this->model->updateTasa($tasa, $idEstacion, $isAdmin);
            if ($request === 'already_updated') {
                $arrResponse = ['success' => false, 'message' => 'No se puede actualizar, la tasa ya fue modificada hoy.'];
            } else if ($request) {
                $arrResponse = ['success' => true, 'message' => 'Tasa actualizada correctamente.'];
            } else {
                $arrResponse = ['success' => false, 'message' => 'Error al actualizar la tasa.'];
            }
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function registrarVenta() {
        $arrResponse = array('success' => false, 'message' => '');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $arrResponse['message'] = 'Método no permitido.';
            echo json_encode($arrResponse);
            return;
        }
        try {
            // Validaciones básicas de los datos
            if (empty($_POST['txtLTS']) || empty($_POST['txtListTipoVehiculo']) || empty($_POST['txtListTipoPago'])) {
                throw new Exception('Datos de venta incompletos.');
            }
            $idUser = $_SESSION['idUser'];
            $tipoVehiculo = intval($_POST['txtListTipoVehiculo']);
            $litros = floatval($_POST['txtLTS']);
            $tipoPago = intval($_POST['txtListTipoPago']);
            $monto = floatval($_POST['txtMonto']);
            $tasa = floatval($_POST['txtTasa']);

            // --- Tipo de combustible (Gasolina por defecto = 1) ---
            $tipoCombustible = intval($_POST['txtListTipoCombustible'] ?? 1);
            if ($tipoCombustible <= 0) {
                $tipoCombustible = 1;
            }

            $idEstacion = 0;
            if ($_SESSION['userData']['departamento_nombre'] != 'SISTEMA') {
                $idEstacion = $_SESSION['userData']['usuario_estacion_id'] ?? 0;
            }
            // Se pasa $tipoCombustible como 4.º parámetro (nuevo orden)
            $request = $this->model->setVenta($idUser, $idEstacion, $tipoVehiculo, $tipoCombustible, $litros, $tipoPago, $monto, $tasa);
            if ($request > 0) {
				$datTicket = $this->model->getTicketData($request, $idUser, date('Y-m-d'), $idEstacion);
                $arrResponse = ['success' => true, 'message' => 'Venta registrada con éxito. Ticket #' . $request, 'ticketData' => $datTicket];
            } else {
                $arrResponse['message'] = 'Error al registrar la venta.';
            }
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function cerrarDia() {
		$arrResponse = array('success' => false, 'message' => '');
		try {
			// Leer el cuerpo de la solicitud JSON
			$json = file_get_contents('php://input');
			$data = json_decode($json, true);
			// Verificar que los datos y la fecha existan
			if (!isset($data['fecha_cierre'])) {
				throw new Exception("Error: La fecha de cierre no fue recibida.");
			}
			$fechaCierre = $data['fecha_cierre'];
			$userId = $data['userId'];
            $idEstacion = 0;
            if ($_SESSION['userData']['departamento_nombre'] != 'SISTEMA') {
                $idEstacion = $_SESSION['userData']['usuario_estacion_id'] ?? 0;
            }
			// Asumiendo que el modelo ya tiene la lógica para cerrar el turno pendiente
			$request = $this->model->setDailyCierre($userId, $fechaCierre, $idEstacion);
			if ($request) {
				$resumen_vacio = [
					'total_ventas' => 0,
					'total_litros' => 0.00,
					'total_bs' => 0.00,
					'total_divisa' => 0.00,
					'total_efectivo' => 0.00,
					'total_debito' => 0.00,
					'tiposVehiculo' => [],
					'tiposCombustible' => [],
					'tiposPago' => [],
				];
                // Obtener tanto los datos del cierre como los detallados
                $dataCierre = $this->model->getDataCierre($userId, $fechaCierre, $request);
                $dataDetallado = $this->model->getDetallado($userId, $fechaCierre, $idEstacion);
				$arrResponse = ['success' => true, 'message' => 'Día cerrado exitosamente.','dataCierre' => $dataCierre, 'dataDetallado' => $dataDetallado, 'resumen' => $resumen_vacio];
			} else {
				$arrResponse['message'] = 'Error al cerrar el día. Puede que ya esté cerrado o no haya ventas.';
			}
		} catch (Exception $e) {
			$arrResponse['message'] = 'Error: ' . $e->getMessage();
		}
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}

    // cerrar turno pendiente de dias anteriores
    public function cerrarTurnoPendiente() {
		$arrResponse = array('success' => false, 'message' => '');
		try {
			// Leer el cuerpo de la solicitud JSON
			$json = file_get_contents('php://input');
			$data = json_decode($json, true);
			// Verificar que los datos y la fecha existan
			if (!isset($data['fecha_cierre'])) {
				throw new Exception("Error: La fecha de cierre no fue recibida.");
			}
			$fechaCierre = $data['fecha_cierre'];
			$userId = $data['userId'];
            // Obtener la estación del usuario de la venta, no del admin en sesión.
            $userInfo = $this->model->getUsuario($userId);
            $idEstacion = $userInfo['usuario_estacion_id'] ?? 0;

			// Asumiendo que el modelo ya tiene la lógica para cerrar el turno pendiente
			$request = $this->model->setDailyCierre($userId, $fechaCierre, $idEstacion);
			if ($request) {
				$resumen_vacio = [
					'total_ventas' => 0,
					'total_litros' => 0.00,
					'total_bs' => 0.00,
					'total_divisa' => 0.00,
					'total_efectivo' => 0.00,
					'total_debito' => 0.00,
					'tiposVehiculo' => [],
					'tiposCombustible' => [],
					'tiposPago' => [],
				];
                // Obtener tanto los datos del cierre como los detallados
                $dataCierre = $this->model->getDataCierre($userId, $fechaCierre, $idEstacion);
                $dataDetallado = $this->model->getDetallado($userId, $fechaCierre, $idEstacion, false);
				$arrResponse = ['success' => true, 'message' => 'Cierre de día pendiente realizado con éxito.','dataCierre' => $dataCierre, 'dataDetallado' => $dataDetallado, 'resumen' => $resumen_vacio];

			} else {
				$arrResponse['message'] = 'Error al cerrar el turno pendiente.';
			}
		} catch (Exception $e) {
			$arrResponse['message'] = 'Error: ' . $e->getMessage();
		}
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}

	// obtener data para imrimir detallado del dia
	public function getDetalleVentas() {
        $arrResponse = array('success' => false, 'message' => '');
        try {
			// Leer el cuerpo de la solicitud JSON
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
			// Verificar que los datos existan (ahora también se espera idUser)
			if (!isset($data['fecha_detalle']) || !isset($data['idUser'])) {
				throw new Exception("Error: Datos incompletos.");
			}
            $fechaTicket = $data['fecha_detalle'];
            $idUser = intval($data['idUser']);

            // Obtener la estación del usuario de la venta, no del admin en sesión.
            $userInfo = $this->model->getUsuario($idUser);
            $idEstacion = $userInfo['usuario_estacion_id'] ?? 0;

            $request = $this->model->getDetallado($idUser, $fechaTicket, $idEstacion, false);
            if (!empty($request)) {
                $arrResponse = ['success' => true, 'message' => 'Ticket obtenido', 'ticketData' => $request];
            } else {
                $arrResponse = ['success' => true, 'message' => 'No hay ventas para detallar.', 'ticketData' => []];
            }
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    // obtener data para imprimir un ticket
	public function getTicket() {
        $arrResponse = array('success' => false, 'message' => '');
        try {
			 // Leer el cuerpo de la solicitud JSON
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
			// Verificar que los datos existan
			if (!isset($data['idVenta']) || !isset($data['fechaTicket'])) {
				throw new Exception("Error: Datos incompletos.");
			}
            $idVenta = $data['idVenta'];
            $fechaTicket = $data['fechaTicket'];
            $idUser = $data['idUser'];
            $idEstacion = 0;
            if ($_SESSION['userData']['departamento_nombre'] != 'SISTEMA') {
                $idEstacion = $_SESSION['userData']['usuario_estacion_id'] ?? 0;
            }
            $request = $this->model->getTicketData($idVenta, $idUser, $fechaTicket, $idEstacion);
            if ($request > 0) {
                $arrResponse = ['success' => true, 'message' => 'Ticket obtenido', 'ticketData' => $request];
            } else {
                $arrResponse['message'] = 'Error al imprimir ticket.';
            }
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

	// para generar el pdf
	public function generarReportePdf() {
		header('Content-Type: application/json');
		$arrResponse = array('success' => false, 'message' => '');
		try {
			$json = file_get_contents('php://input');
			$data = json_decode($json, true);
			if (!isset($data['fecha']) || !isset($data['idUser']) || !isset($data['reportType'])) {
				throw new Exception("Error: Datos incompletos.");
			}
			$fecha = $data['fecha'];
			$idUser = $data['idUser'];
			$reportType = $data['reportType']; // 'unificado' o 'divisa'
			// Modo del detallado: 'unificado', 'separado' o 'ambos' (default: ambos)
			$detalleMode = isset($data['detalleMode']) ? $data['detalleMode'] : 'ambos';
			if (!in_array($detalleMode, ['unificado', 'separado', 'ambos'])) {
				$detalleMode = 'ambos';
			}

			// Se obtiene la estación del usuario de la venta (idUser), no del usuario en sesión.
			$userInfo = $this->model->getUsuario($idUser);
			$idEstacion = $userInfo['usuario_estacion_id'] ?? 0;

			// Se obtienen los datos de las ventas para la fecha y usuario, sin importar si el día está abierto o cerrado.
			$dataTotal = $this->model->getTotal($fecha, $idUser, $idEstacion, true, $reportType);
			$dataDetallado = $this->model->getDetallado($idUser, $fecha, $idEstacion, true, $reportType);

			if (empty($dataTotal) || empty($dataDetallado)) {
				$arrResponse = ['success' => false, 'message' => 'No se encontraron datos de ventas para esta fecha y usuario.'];
			} else {
				$arrResponse = [
					'success' => true,
					'message' => 'Datos obtenidos para el reporte.',
					'data' => [
						'dataTotal'    => $dataTotal,
						'dataDetallado' => $dataDetallado,
						'reportType'   => $reportType,
						'detalleMode'  => $detalleMode
					]
				];
			}
		} catch (Exception $e) {
			$arrResponse['message'] = 'Error: ' . $e->getMessage();
		}
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}

	/* 
    * inicio vista dataventa
    **/
    public function dataventa(){
        // Validar nuevamente la sesión antes de mostrar el home
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        $data = [
            'page_tag' => 'E/S VENTA',
            'page_title' => "Pagina Principal",
            'page_name' => "estacion/dataventa",
            'page_link' => "estacion/dataventa",
            'page_functions' => "function.dataventa.js"
        ];
        $this->views->getViews($this, "dataventa", $data);
    }

    /*
    * inicio del init
    * TODO: Nuevos métodos para la sección de historial de cierres y ventas
    */
    // mostrar litros totales en tarjeta
	public function getLitrosTotales() {
        $arrResponse = array('success' => false, 'message' => '');
        try {
            $totalLitros = $this->model->getLitrosTotalesSistema();
            $arrResponse = ['success' => true, 'totalLitros' => $totalLitros];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    // tabla historial de cierres
    public function getHistorialCierres() {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            try {
                $arrData = $this->model->getHistorialCierres();
                if (empty($arrData)) {
                    // Devolver éxito con un array vacío es una mejor práctica de API
                    $arrResponse = ['success' => true, 'data' => []];
                } else {
                    $arrResponse = ['success' => true, 'data' => $arrData];
                }
            } catch (Exception $e) {
                $arrResponse = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Trae las ventas de un cierre específico.
    public function getVentasByCierre() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postData = json_decode(file_get_contents('php://input'), true);
                if (!isset($postData['idCierre']) || empty($postData['idCierre'])) {
                    $arrResponse = ['success' => false, 'message' => 'ID del cierre no especificado.'];
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }
                $idCierre = intval($postData['idCierre']);
                $idUser = intval($postData['iduser']); // Este es el ID del usuario que hizo las ventas.
                $fechaCierre = strClean($postData['fechaCierre']);

                // Obtener la estación del usuario de la venta, no del admin en sesión
                $userInfo = $this->model->getUsuario($idUser);
                $idEstacion = $userInfo['usuario_estacion_id'] ?? 0;

                if ($idEstacion == 0) {
                    throw new Exception("No se pudo determinar la estación para el usuario del cierre.");
                }

                $arrData = $this->model->getDataVenta($fechaCierre, $idUser, $idEstacion, $idCierre);

                if (empty($arrData)) {
                    $arrResponse = ['success' => true, 'data' => []];
                } else {
                    $arrResponse = ['success' => true, 'data' => $arrData];
                }
            } catch (Exception $e) {
                $arrResponse = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Trae las ventas abiertas (en curso) de un usuario específico.
    public function getVentasAbiertas() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postData = json_decode(file_get_contents('php://input'), true);
                if (!isset($postData['idUser']) || !isset($postData['fecha'])) {
                    throw new Exception("Datos incompletos.");
                }
                $idUser = intval($postData['idUser']);
                $fecha = strClean($postData['fecha']);

                $userInfo = $this->model->getUsuario($idUser);
                $idEstacion = $userInfo['usuario_estacion_id'] ?? 0;

                $arrData = $this->model->getVentasAbiertas($fecha, $idUser, $idEstacion);

                $arrResponse = ['success' => true, 'data' => empty($arrData) ? [] : $arrData];
            } catch (Exception $e) {
                $arrResponse = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    //Elimina una venta específica.
    public function deleteVenta() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $postData = json_decode(file_get_contents('php://input'), true);
                if (!isset($postData['idVenta']) || empty($postData['idVenta'])) {
                    $arrResponse = ['success' => false, 'message' => 'ID de venta no especificado.'];
                    echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                    die();
                }        
                $idVenta = intval($postData['idVenta']);
                $idUser = intval($postData['idUser']);
                $fechaTicket = $postData['fechaTicket'];
                $deleted = $this->model->deleteVenta($idVenta, $fechaTicket, $idUser);
                if ($deleted) {
                    $arrResponse = ['success' => true, 'message' => 'Venta eliminada correctamente.'];
                } else {
                    $arrResponse = ['success' => false, 'message' => 'Error al eliminar la venta o no se encontró.'];
                }
            } catch (Exception $e) {
                $arrResponse = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // obtener datos del cierre
    public function getDataCierre() {
        $arrResponse = array('success' => false, 'message' => '');
        try {
			 // Leer el cuerpo de la solicitud JSON
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
			// Verificar que los datos existan, incluyendo idCierre
			if (!isset($data['idUser']) || !isset($data['fecha_venta']) || !isset($data['idCierre'])) {
				throw new Exception("Error: Datos incompletos.");
			}
            $idUser = $data['idUser'];
            $fechaVenta = $data['fecha_venta'];
            $idCierre = intval($data['idCierre']);

            $request = $this->model->getDataCierre($idUser, $fechaVenta, $idCierre);
            if (!empty($request)) {
                $arrResponse = ['success' => true, 'message' => 'Cierre obtenido', 'cierreData' => $request];
            } else {
                $arrResponse['message'] = 'Error al obtener cierre.';
            }
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Trae los datos de un cierre específico para ser impresos sin necesidad de cerrar el día.
     */
    public function getDatosParaReporte() {
        $arrResponse = [
            'success' => false, 
            'message' => 'Este endpoint está obsoleto. La impresión de reportes se gestiona desde el cierre.'
        ];
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Agregar esta función en EstacionController.php
    public function getFechasConVentas() {
        $arrResponse = array('success' => false, 'message' => '');
        try {
            $fechas = $this->model->getFechasConVentas();
            $arrResponse = ['success' => true, 'fechas' => $fechas];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    // traer data de litros por fecha
    public function getLitrosPorFecha() {
        $arrResponse = array('success' => false, 'message' => '');
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            if (!isset($data['fecha'])) {
                throw new Exception("Error: Dato 'fecha' no proporcionado.");
            }
            $type = $data['type'] ?? 'day'; // 'day' o 'month'
            $fechaFin = $data['fechaFin'] ?? null;
            $totalLitros = $this->model->getLitrosPorFecha($data['fecha'], $type, $fechaFin);
            $arrResponse = ['success' => true, 'totalLitros' => $totalLitros];    
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function generarReporteLitros() {
        $arrResponse = array('success' => false, 'message' => '');
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (!isset($data['fecha']) || !isset($data['type'])) {
                throw new Exception("Datos incompletos.");
            }
            
            $fecha = $data['fecha'];
            $type = $data['type'];
            $fechaFin = $data['fechaFin'] ?? null;
            // Filtro opcional por combustible
            $idCombustible = isset($data['idCombustible']) ? intval($data['idCombustible']) : null;
            
            $reportData = $this->model->selectReporteLitros($fecha, $type, $fechaFin, $idCombustible);
            $totalLitros = $this->model->getLitrosPorFecha($fecha, $type, $fechaFin);

            // --- Desglose por tipo de combustible ---
            $litrosGasolina = $this->model->getLitrosPorFecha($fecha, $type, $fechaFin, 1);
            $litrosDiesel   = $this->model->getLitrosPorFecha($fecha, $type, $fechaFin, 2);
            
            $arrResponse = [
                'success' => true,
                'data' => $reportData,
                'total' => $totalLitros,
                'fecha' => $fecha,
                'type' => $type,
                'fechaFin' => $fechaFin,
                'litrosGasolina' => $litrosGasolina,
                'litrosDiesel'   => $litrosDiesel
            ];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Maneja la solicitud para eliminar un cierre diario y resetear las ventas
     */
    public function deleteCierre() {
        $arrResponse = array('success' => false, 'message' => '');
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            // Validar que los datos necesarios están presentes
            if (!isset($data['id_cierre']) || !isset($data['id_usuario']) || !isset($data['id_estacion']) || !isset($data['fecha_cierre'])) {
                throw new Exception("Error: Datos incompletos para la eliminación.");
            }
            $idCierre = intval($data['id_cierre']);
            $idUsuario = intval($data['id_usuario']);
            $idEstacion = intval($data['id_estacion']);
            $fechaCierre = $data['fecha_cierre'];
            // Llamar al modelo para realizar la operación
            $deleted = $this->model->deleteCierreAndResetVentas($idCierre, $idUsuario, $idEstacion, $fechaCierre);
            if ($deleted) {
                $arrResponse = ['success' => true, 'message' => 'Cierre eliminado. Las ventas han sido actualizadas.', 'data' => $this->model->getHistorialCierres()];
            } else {
                $arrResponse['message'] = 'No se pudo eliminar el cierre o actualizar las ventas. Verifique los datos o inténtelo de nuevo.';
            }
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function mantenimiento(){
        // Validar nuevamente la sesión antes de mostrar el home
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        $data = [
            'page_tag' => 'E/S VENTA',
            'page_title' => "Pagina Principal",
            'page_name' => "combustible",
            'page_link' => "mantenimiento",
            'page_functions' => "function.dataventa.js"
        ];
        $this->views->getViews($this, "mantenimiento", $data);
    }

    public function getOpenSales() {
        $arrResponse = ['success' => false, 'message' => ''];
        try {
            $openSales = $this->model->getOpenSales();
            if (!empty($openSales)) {
                $arrResponse = ['success' => true, 'data' => $openSales];
            } else {
                $arrResponse['message'] = 'No hay ventas abiertas.';
            }
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function closeSale() {
        $arrResponse = ['success' => false, 'message' => ''];
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $idVenta = isset($data['id_venta']) ? intval($data['id_venta']) : 0;
            if ($idVenta > 0) {
                $closed = $this->model->closeSale($idVenta);
                if ($closed) {
                    $arrResponse = ['success' => true, 'message' => 'Venta cerrada exitosamente.'];
                } else {
                    $arrResponse['message'] = 'No se pudo cerrar la venta. Intente de nuevo.';
                }
            } else {
                $arrResponse['message'] = 'ID de venta no válido.';
            }
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function deleteAllOpenSales() {
        $arrResponse = ['success' => false, 'message' => ''];
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['idUser']) || empty($data['fecha'])) {
                throw new Exception("Datos incompletos.");
            }

            $idUser = intval($data['idUser']);
            $fecha = strClean($data['fecha']);

            $deleted = $this->model->deleteAllOpenSales($idUser, $fecha);
            if ($deleted) {
                $arrResponse = ['success' => true, 'message' => 'Registro de ventas eliminado correctamente.'];
            } else {
                $arrResponse['message'] = 'No se pudieron eliminar las ventas o no se encontraron registros abiertos.';
            }
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getTasaCurrent() {
        $arrResponse = array('success' => false, 'message' => '');
        try {
            $idEstacion = 0;
            if ($_SESSION['userData']['departamento_nombre'] != 'SISTEMA') {
                $idEstacion = $_SESSION['userData']['usuario_estacion_id'] ?? 0;
            }
            $tasa = $this->model->getTasa($idEstacion);
            $arrResponse = ['success' => true, 'tasa' => $tasa];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function deleteCierreTotal() {
        $arrResponse = ['success' => false, 'message' => ''];
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['idCierre']) || empty($data['idUser']) || empty($data['fecha'])) {
                throw new Exception("Datos incompletos.");
            }

            $idCierre = intval($data['idCierre']);
            $idUser = intval($data['idUser']);
            $fecha = strClean($data['fecha']);

            $deleted = $this->model->deleteCierreTotal($idCierre, $idUser, $fecha);
            if ($deleted) {
                $arrResponse = ['success' => true, 'message' => 'Cierre y ventas eliminados correctamente.'];
            } else {
                $arrResponse['message'] = 'No se pudo eliminar el cierre o no se encontraron registros coincidentes.';
            }
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
}