<?php
require_once '../dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_POST['reporteData'])) {
    die('Error: No data received.');
}

$data = json_decode($_POST['reporteData'], true);
$reportData = $data['data'];
$totalLitros = $data['total'];
$fecha = $data['fecha'];
$type = $data['type'];
$fechaFin = isset($data['fechaFin']) ? $data['fechaFin'] : null;

$litrosGasolina = isset($data['litrosGasolina']) ? (float)$data['litrosGasolina'] : null;
$litrosDiesel   = isset($data['litrosDiesel'])   ? (float)$data['litrosDiesel']   : null;
$hayDesgloseCombustible = ($litrosGasolina !== null && $litrosDiesel !== null && ($litrosGasolina > 0 || $litrosDiesel > 0));

/**
 * Formatea un número en formato venezolano.
 */
function fmtVE($valor, $decimales = 2) {
    return number_format((float)$valor, $decimales, ',', '.');
}

function formatearMes($fechaYm) {
    $meses = [
        '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo',
        '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio',
        '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre',
        '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
    ];
    $partes = explode('-', $fechaYm);
    if (count($partes) == 2 && isset($meses[$partes[1]])) {
        return $meses[$partes[1]] . ' ' . $partes[0];
    }
    return $fechaYm;
}

if ($type == 'month') {
    if (!empty($fechaFin) && $fechaFin != $fecha) {
        $title = "Reporte de Ventas por Rango de Meses";
        $periodo = "Desde: " . formatearMes($fecha) . " Hasta: " . formatearMes($fechaFin);
    } else {
        $title = "Reporte Mensual de Litros Vendidos";
        $periodo = "Mes: " . formatearMes($fecha);
    }
} else {
    if (!empty($fechaFin) && $fechaFin != $fecha) {
        $title = "Reporte de Ventas por Rango de Días";
        $periodo = "Desde: " . date("d/m/Y", strtotime($fecha)) . " Hasta: " . date("d/m/Y", strtotime($fechaFin));
    } else {
        $title = "Reporte Diario de Litros Vendidos";
        $periodo = "Fecha: " . date("d/m/Y", strtotime($fecha));
    }
}

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$dompdf = new Dompdf($options);

$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        @page { margin: 20px 80px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 40px; margin-top: 15px; }
        .header h1 { margin: 0; font-size: 16px; }
        .header h2 { margin: 0; font-size: 14px; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .total { font-weight: bold; background-color: #e6e6e6; }
        .combustible-box { background-color: #e8f5e9; border: 1px solid #a5d6a7; border-radius: 4px; padding: 10px; margin-top: 15px; }
        .combustible-box .titulo { font-weight: bold; font-size: 11px; color: #2e7d32; margin-bottom: 6px; }
        .combustible-box .item { font-size: 10px; color: #333; margin-bottom: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SERVICIO SOCIALISTA DE ABASTECIMIENTO DEL ESTADO YARACUY</h1>
        <h2>' . $title . '</h2>
        <p style="margin: 5px 0; font-size: 11px;">' . $periodo . '</p>
    </div>
    <table>
        <thead>
            <tr>';

if ($type == 'month') {
    if (!empty($fechaFin) && $fechaFin != $fecha) {
        $html .= '<th>Mes</th><th>Litros Vendidos</th>';
    } else {
        $html .= '<th>Fecha</th><th>Litros Vendidos</th>';
    }
} else {
    $html .= '<th>Tipo Vehículo</th><th>Cantidad Ventas</th><th>Litros Vendidos</th>';
}

$html .= '</tr>
        </thead>
        <tbody>';

if (!empty($reportData)) {
    foreach ($reportData as $row) {
        $html .= '<tr>';
        if ($type == 'month') {
            if (!empty($fechaFin) && $fechaFin != $fecha) {
                $html .= '<td>' . formatearMes($row['mes']) . '</td>';
            } else {
                $html .= '<td>' . $row['fecha_venta'] . '</td>';
            }
            $html .= '<td>' . fmtVE($row['total_litros']) . ' L</td>';
        } else {
            $html .= '<td>' . $row['tipo_vehiculo'] . '</td>';
            $html .= '<td>' . $row['cantidad_ventas'] . '</td>';
            $html .= '<td>' . fmtVE($row['total_litros']) . ' L</td>';
        }
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="' . ($type == 'month' ? '2' : '3') . '">No hay datos registrados.</td></tr>';
}

$html .= '<tr class="total">';
if ($type == 'month') {
    $html .= '<td>TOTAL</td><td>' . fmtVE($totalLitros) . ' L</td>';
} else {
    $html .= '<td colspan="2">TOTAL</td><td>' . fmtVE($totalLitros) . ' L</td>';
}
$html .= '</tr>
        </tbody>
    </table>';

if ($hayDesgloseCombustible) {
    $html .= '
    <div class="combustible-box">
        <div class="titulo">⛽ DESGLOSE POR TIPO DE COMBUSTIBLE</div>
        <div class="item"><strong>Gasolina:</strong> ' . fmtVE($litrosGasolina) . ' L</div>
        <div class="item"><strong>Diesel:</strong> ' . fmtVE($litrosDiesel) . ' L</div>
    </div>';
}

$html .= '
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("reporte_litros_" . $fecha . ".pdf", ["Attachment" => false]);
?>