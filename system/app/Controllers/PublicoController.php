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
    
    /**
     * Get initial data for the dashboard - AJAX endpoint
     */
    public function getMovimientosData() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $fechaInicio = $input['fechaInicio'] ?? date('Y-m-d', strtotime('-30 days'));
            $fechaFin = $input['fechaFin'] ?? date('Y-m-d');
            $tipoMovimiento = $input['tipoMovimiento'] ?? 'todos';
            
            $movimientos = [];
            
            // 1. Despachos de Almacén
            if ($tipoMovimiento === 'todos' || $tipoMovimiento === 'despachos') {
                $despachos = $this->model->getDespachosPublic($fechaInicio, $fechaFin);
                foreach ($despachos as $d) {
                    $movimientos[] = [
                        'tipo' => 'Despacho Almacén',
                        'fecha' => $d['fecha_despacho'],
                        'referencia' => 'DESP-' . str_pad($d['id_despacho'], 6, '0', STR_PAD_LEFT),
                        'id_despacho' => $d['id_despacho'],
                        'unidad' => $d['id_unidad'] ?? 'N/A',
                        'id_flota' => $d['id_flota'] ?? null,
                        'operador' => $d['operador'],
                        'mecanico' => $d['mecanico'],
                        'despachador' => $d['despachador'],
                        'observacion' => $d['observacion'],
                        'estado' => $this->getEstadoDespacho($d['estado_orden']),
                        'detalles' => $d['productos'] ?? []
                    ];
                }
            }
            
            // 2. Mantenimientos de Flota
            if ($tipoMovimiento === 'todos' || $tipoMovimiento === 'mantenimientos') {
                $mantenimientos = $this->model->getMantenimientosPublic($fechaInicio, $fechaFin);
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
            
            // 3. Cambios de Aceite
            if ($tipoMovimiento === 'todos' || $tipoMovimiento === 'aceite') {
                $aceites = $this->model->getCambiosAceitePublic($fechaInicio, $fechaFin);
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
            
            // 4. Kilometraje Flota
            if ($tipoMovimiento === 'todos' || $tipoMovimiento === 'kilometraje') {
                $kilometrajes = $this->model->getKilometrajePublic($fechaInicio, $fechaFin);
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
    
    /**
     * Get detalle de un cambio de aceite
     */
    public function getDetalleAceite() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $idAceite = $input['idAceite'] ?? null;
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
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => $detalle];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    /**
     * Get detalle de un mantenimiento
     */
    public function getDetalleMantenimiento() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $idMantenimiento = $input['idMantenimiento'] ?? null;
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
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => $detalle];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    /**
     * Get detalle de una actualización de kilometraje
     */
    public function getDetalleKilometraje() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $idKilometraje = $input['idKilometraje'] ?? null;
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
                .header h1 { margin: 0; font-size: 14px; color: #2c3e50; }
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
                <h1>SERVICIO SOCIALISTA DE ABASTECIMIENTO DEL ESTADO YARACUY</h1>
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
            $query = "SELECT f.status_unidad, COUNT(*) as total FROM table_flota f WHERE f.status_unidad BETWEEN 1 AND 4 GROUP BY f.status_unidad";
            $statusCounts = $this->model->select_all($query);
            $operativas = 0; $inoperativas = 0; $enMantenimiento = 0; $criticas = 0;
            foreach ($statusCounts as $row) {
                switch ($row['status_unidad']) {
                    case 1: $operativas = (int)$row['total']; break;
                    case 2: $inoperativas = (int)$row['total']; break;
                    case 3: $enMantenimiento = (int)$row['total']; break;
                    case 4: $criticas = (int)$row['total']; break;
                }
            }
            $total = $operativas + $inoperativas + $enMantenimiento + $criticas;
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => [
                'total' => $total, 'operativas' => $operativas, 'inoperativas' => $inoperativas,
                'en_mantenimiento' => $enMantenimiento, 'criticas' => $criticas
            ]];
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
            $query = "SELECT tf.id_flota,
                COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = tf.id_flota ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) as kilometraje_actual,
                COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END FROM table_flota_aceite_historial ah WHERE ah.id_flota = tf.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) as proximo_cambio_km
                FROM table_flota tf WHERE tf.status_unidad = 1";
            $aceites = $this->model->select_all($query);
            $requerido = 0; $proximo = 0; $ok = 0;
            foreach ($aceites as $row) {
                $kmActual = (int)$row['kilometraje_actual'];
                $kmProximo = (int)$row['proximo_cambio_km'];
                $diferencia = $kmProximo - $kmActual;
                if ($kmProximo > 0) {
                    if ($diferencia <= 0) $requerido++;
                    elseif ($diferencia <= 1000) $proximo++;
                    else $ok++;
                }
            }
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => [
                'requerido' => $requerido, 'proximo' => $proximo, 'ok' => $ok
            ]];
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
            $query = "SELECT fm.marca_unidad as marca, fmo.modelo_unidad as modelo,
                CONCAT(fm.marca_unidad, ' ', fmo.modelo_unidad) as marca_modelo,
                f.transmision, f.tipo_combustible as combustible, f.status_unidad, COUNT(*) as total
                FROM table_flota f
                JOIN table_flota_marca fm ON f.id_marca = fm.id_marca
                JOIN table_flota_modelo fmo ON f.id_modelo = fmo.id_modelo
                WHERE f.status_unidad IN (1, 2, 3, 5)
                GROUP BY fm.marca_unidad, fmo.modelo_unidad, f.transmision, f.tipo_combustible, f.status_unidad
                ORDER BY fm.marca_unidad, fmo.modelo_unidad";
            $results = $this->model->select_all($query);
            $grouped = [];
            foreach ($results as $row) {
                $key = $row['marca_modelo'] . '|' . $row['transmision'] . '|' . $row['combustible'];
                if (!isset($grouped[$key])) {
                    $grouped[$key] = ['marca_modelo' => $row['marca_modelo'], 'transmision' => $row['transmision'],
                        'combustible' => $row['combustible'], 'total' => 0, 'operativas' => 0, 'inoperativas' => 0];
                }
                $grouped[$key]['total'] += (int)$row['total'];
                if ($row['status_unidad'] == 1) $grouped[$key]['operativas'] += (int)$row['total'];
                else $grouped[$key]['inoperativas'] += (int)$row['total'];
            }
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => array_values($grouped)];
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
            if (!$status) {
                $arrResponse['message'] = 'Status requerido';
                header('Content-Type: application/json');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
            $query = "SELECT f.id_flota, f.id_unidad, f.vim_unidad, f.fecha_creacion,
                fm.marca_unidad AS marca_unidad, fmo.modelo_unidad AS modelo_unidad,
                f.transmision, f.tipo_combustible, f.status_unidad,
                COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = f.id_flota ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) as km_actual,
                COALESCE((SELECT ah.kilometraje_cambio FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) as ultimo_cambio_aceite,
                COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) as proximo_cambio_aceite
                FROM table_flota f
                INNER JOIN table_flota_marca fm ON f.id_marca = fm.id_marca
                INNER JOIN table_flota_modelo fmo ON f.id_modelo = fmo.id_modelo
                WHERE f.status_unidad = ? ORDER BY f.id_unidad";
            $unidades = $this->model->select_all($query, [$status]);
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
            if (!$status) {
                $arrResponse['message'] = 'Status requerido';
                header('Content-Type: application/json');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
                die();
            }
            $query = "SELECT f.id_flota, f.id_unidad, f.vim_unidad, f.fecha_creacion,
                fm.marca_unidad AS marca_unidad, fmo.modelo_unidad AS modelo_unidad,
                f.transmision, f.tipo_combustible, f.status_unidad,
                COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = f.id_flota ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) as km_actual,
                COALESCE((SELECT ah.kilometraje_cambio FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) as ultimo_cambio_aceite,
                COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) as proximo_cambio_aceite
                FROM table_flota f
                INNER JOIN table_flota_marca fm ON f.id_marca = fm.id_marca
                INNER JOIN table_flota_modelo fmo ON f.id_modelo = fmo.id_modelo
                WHERE f.status_unidad = 1";
            
            if ($status === 'requerido') {
                $query .= " AND (EXISTS (SELECT 1 FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota AND ah.kilometraje_cambio > 0)
                    AND (COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) - 
                    COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = f.id_flota ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) <= 0))";
            } elseif ($status === 'proximo') {
                $query .= " AND ((COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) - 
                    COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = f.id_flota ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) > 0)
                    AND (COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) - 
                    COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = f.id_flota ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) <= 1000))";
            } elseif ($status === 'ok') {
                $query .= " AND ((COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) - 
                    COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = f.id_flota ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) > 1000))";
            }
            
            $query .= " ORDER BY f.id_unidad";
            $unidades = $this->model->select_all($query);
            $arrResponse = ['success' => true, 'message' => 'OK', 'data' => $unidades];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    /**
     * Get ventas de estación for public view with filters
     */
    public function getVentasEstacionData() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        try {
            $input = json_decode(file_get_contents("php://input"), true);
            $fechaInicio = $input['fechaInicio'] ?? date('Y-m-d', strtotime('-30 days'));
            $fechaFin = $input['fechaFin'] ?? date('Y-m-d');
            $estacionId = $input['estacionId'] ?? null;
            
            // Obtener todas las estaciones para el select
            $estaciones = $this->model->getEstacionesPublic();
            
            // Obtener ventas filtradas
            $ventas = $this->model->getVentasEstacionPublic($fechaInicio, $fechaFin, $estacionId);
            
            // Agrupar por fecha y estación
            $grouped = [];
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
                        'vendedores' => [],
                        'tipos_vehiculo' => []
                    ];
                }
                
                $grouped[$key]['total_litros'] += (float)$venta['litros'];
                $grouped[$key]['total_ventas'] += 1;
                
                // Agrupar por vendedor
                $vendedor = $venta['usuario_nick'] ?? 'N/A';
                if (!isset($grouped[$key]['vendedores'][$vendedor])) {
                    $grouped[$key]['vendedores'][$vendedor] = ['litros' => 0, 'ventas' => 0];
                }
                $grouped[$key]['vendedores'][$vendedor]['litros'] += (float)$venta['litros'];
                $grouped[$key]['vendedores'][$vendedor]['ventas'] += 1;
                
                // Agrupar por tipo de vehículo
                $tipoVehiculo = $venta['tipo_vehiculo'] ?? 'N/A';
                if (!isset($grouped[$key]['tipos_vehiculo'][$tipoVehiculo])) {
                    $grouped[$key]['tipos_vehiculo'][$tipoVehiculo] = ['cantidad' => 0, 'litros' => 0];
                }
                $grouped[$key]['tipos_vehiculo'][$tipoVehiculo]['cantidad'] += 1;
                $grouped[$key]['tipos_vehiculo'][$tipoVehiculo]['litros'] += (float)$venta['litros'];
            }
            
            // Convertir a array indexado
            $data = array_values($grouped);
            
            // Convertir objetos vendedores y tipos_vehiculo a arrays
            foreach ($data as &$item) {
                $item['vendedores'] = array_map(function($v, $k) {
                    return ['nombre' => $k, 'litros' => $v['litros'], 'ventas' => $v['ventas']];
                }, $item['vendedores'], array_keys($item['vendedores']));
                
                $item['tipos_vehiculo'] = array_map(function($v, $k) {
                    return ['tipo' => $k, 'cantidad' => $v['cantidad'], 'litros' => $v['litros']];
                }, $item['tipos_vehiculo'], array_keys($item['tipos_vehiculo']));
            }
            
            $arrResponse = [
                'success' => true, 
                'message' => 'OK', 
                'data' => $data,
                'estaciones' => $estaciones
            ];
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error: ' . $e->getMessage();
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    /**
     * Helper: Get estado despacho text
     */
    private function getEstadoDespacho($estado) {
        $estados = [1 => 'Requisición', 2 => 'Aprobada por Compras', 3 => 'Despachada', 4 => 'Rechazada'];
        return $estados[$estado] ?? 'Desconocido (' . $estado . ')';
    }
    
    /**
     * Helper: Get estado mantenimiento text
     */
    private function getEstadoMantenimiento($status) {
        $estados = ['P' => 'Pendiente', 'E' => 'En Proceso', 'T' => 'Terminado', 'C' => 'Cancelado', 'A' => 'Aprobado'];
        return $estados[$status] ?? ($status ?? 'Sin estado');
    }
}