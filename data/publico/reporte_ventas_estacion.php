<?php
require_once __DIR__ . '/../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_POST['reporteData'])) {
    http_response_code(400);
    echo 'Error: No se recibieron datos.';
    exit;
}

$data_received = json_decode($_POST['reporteData'], true);
$data = $data_received['data'] ?? [];
$fechaInicio = $data_received['fechaInicio'] ?? date('Y-m-d');
$fechaFin = $data_received['fechaFin'] ?? date('Y-m-d');
$estacionNombre = $data_received['estacionNombre'] ?? 'Todas las estaciones';

$fmt = function($v, $dec = 2) {
    return number_format((float)$v, $dec, ',', '.');
};

$granTotalLitros = 0;
$granTotalVentas = 0;
$granTotalBs = 0;
$granTotalGasolina = 0;
$granTotalDiesel = 0;
$granTotalDivisa = 0;
$granTotalEfectivo = 0;
$granTotalDebito = 0;

$aggCombustible = [];
$aggVehiculo = [];
$aggPago = [];

foreach ($data as $item) {
    $granTotalLitros += (float)($item['total_litros'] ?? 0);
    $granTotalVentas += (int)($item['total_ventas'] ?? 0);
    $granTotalBs += (float)($item['total_bs'] ?? 0);
    $granTotalGasolina += (float)($item['litros_gasolina'] ?? 0);
    $granTotalDiesel += (float)($item['litros_diesel'] ?? 0);
    $granTotalDivisa += (float)($item['total_divisa'] ?? 0);
    $granTotalEfectivo += (float)($item['total_efectivo'] ?? 0);
    $granTotalDebito += (float)($item['total_debito'] ?? 0);

    foreach (($item['tipos_combustible'] ?? []) as $tc) {
        if (!isset($aggCombustible[$tc['tipo']])) $aggCombustible[$tc['tipo']] = ['cantidad' => 0, 'litros' => 0, 'monto' => 0];
        $aggCombustible[$tc['tipo']]['cantidad'] += (int)$tc['cantidad'];
        $aggCombustible[$tc['tipo']]['litros'] += (float)$tc['litros'];
        $aggCombustible[$tc['tipo']]['monto'] += (float)$tc['monto'];
    }
    foreach (($item['tipos_vehiculo'] ?? []) as $tv) {
        if (!isset($aggVehiculo[$tv['tipo']])) $aggVehiculo[$tv['tipo']] = ['cantidad' => 0, 'litros' => 0];
        $aggVehiculo[$tv['tipo']]['cantidad'] += (int)$tv['cantidad'];
        $aggVehiculo[$tv['tipo']]['litros'] += (float)$tv['litros'];
    }
    foreach (($item['tipos_pago'] ?? []) as $tp) {
        if (!isset($aggPago[$tp['tipo']])) $aggPago[$tp['tipo']] = ['cantidad' => 0, 'monto' => 0];
        $aggPago[$tp['tipo']]['cantidad'] += (int)$tp['cantidad'];
        $aggPago[$tp['tipo']]['monto'] += (float)$tp['monto'];
    }
}

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$periodo = "Desde: " . date('d/m/Y', strtotime($fechaInicio)) . " Hasta: " . date('d/m/Y', strtotime($fechaFin));

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas de Estación</title>
    <style>
        @page { margin: 15mm 10mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9px; color: #333; line-height: 1.3; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 14px; color: #2c3e50; text-transform: uppercase; }
        .header h2 { margin: 5px 0 0 0; font-size: 11px; font-weight: normal; color: #555; }
        .header p { margin: 3px 0; font-size: 9px; color: #777; }
        .section-title { background-color: #2c3e50; color: white; font-weight: bold; padding: 5px 8px; font-size: 10px; margin-top: 12px; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8px; }
        th, td { border: 1px solid #ddd; padding: 4px 5px; vertical-align: middle; }
        th { background-color: #e9ecef; color: #2c3e50; font-weight: bold; text-align: center; }
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .kpi-container { text-align: center; margin-bottom: 10px; }
        .kpi-box { display: inline-block; border: 1px solid #2c3e50; border-radius: 4px; padding: 8px 12px; margin: 0 4px; min-width: 100px; background: #f8f9fa; }
        .kpi-value { font-size: 14px; font-weight: bold; color: #2c3e50; }
        .kpi-label { font-size: 8px; color: #666; text-transform: uppercase; }
        .total-row { background-color: #2c3e50; color: white; font-weight: bold; }
        .subtotal-row { background-color: #e9ecef; font-weight: bold; }
        .footer { margin-top: 20px; text-align: center; font-size: 7px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>BUS YARACUY</h1>
        <h2>Reporte de Ventas de Estación</h2>
        <p>' . $periodo . ' | Estación: ' . htmlspecialchars($estacionNombre) . '</p>
        <p>Generado: ' . date('d/m/Y H:i:s') . '</p>
    </div>

    <div class="kpi-container">
        <div class="kpi-box"><div class="kpi-value">' . $fmt($granTotalLitros) . ' L</div><div class="kpi-label">Litros Totales</div></div>
        <div class="kpi-box"><div class="kpi-value">' . number_format($granTotalVentas) . '</div><div class="kpi-label">Vehículos Atendidos</div></div>
        <div class="kpi-box"><div class="kpi-value">' . $fmt($granTotalBs) . ' Bs</div><div class="kpi-label">Total General</div></div>
    </div>

    <div class="section-title">Desglose por Tipo de Combustible</div>
    <table>
        <thead><tr><th class="text-left" style="width: 30%;">Combustible</th><th style="width: 15%;">Cantidad</th><th style="width: 20%;">Litros</th><th style="width: 35%;">Monto Bs</th></tr></thead>
        <tbody>';

if (!empty($aggCombustible)) {
    foreach ($aggCombustible as $nombre => $info) {
        $html .= '<tr><td class="text-left"><strong>' . htmlspecialchars($nombre) . '</strong></td><td class="text-center">' . number_format($info['cantidad']) . '</td><td class="text-right">' . $fmt($info['litros']) . ' L</td><td class="text-right">' . $fmt($info['monto']) . ' Bs</td></tr>';
    }
    $html .= '<tr class="subtotal-row"><td class="text-left">TOTAL</td><td class="text-center">' . number_format($granTotalVentas) . '</td><td class="text-right">' . $fmt($granTotalLitros) . ' L</td><td class="text-right">' . $fmt($granTotalBs) . ' Bs</td></tr>';
} else {
    $html .= '<tr><td colspan="4" class="text-center">Sin datos.</td></tr>';
}

$html .= '</tbody></table>

    <div class="section-title">Desglose por Tipo de Vehículo</div>
    <table>
        <thead><tr><th class="text-left" style="width: 40%;">Tipo de Vehículo</th><th style="width: 20%;">Cantidad</th><th style="width: 40%;">Litros</th></tr></thead>
        <tbody>';

if (!empty($aggVehiculo)) {
    foreach ($aggVehiculo as $nombre => $info) {
        $html .= '<tr><td class="text-left"><strong>' . htmlspecialchars($nombre) . '</strong></td><td class="text-center">' . number_format($info['cantidad']) . '</td><td class="text-right">' . $fmt($info['litros']) . ' L</td></tr>';
    }
    $html .= '<tr class="subtotal-row"><td class="text-left">TOTAL</td><td class="text-center">' . number_format($granTotalVentas) . '</td><td class="text-right">' . $fmt($granTotalLitros) . ' L</td></tr>';
} else {
    $html .= '<tr><td colspan="3" class="text-center">Sin datos.</td></tr>';
}

$html .= '</tbody></table>

    <div class="section-title">Desglose por Tipo de Pago</div>
    <table>
        <thead><tr><th class="text-left" style="width: 40%;">Tipo de Pago</th><th style="width: 20%;">Cantidad</th><th style="width: 40%;">Monto Bs</th></tr></thead>
        <tbody>';

if (!empty($aggPago)) {
    foreach ($aggPago as $nombre => $info) {
        $html .= '<tr><td class="text-left"><strong>' . htmlspecialchars($nombre) . '</strong></td><td class="text-center">' . number_format($info['cantidad']) . '</td><td class="text-right">' . $fmt($info['monto']) . ' Bs</td></tr>';
    }
    $html .= '<tr class="subtotal-row"><td class="text-left">TOTAL</td><td class="text-center">' . number_format($granTotalVentas) . '</td><td class="text-right">' . $fmt($granTotalBs) . ' Bs</td></tr>';
} else {
    $html .= '<tr><td colspan="3" class="text-center">Sin datos.</td></tr>';
}

$html .= '</tbody></table>

    <div class="section-title">Detalle por Fecha y Estación</div>
    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Fecha</th>
                <th class="text-left" style="width: 20%;">Estación</th>
                <th style="width: 10%;">Ventas</th>
                <th style="width: 13%;">Litros Tot.</th>
                <th style="width: 12%;">Gasolina</th>
                <th style="width: 12%;">Diesel</th>
                <th style="width: 21%;">Monto Bs</th>
            </tr>
        </thead>
        <tbody>';

if (!empty($data)) {
    foreach ($data as $item) {
        $html .= '<tr><td class="text-center">' . date('d/m/Y', strtotime($item['fecha'])) . '</td><td class="text-left">' . htmlspecialchars($item['estacion']) . '</td><td class="text-center">' . number_format($item['total_ventas']) . '</td><td class="text-right">' . $fmt($item['total_litros']) . ' L</td><td class="text-right">' . $fmt($item['litros_gasolina']) . ' L</td><td class="text-right">' . $fmt($item['litros_diesel']) . ' L</td><td class="text-right">' . $fmt($item['total_bs']) . ' Bs</td></tr>';
    }
    $html .= '<tr class="total-row"><td class="text-center" colspan="2">TOTAL GENERAL</td><td class="text-center">' . number_format($granTotalVentas) . '</td><td class="text-right">' . $fmt($granTotalLitros) . ' L</td><td class="text-right">' . $fmt($granTotalGasolina) . ' L</td><td class="text-right">' . $fmt($granTotalDiesel) . ' L</td><td class="text-right">' . $fmt($granTotalBs) . ' Bs</td></tr>';
} else {
    $html .= '<tr><td colspan="7" class="text-center">Sin datos en el período seleccionado.</td></tr>';
}

$html .= '</tbody></table>
    <div class="footer"><p>Sistema BUS Yaracuy | Reporte generado automáticamente | ' . date('d/m/Y H:i:s') . '</p></div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("reporte_ventas_estacion_" . $fechaInicio . "_a_" . $fechaFin . ".pdf", ["Attachment" => false]);
exit();
?>