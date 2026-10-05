<?php
set_time_limit(0);
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_POST['reporteData'])) {
    echo "No se recibieron datos para generar el reporte.";
    exit;
}

$decodedData = json_decode($_POST['reporteData'], true);

if (json_last_error() !== JSON_ERROR_NONE || empty($decodedData) || !isset($decodedData['data'])) {
    echo "Los datos recibidos son inválidos o están incompletos.";
    exit;
}

$reporteData = $decodedData['data'];
$fechaInicio = $decodedData['fechaInicio'] ?? null;
$fechaFin = $decodedData['fechaFin'] ?? null;
$searchValue = $decodedData['searchValue'] ?? '';

// Iniciar sesión como fallback
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------------
// RESOLVER NOMBRE DE INSTITUCIÓN
// Prioridad 1: POST (nombreInstitucion enviado desde el JS)
// Prioridad 2: id_institucion (POST/GET/Sesión) + consulta a BD
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
            error_log("reporte_costeadas_filtrado: Error al resolver institución: " . $e->getMessage());
        }
    }
}

// Título del reporte
$tituloReporte = 'REPORTE DETALLADO DE COMPRAS';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

// ------------------------------------------------------------------
// CALCULAR TOTALES GENERALES (versión robusta, sin array_merge)
// ------------------------------------------------------------------
$totalGeneralDivisa = 0;
$totalGeneralBs = 0;
$totalGeneralArticulos = 0;

foreach ($reporteData as $unidad) {
    $totalGeneralDivisa += floatval($unidad['total_divisa_unidad'] ?? 0);
    $totalGeneralBs += floatval($unidad['total_bs_unidad'] ?? 0);

    if (!empty($unidad['despachos']) && is_array($unidad['despachos'])) {
        foreach ($unidad['despachos'] as $despacho) {
            if (!empty($despacho['articulos']) && is_array($despacho['articulos'])) {
                foreach ($despacho['articulos'] as $articulo) {
                    $totalGeneralArticulos += floatval($articulo['cant_despacho'] ?? 0);
                }
            }
        }
    }
}

$dompdf = new Dompdf($options);

// Importar encabezado (usa $nombreInstitucion y $tituloReporte)
require_once '../encabezado.php';

// Leyenda de filtros
$leyendaFiltros = '<ul>';
if ($fechaInicio && $fechaFin) {
    $fechaInicioFormatted = date('d/m/Y', strtotime($fechaInicio));
    $fechaFinFormatted = date('d/m/Y', strtotime($fechaFin));
    $leyendaFiltros .= "<li><strong>Rango de Fechas:</strong> Del $fechaInicioFormatted al $fechaFinFormatted</li>";
}
if (!empty($searchValue)) {
    $leyendaFiltros .= "<li><strong>Término de Búsqueda:</strong> '" . htmlspecialchars($searchValue) . "'</li>";
}
if ($leyendaFiltros === '<ul>') {
    $leyendaFiltros .= "<li>No se aplicaron filtros específicos.</li>";
}
$leyendaFiltros .= '</ul>';

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Detallado de Compras Costeadas</title>
    ' . $cssCommon . '
    <style>
        /* ============================================================
           Leyendas superiores: Filtros + Totales en tabla de 2 columnas
           (reemplaza el hack display:-pdf-flex que fallaba en Dompdf)
           ============================================================ */
        .legend-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-top: 5px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .legend-table td {
            vertical-align: top;
            border: none;
            padding: 0;
        }
        .filter-legend {
            border: 1px solid #ccc;
            padding: 10px;
            font-size: 9px;
        }
        .filter-legend h4 {
            margin: 0 0 8px 0;
            font-size: 11px;
            border-bottom: 1px solid #eee;
            padding-bottom: 4px;
        }
        .filter-legend ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .totals-legend {
            border: 1px solid #a7d9ff;
            background-color: #f0f8ff;
        }
        .totals-legend h4 { color: #005a9e; }
        .totals-legend ul li { font-size: 10px; }

        /* ============================================================
           Tablas de unidades y despachos
           ============================================================ */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            page-break-inside: auto;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 5px;
            text-align: left;
            font-size: 10px;
        }
        thead { background-color: #f2f2f2; display: table-header-group; }
        tr { page-break-inside: avoid; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .unidad-header { background-color: #d9edf7; font-weight: bold; font-size: 11px; }
        .despacho-header { background-color: #e9f5ff; font-weight: bold; }
        .unidad-footer { background-color: #cce5ff; font-weight: bold; font-size: 11px; }
        .total-general-row { background-color: #a7d9ff; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>
    ' . $headerHtml . '
    ' . $footerHtml . '

    <table class="legend-table">
        <tr>
            <td style="width: 50%;">
                <div class="filter-legend">
                    <h4>Filtros Aplicados</h4>
                    ' . $leyendaFiltros . '
                </div>
            </td>
            <td style="width: 50%;">
                <div class="filter-legend totals-legend">
                    <h4>Totales del Reporte</h4>
                    <ul>
                        <li><strong>Total Artículos:</strong> ' . number_format($totalGeneralArticulos) . '</li>
                        <li><strong>Total Divisas:</strong> $' . number_format($totalGeneralDivisa, 2, ',', '.') . '</li>
                        <li><strong>Total Bolívares:</strong> Bs. ' . number_format($totalGeneralBs, 2, ',', '.') . '</li>
                    </ul>
                </div>
            </td>
        </tr>
    </table>
';

foreach ($reporteData as $unidad) {
    $html .= '
    <table>
        <thead>
            <tr class="unidad-header">
                <th colspan="7">UNIDAD: ' . htmlspecialchars($unidad['nombre_unidad'] ?? '') . '</th>
            </tr>
            <tr>
                <th class="text-center">N° Orden</th>
                <th class="text-center">Fecha Despacho</th>
                <th>Artículo</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Tasa Día</th>
                <th class="text-right">Monto Divisa ($)</th>
                <th class="text-right">Monto Bs.</th>
            </tr>
        </thead>
        <tbody>';

    if (!empty($unidad['despachos']) && is_array($unidad['despachos'])) {
        foreach ($unidad['despachos'] as $despacho) {
            if (!empty($despacho['articulos']) && is_array($despacho['articulos'])) {
                foreach ($despacho['articulos'] as $articulo) {
                    $numeroOrden = $articulo['numero_orden'] ?? $articulo['id_despacho'] ?? '';
                    $html .= '
                    <tr>
                        <td class="text-center">' . htmlspecialchars($numeroOrden) . '</td>
                        <td class="text-center">' . htmlspecialchars($articulo['fecha_despacho'] ?? '') . '</td>
                        <td>' . htmlspecialchars($articulo['producto'] ?? '') . '</td>
                        <td class="text-center">' . htmlspecialchars($articulo['cant_despacho'] ?? '') . '</td>
                        <td class="text-right">' . number_format(floatval($articulo['tasa_dia'] ?? 0), 2, ',', '.') . '</td>
                        <td class="text-right">' . number_format(floatval($articulo['monto_divisa'] ?? 0), 2, ',', '.') . '</td>
                        <td class="text-right">' . number_format(floatval($articulo['monto_bs'] ?? 0), 2, ',', '.') . '</td>
                    </tr>';
                }
            }
        }
    }

    $html .= '
        </tbody>
        <tfoot>
            <tr class="unidad-footer">
                <td colspan="5" class="text-right"><strong>TOTAL UNIDAD (' . htmlspecialchars($unidad['nombre_unidad'] ?? '') . '):</strong></td>
                <td class="text-right"><strong>' . number_format(floatval($unidad['total_divisa_unidad'] ?? 0), 2, ',', '.') . '</strong></td>
                <td class="text-right"><strong>' . number_format(floatval($unidad['total_bs_unidad'] ?? 0), 2, ',', '.') . '</strong></td>
            </tr>
        </tfoot>
    </table>';
}

$html .= '</body></html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("reporte_costeadas_" . date('Ymd') . ".pdf", array("Attachment" => 0));
?>