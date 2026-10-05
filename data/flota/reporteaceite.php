<?php
/**
 * Archivo: reporteaceite.php
 * Reporte de estado de aceite en PDF.
 */

require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_POST['reporteData'])) {
    die("No se recibieron datos.");
}

$data = json_decode($_POST['reporteData'], true);
if (json_last_error() !== JSON_ERROR_NONE || empty($data) || !isset($data['items'])) {
    die("Los datos recibidos son inválidos o están incompletos.");
}

$items = $data['items'] ?? [];
$counts = $data['counts'] ?? [
    'Requerido' => 0,
    'Próximo' => 0,
    'Bien' => 0,
    'Sin Registro' => 0
];
$filtro = !empty($data['filtro']) ? $data['filtro'] : 'Varios';

// Iniciar sesión como fallback
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------------
// RESOLVER NOMBRE DE INSTITUCIÓN
// Prioridad 1: $data['nombre_institucion'] (enviado desde el JS)
// Prioridad 2: POST nombreInstitucion
// Prioridad 3: id_institucion (POST/GET/Sesión) + consulta a BD
// Prioridad 4: Default hardcodeado (se aplica en encabezado.php)
// ------------------------------------------------------------------
$nombreInstitucion = '';

if (!empty($data['nombre_institucion'])) {
    $nombreInstitucion = $data['nombre_institucion'];
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
            error_log("reporteaceite: Error al resolver institución: " . $e->getMessage());
        }
    }
}

// Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Importar encabezado
$tituloReporte = 'Reporte de Aceite';
require_once '../encabezado.php';

$css = $cssCommon . '
    <style>
        /* ============================================================
           Subtítulo con aire respecto al header
           ============================================================ */
        .subtitulo {
            text-align: center;
            color: #666;
            font-size: 11px;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        /* ============================================================
           Caja de leyenda (sin border-radius para evitar bugs de Dompdf)
           ============================================================ */
        .legend-box {
            border: 1px solid #ccc;
            padding: 10px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            font-size: 11px;
        }
        .legend-item { display: inline-block; margin-right: 20px; }

        /* ============================================================
           Estados (colores)
           ============================================================ */
        .status-requerido { color: #dc3545; font-weight: bold; }
        .status-proximo { color: #d39e00; font-weight: bold; }
        .status-bien { color: #28a745; font-weight: bold; }
        .status-sin_registro { color: #6c757d; font-weight: bold; }

        /* ============================================================
           Tabla de datos
           ============================================================ */
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .table-data th {
            background-color: #e9ecef;
            text-align: center;
            border: 1px solid #ccc;
            padding: 5px;
            font-size: 10px;
        }
        .table-data td {
            text-align: center;
            vertical-align: middle;
            border: 1px solid #ccc;
            padding: 5px;
            font-size: 10px;
        }
        .table-data thead { display: table-header-group; }
        .table-data tr { page-break-inside: avoid; }
        .text-left { text-align: left !important; }
    </style>
';

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Estado de Aceite</title>
    ' . $css . '
</head>
<body>
    ' . $headerHtml . '
    ' . $footerHtml . '

    <div class="subtitulo">Estados Incluidos: ' . htmlspecialchars($filtro) . '</div>

    <div class="legend-box">
        <strong>Resumen de Cantidades:</strong><br>
        <div style="margin-top: 5px;">
            <span class="legend-item">Requerido: <strong>' . (strpos($filtro, 'Requerido') !== false ? intval($counts['Requerido'] ?? 0) : '-') . '</strong></span>
            <span class="legend-item">Próximo: <strong>' . (strpos($filtro, 'Próximo') !== false ? intval($counts['Próximo'] ?? 0) : '-') . '</strong></span>
            <span class="legend-item">Bien: <strong>' . (strpos($filtro, 'Bien') !== false ? intval($counts['Bien'] ?? 0) : '-') . '</strong></span>
            <span class="legend-item">Sin Registro: <strong>' . (strpos($filtro, 'Sin Registro') !== false ? intval($counts['Sin Registro'] ?? 0) : '-') . '</strong></span>
            <span class="legend-item" style="float:right;">Total Listado: <strong>' . count($items) . '</strong></span>
        </div>
    </div>

    <table class="table-data">
        <thead>
            <tr>
                <th width="15%">Unidad</th>
                <th width="25%">Marca / Modelo</th>
                <th width="15%">KM Actual</th>
                <th width="15%">Último Cambio</th>
                <th width="15%">Próximo Cambio</th>
                <th width="15%">Estado</th>
            </tr>
        </thead>
        <tbody>';

if (empty($items)) {
    $html .= '<tr><td colspan="6" style="text-align:center; padding: 20px;">No se encontraron unidades con el criterio seleccionado.</td></tr>';
} else {
    foreach ($items as $item) {
        $estado = $item['estado'] ?? '';
        $estadoClass = 'status-' . strtolower(str_replace(' ', '_', $estado));
        $kmUltimo = !empty($item['ultimo_cambio_km']) ? number_format(floatval($item['ultimo_cambio_km'])) : 'N/A';
        $kmProximo = !empty($item['proximo_cambio_km']) ? number_format(floatval($item['proximo_cambio_km'])) : 'N/A';

        $html .= '
            <tr>
                <td><strong>' . htmlspecialchars($item['id_unidad'] ?? '') . '</strong></td>
                <td class="text-left">' . htmlspecialchars(($item['marca_unidad'] ?? '') . ' ' . ($item['modelo_unidad'] ?? '')) . '</td>
                <td>' . number_format(floatval($item['kilometraje_actual'] ?? 0)) . '</td>
                <td>' . $kmUltimo . '</td>
                <td>' . $kmProximo . '</td>
                <td class="' . $estadoClass . '">' . strtoupper(htmlspecialchars($estado)) . '</td>
            </tr>';
    }
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Reporte_Aceite_" . date('Ymd_His') . ".pdf", ["Attachment" => false]);
?>