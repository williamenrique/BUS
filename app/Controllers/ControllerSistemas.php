<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\ModelSistemas;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;
use App\Middleware\MenuGuard;

class ControllerSistemas extends Controller
{
    public function index(string $inst = '1'): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);
        $user   = auth_user();
        $mUser  = new ModelUsuario();

        $this->view('sistemas.index', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Sistemas · Equipos y Mantenimiento',
            'instId'    => $instId,
            'scripts'   => ['sistemas.js'],
        ]);
    }

    public function listar(string $inst = '1'): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);

        $q      = trim((string)($_GET['q'] ?? ''));
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $filtros = [
            'departamento' => $_GET['departamento'] ?? '',
            'tipo'         => $_GET['tipo']         ?? '',
            'status'       => $_GET['status']       ?? '',
            'fecha_desde'  => $_GET['fecha_desde']  ?? '',
            'fecha_hasta'  => $_GET['fecha_hasta']  ?? '',
        ];

        $this->safe(function () use ($q, $limit, $offset, $filtros, $instId) {
            $m = new ModelSistemas();
            $r = $m->listarEquiposPaginado($q, $limit, $offset, $filtros, $instId);
            $this->json([
                'ok'             => true,
                'data'           => $r['data'],
                'total'          => $r['total'],
                'totalFiltrados' => $r['totalFiltrados'],
            ]);
        });
    }

    public function combos(string $inst = '1'): void
    {
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);

        $this->safe(function () {
            $m = new ModelSistemas();
            $this->json([
                'ok'            => true,
                'departamentos' => $m->departamentos(),
                'personal'      => $m->personal(),
            ]);
        });
    }

    public function obtener(string $inst, string $id): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);

        $this->safe(function () use ($id, $instId) {
            $m   = new ModelSistemas();
            $row = $m->findEquipo((int)$id, $instId);
            if (!$row) {
                $this->json(['ok' => false, 'message' => 'Equipo no encontrado.'], 404);
                return;
            }
            $this->json(['ok' => true, 'data' => $row]);
        });
    }

    public function guardar(string $inst = '1'): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);
        $user   = auth_user();

        $d = $this->input();
        if (empty($d)) $d = $_POST;
        $d = $this->normalizarEquipo($d);

        $v = new Validator();
        $v->required('codigo_equipo', $d['codigo_equipo'] ?? '', 'Código de equipo')
          ->required('tipo_equipo',   $d['tipo_equipo']   ?? '', 'Tipo de equipo');
        if ($v->fails()) {
            $this->json(['ok' => false, 'errors' => $v->errors()], 422);
            return;
        }

        $m = new ModelSistemas();
        if ($m->existeCodigoEquipo($d['codigo_equipo'], $instId)) {
            $this->json(['ok' => false, 'errors' => ['codigo_equipo' => 'Ese código de equipo ya está registrado.']], 422);
            return;
        }

        $this->safe(function () use ($m, $d, $user, $instId) {
            $id = $m->createEquipo($d, (int)$user['id'], $instId);
            $this->json([
                'ok'      => true,
                'message' => 'Ficha técnica registrada correctamente.',
                'id'      => $id,
            ]);
        });
    }

    public function actualizar(string $inst, string $id): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);
        $user   = auth_user();
        $idEq   = (int)$id;

        $d = $this->input();
        if (empty($d)) $d = $_POST;
        $d = $this->normalizarEquipo($d);

        $v = new Validator();
        $v->required('codigo_equipo', $d['codigo_equipo'] ?? '', 'Código de equipo')
          ->required('tipo_equipo',   $d['tipo_equipo']   ?? '', 'Tipo de equipo');
        if ($v->fails()) {
            $this->json(['ok' => false, 'errors' => $v->errors()], 422);
            return;
        }

        $m = new ModelSistemas();
        if (!$m->findEquipo($idEq, $instId)) {
            $this->json(['ok' => false, 'message' => 'Equipo no encontrado.'], 404);
            return;
        }
        if ($m->existeCodigoEquipo($d['codigo_equipo'], $instId, $idEq)) {
            $this->json(['ok' => false, 'errors' => ['codigo_equipo' => 'Ese código ya está en uso por otro equipo.']], 422);
            return;
        }

        $this->safe(function () use ($m, $idEq, $d, $user, $instId) {
            $m->updateEquipo($idEq, $d, (int)$user['id'], $instId);
            $this->json(['ok' => true, 'message' => 'Ficha técnica actualizada correctamente.']);
        });
    }

    public function eliminar(string $inst, string $id): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);
        $idEq   = (int)$id;

        $this->safe(function () use ($idEq, $instId) {
            $m = new ModelSistemas();
            if (!$m->findEquipo($idEq, $instId)) {
                $this->json(['ok' => false, 'message' => 'Equipo no encontrado.'], 404);
                return;
            }
            $m->deleteEquipo($idEq);
            $this->json(['ok' => true, 'message' => 'Equipo eliminado.']);
        });
    }

    public function ficha(string $inst, string $id): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);
        $idEq   = (int)$id;

        $this->safe(function () use ($idEq, $instId) {
            $m      = new ModelSistemas();
            $equipo = $m->findEquipo($idEq, $instId);
            if (!$equipo) {
                $this->json(['ok' => false, 'message' => 'Equipo no encontrado.'], 404);
                return;
            }
            $this->json([
                'ok'            => true,
                'equipo'        => $equipo,
                'mantenimientos'=> $m->listarMantenimientoPorEquipo($idEq),
                'historial'     => $m->listarHistorialEquipo($idEq),
            ]);
        });
    }

    public function listarMantenimiento(string $inst): void
    {
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);

        $idEquipo = (int)($_GET['id_equipo'] ?? 0);
        if ($idEquipo <= 0) {
            $this->json(['ok' => false, 'message' => 'Equipo inválido.'], 422);
            return;
        }

        $this->safe(function () use ($idEquipo) {
            $m    = new ModelSistemas();
            $rows = $m->listarMantenimientoPorEquipo($idEquipo);
            $this->json(['ok' => true, 'data' => $rows]);
        });
    }

    public function guardarMantenimiento(string $inst): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);
        $user   = auth_user();

        $d = $this->input();
        if (empty($d)) $d = $_POST;
        $d = $this->normalizarMantenimiento($d);

        $v = new Validator();
        $v->required('id_equipo',          $d['id_equipo']          ?? '', 'Equipo')
          ->required('tipo_intervencion',  $d['tipo_intervencion']  ?? '', 'Tipo de intervención')
          ->required('fecha_intervencion', $d['fecha_intervencion'] ?? '', 'Fecha de intervención')
          ->required('diagnostico',        $d['diagnostico']        ?? '', 'Diagnóstico')
          ->required('accion_realizada',   $d['accion_realizada']   ?? '', 'Acción / trabajo realizado');

        if ($v->fails()) {
            $this->json(['ok' => false, 'errors' => $v->errors()], 422);
            return;
        }

        $m = new ModelSistemas();
        if (!$m->findEquipo((int)$d['id_equipo'], $instId)) {
            $this->json(['ok' => false, 'message' => 'El equipo seleccionado no existe.'], 404);
            return;
        }

        $this->safe(function () use ($m, $d, $user, $instId) {
            $idMant = $m->createMantenimiento($d, (int)$user['id'], $instId);
            $this->json([
                'ok'      => true,
                'message' => 'Intervención registrada correctamente.',
                'id'      => $idMant,
            ]);
        });
    }

    public function eliminarMantenimiento(string $inst, string $id): void
    {
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);
        $idMant = (int)$id;

        $this->safe(function () use ($idMant) {
            $m = new ModelSistemas();
            if (!$m->findMantenimiento($idMant)) {
                $this->json(['ok' => false, 'message' => 'Registro de mantenimiento no encontrado.'], 404);
                return;
            }
            $m->deleteMantenimiento($idMant);
            $this->json(['ok' => true, 'message' => 'Registro eliminado.']);
        });
    }

    private function normalizarEquipo(array $d): array
    {
        $camposMayus = [
            'codigo_equipo', 'tipo_equipo', 'marca', 'modelo',
            'serial_equipo', 'procesador', 'ram_gb', 'almacenamiento',
            'sistema_operativo', 'nombre_host', 'direccion_mac',
            'observaciones', 'status_equipo'
        ];
        foreach ($camposMayus as $campo) {
            if (isset($d[$campo]) && is_string($d[$campo])) {
                $val = trim($d[$campo]);
                $d[$campo] = $val === '' ? null : strtoupper($val);
            }
        }
        if (isset($d['direccion_ip']) && is_string($d['direccion_ip'])) {
            $ip = trim($d['direccion_ip']);
            $d['direccion_ip'] = $ip === '' ? null : $ip;
        }
        return $d;
    }

    private function normalizarMantenimiento(array $d): array
    {
        $camposMayus = [
            'tipo_intervencion', 'solicitante_nombre', 'falla_reportada',
            'diagnostico', 'accion_realizada', 'tipo_componente',
            'componente_anterior', 'componente_nuevo', 'serial_nuevo',
            'estado_intervencion', 'observaciones', 'status_equipo_post'
        ];
        foreach ($camposMayus as $campo) {
            if (isset($d[$campo]) && is_string($d[$campo])) {
                $val = trim($d[$campo]);
                $d[$campo] = $val === '' ? null : strtoupper($val);
            }
        }
        return $d;
    }
    public function obtenerMantenimiento(string $inst, string $id): void
    {
        AuthGuard::check();
        MenuGuard::check();
        inst_actual((int)$inst);

        $this->safe(function () use ($id) {
            $m   = new ModelSistemas();
            $row = $m->findMantenimiento((int)$id);
            if (!$row) {
                $this->json(['ok' => false, 'message' => 'Intervención no encontrada.'], 404);
                return;
            }
            $this->json(['ok' => true, 'data' => $row]);
        });
    }

    public function actualizarMantenimiento(string $inst, string $id): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);
        $user   = auth_user();
        $idMant = (int)$id;

        $d = $this->input();
        if (empty($d)) $d = $_POST;
        $d = $this->normalizarMantenimiento($d);

        $v = new Validator();
        $v->required('id_equipo',          $d['id_equipo']          ?? '', 'Equipo')
          ->required('tipo_intervencion',  $d['tipo_intervencion']  ?? '', 'Tipo de intervención')
          ->required('fecha_intervencion', $d['fecha_intervencion'] ?? '', 'Fecha de intervención')
          ->required('diagnostico',        $d['diagnostico']        ?? '', 'Diagnóstico')
          ->required('accion_realizada',   $d['accion_realizada']   ?? '', 'Acción / trabajo realizado');

        if ($v->fails()) {
            $this->json(['ok' => false, 'errors' => $v->errors()], 422);
            return;
        }

        $m = new ModelSistemas();
        if (!$m->findMantenimiento($idMant)) {
            $this->json(['ok' => false, 'message' => 'Registro de mantenimiento no encontrado.'], 404);
            return;
        }

        $this->safe(function () use ($m, $idMant, $d, $user, $instId) {
            $m->updateMantenimiento($idMant, $d, (int)$user['id'], $instId);
            $this->json(['ok' => true, 'message' => 'Intervención actualizada correctamente.']);
        });
    }

    public function cerrarMantenimiento(string $inst, string $id): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);
        $user   = auth_user();
        $idMant = (int)$id;

        $d = $this->input();
        if (empty($d)) $d = $_POST;

        $nota          = isset($d['nota_cierre']) ? trim((string)$d['nota_cierre']) : null;
        $activarEquipo = !empty($d['activar_equipo']);

        $this->safe(function () use ($idMant, $nota, $activarEquipo, $user, $instId) {
            $m = new ModelSistemas();
            if (!$m->cerrarMantenimiento($idMant, $nota, $activarEquipo, (int)$user['id'], $instId)) {
                $this->json(['ok' => false, 'message' => 'No se pudo cerrar la intervención.'], 404);
                return;
            }
            $this->json(['ok' => true, 'message' => 'Intervención cerrada correctamente.']);
        });
    }
    public function reporteGlobal(string $inst = '1'): void
    {
        AuthGuard::check();
        MenuGuard::check();
        $instId = inst_actual((int)$inst);

        $q = trim((string)($_GET['q'] ?? ''));
        $filtros = [
            'departamento' => $_GET['departamento'] ?? '',
            'tipo'         => $_GET['tipo']         ?? '',
            'status'       => $_GET['status']       ?? '',
            'fecha_desde'  => $_GET['fecha_desde']  ?? '',
            'fecha_hasta'  => $_GET['fecha_hasta']  ?? '',
        ];

        $this->safe(function () use ($filtros, $instId, $q) {
            $m = new ModelSistemas();
            $datos = $m->datosReporteGlobal($filtros, $instId, $q);
            $this->json([
                'ok'   => true,
                'data' => $datos,
            ]);
        });
    }
}