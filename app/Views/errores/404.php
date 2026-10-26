<?php
/**
 * Vista 404 · Página no encontrada
 * Se renderiza con el layout completo (header + footer) cuando el router
 * no encuentra una ruta, o standalone cuando el usuario no está logueado.
 *
 * Variables disponibles (opcionales):
 *   $withLayout (bool)  — si false, no incluye header/footer
 *   $pageTitle  (string)
 */
$withLayout = $withLayout ?? false;
$pageTitle  = $pageTitle  ?? 'Página no encontrada';

// IMPORTANTE: definir $baseUrl ANTES del heredoc
$baseUrl = BASE_URL;

$html = <<<HTML
<div style="
    min-height:calc(100vh - 4rem - 4rem);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:2rem 1rem;
">
    <div style="
        background:#fff;
        border:1px solid #e2e8f0;
        border-radius:16px;
        padding:2.5rem 2rem;
        max-width:460px;
        width:100%;
        text-align:center;
        box-shadow:0 4px 20px rgba(15,23,42,.06);
    ">
        <div style="
            width:80px;
            height:80px;
            border-radius:50%;
            background:#f1f5f9;
            color:#64748b;
            display:flex;
            align-items:center;
            justify-content:center;
            margin:0 auto 1.25rem;
        ">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                 style="width:36px;height:36px;stroke-width:1.8;">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                <line x1="8" y1="11" x2="14" y2="11"></line>
            </svg>
        </div>

        <p style="
            margin:0 0 .35rem;
            font-size:.75rem;
            font-weight:600;
            letter-spacing:.08em;
            text-transform:uppercase;
            color:#94a3b8;
        ">Error 404</p>

        <h1 style="
            margin:0 0 .5rem;
            font-size:1.25rem;
            font-weight:700;
            color:#0f172a;
            letter-spacing:-.01em;
        ">Página no encontrada</h1>

        <p style="
            margin:0 0 1.75rem;
            font-size:.9rem;
            color:#64748b;
            line-height:1.55;
        ">La página que buscas no existe, fue movida o nunca estuvo disponible. Verifica la URL o vuelve al inicio.</p>

        <div style="
            display:flex;
            gap:.5rem;
            justify-content:center;
            flex-wrap:wrap;
        ">
            <button type="button" onclick="history.back()" style="
                display:inline-flex;
                align-items:center;
                gap:.4rem;
                padding:.6rem 1.1rem;
                border-radius:.5rem;
                font-size:.875rem;
                font-weight:500;
                text-decoration:none;
                cursor:pointer;
                font-family:inherit;
                background:#f1f5f9;
                color:#475569;
                border:1px solid #e2e8f0;
                transition:background-color .15s,border-color .15s;
            " onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                     style="width:16px;height:16px;stroke-width:2;">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Volver
            </button>

            <a href="{$baseUrl}dashboard" style="
                display:inline-flex;
                align-items:center;
                gap:.4rem;
                padding:.6rem 1.1rem;
                border-radius:.5rem;
                font-size:.875rem;
                font-weight:500;
                text-decoration:none;
                cursor:pointer;
                font-family:inherit;
                background:#2563eb;
                color:#fff;
                border:1px solid transparent;
                transition:background-color .15s;
            " onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                     style="width:16px;height:16px;stroke-width:2;">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Ir al inicio
            </a>
        </div>
    </div>
</div>
HTML;

if ($withLayout) {
    // Se renderiza dentro del layout completo (header + footer)
    require VIEWS_PATH . 'inc/header.php';
    echo $html;
    require VIEWS_PATH . 'inc/footer.php';
} else {
    // Se renderiza standalone (sin header/footer)
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Página no encontrada · SITGO</title>
        <link rel="icon" href="<?= asset('img/logo.png') ?>">
        <link rel="stylesheet" href="<?= asset('css/tailwind.min.css') ?>">
    </head>
    <body style="margin:0;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;background:#f8fafc;color:#0f172a;-webkit-font-smoothing:antialiased;">
        <?= $html ?>
    </body>
    </html>
    <?php
}