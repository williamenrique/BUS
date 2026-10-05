<?php
/**
 * Archivo: historial_flota.php
 * PDF de la hoja de vida de una unidad.
 */

set_time_limit(0);
require_once '../../system/core/Config/config.system.php';
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_POST['reporteData']) || !isset($_POST['unidadData'])) {
    die("No se recibieron datos para generar el reporte.");
}

$reporteData = json_decode($_POST['reporteData'], true);
$unidadData = json_decode($_POST['unidadData'], true);

if (json_last_error() !== JSON_ERROR_NONE || empty($reporteData)) {
    die("Los datos recibidos son inválidos.");
}

$items = $reporteData['items'] ?? [];
$counts = $reporteData['counts'] ?? [
    'despacho' => 0,
    'mantenimiento' => 0,
    'aceite' => 0,
    'status' => 0
];

// Iniciar sesión como fallback
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------------
// RESOLVER NOMBRE DE INSTITUCIÓN
// ------------------------------------------------------------------
$nombreInstitucion = '';

if (!empty($unidadData['institucion'])) {
    $nombreInstitucion = $unidadData['institucion'];
} elseif (!empty($_POST['nombreInstitucion'])) {
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
            error_log("historial_flota: Error al resolver institución: " . $e->getMessage());
        }
    }
}

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$tituloReporte = 'Hoja de Vida';
require_once '../encabezado.php';

$css = $cssCommon . '
    <style>
        /* ============================================================
           Caja de información de la unidad
           (tabla simple, sin page-break-inside avoid ni thead group
           para evitar el bug "Frame not found in cellmap" de Dompdf)
           ============================================================ */
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 20px;
        }
        .info-box table { width: 100%; border-collapse: collapse; }
        .info-box td {
            vertical-align: top;
            border: none;
            padding: 3px;
            font-size: 11px;
        }
        .info-label { font-weight: bold; color: #555; }

        /* ============================================================
           Tabla de resumen de eventos (una sola fila)
           ============================================================ */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            margin-bottom: 25px;
        }
        .summary-table th, .summary-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: center;
            font-size: 10px;
        }
        .summary-table th {
            background-color: #e9ecef;
        }

        /* ============================================================
           Timeline de eventos
           ============================================================ */
        .timeline-item {
            margin-bottom: 15px;
            border-left: 3px solid #ccc;
            padding-left: 15px;
            page-break-inside: avoid;
        }
        .timeline-header {
            background-color: #f1f1f1;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 11px;
        }
        .timeline-date {
            float: right;
            font-size: 10px;
            color: #666;
        }
        .timeline-body { padding: 5px 10px; font-size: 10px; }
        .timeline-footer {
            font-size: 9px;
            color: #888;
            margin-top: 5px;
            font-style: italic;
        }

        .badge {
            padding: 2px 6px;
            color: white;
            font-size: 9px;
            font-weight: bold;
        }
        .bg-despacho { background-color: #007bff; }
        .bg-mantenimiento { background-color: #17a2b8; }
        .bg-aceite { background-color: #ffc107; color: black; }
        .bg-status { background-color: #6c757d; }

        ul { margin: 5px 0; padding-left: 20px; }

        .h3-section {
            font-size: 12px;
            margin-top: 10px;
            margin-bottom: 10px;
        }
    </style>
';

$html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Historial de Unidad</title>' . $css . '</head><body>';

$html .= $headerHtml;
$html .= $footerHtml;

// Información de la Unidad
$html .= '
    <div class="info-box">
        <table>
            <tr>
                <td><span class="info-label">Unidad:</span> ' . htmlspecialchars($unidadData['id'] ?? 'N/A') . '</td>
                <td><span class="info-label">Marca:</span> ' . htmlspecialchars($unidadData['marca'] ?? 'N/A') . '</td>
                <td><span class="info-label">Modelo:</span> ' . htmlspecialchars($unidadData['modelo'] ?? 'N/A') . '</td>
                <td><span class="info-label">VIN:</span> ' . htmlspecialchars($unidadData['vin'] ?? 'N/A') . '</td>
            </tr>
        </table>
    </div>';

// Resumen de Eventos
$html .= '
    <table class="summary-table">
        <thead>
            <tr>
                <th>Ordenes de Despacho</th>
                <th>Servicios / Mantenimiento</th>
                <th>Cambios de Aceite</th>
                <th>Cambios de Estado</th>
                <th>Total Eventos</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>' . intval($counts['despacho'] ?? 0) . '</td>
                <td>' . intval($counts['mantenimiento'] ?? 0) . '</td>
                <td>' . intval($counts['aceite'] ?? 0) . '</td>
                <td>' . intval($counts['status'] ?? 0) . '</td>
                <td><strong>' . count($items) . '</strong></td>
            </tr>
        </tbody>
    </table>';

// Listado de Eventos
$html .= '<h3 class="h3-section">Detalle de Eventos</h3>';

if (empty($items)) {
    $html .= '<p style="text-align:center; color:#666;">No se encontraron eventos registrados para esta unidad con los filtros aplicados.</p>';
} else {
    foreach ($items as $item) {
        $detalles = json_decode($item['detalles'] ?? '{}', true);
        if (!is_array($detalles)) {
            $detalles = [];
        }
        $tipo = $item['tipo'] ?? '';
        $fecha = !empty($item['fecha']) ? date('d/m/Y', strtotime($item['fecha'])) : '';

        $titulo = strtoupper(str_replace('_', ' ', $tipo));
        if ($tipo !== 'aceite') {
            $titulo .= " #" . ($item['id_evento'] ?? '');
        }
        $usuario = $item['usuario'] ?? 'Sistema';

        $badgeClass = 'bg-' . $tipo;
        $contenido = '';

        switch ($tipo) {
            case 'despacho':
                $contenido = '<strong>Observación:</strong> ' . htmlspecialchars($detalles['observacion'] ?? 'Ninguna') . '<br>';
                if (!empty($detalles['articulos']) && is_array($detalles['articulos'])) {
                    $contenido .= '<strong>Artículos:</strong><ul>';
                    foreach ($detalles['articulos'] as $art) {
                        $contenido .= '<li>' . htmlspecialchars($art['cant_despacho'] ?? '') . 'x ' . htmlspecialchars($art['producto'] ?? '') . '</li>';
                    }
                    $contenido .= '</ul>';
                }
                break;
            case 'aceite':
                $contenido = 'Cambio de aceite registrado.<br>';
                $contenido .= '<strong>KM Cambio:</strong> ' . number_format(floatval($detalles['kilometraje_cambio'] ?? 0)) . '<br>';
                $contenido .= '<strong>KM Anterior:</strong> ' . number_format(floatval($detalles['kilometraje_anterior'] ?? 0)) . '<br>';
                $contenido .= '<strong>Próximo Cambio:</strong> ' . number_format(floatval($detalles['kilometraje_proximo_cambio'] ?? 0));
                break;
            case 'mantenimiento':
                $tipoMant = (($detalles['tipo_mantenimiento'] ?? '') == 'c') ? 'Correctivo' : 'Preventivo';
                $contenido = '<strong>Tipo:</strong> ' . $tipoMant . '<br>';
                $contenido .= '<strong>Diagnóstico:</strong> ' . htmlspecialchars($detalles['diagnostico'] ?? '');
                break;
            case 'status':
                $contenido = 'Cambio de estado a: <strong>' . htmlspecialchars($detalles['status_texto'] ?? '') . '</strong><br>';
                $contenido .= '<strong>Motivo:</strong> ' . htmlspecialchars($detalles['motivo'] ?? '');
                break;
        }

        $html .= '
        <div class="timeline-item">
            <div class="timeline-header">
                <span><span class="badge ' . $badgeClass . '">' . strtoupper($tipo) . '</span> ' . $titulo . '</span>
                <span class="timeline-date">' . $fecha . '</span>
            </div>
            <div class="timeline-body">
                ' . $contenido . '
            </div>
            <div class="timeline-footer">
                Registrado por: ' . htmlspecialchars($usuario) . '
            </div>
        </div>';
    }
}

$html .= '</body></html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Hoja_Vida_" . ($unidadData['id'] ?? 'unidad') . "_" . date('Ymd') . ".pdf", array("Attachment" => 0));
?>