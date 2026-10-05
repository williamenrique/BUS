<?php
/**
 * Archivo: operatividad.php
 * Reporte de operatividad de flota en PDF.
 * El cuadro de resumen va alineado a la izquierda.
 */

// 1. Validación de la Petición
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['reporteData'])) {
    die("Acceso no autorizado o sin datos.");
}

$summaryData = json_decode($_POST['reporteData'], true);

if (empty($summaryData) || !is_array($summaryData)) {
    die("Datos de resumen no válidos.");
}

// Iniciar sesión como fallback
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------------
// RESOLVER NOMBRE DE INSTITUCIÓN
// Prioridad 1: POST (nombreInstitucion enviado desde el JS)
// Prioridad 2: id_institucion (POST/GET/Sesión) + consulta a BD
// Prioridad 3: Default hardcodeado (se aplica en encabezado.php)
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
            error_log("operatividad: Error al resolver institución: " . $e->getMessage());
        }
    }
}

// Título del reporte (va al header)
$tituloReporte = 'REPORTE DE OPERATIVIDAD DE FLOTA';

// 2. Carga del Entorno y Dependencias
require_once '../../system/core/Config/config.system.php';
require_once '../../system/core/Helpers/Helpers.php';
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 3. Calcular totales
$conteo_status = [
    'operativas' => array_sum(array_column($summaryData, 'operativas')),
    'inoperativas' => array_sum(array_column($summaryData, 'inoperativas')),
    'criticas' => array_sum(array_column($summaryData, 'criticas')),
    'total' => array_sum(array_column($summaryData, 'cantidad'))
];

// 4. Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// 5. Importar encabezado (usa $nombreInstitucion y $tituloReporte)
require_once '../encabezado.php';

// 6. Construir el HTML
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Operatividad</title>
    ' . $cssCommon . '
    <style>
        /* ============================================================
           Cuadro de resumen alineado a la IZQUIERDA.
           Sin border-radius ni page-break-inside avoid (evita el bug
           "Frame not found in cellmap" de Dompdf).
           ============================================================ */
        .summary-box {
            border: 1px solid #333;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 25px;
            margin-left: 0;
            margin-right: auto;
            width: 40%;
        }
        .summary-box table { width: 100%; border-collapse: collapse; }
        .summary-box td {
            border: none;
            padding: 5px;
            font-size: 12px;
        }

        /* ============================================================
           Tabla principal
           ============================================================ */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .table th, .table td {
            border: 1px solid #ccc;
            padding: 5px;
            text-align: left;
            font-size: 10px;
        }
        .table th {
            background-color: #f2f2f2;
            font-size: 11px;
        }
        .table thead { display: table-header-group; }
        .table tr { page-break-inside: avoid; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    ' . $headerHtml . '
    ' . $footerHtml . '

    <div class="summary-box">
        <table>
            <tr>
                <td><strong>Operatividad:</strong></td>
                <td style="text-align: right;">' . intval($conteo_status['operativas']) . '</td>
            </tr>
            <tr>
                <td><strong>Inoperativa:</strong></td>
                <td style="text-align: right;">' . intval($conteo_status['inoperativas']) . '</td>
            </tr>
            <tr>
                <td><strong>Crítica:</strong></td>
                <td style="text-align: right;">' . intval($conteo_status['criticas']) . '</td>
            </tr>
            <tr style="border-top: 1px solid #ccc;">
                <td><strong>TOTAL de flota:</strong></td>
                <td style="text-align: right;"><strong>' . intval($conteo_status['total']) . '</strong></td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Grupo (Modelo / Combustible / Transmisión)</th>
                <th style="text-align: center;">Cantidad</th>
                <th style="text-align: center;">Operativas</th>
                <th style="text-align: center;">Inoperativas</th>
                <th style="text-align: center;">Críticas</th>
            </tr>
        </thead>
        <tbody>';

foreach ($summaryData as $group) {
    $html .= '
            <tr>
                <td>' . htmlspecialchars($group['groupName'] ?? '') . '</td>
                <td style="text-align: center;">' . intval($group['cantidad'] ?? 0) . '</td>
                <td style="text-align: center;">' . intval($group['operativas'] ?? 0) . '</td>
                <td style="text-align: center;">' . intval($group['inoperativas'] ?? 0) . '</td>
                <td style="text-align: center;">' . intval($group['criticas'] ?? 0) . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Reporte_Operatividad_" . date('Y-m-d') . ".pdf", ["Attachment" => false]);
?>