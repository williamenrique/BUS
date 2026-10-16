<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ModelRecuperar;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;

class ControllerRecuperar extends Controller{

    /**
     * Verifica si el usuario actual es del departamento Sistema.
     */
    private function esSistema(): bool{
        $user = auth_user();
        if (!$user) return false;
        return (int)($user['departamento_id'] ?? 0) === (int)DEPARTAMENTO_SISTEMA;
    }

    /* ============================================================
       VISTA · Tarjetas de solicitudes
       ============================================================ */
    public function index(): void{
        AuthGuard::check();

        if (!$this->esSistema()){
            http_response_code(403);
            echo 'Acceso restringido.';
            exit;
        }

        $user = auth_user();
        $mUser = new ModelUsuario();
        $this->view('recuperar.index', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Recuperación de contraseñas',
            'scripts'   => ['recuperar.js'],
        ]);
    }

    /* ============================================================
       PÚBLICO · Crear solicitud desde login
       POST /recuperar/solicitar
       ============================================================ */
    public function solicitar(): void{
        header('Content-Type: application/json');

        $ident = trim((string)($_POST['identificador'] ?? ''));

        if ($ident === ''){
            $this->json(['ok' => false, 'message' => 'Ingresa tu cédula, correo o usuario.'], 422);
        }

        $mU = new ModelUsuario();
        $user = null;

        // Cascada: correo → cédula → nick
        if (str_contains($ident, '@')){
            $user = $mU->findByEmail($ident);
        }
        if (!$user && preg_match('/^[A-Za-z]{0,2}[-\s]?\d{5,}$/', $ident)){
            $user = $mU->findByCedula($ident);
        }
        if (!$user){
            $user = $mU->findByNick($ident);
        }

        $msgOk = 'Si el usuario existe, un administrador procesará tu solicitud.';

        if (!$user){
            $this->json(['ok' => true, 'message' => $msgOk]);
            return;
        }

        $userId = (int)$user['usuario_id'];
        $mR = new ModelRecuperar();

        if ($mR->tienePendiente($userId)){
            $this->json(['ok' => true, 'message' => 'Ya tienes una solicitud pendiente. Espera la respuesta.']);
            return;
        }

        try{
            $ok = $mR->crear($userId, $ident);
            if (!$ok){
                $this->json(['ok' => true, 'message' => $msgOk]);
                return;
            }
            $this->json(['ok' => true, 'message' => 'Solicitud enviada. Un administrador la procesará pronto.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error al registrar: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       CONTADOR · Solicitudes pendientes (navbar)
       ============================================================ */
    public function contador(): void{
        AuthGuard::check();

        if (!$this->esSistema()){
            $this->json(['ok' => true, 'count' => 0]);
            return;
        }

        $m = new ModelRecuperar();
        $this->json(['ok' => true, 'count' => $m->contarPendientes()]);
    }

    /* ============================================================
       LISTAR · Solicitudes pendientes (JSON)
       ============================================================ */
    public function listar(): void{
        AuthGuard::check();

        if (!$this->esSistema()){
            $this->json(['ok' => false, 'message' => 'No autorizado.'], 403);
            return;
        }

        $m = new ModelRecuperar();
        $this->json(['ok' => true, 'data' => $m->listarPendientes()]);
    }

    /* ============================================================
       RESET · Restablece contraseña a 123456
       ============================================================ */
    public function resetear(string $id): void{
        AuthGuard::check();

        if (!$this->esSistema()){
            $this->json(['ok' => false, 'message' => 'No autorizado.'], 403);
            return;
        }

        $id = (int)$id;
        $mR = new ModelRecuperar();
        $sol = $mR->find($id);
        if (!$sol){
            $this->json(['ok' => false, 'message' => 'Solicitud no encontrada.'], 404);
        }

        $mU = new ModelUsuario();
        try{
            $mU->updatePassword((int)$sol['user_id'], hash_password(PASSWORD_DEFAULT_RESET));
            $this->json([
                'ok' => true,
                'message' => "Contraseña de \"{$sol['usuario_nick']}\" restablecida a 123456."
            ]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       RESOLVER · Marca como resuelto
       ============================================================ */
    public function resolver(string $id): void{
        AuthGuard::check();

        if (!$this->esSistema()){
            $this->json(['ok' => false, 'message' => 'No autorizado.'], 403);
            return;
        }

        $id = (int)$id;
        $mR = new ModelRecuperar();
        if (!$mR->find($id)){
            $this->json(['ok' => false, 'message' => 'Solicitud no encontrada.'], 404);
        }

        try{
            $mR->marcarResuelto($id);
            $this->json(['ok' => true, 'message' => 'Solicitud atendida y eliminada.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       DEBUG · Verifica el estado del usuario actual
       GET /recuperar/debug
       ============================================================ */
    public function debug(): void{
        AuthGuard::check();
        $user = auth_user();

        $this->json([
            'ok'                  => true,
            'user_id'             => $user['id'] ?? null,
            'usuario_nick'        => $user['nick'] ?? null,
            'departamento_id'     => $user['departamento_id'] ?? null,
            'departamento_nombre' => $user['departamento_nombre'] ?? null,
            'es_sistema_en_sesion'=> $user['es_sistema'] ?? null,
            'es_sistema_calculado'=> ((int)($user['departamento_id'] ?? 0) === (int)DEPARTAMENTO_SISTEMA),
            'DEPARTAMENTO_SISTEMA'=> DEPARTAMENTO_SISTEMA,
            'pendientes'          => (new ModelRecuperar())->contarPendientes(),
        ]);
    }
}