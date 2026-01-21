<?php
set_time_limit(0);
require_once '../../system/core/Config/config.system.php';
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Validar recepción de datos
if (!isset($_POST['reporteData']) || !isset($_POST['unidadData'])) {
    die("No se recibieron datos para generar el reporte.");
}

$reporteData = json_decode($_POST['reporteData'], true);
$unidadData = json_decode($_POST['unidadData'], true);

if (json_last_error() !== JSON_ERROR_NONE || empty($reporteData)) {
    die("Los datos recibidos son inválidos.");
}

$items = $reporteData['items'];
$counts = $reporteData['counts'];

// Configuración de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Importar encabezado estandarizado
require_once '../encabezado.php';

// Estilos CSS Específicos para este reporte (Timeline)
$css = $cssCommon . '
    <style>
        /* Sobrescribir estilos del encabezado para que no sea fijo (solo primera página) */
        .header { 
            position: relative; 
            top: auto; 
            left: auto; 
            right: auto; 
            height: auto; 
            margin-bottom: 20px;
        }
        .header .logo {
            top: -35px;
        }
        .info-box { background-color: #f8f9fa; border: 1px solid #ddd; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        .info-box table { width: 100%; }
        .info-box td { vertical-align: top; }
        .info-label { font-weight: bold; color: #555; }
        
        .summary-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .summary-table th, .summary-table td { border: 1px solid #ddd; padding: 6px; text-align: center; }
        .summary-table th { background-color: #e9ecef; }
        
        .timeline-item { margin-bottom: 15px; border-left: 2px solid #ccc; padding-left: 15px; page-break-inside: avoid; }
        .timeline-header { background-color: #f1f1f1; padding: 5px 10px; font-weight: bold; border-radius: 3px; display: flex; justify-content: space-between; }
        .timeline-date { float: right; font-size: 10px; color: #666; }
        .timeline-body { padding: 5px 10px; }
        .timeline-footer { font-size: 9px; color: #888; margin-top: 5px; font-style: italic; }
        
        .badge { padding: 2px 5px; border-radius: 3px; color: white; font-size: 9px; font-weight: bold; }
        .bg-despacho { background-color: #007bff; }
        .bg-mantenimiento { background-color: #17a2b8; }
        .bg-aceite { background-color: #ffc107; color: black; }
        .bg-status { background-color: #6c757d; }
        
        ul { margin: 5px 0; padding-left: 20px; }
    </style>
';

// Construcción del HTML
$html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Historial de Unidad</title>' . $css . '</head><body>';

$html .= $headerHtml;
$html .= $footerHtml;
$html .= '<h2 style="text-align: center; margin-top: 0; margin-bottom: 20px; font-size: 16px; color: #333;">HOJA DE VIDA DE UNIDAD</h2>';

// Información de la Unidad
$html .= '
    <div class="info-box">
        <table>
            <tr>
                <td><span class="info-label">Unidad:</span> ' . $unidadData['id'] . '</td>
                <td><span class="info-label">Marca:</span> ' . $unidadData['marca'] . '</td>
                <td><span class="info-label">Modelo:</span> ' . $unidadData['modelo'] . '</td>
                <td><span class="info-label">VIN:</span> ' . $unidadData['vin'] . '</td>
            </tr>
        </table>
    </div>';

// Resumen de Eventos
$html .= '
    <table class="summary-table">
        <thead>
            <tr>
                <th>Ordenes de Despacho</th>
                <th>Servicios / Mantenimiento</th>
                <th>Cambios de Aceite</th>
                <th>Cambios de Estado</th>
                <th>Total Eventos</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>' . ($counts['despacho'] ?? 0) . '</td>
                <td>' . ($counts['mantenimiento'] ?? 0) . '</td>
                <td>' . ($counts['aceite'] ?? 0) . '</td>
                <td>' . ($counts['status'] ?? 0) . '</td>
                <td><strong>' . count($items) . '</strong></td>
            </tr>
        </tbody>
    </table>';

// Listado de Eventos (Timeline)
$html .= '<h3>Detalle de Eventos</h3>';

if (empty($items)) {
    $html .= '<p style="text-align:center; color:#666;">No se encontraron eventos registrados para esta unidad con los filtros aplicados.</p>';
} else {
    foreach ($items as $item) {
        $detalles = json_decode($item['detalles'], true);
        $tipo = $item['tipo'];
        $fecha = date('d/m/Y', strtotime($item['fecha']));
        
        // Modificación: No mostrar ID de evento para cambios de aceite
        $titulo = strtoupper(str_replace('_', ' ', $tipo));
        if ($tipo !== 'aceite') {
            $titulo .= " #" . $item['id_evento'];
        }
        $usuario = $item['usuario'] ?? 'Sistema';
        
        // Determinar clase y contenido según tipo
        $badgeClass = 'bg-' . $tipo;
        $contenido = '';

        switch ($tipo) {
            case 'despacho':
                $contenido = '<strong>Observación:</strong> ' . ($detalles['observacion'] ?? 'Ninguna') . '<br>';
                if (!empty($detalles['articulos'])) {
                    $contenido .= '<strong>Artículos:</strong><ul>';
                    foreach ($detalles['articulos'] as $art) {
                        $contenido .= '<li>' . $art['cant_despacho'] . 'x ' . $art['producto'] . '</li>';
                    }
                    $contenido .= '</ul>';
                }
                break;
            case 'aceite':
                $contenido = 'Cambio de aceite registrado.<br>';
                $contenido .= '<strong>KM Cambio:</strong> ' . number_format($detalles['kilometraje_cambio']) . '<br>';
                $contenido .= '<strong>KM Anterior:</strong> ' . number_format($detalles['kilometraje_anterior']) . '<br>';
                $contenido .= '<strong>Próximo Cambio:</strong> ' . number_format($detalles['kilometraje_proximo_cambio']);
                break;
            case 'mantenimiento':
                $tipoMant = ($detalles['tipo_mantenimiento'] == 'c') ? 'Correctivo' : 'Preventivo';
                $contenido = '<strong>Tipo:</strong> ' . $tipoMant . '<br>';
                $contenido .= '<strong>Diagnóstico:</strong> ' . $detalles['diagnostico'];
                break;
            case 'status':
                $contenido = 'Cambio de estado a: <strong>' . $detalles['status_texto'] . '</strong><br>';
                $contenido .= '<strong>Motivo:</strong> ' . $detalles['motivo'];
                break;
        }

        $html .= '
        <div class="timeline-item">
            <div class="timeline-header">
                <span><span class="badge ' . $badgeClass . '">' . strtoupper($tipo) . '</span> ' . $titulo . '</span>
                <span class="timeline-date">' . $fecha . '</span>
            </div>
            <div class="timeline-body">
                ' . $contenido . '
            </div>
            <div class="timeline-footer">
                Registrado por: ' . $usuario . '
            </div>
        </div>';
    }
}

$html .= '</body></html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Hoja_Vida_" . $unidadData['id'] . "_" . date('Ymd') . ".pdf", array("Attachment" => 0));
?>