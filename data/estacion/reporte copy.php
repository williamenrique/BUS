<?php
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_POST['reporteData'])) {
    http_response_code(400);
    echo 'Error: No se recibieron datos para generar el PDF.';
    exit;
}

$data_received = json_decode($_POST['reporteData'], true);

if ($data_received === null || !isset($data_received['dataTotal']) || !isset($data_received['dataDetallado'])) {
    http_response_code(400);
    die('Error: Datos JSON no válidos.');
}

$dataTotal = $data_received['dataTotal'];
$dataDetallado = $data_received['dataDetallado'];

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    <style>
        @page { margin: 20px 80px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 40px; margin-top: 15px; }
        .header h1 { margin: 0; font-size: 16px; }
        .header h2 { margin: 0; font-size: 14px; font-weight: normal; }
        .info-reporte { width: 100%; margin-bottom: 15px; font-size: 11px; }
        .info-reporte .fecha { float: left; }
        .info-reporte .empleado { float: right; }
        .info-reporte::after { content: ""; display: table; clear: both; }
        .section { margin-bottom: 15px; }
        .section-title { font-size: 12px; font-weight: bold; background-color: #e8eaf6; padding: 5px; border-radius: 4px; margin-bottom: 8px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #c5cae9; padding: 5px; text-align: left; }
        .table th { background-color: #f1f3f9; font-weight: bold; }
        .table .center { text-align: center; }
        .table .right { text-align: right; }
        .summary-table td { font-size: 11px; }
        .footer { position: fixed; bottom: -20px; left: 0px; right: 0px; height: 40px; text-align: center; font-size: 9px; color: #777; }
        .page-number:before { content: "Página " counter(page); }
    </style>
</head>
<body>
    <div class="header">
        <h1>SERVICIO SOCIALISTA DE ABASTECIMIENTO DEL ESTADO YARACUY</h1>
        <h2>LIBRO DE VENTAS GASOLINA - ' . htmlspecialchars($dataTotal['estacion']) . '</h2>
    </div>

    <div class="info-reporte">
        <span class="fecha"><strong>Fecha del Reporte:</strong> ' . htmlspecialchars($dataTotal['fecha']) . '</span>
        <span class="empleado"><strong>Empleado:</strong> ' . htmlspecialchars($dataTotal['empleado']) . '</span>
    </div>

    <div class="section">
        <div class="section-title">Resumen de Ventas</div>
        <table class="table summary-table">
            <tr>
                <th width="25%">Vehículos Atendidos:</th>
                <td width="25%">' . htmlspecialchars($dataTotal['total_ventas']) . '</td>
                <th width="25%">Litros Vendidos:</th>
                <td width="25%">' . htmlspecialchars($dataTotal['total_litros']) . ' L</td>
            </tr>
            <tr>
                <th>Efectivo (Bs):</th>
                <td>' . number_format($dataTotal['total_efectivo_bs'] ?? 0, 2) . ' Bs</td>
                <th>Tarjeta de Débito (Bs):</th>
                <td>' . number_format($dataTotal['total_debito'] ?? 0, 2) . ' Bs</td>
            </tr>
            <tr>
                <th colspan="2">TOTAL GENERAL (Bs):</th>
                <td colspan="2"><strong>' . number_format(($dataTotal['total_efectivo_bs'] ?? 0) + ($dataTotal['total_debito'] ?? 0), 2) . ' Bs</strong></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Ventas Diarias Detalladas</div>
        <table class="table">
            <thead>
                <tr>
                    <th class="center" width="15%">Ticket</th>
                    <th width="40%">Vehículo</th>
                    <th class="center" width="15%">Litros</th>
                    <th class="right" width="15%">Efectivo (Bs)</th>
                    <th class="right" width="15%">Débito (Bs)</th>
                </tr>
            </thead>
            <tbody>';

if (empty($dataDetallado)) {
    $html .= '<tr><td colspan="5" class="center">No hay ventas detalladas para mostrar.</td></tr>';
} else {
    foreach ($dataDetallado as $venta) {
        $html .= '
            <tr>
                <td class="center">' . htmlspecialchars($venta['numero_venta']) . '</td>
                <td>' . htmlspecialchars($venta['tipo_vehiculo']) . '</td>
                <td class="center">' . htmlspecialchars(number_format($venta['cantidad_litros'], 2)) . ' L</td>
                <td class="right">' . number_format($venta['efectivob'] ?? 0, 2) . '</td>
                <td class="right">' . number_format($venta['tarjeta_debito'] ?? 0, 2) . '</td>
            </tr>';
    }
}

$html .= '
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p class="page-number"></p>
    </div>
</body>
</html>';

$dompdf->loadHtml($html);

// (Opcional) Aumentar el límite de tiempo si el PDF es muy grande
set_time_limit(300);

// Establecer el tamaño de papel y la orientación a horizontal (landscape)
$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

$filename = "Reporte_Ventas_" . str_replace('/', '-', $dataTotal['fecha']) . ".pdf";

// Enviar el PDF al navegador para que se muestre (no forzar descarga)
$dompdf->stream($filename, ["Attachment" => false]);
exit();
?>