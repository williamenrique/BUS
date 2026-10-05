<?php // Este archivo ahora es multipropósito
require_once '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_POST['reporteData']) || !isset($_POST['reporteTitulo'])) {
    http_response_code(400);
    echo 'Error: No se recibieron datos para generar el PDF.';
    exit;
}

// Iniciar sesión como fallback
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------------
// RESOLVER NOMBRE DE INSTITUCIÓN
// Prioridad 1: POST (enviado por el JS desde el controlador,
//               que a su vez lo resolvió desde id_institucion del menú)
// Prioridad 2: id_institucion vía POST/GET + consulta a BD
// Prioridad 3: Sesión
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
            error_log("reporte_bienes: Error al resolver institución: " . $e->getMessage());
        }
    }
}

$bienesAgrupados = json_decode($_POST['reporteData'], true);
$tituloReporte = $_POST['reporteTitulo'];

if ($bienesAgrupados === null || !is_array($bienesAgrupados)) {
    http_response_code(400);
    die('Error: Datos JSON no válidos.');
}

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

// Importar encabezado estandarizado (usa $nombreInstitucion y $tituloReporte)
require_once '../encabezado.php';

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($tituloReporte) . '</title>
    ' . $cssCommon . '
    <style>
        .department-title {
            font-size: 14px;
            font-weight: bold;
            background-color: #4a5568;
            color: #fff;
            padding: 8px;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .summary-container {
            margin-top: 5px;
            margin-bottom: 25px;
            border: 1px solid #e0e0e0;
            background-color: #fff;
        }
        .summary-container h3 {
            margin: 0;
            padding: 12px;
            font-size: 14px;
            text-align: center;
            background-color: #4a5568;
            color: #fff;
        }
        .summary-table { width: 100%; border-collapse: collapse; }
        .summary-table th {
            background-color: #edf2f7;
            padding: 8px;
            text-align: left;
            font-size: 11px;
            border: 1px solid #ddd;
        }
        .summary-table td {
            padding: 8px;
            border: 1px solid #eee;
            font-size: 10px;
        }
        .summary-table tfoot td {
            font-weight: bold;
            background-color: #edf2f7;
        }

        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td {
            border: 1px solid #c5cae9;
            padding: 6px;
            text-align: left;
            font-size: 10px;
        }
        .table th { background-color: #f1f3f9; font-weight: bold; }
        .table .center { text-align: center; }
        .table .right { text-align: right; }
    </style>
</head>
<body>
    ' . $headerHtml . '
    ' . $footerHtml . '';

if (empty($bienesAgrupados)) {
    $html .= '<p style="text-align:center; margin-top: 20px;">No se encontraron bienes para mostrar.</p>';
} else {
    // --- INICIO: Tabla de Resumen ---
    $html .= '<div class="summary-container">';
    $html .= '<h3>Resumen de Bienes</h3>';
    $html .= '<table class="summary-table">';
    $html .= '<thead><tr><th>Agrupación</th><th class="center">Cantidad de Bienes</th></tr></thead>';
    $html .= '<tbody>';

    $totalGeneral = 0;
    foreach ($bienesAgrupados as $departamento => $bienes) {
        $cantidad = count($bienes);
        $totalGeneral += $cantidad;
        $html .= '
            <tr>
                <td>' . htmlspecialchars($departamento) . '</td>
                <td class="center">' . $cantidad . '</td>
            </tr>';
    }

    $html .= '</tbody>';
    if (count($bienesAgrupados) > 1) {
        $html .= '
            <tfoot>
                <tr>
                    <td>Total General</td>
                    <td class="center">' . $totalGeneral . '</td>
                </tr>
            </tfoot>';
    }
    $html .= '</table></div>';
    // --- FIN: Tabla de Resumen ---

    foreach ($bienesAgrupados as $departamento => $bienes) {
        $html .= '<div class="department-title">' . htmlspecialchars($departamento) . '</div>';
        $html .= '
        <table class="table">
            <thead>
                <tr>
                    <th class="center" width="10%">ID</th>
                    <th width="50%">Descripción</th>
                    <th width="32%">Departamento</th>
                    <th class="center" width="8%">Estado</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($bienes as $bien) {
            $html .= '
                <tr>
                    <td class="center">' . htmlspecialchars($bien['id_bien']) . '</td>
                    <td>' . htmlspecialchars($bien['descripcion_bien']) . '</td>
                    <td>' . htmlspecialchars($bien['departamento_bien']) . '</td>
                    <td class="center">' . htmlspecialchars($bien['status_bien']) . '</td>
                </tr>';
        }
        $html .= '</tbody></table>';
    }
}

$html .= '</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Reporte_Bienes_" . date('Ymd') . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]);
exit();
?>