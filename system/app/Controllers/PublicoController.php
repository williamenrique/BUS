<?php
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../../../data/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

class Publico extends Controllers {
    private $db;
    
    public function __construct() {
        // NO session validation - this is a public read-only interface
        parent::__construct();
    }
    
    /**
     * Main dashboard view for public movements consultation
     */
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
            // Get date range from request (default to last 30 days)
            $input = json_decode(file_get_contents("php://input"), true);
            $fechaInicio = $input['fechaInicio'] ?? date('Y-m-d', strtotime('-30 days'));
            $fechaFin = $input['fechaFin'] ?? date('Y-m-d');
            $tipoMovimiento = $input['tipoMovimiento'] ?? 'todos';
            
            $movimientos = [];
            
            // 1. Despachos de Almacén (Almacén movements)
            if ($tipoMovimiento === 'todos' || $tipoMovimiento === 'despachos') {
                $despachos = $this->model->getDespachosPublic($fechaInicio, $fechaFin);
                foreach ($despachos as $d) {
                    $movimientos[] = [
                        'tipo' => 'Despacho Almacén',
                        'fecha' => $d['fecha_despacho'],
                        'referencia' => 'DESP-' . str_pad($d['id_despacho'], 6, '0', STR_PAD_LEFT),
                        'unidad' => $d['id_unidad'] ?? 'N/A',
                        'operador' => $d['operador'],
                        'mecanico' => $d['mecanico'],
                        'despachador' => $d['despachador'],
                        'observacion' => $d['observacion'],
                        'estado' => $this->getEstadoDespacho($d['estado_orden']),
                        'detalles' => $d['productos'] ?? []
                    ];
                }
            }
            
            // 2. Mantenimientos de Flota (Flota movements)
            if ($tipoMovimiento === 'todos' || $tipoMovimiento === 'mantenimientos') {
                $mantenimientos = $this->model->getMantenimientosPublic($fechaInicio, $fechaFin);
                foreach ($mantenimientos as $m) {
                    $movimientos[] = [
                        'tipo' => 'Mantenimiento Flota',
                        'fecha' => $m['fecha_entrada'],
                        'referencia' => 'MANT-' . str_pad($m['id_unidad_mantenimiento'], 6, '0', STR_PAD_LEFT),
                        'unidad' => $m['id_unidad'] ?? 'N/A',
                        'operador' => $m['operardor_unidad'] ?? 'N/A',
                        'mecanico' => $m['nomb_mecanico'] ?? 'N/A',
                        'despachador' => '-',
                        'observacion' => $m['diagnostico'] ?? '',
                        'estado' => $this->getEstadoMantenimiento($m['status_mantenimiento']),
                        'detalles' => []
                    ];
                }
            }
            
            // 3. Cambios de Aceite (Flota aceite movements)
            if ($tipoMovimiento === 'todos' || $tipoMovimiento === 'aceite') {
                $aceites = $this->model->getCambiosAceitePublic($fechaInicio, $fechaFin);
                foreach ($aceites as $a) {
                    $movimientos[] = [
                        'tipo' => 'Cambio Aceite',
                        'fecha' => $a['fecha_cambio'],
                        'referencia' => 'ACE-' . str_pad($a['id_aceite_historial'], 6, '0', STR_PAD_LEFT),
                        'unidad' => $a['id_unidad'] ?? 'N/A',
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
                        'unidad' => $k['id_unidad'] ?? 'N/A',
                        'operador' => '-',
                        'mecanico' => $k['usuario_nick'] ?? 'N/A',
                        'despachador' => '-',
                        'observacion' => 'Kilometraje: ' . number_format($k['kilometraje_actual']),
                        'estado' => 'Registrado',
                        'detalles' => []
                    ];
                }
            }
            
            // Sort by date descending (most recent first)
            usort($movimientos, function($a, $b) {
                return strtotime($b['fecha']) - strtotime($a['fecha']);
            });
            
            // Summary statistics
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
     * Export movements to PDF
     */
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
            'ventas' => 'Ventas de Estación',
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
                .resumen { display: flex; justify-content: space-around; margin: 10px 0; padding: 8px; background: #f8f9fa; border-radius: 4px; font-size: 7px; }
                .resumen-item { text-align: center; }
                .resumen-item .label { color: #666; }
                .resumen-item .value { font-weight: bold; color: #2c3e50; font-size: 9px; }
                table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 7px; }
                th, td { border: 1px solid #ddd; padding: 4px 3px; text-align: center; vertical-align: middle; }
                th { background-color: #2c3e50; color: white; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                tr:hover { background-color: #f0f0f0; }
                .tipo-despacho { background-color: #e8f5e9; }
                .tipo-mantenimiento { background-color: #fff3e0; }
                .tipo-aceite { background-color: #f3e5f5; }
                .tipo-kilometraje { background-color: #e0f2f1; }
                .estado-completado { color: #2e7d32; font-weight: bold; }
                .estado-pendiente { color: #f57f17; font-weight: bold; }
                .estado-rechazado { color: #c62828; font-weight: bold; }
                .text-left { text-align: left; }
                .text-small { font-size: 6px; }
                .footer { margin-top: 20px; text-align: center; font-size: 7px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>SERVICIO SOCIALISTA DE ABASTECIMIENTO DEL ESTADO YARACUY</h1>
                <h2>Reporte de ' . $titulo . '</h2>
                <p>' . $periodo . ' | Generado: ' . date('d/m/Y H:i:s') . '</p>
            </div>
            
            <div class="resumen">
                <div class="resumen-item"><div class="label">Total Movimientos</div><div class="value">' . count($movimientos) . '</div></div>
                <div class="resumen-item"><div class="label">Despachos</div><div class="value">' . count(array_filter($movimientos, fn($m) => $m["tipo"] === "Despacho Almacén")) . '</div></div>
                <div class="resumen-item"><div class="label">Mantenimientos</div><div class="value">' . count(array_filter($movimientos, fn($m) => $m["tipo"] === "Mantenimiento Flota")) . '</div></div>
                <div class="resumen-item"><div class="label">Cambios Aceite</div><div class="value">' . count(array_filter($movimientos, fn($m) => $m["tipo"] === "Cambio Aceite")) . '</div></div>
                <div class="resumen-item"><div class="label">Actualizaciones KM</div><div class="value">' . count(array_filter($movimientos, fn($m) => $m["tipo"] === "Actualización KM")) . '</div></div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 8%;">Tipo</th>
                        <th style="width: 8%;">Fecha</th>
                        <th style="width: 10%;">Referencia</th>
                        <th style="width: 8%;">Unidad</th>
                        <th style="width: 12%;">Operador</th>
                        <th style="width: 12%;">Mecánico</th>
                        <th style="width: 10%;">Despachador</th>
                        <th style="width: 18%;">Observación</th>
                        <th style="width: 10%;">Estado</th>
                    </tr>
                </thead>
                <tbody>';
        
        if (!empty($movimientos)) {
            foreach ($movimientos as $mov) {
                $tipoClass = strtolower(str_replace(' ', '-', $mov['tipo']));
                $tipoClass = str_replace('á', 'a', $tipoClass);
                $tipoClass = str_replace('é', 'e', $tipoClass);
                $tipoClass = str_replace('ó', 'o', $tipoClass);
                $tipoClass = str_replace('ú', 'u', $tipoClass);
                $tipoClass = str_replace('ñ', 'n', $tipoClass);
                $tipoClass = 'tipo-' . $tipoClass;
                
                $estadoClass = 'estado-' . strtolower($mov['estado']);
                $estadoClass = str_replace(' ', '-', $estadoClass);
                
                $html .= '<tr class="' . $tipoClass . '">';
                $html .= '<td class="text-left text-small">' . htmlspecialchars($mov['tipo']) . '</td>';
                $html .= '<td>' . date('d/m/Y', strtotime($mov['fecha'])) . '</td>';
                $html .= '<td>' . htmlspecialchars($mov['referencia']) . '</td>';
                $html .= '<td>' . htmlspecialchars($mov['unidad']) . '</td>';
                $html .= '<td class="text-left text-small">' . htmlspecialchars($mov['operador']) . '</td>';
                $html .= '<td class="text-left text-small">' . htmlspecialchars($mov['mecanico']) . '</td>';
                $html .= '<td class="text-left text-small">' . htmlspecialchars($mov['despachador']) . '</td>';
                $html .= '<td class="text-left text-small">' . htmlspecialchars($mov['observacion']) . '</td>';
                $html .= '<td class="' . $estadoClass . '">' . htmlspecialchars($mov['estado']) . '</td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="9" style="text-align: center; padding: 20px;">No hay movimientos registrados en el período seleccionado.</td></tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                <p>Sistema BUS Yaracuy - Consulta Pública de Movimientos | ' . date('d/m/Y H:i:s') . '</p>
                <p>Este documento es de solo consulta y no tiene validez legal como comprobante fiscal.</p>
            </div>
        </body>
        </html>';
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream("movimientos_" . $fechaInicio . "_a_" . $fechaFin . ".pdf", ["Attachment" => false]);
    }
    
    /**
     * Get fleet status for dashboard cards
     * Total = status 1+2+3+4 (excluye 0 que son desincorporadas y 5)
     * Operativas = status 1
     * Inoperativas = status 2
     * En Mantenimiento = status 3
     * Críticas = status 4
     */
    public function getEstadoFlota() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        
        try {
            // Get fleet status counts - status 1,2,3,4 (exclude 0 and 5)
            $query = "
                SELECT 
                    f.status_unidad,
                    COUNT(*) as total
                FROM table_flota f
                WHERE f.status_unidad BETWEEN 1 AND 4
                GROUP BY f.status_unidad
            ";
            $statusCounts = $this->model->select_all($query);
            
            $operativas = 0;
            $inoperativas = 0;
            $enMantenimiento = 0;
            $criticas = 0;
            
            foreach ($statusCounts as $row) {
                switch ($row['status_unidad']) {
                    case 1: // Operativa
                        $operativas = (int)$row['total'];
                        break;
                    case 2: // Inoperativa
                        $inoperativas = (int)$row['total'];
                        break;
                    case 3: // En mantenimiento
                        $enMantenimiento = (int)$row['total'];
                        break;
                    case 4: // Críticas
                        $criticas = (int)$row['total'];
                        break;
                }
            }
            
            // Total = sum of status 1, 2, 3, 4 (excluye 0 y 5)
            $total = $operativas + $inoperativas + $enMantenimiento + $criticas;
            
            $arrResponse = [
                'success' => true,
                'message' => 'Estado de flota cargado correctamente',
                'data' => [
                    'total' => $total,
                    'operativas' => $operativas,
                    'inoperativas' => $inoperativas,
                    'en_mantenimiento' => $enMantenimiento,
                    'criticas' => $criticas
                ]
            ];
            
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error al cargar estado de flota: ' . $e->getMessage();
            error_log("Error en PublicController::getEstadoFlota: " . $e->getMessage());
        }
        
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    /**
     * Get oil change status for dashboard cards
     * Matches central system: only status 1 (operativas), calculates proximo_cambio_km = ultimo_cambio + 5000
     */
    public function getEstadoAceite() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        
        try {
            // Match central system logic exactly:
            // 1. Only status 1 (operativas)
            // 2. Get latest km from table_flota_kilometraje (by date)
            // 3. Get last oil change km from table_flota_aceite_historial and add 5000
            // 4. Categorize: requerido (<=0), proximo (>0 and <=1000), ok (>1000)
            $query = "
                SELECT 
                    tf.id_flota,
                    COALESCE((
                        SELECT km.kilometraje_actual 
                        FROM table_flota_kilometraje km 
                        WHERE km.id_flota = tf.id_flota 
                        ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC 
                        LIMIT 1
                    ), 0) as kilometraje_actual,
                    COALESCE((
                        SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END
                        FROM table_flota_aceite_historial ah 
                        WHERE ah.id_flota = tf.id_flota 
                        ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC 
                        LIMIT 1
                    ), 0) as proximo_cambio_km
                FROM table_flota tf
                WHERE tf.status_unidad = 1  -- Solo unidades operativas (status 1)
            ";
            $aceites = $this->model->select_all($query);
            
            $requerido = 0;
            $proximo = 0;
            $ok = 0;
            
            foreach ($aceites as $row) {
                $kmActual = (int)$row['kilometraje_actual'];
                $kmProximo = (int)$row['proximo_cambio_km'];
                $diferencia = $kmProximo - $kmActual;
                
                if ($kmProximo > 0) {
                    if ($diferencia <= 0) {
                        $requerido++;
                    } elseif ($diferencia <= 1000) {
                        $proximo++;
                    } else {
                        $ok++;
                    }
                }
                // If kmProximo is 0 (no oil change history), don't count in any category
            }
            
            $arrResponse = [
                'success' => true,
                'message' => 'Estado de aceite cargado correctamente',
                'data' => [
                    'requerido' => $requerido,
                    'proximo' => $proximo,
                    'ok' => $ok
                ]
            ];
            
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error al cargar estado de aceite: ' . $e->getMessage();
            error_log("Error en PublicController::getEstadoAceite: " . $e->getMessage());
        }
        
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    /**
     * Get fleet summary by model for table
     * Matches logged-in system: status 1=Operativas, status != 1 (2,3,5)=Inoperativas (excluye 4)
     */
    public function getResumenFlota() {
        $arrResponse = ['success' => false, 'message' => '', 'data' => []];
        
        try {
            $query = "
                SELECT 
                    fm.marca_unidad as marca,
                    fmo.modelo_unidad as modelo,
                    CONCAT(fm.marca_unidad, ' ', fmo.modelo_unidad) as marca_modelo,
                    f.transmision,
                    f.tipo_combustible as combustible,
                    f.status_unidad,
                    COUNT(*) as total
                FROM table_flota f
                JOIN table_flota_marca fm ON f.id_marca = fm.id_marca
                JOIN table_flota_modelo fmo ON f.id_modelo = fmo.id_modelo
                WHERE f.status_unidad IN (1, 2, 3, 5)  -- Excluye status 4
                GROUP BY fm.marca_unidad, fmo.modelo_unidad, f.transmision, f.tipo_combustible, f.status_unidad
                ORDER BY fm.marca_unidad, fmo.modelo_unidad
            ";
            $results = $this->model->select_all($query);
            
            // Group by marca/modelo/transmision/combustible
            $grouped = [];
            foreach ($results as $row) {
                $key = $row['marca_modelo'] . '|' . $row['transmision'] . '|' . $row['combustible'];
                
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'marca_modelo' => $row['marca_modelo'],
                        'transmision' => $row['transmision'],
                        'combustible' => $row['combustible'],
                        'total' => 0,
                        'operativas' => 0,
                        'inoperativas' => 0,
                        'estado' => 'Operativa'
                    ];
                }
                
                $grouped[$key]['total'] += (int)$row['total'];
                
                // Logged-in system logic: status 1 = operativas, status != 1 = inoperativas
                if ($row['status_unidad'] == 1) {
                    $grouped[$key]['operativas'] += (int)$row['total'];
                } else {
                    // Status 2, 3, 5 all count as inoperativas
                    $grouped[$key]['inoperativas'] += (int)$row['total'];
                }
            }
            
            $data = array_values($grouped);
            
            $arrResponse = [
                'success' => true,
                'message' => 'Resumen de flota cargado correctamente',
                'data' => $data
            ];
            
        } catch (Exception $e) {
            $arrResponse['message'] = 'Error al cargar resumen de flota: ' . $e->getMessage();
            error_log("Error en PublicController::getResumenFlota: " . $e->getMessage());
        }
        
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    /**
     * Helper: Get estado despacho text
     */
    private function getEstadoDespacho($estado) {
        $estados = [
            1 => 'Requisición',
            2 => 'Aprobada por Compras',
            3 => 'Despachada',
            4 => 'Rechazada'
        ];
        return $estados[$estado] ?? 'Desconocido (' . $estado . ')';
    }
    
    /**
     * Helper: Get estado mantenimiento text
     */
    private function getEstadoMantenimiento($status) {
        $estados = [
            'P' => 'Pendiente',
            'E' => 'En Proceso',
            'T' => 'Terminado',
            'C' => 'Cancelado',
            'A' => 'Aprobado'
        ];
        return $estados[$status] ?? ($status ?? 'Sin estado');
    }
}