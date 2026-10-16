<?php
namespace App\Middleware;

use App\Models\ModelUsuario;

class AuthGuard
{
    public static function check(): void
    {
        // 1) ¿Hay sesión PHP?
        if (!isset($_SESSION['user']['id'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? null;
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        // 2) Expiración por inactividad
        $now = time();
        if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > SESSION_LIFETIME) {
            self::killSession('expired');
        }
        $_SESSION['last_activity'] = $now;

        // 3) Sesión única: verificar que session_id coincida con BD
        if (SINGLE_SESSION) {
            try {
                $uid = (int)$_SESSION['user']['id'];
                $sid = session_id();

                $model = new ModelUsuario();
                $sidBd = $model->obtenerSesionActiva($uid);

                // No hay sesión en BD → fue eliminada (kick o logout remoto)
                if ($sidBd === null) {
                    self::killSession('kicked');
                }

                // El id actual no coincide con el de BD → otra sesión nos reemplazó
                if ($sidBd !== $sid) {
                    self::killSession('kicked');
                }

                // Heartbeat: actualizar last_activity
                $model->tocarSesion($sid);
            } catch (\Throwable $e) {
                // Si la BD falla, no bloqueamos al usuario por seguridad operativa.
                // En producción podrías querer hacer lo contrario.
                error_log('SITGO AuthGuard: ' . $e->getMessage());
            }
        }
    }

    /**
     * Cierra la sesión y redirige con un motivo.
     */
    private static function killSession(string $reason = 'expired'): void
    {
        // Limpiar en BD si podemos
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