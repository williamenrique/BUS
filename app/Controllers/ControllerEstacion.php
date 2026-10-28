<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ModelEstacion;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;
use App\Middleware\MenuGuard;

class ControllerEstacion extends Controller{

    /* ============================================================
       HELPERS
       ============================================================ */
    private function esSistema(): bool{
        $u = auth_user();
        return (int)($u['departamento_id'] ?? 0) === DEPARTAMENTO_SISTEMA;
    }

    private function esEstacion(): bool{
        $u = auth_user();
        return (int)($u['departamento_id'] ?? 0) === DEPARTAMENTO_ESTACION;
    }

    private function idEstacion(): int{
        $u = auth_user();
        if (!empty($u['estacion_id'])) return (int)$u['estacion_id'];
        try{
            $mEst = new ModelEstacion();
            $idEst = $mEst->obtenerEstacionSesion(session_id());
            if ($idEst > 0) $_SESSION['user']['estacion_id'] = $idEst;
            return $idEst;
        } catch (\Throwable $e){
            error_log('ControllerEstacion::idEstacion: ' . $e->getMessage());
            return 0;
        }
    }

    private function query(string $key, $default = null){
        if (!isset($_GET[$key])) return $default;
        $val = $_GET[$key];
        return is_string($val) ? trim($val) : $val;
    }

    /* ============================================================
       SELECCIÓN DE ESTACIÓN
       ============================================================ */
    public function seleccionar(): void{
        AuthGuard::check();
        $u = auth_user();
        if ($this->esSistema()){ $this->redirect('dashboard'); return; }
        if ($this->idEstacion() > 0){ $this->redirect('estacion/registrar'); return; }

        $mUser = new ModelUsuario();
        $mEst  = new ModelEstacion();

        $this->view('estacion.seleccionar', [
            'user'        => $u,
            'menu'        => $mUser->getMenuJerarquico((int)$u['id'], (int)$u['departamento_id']),
            'pageTitle'   => 'Seleccionar estación',
            'estaciones'  => $mEst->estaciones(),
            'scripts'     => ['estacion-seleccionar.js'],
        ], false);
    }

    public function guardarSeleccion(): void{
        AuthGuard::check();
        $d = $this->input();
        $idEstacion = (int)($d['id_estacion'] ?? 0);

        if ($idEstacion <= 0){ $this->json(['ok' => false, 'message' => 'Selecciona una estación.'], 422); return; }

        $mEst = new ModelEstacion();
        $est  = $mEst->findEstacion($idEstacion);
        if (!$est){ $this->json(['ok' => false, 'message' => 'Estación inválida.'], 422); return; }

        $u = auth_user();
        $idUser = (int)$u['id'];

        $idEstAbierta = $mEst->estacionAbiertaHoy($idUser);
        if ($idEstAbierta > 0 && $idEstAbierta !== $idEstacion){
            $this->json([
                'ok' => false,
                'message' => 'Tienes ventas abiertas en otra estación. Ciérralas antes de cambiar.'
            ], 422);
            return;
        }

        $actual = $mEst->obtenerEstacionSesion(session_id());
        if ($actual > 0 && $actual !== $idEstacion){
            $this->json(['ok' => false, 'message' => 'Ya tienes una estación asignada en esta sesión. Cierra sesión para cambiarla.'], 422);
            return;
        }

        $this->safe(function() use ($idEstacion, $est, $u, $idUser){
            $mEst = new ModelEstacion();
            $sid  = session_id();

            error_log("ControllerEstacion::guardarSeleccion — sid={$sid} idEstacion={$idEstacion} idUser={$idUser}");

            $ok = $mEst->guardarEstacionSesion($sid, $idEstacion, $idUser);
            if (!$ok){
                $this->json(['ok' => false, 'message' => 'No se pudo guardar la estación. La sesión no existe en BD.'], 500);
                return;
            }

            $_SESSION['user']['estacion_id'] = $idEstacion;
            $_SESSION['user']['estacion']    = $est['estacion'];

            $this->json([
                'ok'       => true,
                'message'  => 'Estación seleccionada: ' . $est['estacion'],
                'redirect' => base_url('estacion/registrar')
            ]);
        });
    }

    /* ============================================================
       VENTA (vista normal de estación — SIN MenuGuard)
       ============================================================ */
    public function registrar(): void{
        AuthGuard::check();
        // JZ-10 y cualquier usuario que no sea Estación ni Sistema recibe 403 bonito
        if (!$this->esEstacion() && !$this->esSistema()){
            MenuGuard::denegar();
        }
        $u = auth_user();
        if ($this->esEstacion() && $this->idEstacion() === 0){
            $this->redirect('estacion/seleccionar');
        }

        $mUser = new ModelUsuario();
        $this->view('estacion.registrar', [
            'user'      => $u,
            'menu'      => $mUser->getMenuJerarquico((int)$u['id'], (int)$u['departamento_id']),
            'pageTitle' => 'Estación · Registrar venta',
            'scripts'   => ['estacion.js'],
        ]);
    }

    public function initialData(): void{
        AuthGuard::check();
        $u = auth_user();
        $idUser = (int)$u['id'];
        $idEst  = $this->idEstacion();
        $hoy    = date('Y-m-d');

        $this->safe(function() use ($idUser, $idEst, $hoy, $u){
            $m = new ModelEstacion();

            $usuarioNombre   = $u['nombre']   ?? '';
            $usuarioApellido = $u['apellido'] ?? '';

            if ($usuarioNombre === '' || $usuarioApellido === ''){
                $nombre = $m->nombreVendedor($idUser);
                $usuarioNombre   = $nombre['nombre']   ?: $usuarioNombre;
                $usuarioApellido = $nombre['apellido'] ?: $usuarioApellido;
            }

            $estacionNombre = $u['estacion'] ?? '';
            if ($estacionNombre === '' && $idEst > 0){
                $est = $m->findEstacion($idEst);
                $estacionNombre = $est['estacion'] ?? '';
            }

            $this->json([
                'ok'                => true,
                'tiposVehiculo'     => $m->tiposVehiculo(),
                'tiposPago'         => $m->tiposPago(),
                'tiposCombustible'  => $m->tiposCombustible(),
                'tasa'              => $m->getTasa(),
                'ultimosTickets'    => $m->ultimosTickets($idUser, $hoy, $idEst),
                'resumen'           => $m->resumenDia($idUser, $hoy, $idEst),
                'ventasPendientes'  => $m->ventasPendientes($idUser, $idEst),
                'idUser'            => $idUser,
                'idEstacion'        => $idEst,
                'estacionNombre'    => $estacionNombre,
                'esSistema'         => $this->esSistema(),
                'usuarioNombre'     => $usuarioNombre,
                'usuarioApellido'   => $usuarioApellido,
            ]);
        });
    }

    public function updateTasa(): void{
        AuthGuard::check();
        $d = $this->input();
        $tasa = (float)($d['tasa'] ?? 0);
        if ($tasa <= 0){ $this->json(['ok' => false, 'message' => 'Tasa inválida.'], 422); return; }
        $isAdmin = $this->esSistema();
        $this->safe(function() use ($tasa, $isAdmin){
            $m = new ModelEstacion();
            $res = $m->updateTasa($tasa, $isAdmin);
            if ($res === 'already_updated'){ $this->json(['ok' => false, 'message' => 'La tasa ya fue modificada hoy.'], 422); return; }
            $this->json(['ok' => true, 'message' => 'Tasa actualizada.']);
        });
    }

    public function registrarVenta(): void{
        AuthGuard::check();
        $d = $this->input();
        if (empty($d)) $d = $_POST;

        $tipoVehiculo    = (int)($d['tipo_vehiculo'] ?? 0);
        $tipoCombustible = (int)($d['tipo_combustible'] ?? 1);
        $litros          = (float)($d['litros'] ?? 0);
        $tipoPago        = (int)($d['tipo_pago'] ?? 0);
        $monto           = (float)($d['monto'] ?? 0);
        $tasa            = (float)($d['tasa'] ?? 0);

        if ($tipoVehiculo <= 0 || $litros <= 0 || $tipoPago <= 0 || $monto <= 0 || $tasa <= 0){
            $this->json(['ok' => false, 'message' => 'Datos incompletos.'], 422);
            return;
        }

        $u = auth_user();
        $idUser = (int)$u['id'];
        $idEst  = $this->idEstacion();
        if ($idEst === 0){ $this->json(['ok' => false, 'message' => 'No tienes estación asignada.'], 422); return; }

        $this->safe(function() use ($idUser, $idEst, $tipoVehiculo, $tipoCombustible, $litros, $tipoPago, $monto, $tasa){
            $m = new ModelEstacion();
            $num = $m->setVenta($idUser, $idEst, $tipoVehiculo, $tipoCombustible, $litros, $tipoPago, $monto, $tasa);
            if ($num <= 0){ $this->json(['ok' => false, 'message' => 'No se pudo registrar la venta.'], 500); return; }
            $ticket = $m->getTicketData($num, $idUser, date('Y-m-d'), $idEst);
            $this->json(['ok' => true, 'message' => 'Venta registrada. Ticket #' . $num, 'numTicket' => $num, 'ticketData' => $ticket]);
        });
    }

    public function cerrarDia(): void{
        AuthGuard::check();
        $d = $this->input();
        $fecha = $d['fecha'] ?? date('Y-m-d');
        $u = auth_user();
        $idUser = (int)$u['id'];
        $idEst  = $this->idEstacion();

        $this->safe(function() use ($idUser, $idEst, $fecha){
            $m = new ModelEstacion();
            $idCierre = $m->setDailyCierre($idUser, $fecha, $idEst);
            $this->json([
                'ok'           => true,
                'message'      => 'Día cerrado.',
                'idCierre'     => $idCierre,
                'dataCierre'   => $m->dataCierre($idCierre),
                'dataDetallado'=> $m->getDetallado($idUser, $fecha, $idEst, false),
            ]);
        });
    }

    public function cerrarTurnoPendiente(): void{
        AuthGuard::check();
        $d = $this->input();
        $fecha  = $d['fecha'] ?? '';
        $idUser = (int)($d['id_user'] ?? 0);
        if ($fecha === '' || $idUser <= 0){ $this->json(['ok' => false, 'message' => 'Datos incompletos.'], 422); return; }

        $this->safe(function() use ($idUser, $fecha){
            $m = new ModelEstacion();
            $uInfo = $m->usuarioInfo($idUser);
            $idEst = (int)($uInfo['usuario_estacion_id'] ?? 0);
            $idCierre = $m->setDailyCierre($idUser, $fecha, $idEst);
            $this->json([
                'ok'           => true,
                'message'      => 'Turno cerrado.',
                'idCierre'     => $idCierre,
                'dataCierre'   => $m->dataCierre($idCierre),
                'dataDetallado'=> $m->getDetallado($idUser, $fecha, $idEst, false),
            ]);
        });
    }

    public function getDetalleVentas(): void{
        AuthGuard::check();
        $d = $this->input();
        $fecha  = $d['fecha'] ?? date('Y-m-d');
        $idUser = (int)($d['id_user'] ?? 0);
        $this->safe(function() use ($idUser, $fecha){
            $m = new ModelEstacion();
            $uInfo = $m->usuarioInfo($idUser);
            $idEst = (int)($uInfo['usuario_estacion_id'] ?? 0);
            $this->json(['ok' => true, 'data' => $m->getDetallado($idUser, $fecha, $idEst, false)]);
        });
    }

    public function getTicket(string $id): void{
        AuthGuard::check();
        $d = $this->input();
        $idUser  = (int)($d['id_user'] ?? 0);
        $fecha   = $d['fecha'] ?? date('Y-m-d');
        $idVenta = (int)$id;

        $this->safe(function() use ($idVenta, $idUser, $fecha){
            $m = new ModelEstacion();
            $uInfo = $m->usuarioInfo($idUser);
            $idEst = (int)($uInfo['usuario_estacion_id'] ?? 0);
            $ticket = $m->getTicketData($idVenta, $idUser, $fecha, $idEst);
            if (!$ticket){ $this->json(['ok' => false, 'message' => 'Ticket no encontrado.'], 404); return; }
            $this->json(['ok' => true, 'ticketData' => $ticket]);
        });
    }

    public function getTicketByVenta(string $id): void{
        AuthGuard::check();
        $idVenta = (int)$id;
        if ($idVenta <= 0){
            $this->json(['ok' => false, 'message' => 'ID de venta inválido.'], 422);
            return;
        }

        $this->safe(function() use ($idVenta){
            $m = new ModelEstacion();
            $ticket = $m->getTicketById($idVenta);
            if (!$ticket){
                $this->json(['ok' => false, 'message' => 'Ticket no encontrado.'], 404);
                return;
            }
            $this->json(['ok' => true, 'ticketData' => $ticket]);
        });
    }

    public function generarReportePdf(): void{
        AuthGuard::check();
        $d = $this->input();
        $fecha  = $d['fecha'] ?? date('Y-m-d');
        $idUser = (int)($d['id_user'] ?? 0);
        $this->safe(function() use ($idUser, $fecha){
            $m = new ModelEstacion();
            $uInfo = $m->usuarioInfo($idUser);
            $idEst = (int)($uInfo['usuario_estacion_id'] ?? 0);
            $this->json(['ok' => true, 'data' => [
                'detallado' => $m->getDetallado($idUser, $fecha, $idEst, true),
                'resumen'   => $m->resumenDia($idUser, $fecha, $idEst),
            ]]);
        });
    }

    public function getTasaCurrent(): void{
        AuthGuard::check();
        $this->safe(function(){
            $this->json(['ok' => true, 'tasa' => (new ModelEstacion())->getTasa()]);
        });
    }

    /* ============================================================
       DATAVENTA · Endpoints protegidos por MenuGuard
       ------------------------------------------------------------
       Solo usuarios con el menú 10 asignado (o admin Sistema)
       pueden acceder a estos endpoints.
       ============================================================ */
    public function dataventa(): void{
        AuthGuard::check();
        MenuGuard::check();
        $u = auth_user();
        $mUser = new ModelUsuario();
        $this->view('estacion.dataventa', [
            'user'      => $u,
            'menu'      => $mUser->getMenuJerarquico((int)$u['id'], (int)$u['departamento_id']),
            'pageTitle' => 'Estación · Data Venta',
            'scripts'   => ['dataventa.js'],
        ]);
    }

    public function getLitrosTotales(): void{
        AuthGuard::check();
        MenuGuard::check();
        $this->safe(function(){
            $this->json(['ok' => true, 'total' => (new ModelEstacion())->litrosTotales()]);
        });
    }

    public function getLitrosPorFecha(): void{
        AuthGuard::check();
        MenuGuard::check();
        $d = $this->input();
        $fecha = $d['fecha'] ?? '';
        $type  = $d['type'] ?? 'day';
        $this->safe(function() use ($fecha, $type){
            $this->json(['ok' => true, 'total' => (new ModelEstacion())->litrosPorFecha($fecha, $type)]);
        });
    }

    public function getHistorialCierres(): void{
        AuthGuard::check();
        MenuGuard::check();
        $this->safe(function(){
            $this->json(['ok' => true, 'data' => (new ModelEstacion())->historialCierres()]);
        });
    }

    public function listarCierres(): void{
        AuthGuard::check();
        MenuGuard::check();
        $q         = (string)($this->query('q', '') ?? '');
        $limit     = max(1, min(100, (int)($this->query('limit', 10) ?? 10)));
        $offset    = max(0, (int)($this->query('offset', 0) ?? 0));
        $fecha     = (string)($this->query('fecha', '') ?? '');
        $tipoFecha = (string)($this->query('tipoFecha', 'day') ?? 'day');
        $this->safe(function() use ($q, $limit, $offset, $fecha, $tipoFecha){
            $m = new ModelEstacion();
            $res = $m->listarCierresPaginado($q, $limit, $offset, ['fecha'=>$fecha,'tipoFecha'=>$tipoFecha]);
            $this->json(['ok'=>true,'data'=>$res['data'],'total'=>$res['total'],'totalFiltrados'=>$res['totalFiltrados']]);
        });
    }

    public function listarVentasAbiertasPaginado(): void{
        AuthGuard::check();
        MenuGuard::check();
        $q      = (string)($this->query('q', '') ?? '');
        $limit  = max(1, min(100, (int)($this->query('limit', 10) ?? 10)));
        $offset = max(0, (int)($this->query('offset', 0) ?? 0));
        $this->safe(function() use ($q, $limit, $offset){
            $m = new ModelEstacion();
            $res = $m->listarVentasAbiertasPaginado($q, $limit, $offset);
            $this->json(['ok'=>true,'data'=>$res['data'],'total'=>$res['total'],'totalFiltrados'=>$res['totalFiltrados']]);
        });
    }

    public function listarVentasCierre(): void{
        AuthGuard::check();
        MenuGuard::check();
        $idCierre = (int)($this->query('id_cierre', 0) ?? 0);
        if ($idCierre <= 0){ $this->json(['ok'=>false,'message'=>'id_cierre requerido.'], 422); return; }
        $q      = (string)($this->query('q', '') ?? '');
        $limit  = max(1, min(100, (int)($this->query('limit', 10) ?? 10)));
        $offset = max(0, (int)($this->query('offset', 0) ?? 0));
        $this->safe(function() use ($idCierre, $q, $limit, $offset){
            $m = new ModelEstacion();
            $res = $m->listarVentasCierrePaginado($idCierre, $q, $limit, $offset);
            $this->json(['ok'=>true,'data'=>$res['data'],'total'=>$res['total'],'totalFiltrados'=>$res['totalFiltrados']]);
        });
    }

    public function getVentasByCierre(): void{
        AuthGuard::check();
        MenuGuard::check();
        $d = $this->input();
        $idCierre = (int)($d['id_cierre'] ?? 0);
        $idUser   = (int)($d['id_user'] ?? 0);
        $fecha    = $d['fecha'] ?? '';
        $this->safe(function() use ($idCierre, $idUser, $fecha){
            $this->json(['ok'=>true,'data'=>(new ModelEstacion())->ventasPorCierre($idCierre, $idUser, $fecha)]);
        });
    }

    public function getVentasAbiertas(): void{
        AuthGuard::check();
        MenuGuard::check();
        $d = $this->input();
        $idUser = (int)($d['id_user'] ?? 0);
        $fecha  = $d['fecha'] ?? '';
        $this->safe(function() use ($idUser, $fecha){
            $m = new ModelEstacion();
            $uInfo = $m->usuarioInfo($idUser);
            $idEst = (int)($uInfo['usuario_estacion_id'] ?? 0);
            $this->json(['ok'=>true,'data'=>$m->getDetallado($idUser, $fecha, $idEst, false)]);
        });
    }

    public function getOpenSales(): void{
        AuthGuard::check();
        MenuGuard::check();
        $this->safe(function(){
            $this->json(['ok'=>true,'data'=>(new ModelEstacion())->ventasAbiertas()]);
        });
    }

    public function closeSale(string $id): void{
        AuthGuard::check();
        MenuGuard::check();
        $d = $this->input();
        $idVenta = (int)$id;
        $fecha   = $d['fecha'] ?? '';
        $idUser  = (int)($d['id_user'] ?? 0);
        $this->safe(function() use ($idVenta, $fecha, $idUser){
            $ok = (new ModelEstacion())->closeSale($idVenta, $fecha, $idUser);
            if (!$ok){ $this->json(['ok'=>false,'message'=>'No se pudo cerrar la venta.'], 500); return; }
            $this->json(['ok'=>true,'message'=>'Venta cerrada.']);
        });
    }

    public function deleteVenta(): void{
        AuthGuard::check();
        MenuGuard::check();
        $d = $this->input();
        $this->safe(function() use ($d){
            $ok = (new ModelEstacion())->deleteVenta((int)($d['id_venta']??0), $d['fecha']??'', (int)($d['id_user']??0));
            if (!$ok){ $this->json(['ok'=>false,'message'=>'No se pudo eliminar.'], 500); return; }
            $this->json(['ok'=>true,'message'=>'Venta eliminada.']);
        });
    }

    public function deleteCierre(): void{
        AuthGuard::check();
        MenuGuard::check();
        $d = $this->input();
        $this->safe(function() use ($d){
            $ok = (new ModelEstacion())->deleteCierre((int)($d['id_cierre']??0), (int)($d['id_user']??0), (int)($d['id_estacion']??0), $d['fecha']??'');
            if (!$ok){ $this->json(['ok'=>false,'message'=>'No se pudo eliminar el cierre.'], 500); return; }
            $this->json(['ok'=>true,'message'=>'Cierre eliminado y ventas reabiertas.']);
        });
    }

    public function deleteCierreTotal(): void{
        AuthGuard::check();
        MenuGuard::check();
        $d = $this->input();
        $this->safe(function() use ($d){
            $ok = (new ModelEstacion())->deleteCierreTotal((int)($d['id_cierre']??0), (int)($d['id_user']??0), $d['fecha']??'');
            if (!$ok){ $this->json(['ok'=>false,'message'=>'No se pudo eliminar.'], 500); return; }
            $this->json(['ok'=>true,'message'=>'Cierre y ventas eliminados.']);
        });
    }

    public function deleteAllOpenSales(): void{
        AuthGuard::check();
        MenuGuard::check();
        $d = $this->input();
        $this->safe(function() use ($d){
            $ok = (new ModelEstacion())->deleteAllOpenSales((int)($d['id_user']??0), $d['fecha']??'');
            if (!$ok){ $this->json(['ok'=>false,'message'=>'No se pudo eliminar.'], 500); return; }
            $this->json(['ok'=>true,'message'=>'Ventas eliminadas.']);
        });
    }

    public function generarReporteLitros(): void{
        AuthGuard::check();
        $this->json(['ok' => false, 'message' => 'Función de reporte litros se gestiona en el frontend.'], 200);
    }

    /* ============================================================
       PDF · DATOS DEL CIERRE (con validación por estación)
       ============================================================ */
    public function getDataCierre(): void{
        AuthGuard::check();
        MenuGuard::check();   // si llegó aquí, tiene acceso al menú de DataVenta

        $d = $this->input();
        $idCierre = (int)($d['id_cierre'] ?? 0);
        if ($idCierre <= 0){ $this->json(['ok'=>false,'message'=>'id_cierre requerido.'], 422); return; }

        $this->safe(function() use ($idCierre){
            $m = new ModelEstacion();
            $cierre = $m->dataCierre($idCierre);
            if (!$cierre){ $this->json(['ok'=>false,'message'=>'Cierre no encontrado.'], 404); return; }

            // Regla:
            // - Sistema → acceso total
            // - Cualquier otro usuario con menú DataVenta → acceso a cierres
            //   (ya está validado por MenuGuard, así que puede ver el cierre)
            // - Si el usuario tiene estación asignada (es de Estación),
            //   solo puede ver cierres de SU estación
            if (!$this->esSistema()){
                $idEst = $this->idEstacion();
                if ($idEst > 0 && (int)($cierre['id_estacion'] ?? 0) !== $idEst){
                    $this->json(['ok'=>false,'message'=>'No autorizado para ver este cierre.'], 403);
                    return;
                }
                // Si $idEst === 0 (usuario administrativo sin estación propia),
                // puede ver cualquier cierre (como Tesorería)
            }

            $this->json([
                'ok'         => true,
                'cierreData' => $cierre,
                'ventas'     => $m->ventasDetalleCierre($idCierre),
            ]);
        });
    }

    /* ============================================================
       PDF · REPORTE DE LITROS (filtrado por estación)
       ============================================================ */
    public function getLitrosReporte(): void{
        AuthGuard::check();
        MenuGuard::check();   // si llegó aquí, tiene acceso al menú de DataVenta

        $d = $this->input();
        $fecha    = $d['fecha'] ?? date('Y-m-d');
        $type     = $d['type'] ?? 'day';
        $fechaFin = $d['fechaFin'] ?? null;

        // Regla:
        // - Sistema → puede pedir cualquier estación
        // - Estación → solo su estación
        // - Otros con menú DataVenta (Tesoreria, etc.) → sin restricción de estación
        $idEstacion = null;

        if ($this->esSistema()){
            $estReq = $d['id_estacion'] ?? null;
            if ($estReq !== null && $estReq !== '' && (int)$estReq > 0){
                $idEstacion = (int)$estReq;
            }
        } else {
            $idEst = $this->idEstacion();
            if ($idEst > 0){
                $idEstacion = $idEst;   // Estación → solo la suya
            } else {
                // No-Sistema sin estación (Tesoreria, etc.)
                $estReq = $d['id_estacion'] ?? null;
                if ($estReq !== null && $estReq !== '' && (int)$estReq > 0){
                    $idEstacion = (int)$estReq;
                }
                // Si no pide estación → todas
            }
        }

        $this->safe(function() use ($fecha, $type, $fechaFin, $idEstacion){
            $m = new ModelEstacion();
            $data  = $m->litrosReporte($fecha, $type, $fechaFin, $idEstacion);
            $total = $m->litrosReporteTotal($fecha, $type, $fechaFin, $idEstacion);
            $lg    = $m->litrosPorCombustible($fecha, $type, $fechaFin, $idEstacion, 1);
            $ld    = $m->litrosPorCombustible($fecha, $type, $fechaFin, $idEstacion, 2);

            $this->json([
                'ok'    => true,
                'data'  => $data,
                'total' => $total,
                'fecha' => $fecha,
                'type'  => $type,
                'fechaFin'      => $fechaFin,
                'id_estacion'   => $idEstacion,
                'litrosGasolina'=> $lg,
                'litrosDiesel'  => $ld,
            ]);
        });
    }

        /* ============================================================
       PDF POR FECHA · Usuarios de la estación de la sesión
       Nunca acepta id_estacion por request.
       ============================================================ */
    public function usuariosPorEstacion(): void{
        AuthGuard::check();
        $idEst = $this->idEstacion();
        if ($idEst <= 0){
            $this->json(['ok' => false, 'message' => 'No tienes estación asignada.'], 422);
            return;
        }

        $this->safe(function() use ($idEst){
            $m = new ModelEstacion();
            $this->json(['ok' => true, 'data' => $m->usuariosPorEstacion($idEst)]);
        });
    }

    /* ============================================================
       PDF POR FECHA · Ventas de la estación + fecha (+ usuario opcional)
       - id_estacion SIEMPRE de la sesión
       - id_user (si viene) se valida contra la estación de la sesión
       - Devuelve { ok, cierreData, ventas } para que el front use SITGO_PDF
       ============================================================ */
    public function getVentasPorFecha(): void{
        AuthGuard::check();

        $d     = $this->input();
        $fecha = trim((string)($d['fecha'] ?? ''));
        $idUsr = (int)($d['id_user'] ?? 0);

        if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)){
            $this->json(['ok' => false, 'message' => 'Fecha inválida. Usa el formato YYYY-MM-DD.'], 422);
            return;
        }
        if ($fecha > date('Y-m-d')){
            $this->json(['ok' => false, 'message' => 'No se pueden generar reportes de fechas futuras.'], 422);
            return;
        }

        $idEst = $this->idEstacion();
        if ($idEst <= 0){
            $this->json(['ok' => false, 'message' => 'No tienes estación asignada.'], 422);
            return;
        }

        $this->safe(function() use ($idEst, $fecha, $idUsr){
            $m = new ModelEstacion();

            // Si viene id_user, debe pertenecer a la estación de la sesión
            if ($idUsr > 0){
                $uInfo = $m->usuarioInfo($idUsr);
                if (!$uInfo || (int)($uInfo['usuario_estacion_id'] ?? 0) !== $idEst){
                    $this->json(['ok' => false, 'message' => 'El usuario no pertenece a tu estación.'], 403);
                    return;
                }
            }

            $ventas = $m->ventasPorEstacionFecha($idEst, $fecha, $idUsr > 0 ? $idUsr : null);

            if (empty($ventas)){
                $this->json(['ok' => false, 'message' => 'No hay ventas para esa fecha.'], 422);
                return;
            }

            // Nombre del vendedor (individual) o "Todos" si no se filtró por usuario
            $estNom = '';
            $est = $m->findEstacion($idEst);
            if ($est) $estNom = $est['estacion'] ?? '';

            $nombreVendedor = '';
            if ($idUsr > 0){
                $nombreVendedor = $ventas[0]['operador'] ?? '';
            }

            // Resumen calculado en PHP (a partir de las ventas) — igual que el JS hace hoy
            $resumen = $this->calcularResumenVentas($ventas);

            $cierreData = [
                'id_cierre'        => null,
                'fecha_cierre'     => $fecha,
                'estacion'         => $estNom,
                'usuario_nombres'  => $nombreVendedor !== '' ? $nombreVendedor : 'Todos los usuarios',
                'usuario_apellidos'=> '',
                'tasa_dia'         => $ventas[0]['tasa_dia'] ?? 0,
                'total_ventas'     => $resumen['totalVentas'],
                'total_litros'     => $resumen['totalLitros'],
                'total_divisa'     => $resumen['totalDivisa'],
                'total_efectivo'   => $resumen['totalEfectivo'],
                'total_debito'     => $resumen['totalDebito'],
                'total_general_bs' => $resumen['totalGeneralBs'],
                'cant_auto'        => $resumen['cantAuto'],
                'cant_moto'        => $resumen['cantMoto'],
                'cant_camion'      => $resumen['cantCamion'],
                'cant_gasolina'    => $resumen['cantGasolina'],
                'cant_diesel'      => $resumen['cantDiesel'],
                'litros_gasolina'  => $resumen['litrosGasolina'],
                'litros_diesel'    => $resumen['litrosDiesel'],
            ];

            $this->json([
                'ok'         => true,
                'cierreData' => $cierreData,
                'ventas'     => $ventas,
            ]);
        });
    }

    /* ============================================================
       HELPER · Resumen de ventas (mismo cálculo que el JS actual)
       ============================================================ */
    private function calcularResumenVentas(array $ventas): array{
        $totalVentas = count($ventas);
        $totalLitros = 0; $totalDivisa = 0; $totalEfectivo = 0; $totalDebito = 0; $totalGeneralBs = 0;
        $cantAuto = 0; $cantMoto = 0; $cantCamion = 0;
        $cantGasolina = 0; $cantDiesel = 0;
        $litrosGasolina = 0; $litrosDiesel = 0;

        foreach ($ventas as $v){
            $litros    = (float)($v['cantidad_litros'] ?? 0);
            $efectivob = (float)($v['efectivob'] ?? 0);
            $debito    = (float)($v['tarjeta_debito'] ?? 0);
            $divisa    = (float)($v['monto_divisa'] ?? 0);

            $totalLitros    += $litros;
            $totalDivisa    += $divisa;
            $totalEfectivo  += $efectivob;
            $totalDebito    += $debito;
            $totalGeneralBs += $efectivob + $debito;

            $tv = strtolower((string)($v['tipo_vehiculo'] ?? ''));
            if (str_contains($tv, 'moto')) $cantMoto++;
            elseif (str_contains($tv, 'camion') || str_contains($tv, 'camión')) $cantCamion++;
            elseif (str_contains($tv, 'auto') || str_contains($tv, 'carro')) $cantAuto++;

            $tc = strtolower((string)($v['tipo_combustible'] ?? ''));
            if (str_contains($tc, 'gasolina')) { $cantGasolina++; $litrosGasolina += $litros; }
            elseif (str_contains($tc, 'diesel') || str_contains($tc, 'diésel')) { $cantDiesel++; $litrosDiesel += $litros; }
        }

        return compact(
            'totalVentas','totalLitros','totalDivisa','totalEfectivo','totalDebito','totalGeneralBs',
            'cantAuto','cantMoto','cantCamion','cantGasolina','cantDiesel','litrosGasolina','litrosDiesel'
        );
    }
}