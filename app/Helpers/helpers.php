<?php

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        return BASE_URL . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path = ''): string
    {
        // Nivel 1: todos los assets viven en /assets/
        return ASSETS_URL . 'assets/' . ltrim($path, '/');
    }
}

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        header('Location: ' . base_url($path));
        exit;
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return isset($_SESSION['user']['id']);
    }
}

if (!function_exists('has_role')) {
    function has_role(...$roles): bool
    {
        $user = auth_user();
        if (!$user) return false;
        return in_array($user['rol_nombre'] ?? '', $roles, true);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(?string $token): bool
    {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = ''): string
    {
        return e($_SESSION['old'][$key] ?? $default);
    }
}

if (!function_exists('flash_set')) {
    function flash_set(string $key, $value): void
    {
        $_SESSION['flash'][$key] = $value;
    }
}

if (!function_exists('flash_get')) {
    function flash_get(string $key, $default = null)
    {
        $val = $_SESSION['flash'][$key] ?? $default;
        unset($_SESSION['flash'][$key]);
        return $val;
    }
}

// ============================================
// Encriptación legacy (compatibilidad con sistema viejo)
// ============================================
if (!function_exists('legacy_encryption')) {
    /**
     * Encripta usando AES-256-CBC (formato antiguo).
     * Solo para compatibilidad. NO usar para nuevas contraseñas.
     */
    function legacy_encryption(string $string): string
    {
        if ($string === '') return '';

        $key = hash('sha256', LEGACY_SECRET_KEY);
        $iv  = substr(hash('sha256', LEGACY_SECRET_IV), 0, 16);

        $output = openssl_encrypt($string, LEGACY_METHOD, $key, 0, $iv);
        return $output ? base64_encode($output) : '';
    }
}

if (!function_exists('legacy_decryption')) {
    /**
     * Desencripta contraseñas del sistema viejo.
     * Devuelve '' si no puede.
     */
    function legacy_decryption(string $string): string
    {
        if ($string === '') return '';

        $key = hash('sha256', LEGACY_SECRET_KEY);
        $iv  = substr(hash('sha256', LEGACY_SECRET_IV), 0, 16);

        $output = openssl_decrypt(base64_decode($string), LEGACY_METHOD, $key, 0, $iv);
        return $output ?: '';
    }
}

if (!function_exists('is_legacy_password')) {
    /**
     * Detecta si una contraseña almacenada es legacy (AES+base64)
     * o moderna (bcrypt/argon2).
     */
    function is_legacy_password(string $stored): bool
    {
        if ($stored === '') return false;
        return $stored[0] !== '$';
    }
}

if (!function_exists('hash_password')) {
    function hash_password(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 10]);
    }
}

if (!function_exists('verify_password')) {
    /**
     * Verifica una contraseña contra el valor almacenado.
     * @return array{ok: bool, needs_rehash: bool}
     */
    function verify_password(string $plain, string $stored): array
    {
        if (!is_legacy_password($stored)) {
            $ok = password_verify($plain, $stored);
            return [
                'ok'           => $ok,
                'needs_rehash' => $ok && password_needs_rehash($stored, PASSWORD_BCRYPT, ['cost' => 10]),
            ];
        }

        $decrypted = legacy_decryption($stored);
        $ok = $decrypted !== '' && hash_equals($decrypted, $plain);

        return [
            'ok'           => $ok,
            'needs_rehash' => $ok,
        ];
    }
}

if (!function_exists('icon')) {
    function icon(?string $raw, string $fallback = 'circle'): string
    {
        if (empty($raw)) return $fallback;

        if (preg_match('/^[a-z][a-z0-9-]*$/', $raw)) {
            return $raw;
        }

        $map = [
            'fa-home'              => 'home',
            'fa-house'             => 'home',
            'fa-bus'               => 'bus',
            'fa-box'               => 'package',
            'fa-box-archive'       => 'archive',
            'fa-users'             => 'users',
            'fa-user'              => 'user',
            'fa-user-plus'         => 'user-plus',
            'fa-user-shield'       => 'shield-user',
            'fa-gas-pump'          => 'fuel',
            'fa-list'              => 'list',
            'fa-bars'              => 'menu',
            'fa-store'             => 'store',
            'fa-circle'            => 'circle',
            'fa-shopping-cart'     => 'shopping-cart',
            'fa-cart-shopping'     => 'shopping-cart',
            'fa-cash-register'     => 'receipt',
            'fa-receipt'           => 'receipt',
            'fa-truck'             => 'truck',
            'fa-truck-fast'        => 'truck',
            'fa-oil-can'           => 'droplet',
            'fa-droplet'           => 'droplet',
            'fa-sitemap'           => 'network',
            'fa-network-wired'     => 'network',
            'fa-key'               => 'key',
            'fa-warehouse'         => 'warehouse',
            'fa-clock-rotate-left' => 'history',
            'fa-history'           => 'history',
            'fa-chart-line'        => 'line-chart',
            'fa-chart-column'      => 'bar-chart',
            'fa-line-chart'        => 'line-chart',
            'fa-pen-to-square'     => 'pencil',
            'fa-pencil'            => 'pencil',
            'fa-edit'              => 'pencil',
            'fa-cog'               => 'settings',
            'fa-gear'              => 'settings',
            'fa-sign-out'          => 'log-out',
            'fa-right-from-bracket'=> 'log-out',
            'fa-bell'              => 'bell',
            'fa-search'            => 'search',
            'fa-tachometer-alt'    => 'gauge',
            'fa-gauge'             => 'gauge',
            'fa-car'               => 'car',
            'fa-wrench'            => 'wrench',
            'fa-clipboard-list'    => 'clipboard-list',
            'fa-file-invoice'      => 'file-text',
            'fa-money-bill'        => 'banknote',
            'fa-calendar'          => 'calendar',
            'fa-clock'             => 'clock',
            'fa-check'             => 'check',
            'fa-xmark'             => 'x',
            'fa-plus'              => 'plus',
            'fa-trash'             => 'trash-2',
            'fa-download'          => 'download',
            'fa-upload'            => 'upload',
            'fa-print'             => 'printer',
            'fa-filter'            => 'filter',
            'fa-eye'               => 'eye',
            'fa-map'               => 'map',
            'fa-route'             => 'route',
            'fa-flag'              => 'flag',
            'fa-shield'            => 'shield',
            'fa-building'          => 'building-2',
            'fa-briefcase'         => 'briefcase',
        ];

        foreach ($map as $fa => $lucide) {
            if (str_contains($raw, $fa)) {
                return $lucide;
            }
        }

        return $fallback;
    }
}

if (!function_exists('icon_tag')) {
    function icon_tag(?string $raw, string $fallback = 'circle', string $class = ''): string
    {
        $name  = icon($raw, $fallback);
        $class = $class ? ' class="' . e($class) . '"' : '';
        return '<i data-lucide="' . e($name) . '"' . $class . '></i>';
    }
}

if (!function_exists('normalizar_mayusculas')){
    function normalizar_mayusculas(array $data, array $excepto = [], array $solo = []): array{
        foreach ($data as $key => $val){
            if (!is_string($val)) continue;
            if (!empty($solo) && !in_array($key, $solo, true)) continue;
            if (in_array($key, $excepto, true)) continue;
            $data[$key] = mb_strtoupper(trim($val), 'UTF-8');
        }
        return $data;
    }
}

if (!function_exists('normalizar_email')){
    function normalizar_email(string $email): string{
        $email = trim($email);
        if ($email === '' || !str_contains($email, '@')) return $email;
        [$local, $domain] = explode('@', $email, 2);
        return $local . '@' . strtolower($domain);
    }
}

if (!function_exists('es_admin_sistema')){
    function es_admin_sistema(): bool{
        $user = auth_user();
        if (!$user) return false;
        return (int)($user['departamento_id'] ?? 0) === (int)DEPARTAMENTO_SISTEMA;
    }
}

if (!function_exists('inst_actual')){
    function inst_actual(?int $override = null): int{
        if ($override !== null && $override > 0){
            $_SESSION['inst_activa'] = $override;
            return $override;
        }
        if (!empty($_GET['inst']) && (int)$_GET['inst'] > 0){
            $_SESSION['inst_activa'] = (int)$_GET['inst'];
            return (int)$_GET['inst'];
        }
        if (!empty($_SESSION['inst_activa'])){
            return (int)$_SESSION['inst_activa'];
        }
        return (int)DEFAULT_INSTITUCION;
    }
}

if (!function_exists('inst_url')){
    function inst_url(string $path, ?int $inst = null): string{
        $inst = $inst ?? inst_actual();
        $path = trim($path, '/');
        return base_url($path . '/i/' . $inst);
    }
}

if (!function_exists('inst_nombre_corto')){
    /**
     * Devuelve el nombre completo si cabe en $maxLen.
     * Si es más largo, devuelve solo las iniciales de las palabras
     * significativas (ignorando artículos, preposiciones y "ESTADO").
     *
     * Ejemplos con $maxLen = 28:
     *   "TALLER SSLMTY"                                 → "TALLER SSLMTY"  (13)
     *   "171"                                           → "171"            (3)
     *   "GOBERNACION DEL ESTADO YARACUY"                → "GY"             (30 → iniciales)
     *   "SERVICIO SOCIALISTA DE LOGISTICA, MANTENIMIENTO Y TRANSPORTE DEL ESTADO YARACUY"
     *                                                   → "SSLMTY"         (86 → iniciales)
     */
    function inst_nombre_corto(string $nombre, int $maxLen = 40): string{
        $nombre = trim($nombre);
        if ($nombre === '') return '';

        // Nombres cortos: se devuelven tal cual
        if (mb_strlen($nombre, 'UTF-8') <= $maxLen){
            return $nombre;
        }

        // Palabras que NO aportan inicial (artículos, preposiciones, "ESTADO")
        $ignorar = [
            'de', 'del', 'la', 'las', 'el', 'los',
            'y', 'e', 'o', 'u',
            'a', 'al', 'en', 'con', 'por', 'para',
            'un', 'una', 'unos', 'unas',
            'sin', 'sobre', 'entre',
            'estado', 'estados',
            'the', 'of', 'and',
        ];

        // Limpiar signos, dejar solo letras/números/espacios
        $limpio = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $nombre);
        $partes = preg_split('/\s+/u', $limpio, -1, PREG_SPLIT_NO_EMPTY);

        if (!$partes){
            return mb_substr($nombre, 0, $maxLen, 'UTF-8') . '…';
        }

        $iniciales = '';
        foreach ($partes as $p){
            $low = mb_strtolower($p, 'UTF-8');
            if (in_array($low, $ignorar, true)) continue;
            $iniciales .= mb_strtoupper(mb_substr($p, 0, 1, 'UTF-8'), 'UTF-8');
        }

        // Si todo eran palabras ignorables, usar el nombre crudo recortado
        if ($iniciales === ''){
            return mb_substr($nombre, 0, $maxLen, 'UTF-8') . '…';
        }

        // Si por alguna razón las iniciales aún superan el máximo
        if (mb_strlen($iniciales, 'UTF-8') > $maxLen){
            $iniciales = mb_substr($iniciales, 0, $maxLen, 'UTF-8') . '…';
        }

        return $iniciales;
    }
}