<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ModelDashboard;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;

class ControllerDashboard extends Controller
{
    /* ============================================================
       HELPERS
       ============================================================ */
    private function esSistema(): bool
    {
        $u = auth_user();
        return (int)($u['departamento_id'] ?? 0) === DEPARTAMENTO_SISTEMA;
    }

    private function institucionFiltrada(): int
    {
        $req = (int)($_GET['id_institucion'] ?? $_POST['id_institucion'] ?? 1);
        if ($this->esSistema()) {
            return max(1, $req);
        }
        // Departamentos no-admin: usar la institución del usuario
        $u = auth_user();
        return (int)($u['institucion_id'] ?? 1);
    }

    /* ============================================================
       VISTA PRINCIPAL
       ============================================================ */
    public function index(): void
    {
        AuthGuard::check();

        $user  = auth_user();
        $model = new ModelDashboard();
        $mUser = new ModelUsuario();

        $menu = $mUser->getMenuJerarquico(
            (int)$user['id'],
            (int)$user['departamento_id']
        );

        $this->view('dashboard.index', [
            'user'           => $user,
            'menu'           => $menu,
            'notificaciones' => $model->notificaciones(10),
            'totales'        => $model->totales(),
            'pageTitle'      => 'Inicio',
            'scripts'        => ['dashboard.js'],
            'esSistema'      => $this->esSistema(),
        ]);
    }

    /* ============================================================
       INSTITUCIONES (solo Sistema)
       ============================================================ */
    public function getInstituciones(): void
    {
        AuthGuard::check();
        $this->safe(function () {
            $this->json(['success' => true, 'data' => (new ModelDashboard())->getInstituciones()]);
        });
    }

    /* ============================================================
       ALMACÉN
       ============================================================ */
    public function getAlmacenData(): void
    {
        AuthGuard::check();
        $id = $this->institucionFiltrada();
        $this->safe(function () use ($id) {
            $this->json(['success' => true, 'data' => (new ModelDashboard())->getAlmacenDashboard($id)]);
        });
    }

    public function getTopProductosAlmacen(): void
    {
        AuthGuard::check();
        $id = $this->institucionFiltrada();
        $this->safe(function () use ($id) {
            $this->json(['success' => true, 'data' => (new ModelDashboard())->getTopProductosDespachados($id, 10)]);
        });
    }

    public function getUltimasOrdenesAlmacen(): void
    {
        AuthGuard::check();
        $id = $this->institucionFiltrada();
        $this->safe(function () use ($id) {
            $this->json(['success' => true, 'data' => (new ModelDashboard())->getUltimasOrdenes($id, 10)]);
        });
    }

    /* ============================================================
       OPERACIONES
       ============================================================ */
    public function getOperacionesData(): void
    {
        AuthGuard::check();
        $id = $this->institucionFiltrada();
        $this->safe(function () use ($id) {
            $this->json(['success' => true, 'data' => (new ModelDashboard())->getOperacionesDashboard($id)]);
        });
    }

    /* ============================================================
       COMPRAS
       ============================================================ */
    public function getComprasData(): void
    {
        AuthGuard::check();
        $id = $this->institucionFiltrada();
        $this->safe(function () use ($id) {
            $this->json(['success' => true, 'data' => (new ModelDashboard())->getComprasDashboardData($id)]);
        });
    }

    public function getTopProductosCosteados(): void
    {
        AuthGuard::check();
        $id = $this->institucionFiltrada();
        $this->safe(function () use ($id) {
            $this->json(['success' => true, 'data' => (new ModelDashboard())->getTopProductosCosteados($id, 10)]);
        });
    }

    public function getUltimasRequisiciones(): void
    {
        AuthGuard::check();
        $id = $this->institucionFiltrada();
        $this->safe(function () use ($id) {
            $this->json(['success' => true, 'data' => (new ModelDashboard())->getUltimasRequisiciones($id, 10)]);
        });
    }

    public function getUltimasComprasCosteadas(): void
    {
        AuthGuard::check();
        $id = $this->institucionFiltrada();
        $this->safe(function () use ($id) {
            $this->json(['success' => true, 'data' => (new ModelDashboard())->getUltimasComprasCosteadas($id, 10)]);
        });
    }

    /* ============================================================
       BIENES
       ============================================================ */
    public function getBienesData(): void
    {
        AuthGuard::check();
        $id = $this->institucionFiltrada();
        $this->safe(function () use ($id) {
            $this->json(['success' => true, 'data' => (new ModelDashboard())->getBienesDashboardData($id)]);
        });
    }

    /* ============================================================
       ESTACIÓN
       ============================================================ */
    public function getEstacionData(): void
    {
        AuthGuard::check();
        $u = auth_user();
        $this->safe(function () use ($u) {
            $this->json(['success' => true, 'data' => (new ModelDashboard())->getEstacionDashboardData((int)$u['id'])]);
        });
    }

    public function getAvailableMonths(): void
    {
        AuthGuard::check();
        $this->safe(function () {
            $this->json(['success' => true, 'data' => (new ModelDashboard())->getAvailableMonths()]);
        });
    }

    public function getMonthlyLiters(): void
    {
        AuthGuard::check();
        $d = $this->input();
        $start = $d['start_month'] ?? '';
        $end   = $d['end_month']   ?? '';

        if (!preg_match('/^\d{4}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}$/', $end)) {
            $this->json(['success' => false, 'message' => 'Formato de fecha inválido.'], 422);
            return;
        }
        $this->safe(function () use ($start, $end) {
            $this->json(['success' => true, 'data' => (new ModelDashboard())->getMonthlyLiters($start, $end)]);
        });
    }

    public function getDailySales(): void
    {
        AuthGuard::check();
        $d = $this->input();
        $fecha = $d['fecha'] ?? date('Y-m-d');
        $this->safe(function () use ($fecha) {
            $this->json([
                'success' => true,
                'data'    => [
                    'sales_by_user' => (new ModelDashboard())->getDailySalesByUser($fecha),
                    'fecha'         => $fecha,
                ],
            ]);
        });
    }
}