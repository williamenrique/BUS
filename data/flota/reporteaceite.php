<?php
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_POST['reporteData'])) {
    die("No se recibieron datos.");
}

$data = json_decode($_POST['reporteData'], true);
$items = $data['items'];
$counts = $data['counts'];
$filtro = $data['filtro'] ?: 'Varios';

// Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Importar encabezado estandarizado
require_once '../encabezado.php';

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
        .legend-box { 
            border: 1px solid #ccc; 
            padding: 10px; 
            margin-bottom: 20px; 
            background-color: #f9f9f9;
            font-size: 11px;
        }
        .legend-item { display: inline-block; margin-right: 20px; }
        .dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .dot-danger { background-color: #dc3545; }
        .dot-warning { background-color: #ffc107; }
        .dot-success { background-color: #28a745; }
        .dot-secondary { background-color: #6c757d; }
        
        .status-requerido { color: #dc3545; font-weight: bold; }
        .status-proximo { color: #d39e00; font-weight: bold; }
        .status-bien { color: #28a745; font-weight: bold; }
        .status-sin_registro { color: #6c757d; font-weight: bold; }
        
        .table-data th { background-color: #e9ecef; text-align: center; }
        .table-data td { text-align: center; vertical-align: middle; }
        .text-left { text-align: left !important; }
    </style>
';

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Estado de Aceite</title>
    ' . $css . '
</head>
<body>
    ' . $headerHtml . '
    ' . $footerHtml . '

    <h2 style="text-align: center; margin-top: 0; margin-bottom: 10px;">REPORTE DE ESTADO DE ACEITE</h2>
    <p style="text-align: center; margin-top: 0; color: #666;">Estados Incluidos: ' . $filtro . '</p>

    <div class="legend-box">
        <strong>Resumen de Cantidades:</strong><br>
        <div style="margin-top: 5px;">
            <span class="legend-item"><span class="dot dot-danger"></span>Requerido: <strong>' . (strpos($filtro, 'Requerido') !== false ? $counts['Requerido'] : '-') . '</strong></span>
            <span class="legend-item"><span class="dot dot-warning"></span>Próximo: <strong>' . (strpos($filtro, 'Próximo') !== false ? $counts['Próximo'] : '-') . '</strong></span>
            <span class="legend-item"><span class="dot dot-success"></span>Bien: <strong>' . (strpos($filtro, 'Bien') !== false ? $counts['Bien'] : '-') . '</strong></span>
            <span class="legend-item"><span class="dot dot-secondary"></span>Sin Registro: <strong>' . (strpos($filtro, 'Sin Registro') !== false ? $counts['Sin Registro'] : '-') . '</strong></span>
            <span class="legend-item" style="float:right;">Total Listado: <strong>' . count($items) . '</strong></span>
        </div>
    </div>

    <table class="table-data" width="100%" border="1" cellspacing="0" cellpadding="5">
        <thead>
            <tr>
                <th width="15%">Unidad</th>
                <th width="25%">Marca / Modelo</th>
                <th width="15%">KM Actual</th>
                <th width="15%">Último Cambio</th>
                <th width="15%">Próximo Cambio</th>
                <th width="15%">Estado</th>
            </tr>
        </thead>
        <tbody>';

if (empty($items)) {
    $html .= '<tr><td colspan="6" style="text-align:center; padding: 20px;">No se encontraron unidades con el criterio seleccionado.</td></tr>';
} else {
    foreach ($items as $item) {
        // Reemplazamos espacios por guion bajo para que coincida con la clase CSS .status-sin_registro
        $estadoClass = 'status-' . strtolower(str_replace(' ', '_', $item['estado']));
        $kmUltimo = $item['ultimo_cambio_km'] ? number_format($item['ultimo_cambio_km']) : 'N/A';
        $kmProximo = $item['proximo_cambio_km'] ? number_format($item['proximo_cambio_km']) : 'N/A';
        
        $html .= '
            <tr>
                <td><strong>' . $item['id_unidad'] . '</strong></td>
                <td class="text-left">' . $item['marca_unidad'] . ' ' . $item['modelo_unidad'] . '</td>
                <td>' . number_format($item['kilometraje_actual']) . '</td>
                <td>' . $kmUltimo . '</td>
                <td>' . $kmProximo . '</td>
                <td class="' . $estadoClass . '">' . strtoupper($item['estado']) . '</td>
            </tr>';
    }
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Reporte_Aceite_" . date('Ymd_His') . ".pdf", ["Attachment" => false]);
?>
