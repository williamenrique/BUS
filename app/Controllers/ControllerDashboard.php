<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ModelDashboard;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;

class ControllerDashboard extends Controller
{
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
            'user'          => $user,
            'menu'          => $menu,
            'notificaciones'=> $model->notificaciones(10),
            'totales'       => $model->totales(),
            'pageTitle'     => 'Inicio',
        ]);
    }
}