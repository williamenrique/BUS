<?php
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reportData'])) {
    $reporteData = json_decode($_POST['reportData'], true);
    $title = $reporteData['title'] ?? 'Reporte de Inventario';
    $data = $reporteData['data'] ?? [];

    // Agrupar productos por ubicación
    $productosAgrupados = [];
    foreach ($data as $producto) {
        $ubicacion = $producto['ubicacion'] ?: 'Sin Ubicación Asignada';
        $productosAgrupados[$ubicacion][] = $producto;
    }
    ksort($productosAgrupados); // Ordenar ubicaciones alfabéticamente

    // Configurar opciones de Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Helvetica');

    // ------------------------------------------------------------------
    // RESOLVER INSTITUCIÓN DESDE DATOS DEL REPORTE (POST)
    // ------------------------------------------------------------------
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($nombreInstitucion) || empty($nombreInstitucion)) {
        $idInstitucion = null;

        if (isset($_POST['reportData'])) {
            $reporteData = json_decode($_POST['reportData'], true);
            if (is_array($reporteData)) {
                if (isset($reporteData['id_institucion'])) {
                    $idInstitucion = $reporteData['id_institucion'];
                } elseif (isset($reporteData[0]['id_institucion'])) {
                    $idInstitucion = $reporteData[0]['id_institucion'];
                } elseif (isset($reporteData['orden']['id_institucion'])) {
                    $idInstitucion = $reporteData['orden']['id_institucion'];
                }
            }
        }

        if (!$idInstitucion && isset($_SESSION['id_institucion']) && !empty($_SESSION['id_institucion'])) {
            $idInstitucion = $_SESSION['id_institucion'];
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
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result && !empty($result['nombre'])) {
                    $nombreInstitucion = $result['nombre'];
                    error_log("reporte_inventario: Institución encontrada id=$idInstitucion nombre=" . $nombreInstitucion);
                } else {
                    error_log("reporte_inventario: Institución NO encontrada id=$idInstitucion");
                }
            } catch (Exception $e) {
                error_log("reporte_inventario ERROR: " . $e->getMessage());
            }
        }

        if (!isset($nombreInstitucion) || empty($nombreInstitucion)) {
            $nombreInstitucion = 'INSTITUCIÓN NO ENCONTRADA (id=' . ($idInstitucion ?? 'null') . ')';
        }
    }

    // Título del reporte (va al header unificado)
    $tituloReporte = $title;

    // Importar encabezado estandarizado
    require_once '../encabezado.php';

    $dompdf = new Dompdf($options);

    // Contenido HTML del PDF
    $html = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($title) . '</title>
        ' . $cssCommon . '
        <style>
            /* ==================================================
               Estilos específicos del cuerpo del reporte.
               El header ya viene del encabezado unificado (fijo).
               ================================================== */
            .location-header {
                background-color: #e0e0e0;
                font-weight: bold;
                padding: 8px;
                margin-top: 20px;
                margin-bottom: 5px;
                border-radius: 4px;
                font-size: 12px;
                page-break-after: avoid;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 5px;
                page-break-inside: auto;
            }
            th, td {
                border: 1px solid #ccc;
                padding: 6px;
                text-align: left;
                font-size: 10px;
            }
            th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            thead { display: table-header-group; }   /* repite cabecera si la tabla salta de página */
            tr { page-break-inside: avoid; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
        </style>
    </head>
    <body>
        ' . $headerHtml . '
        ' . $footerHtml . '
    ';

    foreach ($productosAgrupados as $ubicacion => $productos) {
        $html .= '<div class="location-header">Ubicación: ' . htmlspecialchars($ubicacion) . '</div>';
        $html .= '
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">ID</th>
                    <th style="width: 55%;">Producto</th>
                    <th style="width: 20%;" class="text-center">Presentación</th>
                    <th style="width: 15%;" class="text-right">Cantidad Actual</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($productos as $p) {
            $html .= '<tr>
                        <td>' . htmlspecialchars($p['id_producto']) . '</td>
                        <td>' . htmlspecialchars($p['producto']) . '</td>
                        <td class="text-center">' . htmlspecialchars($p['present_producto']) . '</td>
                        <td class="text-right">' . htmlspecialchars(number_format($p['cant_producto'], 2)) . '</td>
                      </tr>';
        }
        $html .= '</tbody></table>';
    }

    $html .= '</body></html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("reporte_inventario_" . date('Y-m-d') . ".pdf", ["Attachment" => false]);
} else {
    echo "Acceso no permitido.";
}
?>