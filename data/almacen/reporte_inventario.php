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

    // Usamos la URL completa (BASE_URL) que ha demostrado ser el método más fiable para que Dompdf visualice la imagen.
    require_once '../../system/core/Config/config.system.php';
    $logoUrl = BASE_URL . 'src/img/logo.png';

    $logoHtml = '';
    // No se necesita file_exists() porque es una URL. Dompdf manejará si la imagen no se encuentra.
    $logoHtml = '<img src="' . $logoUrl . '" class="logo">';

    $dompdf = new Dompdf($options);

    // Contenido HTML del PDF
    $html = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($title) . '</title>
        <style>
            @page {
                margin: 20mm 15mm;
            }
            body {
                font-family: Arial, sans-serif;
                font-size: 10px;
            }
            .header {
                width: 100%;
                text-align: center;
                margin-bottom: 35px;
                position: relative;
            }
            .header img {
                position: absolute;
                top: 0;
                left: 0;
                width: 80px;
            }
            .header h1 {
                margin: 0;
                font-size: 18px;
            }
            .header h2 {
                margin: 0;
                font-size: 16px;
            }
            .footer {
                position: fixed;
                bottom: -20mm;
                left: 0;
                right: 0;
                height: 15mm;
                text-align: center;
                font-size: 8px;
            }
            .footer .page-number:before {
                content: "Página " counter(page);
            }
            .report-title {
                text-align: center;
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 40px;
            }
            .header p {
                margin: 2px 0;
                font-size: 10px;
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
        <div class="header">
            ' . $logoHtml . '
            <h1>SERVICIO SOCIALISTA DE LOGISTICA,</h1>
            <h2>MANTENIMIENTO Y TRANSPORTE DEL ESTADO YARACUY</h2>
            <p>Fecha de Creacion: ' . date('d/m/Y') . '</p>
        </div>
        <div class="footer">
            <div class="page-number"></div>
        </div>

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