<?php
// data/encabezado.php

// Validar si la configuración ya está cargada, si no, intentamos cargarla
if (!defined('BASE_URL')) {
    if (file_exists('../../system/core/Config/config.system.php')) {
        require_once '../../system/core/Config/config.system.php';
    }
}

// Procesar Logo a Base64 (Más rápido y compatible con Dompdf)
$pathToLogo = defined('IMG') ? IMG . 'logo.png' : '';
$logoHtml = '';

if (!empty($pathToLogo) && file_exists($pathToLogo)) {
    $logoType = pathinfo($pathToLogo, PATHINFO_EXTENSION);
    $logoData = file_get_contents($pathToLogo);
    $logoBase64 = 'data:image/' . $logoType . ';base64,' . base64_encode($logoData);
    $logoHtml = '<img src="' . $logoBase64 . '" class="logo">';
} else {
    // Fallback a URL si no se encuentra localmente
    $logoUrl = defined('BASE_URL') ? BASE_URL . 'src/img/logo.png' : '';
    if ($logoUrl) {
        $logoHtml = '<img src="' . $logoUrl . '" class="logo">';
    }
}

// Estilos CSS Comunes (Estandarizados)
$cssCommon = '
    <style>
        @page { margin: 25mm 15mm 15mm 15mm; } /* Margenes: Arriba Derecha Abajo Izquierda */
        body { font-family: Arial, sans-serif; font-size: 10px; color: #333; }
        
        .header { 
            position: fixed; 
            top: -20mm; 
            left: 0; 
            right: 0; 
            height: 25mm; 
            text-align: center; 
            border-bottom: 2px solid #0056b3; /* Color Azul Institucional */
            padding-bottom: 5px;
        }
        
        .header .logo { 
            position: absolute; 
            top: 0; 
            left: 10px; 
            width: 60px; 
            height: auto;
        }
        
        .header h1 { 
            margin: 0; 
            font-size: 16px; 
            color: #0056b3; 
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .header h2 { 
            margin: 2px 0; 
            font-size: 12px; 
            color: #555;
            font-weight: normal;
        }
        
        .header .date { 
            margin-top: 5px; 
            font-size: 9px; 
            color: #777; 
        }

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
            padding-top: 5px;
        }
        
        .page-number:before { content: "Página " counter(page); }
        
        /* Clases de utilidad comunes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
';

// HTML del Encabezado
$headerHtml = '
    <div class="header">
        ' . $logoHtml . '
        <h1>SERVICIO SOCIALISTA DE LOGISTICA,</h1>
        <h2>MANTENIMIENTO Y TRANSPORTE DEL ESTADO YARACUY</h2>
        <div class="date">Fecha de Emisión: ' . date('d/m/Y') . '</div>
    </div>
';

// HTML del Pie de Página
$footerHtml = '
    <div class="footer">
        <span class="page-number"></span>
    </div>
';
?>