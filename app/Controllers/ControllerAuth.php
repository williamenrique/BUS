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

    public function login(): void {
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

        if ((int)$user['usuario_status'] !== 1) {
            $this->json([
                'ok'      => false,
                'message' => 'Tu usuario está inhabilitado. Contacta al administrador del sistema.'
            ], 403);
            return;
        }

        if ($check['needs_rehash']) {
            try {
                $model->actualizarPassword((int)$user['usuario_id'], hash_password($pass));
            } catch (\Throwable $e) {
                error_log('SITGO: no se pudo rehashear password usuario ' . $user['usuario_id']);
            }
        }

        if (SINGLE_SESSION) {
            $model->limpiarSesionesExpiradas((int)(SESSION_LIFETIME / 60));
            $model->eliminarSesiones((int)$user['usuario_id']);
        }

        session_regenerate_id(true);
        $newSid = session_id();

        $_SESSION['user'] = [
            'id'                 => (int)$user['usuario_id'],
            'nick'               => $user['usuario_nick'],
            'rol_id'             => (int)$user['usuario_rol_id'],
            'rol_nombre'         => $user['rol_nombre'],
            'departamento_id'    => (int)$user['usuario_departamento_id'],
            'departamento_nombre'=> $user['departamento_nombre'],
            'estacion_id'        => 0,
            'estacion'           => '',
            'personal_id'        => (int)($user['usuario_id_personal'] ?? 0),
            'nombre'             => trim(($user['personal_nombre'] ?? '') . ' ' . ($user['personal_apellido'] ?? '')),
            'email'              => $user['personal_email'] ?? '',
            'imagen'             => $user['usuario_imagen'] ?? '',
            'es_sistema'         => ((int)$user['usuario_departamento_id'] === (int)DEPARTAMENTO_SISTEMA),
        ];
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf_token']    = bin2hex(random_bytes(32));
        $_SESSION['db_session_id'] = $newSid;

        $model->registrarSesion(
            $newSid,
            (int)$user['usuario_id'],
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        $depId      = (int)$user['usuario_departamento_id'];
        $esEstacion = ($depId === (int)DEPARTAMENTO_ESTACION);
        $redirect   = base_url('dashboard');

        if ($esEstacion) {
            $mEst = new ModelEstacion();

            $idEst = 0;
            $hoy   = date('Y-m-d');

            $estHabitual    = (int)($user['usuario_estacion_id'] ?? 0);
            $fechaUltimaEst = (string)($user['usuario_ultima_estacion'] ?? '');

            error_log("Login estación: nick={$user['usuario_nick']} estHabitual={$estHabitual} fechaUltima={$fechaUltimaEst} hoy={$hoy}");

            if ($estHabitual > 0 && $fechaUltimaEst === $hoy) {
                $est = $mEst->findEstacion($estHabitual);
                if ($est) {
                    $idEst = $estHabitual;
                    error_log("Login estación: usando estación habitual {$idEst} (confirmada hoy)");
                }
            }

            if ($idEst > 0) {
                $_SESSION['user']['estacion_id'] = $idEst;

                // Traer el NOMBRE de la estación y guardarlo en la sesión
                $estInfo = $mEst->findEstacion($idEst);
                if ($estInfo) {
                    $_SESSION['user']['estacion'] = $estInfo['estacion'];
                }

                try {
                    $mEst->guardarEstacionSesion($newSid, $idEst);
                } catch (\Throwable $e) {
                    error_log('SITGO login estacion save: ' . $e->getMessage());
                }
                $redirect = base_url('estacion/registrar');
            } else {
                error_log("Login estación: redirigiendo a seleccionar (no hay estación confirmada hoy)");
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