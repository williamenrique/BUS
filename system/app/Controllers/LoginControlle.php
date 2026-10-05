<?php
header('Access-Control-Allow-Origin: *');
class Login extends Controllers{
    private $db;
	public function __construct(){
		// Iniciar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
		//invocar para que se ejecute el metodo de la herencia
		parent::__construct();

	}
	public function login(){
		//invocar la vista con views y usamos getViews y pasamos parametros esta clase y la vista
		//incluimos un arreglo que contendra toda la informacion que se enviara al home
		$data['page_tag'] = "SISTEMA - LOGIN";
		$data['page_title'] = "Login";
		$data['page_name'] = "login";
		$data['page_functions'] = "function.login.js";
        $data['db_error'] = null; // Inicializamos la variable de error
        try {
            // Intentamos instanciar el modelo. Esto activa la conexión
            $this->model = new LoginModel();
            // Verificar si hay error de conexión en el modelo
            if ($this->model->hasConnectionError()) {
                $data['db_error'] = "Error de conexión: " . $this->model->getConnectionError();
            }
        } catch (PDOException $e) {
            // Si hay un error, lo guardamos en una variable para la vista
            $data['db_error'] = "Fallo de conexión: " . $this->getFriendlyErrorMessage($e->getMessage());
        } catch (Exception $e) {
            $data['db_error'] = "Error inesperado: " . $e->getMessage();
        }
        // La vista se carga después de que se ha verificado el estado de la conexión
        $this->views->getViews($this, "login", $data);
    }
    // Método para traducir mensajes de error técnicos a mensajes amigables
    private function getFriendlyErrorMessage($errorMessage) {
        if (strpos($errorMessage, 'Unknown database') !== false) {
            return "La base de datos no fue encontrada. Contacte al administrador.";
        } elseif (strpos($errorMessage, 'Access denied') !== false) {
            return "Credenciales de acceso incorrectas para la base de datos.";
        } elseif (strpos($errorMessage, 'Connection refused') !== false) {
            return "No se puede conectar al servidor de base de datos. Verifique que el servidor esté ejecutándose.";
        } else {
            return "Error de conexión con la base de datos: " . $errorMessage;
        }
    }
    
	public function loginUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método no permitido');
            return;
        }
        try {
             // Verificar conexión primero
            $identifier = strClean(strtolower($_POST['txtUser'] ?? ''));
            $password = $_POST['txtPass'] ?? '';
            if (empty($identifier) || empty($password)) {
                $this->jsonResponse(false, 'Error en datos');
                return;
            }
            $encryptedPass = encryption($password);
            $requestUser = $this->model->loginUser($identifier, $encryptedPass);
            if (empty($requestUser)) {
                $this->jsonResponse(false, 'El usuario o el password es incorrecto');
                return;
            }
            // --- INICIO DE LA MODIFICACIÓN ---
            // Verificar si ya existe una sesión activa para este usuario.
            $activeSession = $this->model->getActiveSession($requestUser['usuario_id'], null);
            if ($activeSession) {
                // Si hay una sesión activa, no iniciamos una nueva.
                // Enviamos un código especial para que el frontend pregunte al usuario si desea forzar el cierre.
                $this->jsonResponse(false, 'Ya existe una sesión activa para este usuario.', 'session_active');
                return;
            }
            // --- FIN DE LA MODIFICACIÓN ---
            if ($requestUser['usuario_status'] != 1) {
                $this->jsonResponse(false, 'El usuario está inactivo');
                return;
            }
            $this->initUserSession($requestUser['usuario_id']);
            $this->jsonResponse(true, 'ok');

        } catch (Exception $e) {
            $this->jsonResponse(false, 'Error en el proceso'. $e);
        }
    }
	public function initUserSession($userId) {
        // Verificar si el usuario ya tiene una sesión activa
        $activeSession = $this->model->getActiveSession($userId,NULL);
        if ($activeSession) {
            // CORRECCIÓN: Si ya existe una sesión activa, simplemente la eliminamos y continuamos.
            // No detenemos el script, para permitir que el nuevo login proceda.
            $this->model->deleteSession($activeSession['session_id']);
        }
        // Crear nueva sesión
        session_regenerate_id(true);
        $_SESSION['idUser'] = $userId;
        $_SESSION['login'] = true;
        $_SESSION['session_id'] = session_id();
        $_SESSION['session_created'] = time();
        $_SESSION['last_activity'] = time();
        $userData = $this->model->sessionLogin($userId);
        // Guardar información de la sesión en la base de datos
        $this->model->saveSessionInfo([
            'session_id' => session_id(),
            'usuario_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'usuario_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
	/*********
	 * forzar la session activa para cerrarla si ya existe 
	 */
    public function forceLogout() {
        // Recibe el userId por POST (puede venir como JSON o FormData)
        $userNick = $_POST['userId'] ?? null;
        if (!$userNick) {
            // Si no viene por POST, intenta obtenerlo por JSON
            $data = json_decode(file_get_contents('php://input'), true);
            $userNick = $data['userId'] ?? null;
        }
        if ($userNick) {
            $activeSession = $this->model->getActiveSession(NULL, $userNick);
            if ($activeSession) {
                $this->model->deleteSession($activeSession['session_id']);
                $this->jsonResponse(true, 'Sesión anterior cerrada. Ahora puedes iniciar sesión.');
            } else {
                $this->jsonResponse(false, 'No se encontró sesión activa para cerrar.');
            }
        } else {
            $this->jsonResponse(false, 'No se recibió el usuario.');
        }
    }

    public function solicitarRecuperacion() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, 'Método no permitido');
            return;
        }

        try {
            $identifier = strClean($_POST['emailOrUser'] ?? '');

            if (empty($identifier)) {
                $this->jsonResponse(false, 'Por favor, ingrese su correo o cédula.');
                return;
            }

            // Buscamos al usuario por su identificador (email o CI)
            $user = $this->model->buscarUsuarioPorIdentificador($identifier);

            // ¡Importante! Por seguridad, siempre devolvemos un mensaje de éxito,
            // exista o no el usuario. Esto evita que alguien pueda adivinar
            // qué correos o cédulas están registrados en el sistema.
            if (!empty($user)) {
                // Si el usuario existe, creamos la solicitud en la BD.
                $this->model->crearSolicitudRecuperacion($user['usuario_id'], $identifier);
                
            }

            // El mensaje es genérico a propósito.
            $this->jsonResponse(true, 'Solicitud enviada. Si sus datos son correctos, un administrador se pondrá en contacto con usted.');

        } catch (Exception $e) {
            // Log del error para depuración interna
            error_log("Error en solicitarRecuperacion: " . $e->getMessage());
            // Mensaje genérico para el usuario
            $this->jsonResponse(false, 'Ocurrió un error al procesar la solicitud. Intente más tarde.');
        }
    }

	public function jsonResponse($status, $msg, $code = null) {
        $response = [
            'status' => $status,
            'msg' => $msg
        ];
        if ($code) {
            $response['code'] = $code;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
	// end class

    
    /**
     * Elimina un directorio de forma recursiva dentro de una carpeta base segura.
     * Esta acción solo está permitida para administradores.
     */
    public function deleteDirectory() {
        $arrResponse = ['success' => false, 'message' => 'Acción no permitida.'];

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido.');
            }

            $relativePath = $_POST['path'] ?? '';
            if (empty($relativePath)) {
                throw new Exception('La ruta del directorio no puede estar vacía.');
            }

            // 2. Medida de seguridad: Definir el directorio base permitido para borrado.
            $baseDir = '';
            // $baseDir = 'storage/';

            // 3. Limpieza y validación de la ruta para evitar Path Traversal.
            // Normaliza la ruta para usar slashes correctos y elimina cualquier '../'
            $cleanPath = str_replace('\\', '/', $relativePath);
            $cleanPath = preg_replace('/\.{2,}\//', '', $cleanPath); // Elimina ../, ../../ etc.
            $cleanPath = trim($cleanPath, '/'); // Quita slashes al inicio y final

            $fullPath = $baseDir . $cleanPath;

            // 4. Verificación final: Asegurarse de que la ruta resuelta sigue dentro del directorio base.
            if (strpos(realpath($fullPath), realpath($baseDir)) !== 0) {
                throw new Exception('Ruta no válida. Intento de acceso fuera del directorio permitido.');
            }

            if (!is_dir($fullPath)) {
                throw new Exception('El directorio no existe: ' . htmlspecialchars($fullPath));
            }

            // 5. Ejecución del borrado recursivo.
            if ($this->recursiveDelete($fullPath)) {
                $arrResponse = ['success' => true, 'message' => 'Directorio eliminado correctamente: ' . htmlspecialchars($fullPath)];
            } else {
                throw new Exception('No se pudo eliminar el directorio. Verifique los permisos.');
            }

        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }

        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Función auxiliar para borrar un directorio y todo su contenido.
     * @param string $dir La ruta del directorio a eliminar.
     * @return bool True si se eliminó, false en caso de error.
     */
    private function recursiveDelete($dir) {
        if (!is_dir($dir)) return false;
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveDelete("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * Obtiene la lista de tablas de la base de datos.
     */
    public function getDatabaseTables() {
        $arrResponse = ['success' => false, 'message' => 'No se pudieron obtener las tablas.'];
        try {
            $tables = $this->model->getTables();
            if (!empty($tables)) {
                $arrResponse = ['success' => true, 'data' => $tables];
            } else {
                $arrResponse['message'] = 'No se encontraron tablas en la base de datos.';
            }
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error en el servidor: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Exporta las tablas seleccionadas a un archivo .sql.
     */
    public function exportTables() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            die();
        }

        try {
            $selectedTables = json_decode($_POST['tables'] ?? '[]', true);
            if (empty($selectedTables)) {
                throw new Exception('No se seleccionaron tablas para exportar.');
            }

            // Medida de seguridad: verificar que las tablas solicitadas realmente existan.
            $allTables = $this->model->getTables();
            $validTables = array_intersect($selectedTables, $allTables);

            if (empty($validTables)) {
                throw new Exception('Ninguna de las tablas seleccionadas es válida.');
            }

            $sqlContent = "-- Respaldo de Base de Datos - Busyaracuy\n";
            $sqlContent .= "-- Generado el: " . date('Y-m-d H:i:s') . "\n";
            $sqlContent .= "-- --------------------------------------------------------\n\n";

            foreach ($validTables as $table) {
                // Estructura de la tabla
                $structure = $this->model->getTableStructure($table);
                if ($structure) {
                    $sqlContent .= "\n--\n-- Estructura de la tabla `{$table}`\n--\n\n";
                    $sqlContent .= "DROP TABLE IF EXISTS `{$table}`;\n";
                    $sqlContent .= $structure['Create Table'] . ";\n\n";
                }

                // Datos de la tabla
                $data = $this->model->getTableData($table);
                if (!empty($data)) {
                    $sqlContent .= "--\n-- Volcado de datos para la tabla `{$table}`\n--\n\n";

                    // --- INICIO DE LA OPTIMIZACIÓN ---
                    // Se agrupan todas las filas en una sola sentencia INSERT
                    $columns = array_keys($data[0]);
                    $sqlContent .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES\n";

                    $valueStrings = [];
                    foreach ($data as $row) {
                        $values = array_map(function($value) {
                            if ($value === null) return 'NULL';
                            // Escapar apóstrofes y barras invertidas
                            return "'" . addslashes($value) . "'";
                        }, array_values($row));
                        $valueStrings[] = "(" . implode(', ', $values) . ")";
                    }
                    $sqlContent .= implode(",\n", $valueStrings) . ";\n\n";
                    // --- FIN DE LA OPTIMIZACIÓN ---
                }
            }

            $filename = "backup_busyaracuy_" . date('Y-m-d') . ".sql";
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($sqlContent));
            echo $sqlContent;

        } catch (Exception $e) {
            http_response_code(500);
            // No podemos enviar JSON porque el frontend espera un archivo,
            // pero podemos registrar el error.
            error_log("Error en exportTables: " . $e->getMessage());
        }
        die();
    }

    /**
     * Elimina las tablas seleccionadas de la base de datos.
     * Incluye una lista de protección para tablas críticas.
     */
    public function deleteTables() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
            die();
        }

        try {
            $selectedTables = json_decode($_POST['tables'] ?? '[]', true);
            if (empty($selectedTables)) {
                throw new Exception('No se seleccionaron tablas para eliminar.');
            }

            // Lista de tablas críticas que NO se pueden eliminar
            $protectedTables = [
                'table_usuarios', 'table_personal', 'table_per_roles', 'table_departamentos',
                'table_men_menu', 'table_men_submenu', 'table_men_usuario_menu', 'table_men_usuario_submenu',
                'table_usuario_sessions'
            ];

            // Filtrar las tablas para eliminar solo las que no están protegidas
            $tablesToDelete = array_diff($selectedTables, $protectedTables);
            $skippedTables = array_intersect($selectedTables, $protectedTables);

            if (empty($tablesToDelete)) {
                $message = 'No se eliminó ninguna tabla. ';
                if (!empty($skippedTables)) {
                    $message .= 'Las tablas seleccionadas (' . implode(', ', $skippedTables) . ') están protegidas.';
                }
                throw new Exception($message);
            }

            // Llamar al método del modelo para eliminar las tablas
            $deletedCount = $this->model->dropTables($tablesToDelete);

            $message = "Se eliminaron {$deletedCount} tabla(s) correctamente.";
            if (!empty($skippedTables)) {
                $message .= " Se omitieron las siguientes tablas protegidas: " . implode(', ', $skippedTables) . ".";
            }

            echo json_encode(['success' => true, 'message' => $message]);

        } catch (Exception $e) {
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        die();
    }

    /**
     * Elimina un archivo específico dentro de una carpeta base segura.
     * Esta acción solo está permitida para administradores.
     */
    public function deleteFile() {
        $arrResponse = ['success' => false, 'message' => 'Acción no permitida.'];

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido.');
            }

            $relativePath = $_POST['path'] ?? '';
            if (empty($relativePath)) {
                throw new Exception('La ruta del archivo no puede estar vacía.');
            }

            $baseDir = '';
            // $baseDir = 'storage/';

            // Limpieza y validación de la ruta para evitar Path Traversal.
            $cleanPath = str_replace('\\', '/', $relativePath);
            $cleanPath = preg_replace('/\.{2,}\//', '', $cleanPath);
            $cleanPath = trim($cleanPath, '/');

            $fullPath = $baseDir . $cleanPath;

            // Verificación final: Asegurarse de que la ruta resuelta sigue dentro del directorio base.
            if (strpos(realpath($fullPath), realpath($baseDir)) !== 0) {
                throw new Exception('Ruta no válida. Intento de acceso fuera del directorio permitido.');
            }

            if (!is_file($fullPath)) {
                throw new Exception('El archivo no existe o no es un archivo: ' . htmlspecialchars($fullPath));
            }

            // Ejecución del borrado.
            if (unlink($fullPath)) {
                $arrResponse = ['success' => true, 'message' => 'Archivo eliminado correctamente: ' . htmlspecialchars($fullPath)];
            } else {
                throw new Exception('No se pudo eliminar el archivo. Verifique los permisos.');
            }

        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }

        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Renombra un archivo o directorio dentro de una carpeta base segura.
     * Esta acción solo está permitida para administradores.
     */
    public function renameFileOrDirectory() {
        $arrResponse = ['success' => false, 'message' => 'Acción no permitida.'];

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido.');
            }

            $oldRelativePath = $_POST['oldPath'] ?? '';
            $newRelativePath = $_POST['newPath'] ?? '';

            if (empty($oldRelativePath) || empty($newRelativePath)) {
                throw new Exception('Ambas rutas (actual y nueva) no pueden estar vacías.');
            }

            // Definimos el directorio base como la raíz del proyecto para la validación.
            $baseDir = $_SERVER['DOCUMENT_ROOT'] . '/Busyaracuy_update/';
            $prohibitedDirs = ['/etc', '/var', '/bin', '/sbin', '/usr', '/boot', '/root', '/sys'];



            // Limpieza y validación de la ruta antigua
            $cleanOldPath = str_replace('\\', '/', $oldRelativePath);
            $cleanOldPath = preg_replace('/\.{2,}\//', '', $cleanOldPath);
            $fullOldPath = $cleanOldPath; // La ruta ya es relativa a la raíz del proyecto

            // Limpieza y validación de la ruta nueva
            $cleanNewPath = str_replace('\\', '/', $newRelativePath);
            $cleanNewPath = preg_replace('/\.{2,}\//', '', $cleanNewPath);
            $fullNewPath = $cleanNewPath; // La ruta ya es relativa a la raíz del proyecto
            
            // Verificación de directorios prohibidos
            foreach ($prohibitedDirs as $dir) {
                if (strpos($fullOldPath, $dir) === 0 || strpos($fullNewPath, $dir) === 0) {
                    throw new Exception('Acceso denegado. No se permite acceder a directorios del sistema.');
                }
            }


            // Verificación de seguridad: Asegurarse de que la ruta real no salga del directorio del proyecto.
            if (strpos(realpath($fullOldPath), realpath($baseDir)) !== 0) {
                throw new Exception('Ruta no válida. Intento de acceso fuera del directorio permitido.');
            }


            if (!file_exists($fullOldPath)) {
                throw new Exception('El archivo o directorio a renombrar no existe: ' . htmlspecialchars($fullOldPath));
            }

            if (rename($fullOldPath, $fullNewPath)) {
                $arrResponse = ['success' => true, 'message' => 'Renombrado correctamente: ' . htmlspecialchars($cleanOldPath) . ' a ' . htmlspecialchars($cleanNewPath)];
            } else {
                throw new Exception('No se pudo renombrar. Verifique los permisos o si el destino ya existe.');
            }

        } catch (Exception $e) {
            $arrResponse['message'] = $e->getMessage();
        }

        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
}