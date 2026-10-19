<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ModelEstacion;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;

class ControllerEstacion extends Controller{

    /* ============================================================
       VISTAS
       ============================================================ */
    public function registrar(): void{
        AuthGuard::check();
        $user = auth_user();
        $mUser = new ModelUsuario();

        $this->view('estacion.registrar', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Estación · Registrar venta',
            'scripts'   => ['estacion.js'],
        ]);
    }

    public function dataventa(): void{
        AuthGuard::check();
        $user = auth_user();
        $mUser = new ModelUsuario();

        $this->view('estacion.dataventa', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Estación · Historial',
            'scripts'   => ['dataventa.js'],
        ]);
    }

    public function mantenimiento(): void{
        AuthGuard::check();
        $user = auth_user();
        $mUser = new ModelUsuario();

        $this->view('estacion.mantenimiento', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Estación · Mantenimiento',
            'scripts'   => ['mantenimiento.js'],
        ]);
    }

    /* ============================================================
       HELPERS
       ============================================================ */
    private function esSistema(): bool{
        $u = auth_user();
        return ($u['departamento_nombre'] ?? '') === 'SISTEMA' || (int)($u['es_sistema'] ?? 0) === 1;
    }

    private function idEstacion(): int{
        $u = auth_user();
        if ($this->esSistema()) return 0;
        return (int)($u['estacion_id'] ?? 0);
    }

    /* ============================================================
       DATA INICIAL
       ============================================================ */
    public function initialData(): void{
        AuthGuard::check();

        $this->safe(function(){
            $m = new ModelEstacion();
            $u = auth_user();
            $idUser  = (int)$u['id'];
            $idEst   = $this->idEstacion();
            $hoy     = date('Y-m-d');

            $this->json([
                'ok'              => true,
                'tiposVehiculo'   => $m->tiposVehiculo(),
                'tiposPago'       => $m->tiposPago(),
                'tasa'            => $m->getTasa(),
                'ultimosTickets'  => $m->ultimosTickets($idUser, $hoy, $idEst),
                'resumen'         => $m->resumenDia($idUser, $hoy, $idEst),
                'ventasPendientes'=> $m->ventasPendientes($idUser, $idEst),
                'idUser'          => $idUser,
                'idEstacion'      => $idEst,
                'esSistema'       => $this->esSistema(),
                'estacionNombre'  => $u['departamento_nombre'] === 'SISTEMA' ? 'Administrador' : ($u['departamento_nombre'] ?? ''),
            ]);
        });
    }

    /* ============================================================
       ACTUALIZAR TASA
       ============================================================ */
    public function updateTasa(): void{
        AuthGuard::check();

        $d = $this->input();
        $tasa = (float)($d['tasa'] ?? 0);

        if ($tasa <= 0){
            $this->json(['ok' => false, 'message' => 'Ingresa una tasa válida.'], 422);
            return;
        }

        $this->safe(function() use ($tasa){
            $res = (new ModelEstacion())->updateTasa($tasa);
            if ($res === 'already_updated'){
                $this->json(['ok' => false, 'message' => 'La tasa ya fue modificada hoy.'], 422);
                return;
            }
            $this->json(['ok' => true, 'message' => 'Tasa actualizada correctamente.']);
        });
    }

    /* ============================================================
       REGISTRAR VENTA
       ============================================================ */
    public function registrarVenta(): void{
        AuthGuard::check();

        $d = $this->input();
        if (empty($d)) $d = $_POST;

        $tipoVehiculo = (int)($d['tipo_vehiculo'] ?? 0);
        $litros       = (float)($d['litros'] ?? 0);
        $tipoPago     = (int)($d['tipo_pago'] ?? 0);
        $monto        = (float)($d['monto'] ?? 0);
        $tasa         = (float)($d['tasa'] ?? 0);

        if ($tipoVehiculo <= 0 || $litros <= 0 || $tipoPago <= 0 || $monto <= 0 || $tasa <= 0){
            $this->json(['ok' => false, 'message' => 'Datos incompletos o inválidos.'], 422);
            return;
        }

        $u = auth_user();
        $idUser = (int)$u['id'];
        $idEst  = $this->idEstacion();

        if ($idEst === 0 && !$this->esSistema()){
            $this->json(['ok' => false, 'message' => 'No tienes estación asignada.'], 422);
            return;
        }

        // Si es admin, debe venir id_estacion explícito o usar la estación 1 por defecto
        if ($this->esSistema()){
            $idEst = (int)($d['id_estacion'] ?? 1);
        }

        $this->safe(function() use ($idUser, $idEst, $tipoVehiculo, $litros, $tipoPago, $monto, $tasa){
            $m = new ModelEstacion();
            $numTicket = $m->setVenta($idUser, $idEst, $tipoVehiculo, $litros, $tipoPago, $monto, $tasa);

            if ($numTicket <= 0){
                $this->json(['ok' => false, 'message' => 'No se pudo registrar la venta.'], 500);
                return;
            }

            $ticket = $m->getTicketData($numTicket, $idUser, date('Y-m-d'), $idEst);

            $this->json([
                'ok'         => true,
                'message'    => 'Venta registrada. Ticket #' . $numTicket,
                'numTicket'  => $numTicket,
                'ticketData' => $ticket,
            ]);
        });
    }

    /* ============================================================
       CERRAR DÍA
       ============================================================ */
    public function cerrarDia(): void{
        AuthGuard::check();

        $d = $this->input();
        $fecha = $d['fecha'] ?? date('Y-m-d');
        $u = auth_user();
        $idUser = (int)$u['id'];
        $idEst  = $this->idEstacion();
        if ($this->esSistema()) $idEst = (int)($d['id_estacion'] ?? 1);

        $this->safe(function() use ($idUser, $idEst, $fecha){
            $m = new ModelEstacion();

            $idCierre = $m->setDailyCierre($idUser, $fecha, $idEst);

            $dataCierre    = $m->dataCierre($idCierre);
            $dataDetallado = $m->getDetallado($idUser, $fecha, $idEst, false);

            $this->json([
                'ok'           => true,
                'message'      => 'Día cerrado correctamente.',
                'idCierre'     => $idCierre,
                'dataCierre'   => $dataCierre,
                'dataDetallado'=> $dataDetallado,
            ]);
        });
    }

    public function cerrarTurnoPendiente(): void{
        AuthGuard::check();

        $d = $this->input();
        $fecha = $d['fecha'] ?? '';
        $idUser = (int)($d['id_user'] ?? 0);

        if ($fecha === '' || $idUser <= 0){
            $this->json(['ok' => false, 'message' => 'Datos incompletos.'], 422);
            return;
        }

        $this->safe(function() use ($idUser, $fecha){
            $m = new ModelEstacion();
            $u = $m->usuarioInfo($idUser);
            $idEst = (int)($u['usuario_estacion_id'] ?? 0);

            $idCierre = $m->setDailyCierre($idUser, $fecha, $idEst);
            $dataCierre = $m->dataCierre($idCierre);
            $dataDetallado = $m->getDetallado($idUser, $fecha, $idEst, false);

            $this->json([
                'ok'           => true,
                'message'      => 'Turno cerrado correctamente.',
                'dataCierre'   => $dataCierre,
                'dataDetallado'=> $dataDetallado,
            ]);
        });
    }

    /* ============================================================
       DETALLE VENTAS / TICKET / PDF
       ============================================================ */
    public function getDetalleVentas(): void{
        AuthGuard::check();

        $d = $this->input();
        $fecha  = $d['fecha'] ?? date('Y-m-d');
        $idUser = (int)($d['id_user'] ?? 0);
        $this->safe(function() use ($idUser, $fecha){
            $m = new ModelEstacion();
            $u = $m->usuarioInfo($idUser);
            $idEst = (int)($u['usuario_estacion_id'] ?? 0);
            $this->json([
                'ok'   => true,
                'data' => $m->getDetallado($idUser, $fecha, $idEst, false),
            ]);
        });
    }

    public function getTicket(string $id): void{
        AuthGuard::check();

        $d = $this->input();
        $idUser = (int)($d['id_user'] ?? 0);
        $fecha  = $d['fecha'] ?? date('Y-m-d');
        $idVenta = (int)$id;

        $this->safe(function() use ($idVenta, $idUser, $fecha){
            $m = new ModelEstacion();
            $ticket = $m->getTicketData($idVenta, $idUser, $fecha, $this->idEstacion());
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
            $u = $m->usuarioInfo($idUser);
            $idEst = (int)($u['usuario_estacion_id'] ?? 0);

            $this->json([
                'ok'   => true,
                'data' => [
                    'detallado' => $m->getDetallado($idUser, $fecha, $idEst, true),
                    'resumen'   => $m->resumenDia($idUser, $fecha, $idEst),
                ],
            ]);
        });
    }

    /* ============================================================
       TASA ACTUAL
       ============================================================ */
    public function getTasaCurrent(): void{
        AuthGuard::check();
        $this->safe(function(){
            $this->json(['ok' => true, 'tasa' => (new ModelEstacion())->getTasa()]);
        });
    }

    /* ============================================================
       HISTORIAL DE CIERRES
       ============================================================ */
    public function getHistorialCierres(): void{
        AuthGuard::check();
        $this->safe(function(){
            $this->json(['ok' => true, 'data' => (new ModelEstacion())->historialCierres()]);
        });
    }

    public function getVentasByCierre(): void{
        AuthGuard::check();

        $d = $this->input();
        $idCierre = (int)($d['id_cierre'] ?? 0);
        $idUser   = (int)($d['id_user'] ?? 0);
        $fecha    = $d['fecha'] ?? '';

        if ($idCierre <= 0 || $idUser <= 0 || $fecha === ''){
            $this->json(['ok' => false, 'message' => 'Datos incompletos.'], 422);
            return;
        }

        $this->safe(function() use ($idCierre, $idUser, $fecha){
            $this->json([
                'ok'   => true,
                'data' => (new ModelEstacion())->ventasPorCierre($idCierre, $idUser, $fecha),
            ]);
        });
    }

    /* ============================================================
       ELIMINAR / CERRAR VENTAS
       ============================================================ */
    public function deleteVenta(): void{
        AuthGuard::check();
        $d = $this->input();
        $idVenta = (int)($d['id_venta'] ?? 0);
        $idUser  = (int)($d['id_user'] ?? 0);
        $fecha   = $d['fecha'] ?? '';

        $this->safe(function() use ($idVenta, $idUser, $fecha){
            $ok = (new ModelEstacion())->deleteVenta($idVenta, $fecha, $idUser);
            if (!$ok){
                $this->json(['ok' => false, 'message' => 'No se pudo eliminar la venta.'], 500);
                return;
            }
            $this->json(['ok' => true, 'message' => 'Venta eliminada.']);
        });
    }

    public function deleteCierre(): void{
        AuthGuard::check();
        $d = $this->input();
        $idCierre = (int)($d['id_cierre'] ?? 0);
        $idUser   = (int)($d['id_user'] ?? 0);
        $idEst    = (int)($d['id_estacion'] ?? 0);
        $fecha    = $d['fecha'] ?? '';

        $this->safe(function() use ($idCierre, $idUser, $idEst, $fecha){
            $ok = (new ModelEstacion())->deleteCierre($idCierre, $idUser, $idEst, $fecha);
            if (!$ok){
                $this->json(['ok' => false, 'message' => 'No se pudo eliminar el cierre.'], 500);
                return;
            }
            $this->json(['ok' => true, 'message' => 'Cierre eliminado y ventas reabiertas.']);
        });
    }

    public function getOpenSales(): void{
        AuthGuard::check();
        $this->safe(function(){
            $this->json(['ok' => true, 'data' => (new ModelEstacion())->ventasAbiertas()]);
        });
    }

    public function closeSale(string $id): void{
        AuthGuard::check();
        $idVenta = (int)$id;
        $this->safe(function() use ($idVenta){
            $ok = (new ModelEstacion())->cerrarVenta($idVenta);
            if (!$ok){
                $this->json(['ok' => false, 'message' => 'No se pudo cerrar la venta.'], 500);
                return;
            }
            $this->json(['ok' => true, 'message' => 'Venta cerrada.']);
        });
    }
}