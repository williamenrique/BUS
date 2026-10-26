<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\ModelFlota;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;
use App\Middleware\MenuGuard;

class ControllerFlota extends Controller{

    /* ============================================================
       VISTA PRINCIPAL · FLOTA
       ============================================================ */
    public function index(string $inst = '1'): void{
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);
        $user = auth_user();
        $mUser = new ModelUsuario();

        $this->view('flota.index', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Flota',
            'instId'    => $instId,
            'scripts'   => ['flota.js'],
        ]);
    }

    /* ============================================================
       LISTAR (paginado)
       ============================================================ */
    public function listar(string $inst = '1'): void{
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);

        $q      = trim((string)($_GET['q'] ?? ''));
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $filtros = [
            'status' => $_GET['status'] ?? '',
            'marca'  => $_GET['marca']  ?? '',
        ];

        try{
            $m = new ModelFlota();
            $r = $m->listarPaginado($q, $limit, $offset, $filtros, $instId);
            $this->json([
                'ok'             => true,
                'data'           => $r['data'],
                'total'          => $r['total'],
                'totalFiltrados' => $r['totalFiltrados'],
            ]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       TODAS
       ============================================================ */
    public function todas(string $inst = '1'): void{
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);
        try{
            $m = new ModelFlota();
            $this->json(['ok' => true, 'data' => $m->todas($instId)]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       COMBOS
       ============================================================ */
    public function combos(string $inst = '1'): void{
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);
        $m = new ModelFlota();
        $this->json([
            'ok'      => true,
            'marcas'  => $m->marcas(),
            'modelos' => $m->modelos(),
        ]);
    }

    /* ============================================================
       OBTENER
       ============================================================ */
    public function obtener(string $inst, string $id): void{
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);
        $row = (new ModelFlota())->find((int)$id);
        if (!$row){
            $this->json(['ok' => false, 'message' => 'Unidad no encontrada.'], 404);
            return;
        }
        $this->json(['ok' => true, 'data' => $row]);
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
        $v->required('id_unidad', $d['id_unidad'] ?? '', 'ID Unidad')
          ->required('vim_unidad', $d['vim_unidad'] ?? '', 'VIN')
          ->required('id_marca',   $d['id_marca'] ?? '',   'Marca')
          ->required('id_modelo',  $d['id_modelo'] ?? '',  'Modelo');
        if ($v->fails()){
            $this->json(['ok' => false, 'errors' => $v->errors()], 422);
            return;
        }

        $m = new ModelFlota();
        if ($m->existeUnidad($d['id_unidad'])){
            $this->json(['ok' => false, 'errors' => ['id_unidad' => 'El ID de unidad ya existe.']], 422);
            return;
        }
        if ($m->existeVin($d['vim_unidad'])){
            $this->json(['ok' => false, 'errors' => ['vim_unidad' => 'El VIN ya está registrado.']], 422);
            return;
        }

        try{
            $id = $m->create($d, $instId);
            $this->json(['ok' => true, 'message' => 'Unidad creada correctamente.', 'id' => $id]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
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
        $v->required('id_unidad', $d['id_unidad'] ?? '', 'ID Unidad')
          ->required('vim_unidad', $d['vim_unidad'] ?? '', 'VIN')
          ->required('id_marca',   $d['id_marca'] ?? '',   'Marca')
          ->required('id_modelo',  $d['id_modelo'] ?? '',  'Modelo');
        if ($v->fails()){
            $this->json(['ok' => false, 'errors' => $v->errors()], 422);
            return;
        }

        $m = new ModelFlota();
        if (!$m->find($id)){
            $this->json(['ok' => false, 'message' => 'Unidad no encontrada.'], 404);
            return;
        }
        if ($m->existeUnidad($d['id_unidad'], $id)){
            $this->json(['ok' => false, 'errors' => ['id_unidad' => 'El ID ya está en uso.']], 422);
            return;
        }
        if ($m->existeVin($d['vim_unidad'], $id)){
            $this->json(['ok' => false, 'errors' => ['vim_unidad' => 'El VIN ya está en uso.']], 422);
            return;
        }

        try{
            $m->update($id, $d);
            $this->json(['ok' => true, 'message' => 'Unidad actualizada correctamente.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       CAMBIAR STATUS
       ============================================================ */
    public function cambiarStatus(string $inst, string $id): void{
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);
        $id = (int)$id;
        $d = $this->input();

        $status = (int)($d['status_unidad'] ?? 0);
        $motivo = trim((string)($d['motivo'] ?? ''));

        if ($motivo === ''){
            $this->json(['ok' => false, 'message' => 'Debes indicar el motivo del cambio.'], 422);
            return;
        }

        $user = auth_user();
        $m = new ModelFlota();
        if (!$m->find($id)){
            $this->json(['ok' => false, 'message' => 'Unidad no encontrada.'], 404);
            return;
        }

        try{
            $m->cambiarStatus($id, $status, $motivo, (int)$user['id']);
            $this->json(['ok' => true, 'message' => 'Estado actualizado correctamente.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       HELPERS
       ============================================================ */
    private function normalizar(array $d): array{
        $upper = ['id_unidad', 'vim_unidad', 'tipo_combustible', 'transmision'];
        foreach ($upper as $k){
            if (isset($d[$k])){
                $d[$k] = strtoupper(trim((string)$d[$k]));
            }
        }
        return $d;
    }

    /* ============================================================
       VISTA · Historial
       ============================================================ */
    public function historial(string $inst, string $id): void{
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);
        $id = (int)$id;

        $m = new ModelFlota();
        $unidad = $m->find($id);
        if (!$unidad){
            http_response_code(404);
            echo 'Unidad no encontrada.';
            return;
        }

        $user = auth_user();
        $mUser = new ModelUsuario();

        $this->view('flota.historial', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Historial · ' . $unidad['id_unidad'],
            'unidad'    => $unidad,
            'idFlota'   => $id,
            'instId'    => $instId,
            'scripts'   => ['historial.js'],
        ]);
    }

    /* ============================================================
       DATA · Historial paginado
       ============================================================ */
    public function historialData(string $inst, string $id): void{
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);
        $id = (int)$id;

        $d = $this->input();
        if (empty($d)) $d = $_POST;

        $filtros = [
            'desde' => trim((string)($d['desde'] ?? '')),
            'hasta' => trim((string)($d['hasta'] ?? '')),
            'tipo'  => trim((string)($d['tipo']  ?? '')),
            'q'     => trim((string)($d['q']     ?? '')),
        ];

        $limit  = max(1, min(50, (int)($d['limit']  ?? 10)));
        $offset = max(0, (int)($d['offset'] ?? 0));

        try{
            $m = new ModelFlota();
            $r = $m->historialUnidad($id, $filtros, $limit, $offset);

            $this->json([
                'ok'             => true,
                'data'           => $r['items'],
                'total'          => $r['total'],
                'totalFiltrados' => $r['total'],
                'counts'         => $r['counts'],
                'page'           => (int)floor($offset / $limit) + 1,
                'limit'          => $limit,
            ]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       VISTA · Cambio de Aceite
       ------------------------------------------------------------
       Protegido con menu_id = 1102 (patrón: flota/aceite)
       ============================================================ */
    public function aceite(string $inst = '1'): void{
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);
        $user = auth_user();
        $mUser = new ModelUsuario();

        $this->view('flota.aceite', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Cambio de Aceite',
            'instId'    => $instId,
            'scripts'   => ['aceite.js'],
        ]);
    }

    /* ============================================================
       DATA · Aceite (paginado)
       ============================================================ */
    public function aceiteStatus(string $inst = '1'): void{
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);

        $q      = trim((string)($_GET['q'] ?? ''));
        $estado = trim((string)($_GET['estado'] ?? ''));
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        try{
            $m = new ModelFlota();
            $r = $m->aceiteStatus($q, $limit, $offset, $estado, $instId);

            $this->json([
                'ok'             => true,
                'data'           => $r['data'],
                'total'          => $r['total'],
                'totalFiltrados' => $r['totalFiltrados'],
                'counts'         => $r['counts'],
            ]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       POST · Actualizar kilometraje
       ============================================================ */
    public function setKilometraje(string $inst = '1'): void{
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);
        $d = $this->input();
        if (empty($d)) $d = $_POST;

        $idFlota = (int)($d['id_flota'] ?? 0);
        $km      = (int)($d['kilometraje_actual'] ?? 0);

        if ($idFlota <= 0 || $km <= 0){
            $this->json(['ok' => false, 'message' => 'Datos inválidos.'], 422);
            return;
        }

        $user = auth_user();
        try{
            $m = new ModelFlota();
            if (!$m->find($idFlota)){
                $this->json(['ok' => false, 'message' => 'Unidad no encontrada.'], 404);
                return;
            }
            $m->actualizarKilometraje($idFlota, $km, (int)$user['id']);
            $this->json(['ok' => true, 'message' => 'Kilometraje actualizado correctamente.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       POST · Registrar cambio de aceite
       ============================================================ */
    public function setCambioAceite(string $inst = '1'): void{
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);
        $d = $this->input();
        if (empty($d)) $d = $_POST;

        $idFlota    = (int)($d['id_flota'] ?? 0);
        $kmCambio   = (int)($d['kilometraje_cambio'] ?? 0);
        $kmAnterior = (int)($d['kilometraje_anterior'] ?? 0);
        $fecha      = trim((string)($d['fecha_cambio'] ?? ''));

        if ($idFlota <= 0 || $kmCambio <= 0 || $fecha === ''){
            $this->json(['ok' => false, 'message' => 'Datos incompletos.'], 422);
            return;
        }

        $user = auth_user();
        try{
            $m = new ModelFlota();
            if (!$m->find($idFlota)){
                $this->json(['ok' => false, 'message' => 'Unidad no encontrada.'], 404);
                return;
            }
            $m->registrarCambioAceite($idFlota, $kmCambio, $kmAnterior, $fecha, (int)$user['id']);
            $this->json(['ok' => true, 'message' => 'Cambio de aceite registrado correctamente.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       POST · Eliminar último registro
       ============================================================ */
    public function eliminarRegistroAceite(string $inst = '1'): void{
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);
        $d = $this->input();
        if (empty($d)) $d = $_POST;

        $idFlota = (int)($d['id_flota'] ?? 0);
        $tipo    = trim((string)($d['tipo'] ?? ''));

        if ($idFlota <= 0 || !in_array($tipo, ['kilometraje', 'aceite'], true)){
            $this->json(['ok' => false, 'message' => 'Datos inválidos.'], 422);
            return;
        }

        try{
            $m = new ModelFlota();
            $ok = $tipo === 'kilometraje'
                ? $m->eliminarUltimoKilometraje($idFlota)
                : $m->eliminarUltimoCambioAceite($idFlota);

            if (!$ok){
                $this->json(['ok' => false, 'message' => 'No se encontró un registro para eliminar.'], 404);
                return;
            }

            $this->json(['ok' => true, 'message' => 'Registro eliminado correctamente.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}