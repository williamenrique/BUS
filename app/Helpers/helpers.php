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
        return ASSETS_URL . ltrim($path, '/');
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
     *
     * Los hashes modernos SIEMPRE comienzan con "$" (ej: $2y$, $argon2id$)
     * El AES+base64 produce base64 estándar (A-Z, a-z, 0-9, +, /, =)
     * por lo que NUNCA empezará con "$".
     */
    function is_legacy_password(string $stored): bool
    {
        if ($stored === '') return false;
        // Si NO empieza con $, y tiene pinta base64, es legacy.
        return $stored[0] !== '$';
    }
}

if (!function_exists('hash_password')) {
    /**
     * Genera hash seguro (bcrypt) para nuevas contraseñas.
     */
    function hash_password(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 10]);
    }
}

if (!function_exists('verify_password')) {
    /**
     * Verifica una contraseña contra el valor almacenado.
     * Soporta bcrypt moderno Y legacy AES.
     *
     * @return array{ok: bool, needs_rehash: bool}
     */
    function verify_password(string $plain, string $stored): array
    {
        // 1) Intento moderno (bcrypt/argon)
        if (!is_legacy_password($stored)) {
            $ok = password_verify($plain, $stored);
            return [
                'ok'           => $ok,
                'needs_rehash' => $ok && password_needs_rehash($stored, PASSWORD_BCRYPT, ['cost' => 10]),
            ];
        }

        // 2) Intento legacy (AES)
        $decrypted = legacy_decryption($stored);
        $ok = $decrypted !== '' && hash_equals($decrypted, $plain);

        return [
            'ok'           => $ok,
            'needs_rehash' => $ok, // si entró con legacy, hay que rehashear YA
        ];
    }
}

if (!function_exists('icon')) {
    /**
     * Normaliza un icono (acepta formato FA viejo o Lucide nuevo)
     * y devuelve el nombre Lucide a usar.
     */
    function icon(?string $raw, string $fallback = 'circle'): string
    {
        if (empty($raw)) return $fallback;

        // 1) Si ya es un nombre Lucide válido (solo letras y guiones)
        if (preg_match('/^[a-z][a-z0-9-]*$/', $raw)) {
            return $raw;
        }

        // 2) Si es formato FontAwesome (contiene "fa-" o espacios)
        // Mapeamos a su equivalente Lucide
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

        // Buscar coincidencia dentro de la cadena (ej: "fas fa-gas-pump" → "fuel")
        foreach ($map as $fa => $lucide) {
            if (str_contains($raw, $fa)) {
                return $lucide;
            }
        }

        // 3) Fallback
        return $fallback;
    }
}

if (!function_exists('icon_tag')) {
    /**
     * Genera el HTML de un icono Lucide listo para renderizar.
     * Ejemplo: <?= icon_tag($item['menu_icono'], 'menu', 'sidebar-icon') ?>
     */
    function icon_tag(?string $raw, string $fallback = 'circle', string $class = ''): string
    {
        $name  = icon($raw, $fallback);
        $class = $class ? ' class="' . e($class) . '"' : '';
        return '<i data-lucide="' . e($name) . '"' . $class . '></i>';
    }
}