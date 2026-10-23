<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ModelFlotaStatus;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;
use App\Middleware\MenuGuard;

class ControllerFlotaStatus extends Controller
{
    /* ============================================================
       VISTA PRINCIPAL
       Protegido con menu_id = 1103 (patrón: flota/status)
       ============================================================ */
    public function index(string $inst = '1'): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);
        $user   = auth_user();
        $mUser  = new ModelUsuario();

        $this->view('flota.status_historial', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Historial de Estados',
            'instId'    => $instId,
            'scripts'   => ['flota-status.js'],
        ]);
    }

    /* ============================================================
       LISTAR (paginado)
       ============================================================ */
    public function listar(string $inst = '1'): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);

        $q      = trim((string)($_GET['q'] ?? ''));
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $filtros = [
            'status'   => $_GET['status']   ?? '',
            'id_flota' => $_GET['id_flota'] ?? '',
            'desde'    => $_GET['desde']    ?? '',
            'hasta'    => $_GET['hasta']    ?? '',
        ];

        try {
            $m = new ModelFlotaStatus();
            $r = $m->listarPaginado($q, $limit, $offset, $filtros, $instId);

            $this->json([
                'ok'             => true,
                'data'           => $r['data'],
                'total'          => $r['total'],
                'totalFiltrados' => $r['totalFiltrados'],
                'counts'         => $r['counts'],
                'page'           => (int)floor($offset / $limit) + 1,
                'limit'          => $limit,
            ]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       COMBOS (unidades para el filtro)
       ============================================================ */
    public function combos(string $inst = '1'): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);

        try {
            $m = new ModelFlotaStatus();
            $this->json([
                'ok'       => true,
                'unidades' => $m->unidadesCombo($instId),
            ]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}