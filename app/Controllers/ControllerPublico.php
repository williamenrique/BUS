<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\ModelPublico;

class ControllerPublico extends Controller{

    /* ============================================================
       VISTA PÚBLICA
       ============================================================ */
    public function index(): void{
        // Vista pública SIN AuthGuard
        $this->view('publico.movimientos', [
            'pageTitle' => 'Movimientos Públicos',
        ], false);  // false = sin layout de sesión
    }

    /* ============================================================
       HELPERS
       ============================================================ */
    private function inputJSON(): array{
        $raw = file_get_contents('php://input');
        $d = json_decode($raw, true);
        return is_array($d) ? $d : [];
    }

    /* ============================================================
       INSTITUCIONES
       ============================================================ */
    public function getInstituciones(): void{
        $this->safe(function(){
            $m = new ModelPublico();
            $inst = $m->getInstituciones();
            foreach ($inst as &$i) {
                $i['iniciales'] = $i['nombre'];
            }
            $this->json(['success' => true, 'data' => $inst]);
        });
    }

    /* ============================================================
       FLOTA
       ============================================================ */
    public function getEstadoFlota(): void{
        $d = $this->inputJSON();
        $idInst = (int)($d['id_institucion'] ?? 1);
        $this->safe(function() use ($idInst){
            $this->json([
                'success' => true,
                'data'    => (new ModelPublico())->getEstadoFlota($idInst),
            ]);
        });
    }

    public function getUnidadesPorEstado(): void{
        $d = $this->inputJSON();
        $status = $d['status'] ?? null;
        $idInst = (int)($d['id_institucion'] ?? 1);

        if (!$status){ $this->json(['success'=>false,'message'=>'Status requerido','data'=>[]], 422); return; }

        $this->safe(function() use ($status, $idInst){
            $this->json([
                'success' => true,
                'data'    => (new ModelPublico())->getUnidadesPorEstado($status, $idInst),
            ]);
        });
    }

    /* ============================================================
       ACEITE
       ============================================================ */
    public function getEstadoAceite(): void{
        $d = $this->inputJSON();
        $idInst = (int)($d['id_institucion'] ?? 1);
        $this->safe(function() use ($idInst){
            $this->json([
                'success' => true,
                'data'    => (new ModelPublico())->getEstadoAceite($idInst),
            ]);
        });
    }

    public function getUnidadesPorEstadoAceite(): void{
        $d = $this->inputJSON();
        $status = $d['status'] ?? null;
        $idInst = (int)($d['id_institucion'] ?? 1);

        if (!$status){ $this->json(['success'=>false,'message'=>'Status requerido','data'=>[]], 422); return; }

        $this->safe(function() use ($status, $idInst){
            $this->json([
                'success' => true,
                'data'    => (new ModelPublico())->getUnidadesPorEstadoAceite($status, $idInst),
            ]);
        });
    }

    /* ============================================================
       RESUMEN FLOTA
       ============================================================ */
    public function getResumenFlota(): void{
        $d = $this->inputJSON();
        $idInst = (int)($d['id_institucion'] ?? 1);
        $this->safe(function() use ($idInst){
            $this->json([
                'success' => true,
                'data'    => (new ModelPublico())->getResumenFlota($idInst),
            ]);
        });
    }

    /* ============================================================
       MOVIMIENTOS
       ============================================================ */
    public function getMovimientosData(): void{
        $d = $this->inputJSON();
        $fechaInicio = $d['fechaInicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fechaFin    = $d['fechaFin']    ?? date('Y-m-d');
        $tipo        = $d['tipoMovimiento'] ?? 'todos';
        $idInst      = (int)($d['id_institucion'] ?? 1);

        $this->safe(function() use ($fechaInicio, $fechaFin, $tipo, $idInst){
            $m = new ModelPublico();
            $movimientos = [];

            if ($tipo === 'todos' || $tipo === 'despachos'){
                foreach ($m->getDespachosPublic($fechaInicio, $fechaFin, $idInst) as $x){
                    $movimientos[] = [
                        'tipo'        => 'Despacho Almacén',
                        'fecha'       => $x['fecha_despacho'],
                        'referencia'  => 'DESP-' . str_pad($x['id_despacho'], 6, '0', STR_PAD_LEFT),
                        'id_despacho' => $x['id_despacho'],
                        'unidad'      => $x['id_unidad'] ?? 'N/A',
                        'id_flota'    => $x['id_flota'] ?? null,
                        'operador'    => $x['operador_nombre'] ?? 'N/A',
                        'mecanico'    => $x['mecanico_nombre'] ?? 'N/A',
                        'despachador' => $x['despachador_nombre'] ?? 'N/A',
                        'observacion' => $x['observacion'],
                        'estado'      => $this->getEstadoDespacho($x['estado_orden']),
                        'detalles'    => $x['productos'] ?? [],
                    ];
                }
            }

            if ($tipo === 'todos' || $tipo === 'mantenimientos'){
                foreach ($m->getMantenimientosPublic($fechaInicio, $fechaFin, $idInst) as $x){
                    $movimientos[] = [
                        'tipo'            => 'Mantenimiento Flota',
                        'fecha'           => $x['fecha_entrada'],
                        'referencia'      => 'MANT-' . str_pad($x['id_unidad_mantenimiento'], 6, '0', STR_PAD_LEFT),
                        'id_mantenimiento'=> $x['id_unidad_mantenimiento'],
                        'unidad'          => $x['id_unidad'] ?? 'N/A',
                        'id_flota'        => $x['id_flota'] ?? null,
                        'operador'        => $x['operardor_unidad'] ?? 'N/A',
                        'mecanico'        => $x['nomb_mecanico'] ?? 'N/A',
                        'despachador'     => '-',
                        'observacion'     => $x['diagnostico'] ?? '',
                        'estado'          => $this->getEstadoMantenimiento($x['status_mantenimiento']),
                        'detalles'        => [],
                    ];
                }
            }

            if ($tipo === 'todos' || $tipo === 'aceite'){
                foreach ($m->getCambiosAceitePublic($fechaInicio, $fechaFin, $idInst) as $x){
                    $movimientos[] = [
                        'tipo'       => 'Cambio Aceite',
                        'fecha'      => $x['fecha_cambio'],
                        'referencia' => 'ACE-' . str_pad($x['id_aceite_historial'], 6, '0', STR_PAD_LEFT),
                        'id_aceite'  => $x['id_aceite_historial'],
                        'unidad'     => $x['id_unidad'] ?? 'N/A',
                        'id_flota'   => $x['id_flota'] ?? null,
                        'operador'   => '-',
                        'mecanico'   => $x['usuario_nick'] ?? 'N/A',
                        'despachador'=> '-',
                        'observacion'=> 'KM: ' . number_format($x['kilometraje_cambio']) . ' | Próx: ' . number_format($x['kilometraje_proximo_cambio']) . ($x['observaciones'] ? ' | ' . $x['observaciones'] : ''),
                        'estado'     => 'Completado',
                        'detalles'   => [],
                    ];
                }
            }

            if ($tipo === 'todos' || $tipo === 'kilometraje'){
                foreach ($m->getKilometrajePublic($fechaInicio, $fechaFin, $idInst) as $x){
                    $movimientos[] = [
                        'tipo'           => 'Actualización KM',
                        'fecha'          => date('Y-m-d', strtotime($x['fecha_actualizacion'])),
                        'referencia'     => 'KM-' . str_pad($x['id_kilometraje'], 6, '0', STR_PAD_LEFT),
                        'id_kilometraje' => $x['id_kilometraje'],
                        'unidad'         => $x['id_unidad'] ?? 'N/A',
                        'id_flota'       => $x['id_flota'] ?? null,
                        'operador'       => '-',
                        'mecanico'       => $x['usuario_nick'] ?? 'N/A',
                        'despachador'    => '-',
                        'observacion'    => 'Kilometraje: ' . number_format($x['kilometraje_actual']),
                        'estado'         => 'Registrado',
                        'detalles'       => [],
                    ];
                }
            }

            usort($movimientos, fn($a, $b) => strtotime($b['fecha']) - strtotime($a['fecha']));

            $this->json([
                'success'      => true,
                'data'         => $movimientos,
                'fechaInicio'  => $fechaInicio,
                'fechaFin'     => $fechaFin,
            ]);
        });
    }

    /* ============================================================
       DETALLES
       ============================================================ */
    public function getDetalleOrden(): void{
        $d = $this->inputJSON();
        $id = $d['idDespacho'] ?? null;
        if (!$id){ $this->json(['success'=>false,'message'=>'ID requerido','data'=>[]], 422); return; }

        $this->safe(function() use ($id){
            $orden = (new ModelPublico())->getDetalleOrden($id);
            if (!$orden){ $this->json(['success'=>false,'message'=>'Orden no encontrada','data'=>[]], 404); return; }
            $this->json(['success' => true, 'data' => $orden]);
        });
    }

    public function getDetalleAceite(): void{
        $d = $this->inputJSON();
        $id = $d['idAceite'] ?? null;
        $idInst = (int)($d['id_institucion'] ?? 1);
        if (!$id){ $this->json(['success'=>false,'message'=>'ID requerido','data'=>[]], 422); return; }

        $this->safe(function() use ($id, $idInst){
            $det = (new ModelPublico())->getDetalleAceite($id);
            if (!$det){ $this->json(['success'=>false,'message'=>'Registro no encontrado','data'=>[]], 404); return; }
            $det['nombre_institucion'] = (new ModelPublico())->getNombreInstitucion($idInst);
            $this->json(['success' => true, 'data' => $det]);
        });
    }

    public function getDetalleMantenimiento(): void{
        $d = $this->inputJSON();
        $id = $d['idMantenimiento'] ?? null;
        $idInst = (int)($d['id_institucion'] ?? 1);
        if (!$id){ $this->json(['success'=>false,'message'=>'ID requerido','data'=>[]], 422); return; }

        $this->safe(function() use ($id, $idInst){
            $det = (new ModelPublico())->getDetalleMantenimiento($id);
            if (!$det){ $this->json(['success'=>false,'message'=>'No encontrado','data'=>[]], 404); return; }
            $det['nombre_institucion'] = (new ModelPublico())->getNombreInstitucion($idInst);
            $this->json(['success' => true, 'data' => $det]);
        });
    }

    public function getDetalleKilometraje(): void{
        $d = $this->inputJSON();
        $id = $d['idKilometraje'] ?? null;
        $idInst = (int)($d['id_institucion'] ?? 1);
        if (!$id){ $this->json(['success'=>false,'message'=>'ID requerido','data'=>[]], 422); return; }

        $this->safe(function() use ($id, $idInst){
            $det = (new ModelPublico())->getDetalleKilometraje($id);
            if (!$det){ $this->json(['success'=>false,'message'=>'No encontrado','data'=>[]], 404); return; }
            $det['nombre_institucion'] = (new ModelPublico())->getNombreInstitucion($idInst);
            $this->json(['success' => true, 'data' => $det]);
        });
    }

    public function getHistorialUnidad(): void{
        $d = $this->inputJSON();
        $idFlota = $d['idFlota'] ?? null;
        $idInst = (int)($d['id_institucion'] ?? 1);
        if (!$idFlota){ $this->json(['success'=>false,'message'=>'ID flota requerido','data'=>[]], 422); return; }

        $this->safe(function() use ($idFlota, $idInst, $d){
            $m = new ModelPublico();
            $u = $m->getUnidadInfo($idFlota);
            if (!$u){ $this->json(['success'=>false,'message'=>'Unidad no encontrada','data'=>[]], 404); return; }
            $u['nombre_institucion'] = $m->getNombreInstitucion($idInst);

            $hist = $m->selectHistorialUnidad($idFlota, $d, 1000);

            $this->json(['success' => true, 'data' => [
                'unidad'   => $u,
                'historial'=> $hist['items'],
                'counts'   => $hist['counts'],
            ]]);
        });
    }

    /* ============================================================
       VENTAS DE ESTACIÓN
       ============================================================ */
    public function getVentasEstacionData(): void{
        $d = $this->inputJSON();
        $fechaInicio = $d['fechaInicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fechaFin    = $d['fechaFin']    ?? date('Y-m-d');
        $estacionId  = $d['estacionId'] ?? null;

        $this->safe(function() use ($fechaInicio, $fechaFin, $estacionId){
            $m = new ModelPublico();
            $estaciones = $m->getEstacionesPublic();
            $ventas = $m->getVentasEstacionPublic($fechaInicio, $fechaFin, $estacionId);

            $grouped = [];
            $totalesComb = [];

            foreach ($ventas as $v){
                $fecha = $v['fecha_venta'];
                $estNom = $v['estacion_nombre'] ?? 'Sin Estación';
                $key = $fecha . '|' . $estNom;

                if (!isset($grouped[$key])){
                    $grouped[$key] = [
                        'fecha' => $fecha, 'estacion' => $estNom,
                        'total_litros' => 0, 'total_ventas' => 0, 'total_bs' => 0,
                        'total_divisa' => 0, 'total_efectivo' => 0, 'total_debito' => 0,
                        'litros_gasolina' => 0, 'litros_diesel' => 0,
                        'vendedores' => [], 'tipos_vehiculo' => [], 'tipos_combustible' => [], 'tipos_pago' => [],
                        'ventas' => [],
                    ];
                }

                $litros = (float)$v['litros'];
                $monto  = (float)$v['monto'];
                $tasa   = (float)$v['tasa_dia'];
                $idPago = (int)$v['id_tipo_pago'];
                $idComb = (int)($v['id_tipo_combustible'] ?? 1);
                $nomComb = $v['tipo_combustible'] ?? 'Gasolina';
                $vendedor = $v['vendedor_nombre'] ?? $v['usuario_nick'] ?? 'N/A';
                $montoBs = ($idPago === 1) ? $monto * $tasa : $monto;

                $grouped[$key]['total_litros'] += $litros;
                $grouped[$key]['total_ventas'] += 1;
                $grouped[$key]['total_bs'] += $montoBs;

                if ($idPago === 1) $grouped[$key]['total_divisa'] += $monto;
                if ($idPago === 2) $grouped[$key]['total_efectivo'] += $monto;
                if ($idPago === 3) $grouped[$key]['total_debito'] += $monto;

                if ($idComb === 1) $grouped[$key]['litros_gasolina'] += $litros;
                if ($idComb === 2) $grouped[$key]['litros_diesel'] += $litros;

                if (!isset($totalesComb[$nomComb])){
                    $totalesComb[$nomComb] = ['cantidad'=>0,'litros'=>0,'monto'=>0];
                }
                $totalesComb[$nomComb]['cantidad']++;
                $totalesComb[$nomComb]['litros'] += $litros;
                $totalesComb[$nomComb]['monto'] += $montoBs;

                if (!isset($grouped[$key]['vendedores'][$vendedor])){
                    $grouped[$key]['vendedores'][$vendedor] = ['litros'=>0,'ventas'=>0,'monto'=>0];
                }
                $grouped[$key]['vendedores'][$vendedor]['litros'] += $litros;
                $grouped[$key]['vendedores'][$vendedor]['ventas']++;
                $grouped[$key]['vendedores'][$vendedor]['monto'] += $montoBs;

                $tipoVeh = $v['tipo_vehiculo'] ?? 'N/A';
                if (!isset($grouped[$key]['tipos_vehiculo'][$tipoVeh])){
                    $grouped[$key]['tipos_vehiculo'][$tipoVeh] = ['cantidad'=>0,'litros'=>0];
                }
                $grouped[$key]['tipos_vehiculo'][$tipoVeh]['cantidad']++;
                $grouped[$key]['tipos_vehiculo'][$tipoVeh]['litros'] += $litros;

                if (!isset($grouped[$key]['tipos_combustible'][$nomComb])){
                    $grouped[$key]['tipos_combustible'][$nomComb] = ['cantidad'=>0,'litros'=>0,'monto'=>0];
                }
                $grouped[$key]['tipos_combustible'][$nomComb]['cantidad']++;
                $grouped[$key]['tipos_combustible'][$nomComb]['litros'] += $litros;
                $grouped[$key]['tipos_combustible'][$nomComb]['monto'] += $montoBs;

                $nomPago = $v['tipo_pago'] ?? 'N/A';
                if (!isset($grouped[$key]['tipos_pago'][$nomPago])){
                    $grouped[$key]['tipos_pago'][$nomPago] = ['cantidad'=>0,'monto'=>0];
                }
                $grouped[$key]['tipos_pago'][$nomPago]['cantidad']++;
                $grouped[$key]['tipos_pago'][$nomPago]['monto'] += $montoBs;

                $grouped[$key]['ventas'][] = [
                    'id_venta'         => (int)$v['id_venta'],
                    'hora_venta'       => $v['hora_venta'],
                    'vendedor'         => $vendedor,
                    'tipo_vehiculo'    => $tipoVeh,
                    'tipo_combustible' => $nomComb,
                    'tipo_pago'        => $nomPago,
                    'litros'           => $litros,
                    'monto'            => $monto,
                    'monto_bs'         => $montoBs,
                    'tasa_dia'         => $tasa,
                ];
            }

            $data = array_values($grouped);
            foreach ($data as &$item){
                $item['vendedores'] = array_map(fn($v,$k)=>['nombre'=>$k,'litros'=>$v['litros'],'ventas'=>$v['ventas'],'monto'=>$v['monto']], $item['vendedores'], array_keys($item['vendedores']));
                $item['tipos_vehiculo'] = array_map(fn($v,$k)=>['tipo'=>$k,'cantidad'=>$v['cantidad'],'litros'=>$v['litros']], $item['tipos_vehiculo'], array_keys($item['tipos_vehiculo']));
                $item['tipos_combustible'] = array_map(fn($v,$k)=>['tipo'=>$k,'cantidad'=>$v['cantidad'],'litros'=>$v['litros'],'monto'=>$v['monto']], $item['tipos_combustible'], array_keys($item['tipos_combustible']));
                $item['tipos_pago'] = array_map(fn($v,$k)=>['tipo'=>$k,'cantidad'=>$v['cantidad'],'monto'=>$v['monto']], $item['tipos_pago'], array_keys($item['tipos_pago']));
            }
            unset($item);

            $totalesCombList = [];
            foreach ($totalesComb as $nombre => $info){
                $totalesCombList[] = [
                    'tipo'     => $nombre,
                    'cantidad' => (int)$info['cantidad'],
                    'litros'   => (float)$info['litros'],
                    'monto'    => (float)$info['monto'],
                ];
            }

            $this->json([
                'success'             => true,
                'data'                => $data,
                'estaciones'          => $estaciones,
                'totales_combustible' => $totalesCombList,
            ]);
        });
    }

    /* ============================================================
       HELPERS INTERNOS
       ============================================================ */
    private function getEstadoDespacho($e): string{
        return [1=>'Requisición', 2=>'Aprobada por Compras', 3=>'Despachada', 4=>'Rechazada'][$e] ?? ('Desconocido (' . $e . ')');
    }

    private function getEstadoMantenimiento($s): string{
        return ['P'=>'Pendiente','E'=>'En Proceso','T'=>'Terminado','C'=>'Cancelado','A'=>'Aprobado'][$s] ?? ($s ?? 'Sin estado');
    }
        /* ============================================================
       PDF PÚBLICO · Genera PDF sin sesión
       Solo permite templates publico_*
       ============================================================ */
    public function pdfGenerar(): void{
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);

        if (!is_array($payload)){
            $this->json(['ok' => false, 'message' => 'Payload inválido.'], 422);
            return;
        }

        $view     = (string)($payload['view'] ?? '');
        $data     = $payload['data'] ?? [];
        $meta     = $payload['meta'] ?? [];
        $filename = (string)($payload['filename'] ?? 'documento.pdf');

        $permitidos = [
            'publico_movimientos',
            'publico_ventas_estacion',
            'publico_aceite',
            'flota_operatividad',   // ← para el resumen público
        ];

        if (!in_array($view, $permitidos, true)){
            $this->json(['ok' => false, 'message' => 'Template no permitido en modo público.'], 403);
            return;
        }

        $filename = preg_replace('/[^A-Za-z0-9._\-]/', '_', $filename);
        if (!preg_match('/\.pdf$/i', $filename)){
            $filename .= '.pdf';
        }

        try {
            $token = bin2hex(random_bytes(16));
            $dir = dirname(__DIR__, 2) . '/storage/pdf_tokens/';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);

            $tokenData = [
                'view'     => $view,
                'data'     => $data,
                'meta'     => $meta,
                'filename' => $filename,
                'expira'   => time() + 1800,
                'publico'  => true,  // ← flag para que /pdf/ver no pida user_id
            ];

            $tokenFile = $dir . $token . '.json';
            file_put_contents($tokenFile, json_encode($tokenData, JSON_UNESCAPED_UNICODE));

            // Limpieza de expirados
            foreach (glob($dir . '*.json') ?: [] as $f){
                $t = @json_decode(@file_get_contents($f), true);
                if (!is_array($t) || ($t['expira'] ?? 0) < time()) @unlink($f);
            }

            $this->json([
                'ok'       => true,
                'url'      => base_url('movimientos/pdf-ver?t=' . $token),
                'filename' => $filename,
            ]);
        } catch (\Throwable $e){
            error_log('ControllerPublico::pdfGenerar → ' . $e->getMessage());
            $this->json(['ok' => false, 'message' => 'Error al preparar el PDF: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       PDF PÚBLICO · Sirve el PDF desde el token
       ============================================================ */
    public function pdfVer(): void{
        $token = (string)($_GET['t'] ?? '');

        if (!preg_match('/^[a-f0-9]{32}$/', $token)){
            http_response_code(400);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Token inválido.';
            exit;
        }

        $dir  = dirname(__DIR__, 2) . '/storage/pdf_tokens/';
        $file = $dir . $token . '.json';

        if (!file_exists($file)){
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'El PDF expiró o no existe. Genera uno nuevo.';
            exit;
        }

        $tokenData = json_decode(file_get_contents($file), true);
        if (!is_array($tokenData)){
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Error al leer el token.';
            exit;
        }

        if (($tokenData['expira'] ?? 0) < time()){
            @unlink($file);
            http_response_code(410);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'El PDF expiró. Genera uno nuevo.';
            exit;
        }

        // Verificar que sea público
        if (empty($tokenData['publico'])){
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Token no autorizado.';
            exit;
        }

        try {
            @unlink($file);

            while (ob_get_level() > 0) ob_end_clean();

            $pdf = new \App\Services\PdfService();
            $pdf->generarDocumento(
                $tokenData['view'],
                is_array($tokenData['data'] ?? null) ? $tokenData['data'] : [],
                $tokenData['filename'] ?? 'documento.pdf',
                false,
                is_array($tokenData['meta'] ?? null) ? $tokenData['meta'] : []
            );
        } catch (\Throwable $e){
            error_log('ControllerPublico::pdfVer → ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Error al generar el PDF: ' . $e->getMessage();
            exit;
        }
    }
}