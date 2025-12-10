<?php
header('Access-Control-Allow-Origin: *');
class Personal extends Controllers{
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
    public function personal(){
        // Validar nuevamente la sesión antes de mostrar el home
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        $data = [
            'page_tag' => "Pagina principal",
            'page_title' => "Pagina Principal",
            'page_name' => "usuarios",
            'page_link' => "personal",
            'page_functions' => "function.personal.js"
        ];
        $this->views->getViews($this, "personal", $data);
    }

    /*
    desde aqui comienzan los metodos
    */

    /**
     * Obtiene y formatea todo el personal para la DataTable.
     */
    public function getPersonal() {
        $arrData = $this->model->selectPersonal();
        // Añadir botón de eliminar
        $btnDelete = '';

        for ($i = 0; $i < count($arrData); $i++) {
            $status = '';
            switch ($arrData[$i]['personal_status']) {
                case 1:
                    $status = '<span class="badge badge-success">Activo</span>';
                    break;
                case 0:
                    $status = '<span class="badge badge-danger">Inactivo</span>';
                    break;
                case 2:
                    $status = '<span class="badge badge-info">Vacaciones</span>';
                    break;
                case 3:
                    $status = '<span class="badge badge-warning">Reposo</span>';
                    break;
            }
            // Concatenar nombre y apellido
            $arrData[$i]['personal_nombre'] = $arrData[$i]['personal_nombre'] . ' ' . $arrData[$i]['personal_apellido'];
            $arrData[$i]['personal_status'] = '<div class="text-center" onclick="fntStatusPersonal('.$arrData[$i]['id_personal'].')" style="cursor:pointer;">'.$status.'</div>';

            $btnView = '<button class="btn btn-info btn-sm" onClick="fntViewPersonal('.$arrData[$i]['id_personal'].')" title="Ver"><i class="far fa-eye"></i></button>';
            $btnEdit = '<button class="btn btn-primary btn-sm" onClick="fntEditPersonal('.$arrData[$i]['id_personal'].')" title="Editar"><i class="fas fa-pencil-alt"></i></button>';
            $btnDelete = '<button class="btn btn-danger btn-sm" onClick="fntDelPersonal('.$arrData[$i]['id_personal'].')" title="Eliminar"><i class="far fa-trash-alt"></i></button>';
            
            $arrData[$i]['acciones'] = '<div class="text-center d-flex justify-content-center">' . $btnView . '&nbsp;' . $btnEdit . '&nbsp;' . $btnDelete . '</div>';
        }
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Obtiene los cargos para poblar un select.
     */
    public function getSelectCargo() {
        $htmlOptions = "<option value='0'>Seleccione un cargo</option>";
        $arrData = $this->model->selectCargo();
        if (count($arrData) > 0) {
            foreach ($arrData as $cargo) {
                $htmlOptions .= '<option value="' . $cargo['id_cargo'] . '">' . $cargo['cargo'] . '</option>';
            }
        }
        echo $htmlOptions;
        die();
    }

    /**
     * Obtiene toda la lista de personal para un select.
     */
    public function getSelectPersonal() {
        $htmlOptions = ""; // Ya no incluimos el placeholder aquí, Select2 lo maneja
        $arrData = $this->model->selectPersonalList(); // Nuevo método en el modelo
        if (count($arrData) > 0) {
            foreach ($arrData as $personal) {
                $htmlOptions .= '<option value="' . $personal['id_personal'] . '">' . $personal['personal_cedula'] . ' - ' . $personal['personal_nombre'] . ' ' . $personal['personal_apellido'] . '</option>';
            }
        }
        echo $htmlOptions;
        die();
    }

    /**
     * Inserta o actualiza un registro de personal.
     */
    public function setPersonal() {
        // --- INICIO DE LA REESTRUCTURACIÓN ---
        if ($_POST) {
            $arrResponse = [];
            try {
                $idPersonal = intval($_POST['idPersonal'] ?? 0);
                $intIdentificacion = strClean($_POST['txtIdentificacion']);
                $strNombre = ucwords(strClean($_POST['txtNombre']));
                $strApellido = ucwords(strClean($_POST['txtApellido']));
                $intlistRolId = intval($_POST['listCargo']);
                $intTxtTlf = strClean($_POST['txtTelefono']);
                $strEmail = strtolower(strClean($_POST['txtEmail']));
                $strDireccion = strClean($_POST['txtDireccion']);
                $intTagPersonal = intval($_POST['listTagPersonal']);
                $intListStatus = intval($_POST['listStatus']);

                if (empty($intIdentificacion) || empty($strNombre) || empty($strApellido) || $intlistRolId == 0) {
                    throw new Exception('Cédula, Nombres, Apellidos y Cargo son obligatorios.');
                }

                if ($idPersonal == 0) { // Lógica para CREAR
                    $request_personal = $this->model->insertPersonal($intIdentificacion, $strNombre, $strApellido, $intlistRolId, $intTxtTlf, $strEmail, $strDireccion, $intTagPersonal, $intListStatus);
                    if ($request_personal > 0) {
                        $arrResponse = ['success' => true, 'message' => 'Personal guardado correctamente.'];
                    } elseif ($request_personal == 'exist') {
                        $arrResponse = ['success' => false, 'message' => '¡Atención! La cédula ya está registrada.'];
                    } else {
                        throw new Exception('No es posible almacenar los datos.');
                    }
                } else { // Lógica para ACTUALIZAR
                    $request_personal = $this->model->updatePersona($idPersonal, $intIdentificacion, $strNombre, $strApellido, $intlistRolId, $intTxtTlf, $strEmail, $strDireccion, $intTagPersonal, $intListStatus);
                    if ($request_personal === true) {
                        $arrResponse = ['success' => true, 'message' => 'Personal actualizado correctamente.'];
                    } elseif ($request_personal == 'exist') {
                        $arrResponse = ['success' => false, 'message' => '¡Atención! La cédula ya está registrada en otro perfil.'];
                    } else {
                        throw new Exception('No se realizó ningún cambio o no fue posible actualizar.');
                    }
                }
            } catch (Exception $e) {
                $arrResponse = ['success' => false, 'message' => $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
        // --- FIN DE LA REESTRUCTURACIÓN ---
    }

    /**
     * Obtiene los datos de un miembro del personal por su Cédula.
     * Usado para autocompletar el formulario de nuevo usuario.
     */
    public function getPersonalByCedula(string $cedula) {
        $cedula = strClean($cedula);
        if (empty($cedula)) {
            $arrResponse = ['success' => false, 'message' => 'Cédula no proporcionada.'];
        } else {
            $arrData = $this->model->selectPersonalByCedula($cedula); // Usamos un método que busca una cédula exacta
            if (empty($arrData)) {
                $arrResponse = ['success' => false, 'message' => 'No se encontró personal con esa cédula.'];
            } else {
                // Si se encuentra, se asume que el usuario no existe todavía y se devuelven los datos.
                $arrResponse = ['success' => true, 'data' => $arrData];
            }
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Busca personal por coincidencias en la cédula para autocompletado.
     */
    public function searchCedulas(string $term) {
        $term = strClean(strtolower($term));
        $results = [];

        if (strlen($term) >= 2) {
            $arrData = $this->model->searchPersonalByCedula($term);
            if (!empty($arrData)) {
                foreach ($arrData as $person) {
                    $results[] = [
                        'id' => $person['id_personal'],
                        'text' => $person['personal_cedula'] . ' - ' . $person['personal_nombre'] . ' ' . $person['personal_apellido']
                    ];
                }
            }
        } else {
            // Select2 espera un array 'results'
            echo json_encode(['results' => []]);
            die();
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results], JSON_UNESCAPED_UNICODE);
        die();
    }


    /**
     * Obtiene los datos de un miembro del personal por su ID.
     */
    public function getPersonalById(int $idpersonal) {
        $idpersonal = intval($idpersonal);
        if ($idpersonal > 0) {
            $arrData = $this->model->selectPersonalID($idpersonal);
            if (empty($arrData)) {
                $arrResponse = ['success' => false, 'message' => 'Datos no encontrados.'];
            } else {
                $arrResponse = ['success' => true, 'data' => $arrData];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /**
     * Elimina un registro de personal (eliminación lógica).
     */
    public function delPersonal() {
        if ($_POST) {
            $idPersonal = intval($_POST['idPersonal']);
            if ($idPersonal > 0) {
                $requestDelete = $this->model->deletePersonal($idPersonal);
                if ($requestDelete) {
                    $arrResponse = ['success' => true, 'message' => 'Personal eliminado correctamente.'];
                } else {
                    $arrResponse = ['success' => false, 'message' => 'Error al eliminar el personal.'];
                }
            } else {
                $arrResponse = ['success' => false, 'message' => 'ID de personal inválido.'];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /**
     * Cambia el estado de un miembro del personal y registra el motivo.
     */
    public function setStatusPersonal() {
        if ($_POST) {
            $idPersonal = intval($_POST['idPersonal']);
            $idStatus = intval($_POST['idStatus']);
            $srtText = strClean($_POST['srtText']);
            $intUserId = $_SESSION['idUser'];

            if ($idPersonal <= 0 || !in_array($idStatus, [0, 1, 2, 3]) || empty($srtText)) {
                $arrResponse = ['success' => false, 'message' => 'Datos incorrectos.'];
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }

            // Actualizar el estado
            $requestStatus = $this->model->statusPersonal($idPersonal, $idStatus);

            if ($requestStatus) {
                // Registrar el cambio en el historial
                $this->model->cambioStatusPersonal($idPersonal, $idStatus, $srtText, $intUserId);
                $arrResponse = ['success' => true, 'message' => 'Estado actualizado correctamente.'];
            } else {
                $arrResponse = ['success' => false, 'message' => 'Error al cambiar el estado del personal.'];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /**
     * Obtiene el cargo actual de una persona y la lista de todos los cargos.
     */
    public function getSelectCargoP(int $idPersonal) {
        $personalData = $this->model->selectPersonalID($idPersonal);
        $allCargos = $this->model->selectCargo();
        
        $htmlOptions = '<option value="'.$personalData['personal_cargo'].'" selected>'.$personalData['cargo'].'</option>';
        foreach ($allCargos as $cargo) {
            if ($cargo['id_cargo'] != $personalData['personal_cargo']) {
                $htmlOptions .= '<option value="' . $cargo['id_cargo'] . '">' . $cargo['cargo'] . '</option>';
            }
        }
        echo $htmlOptions;
        die();
    }
    
}