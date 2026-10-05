<?php
/**
 * Archivo: operatividad.php
 * Descripción: Script independiente para generar el reporte de operatividad en PDF.
 *              Recibe una lista de IDs de unidades vía POST, consulta sus datos
 *              a través del FlotaModel y utiliza Dompdf para renderizar el documento.
 */

// 1. Validación de la Petición
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['reporteData'])) {
    die("Acceso no autorizado o sin datos.");
}

// Decodificar los datos completos enviados desde JS
$summaryData = json_decode($_POST['reporteData'], true);

if (empty($summaryData) || !is_array($summaryData)) {
    die("Datos de resumen no válidos.");
}

// 2. Carga del Entorno y Dependencias
require_once '../../system/core/Config/config.system.php';
require_once '../../system/core/Helpers/Helpers.php';
require_once '../dompdf/autoload.inc.php'; // Autoloader de Dompdf

use Dompdf\Dompdf;
use Dompdf\Options;

// 3. Calcular totales generales desde los datos de resumen
$conteo_status = [
    'operativas' => array_sum(array_column($summaryData, 'operativas')),
    'inoperativas' => array_sum(array_column($summaryData, 'inoperativas')),
    'criticas' => array_sum(array_column($summaryData, 'criticas')),
    'total' => array_sum(array_column($summaryData, 'cantidad'))
];

// 4. Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Importar encabezado estandarizado
require_once '../encabezado.php';

// 5. Construir el contenido HTML del PDF
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Operatividad</title>
    ' . $cssCommon . '
    <style>
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { border: 1px solid #ccc; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; font-size: 11px; }
        .status-group { margin-bottom: 25px; page-break-inside: avoid; }
        .status-title { 
            background-color: #333; 
            color: white; 
            padding: 8px; 
            font-size: 14px; 
            font-weight: bold;
            border-radius: 4px;
            margin-bottom: 5px;
        }
        .summary-box { border: 1px solid #333; padding: 10px; margin-bottom: 20px; border-radius: 5px; width: 30%; margin-right: auto; }
        .summary-box table { width: 100%; }
        .summary-box td { border: none; padding: 5px; font-size: 12px; }
    </style>
</head>
<body>
    ' . $headerHtml . '
    ' . $footerHtml . '

    <div style="text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 40px;">REPORTE DE OPERATIVIDAD DE FLOTA</div>
    <div class="summary-box">
        <table>
            <tr>
                <td><strong>Operatividad:</strong></td>
                <td style="text-align: right;">' . $conteo_status['operativas'] . '</td>
            </tr>
            <tr>
                <td><strong>Inoperativa:</strong></td>
                <td style="text-align: right;">' . $conteo_status['inoperativas'] . '</td>
            </tr>
            <tr>
                <td><strong>Crítica:</strong></td>
                <td style="text-align: right;">' . $conteo_status['criticas'] . '</td>
            </tr>
            <tr style="border-top: 1px solid #ccc;">
                <td><strong>TOTAL de flota:</strong></td>
                <td style="text-align: right;"><strong>' . $conteo_status['total'] . '</strong></td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr style="text-align: left;">
                <th>Grupo (Modelo / Combustible / Transmisión)</th>
                <th style="text-align: center;">Cantidad</th>
                <th style="text-align: center;">Operativas</th>
                <th style="text-align: center;">Inoperativas</th>
                <th style="text-align: center;">Críticas</th>
            </tr>
        </thead>
        <tbody>';

foreach ($summaryData as $group) {
    $html .= '
            <tr>
                <td>' . htmlspecialchars($group['groupName']) . '</td>
                <td style="text-align: center;">' . $group['cantidad'] . '</td>
                <td style="text-align: center;">' . $group['operativas'] . '</td>
                <td style="text-align: center;">' . $group['inoperativas'] . '</td>
                <td style="text-align: center;">' . $group['criticas'] . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>

</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Reporte_Operatividad_" . date('Y-m-d') . ".pdf", ["Attachment" => false]);

?>