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

if ($data_received === null || !isset($data_received['dataTotal']) || !isset($data_received['dataDetallado']) || !isset($data_received['reportType'])) {
    http_response_code(400);
    die('Error: Datos JSON no válidos.');
}

$reportType    = $data_received['reportType'];
$dataTotal     = $data_received['dataTotal'];
$dataDetallado = $data_received['dataDetallado'];
// Sección: 'resumen' | 'ventas' | 'gasolina' | 'diesel'
$seccion       = isset($data_received['seccion']) ? $data_received['seccion'] : 'resumen';
if (!in_array($seccion, ['resumen', 'ventas', 'gasolina', 'diesel'])) {
    $seccion = 'resumen';
}

// --- Filtrar el detallado según la sección ---
if ($seccion === 'gasolina') {
    $dataDetallado = array_values(array_filter($dataDetallado, function ($v) {
        $n = mb_strtolower($v['tipo_combustible'] ?? '');
        return strpos($n, 'gasolina') !== false;
    }));
} elseif ($seccion === 'diesel') {
    $dataDetallado = array_values(array_filter($dataDetallado, function ($v) {
        $n = mb_strtolower($v['tipo_combustible'] ?? '');
        return strpos($n, 'diesel') !== false || strpos($n, 'diésel') !== false;
    }));
}

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$isDivisaReport = ($reportType === 'divisa');

/**
 * Formatea un número en formato venezolano.
 */
function fmtVE($valor, $decimales = 2) {
    return number_format((float)$valor, $decimales, ',', '.');
}

// =========================================================================
// CÁLCULOS DE TOTALES
// =========================================================================
// Para las secciones de listado (ventas / gasolina / diesel) calculamos
// los totales a partir del detallado YA FILTRADO, para que cuadren.
// Para el resumen también lo calculamos del detallado (que no está filtrado).
// =========================================================================

$totalVentas    = count($dataDetallado);
$totalLitros    = 0;
$totalEfectivo  = 0;   // Efectivo Bs + Divisa convertida (si unificado)
$totalDivisa    = 0;   // Divisa $ (monto original)
$totalDebito    = 0;
$totalGeneralBs = 0;

// Contadores por tipo de vehículo
$cantAuto = 0; $cantMoto = 0; $cantCamion = 0; $cantOtrosVeh = 0;
$litrosAuto = 0; $litrosMoto = 0; $litrosCamion = 0; $litrosOtrosVeh = 0;

// Contadores por tipo de pago
$pagoDivisa   = 0; $pagoEfectivo = 0; $pagoDebito = 0;
$montoDivisaTotal   = 0; $montoEfectivoTotal = 0; $montoDebitoTotal = 0;

// Contadores por tipo de combustible
$combustibles = []; // ['Gasolina' => ['cant'=>X, 'litros'=>Y, 'monto_bs'=>Z, 'monto_divisa'=>W], ...]

foreach ($dataDetallado as $v) {
    $litros    = (float)($v['cantidad_litros'] ?? 0);
    $efectivob = (float)($v['efectivob'] ?? 0);
    $debito    = (float)($v['tarjeta_debito'] ?? 0);
    $divisa    = (float)($v['monto_divisa'] ?? 0);
    $tasa      = (float)($v['tasa_dia'] ?? 0);

    $totalLitros += $litros;
    $totalDebito += $debito;
    $totalDivisa += $divisa;

    if ($isDivisaReport) {
        $totalEfectivo += $efectivob;          // efectivob ya trae solo el efectivo Bs puro
        $totalGeneralBs += $efectivob + $debito + ($divisa * $tasa);
    } else {
        // Unificado: efectivob ya trae la divisa convertida a Bs (según reportType del modelo)
        $totalEfectivo += $efectivob;
        $totalGeneralBs += $efectivob + $debito;
    }

    // Tipo de vehículo
    $tv = mb_strtolower($v['tipo_vehiculo'] ?? '');
    if (strpos($tv, 'moto') !== false) {
        $cantMoto++; $litrosMoto += $litros;
    } elseif (strpos($tv, 'camion') !== false || strpos($tv, 'camión') !== false) {
        $cantCamion++; $litrosCamion += $litros;
    } elseif (strpos($tv, 'auto') !== false || strpos($tv, 'carro') !== false) {
        $cantAuto++; $litrosAuto += $litros;
    } else {
        $cantOtrosVeh++; $litrosOtrosVeh += $litros;
    }

    // Tipo de pago
    $tp = mb_strtolower($v['tipo_pago'] ?? '');
    if (strpos($tp, 'divisa') !== false) {
        $pagoDivisa++; $montoDivisaTotal += $divisa;
    } elseif (strpos($tp, 'efectivo') !== false) {
        $pagoEfectivo++; $montoEfectivoTotal += $efectivob;
    } elseif (strpos($tp, 'débito') !== false || strpos($tp, 'debito') !== false || strpos($tp, 'punto') !== false || strpos($tp, 'tarjeta') !== false) {
        $pagoDebito++; $montoDebitoTotal += $debito;
    }

    // Combustible
    $nombreComb = $v['tipo_combustible'] ?? 'Otros';
    if (!isset($combustibles[$nombreComb])) {
        $combustibles[$nombreComb] = [
            'cant' => 0,
            'litros' => 0,
            'monto_bs' => 0,
            'monto_divisa' => 0,
        ];
    }
    $combustibles[$nombreComb]['cant']++;
    $combustibles[$nombreComb]['litros'] += $litros;

    // monto_bs del combustible = efectivob + debito + (divisa*tasa si aplica)
    if ($isDivisaReport) {
        $combustibles[$nombreComb]['monto_bs'] += $efectivob + $debito + ($divisa * $tasa);
    } else {
        $combustibles[$nombreComb]['monto_bs'] += $efectivob + $debito;
    }
    $combustibles[$nombreComb]['monto_divisa'] += $divisa;
}

$dompdf = new Dompdf($options);

// =========================================================================
// RENDERIZADO POR SECCIÓN
// =========================================================================

$tituloPrincipal = 'SERVICIO SOCIALISTA DE ABASTECIMIENTO DEL ESTADO YARACUY';
$fecha = $dataTotal['fecha'];
$estacion = $dataTotal['estacion'];
$empleado = $dataTotal['empleado'];
$tasaDia = $dataTotal['tasa_dia'] ?? 0;

$nombreArchivo = '';
$tituloPDF = '';
$subtituloPDF = '';

$cssBase = '
    @page { margin: 20px 80px; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #333; }
    .header { text-align: center; margin-bottom: 30px; margin-top: 15px; }
    .header h1 { margin: 0; font-size: 16px; }
    .header h2 { margin: 0; font-size: 14px; font-weight: normal; }
    .header .subtitulo { margin-top: 8px; font-size: 13px; font-weight: bold; color: #1a237e; letter-spacing: 1px; }
    .info-reporte { width: 100%; margin-bottom: 15px; font-size: 11px; }
    .info-reporte .fecha { float: left; }
    .info-reporte .empleado { float: right; }
    .info-reporte::after { content: ""; display: table; clear: both; }
    .section { margin-bottom: 15px; }
    .section-title { font-size: 12px; font-weight: bold; background-color: #e8eaf6; padding: 6px; border-radius: 4px; margin-bottom: 8px; }
    .table { width: 100%; border-collapse: collapse; }
    .table th, .table td { border: 1px solid #c5cae9; padding: 6px; text-align: left; }
    .table th { background-color: #f1f3f9; font-weight: bold; }
    .table .center { text-align: center; }
    .table .right { text-align: right; }
    .summary-table td { font-size: 11px; }
    .summary-table th { font-size: 11px; background-color: #e8eaf6; color: #1a237e; }
    .footer { position: fixed; bottom: -20px; left: 0px; right: 0px; height: 40px; text-align: center; font-size: 9px; color: #777; }
    .page-number:before { content: "Página " counter(page); }
    .empty-msg { text-align: center; padding: 30px; font-size: 12px; color: #888; }
    .total-row td { background-color: #e8eaf6; font-weight: bold; }
    .combustible-block-title { font-size: 13px; font-weight: bold; color: #1a237e; background-color: #e3f2fd; padding: 8px; border-radius: 4px; margin-bottom: 8px; border-left: 4px solid #1976d2; }
    .big-total { font-size: 18px; font-weight: bold; color: #1b5e20; }
    .info-card { background-color: #f9fbe7; border: 1px solid #dce775; border-radius: 4px; padding: 10px; margin-bottom: 10px; }
    .info-card .titulo { font-weight: bold; font-size: 12px; color: #33691e; margin-bottom: 6px; }
    .kpi-box { display: inline-block; width: 23%; background: #e3f2fd; border-radius: 6px; padding: 10px; margin-right: 1%; text-align: center; vertical-align: top; }
    .kpi-box .kpi-num { font-size: 18px; font-weight: bold; color: #1565c0; display: block; }
    .kpi-box .kpi-lbl { font-size: 9px; color: #555; text-transform: uppercase; }
    .combustible-kpi { background: #e8f5e9; border: 1px solid #a5d6a7; border-radius: 6px; padding: 10px; margin-bottom: 10px; }
    .combustible-kpi .titulo { font-weight: bold; font-size: 12px; color: #2e7d32; margin-bottom: 6px; }
';

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte</title>
    <style>' . $cssBase . '</style>
</head>
<body>
    <div class="header">
        <h1>' . htmlspecialchars($tituloPrincipal) . '</h1>
        <h2>' . htmlspecialchars($tituloPDF) . ' - ' . htmlspecialchars($estacion) . '</h2>
        <div class="subtitulo">' . htmlspecialchars($subtituloPDF) . '</div>
    </div>

    <div class="info-reporte">
        <span class="fecha"><strong>Fecha del Reporte:</strong> ' . htmlspecialchars($fecha) . '</span>
        <span class="empleado"><strong>Empleado:</strong> ' . htmlspecialchars($empleado) . '</span>
    </div>';

// =========================================================================
// SECCIÓN: RESUMEN (cuadro de totales)
// =========================================================================
if ($seccion === 'resumen') {
    $tituloPDF = 'REPORTE DE VENTAS';
    $subtituloPDF = 'RESUMEN GENERAL Y DESGLOSES';
    $nombreArchivo = 'Reporte_Resumen_' . str_replace('/', '-', $fecha) . '.pdf';

    // --- KPI boxes ---
    $html .= '
    <div class="section">
        <div class="section-title">Totales Generales</div>
        <div style="margin-bottom:12px;">
            <div class="kpi-box">
                <span class="kpi-num">' . $totalVentas . '</span>
                <span class="kpi-lbl">Vehículos Atendidos</span>
            </div>
            <div class="kpi-box">
                <span class="kpi-num">' . fmtVE($totalLitros) . ' L</span>
                <span class="kpi-lbl">Litros Vendidos</span>
            </div>
            <div class="kpi-box">
                <span class="kpi-num">' . fmtVE($totalGeneralBs) . '</span>
                <span class="kpi-lbl">Total (Bs)</span>
            </div>
        </div>
        <table class="table summary-table">
            <tr>
                <th width="35%">Tasa del Día:</th>
                <td width="15%">' . fmtVE($tasaDia) . ' Bs</td>
                <th width="35%">Empleado:</th>
                <td width="15%">' . htmlspecialchars($empleado) . '</td>
            </tr>
        </table>
    </div>';

    // --- Desglose por Combustible ---
    if (!empty($combustibles)) {
        $html .= '
        <div class="section">
            <div class="section-title">Desglose por Tipo de Combustible</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Combustible</th>
                        <th class="center">Vehículos Atendidos</th>
                        <th class="right">Litros</th>' .
                        ($isDivisaReport ? '<th class="right">Divisa ($)</th>' : '') .
                        '<th class="right">Total Bs</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($combustibles as $nombre => $info) {
            $html .= '
                    <tr>
                        <td><strong>' . htmlspecialchars($nombre) . '</strong></td>
                        <td class="center">' . $info['cant'] . '</td>
                        <td class="right">' . fmtVE($info['litros']) . ' L</td>' .
                        ($isDivisaReport ? '<td class="right">' . fmtVE($info['monto_divisa']) . ' $</td>' : '') .
                        '<td class="right">' . fmtVE($info['monto_bs']) . ' Bs</td>
                    </tr>';
        }

        $html .= '
                    <tr class="total-row">
                        <td>TOTAL</td>
                        <td class="center">' . $totalVentas . '</td>
                        <td class="right">' . fmtVE($totalLitros) . ' L</td>' .
                        ($isDivisaReport ? '<td class="right">' . fmtVE($totalDivisa) . ' $</td>' : '') .
                        '<td class="right">' . fmtVE($totalGeneralBs) . ' Bs</td>
                    </tr>
                </tbody>
            </table>
        </div>';
    }

    // --- Desglose por Tipo de Vehículo ---
    $html .= '
    <div class="section">
        <div class="section-title">Desglose por Tipo de Vehículo</div>
        <table class="table">
            <thead>
                <tr>
                    <th width="40%">Tipo de Vehículo</th>
                    <th class="center">Cantidad</th>
                    <th class="right">Litros</th>
                </tr>
            </thead>
            <tbody>';

    if ($cantAuto > 0) {
        $html .= '<tr><td>Automóvil</td><td class="center">' . $cantAuto . '</td><td class="right">' . fmtVE($litrosAuto) . ' L</td></tr>';
    }
    if ($cantMoto > 0) {
        $html .= '<tr><td>Motocicleta</td><td class="center">' . $cantMoto . '</td><td class="right">' . fmtVE($litrosMoto) . ' L</td></tr>';
    }
    if ($cantCamion > 0) {
        $html .= '<tr><td>Camión</td><td class="center">' . $cantCamion . '</td><td class="right">' . fmtVE($litrosCamion) . ' L</td></tr>';
    }
    if ($cantOtrosVeh > 0) {
        $html .= '<tr><td>Otros</td><td class="center">' . $cantOtrosVeh . '</td><td class="right">' . fmtVE($litrosOtrosVeh) . ' L</td></tr>';
    }

    $html .= '
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td class="center">' . $totalVentas . '</td>
                    <td class="right">' . fmtVE($totalLitros) . ' L</td>
                </tr>
            </tbody>
        </table>
    </div>';

    // --- Desglose por Tipo de Pago ---
    $html .= '
    <div class="section">
        <div class="section-title">Desglose por Tipo de Pago</div>
        <table class="table">
            <thead>
                <tr>
                    <th width="40%">Tipo de Pago</th>
                    <th class="center">Cantidad</th>
                    <th class="right">Monto</th>
                </tr>
            </thead>
            <tbody>';

    if ($pagoDivisa > 0) {
        $html .= '<tr><td>Efectivo Divisa ($)</td><td class="center">' . $pagoDivisa . '</td><td class="right">' . fmtVE($montoDivisaTotal) . ' $</td></tr>';
    }
    if ($pagoEfectivo > 0) {
        $html .= '<tr><td>Efectivo (Bs)</td><td class="center">' . $pagoEfectivo . '</td><td class="right">' . fmtVE($montoEfectivoTotal) . ' Bs</td></tr>';
    }
    if ($pagoDebito > 0) {
        $html .= '<tr><td>Punto de Venta (Bs)</td><td class="center">' . $pagoDebito . '</td><td class="right">' . fmtVE($montoDebitoTotal) . ' Bs</td></tr>';
    }

    $html .= '
            </tbody>
        </table>
    </div>';

    // --- Gran Total destacado ---
    $html .= '
    <div class="section" style="text-align:center; margin-top:20px;">
        <div class="section-title" style="text-align:left;">Total General del Día</div>
        <div style="background:#e8f5e9; border:2px solid #4caf50; border-radius:8px; padding:15px;">
            <div style="font-size:12px; color:#555;">TOTAL GENERAL EN BOLÍVARES</div>
            <div class="big-total">' . fmtVE($totalGeneralBs) . ' Bs</div>
            ' . ($isDivisaReport && $totalDivisa > 0 ? '<div style="margin-top:8px; font-size:11px; color:#555;">Divisa recibida: <strong>' . fmtVE($totalDivisa) . ' $</strong> (convertida con tasa ' . fmtVE($tasaDia) . ')</div>' : '') . '
        </div>
    </div>';
}

// =========================================================================
// SECCIÓN: VENTAS (listado unificado)
// =========================================================================
elseif ($seccion === 'ventas') {
    $tituloPDF = 'LISTADO UNIFICADO DE VENTAS';
    $subtituloPDF = 'TODAS LAS VENTAS DEL DÍA';
    $nombreArchivo = 'Reporte_Ventas_Unificado_' . str_replace('/', '-', $fecha) . '.pdf';

    $html .= renderTablaVentas($dataDetallado, $isDivisaReport, true, $totalLitros, $totalEfectivo, $totalDebito, $totalDivisa, $totalGeneralBs);
}

// =========================================================================
// SECCIÓN: GASOLINA (listado solo gasolina)
// =========================================================================
elseif ($seccion === 'gasolina') {
    $tituloPDF = 'LISTADO DE VENTAS - GASOLINA';
    $subtituloPDF = 'DETALLE POR TIPO DE COMBUSTIBLE';
    $nombreArchivo = 'Reporte_Gasolina_' . str_replace('/', '-', $fecha) . '.pdf';

    $html .= renderTablaVentas($dataDetallado, $isDivisaReport, false, $totalLitros, $totalEfectivo, $totalDebito, $totalDivisa, $totalGeneralBs);
}

// =========================================================================
// SECCIÓN: DIESEL (listado solo diesel)
// =========================================================================
elseif ($seccion === 'diesel') {
    $tituloPDF = 'LISTADO DE VENTAS - DIESEL';
    $subtituloPDF = 'DETALLE POR TIPO DE COMBUSTIBLE';
    $nombreArchivo = 'Reporte_Diesel_' . str_replace('/', '-', $fecha) . '.pdf';

    $html .= renderTablaVentas($dataDetallado, $isDivisaReport, false, $totalLitros, $totalEfectivo, $totalDebito, $totalDivisa, $totalGeneralBs);
}

$html .= '
    <div class="footer">
        <p class="page-number"></p>
    </div>
</body>
</html>';

/**
 * Renderiza la tabla HTML con el listado de ventas + resumen al pie.
 */
function renderTablaVentas($ventas, $isDivisaReport, $conColumnaCombustible, $totalLitros, $totalEfectivo, $totalDebito, $totalDivisa, $totalGeneralBs) {
    $numColumnas = 3 + ($conColumnaCombustible ? 1 : 0) + ($isDivisaReport ? 1 : 0) + 2;

    $html = '
    <div class="section">
        <div class="section-title">Detalle de Ventas</div>
        <table class="table">
            <thead>
                <tr>
                    <th class="center" width="8%">Ticket</th>
                    <th width="22%">Vehículo</th>' .
                    ($conColumnaCombustible ? '<th width="15%">Combustible</th>' : '') .
                    '<th class="center" width="12%">Litros</th>' .
                    ($isDivisaReport ? '<th class="right" width="14%">Divisa ($)</th>' : '') .
                    '<th class="right" width="15%">Efectivo (Bs)</th>
                    <th class="right" width="14%">Débito (Bs)</th>
                </tr>
            </thead>
            <tbody>';

    if (empty($ventas)) {
        $html .= '<tr><td colspan="' . $numColumnas . '" class="empty-msg">No hay ventas para mostrar en esta fecha.</td></tr>';
    } else {
        foreach ($ventas as $v) {
            $nombreComb = $v['tipo_combustible'] ?? 'Gasolina';
            $html .= '
                <tr>
                    <td class="center">' . htmlspecialchars($v['numero_venta']) . '</td>
                    <td>' . htmlspecialchars($v['tipo_vehiculo']) . '</td>' .
                    ($conColumnaCombustible ? '<td>' . htmlspecialchars($nombreComb) . '</td>' : '') .
                    '<td class="center">' . fmtVE($v['cantidad_litros']) . ' L</td>' .
                    ($isDivisaReport ? '<td class="right">' . fmtVE($v['monto_divisa'] ?? 0) . '</td>' : '') .
                    '<td class="right">' . fmtVE($v['efectivob'] ?? 0) . '</td>
                    <td class="right">' . fmtVE($v['tarjeta_debito'] ?? 0) . '</td>
                </tr>';
        }

        $html .= '
                <tr class="total-row">
                    <td colspan="' . (2 + ($conColumnaCombustible ? 1 : 0)) . '" class="right">TOTAL GENERAL:</td>
                    <td class="center">' . fmtVE($totalLitros) . ' L</td>' .
                    ($isDivisaReport ? '<td class="right">' . fmtVE($totalDivisa) . '</td>' : '') .
                    '<td class="right">' . fmtVE($totalEfectivo) . '</td>
                    <td class="right">' . fmtVE($totalDebito) . '</td>
                </tr>';
    }

    $html .= '
            </tbody>
        </table>
    </div>

    <div class="section" style="margin-top:15px;">
        <table class="table summary-table">
            <tr>
                <th width="30%">Total Ventas:</th>
                <td width="20%">' . count($ventas) . '</td>
                <th width="30%">Total Litros:</th>
                <td width="20%">' . fmtVE($totalLitros) . ' L</td>
            </tr>';

    if ($isDivisaReport) {
        $html .= '
            <tr>
                <th>Efectivo (Bs):</th>
                <td>' . fmtVE($totalEfectivo) . ' Bs</td>
                <th>Divisa ($):</th>
                <td>' . fmtVE($totalDivisa) . ' $</td>
            </tr>
            <tr>
                <th>Tarjeta de Débito (Bs):</th>
                <td colspan="3">' . fmtVE($totalDebito) . ' Bs</td>
            </tr>';
    } else {
        $html .= '
            <tr>
                <th>Efectivo (Bs):</th>
                <td>' . fmtVE($totalEfectivo) . ' Bs</td>
                <th>Tarjeta de Débito (Bs):</th>
                <td>' . fmtVE($totalDebito) . ' Bs</td>
            </tr>';
    }

    $html .= '
            <tr class="total-row">
                <th colspan="2">TOTAL GENERAL (Bs):</th>
                <td colspan="2"><strong>' . fmtVE($totalGeneralBs) . ' Bs</strong></td>
            </tr>
        </table>
    </div>';

    return $html;
}

$dompdf->loadHtml($html);
set_time_limit(300);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream($nombreArchivo, ["Attachment" => false]);
exit();
?>