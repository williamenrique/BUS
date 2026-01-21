<?php
set_time_limit(0); // Establece el tiempo máximo de ejecución a ilimitado para este script
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_POST['reporteData'])) {
    echo "No se recibieron datos para generar el reporte.";
    exit;
}

$decodedData = json_decode($_POST['reporteData'], true);

if (json_last_error() !== JSON_ERROR_NONE || empty($decodedData) || !isset($decodedData['data'])) {
    echo "Los datos recibidos son inválidos o están incompletos.";
    exit;
}

$reporteData = $decodedData['data'];
$fechaInicio = $decodedData['fechaInicio'] ?? null;
$fechaFin = $decodedData['fechaFin'] ?? null;
$searchValue = $decodedData['searchValue'] ?? '';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

// Calcular totales generales a partir de los datos agrupados
$totalGeneralDivisa = 0;
$totalGeneralBs = 0;
$totalGeneralArticulos = 0;

foreach ($reporteData as $unidad) {
    $totalGeneralDivisa += $unidad['total_divisa_unidad'];
    $totalGeneralBs += $unidad['total_bs_unidad'];
    $totalGeneralArticulos += array_sum(array_column(array_merge(...array_column($unidad['despachos'], 'articulos')), 'cant_despacho'));
}

$dompdf = new Dompdf($options);

// Importar encabezado estandarizado
require_once '../encabezado.php';

// Construir la leyenda de filtros
$leyendaFiltros = '<ul>';
if ($fechaInicio && $fechaFin) {
    $fechaInicioFormatted = date('d/m/Y', strtotime($fechaInicio));
    $fechaFinFormatted = date('d/m/Y', strtotime($fechaFin));
    $leyendaFiltros .= "<li><strong>Rango de Fechas:</strong> Del $fechaInicioFormatted al $fechaFinFormatted</li>";
}
if (!empty($searchValue)) {
    $leyendaFiltros .= "<li><strong>Término de Búsqueda:</strong> '" . htmlspecialchars($searchValue) . "'</li>";
}
if ($leyendaFiltros === '<ul>') {
    $leyendaFiltros .= "<li>No se aplicaron filtros específicos.</li>";
}
$leyendaFiltros .= '</ul>';

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Detallado de Compras Costeadas</title>
    ' . $cssCommon . '
    <style>
        .report-title { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 40px; }
        .filter-legend { border: 1px solid #ccc; padding: 10px; margin-bottom: 20px; font-size: 9px; }
        .filter-legend h4 { margin: 0 0 8px 0; font-size: 11px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
        .filter-legend ul { list-style: none; padding: 0; margin: 0; }
        .legend-container { display: -pdf-flex; flex-direction: row; justify-content: space-between; margin-bottom: 20px; }
        .legend-container .filter-legend { width: 48%; }
        .legend-container .totals-legend { width: 48%; border: 1px solid #a7d9ff; background-color: #f0f8ff; }
        .totals-legend h4 { color: #005a9e; }
        .totals-legend ul li { font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; page-break-inside: avoid; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
        thead { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .unidad-header { background-color: #d9edf7; font-weight: bold; font-size: 11px; }
        .despacho-header { background-color: #e9f5ff; font-weight: bold; }
        .unidad-footer { background-color: #cce5ff; font-weight: bold; font-size: 11px; }
        .total-general-row { background-color: #a7d9ff; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>
    ' . $headerHtml . '
    ' . $footerHtml . '

    <div class="report-title">REPORTE DETALLADO DE COMPRAS</div>

    <div class="legend-container">
        <div class="filter-legend">
            <h4>Filtros Aplicados</h4>
            ' . $leyendaFiltros . '
        </div>
        <div class="filter-legend totals-legend">
            <h4>Totales del Reporte</h4>
            <ul>
                <li><strong>Total Artículos:</strong> ' . number_format($totalGeneralArticulos) . '</li>
                <li><strong>Total Divisas:</strong> $' . number_format($totalGeneralDivisa, 2, ',', '.') . '</li>
                <li><strong>Total Bolívares:</strong> Bs. ' . number_format($totalGeneralBs, 2, ',', '.') . '</li>
            </ul>
        </div>
    </div>
';

foreach ($reporteData as $unidad) {
    $html .= '
    <table>
        <thead>
            <tr class="unidad-header">
                <th colspan="7">UNIDAD: ' . htmlspecialchars($unidad['nombre_unidad']) . '</th>
            </tr>
            <tr>
                <th class="text-center">ID Despacho</th>
                <th class="text-center">Fecha Despacho</th>
                <th>Artículo</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Tasa Día</th>
                <th class="text-right">Monto Divisa ($)</th>
                <th class="text-right">Monto Bs.</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($unidad['despachos'] as $despacho) {
        foreach ($despacho['articulos'] as $articulo) {
            $html .= '
            <tr>
                <td class="text-center">' . htmlspecialchars($articulo['id_despacho']) . '</td>
                <td class="text-center">' . htmlspecialchars($articulo['fecha_despacho']) . '</td>
                <td>' . htmlspecialchars($articulo['producto']) . '</td>
                <td class="text-center">' . htmlspecialchars($articulo['cant_despacho']) . '</td>
                <td class="text-right">' . number_format($articulo['tasa_dia'], 2, ',', '.') . '</td>
                <td class="text-right">' . number_format($articulo['monto_divisa'], 2, ',', '.') . '</td>
                <td class="text-right">' . number_format($articulo['monto_bs'], 2, ',', '.') . '</td>
            </tr>';
        }
    }

    $html .= '
        </tbody>
        <tfoot>
            <tr class="unidad-footer">
                <td colspan="5" class="text-right"><strong>TOTAL UNIDAD (' . htmlspecialchars($unidad['nombre_unidad']) . '):</strong></td>
                <td class="text-right"><strong>' . number_format($unidad['total_divisa_unidad'], 2, ',', '.') . '</strong></td>
                <td class="text-right"><strong>' . number_format($unidad['total_bs_unidad'], 2, ',', '.') . '</strong></td>
            </tr>
        </tfoot>
    </table>';
}

$html .= '</body></html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("reporte_costeadas_" . date('Ymd') . ".pdf", array("Attachment" => 0));
?>