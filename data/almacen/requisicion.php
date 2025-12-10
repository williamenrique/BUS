<?php
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['reporteData'])) {
    die('Acceso no autorizado.');
}

// Decodificar los datos JSON recibidos del formulario
$reporteData = json_decode($_POST['reporteData'], true);

if (!$reporteData) {
    die('Error: No se recibieron datos válidos para generar el reporte.');
}

$dataInfo = $reporteData;
$dataArt = $reporteData['articulos'] ?? [];

// Determinar el título y los campos a mostrar según el estado de la orden
$tituloReporte = "ORDEN DE REQUISICIÓN";
$estado = $dataInfo['status_requisicion'] ?? 1;

if ($estado == 2) {
    $tituloReporte = "ORDEN DE SERVICIO APROBADA";
} elseif ($estado == 3) {
    $tituloReporte = "ORDEN DE DESPACHO";
}

// Configurar opciones de Dompdf
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Cargar logo
require_once '../../system/core/Config/config.system.php';
$logoUrl = BASE_URL . 'src/img/logo.png';
$logoHtml = '<img src="' . $logoUrl . '" class="logo">';

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($tituloReporte) . '</title>
    <style>
        @page { margin: 20mm 15mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #333; }
        .header { width: 100%; text-align: center; margin-bottom: 50px; position: relative; }
        .header .logo { position: absolute; top: 0; left: 0; width: 80px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header h2 { margin: 0; font-size: 16px; font-weight: normal; }
        .info-orden-table { width: 100%; margin-bottom: 20px; }
        .info-orden-table td { border: none; padding: 0; vertical-align: middle; }
        .info-orden-table .numero-orden { text-align: right; font-weight: bold; font-size: 14px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 13px; font-weight: bold; background-color: #e8eaf6; padding: 8px; border-radius: 4px; margin-bottom: 10px; color: #1a237e; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #c5cae9; padding: 8px; text-align: left; }
        .table th { background-color: #f1f3f9; font-weight: bold; }
        .table .center { text-align: center; }
        .footer-section { margin-top: 30px; }
        .footer-section .observacion { width: 60%; float: left; }
        .footer-section .responsable { width: 35%; float: right; text-align: center; }
        .footer-section::after { content: ""; display: table; clear: both; }
        .footer { position: fixed; bottom: -20px; left: 0px; right: 0px; height: 50px; text-align: center; font-size: 9px; color: #777; }
        .page-number:before { content: "Página " counter(page); }
    </style>
</head>
<body>
    <div class="header">
        ' . $logoHtml . '
        <h1>SERVICIO SOCIALISTA DE LOGISTICA,</h1>
        <h2>MANTENIMIENTO Y TRANSPORTE DEL ESTADO YARACUY</h2>
        <p>Fecha de Creación: ' . date('d/m/Y') . '</p>
    </div>

    <table class="info-orden-table">
        <tr>
            <td><strong>Fecha:</strong> ' . date("d/m/Y", strtotime($dataInfo['fecha_requisicion'])) . '</td>
            <td class="numero-orden">' . htmlspecialchars($tituloReporte) . ' N°: ' . str_pad($dataInfo['id_despacho_flujo'], 6, "0", STR_PAD_LEFT) . '</td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Información de la Unidad</div>
        <table class="table">
            <tr>
                <th width="15%">Unidad:</th>
                <td>' . htmlspecialchars($dataInfo['id_unidad']) . '</td>
                <th width="15%">Modelo:</th>
                <td>' . htmlspecialchars($dataInfo['modelo_unidad']) . '</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Personal Involucrado</div>
        <table class="table">
            <tr>
                <th width="20%">Solicitante:</th>
                <td>' . htmlspecialchars($dataInfo['jefe_patio_nombre']) . '</td>
            </tr>
            <tr>
                <th>Mecánico:</th>
                <td>' . htmlspecialchars($dataInfo['mecanico_nombre'] ?? $dataInfo['mecanico_cedula']) . '</td>
            </tr>';

// Mostrar Operador y Despachador solo si la orden ya fue despachada
if ($estado == 3) {
    $html .= '
            <tr>
                <th>Operador:</th>
                <td>' . htmlspecialchars($dataInfo['operador_final']) . '</td>
            </tr>
            <tr>
                <th>Despachador:</th>
                <td>' . htmlspecialchars($dataInfo['despachador_final']) . '</td>
            </tr>';
}
$html .= '
        </table>
    </div>

    <div class="section">
        <div class="section-title">Artículos Solicitados</div>
        <table class="table">
            <thead>
                <tr>
                    <th class="center" width="15%">Código</th>
                    <th width="65%">Descripción</th>
                    <th class="center" width="20%">Cantidad</th>
                </tr>
            </thead>
            <tbody>';

if (empty($dataArt)) {
    $html .= '<tr><td colspan="3" class="center">No se solicitaron artículos.</td></tr>';
} else {
    foreach ($dataArt as $row) {
        $html .= '
            <tr>
                <td class="center">' . htmlspecialchars($row["id_producto"]) . '</td>
                <td>' . htmlspecialchars($row['producto']) . ' (' . htmlspecialchars($row['present_producto']) . ')</td>
                <td class="center">' . htmlspecialchars($row['cant_despacho']) . '</td>
            </tr>';
    }
}

$html .= '
            </tbody>
        </table>
    </div>

    <div class="footer-section">
        <div class="observacion">
            <strong>Diagnóstico / Observación:</strong>
            <p>' . (!empty($dataInfo['diagnostico']) ? htmlspecialchars($dataInfo['diagnostico']) : 'Ninguna.') . '</p>
        </div>
        <div class="responsable">
            <strong>Responsable:</strong>
            <p style="margin-top: 40px; border-top: 1px solid #333; padding-top: 5px;">' . htmlspecialchars($dataInfo['jefe_patio_nombre']) . '</p>
            <p>Firma y Sello</p>
        </div>
    </div>

    <div class="footer">
        <p class="page-number"></p>
    </div>
</body>
</html>';

// Cargar el HTML en Dompdf
$dompdf->loadHtml($html);

// Establecer el tamaño de papel y la orientación
$dompdf->setPaper('A4', 'portrait');

// Renderizar el HTML a PDF
$dompdf->render();

// Generar el nombre del archivo
$filename = "Requisicion_" . str_pad($dataInfo['id_despacho_flujo'], 6, "0", STR_PAD_LEFT) . ".pdf";

// Enviar el PDF al navegador para que se muestre (no forzar descarga)
$dompdf->stream($filename, ["Attachment" => false]);
exit();

?>