<?php
namespace App\Middleware;

use App\Models\ModelUsuario;

/**
 * MenuGuard · Autorización por asignación de menú.
 *
 * - check(): verifica la ruta actual y bloquea si no hay permiso.
 * - denegar(): responde 403 (HTML bonito o JSON) — público para reutilizar.
 */
class MenuGuard
{
    /**
     * Ejecuta la verificación. Debe llamarse después de AuthGuard::check().
     */
    public static function check(): void
    {
        if (!isset($_SESSION['user']['id'])) return;

        $user   = $_SESSION['user'];
        $userId = (int)($user['id']              ?? 0);
        $deptId = (int)($user['departamento_id'] ?? 0);

        if ($deptId === (int)DEPARTAMENTO_SISTEMA) return;

        $ruta = self::rutaRelativa();
        if ($ruta === '') return;

        try {
            $mUser  = new ModelUsuario();
            $menuId = $mUser->menuIdPorRuta($ruta);
        } catch (\Throwable $e) {
            error_log('MenuGuard::check menuIdPorRuta → ' . $e->getMessage());
            return;
        }

        if ($menuId === null) return;

        $tieneAcceso = false;
        try {
            $tieneAcceso = $mUser->tieneMenuAsignado($userId, $deptId, $menuId);
        } catch (\Throwable $e) {
            error_log('MenuGuard::check tieneMenuAsignado → ' . $e->getMessage());
            return;
        }

        if (!$tieneAcceso) {
            self::denegar();
        }
    }

    /**
     * Devuelve la ruta relativa a BASE_URL, sin query ni barra inicial.
     */
    private static function rutaRelativa(): string
    {
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $base = parse_url(BASE_URL, PHP_URL_PATH) ?: '/';
        if ($base !== '/' && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }

        return trim($path, '/');
    }

    /**
     * Responde 403. JSON si es AJAX, HTML bonito si es vista normal.
     * Público para que otros controladores lo reutilicen.
     */
    public static function denegar(): void
    {
        http_response_code(403);

        $isAjax = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT'])
                && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        );

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'      => false,
                'message' => 'No tienes acceso a esta sección.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
           . '<title>Acceso denegado</title>'
           . '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;'
           . 'background:#f1f5f9;color:#0f172a;display:flex;align-items:center;'
           . 'justify-content:center;min-height:100vh;margin:0;padding:1rem;}'
           . '.card{background:#fff;border-radius:14px;padding:2rem 2.5rem;'
           . 'box-shadow:0 10px 30px rgba(15,23,42,.1);text-align:center;max-width:420px;}'
           . 'h1{margin:0 0 .5rem;font-size:1.25rem;}'
           . 'p{margin:0 0 1.5rem;color:#475569;font-size:.9rem;line-height:1.5;}'
           . 'a{display:inline-block;padding:.6rem 1.2rem;background:#2563eb;'
           . 'color:#fff;text-decoration:none;border-radius:.5rem;font-size:.9rem;'
           . 'font-weight:500;}a:hover{background:#1d4ed8;}</style></head><body>'
           . '<div class="card"><h1>Acceso denegado</h1>'
           . '<p>No tienes permiso para acceder a esta sección. '
           . 'Contacta al administrador si crees que es un error.</p>'
           . '<a href="' . htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') . 'dashboard">'
           . 'Volver al inicio</a></div></body></html>';
        exit;
    }
}