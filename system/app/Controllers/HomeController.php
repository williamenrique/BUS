<?php

class Home extends Controllers {
	private $loginModel;
    private $db; //para inicializar la base de datos
    public function __construct(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Validar sesión de manera más robusta
        // if (!$this->validateSession()) {
        //     header("Location:".base_url().'login');
        //     exit();
        // }
        //invocar para que se ejecute el metodo de la herencia
        parent::__construct();

        // $this->estacionModel = new EstacionModel();
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

        // Lógica para obtener las estaciones y pasarlas a la vista.
        // Esto es necesario para que el <select> de estaciones funcione.
        $rol = strtoupper($_SESSION['userData']['rol_nombre'] ?? 'DEFAULT');
        $department = strtoupper($_SESSION['userData']['departamento_nombre'] ?? 'DEFAULT');
        if ($rol === 'ADMINISTRADOR' || $department === 'SISTEMAS' || $department === 'SISTEMA') {
            // $data['estaciones'] = $this->estacionModel->selectEstaciones();
        }

        // Lógica para cargar dashboard por departamento
        // CORRECCIÓN: Usar el rol y el departamento para una lógica más clara y robusta.
        $department = strtoupper($_SESSION['userData']['departamento_nombre'] ?? 'DEFAULT');
        $rol = strtoupper($_SESSION['userData']['rol_nombre'] ?? 'DEFAULT');
        $view_path = 'Modules/data/';

        // Solo el departamento de Sistemas carga la vista completa del sistema.
        if ($department === 'SISTEMAS' || $department === 'SISTEMA') {
            $data['dashboard_view'] = $view_path . 'sistema.php';
            // --- INICIO DE LA CORRECCIÓN ---
            // Incluimos explícitamente la vista de almacén para el admin
            $data['almacen_view_for_admin'] = $view_path . 'almacen.php';
        } else {
            switch ($department) {
            case 'ALMACEN':
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
                // No se asigna dashboard_view para que la vista muestre el mensaje por defecto
                break;
            }
        }

        $this->views->getViews($this, "home", $data);
    }

    /**
     * Actualiza la estación seleccionada para el usuario actual.
     * Recibe el ID de la estación por POST.
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

            // 1. Actualizar la estación en la base de datos
            $updated = $this->model->updateUserStation($userId, $stationId);

            if ($updated = '1' && $updated = ' ') {
                // 2. Refrescar los datos de la sesión para que el cambio sea inmediato
                // Se usa el HomeModel para refrescar la sesión
                $newSessionData = $this->model->refreshSession($userId);

                if ($newSessionData) {
                    // El método refreshSession ya actualiza la variable de sesión.
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
    public function getOperacionesData() {
        try {
            $data = $this->model->getOperacionesDashboard();
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getAlmacenData() {
        try {
            $data = $this->model->getAlmacenDashboard();
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

    public function getComprasData() {
        try {
            $data = $this->model->getComprasDashboard();
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
    // mostrar dona 
    public function getMonthlyLiters() {
        if (!isset($_POST['start_month']) || !isset($_POST['end_month'])) {
            $response = ['success' => false, 'message' => 'Parámetros incompletos.'];
            echo json_encode($response);
            return;
        }
        
        $startMonth = $_POST['start_month'];
        $endMonth = $_POST['end_month'];
        
        // Validar formato de meses (YYYY-MM)
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
            // Obtener datos de la sesión del usuario para el filtrado
            $userRol = $_SESSION['userData']['rol_nombre'] ?? '';
            $userEstacionId = $_SESSION['userData']['id_estacion'] ?? 0;

            // Obtener ventas por usuario
            $salesByUser = $this->model->getDailySalesByUser($fecha, $userRol, $userEstacionId);

            // Obtener resumen general del día
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

    /**
     * Obtiene la lista de usuarios con sesiones activas.
     * Esta información solo debe ser accesible para roles de administrador.
     */
    public function getActiveUsers() {
        // Validar que solo el admin/sistema pueda acceder
        // CORRECCIÓN: La clave correcta en la sesión es 'usuario_rol_id' según la estructura de la tabla y el login.
        if (!isset($_SESSION['userData']['usuario_rol_id']) || $_SESSION['userData']['usuario_rol_id'] != 1) {
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            die();
        }

        try {
            $activeUsers = $this->model->selectActiveUsers(); // Esta función ya trae los datos necesarios.
            foreach ($activeUsers as &$user) { // Usamos '&' para modificar el array directamente
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

    /**
     * Obtiene la lista de todos los usuarios para la gestión del administrador.
     * Accesible solo para roles de administrador.
     */
    public function getAllUsersForAdmin() {
        // Validar que solo el admin/sistema pueda acceder
        // CORRECCIÓN: La clave correcta en la sesión es 'usuario_rol_id'.
        if (!isset($_SESSION['userData']['usuario_rol_id']) || $_SESSION['userData']['usuario_rol_id'] != 1) {
            echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
            die();
        }

        try {
            $allUsers = $this->model->selectAllUsersForAdmin();

            // Asegurarnos de que cada usuario tenga el nombre del departamento.
            // Esto previene errores si la consulta original no lo incluye.
            if (is_array($allUsers)) {
                foreach ($allUsers as &$user) { // Usamos '&' para modificar el array original
                    if (!isset($user['departamento_nombre']) && isset($user['usuario_departamento_id'])) {
                        // Si no tenemos el nombre pero sí el ID, lo buscamos.
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

    /**
     * Obtiene las notificaciones pendientes para el administrador.
     * Accesible solo para roles de administrador.
     */
    public function getNotifications() {
        // =================================================================
        // INICIO DE LA LÓGICA CENTRALIZADA DE NOTIFICACIONES
        // =================================================================
        // Obtener datos del usuario de la sesión, si existen.
        $userId = $_SESSION['idUser'] ?? 0; // Usar 0 si no hay sesión
        $userRole = $_SESSION['userData']['rol_nombre'] ?? '';
        $userDepartmentName = $_SESSION['userData']['departamento_nombre'] ?? '';

        $notificationsData = $this->model->getPendingNotifications($userId, $userRole, $userDepartmentName);

        $response = ['success' => true, 'count' => $notificationsData['count'], 'notifications' => $notificationsData['notifications']];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        die();
    }
}