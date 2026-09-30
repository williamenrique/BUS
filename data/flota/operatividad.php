<?php
/**
 * Archivo: operatividad.php
 * Reporte de operatividad de flota en PDF.
 * El cuadro de resumen (Operatividad, Inoperativa, Crítica, Total) va alineado a la izquierda.
 */

// 1. Validación de la Petición
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['reporteData'])) {
    die("Acceso no autorizado o sin datos.");
}

$summaryData = json_decode($_POST['reporteData'], true);

if (empty($summaryData) || !is_array($summaryData)) {
    die("Datos de resumen no válidos.");
}

// Nombre de la institución (enviado desde el JS)
$nombreInstitucion = !empty($_POST['nombreInstitucion']) 
    ? htmlspecialchars($_POST['nombreInstitucion'], ENT_QUOTES, 'UTF-8') 
    : 'SERVICIO SOCIALISTA DE LOGISTICA, MANTENIMIENTO Y TRANSPORTE DEL ESTADO YARACUY';

// Título del reporte (va al header)
$tituloReporte = 'REPORTE DE OPERATIVIDAD DE FLOTA';

// 2. Carga del Entorno y Dependencias
require_once '../../system/core/Config/config.system.php';
require_once '../../system/core/Helpers/Helpers.php';
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 3. Calcular totales
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

// 5. Importar encabezado (usa $nombreInstitucion y $tituloReporte)
require_once '../encabezado.php';

// 6. Construir el HTML
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
        .table th { background-color: #f2f2f2; font-size: 11px; }

        /* Cuadro de resumen alineado a la IZQUIERDA */
        .summary-box { 
            border: 1px solid #333; 
            padding: 10px; 
            margin-top: 20px;
            margin-bottom: 25px; 
            margin-left: 0; 
            margin-right: auto;
            border-radius: 5px; 
            width: 40%; 
        }
        .summary-box table { width: 100%; border-collapse: collapse; }
        .summary-box td { border: none; padding: 5px; font-size: 12px; }
    </style>
</head>
<body>
    ' . $headerHtml . '
    ' . $footerHtml . '

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
            <tr>
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