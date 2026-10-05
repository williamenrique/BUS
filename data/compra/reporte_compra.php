<?php
require_once '../dompdf/autoload.inc.php';

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

// Iniciar sesión como fallback
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------------
// RESOLVER NOMBRE DE INSTITUCIÓN
// Prioridad 1: POST (nombreInstitucion enviado desde el JS)
// Prioridad 2: id_institucion (POST/GET/Sesión) + consulta a BD
// Prioridad 3: Default hardcodeado (aplica en encabezado.php)
// ------------------------------------------------------------------
$nombreInstitucion = '';

if (!empty($_POST['nombreInstitucion'])) {
    $nombreInstitucion = $_POST['nombreInstitucion'];
} else {
    $idInstitucion = null;
    if (!empty($_POST['id_institucion'])) {
        $idInstitucion = intval($_POST['id_institucion']);
    } elseif (!empty($_GET['id_institucion'])) {
        $idInstitucion = intval($_GET['id_institucion']);
    } elseif (!empty($_SESSION['id_institucion'])) {
        $idInstitucion = intval($_SESSION['id_institucion']);
    }

    if ($idInstitucion) {
        try {
            require_once '../../system/core/Config/config.system.php';
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $stmt = $pdo->prepare("SELECT nombre FROM table_instituciones WHERE id_institucion = ?");
            $stmt->execute([$idInstitucion]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['nombre'])) {
                $nombreInstitucion = $row['nombre'];
            }
        } catch (Exception $e) {
            error_log("reporte_compra: Error al resolver institución: " . $e->getMessage());
        }
    }
}

// Título del reporte (va al header)
$tituloReporte = 'REPORTE DE COMPRAS POR UNIDAD';

// Calcular totales generales antes de construir el HTML
$grandTotalArticulos = 0;
$grandTotalDivisa = 0;
$grandTotalBs = 0;

foreach ($reporteData as $despacho) {
    if (!empty($despacho['articulos']) && is_array($despacho['articulos'])) {
        foreach ($despacho['articulos'] as $articulo) {
            $grandTotalArticulos += floatval($articulo['cant_despacho'] ?? 0);
        }
    }
    $grandTotalDivisa += floatval($despacho['total_divisa'] ?? 0);
    $grandTotalBs += floatval($despacho['total_bs'] ?? 0);
}

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$unidad = !empty($reporteData) && !empty($reporteData[0]['unidad'])
    ? htmlspecialchars($reporteData[0]['unidad'])
    : 'N/A';

// Importar encabezado estandarizado (usa $nombreInstitucion y $tituloReporte)
require_once '../encabezado.php';

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Compras por Unidad</title>
    ' . $cssCommon . '
    <style>
        .unidad-label {
            text-align: center;
            font-size: 12px;
            color: #555;
            margin-top: 10px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 5px;
            text-align: left;
            font-size: 10px;
        }
        thead {
            background-color: #f2f2f2;
            display: table-header-group;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .despacho-header {
            background-color: #d9edf7;
            font-weight: bold;
            font-size: 11px;
        }
        .despacho-footer {
            background-color: #cce5ff;
            font-weight: bold;
        }
    </style>
</head>
<body>
    ' . $headerHtml . '
    ' . $footerHtml . '

    <div class="unidad-label">Unidad: ' . $unidad . '</div>

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
    $numeroOrden = $despacho['numero_orden'] ?? $despacho['id_despacho'] ?? '';
    $html .= '
    <table>
        <thead>
            <tr class="despacho-header">
                <th>Orden: #' . htmlspecialchars($numeroOrden) . '</th>
                <th class="text-center">Fecha: ' . htmlspecialchars($despacho['fecha_despacho'] ?? '') . '</th>
                <th colspan="2" class="text-right">Tasa del Día: ' . number_format(floatval($despacho['tasa_dia'] ?? 0), 2, ',', '.') . ' Bs</th>
            </tr>
            <tr>
                <th style="width: 52%;">Artículo</th>
                <th class="text-center" style="width: 16%;">Cantidad</th>
                <th class="text-right" style="width: 16%;">Monto Divisa ($)</th>
                <th class="text-right" style="width: 16%;">Monto Bs.</th>
            </tr>
        </thead>
        <tbody>';

    if (!empty($despacho['articulos']) && is_array($despacho['articulos'])) {
        foreach ($despacho['articulos'] as $articulo) {
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($articulo['producto'] ?? '') . '</td>
                    <td class="text-center">' . htmlspecialchars($articulo['cant_despacho'] ?? '') . '</td>
                    <td class="text-right">' . number_format(floatval($articulo['monto_divisa'] ?? 0), 2, ',', '.') . '</td>
                    <td class="text-right">' . number_format(floatval($articulo['monto_bs'] ?? 0), 2, ',', '.') . '</td>
                </tr>';
        }
    }

    $html .= '
        </tbody>
        <tfoot>
            <tr class="despacho-footer">
                <td>Total Artículos: ' . count($despacho['articulos'] ?? []) . '</td>
                <td colspan="1"></td>
                <td class="text-right">$. ' . number_format(floatval($despacho['total_divisa'] ?? 0), 2, ',', '.') . '</td>
                <td class="text-right">Bs. ' . number_format(floatval($despacho['total_bs'] ?? 0), 2, ',', '.') . '</td>
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