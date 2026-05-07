<?php
// Retorna la ruta del proyecto
function base_url() {
    return defined('BASE_URL') ? BASE_URL : '';
}

function head($data = "") {
    $view_header = VIEWS . "Modules/header.php";
    if (file_exists($view_header)) {
        require_once $view_header;
    }
}

function footer($data = "") {
    $view_footer = VIEWS . "Modules/footer.php";
    if (file_exists($view_footer)) {
        require_once $view_footer;
    }
}

// Muestra información formateada
function dep($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

function encryption($string) {
    if (empty($string)) return '';
    
    $output = false;
    $key = hash('sha256', SECRET_KEY);
    $iv = substr(hash('sha256', SECRET_IV), 0, 16);
    $output = openssl_encrypt($string, METHOD, $key, 0, $iv);
    return $output ? base64_encode($output) : '';
}

function decryption($string) {
    if (empty($string)) return '';
    
    $key = hash('sha256', SECRET_KEY);
    $iv = substr(hash('sha256', SECRET_IV), 0, 16);
    $output = openssl_decrypt(base64_decode($string), METHOD, $key, 0, $iv);
    return $output ?: '';
}

function formatear_timestamp($fecha) {
    if (empty($fecha)) return '';
    
    $timestamp = is_numeric($fecha) ? $fecha : strtotime($fecha);
    if ($timestamp === false) return '';
    
    $dias = ["Dom", "Lun", "Mar", "Mie", "Jue", "Vie", "Sab"];
    $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", 
              "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    
    $dia_semana = $dias[date('w', $timestamp)];
    $dia = date('d', $timestamp);
    $mes = $meses[date('n', $timestamp) - 1];
    $hora = date('G:i a', $timestamp);
    
    return "{$dia_semana}, {$dia} de {$mes} a las {$hora}";
}

function formatear_fecha($fecha) {
    if (empty($fecha)) return '';
    
    $timestamp = strtotime($fecha);
    if ($timestamp === false) return '';
    
    $dias = ["Lun", "Mar", "Mie", "Jue", "Vie", "Sab", "Dom"];
    $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", 
              "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    
    $dia_semana = $dias[date('N', $timestamp) - 1];
    $dia = date('d', $timestamp);
    $mes = $meses[date('n', $timestamp) - 1];
    $ano = date('Y', $timestamp);
    
    return "{$dia_semana}, {$dia} de {$mes} · {$ano}";
}

function sessionUser(int $idUser) {
    if ($idUser <= 0) return null;
    
    $modelPath = "system/app/Models/LoginModel.php";
    if (file_exists($modelPath)) {
        require_once $modelPath;
        $objLogin = new LoginModel();
        return $objLogin->sessionLogin($idUser);
    }
    return null;
}

function time_ago($timestamp) {
    $current_time = time();
    $time_diff = $current_time - $timestamp;
    $seconds = $time_diff;
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);

    if ($seconds <= 60) {
        return "justo ahora";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "hace 1 minuto" : "hace $minutes minutos";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "hace 1 hora" : "hace $hours horas";
    } else if ($days <= 7) {
        return ($days == 1) ? "hace 1 día" : "hace $days días";
    } else if ($weeks <= 4.3) { // 4.3 semanas por mes
        return ($weeks == 1) ? "hace 1 semana" : "hace $weeks semanas";
    } else if ($months <= 12) {
        return ($months == 1) ? "hace 1 mes" : "hace $months meses";
    } else {
        return ($years == 1) ? "hace 1 año" : "hace $years años";
    }
}


function getActiveSession(int $intIdUser) {
    if ($intIdUser <= 0) return null;
    
    $modelPath = "system/app/Models/LoginModel.php";
    if (file_exists($modelPath)) {
        require_once $modelPath;
        $objSession = new LoginModel();
        return $objSession->getActiveSession($intIdUser);
    }
    return null;
}

function validateSessionDB(string $idSesion, int $intIdUser) {
    if (empty($idSesion) || $intIdUser <= 0) return false;
    
    $modelPath = "system/app/Models/LoginModel.php";
    if (file_exists($modelPath)) {
        require_once $modelPath;
        $objValidar = new LoginModel();
        return (bool) $objValidar->validateSessionDB($idSesion, $intIdUser);
    }
    return false;
}

function deleteSession(string $idSession = null) {
    if ($idSession) {
        $modelPath = "system/app/Models/LoginModel.php";
        if (file_exists($modelPath)) {
            require_once $modelPath;
            $objDestroy = new LoginModel();
            $objDestroy->deleteSession($idSession);
        }
    }
    
    destroySession();
}

function destroySession() {
    $_SESSION = [];
    
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], 
            $params['domain'],
            $params['secure'], 
            $params['httponly']
        );
    }
    
    session_destroy();
}

function strClean($strCadena) {
    if (empty($strCadena)) return '';
    
    // Limpieza básica
    $string = trim($strCadena);
    $string = stripslashes($string);
    $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    
    // Patrones de inyección SQL
    $patterns = [
        '/<script.*?>.*?<\/script>/is',
        '/SELECT.*?FROM/i',
        '/DELETE.*?FROM/i',
        '/INSERT INTO/i',
        '/DROP TABLE/i',
        '/UPDATE.*?SET/i',
        '/OR \'1\'=\'1\'/i',
        '/OR "1"="1"/i',
        '/--/',
        '/#/',
        '/\/\*/',
        '/\*\//',
        '/UNION.*?SELECT/i'
    ];
    
    $string = preg_replace($patterns, '', $string);
    
    // Caracteres peligrosos
    $string = str_replace(['^', '[', ']', '==', ';'], '', $string);
    
    return $string;
}

function passGenerator($length = 12) {
    if ($length < 8) $length = 8;
    
    $chars = [
        'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'abcdefghijklmnopqrstuvwxyz',
        '0123456789',
        '!@#$%^&*()_+-=[]{}|;:,.<>?'
    ];
    
    $password = '';
    
    // Asegurar al menos un carácter de cada tipo
    foreach ($chars as $charSet) {
        $password .= $charSet[random_int(0, strlen($charSet) - 1)];
    }
    
    // Completar con caracteres aleatorios
    $allChars = implode('', $chars);
    for ($i = strlen($password); $i < $length; $i++) {
        $password .= $allChars[random_int(0, strlen($allChars) - 1)];
    }
    
    // Mezclar la contraseña
    return str_shuffle($password);
}

function codGenerator($length = 6) {
    if ($length < 4) $length = 4;
    
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, strlen($characters) - 1)];
    }
    
    return $code;
}

function token() {
    return sprintf('%s-%s-%s-%s',
        bin2hex(random_bytes(4)),
        bin2hex(random_bytes(4)),
        bin2hex(random_bytes(4)),
        bin2hex(random_bytes(4))
    );
}

function versql($sql, $arrData) {
    $sqlDebug = $sql;
    
    foreach ($arrData as $valor) {
        $valorFormateado = is_numeric($valor) ? $valor : "'" . addslashes($valor) . "'";
        $sqlDebug = preg_replace('/\?/', $valorFormateado, $sqlDebug, 1);
    }
    
    error_log("SQL DEBUG: " . $sqlDebug);
    
    if (defined('DEBUG') && DEBUG) {
        echo "<pre style='background: #f4f4f4; padding: 10px; border: 1px solid #ccc;'>SQL: " . htmlspecialchars($sqlDebug) . "</pre>";
    }
}

function validarCaracteres($name) {
    if (empty($name)) return '';
    
    $caracteresPermitidos = "0123456789_-.@$()={}[]° abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZáéíóúÁÉÍÓÚñÑ";
    $resultado = '';
    
    $caracteresArray = preg_split('//u', $name, -1, PREG_SPLIT_NO_EMPTY);
    $permitidosArray = preg_split('//u', $caracteresPermitidos, -1, PREG_SPLIT_NO_EMPTY);
    
    foreach ($caracteresArray as $caracter) {
        if (in_array($caracter, $permitidosArray)) {
            $resultado .= $caracter;
        }
    }
    
    return $resultado;
}

// Función para sanear nombres de archivo
function sanitize_filename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    return trim($filename, '_');
}

// Función para verificar si es una petición AJAX
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Función para redireccionar
function redirect($url, $statusCode = 303) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

function cargar_menu_dinamico($usuarioNick, $data = []) {
    // Asegurarse de que el modelo está disponible
    $modelPath = "system/app/Models/MenuModel.php";
    if (!class_exists('MenuModel')) {
        if (file_exists($modelPath)) {
            require_once $modelPath;
        } else {
            echo '<div class="alert alert-danger">Error: No se pudo cargar el modelo de menú.</div>';
            return;
        }
    }

    $currentPageName = $data['page_name'] ?? '';
    $currentPageLink = $data['page_link'] ?? '';

    $menuModel = new MenuModel();
    $menuData = $menuModel->obtenerMenuUsuario($usuarioNick);

    // Organizar los datos en una estructura jerárquica
    $menuEstructura = [];
    foreach ($menuData as $item) {
        $menuId = $item['menu_id'];

        if (!isset($menuEstructura[$menuId])) {
            $menuEstructura[$menuId] = [
                'menu_id' => $item['menu_id'],
                'menu_nombre' => $item['menu_nombre'],
                'menu_icono' => $item['menu_icono'],
                'menu_es_desplegable' => $item['menu_tiene_submenu'],
                'menu_link' => $item['menu_pagina'], // Usar menu_link
                'submenus' => []
            ];
        }

        if (!empty($item['submenu_id']) && !empty($item['submenu_nombre'])) {
            $menuEstructura[$menuId]['submenus'][] = [
                'submenu_id' => $item['submenu_id'],
                'submenu_nombre' => $item['submenu_nombre'],
                'submenu_pagina' => $item['submenu_pagina'],
                'submenu_url' => $item['submenu_url']
            ];
        }
    }

    // Iniciar la construcción del menú con la estructura de AdminLTE
    echo '<nav class="mt-2">';
    echo '<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">';

    // --- Enlaces básicos (siempre visibles) ---
    $isHomeActive = ($currentPageName === 'home') ? 'active' : '';
    // Inicio (Home)
    echo '<li class="nav-item">
            <a href="' . base_url() . 'home" class="nav-link ' . $isHomeActive . '">
                <i class="nav-icon fas fa-home"></i>
                <p>Inicio</p>
            </a>
          </li>';

    $isProfileActive = ($currentPageName === 'perfil') ? 'active' : '';
    // Perfil
    echo '<li class="nav-item">
            <a href="' . base_url() . 'user/perfil" class="nav-link ' . $isProfileActive . '">
                <i class="nav-icon fas fa-user"></i>
                <p>Perfil</p>
            </a>
          </li>';

    // --- Menús personalizados desde la base de datos ---
    if (empty($menuEstructura)) {
        echo '<li class="nav-header">SIN PERMISOS</li>';
        echo '<li class="nav-item"><a href="#" class="nav-link"><i class="nav-icon fas fa-exclamation-triangle text-warning"></i><p>No tiene menús asignados</p></a></li>';
    } else {
        foreach ($menuEstructura as $menu) {
            $tieneSubmenus = !empty($menu['submenus']) && $menu['menu_es_desplegable'];
            $claseMenu = $tieneSubmenus ? 'nav-item has-treeview' : 'nav-item';
            $claseEnlaceMenu = 'nav-link';
            $isParentActive = false;

            if ($tieneSubmenus) {
                // Verificar si algún submenú de este menú está activo
                foreach ($menu['submenus'] as $submenu) {
                    if ($submenu['submenu_pagina'] === $currentPageLink) {
                        $isParentActive = true;
                        break;
                    }
                }
                if ($isParentActive) {
                    $claseMenu .= ' menu-open';
                    $claseEnlaceMenu .= ' active';
                }

                echo '<li class="' . $claseMenu . '">';
                // Menú con submenús
                echo '<a href="#" class="' . $claseEnlaceMenu . '">
                        <i class="nav-icon ' . $menu['menu_icono'] . '"></i>
                        <p>
                            ' . $menu['menu_nombre'] . '
                            <i class="right fas fa-angle-left"></i>
                        </p>
                      </a>';
                echo '<ul class="nav nav-treeview">';
                foreach ($menu['submenus'] as $submenu) {
                    $claseSubmenu = ($submenu['submenu_pagina'] === $currentPageLink) ? 'active' : '';
                    $url = $submenu['submenu_url'] ? base_url() . $submenu['submenu_url'] : '#';
                    echo '<li class="nav-item">
                            <a href="' . $url . '" class="nav-link ' . $claseSubmenu . '" data-page="' . $submenu['submenu_pagina'] . '">
                               <i class="far fa-circle nav-icon"></i>
                               <p>' . $submenu['submenu_nombre'] . '</p>
                            </a>
                          </li>';
                }
                echo '</ul>';
            } else {
                // Menú sin submenús
                if ($menu['menu_link'] === $currentPageName) {
                    $claseEnlaceMenu .= ' active';
                }
                echo '<li class="' . $claseMenu . '">';
                // Menú sin submenús
                $enlace = $menu['menu_link'] ? base_url() . $menu['menu_link'] : '#';
                echo '<a href="' . $enlace . '" class="' . $claseEnlaceMenu . '" data-page="' . $menu['menu_link'] . '">
                        <i class="nav-icon ' . $menu['menu_icono'] . '"></i>
                        <p>' . $menu['menu_nombre'] . '</p>
                      </a>';
            }

            echo '</li>';
        }
    }

    // --- Cerrar sesión ---
    /*
    echo '<li class="nav-header">CUENTA</li>';
    echo '<li class="nav-item">
            <a href="' . base_url() . 'logout" class="nav-link">
                <i class="nav-icon fas fa-sign-out-alt text-danger"></i>
                <p class="text">Cerrar Sesión</p>
            </a>
          </li>';
    */
    echo '</ul></nav>';
}


//aqui la funcion para historial auditoria
// Función para registrar acciones de auditoría
function log_audit_action(string $action_type, string $module, string $description, ?int $reference_id = null): bool {
    // Asegurarse de que la sesión esté iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $usuario_id = $_SESSION['idUser'] ?? null;
    if (!$usuario_id) {
        error_log("Intento de log_audit_action sin usuario_id en sesión. Acción: $action_type, Módulo: $module");
        return false;
    }

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    // Cargar el modelo de auditoría
    $modelPath = "system/app/Models/AuditModel.php";
    // Asegurarse de que el archivo del modelo exista y cargarlo si no está ya cargado
    if (!class_exists('AuditModel')) {
        if (file_exists($modelPath)) {
            require_once $modelPath;
        } else {
            error_log("Error: No se pudo cargar el modelo de auditoría en log_audit_action. Ruta: " . $modelPath);
            return false;
        }
    }

    try {
        $auditModel = new AuditModel();
        return $auditModel->logAction($usuario_id, $action_type, $module, $description, $reference_id, $ip_address, $user_agent);
    } catch (Exception $e) {
        error_log("Error al registrar acción de auditoría en el modelo: " . $e->getMessage());
        return false;
    }
}

// Función para registrar acciones de auditoría desde el frontend (AJAX)
function log_frontend_action(string $action_type, string $module, string $description, ?int $reference_id = null): bool {
    // Reutiliza la función log_audit_action, ya que la lógica de registro es la misma.
    // La distinción 'frontend' es más sobre el origen de la llamada que la lógica de registro en sí.
    return log_audit_action($action_type, $module, $description, $reference_id);
}
