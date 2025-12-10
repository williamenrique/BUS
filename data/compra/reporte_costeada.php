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
require_once '../../system/core/Config/config.system.php';
$logoUrl = BASE_URL . 'src/img/logo.png';
$logoHtml = '';
$logoHtml = '<img src="' . $logoUrl . '" class="logo">';
// Construir el HTML para el PDF usando el método de concatenación
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orden de Compra Costeada #' . htmlspecialchars($info['id_despacho']) . '</title>
    <style>
        @page { margin: 20px 50px; }
        body { font-family: Arial, sans-serif; font-size: 10px; }
        .header { width: 100%; text-align: center; margin-bottom: 20px; position: relative; }
        .header img { position: absolute; top: 0; left: 0; width: 80px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header h2 { margin: 0; font-size: 16px; }
        .header p { margin: 2px 0; font-size: 10px; }
        .report-title { text-align: center; font-size: 16px; font-weight: bold; margin-bottom: 40px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 25px; page-break-inside: avoid; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
        thead { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .despacho-header { background-color: #cce5ff; font-weight: bold; }
        .despacho-footer { background-color: #cce5ff; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        ' . $logoHtml . '
        <h1>SERVICIO SOCIALISTA DE LOGISTICA,</h1>
        <h2>MANTENIMIENTO Y TRANSPORTE DEL ESTADO YARACUY</h2>
        <p>Fecha de Creacion: ' . date('d/m/Y') . '</p>
    </div>
    <div class="report-title">ORDEN DE COMPRA COSTEADA</div>
    <table>
        <thead>
            <tr class="despacho-header">
                <th>Despacho: #' . htmlspecialchars($info['id_despacho']) . '</th>
                <th class="text-center">Fecha: ' . htmlspecialchars($info['fecha_despacho']) . '</th>
                <th colspan="3" class="text-right">Unidad: ' . htmlspecialchars($info['id_unidad'] . ' - ' . $info['modelo_unidad']) . '</th>
            </tr>
            <tr>
                <th>Artículo</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Tasa (Bs.)</th>
                <th class="text-right">Monto (\$)</th>
                <th class="text-right">Monto (Bs.)</th>
            </tr>
        </thead>
        <tbody>';

$totalDivisa = 0;
$totalBs = 0;
foreach ($articulos as $row) {
    $totalDivisa += floatval($row['monto_divisa']);
    $totalBs += floatval($row['monto_bs']);
    $html .= '
        <tr>
            <td>' . htmlspecialchars($row['producto']) . '</td>
            <td class="text-center">' . htmlspecialchars($row['cant_despacho']) . '</td>
            <td class="text-right">' . number_format($row['tasa_dia'], 2, ',', '.') . '</td>
            <td class="text-right">' . number_format($row['monto_divisa'], 2, ',', '.') . '</td>
            <td class="text-right">' . number_format($row['monto_bs'], 2, ',', '.') . '</td>
        </tr>';
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

// Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

// Instanciar Dompdf
$dompdf = new Dompdf($options);

// Cargar el HTML
$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

// Renderizar el HTML como PDF
$dompdf->render();

// Enviar el PDF al navegador para su visualización/descarga
$dompdf->stream('Orden_Costeada_' . $info['id_despacho'] . '.pdf', array("Attachment" => 0));

?>

```

**Importante:** No olvides agregar el botón para generar el PDF en el pie de tu modal `modalVerDetalle` (en tu vista de compras), como te mostré en la conversación anterior.

Con estos cambios, la nueva funcionalidad estará lista.

<!--
[PROMPT_SUGGESTION]¿Podemos añadir una firma digital al final del PDF de la orden costeada?[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Ahora, optimiza la carga de la tabla de compras costeadas para que sea más rápida.[/PROMPT_SUGGESTION]
-->