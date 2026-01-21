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
    public function flota(){
        // Validar nuevamente la sesión antes de mostrar el home
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        $data = [
            'page_tag' => "FLOTA",
            'page_title' => "Pagina Principal",
            'page_name' => "operaciones",
            'page_link' => "flota",
            'page_functions' => "function.flota.js"
        ];
        $this->views->getViews($this, "flota", $data);
    }
    // comienzan los metodos

 /**
     * Obtiene los datos iniciales para los selects de los formularios.
     */
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

    /**
     * Obtiene todas las unidades para la DataTable.
     */
    public function getFlota() {
        try {
            $arrData = $this->model->selectFlota();
            echo json_encode(['data' => $arrData], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    /**
     * Obtiene los detalles de una unidad, incluyendo su historial de mantenimiento.
     */
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

    /**
     * Obtiene únicamente el historial de mantenimiento de una unidad.
     * @param int $idFlota
     */
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

    /**
     * Inserta o actualiza una unidad.
     */
    public function setUnidad() {
        if ($_POST) {
            try {
                $idFlota = intval($_POST['id_flota'] ?? 0);
                $data = [
                    'id_unidad' => strClean($_POST['id_unidad']),
                    'vim_unidad' => strClean($_POST['vim_unidad']),
                    'id_marca' => intval($_POST['id_marca']),
                    'id_modelo' => intval($_POST['id_modelo']),
                    'fecha_creacion' => strClean($_POST['fecha_creacion']),
                    'cap_pasajero' => intval($_POST['cap_pasajero']),
                    'tipo_combustible' => strClean($_POST['tipo_combustible']),
                    'transmision' => strClean($_POST['transmision']),
                    'status_unidad' => 1 // Status por defecto al crear
                ];

                if ($idFlota == 0) { // Crear
                    $request = $this->model->insertFlota($data);
                    $option = 1;
                } else { // Actualizar
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

    /**
     * Actualiza el estado de una unidad.
     */
    public function setStatus() {
        if ($_POST) {
            try {
                $idFlota = intval($_POST['id_flota']);
                $status = intval($_POST['status']);
                $motivo = strClean($_POST['motivo']);
                $userId = $_SESSION['idUser'];

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

    /**
     * Obtiene los datos detallados de un conjunto de unidades para el reporte de operatividad.
     */
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

    public function statusaceite(){
        $data = [
            'page_tag' => "FLOTA",
            'page_title' => "Pagina Principal",
            'page_name' => "operaciones",
            'page_link' => "statusaceite",
            'page_functions' => "function.cambioaceite.js"
        ];
        $this->views->getViews($this, "statusaceite", $data);
    }
    // AQUI VAN LOS METODOS PARA EL CAMBIO DE ACEITE

    /**
     * Obtiene el estado del cambio de aceite de todas las unidades.
     */
    public function getAceiteStatus() {
        try { // Este método se usa para la card y para la tabla (ahora DataTable)
            $arrData = $this->model->selectAceiteStatus();
            $unidadesRequeridas = 0;
            $unidadesProximas = 0;
            $unidadesOk = 0;

            // Definir umbrales
            $umbral_proximo = 1000; // Km antes de que se ponga amarillo
            $intervalo_cambio = 5000; // Intervalo estándar para el cambio

            foreach ($arrData as &$unidad) {
                $kmActual = $unidad['kilometraje_actual'] ?? 0;
                $kmUltimoCambio = $unidad['ultimo_cambio_km'] ?? 0;
                $kmProximoCambio = ($kmUltimoCambio > 0) ? $kmUltimoCambio + $intervalo_cambio : 0;
                $kmRestantes = $kmProximoCambio > 0 ? $kmProximoCambio - $kmActual : 0;

                if ($kmProximoCambio == 0) { // Nunca se ha hecho un cambio
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

            // Para la card, necesitamos el total de requeridas.
            // Para la DataTable, necesitamos el array de datos bajo la clave "data".
            // Podemos devolver todo en una sola respuesta.
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

    /**
     * Actualiza el kilometraje de una unidad.
     */
    public function setKilometraje() {
        if ($_POST) {
            $idFlota = intval($_POST['id_flota_km']);
            $kilometraje = intval($_POST['kilometraje_actual']);
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
            $fechaCambio = strClean($_POST['fecha_cambio_aceite']); // Recibimos la fecha
            $request = $this->model->insertCambioAceite($idFlota, $kilometraje, $kilometrajeAnterior, $_SESSION['idUser'], $fechaCambio);
            $arrResponse = $request ? ['success' => true, 'message' => 'Cambio de aceite registrado.'] : ['success' => false, 'message' => 'No se pudo registrar el cambio.'];
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
     public function historialunidad(){
        // Se pasa el ID de la flota desde la URL, si no existe, se redirige.
        $url = $_SERVER['REQUEST_URI'];
        $parts = explode('/', rtrim($url, '/'));
        $idFlota = end($parts);
        $idFlota = intval($idFlota);

        if ($idFlota <= 0) {
            header("Location:".base_url().'flota');
            exit();
        }

        // Obtener datos básicos de la unidad para mostrar en la cabecera
        $unidadData = $this->model->selectUnidad($idFlota);
        if (empty($unidadData)) {
            header("Location:".base_url().'flota'); // O a una página de error 404
            exit();
        }

        $data = [
            'page_tag' => "FLOTA",
            'page_title' => "Historial de Unidad: " . $unidadData['id_unidad'],
            'page_name' => "operaciones",
            'page_link' => "flota",
            'page_functions' => "function.historialunidad.js",
            'id_flota' => $idFlota,
            'unidad' => $unidadData
        ];
        $this->views->getViews($this, "historialunidad", $data);
    }
    // AQUI VAN LOS METODOS PARA EL HISTORIAL DE UNA UNIDAD
    public function getHistorialUnidad(int $idFlota) {
        try {
            $postData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $page = isset($postData['page']) ? intval($postData['page']) : 1;
            $perPage = 5; // Items por página, debe coincidir con el JS
            $historialData = $this->model->selectHistorialUnidad($idFlota, $postData, $perPage);

            $totalPages = ceil($historialData['total_items'] / $perPage);

            $response = [
                'success' => true,
                'data' => [
                    'items' => $historialData['items'],
                    'total_items' => $historialData['total_items'],
                    'counts' => $historialData['counts'] // Enviamos los contadores al JS
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

    // Método para obtener TODO el historial filtrado para impresión (sin paginación real)
    public function getHistorialUnidadPrint(int $idFlota) {
        try {
            $postData = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $perPage = 10000; // Límite alto para traer todos los registros
            $historialData = $this->model->selectHistorialUnidad($idFlota, $postData, $perPage);

            $response = [
                'success' => true,
                'data' => [
                    'items' => $historialData['items'],
                    'counts' => $historialData['counts']
                ]
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    /**
     * Elimina el último registro de kilometraje o de cambio de aceite.
     */
    public function deleteRecord() {
        if ($_POST) {
            try {
                $idFlota = intval($_POST['id_flota']);
                $recordType = strClean($_POST['record_type']); // 'kilometraje' o 'aceite'

                if ($idFlota <= 0 || !in_array($recordType, ['kilometraje', 'aceite'])) {
                    $arrResponse = ['success' => false, 'message' => 'Datos inválidos para la eliminación.'];
                } else {
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