<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\ModelBienes;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;

class ControllerBienes extends Controller{

    /* ============================================================
       VISTA
       ============================================================ */
    public function index(string $inst = '1'): void{
        AuthGuard::check();
        $instId = inst_actual((int)$inst);
        $user = auth_user();
        $mUser = new ModelUsuario();

        $this->view('bienes.index', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Bienes',
            'instId'    => $instId,
            'scripts'   => ['bienes.js'],   // 👈 ESTO FALTABA
        ]);
    }

    /* ============================================================
       LISTAR
       ============================================================ */
    public function listar(string $inst = '1'): void{
        AuthGuard::check();
        $instId = inst_actual((int)$inst);

        $q      = trim((string)($_GET['q'] ?? ''));
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $filtros = [
            'departamento' => $_GET['departamento'] ?? '',
            'grupo'        => $_GET['grupo']        ?? '',
            'status'       => $_GET['status']       ?? '',
        ];

        $this->safe(function() use ($q, $limit, $offset, $filtros, $instId){
            $m = new ModelBienes();
            $r = $m->listarPaginado($q, $limit, $offset, $filtros, $instId);
            $this->json([
                'ok'             => true,
                'data'           => $r['data'],
                'total'          => $r['total'],
                'totalFiltrados' => $r['totalFiltrados'],
            ]);
        });
    }

    /* ============================================================
       COMBOS
       ============================================================ */
    public function combos(string $inst = '1'): void{
        AuthGuard::check();
        inst_actual((int)$inst);

        $this->safe(function(){
            $m = new ModelBienes();
            $this->json([
                'ok'            => true,
                'departamentos' => $m->departamentos(),
                'grupos'        => $m->grupos(),
                'subgrupos'     => $m->subgrupos(),
                'secciones'     => $m->secciones(),
            ]);
        });
    }

    /* ============================================================
       OBTENER
       ============================================================ */
    public function obtener(string $inst, string $id): void{
        AuthGuard::check();
        inst_actual((int)$inst);

        $this->safe(function() use ($id){
            $row = (new ModelBienes())->find((int)$id);
            if (!$row){
                $this->json(['ok' => false, 'message' => 'Bien no encontrado.'], 404);
                return;
            }
            $this->json(['ok' => true, 'data' => $row]);
        });
    }

    /* ============================================================
       GUARDAR
       ============================================================ */
    public function guardar(string $inst = '1'): void{
        AuthGuard::check();
        $instId = inst_actual((int)$inst);
        $user = auth_user();

        $d = $this->input();
        if (empty($d)) $d = $_POST;
        $d = $this->normalizar($d);

        $v = new Validator();
        $v->required('bien_depatamento_id', $d['bien_depatamento_id'] ?? '', 'Departamento')
          ->required('descripcion_bien',    $d['descripcion_bien'] ?? '',    'Descripción');
        if ($v->fails()){
            $this->json(['ok' => false, 'errors' => $v->errors()], 422);
            return;
        }

        $this->safe(function() use ($d, $user, $instId){
            $m = new ModelBienes();
            $id = $m->create($d, (int)$user['id'], $instId);
            $this->json([
                'ok'      => true,
                'message' => 'Bien registrado correctamente.',
                'id'      => $id,
            ]);
        });
    }

    /* ============================================================
       ACTUALIZAR
       ============================================================ */
    public function actualizar(string $inst, string $id): void{
        AuthGuard::check();
        inst_actual((int)$inst);
        $id = (int)$id;

        $d = $this->input();
        if (empty($d)) $d = $_POST;
        $d = $this->normalizar($d);

        $v = new Validator();
        $v->required('bien_depatamento_id', $d['bien_depatamento_id'] ?? '', 'Departamento')
          ->required('descripcion_bien',    $d['descripcion_bien'] ?? '',    'Descripción');
        if ($v->fails()){
            $this->json(['ok' => false, 'errors' => $v->errors()], 422);
            return;
        }

        $this->safe(function() use ($d, $id){
            $m = new ModelBienes();
            if (!$m->find($id)){
                $this->json(['ok' => false, 'message' => 'Bien no encontrado.'], 404);
                return;
            }
            $m->update($id, $d);
            $this->json(['ok' => true, 'message' => 'Bien actualizado correctamente.']);
        });
    }

    /* ============================================================
       ELIMINAR
       ============================================================ */
    public function eliminar(string $inst, string $id): void{
        AuthGuard::check();
        inst_actual((int)$inst);
        $id = (int)$id;

        $this->safe(function() use ($id){
            $m = new ModelBienes();
            if (!$m->find($id)){
                $this->json(['ok' => false, 'message' => 'Bien no encontrado.'], 404);
                return;
            }
            $m->delete($id);
            $this->json(['ok' => true, 'message' => 'Bien eliminado.']);
        });
    }

    /* ============================================================
       HELPERS
       ============================================================ */
    private function normalizar(array $d): array{
        if (isset($d['descripcion_bien'])){
            $d['descripcion_bien'] = strtoupper(trim((string)$d['descripcion_bien']));
        }
        return $d;
    }
}