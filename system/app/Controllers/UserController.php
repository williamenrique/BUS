<?php
header('Access-Control-Allow-Origin: *');
class User extends Controllers{
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
	//TODO: inicio de vista
	public function perfil(){
		// Validar nuevamente la sesión antes de mostrar el home
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
		//invocar la vista con views y usamos getView y pasamos parametros esta clase y la vista
		//incluimos un arreglo que contendra toda la informacion que se enviara al home
		$data = [
			'page_tag' => "GESTION USUARIO",
			'page_title' => "Pagina Principal",
			'page_name' => "usuarios",
			'page_link' => "perfil",//activar el menu desplegable o un lin solo
			'page_functions' => "function.user.js"
		];
		$this->views->getViews($this, "perfil", $data);
	}
	// cambiar imagen de usuario
	public function subirImagen(){
		$arrResponse = array('success' => false, 'message' => '', 'ruta_imagen' => '');
		try {
			// Verificar que se haya enviado una imagen
			if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
				throw new Exception('No se ha seleccionado ninguna imagen o ocurrió un error en la carga');
			}
			// Obtener datos del usuario desde el formulario
			$id_usuario = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
			if ($id_usuario === 0) {
				throw new Exception('ID de usuario no válido');
			}
			// Configuración
			$archivos_permitidos = array('jpg', 'jpeg', 'png', 'gif', 'svg', 'webp');
			$max_size = 8 * 1024 * 1024; // 8MB en bytes
			$max_width = 800;
			$max_height = 800;
			// Obtener información del archivo
			$file = $_FILES['imagen'];
			$fileData = pathinfo($file['name']);
			$fileExtension = strtolower($fileData['extension']);
			// Validaciones
			if (!in_array($fileExtension, $archivos_permitidos)) {
				throw new Exception('Formato de archivo no permitido. Use: ' . implode(', ', $archivos_permitidos));
			}
			if ($file['size'] > $max_size) {
				throw new Exception('La imagen es demasiado grande. Tamaño máximo: 8MB');
			}
			// Crear directorio si no existe
			$baseDirectory = 'storage/'.$_SESSION['userData']['usuario_nick'].'/';
			if (!file_exists($baseDirectory)) {
				if (!mkdir($baseDirectory, 0777, true)) {
					throw new Exception('No se pudo crear el directorio de almacenamiento');
				}
			}
			// Generar nombre único para el archivo
			$fileName = uniqid('profile_', true) . '.' . $fileExtension;
			$filePath = $baseDirectory . $fileName;
			// Validar y procesar imagen
			$imageInfo = getimagesize($file['tmp_name']);
			if (!$imageInfo) {
				throw new Exception('El archivo no es una imagen válida');
			}
			// Opcional: Redimensionar imagen si es muy grande
			list($width, $height) = $imageInfo;
			if ($width > $max_width || $height > $max_height) {
				// Aquí podrías agregar lógica de redimensionamiento
				// usando GD library o Intervention Image
			}
			// Mover archivo al directorio destino
			if (!move_uploaded_file($file['tmp_name'], $filePath)) {
				throw new Exception('Error al guardar la imagen en el servidor');
			}
			// Eliminar imagen anterior si existe
			if (!empty($_SESSION['userData']['usuario_imagen']) && file_exists($_SESSION['userData']['usuario_imagen'])) {
				@unlink($_SESSION['userData']['usuario_imagen']);
			}
			// Actualizar en base de datos
			$requestUser = $this->model->updateImg($id_usuario, $filePath);
			if (!$requestUser) {
				// Si falla la BD, eliminar la imagen subida
				@unlink($filePath);
				throw new Exception('Error al actualizar la base de datos');
			}
			// Actualizar sesión
			sessionUser($_SESSION['idUser']);
	
			$arrResponse = [
				'success' => true,
				'message' => 'Imagen de perfil actualizada correctamente',
				'ruta_imagen' => $filePath,
				'userData' => $_SESSION['userData'] // Opcional: enviar datos actualizados
			];
	
		} catch (Exception $e) {
			$arrResponse = [
				'success' => false,
				'message' => $e->getMessage(),
				'ruta_imagen' => ''
			];
		}
		header('Content-Type: application/json');
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}
	// funcion cambiar password
	public function cambiarPassword() {
		$arrResponse = array('success' => false, 'message' => '');	
		try {
			// uso de manejo de error Verificar conexión primero
            if ($this->model->hasError()) {
				throw new Exception('Error de conexión a la base de datos: ' . $this->model->getError());
			}
			// Obtener datos JSON
			$json = file_get_contents('php://input');
			$data = json_decode($json, true);
			if (!$data || !isset($data['currentPassword']) || !isset($data['newPassword']) || !isset($data['id_usuario'])) {
				throw new Exception('Datos incompletos');
			}
			$currentPassword = $data['currentPassword'];
			$newPassword = $data['newPassword'];
			$id_usuario = intval($data['id_usuario']);
			// Validaciones
			if (empty($currentPassword) || empty($newPassword)) {
				throw new Exception('Las contraseñas no pueden estar vacías');
			}
			if (strlen($newPassword) < 2) {
				throw new Exception('La nueva contraseña debe tener al menos 6 caracteres');
			}
			// Verificar usuario y obtener datos
			// echo $id_usuario;
			$userData = $this->model->selectUsuario($id_usuario);
			if (empty($userData)) {
				throw new Exception('Usuario no encontrado');
			}
			// Verificar contraseña actual
			// if (!password_verify($currentPassword, $userData['usuario_password'])) {
			// 	throw new Exception('La contraseña actual es incorrecta');
			// }
			if ($currentPassword != decryption($userData['usuario_password'])) {
				// echo "nueva ".$currentPassword.' actual '. decryption($userData['usuario_password']);
				throw new Exception('La contraseña actual es incorrecta');
			}
			// Hash de la nueva contraseña
			// $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
			$hashedPassword = encryption($newPassword);
			// Actualizar en base de datos
			$requestUpdate = $this->model->updatePassword($id_usuario, $hashedPassword);
			if (!$requestUpdate) {
				throw new Exception('Error al actualizar la contraseña en la base de datos');
			}
			$arrResponse = [
				'success' => true,
				'message' => 'Contraseña actualizada correctamente'
			];
		} catch (Exception $e) {
			$arrResponse = [
				'success' => false,
				'message' => $e->getMessage()
			];
		}
	
		header('Content-Type: application/json');
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}
	// funcion actualizar datos del usuario
	public function actualizarDatos() {
		$arrResponse = array('success' => false, 'message' => '');
		try {
			// uso de manejo de error Verificar conexión primero
            if ($this->model->hasError()) {
				throw new Exception('Error de conexión a la base de datos: ' . $this->model->getError());
			}
			// colocarlo siempre para manejo de errores
			// Obtener datos JSON
			$json = file_get_contents('php://input');
			$data = json_decode($json, true);
	
			if (!$data || !isset($data['id_usuario'])) {
				throw new Exception('Datos incompletos');
			}
			$usuario_id = intval($data['id_usuario']);
			$nombres = trim($data['usuario_nombres'] ?? '');
			$apellidos = trim($data['usuario_apellidos'] ?? '');
			$email = trim($data['usuario_email'] ?? '');
			$telefono = trim($data['usuario_telefono'] ?? '');
			$direccion = trim($data['usuario_direccion'] ?? '');
			// Validaciones
			if (empty($nombres)) {
				throw new Exception('El nombre es obligatorio');
			}
			if (empty($apellidos)) {
				throw new Exception('El apellido es obligatorio');
			}
			if (empty($email)) {
				throw new Exception('El correo electrónico es obligatorio');
			}
			// Validar formato de email
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				throw new Exception('El formato del correo electrónico no es válido');
			}
			// Verificar si el email ya existe para otro usuario
			$existingUser = $this->model->checkEmailExists($email, $usuario_id);
			// 
			if ($existingUser) {
				throw new Exception('El correo electrónico ya está en uso por otro usuario');
			}
			// Actualizar en base de datos
			$requestUpdate = $this->model->updateUserData($usuario_id, [
				'usuario_nombres' => $nombres,
				'usuario_apellidos' => $apellidos,
				'usuario_email' => $email,
				'usuario_telefono' => $telefono,
				'usuario_direccion' => $direccion
			]);
			// dep($requestUpdate);
			// se puede usar esta o no 
			// if (!$requestUpdate) {
			// 	throw new Exception('Error al actualizar los datos en la base de datos');
			// }
			 // VERIFICAR ERROR INMEDIATAMENTE después de la actualización
			// if (!$requestUpdate['success']) {
			// 	throw new Exception('Error al actualizar: ' . $requestUpdate['error']);
			// }
			// Obtener datos actualizados
			$userData = $this->model->selectUsuario($usuario_id);
			// Actualizar sesión
			// CORRECCIÓN: Actualizar la sesión con los datos correctos de la tabla personal
			$_SESSION['userData']['personal_nombre'] = $nombres;
			$_SESSION['userData']['personal_apellido'] = $apellidos;
			$_SESSION['userData']['personal_email'] = $email;
			$_SESSION['userData']['personal_tlf'] = $telefono;

			$arrResponse = [
				'success' => true,
				'message' => 'Datos actualizados correctamente',
				'userData' => $userData
			];
		} catch (Exception $e) {
			$arrResponse = [
				'success' => false,
				'message' => $e->getMessage()
			];
		}
		header('Content-Type: application/json');
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}
	// TODO: incio de ista
	public function newuser(){
		//invocar la vista con views y usamos getView y pasamos parametros esta clase y la vista
		//incluimos un arreglo que contendra toda la informacion que se enviara al home
		$data = [
			'page_tag' => "GESTION USUARIO",
			'page_title' => "Pagina Principal",
			'page_name' => "usuarios",
			'page_link' => "newuser",//activar el menu desplegable o un lin solo
			'page_functions' => "function.user.js"
		];
		$this->views->getViews($this, "newuser", $data);
	}
	// Obtener roles para select
	public function getRoles() {
		$arrResponse = array('success' => false, 'roles' => array());
		try {
			// uso de manejo de error Verificar conexión primero
            if ($this->model->hasError()) {
				throw new Exception('Error de conexión a la base de datos: ' . $this->model->getError());
			}
			
			$roles = $this->model->getRoles();
			
			$arrResponse = [
				'success' => true,
				'roles' => $roles
			];
		} catch (Exception $e) {
			$arrResponse = [
				'success' => false,
				'message' => $e->getMessage()
			];
		}
		header('Content-Type: application/json');
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}

	// Obtener departamentos para select
	public function getDepartments() {
		$arrResponse = array('success' => false, 'departments' => array());
		try {
			// if ($this->db->hasError()) {
			// 	throw new Exception('Error de conexión a la base de datos: ' . $this->db->getAnyError());
			// }
			$departments = $this->model->getDepartments();
			
			$arrResponse = [
				'success' => true,
				'departments' => $departments
			];
		} catch (Exception $e) {
			$arrResponse = [
				'success' => false,
				'message' => $e->getMessage()
			];
		}
		header('Content-Type: application/json');
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}
/***** Crear usuario **********/
	public function setUser() {
		$arrResponse = array('success' => false, 'message' => '');
		try {
			if ($this->model->hasError()) {
				throw new Exception('Error de conexión a la base de datos: ' . $this->model->getError());
			}
			// Obtener datos JSON
			$json = file_get_contents('php://input');
			$data = json_decode($json, true);
			if (!$data) {
				throw new Exception('Datos incompletos');
			}
			// El ID del registro de personal que se va a usar o actualizar
			$intIdPersonalFk = intval($data['id_personal_fk'] ?? 0);
			$strNombre = ucwords(trim($data['txtNombre'] ?? ''));
			$strApellidos = ucwords(trim($data['txtApellido'] ?? ''));
			$srtDireccion = ucwords(trim($data['txtDireccion'] ?? ''));
			$intTlf = intval($data['txtTelefono'] ?? 0);
			$strEmail = strtolower(trim($data['txtEmail'] ?? ''));
			$intlistRolId = intval($data['listRolId'] ?? 0);
			$intlistDep = intval($data['listDep'] ?? 0);
			
			// comienzan las validaciones
			// Si no se seleccionó un personal existente Y no se escribió una cédula nueva
			if ($intIdPersonalFk === 0) {
				throw new Exception('La identificación es obligatoria');
			}
			if (empty($strNombre)) {
				throw new Exception('El nombre es obligatorio');
			}
			if (empty($strApellidos)) {
				throw new Exception('El apellido es obligatorio');
			}
			if (empty($strEmail)) {
				throw new Exception('El correo electrónico es obligatorio');
			}
			if (!filter_var($strEmail, FILTER_VALIDATE_EMAIL)) {
				throw new Exception('El formato del correo electrónico no es válido');
			}
			if ($intlistRolId === 0) {
				throw new Exception('Debe seleccionar un rol');
			}
			if ($intlistDep === 0) {
				throw new Exception('Debe seleccionar un departamento');
			}
			// validado que no ingresen vacion o datos erroneos
			// Verificar si el email ya existe
			$existingEmail = $this->model->checkEmailExists($strEmail, 0);
			if ($existingEmail) {
				throw new Exception('El correo electrónico ya está en uso por otro usuario');
			}
			// Verificar si ya existe un usuario para esta persona
			$existingId = $this->model->checkIdExists($intIdPersonalFk);
			if ($existingId) {
				throw new Exception('Esta persona ya tiene una cuenta de usuario asignada.');
			}		
			// Crear contraseña por defecto (123456)
			$strPass = encryption('123456');

			// Agrupar datos para el modelo
			$personalData = [
				'id_personal' => $intIdPersonalFk,
				'nombre' => $strNombre,
				'apellido' => $strApellidos,
				'telefono' => $intTlf,
				'email' => $strEmail,
				'direccion' => $srtDireccion
			];

			$userData = [
				'rol_id' => $intlistRolId, 
				'dep_id' => $intlistDep, 
				'password' => $strPass
			];

			$requestUser = $this->model->createUserWithPersonalUpdate($personalData, $userData);

			if ($requestUser == 0) {
				throw new Exception('Error al crear el usuario en la base de datos');
			}
			if ($requestUser == "exist") {
				throw new Exception('Usuario existente');
			}
			// Crear nick y carpeta
			$userNIck = substr($strNombre, 0, 1) . substr($strApellidos, 0, 1) . '-' . $requestUser;
			$fileBase = "storage/" . $userNIck . "/";
			// Crear directorio si no existe
			if (!file_exists($fileBase)) {
				if (!mkdir($fileBase, 0777, true)) {
					throw new Exception('No se pudo crear el directorio de almacenamiento');
				}
			}
			// Actualizar nick en base de datos
			$createNick = $this->model->updateUserNickAndPath($requestUser, $userNIck, $fileBase);
			if (!$createNick) {
				throw new Exception('Error al actualizar el nick del usuario');
			}
			// Copiar imagen por defecto
			$source = "src/img/logo.png";
			$destination = $fileBase . 'default.png';
			if (file_exists($source)) {
				if (!copy($source, $destination)) {
					print_r("No se pudo copiar la imagen por defecto para el usuario " . $requestUser);
				}
			}
			$arrResponse = [
				'success' => true,
				'message' => 'Usuario creado correctamente',
				'userId' => $requestUser
			];
		} catch (Exception $e) {
			$arrResponse = [
				'success' => false,
				'message' => $e->getMessage()
			];
		}
		header('Content-Type: application/json');
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}
	/**** mostrar usuarios en tabla y sus acciones *******/
	// Obtener todos los usuarios para DataTable
	public function getUsuarios() {
		$arrResponse = array('success' => false, 'data' => array());
		try {
			if ($this->model->hasError()) {
				throw new Exception('Error de conexión a la base de datos: ' . $this->model->getError());
			}
			$usuarios = $this->model->getUsuarios();
			$arrResponse = [
				'success' => true,
				'data' => $usuarios
			];
		} catch (Exception $e) {
			$arrResponse = [
				'success' => false,
				'message' => $e->getMessage()
			];
		}
		header('Content-Type: application/json');
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}
	// Obtener un usuario específico para edición
	public function getUsuario($idUsuario) {
		$arrResponse = array('success' => false, 'usuario' => array());
		try {
			if ($this->model->hasError()) {
				throw new Exception('Error de conexión a la base de datos: ' . $this->model->getError());
			}
			$idUsuario = intval($idUsuario);
			if ($idUsuario <= 0) {
				throw new Exception('ID de usuario no válido');
			}
			$usuario = $this->model->getUsuario($idUsuario);
			if (!$usuario) {
				throw new Exception('Usuario no encontrado');
			}
			$arrResponse = [
				'success' => true,
				'usuario' => $usuario
			];
		} catch (Exception $e) {
			$arrResponse = [
				'success' => false,
				'message' => $e->getMessage()
			];
		}
		header('Content-Type: application/json');
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}
	/**** fin mostrar usuarios en tabla y sus acciones *******/
	// Actualizar usuario
	public function updateUsuario() {
		$arrResponse = array('success' => false, 'message' => '');
		try {
			if ($this->model->hasError()) {
				throw new Exception('Error de conexión a la base de datos: ' . $this->model->getError());
			}
			// Obtener datos JSON
			$json = file_get_contents('php://input');
			$data = json_decode($json, true);
			
			if (!$data || !isset($data['usuario_id'])) {
				throw new Exception('Datos incompletos');
			}
			// Validar formato de email solo si no está vacío
			if (!empty($data['personal_email']) && !filter_var($data['personal_email'], FILTER_VALIDATE_EMAIL)) {
				throw new Exception('El formato del correo electrónico no es válido');
			}
			// Verificar si el email ya existe para otro usuario
			$existingUser = $this->model->checkEmailExists($data['personal_email'], $data['usuario_id']);
			if ($existingUser) {
				throw new Exception('El correo electrónico ya está en uso por otro usuario');
			}
			// Actualizar en base de datos
			$requestUpdate = $this->model->updateUsuario($data);
			
			if (!$requestUpdate) {
				throw new Exception('Error al actualizar el usuario en la base de datos');
			}
			$arrResponse = [
				'success' => true,
				'message' => 'Usuario actualizado correctamente'
			];
		} catch (Exception $e) {
			$arrResponse = [
				'success' => false,
				'message' => $e->getMessage()
			];
		}
		header('Content-Type: application/json');
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}
	// Actualizar estado del usuario
	public function updateStatus() {
		$arrResponse = array('success' => false, 'message' => '');
		try {
			if ($this->model->hasError()) {
				throw new Exception('Error de conexión a la base de datos: ' . $this->model->getError());
			}
			// Obtener datos JSON
			$json = file_get_contents('php://input');
			$data = json_decode($json, true);
			
			if (!$data || !isset($data['usuario_id']) || !isset($data['usuario_status'])) {
				throw new Exception('Datos incompletos');
			}
			// Actualizar estado en base de datos
			$requestUpdate = $this->model->updateStatus($data['usuario_id'], $data['usuario_status']);
			if (!$requestUpdate) {
				throw new Exception('Error al actualizar el estado del usuario');
			}
			$arrResponse = [
				'success' => true,
				'message' => 'Estado actualizado correctamente'
			];
		} catch (Exception $e) {
			$arrResponse = [
				'success' => false,
				'message' => $e->getMessage()
			];
		}
		header('Content-Type: application/json');
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}

	// Vista de lista de usuarios
	public function usuarios() {
		$data = [
			'page_tag' => "Gestión de Usuarios",
			'page_title' => "Lista de Usuarios",
			'page_name' => "usuarios",
			'page_link' => "newuser",
			'page_functions' => "function.user.js"
		];
		$this->views->getViews($this, "usuarios", $data);
	}

	/*****
	vista de departamentos
	*****/
	public function departamentos(){

        $data = [
            'page_tag' => "Departamentos",
            'page_title' => "Gestión de Departamentos",
            'page_name' => "usuarios",
            'page_link' => "departamentos",
            'page_functions' => "function.departamentos.js"
        ];
        $this->views->getViews($this, "departamentos", $data);
    }

    public function getDepartamentos() {
        $arrData = $this->model->selectDepartamentos();
        for ($i=0; $i < count($arrData); $i++) {
            $status = $arrData[$i]['departamento_status'] == 1 
                ? '<span class="badge badge-success">Activo</span>' 
                : '<span class="badge badge-danger">Inactivo</span>';
            $arrData[$i]['departamento_status'] = $status;

            $btnEdit = '<button class="btn btn-primary btn-sm" onClick="fntEditDepto('.$arrData[$i]['departamento_id'].')" title="Editar"><i class="fas fa-pencil-alt"></i></button>';
            $btnDelete = '<button class="btn btn-danger btn-sm" onClick="fntDelDepto('.$arrData[$i]['departamento_id'].')" title="Eliminar"><i class="far fa-trash-alt"></i></button>';
            $arrData[$i]['acciones'] = '<div class="text-center">' . $btnEdit . ' ' . $btnDelete . '</div>';
        }
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getDepartamento(int $iddepto) {
        $iddepto = intval($iddepto);
        if ($iddepto > 0) {
            $arrData = $this->model->selectDepartamento($iddepto);
            if (empty($arrData)) {
                $arrResponse = ['success' => false, 'message' => 'Datos no encontrados.'];
            } else {
                $arrResponse = ['success' => true, 'data' => $arrData];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function setDepartamento() {
        if ($_POST) {
            $idDepto = intval($_POST['idDepartamento']);
            $nombre = strClean($_POST['txtNombre']);
            $descripcion = strClean($_POST['txtDescripcion']);
            $status = intval($_POST['listStatus']);

            if ($idDepto == 0) {
                // Crear
                $request_depto = $this->model->insertDepartamento($nombre, $descripcion, $status);
                $option = 1;
            } else {
                // Actualizar
                $request_depto = $this->model->updateDepartamento($idDepto, $nombre, $descripcion, $status);
                $option = 2;
            }

            if (intval($request_depto) > 0) {
                if ($option == 1) {
                    $arrResponse = ['success' => true, 'message' => 'Departamento guardado correctamente.'];
                } else {
                    $arrResponse = ['success' => true, 'message' => 'Departamento actualizado correctamente.'];
                }
            } else if ($request_depto == 'exist') {
                $arrResponse = ['success' => false, 'message' => '¡Atención! El departamento ya existe.'];
            } else {
                $arrResponse = ['success' => false, 'message' => 'No es posible almacenar los datos.'];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function delDepartamento() {
        if ($_POST) {
            $idDepto = intval($_POST['idDepartamento']);
            $requestDelete = $this->model->deleteDepartamento($idDepto);
            if ($requestDelete == 'in_use') {
                $arrResponse = ['success' => false, 'message' => 'No se puede eliminar. El departamento está asignado a uno o más usuarios.'];
            } else if ($requestDelete) {
                $arrResponse = ['success' => true, 'message' => 'Se ha eliminado el departamento.'];
            } else {
                $arrResponse = ['success' => false, 'message' => 'Error al eliminar el departamento.'];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /*****
	* CRUD de Roles
	*****/
    public function getRolesForTable() {
        $arrData = $this->model->selectRoles();
        for ($i=0; $i < count($arrData); $i++) {
            $status = $arrData[$i]['rol_status'] == 1 
                ? '<span class="badge badge-success">Activo</span>' 
                : '<span class="badge badge-danger">Inactivo</span>';
            $arrData[$i]['rol_status'] = $status;

            $btnEdit = '<button class="btn btn-primary btn-sm" onClick="fntEditRol('.$arrData[$i]['rol_id'].')" title="Editar"><i class="fas fa-pencil-alt"></i></button>';
            $btnDelete = '<button class="btn btn-danger btn-sm" onClick="fntDelRol('.$arrData[$i]['rol_id'].')" title="Eliminar"><i class="far fa-trash-alt"></i></button>';
            $arrData[$i]['acciones'] = '<div class="text-center">' . $btnEdit . ' ' . $btnDelete . '</div>';
        }
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getRol(int $idrol) {
        $idrol = intval($idrol);
        if ($idrol > 0) {
            $arrData = $this->model->selectRol($idrol);
            if (empty($arrData)) {
                $arrResponse = ['success' => false, 'message' => 'Datos no encontrados.'];
            } else {
                $arrResponse = ['success' => true, 'data' => $arrData];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function setRol() {
        if ($_POST) {
            $idRol = intval($_POST['idRol']);
            $nombre = strClean($_POST['txtNombre']);
            $descripcion = strClean($_POST['txtDescripcion']);
            $status = intval($_POST['listStatus']);

            if ($idRol == 0) {
                $request_rol = $this->model->insertRol($nombre, $descripcion, $status);
                $option = 1;
            } else {
                $request_rol = $this->model->updateRol($idRol, $nombre, $descripcion, $status);
                $option = 2;
            }

            if (intval($request_rol) > 0) {
                $arrResponse = ($option == 1) 
                    ? ['success' => true, 'message' => 'Rol guardado correctamente.'] 
                    : ['success' => true, 'message' => 'Rol actualizado correctamente.'];
            } else if ($request_rol == 'exist') {
                $arrResponse = ['success' => false, 'message' => '¡Atención! El rol ya existe.'];
            } else {
                $arrResponse = ['success' => false, 'message' => 'No es posible almacenar los datos.'];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function delRol() {
        if ($_POST) {
            $idRol = intval($_POST['idRol']);
            $requestDelete = $this->model->deleteRol($idRol);
            if ($requestDelete == 'in_use') {
                $arrResponse = ['success' => false, 'message' => 'No se puede eliminar. El rol está asignado a uno o más usuarios.'];
            } else if ($requestDelete) {
                $arrResponse = ['success' => true, 'message' => 'Se ha eliminado el rol.'];
            } else {
                $arrResponse = ['success' => false, 'message' => 'Error al eliminar el rol.'];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }
	/* METODOS PARA RECUPRAR INFO DE USUARIOS */
	public function recuperar() {
		// Opcional: Validar que solo administradores puedan ver esta página
        // if ($_SESSION['userData']['rol_id'] != 1) {
        //     header("Location:".base_url().'home');
        //     exit();
        // }

		$data = [
			'page_tag' => "Recuperación de Cuentas",
			'page_title' => "Solicitudes de Recuperación",
			'page_name' => "usuarios",
			'page_link' => "recuperar",
			'page_functions' => "function.user.js"
		];
		$this->views->getViews($this, "recuperar", $data);
	}

	public function getPendingRequests() {
        $requests = $this->model->getPendingRecoveryRequests();
        if (empty($requests)) {
            $response = ['success' => true, 'data' => []];
        } else {
            // Desencriptar la contraseña para cada solicitud antes de enviarla a la vista.
            foreach ($requests as &$request) { // Usar '&' para modificar el array directamente
                $request['usuario_password'] = decryption($request['usuario_password']);
            }
            $response = ['success' => true, 'data' => $requests];
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function resolverSolicitud() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                $this->model->deleteRecoveryRequest($id);
                echo json_encode(['success' => true, 'message' => 'Solicitud resuelta.']);
            }
        }
        die();
    }
}