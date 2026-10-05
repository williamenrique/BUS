<?php
header('Access-Control-Allow-Origin: *');
class Flota extends Controllers{
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
        error_log("Error de BD en controlador User: " . $error);
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
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
	/**fin de manejo de errores en cada controlador debe estar*/

    /**
     * Determina el ID de institución a partir del parámetro de la URL.
     * @param string $institucion 'actual' (default) o 'taller'
     * @return int
     */
    private function resolverInstitucion($institucion = 'actual') {
        return ($institucion === 'taller') ? 2 : 1;
    }

    // =========================================================================
    // VISTAS PRINCIPALES (SSLMTY - institución 1)
    // =========================================================================

    public function flota($institucion = 'actual'){
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        
        $idInstitucion = $this->resolverInstitucion($institucion);
        $nombreInstitucion = $this->model->getNombreInstitucion($idInstitucion);
        $this->model->setInstitucion($idInstitucion);
        
        $data = [
            'page_tag' => "FLOTA",
            'page_title' => "Flota - " . obtenerIniciales($nombreInstitucion),
            'page_name' => "operaciones",
            'page_link' => ($idInstitucion === 2) ? "flota_taller" : "flota",
            'page_functions' => "function.flota.js",
            'id_institucion' => $idInstitucion,
            'nombre_institucion' => $nombreInstitucion,
            'es_taller' => ($idInstitucion === 2)
        ];
        $this->views->getViews($this, "flota", $data);
    }

    public function statusaceite($institucion = 'actual'){
        $idInstitucion = $this->resolverInstitucion($institucion);
        $nombreInstitucion = $this->model->getNombreInstitucion($idInstitucion);
        $this->model->setInstitucion($idInstitucion);
        
        $data = [
            'page_tag' => "FLOTA",
            'page_title' => "Cambio de Aceite - " . obtenerIniciales($nombreInstitucion),
            'page_name' => "operaciones",
            'page_link' => ($idInstitucion === 2) ? "aceite_taller" : "statusaceite",
            'page_functions' => "function.cambioaceite.js",
            'id_institucion' => $idInstitucion,
            'nombre_institucion' => $nombreInstitucion,
            'es_taller' => ($idInstitucion === 2)
        ];
        $this->views->getViews($this, "statusaceite", $data);
    }

    public function historialunidad($institucion = 'actual'){
        $url = $_SERVER['REQUEST_URI'];
        $parts = explode('/', rtrim($url, '/'));
        $idFlota = end($parts);
        $idFlota = intval($idFlota);

        if ($idFlota <= 0) {
            header("Location:".base_url().'flota');
            exit();
        }

        $idInstitucion = $this->resolverInstitucion($institucion);
        $nombreInstitucion = $this->model->getNombreInstitucion($idInstitucion);
        $this->model->setInstitucion($idInstitucion);

        $unidadData = $this->model->selectUnidad($idFlota);
        if (empty($unidadData)) {
            header("Location:".base_url().'flota');
            exit();
        }

        $data = [
            'page_tag' => "FLOTA",
            'page_title' => "Historial de Unidad: " . $unidadData['id_unidad'],
            'page_name' => "operaciones",
            'page_link' => ($idInstitucion === 2) ? "flota_taller" : "flota",
            'page_functions' => "function.historialunidad.js",
            'id_flota' => $idFlota,
            'unidad' => $unidadData,
            'id_institucion' => $idInstitucion,
            'nombre_institucion' => $nombreInstitucion,
            'es_taller' => ($idInstitucion === 2)
        ];
        $this->views->getViews($this, "historialunidad", $data);
    }

    // =========================================================================
    // WRAPPERS PARA EL TALLER (institución 2)
    // Estos métodos son necesarios porque tu enrutamiento es controlador/metodo,
    // no controlador/metodo/parametro. Se llaman desde URLs como:
    //   flota/taller
    //   flota/talleraceite
    //   flota/tallerhistorial/123
    // =========================================================================

    public function taller() {
        $this->flota('taller');
    }

    public function talleraceite() {
        $this->statusaceite('taller');
    }

    public function tallerhistorial() {
        $this->historialunidad('taller');
    }

    // =========================================================================
    // MÉTODOS AJAX / API
    // =========================================================================

    public function getSelects() {
        try {
            $data = $this->model->getSelectsData();
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getFlota() {
        try {
            $idInstitucion = intval($_GET['id_institucion'] ?? 1);
            $this->model->setInstitucion($idInstitucion);
            $arrData = $this->model->selectFlota();
            echo json_encode(['data' => $arrData], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    public function getUnidad(int $idFlota) {
        try {
            $idFlota = intval($idFlota);
            if ($idFlota > 0) {
                $arrData = $this->model->selectUnidad($idFlota);
                if (empty($arrData)) {
                    $arrResponse = ['success' => false, 'message' => 'Unidad no encontrada.'];
                } else {
                    $arrData['historial_mantenimiento'] = $this->model->selectMantenimientoHistory($idFlota);
                    $arrResponse = ['success' => true, 'data' => $arrData];
                }
            } else {
                $arrResponse = ['success' => false, 'message' => 'ID de unidad no válido.'];
            }
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => 'Error al obtener los datos: ' . $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getMantenimientoHistory(int $idFlota) {
        try {
            $idFlota = intval($idFlota);
            if ($idFlota > 0) {
                $arrData = $this->model->selectMantenimientoHistory($idFlota);
                $arrResponse = ['success' => true, 'data' => $arrData];
            } else {
                $arrResponse = ['success' => false, 'message' => 'ID de unidad no válido.'];
            }
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => 'Error al obtener el historial: ' . $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function setUnidad() {
        if ($_POST) {
            try {
                $idFlota = intval($_POST['id_flota'] ?? 0);
                $idInstitucion = intval($_POST['id_institucion'] ?? 1);
                $this->model->setInstitucion($idInstitucion);
                
                $data = [
                    'id_unidad' => strClean($_POST['id_unidad']),
                    'vim_unidad' => strClean($_POST['vim_unidad']),
                    'id_marca' => intval($_POST['id_marca']),
                    'id_modelo' => intval($_POST['id_modelo']),
                    'fecha_creacion' => strClean($_POST['fecha_creacion']),
                    'cap_pasajero' => intval($_POST['cap_pasajero']),
                    'tipo_combustible' => strClean($_POST['tipo_combustible']),
                    'transmision' => strClean($_POST['transmision']),
                    'status_unidad' => 1
                ];

                if ($idFlota == 0) {
                    $request = $this->model->insertFlota($data);
                    $option = 1;
                } else {
                    $data['id_flota'] = $idFlota;
                    $request = $this->model->updateFlota($data);
                    $option = 2;
                }

                if (intval($request) > 0 || $request === true) {
                    $message = ($option == 1) ? 'Unidad guardada correctamente.' : 'Unidad actualizada correctamente.';
                    $arrResponse = ['success' => true, 'message' => $message];
                } elseif ($request == "exist") {
                    $arrResponse = ['success' => false, 'message' => '¡Atención! El identificador o VIN de la unidad ya existe.'];
                } else {
                    $arrResponse = ['success' => false, 'message' => 'No es posible almacenar los datos.'];
                }
            } catch (Exception $e) {
                $arrResponse = ['success' => false, 'message' => 'Error en el proceso: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function setStatus() {
        if ($_POST) {
            try {
                $idFlota = intval($_POST['id_flota']);
                $status = intval($_POST['status']);
                $motivo = strClean($_POST['motivo']);
                $userId = $_SESSION['idUser'];
                
                $unidad = $this->model->selectUnidad($idFlota);
                $idInstitucion = $unidad['id_institucion'] ?? 1;
                $this->model->setInstitucion($idInstitucion);

                if ($idFlota > 0 && !empty($motivo)) {
                    $request = $this->model->updateStatusUnidad($idFlota, $status, $motivo, $userId);
                    $arrResponse = $request 
                        ? ['success' => true, 'message' => 'Estado actualizado correctamente.']
                        : ['success' => false, 'message' => 'No se pudo actualizar el estado.'];
                } else {
                    $arrResponse = ['success' => false, 'message' => 'Datos incompletos.'];
                }
            } catch (Exception $e) {
                $arrResponse = ['success' => false, 'message' => 'Error en el proceso: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getReporteData() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                $ids = $data['ids'] ?? [];

                if (empty($ids)) {
                    throw new Exception("No se proporcionaron IDs de unidades.");
                }

                $unidades = $this->model->selectUnidadesParaReporte($ids);
                $arrResponse = ['success' => true, 'data' => $unidades];

            } catch (Exception $e) {
                $arrResponse = ['success' => false, 'message' => 'Error en el proceso: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getAceiteStatus() {
        try {
            $idInstitucion = intval($_GET['id_institucion'] ?? 1);
            $this->model->setInstitucion($idInstitucion);
            
            $arrData = $this->model->selectAceiteStatus();
            $unidadesRequeridas = 0;
            $unidadesProximas = 0;
            $unidadesOk = 0;

            $umbral_proximo = 1000;
            $intervalo_cambio = 5000;

            foreach ($arrData as &$unidad) {
                $kmActual = $unidad['kilometraje_actual'] ?? 0;
                $kmUltimoCambio = $unidad['ultimo_cambio_km'] ?? 0;
                $kmProximoCambio = ($kmUltimoCambio > 0) ? $kmUltimoCambio + $intervalo_cambio : 0;
                $kmRestantes = $kmProximoCambio > 0 ? $kmProximoCambio - $kmActual : 0;

                if ($kmProximoCambio == 0) {
                    $unidad['estado'] = 'sin_registro';
                } else if ($kmRestantes <= 0) {
                    $unidad['estado'] = 'Requerido';
                    $unidadesRequeridas++;
                } else if ($kmRestantes <= $umbral_proximo) {
                    $unidad['estado'] = 'Próximo';
                    $unidadesProximas++;
                } else {
                    $unidad['estado'] = 'Bien';
                    $unidadesOk++;
                }
                $unidad['km_restantes'] = $kmRestantes;
                $unidad['proximo_cambio_km'] = $kmProximoCambio;
            }

            $response = [
                'data' => $arrData, 
                'requeridas' => $unidadesRequeridas,
                'proximas' => $unidadesProximas,
                'ok' => $unidadesOk
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => 'Error al obtener los datos: ' . $e->getMessage()];
        }
        die();
    }

    public function getReporteAceiteData() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                $idInstitucion = intval($_POST['id_institucion'] ?? 1);
                $this->model->setInstitucion($idInstitucion);
                
                $filtroRaw = $_POST['filtro'] ?? '';
                $filtrosSeleccionados = !empty($filtroRaw) ? explode(',', $filtroRaw) : [];
                $arrData = $this->model->selectAceiteStatus();
                
                $reporteData = [];
                $counts = ['Requerido' => 0, 'Próximo' => 0, 'Bien' => 0, 'Sin Registro' => 0, 'Total' => 0];
                
                $umbral_proximo = 1000;
                $intervalo_cambio = 5000;

                foreach ($arrData as $unidad) {
                    $kmActual = $unidad['kilometraje_actual'] ?? 0;
                    $kmUltimoCambio = $unidad['ultimo_cambio_km'] ?? 0;
                    $kmProximoCambio = ($kmUltimoCambio > 0) ? $kmUltimoCambio + $intervalo_cambio : 0;
                    $kmRestantes = $kmProximoCambio > 0 ? $kmProximoCambio - $kmActual : 0;
                    
                    $estado = 'Bien';
                    if ($kmProximoCambio == 0) {
                        $estado = 'Sin Registro';
                    } else if ($kmRestantes <= 0) {
                        $estado = 'Requerido';
                    } else if ($kmRestantes <= $umbral_proximo) {
                        $estado = 'Próximo';
                    }

                    if(isset($counts[$estado])) {
                        $counts[$estado]++;
                    }

                    if (in_array($estado, $filtrosSeleccionados)) {
                        $unidad['estado'] = $estado;
                        $unidad['km_restantes'] = $kmRestantes;
                        $unidad['proximo_cambio_km'] = $kmProximoCambio;
                        $reporteData[] = $unidad;
                    }
                }
                
                usort($reporteData, function($a, $b) {
                    $prioridad = ['Requerido' => 1, 'Próximo' => 2, 'Bien' => 3, 'Sin Registro' => 4];
                    $valA = $prioridad[$a['estado']] ?? 99;
                    $valB = $prioridad[$b['estado']] ?? 99;
                    
                    if ($valA == $valB) return 0;
                    return ($valA < $valB) ? -1 : 1;
                });

                $counts['Total'] = count($reporteData);
                $textoFiltros = empty($filtrosSeleccionados) ? 'Todos' : implode(', ', $filtrosSeleccionados);

                $nombreInstitucion = $this->model->getNombreInstitucion($idInstitucion);

                echo json_encode([
                    'success' => true, 
                    'items' => $reporteData, 
                    'counts' => $counts, 
                    'filtro' => $textoFiltros,
                    'nombre_institucion' => $nombreInstitucion
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
        die();
    }

    public function setKilometraje() {
        if ($_POST) {
            $idFlota = intval($_POST['id_flota_km']);
            $kilometraje = intval($_POST['kilometraje_actual']);
            
            $unidad = $this->model->selectUnidad($idFlota);
            $idInstitucion = $unidad['id_institucion'] ?? 1;
            $this->model->setInstitucion($idInstitucion);
            
            $request = $this->model->updateKilometraje($idFlota, $kilometraje, $_SESSION['idUser']);
            $arrResponse = $request ? ['success' => true, 'message' => 'Kilometraje actualizado.'] : ['success' => false, 'message' => 'No se pudo actualizar.'];
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function setCambioAceite() {
        if ($_POST) {
            $idFlota = intval($_POST['id_flota_aceite']);
            $kilometraje = intval($_POST['kilometraje_cambio']);
            $kilometrajeAnterior = intval($_POST['kilometraje_anterior_aceite']);
            $fechaCambio = strClean($_POST['fecha_cambio_aceite']);
            
            $unidad = $this->model->selectUnidad($idFlota);
            $idInstitucion = $unidad['id_institucion'] ?? 1;
            $this->model->setInstitucion($idInstitucion);
            
            $request = $this->model->insertCambioAceite($idFlota, $kilometraje, $kilometrajeAnterior, $_SESSION['idUser'], $fechaCambio);
            $arrResponse = $request ? ['success' => true, 'message' => 'Cambio de aceite registrado.'] : ['success' => false, 'message' => 'No se pudo registrar el cambio.'];
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getHistorialUnidad(int $idFlota) {
        try {
            $postData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $page = isset($postData['page']) ? intval($postData['page']) : 1;
            $perPage = 5;
            
            $unidad = $this->model->selectUnidad($idFlota);
            $idInstitucion = $unidad['id_institucion'] ?? 1;
            $this->model->setInstitucion($idInstitucion);
            
            $historialData = $this->model->selectHistorialUnidad($idFlota, $postData, $perPage);

            $totalPages = ceil($historialData['total_items'] / $perPage);

            $response = [
                'success' => true,
                'data' => [
                    'items' => $historialData['items'],
                    'total_items' => $historialData['total_items'],
                    'counts' => $historialData['counts']
                ],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages
                ]
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    public function getHistorialUnidadPrint(int $idFlota) {
        try {
            $postData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $perPage = 10000;
            
            $unidad = $this->model->selectUnidad($idFlota);
            $idInstitucion = $unidad['id_institucion'] ?? 1;
            $this->model->setInstitucion($idInstitucion);
            
            $historialData = $this->model->selectHistorialUnidad($idFlota, $postData, $perPage);
            $nombreInstitucion = $this->model->getNombreInstitucion($idInstitucion);

            $response = [
                'success' => true,
                'data' => [
                    'items' => $historialData['items'],
                    'counts' => $historialData['counts'],
                    'nombre_institucion' => $nombreInstitucion
                ]
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    public function deleteRecord() {
        if ($_POST) {
            try {
                $idFlota = intval($_POST['id_flota']);
                $recordType = strClean($_POST['record_type']);

                if ($idFlota <= 0 || !in_array($recordType, ['kilometraje', 'aceite'])) {
                    $arrResponse = ['success' => false, 'message' => 'Datos inválidos para la eliminación.'];
                } else {
                    $unidad = $this->model->selectUnidad($idFlota);
                    $idInstitucion = $unidad['id_institucion'] ?? 1;
                    $this->model->setInstitucion($idInstitucion);
                    
                    $request = false;
                    if ($recordType === 'kilometraje') {
                        $request = $this->model->deleteLatestKilometraje($idFlota);
                    } elseif ($recordType === 'aceite') {
                        $request = $this->model->deleteLatestAceite($idFlota);
                    }
                    $arrResponse = $request ? ['success' => true, 'message' => 'Registro eliminado correctamente.'] : ['success' => false, 'message' => 'No se pudo eliminar el registro o no existe.'];
                }
            } catch (Exception $e) {
                $arrResponse = ['success' => false, 'message' => 'Error en el proceso de eliminación: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
}