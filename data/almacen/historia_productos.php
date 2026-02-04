<?php
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reportData'])) {
    $reporteData = json_decode($_POST['reportData'], true);
    $title = $reporteData['title'] ?? 'Reporte de Historial de Productos';
    $data = $reporteData['data'] ?? [];
    $fechaInicio = $reporteData['fechaInicio'] ?? '';
    $fechaFin = $reporteData['fechaFin'] ?? '';

    // Configurar opciones de Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Helvetica');

    // Importar encabezado estandarizado
    require_once '../encabezado.php';

    $dompdf = new Dompdf($options);

    // Contenido HTML del PDF
    $html = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($title) . '</title>
        ' . $cssCommon . '
        <style>
            .header {
                position: static !important;
                top: auto !important;
                margin-bottom: 10px;
            }
            .report-title {
                text-align: center;
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 20px;
            }
            .date-range {
                text-align: center;
                font-size: 12px;
                margin-bottom: 20px;
                color: #555;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
                font-size: 10px;
            }
            th, td {
                border: 1px solid #ccc;
                padding: 6px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
                font-weight: bold;
                text-align: center;
            }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
        </style>
    </head>
    <body>
        ' . $headerHtml . '
        ' . $footerHtml . '

        <div class="report-title">' . htmlspecialchars($title) . '</div>';
        
    if (!empty($fechaInicio) && !empty($fechaFin)) {
        $html .= '<div class="date-range">Desde: ' . htmlspecialchars($fechaInicio) . ' Hasta: ' . htmlspecialchars($fechaFin) . '</div>';
    }

    $html .= '
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 35%;">Producto</th>
                    <th style="width: 10%;">Stock</th>
                    <th style="width: 10%;">Total Desp.</th>
                    <th style="width: 20%;">Primer Despacho</th>
                    <th style="width: 20%;">Último Despacho</th>
                </tr>
            </thead>
            <tbody>';
            
    if (empty($data)) {
        $html .= '<tr><td colspan="6" class="text-center">No hay datos para mostrar.</td></tr>';
    } else {
        foreach ($data as $row) {
            $html .= '<tr>
                        <td class="text-center">' . htmlspecialchars($row['id_producto']) . '</td>
                        <td>' . htmlspecialchars($row['producto']) . '</td>
                        <td class="text-center">' . htmlspecialchars($row['stock_actual']) . '</td>
                        <td class="text-center">' . htmlspecialchars($row['total_despachado']) . '</td>
                        <td class="text-center">' . htmlspecialchars($row['primer_despacho']) . '</td>
                        <td class="text-center">' . htmlspecialchars($row['ultimo_despacho']) . '</td>
                      </tr>';
        }
    }

    $html .= '</tbody></table></body></html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("historial_productos_" . date('Y-m-d') . ".pdf", ["Attachment" => false]);
} else {
    echo "Acceso no permitido.";
}
?>