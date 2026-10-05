<?php
// data/encabezado.php
// ============================================================
// ENCABEZADO ESTÁNDAR UNIFICADO PARA TODOS LOS REPORTES PDF
// ============================================================
// Estructura (usando TABLAS para compatibilidad total con Dompdf):
//  - Logo a la izquierda (ancho fijo 80px)
//  - Centro: Nombre de institución (centrado horizontal y vertical)
//  - Debajo: Fecha de emisión
//  - Línea divisoria azul (#0056b3)
//  - Título del reporte (opcional, centrado)
//  - El contenido del cuerpo inicia debajo de todo el bloque
// ============================================================

// Validar si la configuración ya está cargada
if (!defined('BASE_URL')) {
    if (file_exists('../../system/core/Config/config.system.php')) {
        require_once '../../system/core/Config/config.system.php';
    }
}

// Iniciar sesión si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------------
// 1. RESOLVER NOMBRE DE INSTITUCIÓN
// ------------------------------------------------------------------
// El encabezado NO resuelve la institución desde BD.
// Espera que el script que lo incluye ya haya seteado $nombreInstitucion.
if (!isset($nombreInstitucion) || empty($nombreInstitucion)) {
    $nombreInstitucion = 'SERVICIO SOCIALISTA DE LOGISTICA, MANTENIMIENTO Y TRANSPORTE DEL ESTADO YARACUY';
}

// ------------------------------------------------------------------
// 2. TÍTULO DEL REPORTE (opcional)
// ------------------------------------------------------------------
if (!isset($tituloReporte)) {
    $tituloReporte = '';
}

// ------------------------------------------------------------------
// 3. PROCESAR LOGO A BASE64
// ------------------------------------------------------------------
$pathToLogo = defined('IMG') ? IMG . 'logo.png' : '';
$logoHtml = '';

if (!empty($pathToLogo) && file_exists($pathToLogo)) {
    $logoType = pathinfo($pathToLogo, PATHINFO_EXTENSION);
    $logoData = file_get_contents($pathToLogo);
    $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
    $logoHtml = '<img src="' . $logoBase64 . '" class="logo" alt="Logo">';
} else {
    $logoUrl = defined('BASE_URL') ? BASE_URL . 'src/img/logo.png' : '';
    if ($logoUrl) {
        $logoHtml = '<img src="' . $logoUrl . '" class="logo" alt="Logo">';
    }
}

// ------------------------------------------------------------------
// 4. CSS COMÚN - ESTRUCTURA UNIFICADA (basada en TABLAS)
// ------------------------------------------------------------------
$cssCommon = '
    <style>
        /* ============ CONFIGURACIÓN DE PÁGINA ============ */
        /* 50mm de margen superior: 4mm de aire + 46mm de header */
        @page {
            margin: 50mm 15mm 18mm 15mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* ============ HEADER FIJO (aparece en cada página) ============ */
        .header {
            position: fixed;
            top: -46mm;              /* sube el header al área del margen */
            left: 0;
            right: 0;
            height: 46mm;            /* 4mm aire arriba + 42mm de contenido real */
            width: 100%;
        }

        /* Tabla principal: Logo | Centro | Spacer */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            table-layout: fixed;
            margin: 0;
            padding: 0;
        }
        .header-table td {
            border: none;
            padding: 0;
            margin: 0;
            vertical-align: middle;
            line-height: 1;
        }

        /* Columna izquierda: logo */
        .header-logo-cell {
            width: 80px;
            text-align: left;
            vertical-align: middle;
        }
        .header-logo-cell .logo {
            width: 65px;
            height: auto;
            max-height: 26mm;
            display: block;
        }

        /* Columna derecha: spacer del mismo ancho que la izquierda
           para que la columna central quede centrada en TODA la página */
        .header-spacer-cell {
            width: 80px;
        }

        /* Columna central: institución + fecha, centrada H y V */
        .header-center-cell {
            text-align: center;
            vertical-align: middle;
            padding: 0 6px;
        }

        /* Nombre de la institución */
        .header .institucion {
            margin: 0;
            padding: 0;
            font-size: 14px;
            color: #0056b3;
            text-transform: uppercase;
            font-weight: bold;
            line-height: 1.25;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Fecha de emisión */
        .header .fecha {
            margin: 4px 0 0 0;
            padding: 0;
            font-size: 8.5px;
            color: #555;
            font-style: italic;
            line-height: 1;
        }

        /* Línea divisoria azul */
        .header-divider {
            width: 100%;
            height: 2px;
            background-color: #0056b3;
            margin: 4px 0 0 0;
            padding: 0;
            line-height: 0;
            font-size: 0;
            display: block;
        }

        /* Título del reporte (debajo de la línea azul) */
        .header .titulo-reporte {
            margin: 4px 0 0 0;
            padding: 0;
            font-size: 18px;
            color: #0056b3;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: center;
            line-height: 1.2;
        }

        /* ============ FOOTER FIJO ============ */
        .footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            height: 10mm;
            text-align: center;
            font-size: 8.5px;
            color: #777;
            border-top: 1px solid #ccc;
            padding-top: 3px;
        }

        .page-number:before { content: "Página " counter(page); }

        /* ============ UTILIDADES ============ */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
    </style>
';

// ------------------------------------------------------------------
// 5. HTML DEL HEADER
// ------------------------------------------------------------------
$tituloHtml = '';
if (!empty($tituloReporte)) {
    $tituloHtml = '<div class="titulo-reporte">' . htmlspecialchars($tituloReporte) . '</div>';
}

$headerHtml = '
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-logo-cell">' . $logoHtml . '</td>
                <td class="header-center-cell">
                    <div class="institucion">' . htmlspecialchars($nombreInstitucion) . '</div>
                    <div class="fecha">Fecha de Emisión: ' . date('d/m/Y') . '</div>
                </td>
                <td class="header-spacer-cell"></td>
            </tr>
        </table>
        <div class="header-divider"></div>
        ' . $tituloHtml . '
    </div>
';

// ------------------------------------------------------------------
// 6. HTML DEL FOOTER
// ------------------------------------------------------------------
$footerHtml = '
    <div class="footer">
        <span class="page-number"></span>
    </div>
';
?>