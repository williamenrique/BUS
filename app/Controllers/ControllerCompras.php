<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ModelCompras;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;

class ControllerCompras extends Controller{

    /* ============================================================
       VISTA
       ============================================================ */
    public function index(string $inst = '1'): void{
        AuthGuard::check();
        $instId = inst_actual((int)$inst);
        $user = auth_user();
        $mUser = new ModelUsuario();

        $this->view('compras.index', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Compras',
            'instId'    => $instId,
            'scripts'   => ['compras.js'],
        ]);
    }

    /* ============================================================
       PENDIENTES
       ============================================================ */
    public function pendientes(string $inst = '1'): void{
        AuthGuard::check();
        $instId = inst_actual((int)$inst);

        $q      = trim((string)($_GET['q'] ?? ''));
        $limit  = max(1, min(5000, (int)($_GET['limit'] ?? 10)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $this->safe(function() use ($q, $limit, $offset, $instId){
            $r = (new ModelCompras())->pendientes($q, $limit, $offset, $instId);
            $this->json([
                'ok'             => true,
                'data'           => $r['data'],
                'total'          => $r['total'],
                'totalFiltrados' => $r['totalFiltrados'],
            ]);
        });
    }

    /* ============================================================
       COSTEADAS
       ============================================================ */
    public function costeadas(string $inst = '1'): void{
        AuthGuard::check();
        $instId = inst_actual((int)$inst);

        $q      = trim((string)($_GET['q'] ?? ''));
        $limit  = max(1, min(5000, (int)($_GET['limit'] ?? 10)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $filtros = [
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
        ];

        $this->safe(function() use ($q, $limit, $offset, $filtros, $instId){
            $r = (new ModelCompras())->costeadas($q, $limit, $offset, $filtros, $instId);
            $this->json([
                'ok'             => true,
                'data'           => $r['data'],
                'total'          => $r['total'],
                'totalFiltrados' => $r['totalFiltrados'],
            ]);
        });
    }

    /* ============================================================
       ARTÍCULOS de un despacho
       ============================================================ */
    public function articulosDespacho(string $inst, string $id): void{
        AuthGuard::check();
        inst_actual((int)$inst);
        $id = (int)$id;

        $this->safe(function() use ($id){
            $m = new ModelCompras();
            $info = $m->infoDespacho($id);
            if (!$info){
                $this->json(['ok' => false, 'message' => 'Despacho no encontrado.'], 404);
                return;
            }
            $this->json([
                'ok'       => true,
                'info'     => $info,
                'articulos'=> $m->articulosPendientes($id),
            ]);
        });
    }

    /* ============================================================
       ASIGNAR COSTO
       ============================================================ */
    public function asignarCosto(string $inst = '1'): void{
        AuthGuard::check();
        $instId = inst_actual((int)$inst);
        $user = auth_user();

        $d = $this->input();
        $idDespacho = (int)($d['id_despacho'] ?? 0);
        $tasa       = (float)($d['tasa'] ?? 0);
        $articulos  = $d['articulos'] ?? [];

        if ($idDespacho <= 0 || $tasa <= 0 || empty($articulos)){
            $this->json(['ok' => false, 'message' => 'Datos incompletos o tasa inválida.'], 422);
            return;
        }

        $this->safe(function() use ($idDespacho, $tasa, $articulos, $user, $instId){
            $n = (new ModelCompras())->asignarCosto($idDespacho, $tasa, $articulos, (int)$user['id'], $instId);
            if ($n <= 0){
                $this->json(['ok' => false, 'message' => 'No se pudo asignar el costo.'], 500);
                return;
            }
            $this->json([
                'ok'      => true,
                'message' => "Se asignó el costo a {$n} artículo(s).",
                'count'   => $n,
            ]);
        });
    }

    /* ============================================================
       DETALLE costeado
       ============================================================ */
    public function detalleCosteado(string $inst, string $id): void{
        AuthGuard::check();
        inst_actual((int)$inst);
        $id = (int)$id;

        $this->safe(function() use ($id){
            $m = new ModelCompras();
            $info = $m->infoDespacho($id);
            if (!$info){
                $this->json(['ok' => false, 'message' => 'Despacho no encontrado.'], 404);
                return;
            }
            $articulos = $m->articulosCosteados($id);

            $totalDivisa = 0;
            $totalBs = 0;
            foreach ($articulos as $a){
                $totalDivisa += (float)$a['monto_divisa'];
                $totalBs     += (float)$a['monto_bs'];
            }

            $this->json([
                'ok'         => true,
                'info'       => $info,
                'articulos'  => $articulos,
                'total_divisa' => $totalDivisa,
                'total_bs'     => $totalBs,
            ]);
        });
    }

    /* ============================================================
       ANULAR COSTO
       ============================================================ */
    public function anularCosto(string $inst, string $id): void{
        AuthGuard::check();
        inst_actual((int)$inst);
        $id = (int)$id;

        $this->safe(function() use ($id){
            $ok = (new ModelCompras())->anularCosto($id);
            if (!$ok){
                $this->json(['ok' => false, 'message' => 'No se pudo anular el costeo.'], 500);
                return;
            }
            $this->json(['ok' => true, 'message' => 'Costeo anulado. La orden volvió a pendientes.']);
        });
    }

    /* ============================================================
       FLOTA
       ============================================================ */
    public function flota(string $inst = '1'): void{
        AuthGuard::check();
        $instId = inst_actual((int)$inst);

        $this->safe(function() use ($instId){
            $this->json(['ok' => true, 'data' => (new ModelCompras())->flota($instId)]);
        });
    }

    /* ============================================================
       CONTEO ÓRDENES
       ============================================================ */
    public function conteoOrdenes(string $inst = '1'): void{
        AuthGuard::check();
        $instId = inst_actual((int)$inst);

        $d = $this->input();
        $idFlota    = (int)($d['id_flota'] ?? 0);
        $fechaDesde = !empty($d['fecha_desde']) ? $d['fecha_desde'] : null;
        $fechaHasta = !empty($d['fecha_hasta']) ? $d['fecha_hasta'] : null;

        if ($idFlota <= 0){
            $this->json(['ok' => false, 'message' => 'Selecciona una unidad.'], 422);
            return;
        }

        $this->safe(function() use ($idFlota, $fechaDesde, $fechaHasta, $instId){
            $total = (new ModelCompras())->conteoOrdenes($idFlota, $fechaDesde, $fechaHasta, $instId);
            $this->json(['ok' => true, 'total' => $total]);
        });
    }
}