<?php
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reportData'])) {
    $reporteData = json_decode($_POST['reportData'], true);
    $title = $reporteData['title'] ?? 'Reporte de Inventario';
    $data = $reporteData['data'] ?? [];

    // Agrupar productos por ubicación
    $productosAgrupados = [];
    foreach ($data as $producto) {
        $ubicacion = $producto['ubicacion'] ?: 'Sin Ubicación Asignada';
        $productosAgrupados[$ubicacion][] = $producto;
    }
    ksort($productosAgrupados); // Ordenar ubicaciones alfabéticamente

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
            .report-title {
                text-align: center;
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 40px;
            }
            .location-header {
                background-color: #e0e0e0;
                font-weight: bold;
                padding: 8px;
                margin-top: 15px;
                border-radius: 4px;
                font-size: 12px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 5px;
            }
            th, td {
                border: 1px solid #ccc;
                padding: 6px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
        </style>
    </head>
    <body>
        ' . $headerHtml . '
        ' . $footerHtml . '

        <div class="report-title">' . htmlspecialchars($title) . '</div>';

    foreach ($productosAgrupados as $ubicacion => $productos) {
        $html .= '<div class="location-header">Ubicación: ' . htmlspecialchars($ubicacion) . '</div>';
        $html .= '
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">ID</th>
                    <th style="width: 55%;">Producto</th>
                    <th style="width: 20%;" class="text-center">Presentación</th>
                    <th style="width: 15%;" class="text-right">Cantidad Actual</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($productos as $p) {
            $html .= '<tr>
                        <td>' . htmlspecialchars($p['id_producto']) . '</td>
                        <td>' . htmlspecialchars($p['producto']) . '</td>
                        <td class="text-center">' . htmlspecialchars($p['present_producto']) . '</td>
                        <td class="text-right">' . htmlspecialchars(number_format($p['cant_producto'], 2)) . '</td>
                      </tr>';
        }
        $html .= '</tbody></table>';
    }

    $html .= '</body></html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("reporte_inventario_" . date('Y-m-d') . ".pdf", ["Attachment" => false]);
} else {
    echo "Acceso no permitido.";
}
?>