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
     * Responde 403. JSON si es AJAX, HTML elegante si es vista normal.
     */
    public static function denegar(): void {
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

        $base = htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8');

        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
            <html lang="es">
            <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Acceso denegado · SITGO</title>
            <link rel="icon" href="' . $base . 'img/logo.png">
            <style>
                *{box-sizing:border-box;}
                html,body{margin:0;padding:0;}
                body{
                    font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
                    background:#f8fafc;
                    color:#0f172a;
                    min-height:100vh;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    padding:1.5rem;
                    -webkit-font-smoothing:antialiased;
                }
                .card{
                    background:#fff;
                    border:1px solid #e2e8f0;
                    border-radius:16px;
                    padding:2.5rem 2rem;
                    max-width:440px;
                    width:100%;
                    text-align:center;
                    box-shadow:0 4px 20px rgba(15,23,42,.06);
                }
                .icon{
                    width:72px;height:72px;
                    border-radius:50%;
                    background:#f1f5f9;
                    color:#64748b;
                    display:flex;align-items:center;justify-content:center;
                    margin:0 auto 1.25rem;
                }
                .icon svg{width:32px;height:32px;stroke-width:1.8;}
                h1{
                    margin:0 0 .5rem;
                    font-size:1.25rem;
                    font-weight:700;
                    color:#0f172a;
                    letter-spacing:-.01em;
                }
                p{
                    margin:0 0 1.75rem;
                    font-size:.9rem;
                    color:#64748b;
                    line-height:1.55;
                }
                .actions{
                    display:flex;
                    gap:.5rem;
                    justify-content:center;
                    flex-wrap:wrap;
                }
                .btn{
                    display:inline-flex;
                    align-items:center;
                    gap:.4rem;
                    padding:.6rem 1.1rem;
                    border-radius:.5rem;
                    font-size:.875rem;
                    font-weight:500;
                    text-decoration:none;
                    transition:background-color .15s,border-color .15s;
                    border:1px solid transparent;
                    cursor:pointer;
                }
                .btn svg{width:16px;height:16px;stroke-width:2;}
                .btn-primary{background:#2563eb;color:#fff;}
                .btn-primary:hover{background:#1d4ed8;}
                .btn-ghost{background:#f1f5f9;color:#475569;border-color:#e2e8f0;}
                .btn-ghost:hover{background:#e2e8f0;}
                @media(max-width:420px){
                    .card{padding:2rem 1.5rem;}
                    .actions{flex-direction:column-reverse;}
                    .btn{width:100%;justify-content:center;}
                }
            </style>
            </head>
            <body>
                <div class="card">
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                    </div>
                    <h1>Acceso denegado</h1>
                    <p>No tienes permiso para acceder a esta sección. Si crees que es un error, contacta al administrador del sistema.</p>
                    <div class="actions">
                        <button type="button" class="btn btn-ghost" onclick="history.back()">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                            Volver
                        </button>
                        <a href="' . $base . 'dashboard" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Ir al inicio
                        </a>
                    </div>
                </div>
            </body>
            </html>';
                    exit;
                }
}