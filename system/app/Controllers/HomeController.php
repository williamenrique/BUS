<?php

class Home extends Controllers {
	private $loginModel;
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
    }

    /*manejo de sesiones activas*/
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
    /*fin manejo de sesiones activas*/

    /**inicio de manejo de errores en cada controlador debe estar */
	private function handleDatabaseError($error) {
        error_log("Error de BD en controlador Home: " . $error);
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
	/**fin de manejo de errores en cada controlador debe estar*/

    /**
     * Lee el id_institucion solicitado y lo filtra por permisos del usuario.
     */
    private function obtenerInstitucionFiltrada(): int {
        $solicitada = 1;
        if (isset($_GET['id_institucion'])) {
            $solicitada = intval($_GET['id_institucion']);
        } elseif (isset($_POST['id_institucion'])) {
            $solicitada = intval($_POST['id_institucion']);
        }
        return forceUserInstitution($solicitada);
    }

    public function home(){
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        $data = [
            'page_tag' => "Pagina principal",
            'page_title' => "Pagina Principal",
            'page_name' => "home",
            'page_link' => "home",
            'page_functions' => "function.home.js"
        ];

        // Lógica para cargar dashboard por departamento
        $department = strtoupper($_SESSION['userData']['departamento_nombre'] ?? 'DEFAULT');
        $rol = strtoupper($_SESSION['userData']['rol_nombre'] ?? 'DEFAULT');
        $view_path = 'Modules/data/';

        // El admin (Sistemas) ve la vista completa del sistema
        if ($department === 'SISTEMAS' || $department === 'SISTEMA') {
            $data['dashboard_view'] = $view_path . 'sistema.php';
            $data['es_admin'] = true;
        } else {
            $data['es_admin'] = false;
            switch ($department) {
                case 'ALMACEN':
                    $data['dashboard_view'] = $view_path . 'almacen.php';
                    break;
                case 'TALLER':
                    $data['dashboard_view'] = $view_path . 'almacen.php';
                    break;
                case 'OPERACIONES':
                    $data['dashboard_view'] = $view_path . 'operaciones.php';
                    break;
                case 'ESTACION':
                    $data['dashboard_view'] = $view_path . 'estacion.php';
                    break;
                case 'BIENES':
                    $data['dashboard_view'] = $view_path . 'bienes.php';
                    break;
                case 'COMPRAS':
                    $data['dashboard_view'] = $view_path . 'compras.php';
                    break;
                default:
                    break;
            }
        }

        // Pasar la institución del usuario a la vista
        $data['id_institucion_usuario'] = $_SESSION['userData']['id_institucion'] ?? 1;
        $data['es_admin'] = ($data['id_institucion_usuario'] === 0);

        $this->views->getViews($this, "home", $data);
    }

    /**
     * Actualiza la estación seleccionada para el usuario actual.
     */
    public function setStation() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (empty($_POST['idEstacion'])) {
                $arrResponse = ['status' => false, 'msg' => 'No se ha seleccionado una estación.'];
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            $userId = $_SESSION['userData']['usuario_id'];
            $stationId = intval($_POST['idEstacion']);

            $updated = $this->model->updateUserStation($userId, $stationId);

            if ($updated) {
                $newSessionData = $this->model->refreshSession($userId);

                if ($newSessionData) {
                    $arrResponse = ['status' => true, 'msg' => 'Estación actualizada correctamente. La página se recargará.'];
                } else {
                    $arrResponse = ['status' => false, 'msg' => 'La estación se actualizó, pero no se pudo refrescar la sesión.'];
                }
            } else {
                $arrResponse = ['status' => false, 'msg' => 'Error al actualizar la estación.'];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /**
     * Devuelve la lista de instituciones para el selector del admin.
     */
    public function getInstituciones() {
        try {
            $sql = "SELECT id_institucion, nombre FROM table_instituciones WHERE status = 1 ORDER BY id_institucion ASC";
            $data = $this->model->select_all($sql);
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getOperacionesData() {
        try {
            $idInstitucion = $this->obtenerInstitucionFiltrada();
            $data = $this->model->getOperacionesDashboard($idInstitucion);
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getAlmacenData() {
        try {
            $idInstitucion = $this->obtenerInstitucionFiltrada();
            $data = $this->model->getAlmacenDashboard($idInstitucion);
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Top 10 productos despachados del mes (Almacén).
     */
    public function getTopProductosAlmacen() {
        try {
            $idInstitucion = $this->obtenerInstitucionFiltrada();
            $data = $this->model->getTopProductosDespachados($idInstitucion, 10);
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Últimas 10 órdenes registradas (Almacén).
     */
    public function getUltimasOrdenesAlmacen() {
        try {
            $idInstitucion = $this->obtenerInstitucionFiltrada();
            $data = $this->model->getUltimasOrdenes($idInstitucion, 10);
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getEstacionDashboardData() {
        try {
            $data = $this->model->getEstacionDashboardData();
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Dashboard de Compras (datos ampliados).
     */
    public function getComprasData() {
        try {
            $idInstitucion = $this->obtenerInstitucionFiltrada();
            $data = $this->model->getComprasDashboardData($idInstitucion);
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Top 10 productos costeados (Compras).
     */
    public function getTopProductosCosteados() {
        try {
            $idInstitucion = $this->obtenerInstitucionFiltrada();
            $data = $this->model->getTopProductosCosteados($idInstitucion, 10);
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Últimas 10 requisiciones (Compras).
     */
    public function getUltimasRequisiciones() {
        try {
            $idInstitucion = $this->obtenerInstitucionFiltrada();
            $data = $this->model->getUltimasRequisiciones($idInstitucion, 10);
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Últimas 10 compras costeadas (Compras).
     */
    public function getUltimasComprasCosteadas() {
        try {
            $idInstitucion = $this->obtenerInstitucionFiltrada();
            $data = $this->model->getUltimasComprasCosteadas($idInstitucion, 10);
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getBienesDashboardData() {
        try {
            $data = $this->model->getBienesDashboardData();
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getAvailableMonths() {
        $data = $this->model->getAvailableMonths();
        if ($data) {
            $response = ['success' => true, 'data' => $data];
        } else {
            $response = ['success' => false, 'message' => 'No hay datos para mostrar.'];
        }
        echo json_encode($response);
    }

    public function getMonthlyLiters() {
        if (!isset($_POST['start_month']) || !isset($_POST['end_month'])) {
            $response = ['success' => false, 'message' => 'Parámetros incompletos.'];
            echo json_encode($response);
            return;
        }

        $startMonth = $_POST['start_month'];
        $endMonth = $_POST['end_month'];

        if (!preg_match('/^\d{4}-\d{2}$/', $startMonth) || !preg_match('/^\d{4}-\d{2}$/', $endMonth)) {
            $response = ['success' => false, 'message' => 'Formato de fecha inválido.'];
            echo json_encode($response);
            return;
        }

        $data = $this->model->getMonthlyLiters($startMonth, $endMonth);

        if ($data) {
            $response = ['success' => true, 'data' => $data];
        } else {
            $response = ['success' => false, 'message' => 'No hay datos para mostrar.'];
        }

        echo json_encode($response);
    }

    public function getDailySales() {
        $fecha = isset($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d');

        try {
            $userRol = $_SESSION['userData']['rol_nombre'] ?? '';
            $userEstacionId = $_SESSION['userData']['id_estacion'] ?? 0;

            $salesByUser = $this->model->getDailySalesByUser($fecha, $userRol, $userEstacionId);
            $dailySummary = $this->model->getDailySalesSummary($fecha);

            $response = [
                'success' => true,
                'data' => [
                    'sales_by_user' => $salesByUser,
                    'daily_summary' => $dailySummary,
                    'fecha' => $fecha
                ]
            ];
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => 'Error al obtener datos de ventas del día: ' . $e->getMessage()
            ];
        }

        echo json_encode($response);
    }

    public function getActiveUsers() {
        if (!isset($_SESSION['userData']['usuario_rol_id']) || $_SESSION['userData']['usuario_rol_id'] != 1) {
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            die();
        }

        try {
            $activeUsers = $this->model->selectActiveUsers();
            foreach ($activeUsers as &$user) {
                if (isset($user['created_at'])) {
                    $user['session_start_formatted'] = date('d/m/Y h:i A', strtotime($user['created_at']));
                }
            }
            echo json_encode(['success' => true, 'data' => $activeUsers]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener usuarios activos: ' . $e->getMessage()]);
        }
        die();
    }

    public function getAllUsersForAdmin() {
        if (!isset($_SESSION['userData']['usuario_rol_id']) || $_SESSION['userData']['usuario_rol_id'] != 1) {
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            die();
        }

        try {
            $allUsers = $this->model->selectAllUsersForAdmin();

            if (is_array($allUsers)) {
                foreach ($allUsers as &$user) {
                    if (!isset($user['departamento_nombre']) && isset($user['usuario_departamento_id'])) {
                        $deptoData = $this->model->selectDepartamento($user['usuario_departamento_id']);
                        $user['departamento_nombre'] = $deptoData ? $deptoData['departamento_nombre'] : 'No asignado';
                    }
                }
            }

            echo json_encode(['success' => true, 'data' => $allUsers ?: []]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener la lista de usuarios: ' . $e->getMessage()]);
        }
        die();
    }

    public function getNotifications() {
        $userId = $_SESSION['idUser'] ?? 0;
        $userRole = $_SESSION['userData']['rol_nombre'] ?? '';
        $userDepartmentName = $_SESSION['userData']['departamento_nombre'] ?? '';

        $notificationsData = $this->model->getPendingNotifications($userId, $userRole, $userDepartmentName);

        $response = ['success' => true, 'count' => $notificationsData['count'], 'notifications' => $notificationsData['notifications']];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        die();
    }
}