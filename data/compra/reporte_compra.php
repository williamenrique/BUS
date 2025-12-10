<?php
require_once './dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_POST['reporteData'])) {
    echo "No se recibieron datos para generar el reporte.";
    exit;
}

$reporteData = json_decode($_POST['reporteData'], true);

if (json_last_error() !== JSON_ERROR_NONE || empty($reporteData)) {
    echo "Los datos recibidos son inválidos.";
    exit;
}

// Calcular totales generales antes de construir el HTML
$grandTotalArticulos = 0;
$grandTotalDivisa = 0;
$grandTotalBs = 0;

foreach ($reporteData as $despacho) {
    // Sumar la cantidad de cada artículo para obtener el total de unidades
    foreach ($despacho['articulos'] as $articulo) {
        $grandTotalArticulos += floatval($articulo['cant_despacho']);
    }
    $grandTotalDivisa += floatval($despacho['total_divisa']);
    $grandTotalBs += floatval($despacho['total_bs']);
}

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$unidad = !empty($reporteData) ? htmlspecialchars($reporteData[0]['unidad']) : 'N/A';
require_once '../system/core/Config/config.system.php';
$logoUrl = BASE_URL . 'src/img/logo.png';
$logoHtml = '';
$logoHtml = '<img src="' . $logoUrl . '" class="logo">';
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Compras por Unidad</title>
    <style>
        @page {
            margin: 20px 50px;
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
        .header p {
            margin: 2px 0;
            font-size: 10px;
        }
        .report-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 40px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px; /* Aumentamos el margen para más espacio */
            page-break-inside: avoid; /* Evita que la tabla se corte entre páginas */
        }
        th, td {
            border: 1px solid #ccc;
            padding: 5px;
            text-align: left;
        }
        thead {
            background-color: #f2f2f2;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .despacho-header {
            background-color: #d9edf7; /* Color azul claro del otro reporte */
            font-weight: bold;
            font-size: 11px;
        }
        .despacho-footer {
            background-color: #cce5ff; /* Color azul más oscuro del otro reporte */
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        ' . $logoHtml . '
        <h1>SERVICIO SOCIALISTA DE LOGISTICA,</h1>
        <h2>MANTENIMIENTO Y TRANSPORTE DEL ESTADO YARACUY</h2>
        <p>Fecha de Creacion: ' . date('d/m/Y') . '</p>
    </div>
        
    <div class="report-title">REPORTE DE COMPRAS POR UNIDAD: ' . $unidad . '</div>

    <!-- Tabla de Resumen General -->
    <table style="margin-bottom: 30px;">
        <thead>
            <tr style="background-color: #cce5ff; color: black; font-weight: bold">
                <th colspan="3" style="text-align: center; font-size: 12px;">RESUMEN GENERAL DEL PERÍODO</th>
            </tr>
            <tr>
                <th class="text-center">Total Unidades de Artículos</th>
                <th class="text-center">Total Divisas ($)</th>
                <th class="text-center">Total Bolívares (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center" style="font-size: 11px; font-weight: bold;">' . number_format($grandTotalArticulos, 2, ',', '.') . '</td>
                <td class="text-center" style="font-size: 11px; font-weight: bold;">$. ' . number_format($grandTotalDivisa, 2, ',', '.') . '</td>
                <td class="text-center" style="font-size: 11px; font-weight: bold;">Bs. ' . number_format($grandTotalBs, 2, ',', '.') . '</td>
            </tr>
        </tbody>
    </table>
';

foreach ($reporteData as $despacho) {
    $html .= '
    <table>
        <thead>
            <tr class="despacho-header">
                <th>Despacho: #' . htmlspecialchars($despacho['id_despacho']) . '</th>
                <th class="text-center">Fecha: ' . htmlspecialchars($despacho['fecha_despacho']) . '</th>
                <th colspan="2" class="text-right">Tasa del Día: ' . number_format($despacho['tasa_dia'], 2, ',', '.') . ' Bs</th>
            </tr>
            <tr>
                <th style="width: 52%;">Artículo</th>
                <th class="text-center" style="width: 16%;">Cantidad</th>
                <th class="text-right" style="width: 16%;">Monto Divisa ($)</th>
                <th class="text-right" style="width: 16%;">Monto Bs.</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($despacho['articulos'] as $articulo) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($articulo['producto']) . '</td>
                <td class="text-center">' . htmlspecialchars($articulo['cant_despacho']) . '</td>
                <td class="text-right">' . number_format($articulo['monto_divisa'], 2, ',', '.') . '</td>
                <td class="text-right">' . number_format($articulo['monto_bs'], 2, ',', '.') . '</td>
            </tr>';
    }

    $html .= '
        </tbody>
        <tfoot>
            <tr class="despacho-footer">
                <td>Total Artículos: ' . count($despacho['articulos']) . '</td>
                <td colspan="1"></td>
                <td class="text-right">$. ' . number_format($despacho['total_divisa'], 2, ',', '.') . '</td>
                <td class="text-right">Bs. ' . number_format($despacho['total_bs'], 2, ',', '.') . '</td>
            </tr>
        </tfoot>
    </table>';
}

$html .= '</body></html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("reporte_compras_" . $unidad . "_" . date('Ymd') . ".pdf", array("Attachment" => 0));
?>