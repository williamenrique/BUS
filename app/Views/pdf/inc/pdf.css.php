<style>
    /* ============================================
       SITGO · CSS común para todos los PDFs
       ============================================ */

    @page { margin: <?= $meta['page_margin_top'] ?? '25mm' ?> 15mm <?= $meta['page_margin_bottom'] ?? '20mm' ?> 15mm; }

    * { box-sizing: border-box; }

    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 10px;
        color: #1e293b;
        line-height: 1.4;
        margin: 0;
        padding: 0;
    }

    /* ============================================
       HEADER (tabla de 3 columnas)
       ============================================ */
    .pdf-header {
        position: fixed;
        top: -20mm;
        left: 0;
        right: 0;
        height: 24mm;
        border-bottom: 2px solid <?= $meta['doc_color'] ?? '#1e40af' ?>;
        padding-bottom: 4px;
        border-collapse: collapse;
    }

    .pdf-header .header-col-logo {
        width: 70px;
        vertical-align: middle;
        padding: 0 6px 0 0;
    }

    .pdf-header .header-logo {
        width: 58px;
        height: auto;
        display: block;
    }

    .pdf-header .header-col-info {
        vertical-align: middle;
        text-align: center;
        padding: 0 6px;
    }

    .pdf-header .header-nombre {
        font-size: 10px;
        font-weight: 900;
        color: <?= $meta['doc_color'] ?? '#1e40af' ?>;
        text-transform: uppercase;
        line-height: 1.2;
        margin: 0 0 2px 0;
        letter-spacing: .02em;
        text-align: center;
    }

    .pdf-header .header-meta {
        font-size: 7.5px;
        color: #475569;
        line-height: 1.25;
        margin: 0;
        text-align: center;
    }

    .pdf-header .header-extra {
        font-size: 7.5px;
        color: #475569;
        margin-top: 1px;
        text-align: center;
    }

    .pdf-header .header-col-doc {
        width: 150px;
        vertical-align: middle;
        text-align: right;
        padding: 0 0 0 6px;
    }

    .pdf-header .header-doc-tipo {
        font-size: 8px;
        font-weight: 900;
        color: <?= $meta['doc_color'] ?? '#1e40af' ?>;
        text-transform: uppercase;
        letter-spacing: .08em;
        margin: 0 0 1px 0;
        line-height: 1.15;
    }

    .pdf-header .header-doc-numero {
        font-size: 13px;
        font-weight: 900;
        color: #0f172a;
        margin: 0;
        line-height: 1.1;
    }

    /* ============================================
       FOOTER
       ============================================ */
    .pdf-footer {
        position: fixed;
        bottom: -15mm;
        left: 0;
        right: 0;
        height: 10mm;
        padding-top: 3px;
        border-top: 1px solid #e2e8f0;
        font-size: 7.5px;
        color: #94a3b8;
        text-align: center;
    }

    .pdf-footer .footer-pagina {
        font-weight: 600;
        color: #64748b;
    }

    .pdf-footer .footer-pagina:before {
        content: "Página " counter(page) " de " counter(pages);
    }

    .pdf-footer .footer-marca {
        font-size: 7px;
        color: #cbd5e1;
        margin-top: 1px;
    }

    /* ============================================
       CONTENIDO
       ============================================ */
    .pdf-content {
        position: relative;
        margin-top: 5mm;
    }

    .pdf-titulo {
        text-align: center;
        font-size: 14px;
        font-weight: 900;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: .05em;
        margin: 0 0 4px 0;
    }

    .pdf-subtitulo {
        text-align: center;
        font-size: 10px;
        color: #64748b;
        margin: 0 0 12px 0;
    }

    /* ============================================
       SECCIONES
       ============================================ */
    .section-box {
        margin: 6px 0 8px 0;
        padding: 6px 8px;
        background: #f8fafc;
        border-radius: 2px;
    }

    .section-title {
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: <?= $meta['doc_color'] ?? '#1e40af' ?>;
        border-bottom: 1.5px solid #dbeafe;
        padding: 4px 8px;
        margin: 0 0 6px 0;
        background: #f0f4ff;
        border-radius: 2px 2px 0 0;
    }

    .section-content {
        padding: 4px 0;
    }

    .label-min {
        font-size: 7.5px;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: .04em;
    }

    .val-text {
        font-size: 10px;
        font-weight: 700;
        color: #0f172a;
    }

    /* ============================================
       TABLAS
       ============================================ */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4px;
    }

    .items-table th {
        background: #0f172a;
        color: #ffffff;
        padding: 5px 6px;
        text-align: left;
        font-size: 7.5px;
        text-transform: uppercase;
        letter-spacing: .03em;
        font-weight: 800;
    }

    .items-table td {
        padding: 5px 6px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 9px;
        color: #334155;
        vertical-align: middle;
    }

    .items-table tr:nth-child(even) td {
        background: #f8fafc;
    }

    .items-table tr:last-child td {
        border-bottom: none;
    }

    /* ============================================
       UTILIDADES
       ============================================ */
    .text-right  { text-align: right; }
    .text-center { text-align: center; }
    .text-left   { text-align: left; }
    .text-bold   { font-weight: 700; }
    .text-upper  { text-transform: uppercase; }
    .text-muted  { color: #94a3b8; }

    .badge {
        display: inline-block;
        padding: 1px 6px;
        border-radius: 999px;
        font-size: 7.5px;
        font-weight: 800;
        text-transform: uppercase;
        color: #fff;
    }

    .badge-info    { background: #3b82f6; }
    .badge-success { background: #10b981; }
    .badge-warning { background: #f59e0b; }
    .badge-danger  { background: #e11d48; }
    .badge-neutral { background: #64748b; }

    /* Firmas */
    .firmas-table {
        width: 100%;
        margin-top: 30px;
    }

    .firma-box {
        text-align: center;
        padding: 0 20px;
    }

    .firma-linea {
        border-top: 1px solid #0f172a;
        padding-top: 4px;
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        margin-top: 30px;
    }

    /* Evitar cortes raros */
    .no-break { page-break-inside: avoid; }
    .page-break { page-break-after: always; }
</style>