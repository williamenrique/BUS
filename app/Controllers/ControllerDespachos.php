<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\ModelDespachos;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;

class ControllerDespachos extends Controller{

    /* ============================================================
       VISTA
       ============================================================ */
    public function index(string $inst = '1'): void{
        AuthGuard::check();
        $instId = inst_actual((int)$inst);
        $user = auth_user();
        $mUser = new ModelUsuario();

        $this->view('despachos.index', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Despachos',
            'instId'    => $instId,
            'scripts'   => ['despachos.js'],
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

        $filtros = ['fecha' => $_GET['fecha'] ?? ''];

        $this->safe(function() use ($q, $limit, $offset, $filtros, $instId){
            $m = new ModelDespachos();
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
        $instId = inst_actual((int)$inst);

        $this->safe(function() use ($instId){
            $m = new ModelDespachos();
            $this->json([
                'ok'             => true,
                'flota'          => $m->flota($instId),
                'operadores'     => $m->operadores(),
                'mecanicos'      => $m->mecanicos(),
                'despachadores'  => $m->despachadores(),
                'articulos'      => $m->articulosDisponibles($instId),
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
            $m = new ModelDespachos();
            $row = $m->find((int)$id);
            if (!$row){
                $this->json(['ok' => false, 'message' => 'Despacho no encontrado.'], 404);
                return;
            }
            $row['articulos'] = $m->articulos((int)$id);
            $this->json(['ok' => true, 'data' => $row]);
        });
    }

    /* ============================================================
       ARTÍCULOS
       ============================================================ */
    public function articulos(string $inst, string $id): void{
        AuthGuard::check();
        inst_actual((int)$inst);

        $this->safe(function() use ($id){
            $this->json(['ok' => true, 'data' => (new ModelDespachos())->articulos((int)$id)]);
        });
    }

    /* ============================================================
       STATS
       ============================================================ */
    public function stats(string $inst = '1'): void{
        AuthGuard::check();
        $instId = inst_actual((int)$inst);

        $this->safe(function() use ($instId){
            $this->json(['ok' => true, 'data' => (new ModelDespachos())->statsMes($instId)]);
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
        $v->required('id_flota',        $d['id_flota'] ?? '',        'Unidad')
          ->required('operador_id',     $d['operador_id'] ?? '',     'Operador')
          ->required('mecanico_id',     $d['mecanico_id'] ?? '',     'Mecánico')
          ->required('despachador_id',  $d['despachador_id'] ?? '',  'Despachador')
          ->required('fecha_despacho',  $d['fecha_despacho'] ?? '',  'Fecha');
        if ($v->fails()){
            $this->json(['ok' => false, 'errors' => $v->errors()], 422);
            return;
        }

        if (empty($d['articulos']) || !is_array($d['articulos'])){
            $this->json(['ok' => false, 'errors' => ['articulos' => 'Debe agregar al menos un artículo.']], 422);
            return;
        }

        $this->safe(function() use ($d, $user, $instId){
            $m = new ModelDespachos();
            $id = $m->create($d, (int)$user['id'], $instId);
            $this->json([
                'ok'      => true,
                'message' => 'Despacho registrado correctamente.',
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
        $v->required('id_flota',        $d['id_flota'] ?? '',        'Unidad')
          ->required('operador_id',     $d['operador_id'] ?? '',     'Operador')
          ->required('mecanico_id',     $d['mecanico_id'] ?? '',     'Mecánico')
          ->required('despachador_id',  $d['despachador_id'] ?? '',  'Despachador')
          ->required('fecha_despacho',  $d['fecha_despacho'] ?? '',  'Fecha');
        if ($v->fails()){
            $this->json(['ok' => false, 'errors' => $v->errors()], 422);
            return;
        }

        if (empty($d['articulos']) || !is_array($d['articulos'])){
            $this->json(['ok' => false, 'errors' => ['articulos' => 'Debe agregar al menos un artículo.']], 422);
            return;
        }

        $this->safe(function() use ($d, $id){
            $m = new ModelDespachos();
            if (!$m->find($id)){
                $this->json(['ok' => false, 'message' => 'Despacho no encontrado.'], 404);
                return;
            }
            $m->update($id, $d);
            $this->json(['ok' => true, 'message' => 'Despacho actualizado correctamente.']);
        });
    }

    /* ============================================================
       ELIMINAR
       ============================================================ */
    public function eliminar(string $inst, string $id): void{
        AuthGuard::check();
        inst_actual((int)$inst);
        $id = (int)$id;
        $user = auth_user();

        $d = $this->input();
        $motivo = trim((string)($d['motivo'] ?? ''));

        if ($motivo === ''){
            $this->json(['ok' => false, 'message' => 'Debes indicar el motivo.'], 422);
            return;
        }

        $this->safe(function() use ($id, $motivo, $user){
            $m = new ModelDespachos();
            if (!$m->find($id)){
                $this->json(['ok' => false, 'message' => 'Despacho no encontrado.'], 404);
                return;
            }
            $m->delete($id, $motivo, (int)$user['id']);
            $this->json(['ok' => true, 'message' => 'Despacho eliminado y stock revertido.']);
        });
    }

    /* ============================================================
       HELPERS
       ============================================================ */
    private function normalizar(array $d): array{
        if (isset($d['observacion'])){
            $d['observacion'] = strtoupper(trim((string)$d['observacion']));
        }
        return $d;
    }
        /* ============================================================
       VISTA · Historial de productos
       ============================================================ */
    public function historialProductos(string $inst = '1'): void{
        AuthGuard::check();
        $instId = inst_actual((int)$inst);
        $user = auth_user();
        $mUser = new ModelUsuario();

        $this->view('despachos.historial', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Historial de Productos',
            'instId'    => $instId,
            'scripts'   => ['historial-productos.js'],
        ]);
    }

    /* ============================================================
       LISTAR · Resumen de productos con stock y despachos
       ============================================================ */
    public function historialListar(string $inst = '1'): void{
        AuthGuard::check();
        $instId = inst_actual((int)$inst);

        $q      = trim((string)($_GET['q'] ?? ''));
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $this->safe(function() use ($q, $limit, $offset, $instId){
            $r = (new ModelDespachos())->historialProductos($q, $limit, $offset, $instId);
            $this->json([
                'ok'             => true,
                'data'           => $r['data'],
                'total'          => $r['total'],
                'totalFiltrados' => $r['totalFiltrados'],
            ]);
        });
    }

    /* ============================================================
       DETALLE · Timeline paginado de un producto
       ============================================================ */
    public function historialDetalle(string $inst, string $id): void{
        AuthGuard::check();
        inst_actual((int)$inst);
        $id = (int)$id;

        $d = $this->input();
        if (empty($d)) $d = $_POST;

        $limit  = max(1, min(50, (int)($d['limit']  ?? 10)));
        $offset = max(0, (int)($d['offset'] ?? 0));

        $this->safe(function() use ($id, $limit, $offset){
            $r = (new ModelDespachos())->historialDetalle($id, $limit, $offset);

            if (!$r['producto']){
                $this->json(['ok' => false, 'message' => 'Producto no encontrado.'], 404);
                return;
            }

            $this->json([
                'ok'             => true,
                'producto'       => $r['producto'],
                'data'           => $r['data'],
                'total'          => $r['total'],
                'totalFiltrados' => $r['total'],
                'page'           => (int)floor($offset / $limit) + 1,
                'limit'          => $limit,
            ]);
        });
    }
}