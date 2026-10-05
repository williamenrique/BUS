<?php
require_once  '../dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['reporteData'])) {
    die('Acceso no autorizado.');
}

// ------------------------------------------------------------------
// DECODIFICAR Y NORMALIZAR LOS DATOS JSON RECIBIDOS
// Acepta varios formatos:
//   Caso 1: { "ordenes": [ {...}, {...} ] }
//   Caso 2: [ {...}, {...} ]                  (array de órdenes)
//   Caso 3: { "articulos": [...], ... }       (una sola orden)
//   Caso 4: objeto mixto con id_institucion + órdenes
// ------------------------------------------------------------------
$decoded = json_decode($_POST['reporteData'], true);

if (!is_array($decoded) || empty($decoded)) {
    die('Error: No se recibieron datos válidos para generar el reporte.');
}

$ordenes = [];

if (isset($decoded['ordenes']) && is_array($decoded['ordenes'])) {
    // Caso 1
    $ordenes = $decoded['ordenes'];
} elseif (isset($decoded['articulos']) && is_array($decoded['articulos'])) {
    // Caso 3
    $ordenes = [$decoded];
} else {
    // Caso 2 / 4: filtramos solo los items que parezcan órdenes
    $filtered = [];
    foreach ($decoded as $val) {
        if (is_array($val) && isset($val['articulos'])) {
            $filtered[] = $val;
        }
    }
    $ordenes = !empty($filtered) ? $filtered : $decoded;
}

if (empty($ordenes) || !is_array($ordenes)) {
    die('Error: No se recibieron órdenes válidas para generar el reporte.');
}

// Configurar opciones de Dompdf
$options = new Options();
$options->set('defaultFont', 'Helvetica');
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// ------------------------------------------------------------------
// RESOLVER INSTITUCIÓN DESDE DATOS DEL REPORTE (POST)
// ------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($nombreInstitucion) || empty($nombreInstitucion)) {
    $idInstitucion = null;

    if (is_array($decoded)) {
        if (isset($decoded['id_institucion'])) {
            $idInstitucion = $decoded['id_institucion'];
        } elseif (isset($decoded[0]['id_institucion'])) {
            $idInstitucion = $decoded[0]['id_institucion'];
        } elseif (isset($decoded['ordenes'][0]['id_institucion'])) {
            $idInstitucion = $decoded['ordenes'][0]['id_institucion'];
        } elseif (isset($decoded['orden']['id_institucion'])) {
            $idInstitucion = $decoded['orden']['id_institucion'];
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
                error_log("reporte: Institución encontrada id=$idInstitucion nombre=" . $nombreInstitucion);
            } else {
                error_log("reporte: Institución NO encontrada id=$idInstitucion");
            }
        } catch (Exception $e) {
            error_log("reporte ERROR: " . $e->getMessage());
        }
    }

    if (!isset($nombreInstitucion) || empty($nombreInstitucion)) {
        $nombreInstitucion = 'INSTITUCIÓN NO ENCONTRADA (id=' . ($idInstitucion ?? 'null') . ')';
    }
}

// Título del reporte
$tituloReporte = 'REPORTE DE ÓRDENES';

// Importar encabezado estandarizado
require_once '../encabezado.php';

// ============================================================
// ESTILOS ESPECÍFICOS DE ESTE REPORTE (2 órdenes por página)
// ============================================================
$css = $cssCommon . '
    <style>
        /* Márgenes reducidos porque el header aquí es estático dentro de cada orden */
        @page { margin: 10mm 10mm 12mm 10mm; }

        /* ============================================================
           OVERRIDE DEL HEADER PARA ESTE REPORTE
           El header se repite dentro de cada .orden-container (no fijo)
           ============================================================ */
        .header {
            position: static !important;
            top: auto !important;
            left: auto !important;
            right: auto !important;
            height: auto !important;
            width: 100% !important;
            margin: 0 0 6px 0 !important;
            padding: 0 !important;
            page-break-inside: avoid;
        }

        /* Ajustes finos: logo más pequeño para que quepan 2 órdenes */
        .header-table { width: 100%; }
        .header-logo-cell { width: 60px !important; }
        .header-logo-cell .logo { width: 50px !important; max-height: 18mm !important; }
        .header-spacer-cell { width: 60px !important; }
        .header .institucion { font-size: 11px !important; line-height: 1.2 !important; }
        .header .fecha { font-size: 7.5px !important; margin-top: 2px !important; }
        .header-divider { margin-top: 3px !important; }

        /* ============================================================
           CONTENEDOR DE CADA ORDEN
           ============================================================ */
        .orden-container {
            box-sizing: border-box;
            height: 47%;
            margin-bottom: 0;
            padding: 4px 0 6px 0;
            border-bottom: 2px dashed #ccc;
            page-break-inside: avoid;
            position: relative;
        }
        .orden-container:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        /* ============================================================
           TABLA DE INFORMACIÓN DE LA ORDEN
           ============================================================ */
        .info-orden-table { width: 100%; margin: 0 0 6px 0; }
        .info-orden-table td { border: none; padding: 2px 0; vertical-align: bottom; }
        .info-orden-table .numero-orden { text-align: right; font-weight: bold; font-size: 12px; }

        .section { margin-bottom: 8px; }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            background-color: #e8eaf6;
            padding: 4px;
            border-radius: 4px;
            margin-bottom: 5px;
            color: #1a237e;
        }

        .table { width: 100%; border-collapse: collapse; font-size: 9px; }
        .table th, .table td { border: 1px solid #c5cae9; padding: 4px; text-align: left; }
        .table th { background-color: #f1f3f9; font-weight: bold; }
        .table .center { text-align: center; }

        .footer-section { margin-top: 6px; width: 100%; }
        .firma-box { width: 30%; text-align: center; font-size: 9px; }
        .observacion-box { width: 38%; font-size: 9px; text-align: center; padding: 0 5px; }

        .firma-line {
            border-top: 1px solid #333;
            margin-top: 35px;
            padding-top: 2px;
            font-weight: bold;
            font-size: 9px;
        }
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
    // Defensa: si por alguna razón un item no es array, lo saltamos
    if (!is_array($dataInfo)) {
        continue;
    }
    $dataArt = $dataInfo['articulos'] ?? [];

    $html .= '
    <div class="orden-container">
        ' . $headerHtml . '
        <table class="info-orden-table">
            <tr>
                <td><strong>Fecha:</strong> ' . date("d/m/Y", strtotime($dataInfo['fecha_despacho'])) . '</td>
                <td class="numero-orden">ORDEN DE DESPACHO N°: ' . str_pad($dataInfo['numero_orden'], 6, "0", STR_PAD_LEFT) . '</td>
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
        $count = 0;
        foreach ($dataArt as $row) {
            if ($count < 8) {
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

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = "Ordenes_Lote_" . date('Ymd_His') . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]);
exit();
?>