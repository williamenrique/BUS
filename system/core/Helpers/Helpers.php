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
    } else if ($weeks <= 4.3) {
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
    
    $string = trim($strCadena);
    $string = stripslashes($string);
    $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    
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
    
    foreach ($chars as $charSet) {
        $password .= $charSet[random_int(0, strlen($charSet) - 1)];
    }
    
    $allChars = implode('', $chars);
    for ($i = strlen($password); $i < $length; $i++) {
        $password .= $allChars[random_int(0, strlen($allChars) - 1)];
    }
    
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

function sanitize_filename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    return trim($filename, '_');
}

function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function redirect($url, $statusCode = 303) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * =====================================================================
 * MULTI-INSTITUCIÓN: Helpers
 * =====================================================================
 */

/**
 * Determina la institución del usuario en sesión.
 * 
 * @return int
 *   0 = Admin/Sistema (puede ver todas las instituciones)
 *   1 = SSLMTY
 *   2 = Taller
 */
function getUserInstitutionId() {
    if (empty($_SESSION['userData'])) {
        return 1;
    }
    
    $departamentoId = intval($_SESSION['userData']['usuario_departamento_id'] ?? 0);
    $departamentoNombre = strtoupper($_SESSION['userData']['departamento_nombre'] ?? '');
    $rolNombre = strtoupper($_SESSION['userData']['rol_nombre'] ?? '');
    
    // Admin/Sistema: puede ver todas las instituciones
    if ($departamentoId === 1 || $departamentoNombre === 'SISTEMA' || $departamentoNombre === 'SISTEMAS') {
        return 0;
    }
    
    // Taller: institución 2
    if ($departamentoId === 8 || $departamentoNombre === 'TALLER') {
        return 2;
    }
    
    // Todos los demás departamentos: SSLMTY (institución 1)
    return 1;
}

/**
 * Determina si el usuario en sesión es administrador (Sistema).
 * @return bool
 */
function isAdminUser() {
    return getUserInstitutionId() === 0;
}

/**
 * Fuerza la institución activa según el usuario en sesión.
 * Si el usuario es admin, respeta el id_institucion que venga por parámetro.
 * Si no lo es, fuerza la institución del usuario.
 * 
 * @param int $idInstitucionSolicitada El id que viene por URL/POST
 * @return int El id de institución a usar
 */
function forceUserInstitution(int $idInstitucionSolicitada = 1) {
    $userInstitucion = getUserInstitutionId();
    
    // Admin: respeta lo que se solicite
    if ($userInstitucion === 0) {
        return $idInstitucionSolicitada;
    }
    
    // No admin: fuerza su institución
    return $userInstitucion;
}

/**
 * =====================================================================
 * RENDERIZADO DEL MENÚ LATERAL DINÁMICO
 * =====================================================================
 * Este es el corazón del nuevo sistema. Recibe el nick del usuario y 
 * la URL actual, resuelve qué menú debe estar activo, y renderiza el
 * árbol completo (menús, submenús, sub-submenús, etc.) usando AdminLTE.
 * =====================================================================
 */
function cargar_menu_dinamico($usuarioNick, $data = []) {
    // 1. Cargar el modelo
    $modelPath = "system/app/Models/MenuModel.php";
    if (!class_exists('MenuModel')) {
        if (file_exists($modelPath)) {
            require_once $modelPath;
        } else {
            echo '<div class="alert alert-danger">Error: No se pudo cargar el modelo de menú.</div>';
            return;
        }
    }

    // 2. Obtener la URL actual
    $urlActual = trim($_GET['url'] ?? '', '/');
    if (empty($urlActual)) {
        $urlActual = 'home/home';
    }

    // 3. Obtener los menús asignados al usuario
    $menuModel = new MenuModel();
    $menusPlanos = $menuModel->obtenerMenuUsuario($usuarioNick);

    if (empty($menusPlanos)) {
        echo '<nav class="mt-2">';
        echo '<ul class="nav nav-pills nav-sidebar flex-column">';
        echo '<li class="nav-header">SIN PERMISOS</li>';
        echo '<li class="nav-item"><a href="#" class="nav-link"><i class="nav-icon fas fa-exclamation-triangle text-warning"></i><p>No tiene menús asignados</p></a></li>';
        echo '</ul></nav>';
        return;
    }

    // 4. Recolectar los IDs de menús permitidos
    $menuIdsPermitidos = array_column($menusPlanos, 'menu_id');

    // 5. Resolver qué menú debe estar activo (por patrones de URL)
    $menuActivoId = $menuModel->resolverMenuActivo($urlActual, $menuIdsPermitidos);

    // 6. Construir el árbol desde la lista plana
    $arbol = construir_arbol_menu($menusPlanos, null);

    // 7. Marcar como activos todos los ancestros del menú activo
    $menusActivos = [];
    $menusAbiertos = [];
    if ($menuActivoId) {
        $menusActivos[$menuActivoId] = true;
        $menuActual = buscar_menu_por_id($menusPlanos, $menuActivoId);
        while ($menuActual && !empty($menuActual['menu_padre_id'])) {
            $menusActivos[$menuActual['menu_padre_id']] = true;
            $menusAbiertos[$menuActual['menu_padre_id']] = true;
            $menuActual = buscar_menu_por_id($menusPlanos, $menuActual['menu_padre_id']);
        }
    }

    // 8. Renderizar
    echo '<nav class="mt-2">';
    echo '<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">';

    // --- Enlaces fijos (Inicio, Perfil) ---
    $isHomeActive = ($urlActual === 'home/home' || $urlActual === 'home') ? 'active' : '';
    echo '<li class="nav-item">
            <a href="' . base_url() . 'home" class="nav-link ' . $isHomeActive . '">
                <i class="nav-icon fas fa-home"></i>
                <p>Inicio</p>
            </a>
          </li>';

    $isProfileActive = (strpos($urlActual, 'user/perfil') === 0) ? 'active' : '';
    echo '<li class="nav-item">
            <a href="' . base_url() . 'user/perfil" class="nav-link ' . $isProfileActive . '">
                <i class="nav-icon fas fa-user"></i>
                <p>Perfil</p>
            </a>
          </li>';

    // --- Renderizar árbol recursivo ---
    renderizar_nodos_menu($arbol, $menusActivos, $menusAbiertos, 0);

    echo '</ul></nav>';
}

/**
 * Construye un árbol jerárquico a partir de una lista plana de menús.
 * @param array $items Lista plana con menu_id, menu_padre_id, etc.
 * @param int|null $padreId ID del padre cuyos hijos estamos buscando.
 * @return array Árbol jerárquico.
 */
function construir_arbol_menu($items, $padreId = null) {
    $rama = [];
    foreach ($items as $item) {
        if ($item['menu_padre_id'] == $padreId) {
            $hijos = construir_arbol_menu($items, $item['menu_id']);
            if (!empty($hijos)) {
                $item['hijos'] = $hijos;
            }
            $rama[] = $item;
        }
    }
    return $rama;
}

/**
 * Busca un menú por ID dentro de una lista plana.
 */
function buscar_menu_por_id($items, $menuId) {
    foreach ($items as $item) {
        if ($item['menu_id'] == $menuId) return $item;
    }
    return null;
}

/**
 * Renderiza recursivamente un árbol de menús en formato AdminLTE.
 * @param array $nodos Lista de nodos del árbol.
 * @param array $activos Mapa de menu_id => true para los activos.
 * @param array $abiertos Mapa de menu_id => true para los que deben desplegarse.
 * @param int $nivel Nivel actual de profundidad (0 = raíz).
 */
function renderizar_nodos_menu($nodos, $activos, $abiertos, $nivel = 0) {
    foreach ($nodos as $nodo) {
        $tieneHijos = !empty($nodo['hijos']);
        $menuId = $nodo['menu_id'];
        $estaActivo = !empty($activos[$menuId]);
        $estaAbierto = !empty($abiertos[$menuId]);
        
        // Construir clases del <li>
        $clasesLi = ['nav-item'];
        if ($tieneHijos) $clasesLi[] = 'has-treeview';
        if ($estaAbierto) $clasesLi[] = 'menu-open';
        
        // Construir clases del <a>
        $clasesA = ['nav-link'];
        if ($estaActivo) $clasesA[] = 'active';
        
        // Icono
        $icono = !empty($nodo['menu_icono']) ? $nodo['menu_icono'] : 'far fa-circle';
        
        // URL
        $url = !empty($nodo['menu_ruta']) ? base_url() . $nodo['menu_ruta'] : '#';
        
        echo '<li class="' . implode(' ', $clasesLi) . '">';
        
        if ($tieneHijos) {
            // Menú con hijos → no navega, solo despliega
            echo '<a href="#" class="' . implode(' ', $clasesA) . '">';
            echo '<i class="nav-icon ' . $icono . '"></i>';
            echo '<p>' . htmlspecialchars($nodo['menu_nombre']) . '<i class="right fas fa-angle-left"></i></p>';
            echo '</a>';
            
            // Renderizar hijos
            echo '<ul class="nav nav-treeview">';
            renderizar_nodos_menu($nodo['hijos'], $activos, $abiertos, $nivel + 1);
            echo '</ul>';
        } else {
            // Menú hoja → navega
            echo '<a href="' . $url . '" class="' . implode(' ', $clasesA) . '" data-page="' . htmlspecialchars($nodo['menu_ruta'] ?? '') . '">';
            echo '<i class="nav-icon ' . $icono . '"></i>';
            echo '<p>' . htmlspecialchars($nodo['menu_nombre']) . '</p>';
            echo '</a>';
        }
        
        echo '</li>';
    }
}