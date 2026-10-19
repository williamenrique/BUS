<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\ModelUsuario;
use App\Models\ModelEstacion;

class ControllerAuth extends Controller
{
    public function showLogin(): void
    {
        if (is_logged_in()) {
            $this->redirect('dashboard');
        }
        $this->view('auth.Login', [
            'appName' => SITGO_APP_NAME,
        ], false);
    }

    public function login(): void
    {
        header('Content-Type: application/json');

        $nick = trim((string)$this->post('nick'));
        $pass = (string)$this->post('password');

        $v = new Validator();
        $v->required('nick', $nick, 'Usuario')
        ->required('password', $pass, 'Contraseña');

        if ($v->fails()) {
            $this->json(['ok' => false, 'errors' => $v->errors()], 422);
            return;
        }

        $model = new ModelUsuario();
        $user  = $model->findByNick($nick);

        if (!$user) {
            $this->json(['ok' => false, 'message' => 'Credenciales inválidas.'], 401);
            return;
        }

        $check = verify_password($pass, (string)$user['usuario_password']);
        if (!$check['ok']) {
            $this->json(['ok' => false, 'message' => 'Credenciales inválidas.'], 401);
            return;
        }

        // Rehash si es legacy
        if ($check['needs_rehash']) {
            try {
                $model->actualizarPassword((int)$user['usuario_id'], hash_password($pass));
            } catch (\Throwable $e) {
                error_log('SITGO: no se pudo rehashear password usuario ' . $user['usuario_id']);
            }
        }

        // =====================================================
        // SESIÓN ÚNICA: matar sesiones previas + limpiar antiguas
        // =====================================================
        if (SINGLE_SESSION) {
            $model->limpiarSesionesExpiradas((int)(SESSION_LIFETIME / 60));
            $model->eliminarSesiones((int)$user['usuario_id']);
        }

        // Regenerar id de sesión (evita fixation) y guardar estado
        session_regenerate_id(true);
        $newSid = session_id();

        $_SESSION['user'] = [
            'id'                 => (int)$user['usuario_id'],
            'nick'               => $user['usuario_nick'],
            'rol_id'             => (int)$user['usuario_rol_id'],
            'rol_nombre'         => $user['rol_nombre'],
            'departamento_id'    => (int)$user['usuario_departamento_id'],
            'departamento_nombre'=> $user['departamento_nombre'],
            'estacion_id'        => 0, // se hidrata desde table_usuario_sessions abajo
            'personal_id'        => (int)($user['usuario_id_personal'] ?? 0),
            'nombre'             => trim(($user['personal_nombre'] ?? '') . ' ' . ($user['personal_apellido'] ?? '')),
            'email'              => $user['personal_email'] ?? '',
            'imagen'             => $user['usuario_imagen'] ?? '',
            'es_sistema' => ((int)$user['usuario_departamento_id'] === (int)DEPARTAMENTO_SISTEMA),
        ];
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf_token']    = bin2hex(random_bytes(32));
        $_SESSION['db_session_id'] = $newSid;

        // Registrar la NUEVA sesión (id_estacion queda NULL)
        $model->registrarSesion(
            $newSid,
            (int)$user['usuario_id'],
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        // =====================================================
        // DETERMINAR REDIRECT FINAL (UN SOLO json())
        // =====================================================
        $depId     = (int)$user['usuario_departamento_id'];
        $esSistema = ($depId === (int)DEPARTAMENTO_SISTEMA);
        $esEstacion= ($depId === (int)DEPARTAMENTO_ESTACION);

        $redirect = base_url('dashboard');

        if ($esEstacion) {
            // ¿La sesión recién creada tiene estación? (por ahora será 0/NULL)
            try {
                $mEst  = new ModelEstacion();
                $idEst = $mEst->obtenerEstacionSesion($newSid);
            } catch (\Throwable $e) {
                error_log('SITGO login estacion lookup: ' . $e->getMessage());
                $idEst = 0;
            }

            if ($idEst > 0) {
                // Rehidratar por si el usuario ya había elegido estación antes (no debería en login nuevo)
                $_SESSION['user']['estacion_id'] = $idEst;
                $redirect = base_url('estacion/registrar');
            } else {
                $redirect = base_url('estacion/seleccionar');
            }
        }

        $this->json(['ok' => true, 'redirect' => $redirect]);
        return;
    }

    public function logout(): void
    {
        if (is_logged_in()) {
            try {
                $model = new ModelUsuario();
                $model->eliminarSesionPorId(session_id());
            } catch (\Throwable $e) {
                error_log('SITGO: error al limpiar sesión en logout: ' . $e->getMessage());
            }
        }

        session_unset();
        session_destroy();
        $this->redirect('login?logout=1');
    }

    public function recuperar(): void
    {
        header('Content-Type: application/json');

        $ident = trim((string)$this->post('identificador'));

        if ($ident === '') {
            $this->json(['ok' => false, 'message' => 'Debes indicar tu usuario o correo.'], 422);
            return;
        }

        $model = new ModelUsuario();
        $user  = str_contains($ident, '@')
            ? $model->findByEmail($ident)
            : $model->findByNick($ident);

        if ($user) {
            $model->registrarRecuperacion((int)$user['usuario_id'], $ident);
        }

        $this->json([
            'ok' => true,
            'message' => 'Si el usuario existe, un administrador procesará tu solicitud de recuperación.'
        ]);
    }
}