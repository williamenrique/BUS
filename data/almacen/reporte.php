<?php
require_once  '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['reporteData'])) {
    die('Acceso no autorizado.');
}

// Decodificar los datos JSON recibidos (Array de 2 órdenes)
$ordenes = json_decode($_POST['reporteData'], true);

if (!$ordenes || count($ordenes) < 1) {
    die('Error: No se recibieron datos válidos para generar el reporte.');
}

// Configurar opciones de Dompdf
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Importar encabezado estandarizado
require_once '../encabezado.php';

// Estilos CSS específicos para ajustar 2 órdenes en una página
$css = $cssCommon . '
    <style>
        @page { margin: 10mm 10mm 10mm 10mm; } /* Margen reducido para aprovechar espacio */
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
        
        /* Sobrescribir estilos del encabezado para que no sea fijo y quepa en cada orden */
        .header { 
            position: relative !important; 
            top: auto !important; 
            left: auto !important; 
            right: auto !important; 
            height: auto !important; 
            margin-bottom: 5px;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 5px;
        }
        .header .logo {
            width: 50px !important;
            top: -20px !important;
            left: 0 !important;
        }
        .header h1 { font-size: 14px !important; }
        .header h2 { font-size: 10px !important; }
        
        .orden-container {
            box-sizing: border-box; /* Para que el padding se incluya en la altura */
            height: 44%; /* Reducido para evitar salto de página */
            margin-bottom: 0px; 
            border-bottom: 2px dashed #ccc; /* Separador entre órdenes */
            padding-top: 35px; /* Aumentado para separar el logo de la línea punteada */
            padding-bottom: 5px;
            position: relative;
        }
        .orden-container:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .info-orden-table { width: 100%; margin-bottom: 5px; margin-top: 0; }
        .info-orden-table td { border: none; padding: 2px 0; vertical-align: bottom; }
        .info-orden-table .numero-orden { text-align: right; font-weight: bold; font-size: 12px; }
        
        .section { margin-bottom: 10px; }
        .section-title { font-size: 11px; font-weight: bold; background-color: #e8eaf6; padding: 4px; border-radius: 4px; margin-bottom: 5px; color: #1a237e; }
        
        .table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .table th, .table td { border: 1px solid #c5cae9; padding: 4px; text-align: left; }
        .table th { background-color: #f1f3f9; font-weight: bold; }
        .table .center { text-align: center; }
        
        .footer-section { margin-top: 5px; width: 100%; }
        .firma-box { width: 30%; text-align: center; font-size: 9px; }
        .observacion-box { width: 38%; font-size: 9px; text-align: center; padding: 0 5px; }
        
        .firma-line { border-top: 1px solid #333; margin-top: 35px; padding-top: 2px; font-weight: bold; font-size: 9px; }
        .footer-section::after { content: ""; display: table; clear: both; }
    </style>
';

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Órdenes</title>
    ' . $css . '
</head>
<body>
    '; 

foreach ($ordenes as $index => $dataInfo) {
    $dataArt = $dataInfo['articulos'];
    
    $html .= '
    <div class="orden-container">
        ' . $headerHtml . '
        <table class="info-orden-table">
            <tr>
                <td><strong>Fecha:</strong> ' . date("d/m/Y", strtotime($dataInfo['fecha_despacho'])) . '</td>
                <td class="numero-orden">ORDEN DE DESPACHO N°: ' . str_pad($dataInfo['id_despacho'], 6, "0", STR_PAD_LEFT) . '</td>
            </tr>
        </table>

        <div class="section">
            <div class="section-title">Información de la Unidad y Personal</div>
            <table class="table">
                <tr>
                    <th width="15%">Unidad:</th>
                    <td>' . htmlspecialchars($dataInfo['id_unidad']) . '</td>
                    <th width="15%">Mecánico:</th>
                    <td>' . htmlspecialchars($dataInfo['mecanico_nombre']) . '</td>
                </tr>
                <tr>
                    <th>Modelo:</th>
                    <td>' . htmlspecialchars($dataInfo['modelo_unidad']) . '</td>
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
        // Limitamos visualmente si son muchos artículos para que quepan 2 órdenes
        $count = 0;
        foreach ($dataArt as $row) {
            if ($count < 8) { // Mostrar máximo 8 líneas por orden para asegurar ajuste
                $html .= '
                    <tr>
                        <td class="center">' . htmlspecialchars($row["id_producto"]) . '</td>
                        <td>' . htmlspecialchars($row['producto']) . '</td>
                        <td class="center">' . htmlspecialchars($row['cant_despacho']) . '</td>
                        <td class="center">' . htmlspecialchars($row['ubicacion']) . '</td>
                    </tr>';
            }
            $count++;
        }
        if ($count > 8) {
             $html .= '<tr><td colspan="4" class="center">... y ' . ($count - 8) . ' artículos más.</td></tr>';
        }
    }

    $html .= '
                </tbody>
            </table>
        </div>

        <div class="footer-section">
            <div class="firma-box" style="float: left;">
                <div class="firma-line">' . htmlspecialchars($dataInfo['usuario_registro']) . '</div>
                <div>Elaborado Por (Almacén)</div>
            </div>
            
            <div class="observacion-box" style="float: left;">
                <strong>Observación:</strong><br>
                ' . (!empty($dataInfo['observacion']) ? htmlspecialchars($dataInfo['observacion']) : 'Ninguna.') . '
            </div>

            <div class="firma-box" style="float: right;">
                    <div class="firma-line">Recibido Por</div>
                    <div>Firma y Sello</div>
            </div>
        </div>
    </div>';
}

$html .= '
    ' . $footerHtml . '
</body>
</html>';

// Cargar el HTML en Dompdf
$dompdf->loadHtml($html);

// Establecer el tamaño de papel y la orientación
$dompdf->setPaper('A4', 'portrait');

// Renderizar el HTML a PDF
$dompdf->render();

// Generar el nombre del archivo
$filename = "Ordenes_Lote_" . date('Ymd_His') . ".pdf";

// Enviar el PDF al navegador
$dompdf->stream($filename, ["Attachment" => false]);
exit();
?>
