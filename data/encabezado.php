<?php
// data/encabezado.php

// Validar si la configuración ya está cargada
if (!defined('BASE_URL')) {
    if (file_exists('../../system/core/Config/config.system.php')) {
        require_once '../../system/core/Config/config.system.php';
    }
}

// Nombre de la institución (viene del script que incluye este archivo)
if (!isset($nombreInstitucion) || empty($nombreInstitucion)) {
    $nombreInstitucion = 'SERVICIO SOCIALISTA DE LOGISTICA, MANTENIMIENTO Y TRANSPORTE DEL ESTADO YARACUY';
}

// Título del reporte (opcional, lo define cada script)
if (!isset($tituloReporte)) {
    $tituloReporte = '';
}

// Procesar Logo a Base64
$pathToLogo = defined('IMG') ? IMG . 'logo.png' : '';
$logoHtml = '';

if (!empty($pathToLogo) && file_exists($pathToLogo)) {
    $logoType = pathinfo($pathToLogo, PATHINFO_EXTENSION);
    $logoData = file_get_contents($pathToLogo);
    $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
    $logoHtml = '<img src="' . $logoBase64 . '" class="logo">';
} else {
    $logoUrl = defined('BASE_URL') ? BASE_URL . 'src/img/logo.png' : '';
    if ($logoUrl) {
        $logoHtml = '<img src="' . $logoUrl . '" class="logo">';
    }
}

// CSS común
$cssCommon = '
    <style>
        @page { margin: 45mm 15mm 15mm 15mm; }

        body { 
            font-family: Arial, sans-serif; 
            font-size: 10px; 
            color: #333; 
            margin: 0;
            padding: 0;
        }

        /* ============ HEADER FIJO ============ */
        .header { 
            position: fixed; 
            top: -38mm; 
            left: 0; 
            right: 0; 
            height: 38mm; 
            border-bottom: 2px solid #0056b3;
        }

        /* Logo a la izquierda, verticalmente centrado */
        .header .logo { 
            position: absolute; 
            top: 50%; 
            left: 10px; 
            width: 60px; 
            height: auto;
            transform: translateY(-50%);
        }

        /* Bloque de texto centrado (ocupa todo el ancho menos los márgenes del logo) */
        .header .header-text {
            position: absolute;
            top: 5mm;
            left: 80px;
            right: 80px;
            text-align: center;
        }

        /* Nombre de institución */
        .header .header-text .institucion {
            margin: 0;
            font-size: 12px;
            color: #0056b3;
            text-transform: uppercase;
            font-weight: bold;
            line-height: 1.3;
        }

        /* Fecha */
        .header .header-text .fecha {
            margin-top: 3px;
            font-size: 9px;
            color: #777;
        }

        /* Título del reporte */
        .header .header-text .titulo-reporte {
            margin-top: 6px;
            font-size: 11px;
            color: #0056b3;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ============ FOOTER FIJO ============ */
        .footer { 
            position: fixed; 
            bottom: -10mm; 
            left: 0; 
            right: 0; 
            height: 10mm; 
            text-align: center; 
            font-size: 9px; 
            color: #777; 
            border-top: 1px solid #ccc;
            padding-top: 3px;
        }

        .page-number:before { content: "Página " counter(page); }

        /* Utilidades */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
    </style>
';

// HTML del título del reporte (si existe)
$tituloHtml = '';
if (!empty($tituloReporte)) {
    $tituloHtml = '<div class="titulo-reporte">' . $tituloReporte . '</div>';
}

// HTML del header
$headerHtml = '
    <div class="header">
        ' . $logoHtml . '
        <div class="header-text">
            <div class="institucion">' . $nombreInstitucion . '</div>
            <div class="fecha">Fecha de Emisión: ' . date('d/m/Y') . '</div>
            ' . $tituloHtml . '
        </div>
    </div>
';

// HTML del footer
$footerHtml = '
    <div class="footer">
        <span class="page-number"></span>
    </div>
';
?>