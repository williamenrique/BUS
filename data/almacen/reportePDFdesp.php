<?php
require_once  '../dompdf/autoload.inc.php';

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
$dataArt = $reporteData['articulos'];

// Configurar opciones de Dompdf
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // Necesario para cargar imágenes externas si las hubiera
$dompdf = new Dompdf($options);

// Importar encabezado estandarizado
require_once '../encabezado.php';

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Despacho</title>
    ' . $cssCommon . '
    <style>
        .info-orden-table { width: 100%; margin-bottom: 20px; margin-top: 40px; }
        .info-orden-table td { border: none; padding: 5px 0; vertical-align: bottom; }
        .info-orden-table .numero-orden { text-align: right; font-weight: bold; font-size: 14px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 13px; font-weight: bold; background-color: #e8eaf6; padding: 8px; border-radius: 4px; margin-bottom: 10px; color: #1a237e; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #c5cae9; padding: 8px; text-align: left; }
        .table th { background-color: #f1f3f9; font-weight: bold; }
        .table .center { text-align: center; }
        .table .right { text-align: right; }
        .footer-section { margin-top: 30px; }
        .footer-section .observacion { width: 60%; float: left; }
        .footer-section .responsable { width: 35%; float: right; text-align: center; }
        .footer-section::after { content: ""; display: table; clear: both; }
    </style>
</head>
<body>
    ' . $headerHtml . '
    ' . $footerHtml . '

    <table class="info-orden-table">
        <tr>
            <td><strong>Fecha:</strong> ' . date("d/m/Y", strtotime($dataInfo['fecha_despacho'])) . '</td>
            <td class="numero-orden">ORDEN DE DESPACHO N°: ' . str_pad($dataInfo['id_despacho'], 6, "0", STR_PAD_LEFT) . '</td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Información de la Unidad</div>
        <table class="table">
            <tr>
                <th width="15%">Unidad:</th>
                <td>' . htmlspecialchars($dataInfo['id_unidad']) . '</td>
                <th width="15%">Marca:</th>
                <td>' . htmlspecialchars($dataInfo['marca_unidad']) . '</td>
            </tr>
            <tr>
                <th>Modelo:</th>
                <td>' . htmlspecialchars($dataInfo['modelo_unidad']) . '</td>
                <th>VIN:</th>
                <td>' . htmlspecialchars($dataInfo['vim_unidad']) . '</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Personal Involucrado</div>
        <table class="table">
            <tr>
                <th width="20%">Operador:</th>
                <td>' . htmlspecialchars($dataInfo['operador_nombre']) . '</td>
            </tr>
            <tr>
                <th>Mecánico:</th>
                <td>' . htmlspecialchars($dataInfo['mecanico_nombre']) . '</td>
            </tr>
            <tr>
                <th>Despachador:</th>
                <td>' . htmlspecialchars($dataInfo['despachador_nombre']) . '</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Artículos Despachados</div>
        <table class="table">
            <thead>
                <tr>
                    <th class="center" width="15%">Código</th>
                    <th width="50%">Descripción</th>
                    <th class="center" width="15%">Cantidad</th>
                    <th class="center" width="20%">Ubicación</th>
                </tr>
            </thead>
            <tbody>';

if (empty($dataArt)) {
    $html .= '<tr><td colspan="4" class="center">No se despacharon artículos.</td></tr>';
} else {
    foreach ($dataArt as $row) {
        $html .= '
            <tr>
                <td class="center">' . htmlspecialchars($row["id_producto"]) . '</td>
                <td>' . htmlspecialchars($row['producto']) . '</td>
                <td class="center">' . htmlspecialchars($row['cant_despacho']) . '</td>
                <td class="center">' . htmlspecialchars($row['ubicacion']) . '</td>
            </tr>';
    }
}

$html .= '
            </tbody>
        </table>
    </div>

    <div class="footer-section">
        <div class="observacion">
            <strong>Observación:</strong>
            <p>' . (!empty($dataInfo['observacion']) ? htmlspecialchars($dataInfo['observacion']) : 'Ninguna.') . '</p>
        </div>
        <div class="responsable">
            <strong>Responsable:</strong>
            <p style="margin-top: 40px; border-top: 1px solid #333; padding-top: 5px;">' . htmlspecialchars($dataInfo['usuario_registro']) . '</p>
            <p>Firma y Sello</p>
        </div>
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
$filename = "Orden_Despacho_" . str_pad($dataInfo['id_despacho'], 6, "0", STR_PAD_LEFT) . ".pdf";

// Enviar el PDF al navegador para que se muestre (no forzar descarga)
$dompdf->stream($filename, ["Attachment" => false]);
exit();

?>