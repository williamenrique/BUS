<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\ModelProductos;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;
use App\Middleware\MenuGuard;

class ControllerProductos extends Controller{

    /* ============================================================
       VISTA
       Protegido con menu_id = 1201 (patrón: productos/i)
       ============================================================ */
    public function index(string $inst = '1'): void{
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);
        $user = auth_user();
        $mUser = new ModelUsuario();

        $this->view('productos.index', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Productos',
            'instId'    => $instId,
            'scripts'   => ['productos.js'],
        ]);
    }

    /* ============================================================
       LISTAR
       ============================================================ */
    public function listar(string $inst = '1'): void{
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);

        $q      = trim((string)($_GET['q'] ?? ''));
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $filtros = [
            'enlace'    => $_GET['enlace']    ?? '',
            'ubicacion' => $_GET['ubicacion'] ?? '',
            'stock'     => $_GET['stock']     ?? '',
        ];

        $this->safe(function() use ($q, $limit, $offset, $filtros, $instId){
            $m = new ModelProductos();
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
        MenuGuard::check();
        inst_actual((int)$inst);

        $this->safe(function(){
            $m = new ModelProductos();
            $this->json([
                'ok'          => true,
                'enlaces'     => $m->enlaces(),
                'proveedores' => $m->proveedores(),
                'ubicaciones' => $m->ubicaciones(),
            ]);
        });
    }

    /* ============================================================
       OBTENER
       ============================================================ */
    public function obtener(string $inst, string $id): void{
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);

        $this->safe(function() use ($id){
            $row = (new ModelProductos())->find((int)$id);
            if (!$row){
                $this->json(['ok' => false, 'message' => 'Producto no encontrado.'], 404);
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
        MenuGuard::check();
        $instId = inst_actual((int)$inst);

        $d = $this->input();
        if (empty($d)) $d = $_POST;
        $d = $this->normalizar($d);

        $v = new Validator();
        $v->required('producto',           $d['producto'] ?? '',           'Nombre')
          ->required('id_enlace_producto', $d['id_enlace_producto'] ?? '', 'Tipo')
          ->required('id_ubicacion',       $d['id_ubicacion'] ?? '',       'Ubicación')
          ->required('id_proveedor',       $d['id_proveedor'] ?? '',       'Proveedor');
        if ($v->fails()){
            $this->json(['ok' => false, 'errors' => $v->errors()], 422);
            return;
        }

        $this->safe(function() use ($d, $instId){
            $m = new ModelProductos();
            $id = $m->create($d, $instId);
            if ($id <= 0){
                $this->json(['ok' => false, 'errors' => ['producto' => 'Este producto ya existe con ese mismo tipo.']], 422);
                return;
            }
            $this->json([
                'ok'      => true,
                'message' => 'Producto registrado correctamente.',
                'id'      => $id,
            ]);
        });
    }

    /* ============================================================
       ACTUALIZAR
       ============================================================ */
    public function actualizar(string $inst, string $id): void{
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);
        $id = (int)$id;

        $d = $this->input();
        if (empty($d)) $d = $_POST;
        $d = $this->normalizar($d);

        $v = new Validator();
        $v->required('producto',           $d['producto'] ?? '',           'Nombre')
          ->required('id_enlace_producto', $d['id_enlace_producto'] ?? '', 'Tipo')
          ->required('id_ubicacion',       $d['id_ubicacion'] ?? '',       'Ubicación');
        if ($v->fails()){
            $this->json(['ok' => false, 'errors' => $v->errors()], 422);
            return;
        }

        $this->safe(function() use ($d, $id){
            $m = new ModelProductos();
            if (!$m->find($id)){
                $this->json(['ok' => false, 'message' => 'Producto no encontrado.'], 404);
                return;
            }
            $ok = $m->update($id, $d);
            if (!$ok){
                $this->json(['ok' => false, 'errors' => ['producto' => 'Ya existe otro producto con ese nombre y tipo.']], 422);
                return;
            }
            $this->json(['ok' => true, 'message' => 'Producto actualizado correctamente.']);
        });
    }

    /* ============================================================
       AGREGAR STOCK
       ============================================================ */
    public function agregarStock(string $inst, string $id): void{
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);
        $id = (int)$id;

        $d = $this->input();
        $cantidad = (float)($d['cantidad'] ?? 0);

        if ($cantidad <= 0){
            $this->json(['ok' => false, 'message' => 'La cantidad debe ser mayor a 0.'], 422);
            return;
        }

        $this->safe(function() use ($id, $cantidad){
            $m = new ModelProductos();
            if (!$m->find($id)){
                $this->json(['ok' => false, 'message' => 'Producto no encontrado.'], 404);
                return;
            }
            $m->agregarStock($id, $cantidad);
            $this->json([
                'ok'      => true,
                'message' => "Se agregaron {$cantidad} unidades al stock."
            ]);
        });
    }

    /* ============================================================
       ELIMINAR
       ============================================================ */
    public function eliminar(string $inst, string $id): void{
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);
        $id = (int)$id;

        $this->safe(function() use ($id){
            $m = new ModelProductos();
            if (!$m->find($id)){
                $this->json(['ok' => false, 'message' => 'Producto no encontrado.'], 404);
                return;
            }
            $m->delete($id);
            $this->json(['ok' => true, 'message' => 'Producto eliminado.']);
        });
    }

    /* ============================================================
       HELPERS
       ============================================================ */
    private function normalizar(array $d): array{
        if (isset($d['producto'])){
            $d['producto'] = strtoupper(trim((string)$d['producto']));
        }
        return $d;
    }
        /* ============================================================
       PDF · PRODUCTOS POR ESTADO DE STOCK
       ============================================================ */
    public function pdfPorEstado(string $inst = '1'): void{
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);

        $this->safe(function() use ($instId){
            $m = new ModelProductos();
            $grupos = $m->productosPorEstadoStock($instId);

            $total = count($grupos['Sin stock'])
                   + count($grupos['Stock bajo'])
                   + count($grupos['Con stock']);

            if ($total === 0){
                $this->json([
                    'ok'      => false,
                    'message' => 'No hay productos para generar el PDF.'
                ], 422);
                return;
            }

            $this->json([
                'ok'     => true,
                'grupos' => $grupos,
                'conteos' => [
                    'sin_stock' => count($grupos['Sin stock']),
                    'bajo'      => count($grupos['Stock bajo']),
                    'ok'        => count($grupos['Con stock']),
                    'total'     => $total,
                ],
            ]);
        });
    }
}