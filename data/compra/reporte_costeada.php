<?php
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_POST['reporteData'])) {
    echo "No se recibieron datos para generar el reporte.";
    exit;
}

$data = json_decode($_POST['reporteData'], true);
if (json_last_error() !== JSON_ERROR_NONE || empty($data) || !isset($data['info']) || !isset($data['articulos'])) {
    echo "Los datos recibidos son inválidos o están incompletos.";
    exit;
}

$info = $data['info'] ?? [];
$articulos = $data['articulos'] ?? [];

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
            error_log("reporte_costeada: Error al resolver institución: " . $e->getMessage());
        }
    }
}

// Título del reporte
$tituloReporte = 'ORDEN DE COMPRA COSTEADA';

$numeroOrden = $info['numero_orden'] ?? $info['id_despacho'] ?? '';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// Importar encabezado estandarizado (usa $nombreInstitucion y $tituloReporte)
require_once '../encabezado.php';

// Construir el HTML para el PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orden de Compra Costeada #' . htmlspecialchars($numeroOrden) . '</title>
    ' . $cssCommon . '
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 25px;
            page-break-inside: auto;
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
        tr { page-break-inside: avoid; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .despacho-header { background-color: #cce5ff; font-weight: bold; }
        .despacho-footer { background-color: #cce5ff; font-weight: bold; }
    </style>
</head>
<body>
    ' . $headerHtml . '
    ' . $footerHtml . '
    <table>
        <thead>
            <tr class="despacho-header">
                <th>Orden: #' . htmlspecialchars($numeroOrden) . '</th>
                <th class="text-center">Fecha: ' . htmlspecialchars($info['fecha_despacho'] ?? '') . '</th>
                <th colspan="3" class="text-right">Unidad: ' . htmlspecialchars(($info['id_unidad'] ?? '') . ' - ' . ($info['modelo_unidad'] ?? '')) . '</th>
            </tr>
            <tr>
                <th>Artículo</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Tasa (Bs.)</th>
                <th class="text-right">Monto ($)</th>
                <th class="text-right">Monto (Bs.)</th>
            </tr>
        </thead>
        <tbody>';

$totalDivisa = 0;
$totalBs = 0;

if (!empty($articulos) && is_array($articulos)) {
    foreach ($articulos as $row) {
        $montoDivisa = floatval($row['monto_divisa'] ?? 0);
        $montoBs = floatval($row['monto_bs'] ?? 0);
        $totalDivisa += $montoDivisa;
        $totalBs += $montoBs;

        $html .= '
        <tr>
            <td>' . htmlspecialchars($row['producto'] ?? '') . '</td>
            <td class="text-center">' . htmlspecialchars($row['cant_despacho'] ?? '') . '</td>
            <td class="text-right">' . number_format(floatval($row['tasa_dia'] ?? 0), 2, ',', '.') . '</td>
            <td class="text-right">' . number_format($montoDivisa, 2, ',', '.') . '</td>
            <td class="text-right">' . number_format($montoBs, 2, ',', '.') . '</td>
        </tr>';
    }
}

$html .= '
        </tbody>
        <tfoot>
            <tr class="despacho-footer">
                <td>Total Artículos: ' . count($articulos) . '</td>
                <td colspan="2" class="text-right"><strong>Totales:</strong></td>
                <td class="text-right"><strong>$. ' . number_format($totalDivisa, 2, ',', '.') . '</strong></td>
                <td class="text-right"><strong>Bs. ' . number_format($totalBs, 2, ',', '.') . '</strong></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('Orden_Costeada_' . $numeroOrden . '.pdf', array("Attachment" => 0));
?>