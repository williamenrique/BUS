<?php
namespace App\Middleware;

use App\Models\ModelUsuario;
use App\Models\ModelEstacion;

class AuthGuard
{
    public static function check(): void
    {
        if (!isset($_SESSION['user']['id'])){
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? null;
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        // Expiración por inactividad
        $now = time();
        if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > SESSION_LIFETIME){
            self::killSession('expired');
        }
        $_SESSION['last_activity'] = $now;

        // Sesión única
        if (SINGLE_SESSION){
            try{
                $uid = (int)$_SESSION['user']['id'];
                $sid = session_id();
                $mUser = new ModelUsuario();
                $sidBd = $mUser->obtenerSesionActiva($uid);
                if ($sidBd === null || $sidBd !== $sid){
                    self::killSession('kicked');
                }
                $mUser->tocarSesion($sid);
            } catch (\Throwable $e){
                error_log('SITGO AuthGuard: ' . $e->getMessage());
            }
        }

        // =====================================================
        // ¿Es del depto Estación y aún no elige estación en esta sesión?
        // Se consulta SIEMPRE primero $_SESSION y, si no hay, la BD.
        // =====================================================
        $depId = (int)($_SESSION['user']['departamento_id'] ?? 0);

        if ($depId === DEPARTAMENTO_ESTACION){

            $estId = (int)($_SESSION['user']['estacion_id'] ?? 0);

            // Rehidratar desde table_usuario_sessions si $_SESSION lo perdió
            if ($estId === 0){
                try{
                    $mEst = new ModelEstacion();
                    $estId = $mEst->obtenerEstacionSesion(session_id());
                    if ($estId > 0){
                        $_SESSION['user']['estacion_id'] = $estId;
                    }
                } catch (\Throwable $e){
                    error_log('SITGO AuthGuard estacion: ' . $e->getMessage());
                }
            }

            // Si sigue en 0 → forzar selección (salvo que ya esté en esa ruta)
            if ($estId === 0){
                $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
                $base = parse_url(BASE_URL, PHP_URL_PATH) ?: '/';
                $rel  = ($base !== '/' && strpos($uri, $base) === 0)
                    ? substr($uri, strlen($base))
                    : ltrim($uri, '/');
                $rel = '/' . trim($rel, '/');

                if ($rel !== '/estacion/seleccionar'){
                    header('Location: ' . BASE_URL . 'estacion/seleccionar');
                    exit;
                }
            }
        }
    }

    /**
     * Cierra la sesión y redirige con un motivo.
     */
    private static function killSession(string $reason = 'expired'): void
    {
        try {
            if (isset($_SESSION['user']['id'])) {
                $model = new ModelUsuario();
                $model->eliminarSesionPorId(session_id());
            }
        } catch (\Throwable $e) {
            error_log('SITGO killSession: ' . $e->getMessage());
        }

        session_unset();
        session_destroy();

        $qs = match ($reason) {
            'kicked'  => '?kicked=1',
            'expired' => '?expired=1',
            default   => '',
        };
        header('Location: ' . BASE_URL . 'login' . $qs);
        exit;
    }
}