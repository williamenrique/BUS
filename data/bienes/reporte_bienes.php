<?php // Este archivo ahora es multipropósito
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_POST['reporteData']) || !isset($_POST['reporteTitulo'])) {
    http_response_code(400);
    echo 'Error: No se recibieron datos para generar el PDF.';
    exit;
}

$bienesAgrupados = json_decode($_POST['reporteData'], true);
$tituloReporte = htmlspecialchars($_POST['reporteTitulo']);

if ($bienesAgrupados === null || !is_array($bienesAgrupados)) {
    http_response_code(400);
    die('Error: Datos JSON no válidos.');
}

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// Importar encabezado estandarizado
require_once '../encabezado.php';

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>' . $tituloReporte . '</title>
    ' . $cssCommon . '
    <style>
        .department-title { font-size: 14px; font-weight: bold; background-color: #4a5568; color: #fff; padding: 8px; border-radius: 4px; margin-top: 20px; margin-bottom: 10px; }
        .summary-container { margin-bottom: 25px; border: 1px solid #e0e0e0; border-radius: 8px; padding: 0; background-color: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden; }
        .summary-container h3 { margin: 0; padding: 15px; font-size: 16px; text-align: center; background-color: #4a5568; color: #fff; }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table th { background-color: #edf2f7; padding: 10px; text-align: left; font-size: 11px; }
        .summary-table td { padding: 10px; border-bottom: 1px solid #e0e0e0; }
        .summary-table tbody tr:last-child td { border-bottom: none; }
        .summary-table tfoot td { font-weight: bold; background-color: #edf2f7; border-top: 2px solid #cbd5e0; }

        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #c5cae9; padding: 6px; text-align: left; }
        .table th { background-color: #f1f3f9; font-weight: bold; }
        .table .center { text-align: center; }
        .table .right { text-align: right; }
    </style>
</head>
<body>
    ' . $headerHtml . '
    ' . $footerHtml . '
    <h2 style="text-align: center; margin-top: 0;">' . $tituloReporte . '</h2>';

if (empty($bienesAgrupados)) {
    $html .= '<p style="text-align:center;">No se encontraron bienes para mostrar.</p>';
} else {
    // --- INICIO: Tabla de Resumen ---
    $html .= '<div class="summary-container">';
    $html .= '<h3>Resumen de Bienes</h3>';
    $html .= '<table class="summary-table">';
    $html .= '<thead><tr><th>Agrupación</th><th class="center">Cantidad de Bienes</th></tr></thead>';
    $html .= '<tbody>';

    $totalGeneral = 0;
    foreach ($bienesAgrupados as $departamento => $bienes) {
        $cantidad = count($bienes);
        $totalGeneral += $cantidad;
        $html .= '
            <tr>
                <td>' . htmlspecialchars($departamento) . '</td>
                <td class="center">' . $cantidad . '</td>
            </tr>';
    }

    $html .= '</tbody>';
    // Solo mostrar el total general si hay más de una agrupación
    if (count($bienesAgrupados) > 1) {
        $html .= '
            <tfoot>
                <tr>
                    <td>Total General</td>
                    <td class="center">' . $totalGeneral . '</td>
                </tr>
            </tfoot>';
    }
    $html .= '</table></div>';
    // --- FIN: Tabla de Resumen ---

    foreach ($bienesAgrupados as $departamento => $bienes) {
        $html .= '<div class="department-title">' . htmlspecialchars($departamento) . '</div>';
        $html .= '
        <table class="table">
            <thead>
                <tr>
                    <th class="center" width="10%">ID</th>
                    <th width="50%">Descripción</th>
                    <th width="32%">Departamento</th>
                    <th class="center" width="8%">Estado</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($bienes as $bien) {
            $html .= '
                <tr>
                    <td class="center">' . htmlspecialchars($bien['id_bien']) . '</td>
                    <td>' . htmlspecialchars($bien['descripcion_bien']) . '</td>
                    <td>' . htmlspecialchars($bien['departamento_bien']) . '</td>
                    <td class="center">' . htmlspecialchars($bien['status_bien']) . '</td>
                </tr>';
        }
        $html .= '</tbody></table>';
    }
}

$html .= '</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Reporte_Bienes_" . date('Ymd') . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]);
exit();
?>
