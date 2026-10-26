<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\ModelPersonal;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;
use App\Middleware\MenuGuard;

class ControllerPersonal extends Controller
{
    public function index(): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $user = auth_user();
        $mUser = new ModelUsuario();
        $this->view('personal.index', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Personal',
            'scripts'   => ['personal.js'],
        ]);
    }

    public function listar(): void
    {
        AuthGuard::check();
        MenuGuard::check();

        $q      = trim((string)($_GET['q'] ?? ''));
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $filtros = [
            'status'       => $_GET['status']       ?? '',
            'departamento' => $_GET['departamento'] ?? '',
        ];

        try {
            $m = new ModelPersonal();
            $r = $m->listarPaginado($q, $limit, $offset, $filtros);

            $this->json([
                'ok'             => true,
                'data'           => $r['data'],
                'total'          => $r['total'],
                'totalFiltrados' => $r['totalFiltrados'],
                'page'           => (int)floor($offset / $limit) + 1,
                'limit'          => $limit,
            ]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function obtener(string $id): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $row = (new ModelPersonal())->find((int)$id);
        if (!$row) $this->json(['ok' => false, 'message' => 'Personal no encontrado.'], 404);
        $this->json(['ok' => true, 'data' => $row]);
    }

    public function combos(): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $m = new ModelPersonal();
        $this->json([
            'ok'            => true,
            'cargos'        => $m->cargos(),
            'departamentos' => $m->departamentos(),
        ]);
    }

    public function guardar(): void{
        AuthGuard::check();
        MenuGuard::check();
        $d = $this->input();

        // Normalizar (MAYÚSCULAS excepto email)
        $d = $this->normalizar($d);

        $v = new Validator();
        $v->required('personal_cedula',   $d['personal_cedula'] ?? '',   'Cédula')
        ->required('personal_nombre',   $d['personal_nombre'] ?? '',   'Nombre')
        ->required('personal_apellido', $d['personal_apellido'] ?? '', 'Apellido')
        ->required('personal_cargo',    $d['personal_cargo'] ?? '',    'Cargo');
        if (!empty($d['personal_email'])) $v->email('personal_email', $d['personal_email']);
        if ($v->fails()) $this->json(['ok' => false, 'errors' => $v->errors()], 422);

        $m = new ModelPersonal();
        if ($m->existeCedula($d['personal_cedula']))
            $this->json(['ok' => false, 'errors' => ['personal_cedula' => 'La cédula ya está registrada.']], 422);
        if (!empty($d['personal_email']) && $m->existeEmail($d['personal_email']))
            $this->json(['ok' => false, 'errors' => ['personal_email' => 'El correo ya está registrado.']], 422);

        try {
            $id = $m->create($d);
            $this->json(['ok' => true, 'message' => 'Personal creado correctamente.', 'id' => $id]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function actualizar(string $id): void{
        AuthGuard::check();
        MenuGuard::check();
        $id = (int)$id;
        $d = $this->input();

        // Normalizar (MAYÚSCULAS excepto email)
        $d = $this->normalizar($d);

        $v = new Validator();
        $v->required('personal_nombre',   $d['personal_nombre'] ?? '',   'Nombre')
        ->required('personal_apellido', $d['personal_apellido'] ?? '', 'Apellido')
        ->required('personal_cargo',    $d['personal_cargo'] ?? '',    'Cargo');
        if (!empty($d['personal_email'])) $v->email('personal_email', $d['personal_email']);
        if ($v->fails()) $this->json(['ok' => false, 'errors' => $v->errors()], 422);

        $m = new ModelPersonal();
        if (!$m->find($id)) $this->json(['ok' => false, 'message' => 'Personal no encontrado.'], 404);
        if (!empty($d['personal_email']) && $m->existeEmail($d['personal_email'], $id))
            $this->json(['ok' => false, 'errors' => ['personal_email' => 'El correo ya está en uso.']], 422);

        try {
            $m->update($id, $d);
            $this->json(['ok' => true, 'message' => 'Personal actualizado correctamente.']);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function eliminar(string $id): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $id = (int)$id;
        $m = new ModelPersonal();
        if (!$m->find($id)) $this->json(['ok' => false, 'message' => 'Personal no encontrado.'], 404);
        try {
            $m->delete($id);
            $this->json(['ok' => true, 'message' => 'Personal eliminado.']);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => 'No se pudo eliminar (puede estar en uso).'], 500);
        }
    }

    public function cambiarStatus(string $id): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $id = (int)$id;
        $d = $this->input();
        $status = (int)($d['personal_status'] ?? 1);
        $m = new ModelPersonal();
        if (!$m->find($id)) $this->json(['ok' => false, 'message' => 'Personal no encontrado.'], 404);
        try {
            $m->cambiarStatus($id, $status);
            $this->json(['ok' => true, 'message' => 'Estatus actualizado.']);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Normaliza el payload del personal.
     * Todos los campos de texto van en MAYÚSCULAS excepto el email.
     */
    private function normalizar(array $d): array{
        // 1) Uppercase en todo excepto el email
        $d = normalizar_mayusculas($d, ['personal_email']);

        // 2) Email normalizado (trim + lowercase del dominio)
        if (isset($d['personal_email'])){
            $d['personal_email'] = normalizar_email((string)$d['personal_email']);
        }

        return $d;
    }
}