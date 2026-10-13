<?php
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../../data/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class Publico extends Controllers {
    private $db;
    
    public function __construct() {
        parent::__construct();
    }
    
    public function movimientos($params = "") {
        $data = [
            'page_tag' => 'CONSULTA MOVIMIENTOS',
            'page_title' => "Dashboard Público de Movimientos",
            'page_name' => "Public/movimientos",
            'page_link' => "movimientos",
            'page_functions' => "function.movimientos.js"
        ];
        $this->views->getViews($this, "movimientos", $data);
    }
    
    public function getInstituciones() {
        try {
            $instituciones = $this->model->getInstituciones();
            foreach ($instituciones as &$inst) {
                $inst['iniciales'] = obtenerIniciales($inst['nombre']);
            }
            $arrResponse = ['success' => true, 'data' => $instituciones];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => []];
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function getMovimientosData() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $fechaInicio = $input['fechaInicio'] ?? date('Y-m-d', strtotime('-30 days'));
            $fechaFin = $input['fechaFin'] ?? date('Y-m-d');
            $tipoMovimiento = $input['tipoMovimiento'] ?? 'todos';
            $idInstitucion = intval($input['id_institucion'] ?? 1);
            
            $movimientos = [];
            
            if ($tipoMovimiento === 'todos' || $tipoMovimiento === 'despachos') {
                $despachos = $this->model->getDespachosPublic($fechaInicio, $fechaFin, $idInstitucion);
                foreach ($despachos as $d) {
                    $movimientos[] = [
                        'tipo' => 'Despacho Almacén',
                        'fecha' => $d['fecha_despacho'],
                        'referencia' => 'DESP-' . str_pad($d['id_despacho'], 6, '0', STR_PAD_LEFT),
                        'id_despacho' => $d['id_despacho'],
                        'unidad' => $d['id_unidad'] ?? 'N/A',
                        'id_flota' => $d['id_flota'] ?? null,
                        'operador' => $d['operador_nombre'] ?? 'N/A',
                        'mecanico' => $d['mecanico_nombre'] ?? 'N/A',
                        'despachador' => $d['despachador_nombre'] ?? 'N/A',
                        'observacion' => $d['observacion'],
                        'estado' => $this->getEstadoDespacho($d['estado_orden']),
                        'detalles' => $d['productos'] ?? []
                    ];
                }
            }
            
            if ($tipoMovimiento === 'todos' || $tipoMovimiento === 'mantenimientos') {
                $mantenimientos = $this->model->getMantenimientosPublic($fechaInicio, $fechaFin, $idInstitucion);
                foreach ($mantenimientos as $m) {
                    $movimientos[] = [
                        'tipo' => 'Mantenimiento Flota',
                        'fecha' => $m['fecha_entrada'],
                        'referencia' => 'MANT-' . str_pad($m['id_unidad_mantenimiento'], 6, '0', STR_PAD_LEFT),
                        'id_mantenimiento' => $m['id_unidad_mantenimiento'],
                        'unidad' => $m['id_unidad'] ?? 'N/A',
                        'id_flota' => $m['id_flota'] ?? null,
                        'operador' => $m['operardor_unidad'] ?? 'N/A',
                        'mecanico' => $m['nomb_mecanico'] ?? 'N/A',
                        'despachador' => '-',
                        'observacion' => $m['diagnostico'] ?? '',
                        'estado' => $this->getEstadoMantenimiento($m['status_mantenimiento']),
                        'detalles' => []
                    ];
                }
            }
            
            if ($tipoMovimiento === 'todos' || $tipoMovimiento === 'aceite') {
                $aceites = $this->model->getCambiosAceitePublic($fechaInicio, $fechaFin, $idInstitucion);
                foreach ($aceites as $a) {
                    $movimientos[] = [
                        'tipo' => 'Cambio Aceite',
                        'fecha' => $a['fecha_cambio'],
                        'referencia' => 'ACE-' . str_pad($a['id_aceite_historial'], 6, '0', STR_PAD_LEFT),
                        'id_aceite' => $a['id_aceite_historial'],
                        'unidad' => $a['id_unidad'] ?? 'N/A',
                        'id_flota' => $a['id_flota'] ?? null,
                        'operador' => '-',
                        'mecanico' => $a['usuario_nick'] ?? 'N/A',
                        'despachador' => '-',
                        'observacion' => 'KM: ' . number_format($a['kilometraje_cambio']) . ' | Próx: ' . number_format($a['kilometraje_proximo_cambio']) . ($a['observaciones'] ? ' | ' . $a['observaciones'] : ''),
                        'estado' => 'Completado',
                        'detalles' => []
                    ];
                }
            }
            
            if ($tipoMovimiento === 'todos' || $tipoMovimiento === 'kilometraje') {
                $kilometrajes = $this->model->getKilometrajePublic($fechaInicio, $fechaFin, $idInstitucion);
                foreach ($kilometrajes as $k) {
                    $movimientos[] = [
                        'tipo' => 'Actualización KM',
                        'fecha' => date('Y-m-d', strtotime($k['fecha_actualizacion'])),
                        'referencia' => 'KM-' . str_pad($k['id_kilometraje'], 6, '0', STR_PAD_LEFT),
                        'id_kilometraje' => $k['id_kilometraje'],
                        'unidad' => $k['id_unidad'] ?? 'N/A',
                        'id_flota' => $k['id_flota'] ?? null,
                        'operador' => '-',
                        'mecanico' => $k['usuario_nick'] ?? 'N/A',
                        'despachador' => '-',
                        'observacion' => 'Kilometraje: ' . number_format($k['kilometraje_actual']),
                        'estado' => 'Registrado',
                        'detalles' => []
                    ];
                }
            }
            
            usort($movimientos, function($a, $b) {
                return strtotime($b['fecha']) - strtotime($a['fecha']);
            });
            
            $resumen = [
                'total_movimientos' => count($movimientos),
                'despachos' => count(array_filter($movimientos, fn($m) => $m['tipo'] === 'Despacho Almacén')),
                'mantenimientos' => count(array_filter($movimientos, fn($m) => $m['tipo'] === 'Mantenimiento Flota')),
                'aceite' => count(array_filter($movimientos, fn($m) => $m['tipo'] === 'Cambio Aceite')),
                'kilometraje' => count(array_filter($movimientos, fn($m) => $m['tipo'] === 'Actualización KM')),
            ];
            
            $arrResponse = [
                'success' => true,
                'message' => 'Datos cargados correctamente',
                'data' => $movimientos,
                'resumen' => $resumen,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin
            ];
            
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error al cargar datos: ' . $e->getMessage();
            error_log("Error en PublicController::getMovimientosData: " . $e->getMessage());
        }
        
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function getDetalleAceite() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $idAceite = $input['idAceite'] ?? null;
            $idInstitucion = intval($input['id_institucion'] ?? 1);
            
            if (!$idAceite) {
                $arrResponse['message'] = 'ID de aceite requerido';
                header('Content-Type: application/json');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
            $detalle = $this->model->getDetalleAceite($idAceite);
            if (!$detalle) {
                $arrResponse['message'] = 'Registro no encontrado';
                header('Content-Type: application/json');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
            $detalle['nombre_institucion'] = $this->model->getNombreInstitucion($idInstitucion);
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => $detalle];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function getDetalleMantenimiento() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $idMantenimiento = $input['idMantenimiento'] ?? null;
            $idInstitucion = intval($input['id_institucion'] ?? 1);
            
            if (!$idMantenimiento) {
                $arrResponse['message'] = 'ID de mantenimiento requerido';
                header('Content-Type: application/json');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
            $detalle = $this->model->getDetalleMantenimiento($idMantenimiento);
            if (!$detalle) {
                $arrResponse['message'] = 'Mantenimiento no encontrado';
                header('Content-Type: application/json');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
            $detalle['nombre_institucion'] = $this->model->getNombreInstitucion($idInstitucion);
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => $detalle];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function getDetalleKilometraje() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $idKilometraje = $input['idKilometraje'] ?? null;
            $idInstitucion = intval($input['id_institucion'] ?? 1);
            
            if (!$idKilometraje) {
                $arrResponse['message'] = 'ID de kilometraje requerido';
                header('Content-Type: application/json');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
            $detalle = $this->model->getDetalleKilometraje($idKilometraje);
            if (!$detalle) {
                $arrResponse['message'] = 'Registro no encontrado';
                header('Content-Type: application/json');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
            $detalle['nombre_institucion'] = $this->model->getNombreInstitucion($idInstitucion);
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => $detalle];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function exportarPDF() {
        $input = json_decode(file_get_contents("php://input"), true);
        $movimientos = $input['movimientos'] ?? [];
        $fechaInicio = $input['fechaInicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fechaFin = $input['fechaFin'] ?? date('Y-m-d');
        $tipoMovimiento = $input['tipoMovimiento'] ?? 'todos';
        $idInstitucion = intval($input['id_institucion'] ?? 1);
        
        $nombreInstitucion = $this->model->getNombreInstitucion($idInstitucion);
        
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        
        $tiposTexto = [
            'todos' => 'Todos los Movimientos',
            'despachos' => 'Despachos de Almacén',
            'mantenimientos' => 'Mantenimientos de Flota',
            'compras' => 'Compras/Órdenes',
            'aceite' => 'Cambios de Aceite',
            'kilometraje' => 'Actualizaciones de Kilometraje'
        ];
        
        $titulo = $tiposTexto[$tipoMovimiento] ?? 'Movimientos';
        $periodo = "Desde: " . date('d/m/Y', strtotime($fechaInicio)) . " Hasta: " . date('d/m/Y', strtotime($fechaFin));
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 15mm 10mm; }
                body { font-family: Helvetica, Arial, sans-serif; font-size: 8px; color: #333; line-height: 1.3; }
                .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
                .header h1 { margin: 0; font-size: 14px; color: #2c3e50; text-transform: uppercase; }
                .header h2 { margin: 5px 0 0 0; font-size: 11px; font-weight: normal; color: #555; }
                .header p { margin: 3px 0; font-size: 9px; color: #777; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 7px; }
                th, td { border: 1px solid #ddd; padding: 4px 3px; text-align: center; vertical-align: middle; }
                th { background-color: #2c3e50; color: white; font-weight: bold; }
                .text-left { text-align: left; }
                .footer { margin-top: 20px; text-align: center; font-size: 7px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>' . htmlspecialchars($nombreInstitucion) . '</h1>
                <h2>Reporte de ' . $titulo . '</h2>
                <p>' . $periodo . ' | Generado: ' . date('d/m/Y H:i:s') . '</p>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th><th>Fecha</th><th>Referencia</th><th>Unidad</th>
                        <th>Operador</th><th>Mecánico</th><th>Observación</th><th>Estado</th>
                    </tr>
                </thead>
                <tbody>';
        
        if (!empty($movimientos)) {
            foreach ($movimientos as $mov) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($mov['tipo']) . '</td>';
                $html .= '<td>' . date('d/m/Y', strtotime($mov['fecha'])) . '</td>';
                $html .= '<td>' . htmlspecialchars($mov['referencia']) . '</td>';
                $html .= '<td>' . htmlspecialchars($mov['unidad']) . '</td>';
                $html .= '<td>' . htmlspecialchars($mov['operador']) . '</td>';
                $html .= '<td>' . htmlspecialchars($mov['mecanico']) . '</td>';
                $html .= '<td>' . htmlspecialchars($mov['observacion']) . '</td>';
                $html .= '<td>' . htmlspecialchars($mov['estado']) . '</td>';
                $html .= '</tr>';
            }
        }
        
        $html .= '</tbody></table>
            <div class="footer">
                <p>Sistema BUS Yaracuy | ' . date('d/m/Y H:i:s') . '</p>
            </div>
        </body></html>';
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream("movimientos_" . $fechaInicio . "_a_" . $fechaFin . ".pdf", ["Attachment" => false]);
    }
    
    public function getEstadoFlota() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $idInstitucion = intval($input['id_institucion'] ?? 1);
            
            $data = $this->model->getEstadoFlota($idInstitucion);
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => $data];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function getEstadoAceite() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $idInstitucion = intval($input['id_institucion'] ?? 1);
            
            $data = $this->model->getEstadoAceite($idInstitucion);
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => $data];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function getResumenFlota() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $idInstitucion = intval($input['id_institucion'] ?? 1);
            
            $data = $this->model->getResumenFlota($idInstitucion);
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => $data];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function getHistorialUnidad() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $idFlota = $input['idFlota'] ?? null;
            $idInstitucion = intval($input['id_institucion'] ?? 1);
            
            if (!$idFlota) {
                $arrResponse['message'] = 'ID de flota requerido';
                header('Content-Type: application/json');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
            $unidadInfo = $this->model->getUnidadInfo($idFlota);
            if (!$unidadInfo) {
                $arrResponse['message'] = 'Unidad no encontrada';
                header('Content-Type: application/json');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
            $unidadInfo['nombre_institucion'] = $this->model->getNombreInstitucion($idInstitucion);
            
            $postData = $input;
            $historialData = $this->model->selectHistorialUnidad($idFlota, $postData, 1000);
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => [
                'unidad' => $unidadInfo, 'historial' => $historialData['items'], 'counts' => $historialData['counts']
            ]];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function getDetalleOrden() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $idDespacho = $input['idDespacho'] ?? null;
            if (!$idDespacho) {
                $arrResponse['message'] = 'ID de despacho requerido';
                header('Content-Type: application/json');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
            $orden = $this->model->getDetalleOrden($idDespacho);
            if (!$orden) {
                $arrResponse['message'] = 'Orden no encontrada';
                header('Content-Type: application/json');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => $orden];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function getUnidadesPorEstado() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $status = $input['status'] ?? null;
            $idInstitucion = intval($input['id_institucion'] ?? 1);
            
            if (!$status) {
                $arrResponse['message'] = 'Status requerido';
                header('Content-Type: application/json');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
            
            $unidades = $this->model->getUnidadesPorEstado($status, $idInstitucion);
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => $unidades];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function getUnidadesPorEstadoAceite() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $status = $input['status'] ?? null;
            $idInstitucion = intval($input['id_institucion'] ?? 1);
            
            if (!$status) {
                $arrResponse['message'] = 'Status requerido';
                header('Content-Type: application/json');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
            
            $unidades = $this->model->getUnidadesPorEstadoAceite($status, $idInstitucion);
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => $unidades];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    /**
     * =============================================================
     * VENTAS DE ESTACIÓN - VISTA PÚBLICA
     * CAMBIO: usa `vendedor_nombre` (nombre completo de la persona)
     * en lugar de `usuario_nick` para las agrupaciones.
     * =============================================================
     */
    public function getVentasEstacionData() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => [], 'totales_combustible' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $fechaInicio = $input['fechaInicio'] ?? date('Y-m-d', strtotime('-30 days'));
            $fechaFin = $input['fechaFin'] ?? date('Y-m-d');
            $estacionId = $input['estacionId'] ?? null;
            
            $estaciones = $this->model->getEstacionesPublic();
            $ventas = $this->model->getVentasEstacionPublic($fechaInicio, $fechaFin, $estacionId);
            
            $grouped = [];
            $totalesComb = [];
            
            foreach ($ventas as $venta) {
                $fecha = $venta['fecha_venta'];
                $estacionNombre = $venta['estacion_nombre'] ?? 'Sin Estación';
                $key = $fecha . '|' . $estacionNombre;
                
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'fecha' => $fecha,
                        'estacion' => $estacionNombre,
                        'total_litros' => 0,
                        'total_ventas' => 0,
                        'total_bs' => 0,
                        'total_divisa' => 0,
                        'total_efectivo' => 0,
                        'total_debito' => 0,
                        'litros_gasolina' => 0,
                        'litros_diesel' => 0,
                        'vendedores' => [],
                        'tipos_vehiculo' => [],
                        'tipos_combustible' => [],
                        'tipos_pago' => [],
                        'ventas' => []
                    ];
                }
                
                $litros = (float)$venta['litros'];
                $monto = (float)$venta['monto'];
                $tasa = (float)$venta['tasa_dia'];
                $idTipoPago = (int)$venta['id_tipo_pago'];
                $idCombustible = (int)($venta['id_tipo_combustible'] ?? 1);
                $nombreComb = $venta['tipo_combustible'] ?? 'Gasolina';
                
                // *** CAMBIO: usar vendedor_nombre (nombre completo de la persona) con fallback ***
                $vendedor = $venta['vendedor_nombre'] ?? $venta['usuario_nick'] ?? 'N/A';
                
                $montoBs = ($idTipoPago == 1) ? $monto * $tasa : $monto;
                
                $grouped[$key]['total_litros'] += $litros;
                $grouped[$key]['total_ventas'] += 1;
                $grouped[$key]['total_bs'] += $montoBs;
                
                if ($idTipoPago == 1) $grouped[$key]['total_divisa'] += $monto;
                if ($idTipoPago == 2) $grouped[$key]['total_efectivo'] += $monto;
                if ($idTipoPago == 3) $grouped[$key]['total_debito'] += $monto;
                
                if ($idCombustible == 1) $grouped[$key]['litros_gasolina'] += $litros;
                else if ($idCombustible == 2) $grouped[$key]['litros_diesel'] += $litros;
                
                if (!isset($totalesComb[$nombreComb])) {
                    $totalesComb[$nombreComb] = ['cantidad' => 0, 'litros' => 0, 'monto' => 0];
                }
                $totalesComb[$nombreComb]['cantidad'] += 1;
                $totalesComb[$nombreComb]['litros'] += $litros;
                $totalesComb[$nombreComb]['monto'] += $montoBs;
                
                // Vendedor (ahora con nombre completo)
                if (!isset($grouped[$key]['vendedores'][$vendedor])) {
                    $grouped[$key]['vendedores'][$vendedor] = ['litros' => 0, 'ventas' => 0, 'monto' => 0];
                }
                $grouped[$key]['vendedores'][$vendedor]['litros'] += $litros;
                $grouped[$key]['vendedores'][$vendedor]['ventas'] += 1;
                $grouped[$key]['vendedores'][$vendedor]['monto'] += $montoBs;
                
                $tipoVehiculo = $venta['tipo_vehiculo'] ?? 'N/A';
                if (!isset($grouped[$key]['tipos_vehiculo'][$tipoVehiculo])) {
                    $grouped[$key]['tipos_vehiculo'][$tipoVehiculo] = ['cantidad' => 0, 'litros' => 0];
                }
                $grouped[$key]['tipos_vehiculo'][$tipoVehiculo]['cantidad'] += 1;
                $grouped[$key]['tipos_vehiculo'][$tipoVehiculo]['litros'] += $litros;
                
                if (!isset($grouped[$key]['tipos_combustible'][$nombreComb])) {
                    $grouped[$key]['tipos_combustible'][$nombreComb] = ['cantidad' => 0, 'litros' => 0, 'monto' => 0];
                }
                $grouped[$key]['tipos_combustible'][$nombreComb]['cantidad'] += 1;
                $grouped[$key]['tipos_combustible'][$nombreComb]['litros'] += $litros;
                $grouped[$key]['tipos_combustible'][$nombreComb]['monto'] += $montoBs;
                
                $nombrePago = $venta['tipo_pago'] ?? 'N/A';
                if (!isset($grouped[$key]['tipos_pago'][$nombrePago])) {
                    $grouped[$key]['tipos_pago'][$nombrePago] = ['cantidad' => 0, 'monto' => 0];
                }
                $grouped[$key]['tipos_pago'][$nombrePago]['cantidad'] += 1;
                $grouped[$key]['tipos_pago'][$nombrePago]['monto'] += $montoBs;
                
                // Guardar venta individual
                $grouped[$key]['ventas'][] = [
                    'id_venta' => (int)$venta['id_venta'],
                    'hora_venta' => $venta['hora_venta'],
                    'vendedor' => $vendedor,   // *** ahora nombre completo ***
                    'tipo_vehiculo' => $tipoVehiculo,
                    'tipo_combustible' => $nombreComb,
                    'tipo_pago' => $nombrePago,
                    'litros' => $litros,
                    'monto' => $monto,
                    'monto_bs' => $montoBs,
                    'tasa_dia' => $tasa
                ];
            }
            
            $data = array_values($grouped);
            
            foreach ($data as &$item) {
                $item['vendedores'] = array_map(function($v, $k) {
                    return ['nombre' => $k, 'litros' => $v['litros'], 'ventas' => $v['ventas'], 'monto' => $v['monto']];
                }, $item['vendedores'], array_keys($item['vendedores']));
                
                $item['tipos_vehiculo'] = array_map(function($v, $k) {
                    return ['tipo' => $k, 'cantidad' => $v['cantidad'], 'litros' => $v['litros']];
                }, $item['tipos_vehiculo'], array_keys($item['tipos_vehiculo']));
                
                $item['tipos_combustible'] = array_map(function($v, $k) {
                    return ['tipo' => $k, 'cantidad' => $v['cantidad'], 'litros' => $v['litros'], 'monto' => $v['monto']];
                }, $item['tipos_combustible'], array_keys($item['tipos_combustible']));
                
                $item['tipos_pago'] = array_map(function($v, $k) {
                    return ['tipo' => $k, 'cantidad' => $v['cantidad'], 'monto' => $v['monto']];
                }, $item['tipos_pago'], array_keys($item['tipos_pago']));
            }
            unset($item);
            
            $totalesCombList = [];
            foreach ($totalesComb as $nombre => $info) {
                $totalesCombList[] = [
                    'tipo' => $nombre,
                    'cantidad' => (int)$info['cantidad'],
                    'litros' => (float)$info['litros'],
                    'monto' => (float)$info['monto']
                ];
            }
            
            $arrResponse = [
                'success' => true, 
                'message' => 'OK', 
                'data' => $data,
                'estaciones' => $estaciones,
                'totales_combustible' => $totalesCombList
            ];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    /**
     * Genera el PDF del reporte de ventas de estación.
     */
    public function exportarVentasEstacionPDF() {
        $input = json_decode(file_get_contents("php://input"), true);
        $data = $input['data'] ?? [];
        $fechaInicio = $input['fechaInicio'] ?? date('Y-m-d', strtotime('-30 days'));
        $fechaFin = $input['fechaFin'] ?? date('Y-m-d');
        $estacionNombre = $input['estacionNombre'] ?? 'Todas las estaciones';
        
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        
        $fmt = function($v, $dec = 2) {
            return number_format((float)$v, $dec, ',', '.');
        };
        
        $granTotalLitros = 0;
        $granTotalVentas = 0;
        $granTotalBs = 0;
        $granTotalGasolina = 0;
        $granTotalDiesel = 0;
        $granTotalDivisa = 0;
        $granTotalEfectivo = 0;
        $granTotalDebito = 0;
        
        $aggCombustible = [];
        $aggVehiculo = [];
        $aggPago = [];
        
        foreach ($data as $item) {
            $granTotalLitros += (float)$item['total_litros'];
            $granTotalVentas += (int)$item['total_ventas'];
            $granTotalBs += (float)$item['total_bs'];
            $granTotalGasolina += (float)$item['litros_gasolina'];
            $granTotalDiesel += (float)$item['litros_diesel'];
            $granTotalDivisa += (float)$item['total_divisa'];
            $granTotalEfectivo += (float)$item['total_efectivo'];
            $granTotalDebito += (float)$item['total_debito'];
            
            foreach ($item['tipos_combustible'] ?? [] as $tc) {
                if (!isset($aggCombustible[$tc['tipo']])) {
                    $aggCombustible[$tc['tipo']] = ['cantidad' => 0, 'litros' => 0, 'monto' => 0];
                }
                $aggCombustible[$tc['tipo']]['cantidad'] += (int)$tc['cantidad'];
                $aggCombustible[$tc['tipo']]['litros'] += (float)$tc['litros'];
                $aggCombustible[$tc['tipo']]['monto'] += (float)$tc['monto'];
            }
            
            foreach ($item['tipos_vehiculo'] ?? [] as $tv) {
                if (!isset($aggVehiculo[$tv['tipo']])) {
                    $aggVehiculo[$tv['tipo']] = ['cantidad' => 0, 'litros' => 0];
                }
                $aggVehiculo[$tv['tipo']]['cantidad'] += (int)$tv['cantidad'];
                $aggVehiculo[$tv['tipo']]['litros'] += (float)$tv['litros'];
            }
            
            foreach ($item['tipos_pago'] ?? [] as $tp) {
                if (!isset($aggPago[$tp['tipo']])) {
                    $aggPago[$tp['tipo']] = ['cantidad' => 0, 'monto' => 0];
                }
                $aggPago[$tp['tipo']]['cantidad'] += (int)$tp['cantidad'];
                $aggPago[$tp['tipo']]['monto'] += (float)$tp['monto'];
            }
        }
        
        $periodo = "Desde: " . date('d/m/Y', strtotime($fechaInicio)) . " Hasta: " . date('d/m/Y', strtotime($fechaFin));
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                @page { margin: 15mm 10mm; }
                body { font-family: Helvetica, Arial, sans-serif; font-size: 9px; color: #333; line-height: 1.3; }
                .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
                .header h1 { margin: 0; font-size: 14px; color: #2c3e50; text-transform: uppercase; }
                .header h2 { margin: 5px 0 0 0; font-size: 11px; font-weight: normal; color: #555; }
                .header p { margin: 3px 0; font-size: 9px; color: #777; }
                .section-title { background-color: #2c3e50; color: white; font-weight: bold; padding: 5px 8px; font-size: 10px; margin-top: 12px; margin-bottom: 6px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8px; }
                th, td { border: 1px solid #ddd; padding: 4px 5px; vertical-align: middle; }
                th { background-color: #e9ecef; color: #2c3e50; font-weight: bold; text-align: center; }
                .text-left { text-align: left; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .kpi-container { text-align: center; margin-bottom: 10px; }
                .kpi-box { display: inline-block; border: 1px solid #2c3e50; border-radius: 4px; padding: 8px 12px; margin: 0 4px; min-width: 100px; background: #f8f9fa; }
                .kpi-value { font-size: 14px; font-weight: bold; color: #2c3e50; }
                .kpi-label { font-size: 8px; color: #666; text-transform: uppercase; }
                .total-row { background-color: #2c3e50; color: white; font-weight: bold; }
                .subtotal-row { background-color: #e9ecef; font-weight: bold; }
                .footer { margin-top: 20px; text-align: center; font-size: 7px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>BUS YARACUY</h1>
                <h2>Reporte de Ventas de Estación</h2>
                <p>' . $periodo . ' | Estación: ' . htmlspecialchars($estacionNombre) . '</p>
                <p>Generado: ' . date('d/m/Y H:i:s') . '</p>
            </div>
            
            <div class="kpi-container">
                <div class="kpi-box"><div class="kpi-value">' . $fmt($granTotalLitros) . ' L</div><div class="kpi-label">Litros Totales</div></div>
                <div class="kpi-box"><div class="kpi-value">' . number_format($granTotalVentas) . '</div><div class="kpi-label">Vehículos Atendidos</div></div>
                <div class="kpi-box"><div class="kpi-value">' . $fmt($granTotalBs) . ' Bs</div><div class="kpi-label">Total General</div></div>
            </div>
            
            <div class="section-title">Desglose por Tipo de Combustible</div>
            <table>
                <thead><tr><th class="text-left" style="width: 30%;">Combustible</th><th style="width: 15%;">Cantidad</th><th style="width: 20%;">Litros</th><th style="width: 35%;">Monto Bs</th></tr></thead>
                <tbody>';
        
        if (!empty($aggCombustible)) {
            foreach ($aggCombustible as $nombre => $info) {
                $html .= '<tr><td class="text-left"><strong>' . htmlspecialchars($nombre) . '</strong></td><td class="text-center">' . number_format($info['cantidad']) . '</td><td class="text-right">' . $fmt($info['litros']) . ' L</td><td class="text-right">' . $fmt($info['monto']) . ' Bs</td></tr>';
            }
            $html .= '<tr class="subtotal-row"><td class="text-left">TOTAL</td><td class="text-center">' . number_format($granTotalVentas) . '</td><td class="text-right">' . $fmt($granTotalLitros) . ' L</td><td class="text-right">' . $fmt($granTotalBs) . ' Bs</td></tr>';
        } else {
            $html .= '<tr><td colspan="4" class="text-center">Sin datos.</td></tr>';
        }
        
        $html .= '</tbody></table>
            
            <div class="section-title">Desglose por Tipo de Vehículo</div>
            <table>
                <thead><tr><th class="text-left" style="width: 40%;">Tipo de Vehículo</th><th style="width: 20%;">Cantidad</th><th style="width: 40%;">Litros</th></tr></thead>
                <tbody>';
        
        if (!empty($aggVehiculo)) {
            foreach ($aggVehiculo as $nombre => $info) {
                $html .= '<tr><td class="text-left"><strong>' . htmlspecialchars($nombre) . '</strong></td><td class="text-center">' . number_format($info['cantidad']) . '</td><td class="text-right">' . $fmt($info['litros']) . ' L</td></tr>';
            }
            $html .= '<tr class="subtotal-row"><td class="text-left">TOTAL</td><td class="text-center">' . number_format($granTotalVentas) . '</td><td class="text-right">' . $fmt($granTotalLitros) . ' L</td></tr>';
        } else {
            $html .= '<tr><td colspan="3" class="text-center">Sin datos.</td></tr>';
        }
        
        $html .= '</tbody></table>
            
            <div class="section-title">Desglose por Tipo de Pago</div>
            <table>
                <thead><tr><th class="text-left" style="width: 40%;">Tipo de Pago</th><th style="width: 20%;">Cantidad</th><th style="width: 40%;">Monto Bs</th></tr></thead>
                <tbody>';
        
        if (!empty($aggPago)) {
            foreach ($aggPago as $nombre => $info) {
                $html .= '<tr><td class="text-left"><strong>' . htmlspecialchars($nombre) . '</strong></td><td class="text-center">' . number_format($info['cantidad']) . '</td><td class="text-right">' . $fmt($info['monto']) . ' Bs</td></tr>';
            }
            $html .= '<tr class="subtotal-row"><td class="text-left">TOTAL</td><td class="text-center">' . number_format($granTotalVentas) . '</td><td class="text-right">' . $fmt($granTotalBs) . ' Bs</td></tr>';
        } else {
            $html .= '<tr><td colspan="3" class="text-center">Sin datos.</td></tr>';
        }
        
        $html .= '</tbody></table>
            
            <div class="section-title">Detalle por Fecha y Estación</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 12%;">Fecha</th>
                        <th class="text-left" style="width: 20%;">Estación</th>
                        <th style="width: 10%;">Ventas</th>
                        <th style="width: 13%;">Litros Tot.</th>
                        <th style="width: 12%;">Gasolina</th>
                        <th style="width: 12%;">Diesel</th>
                        <th style="width: 21%;">Monto Bs</th>
                    </tr>
                </thead>
                <tbody>';
        
        if (!empty($data)) {
            foreach ($data as $item) {
                $html .= '<tr><td class="text-center">' . date('d/m/Y', strtotime($item['fecha'])) . '</td><td class="text-left">' . htmlspecialchars($item['estacion']) . '</td><td class="text-center">' . number_format($item['total_ventas']) . '</td><td class="text-right">' . $fmt($item['total_litros']) . ' L</td><td class="text-right">' . $fmt($item['litros_gasolina']) . ' L</td><td class="text-right">' . $fmt($item['litros_diesel']) . ' L</td><td class="text-right">' . $fmt($item['total_bs']) . ' Bs</td></tr>';
            }
            $html .= '<tr class="total-row"><td class="text-center" colspan="2">TOTAL GENERAL</td><td class="text-center">' . number_format($granTotalVentas) . '</td><td class="text-right">' . $fmt($granTotalLitros) . ' L</td><td class="text-right">' . $fmt($granTotalGasolina) . ' L</td><td class="text-right">' . $fmt($granTotalDiesel) . ' L</td><td class="text-right">' . $fmt($granTotalBs) . ' Bs</td></tr>';
        } else {
            $html .= '<tr><td colspan="7" class="text-center">Sin datos en el período seleccionado.</td></tr>';
        }
        
        $html .= '</tbody></table>
            <div class="footer"><p>Sistema BUS Yaracuy | Reporte generado automáticamente | ' . date('d/m/Y H:i:s') . '</p></div>
        </body>
        </html>';
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("reporte_ventas_estacion_" . $fechaInicio . "_a_" . $fechaFin . ".pdf", ["Attachment" => false]);
    }
    
    private function getEstadoDespacho($estado) {
        $estados = [1 => 'Requisición', 2 => 'Aprobada por Compras', 3 => 'Despachada', 4 => 'Rechazada'];
        return $estados[$estado] ?? 'Desconocido (' . $estado . ')';
    }
    
    private function getEstadoMantenimiento($status) {
        $estados = ['P' => 'Pendiente', 'E' => 'En Proceso', 'T' => 'Terminado', 'C' => 'Cancelado', 'A' => 'Aprobado'];
        return $estados[$status] ?? ($status ?? 'Sin estado');
    }
}