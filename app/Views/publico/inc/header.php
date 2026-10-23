<?php
/**
 * Header público · Sin sesión · Sin sidebar
 */
$pageTitle = $pageTitle ?? 'Movimientos Públicos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> · SITGO</title>
    <link rel="icon" href="<?= asset('img/logo.png') ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?= asset('css/tailwind.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/styles.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        
        body {
            background: #f1f5f9;
            padding-top: 0;   /* el navbar es sticky, no fixed, no hace falta */
        }
        .publico-wrap { max-width: 1400px; margin: 0 auto; padding: 1.5rem; }
        .publico-nav {
            position: sticky; top: 0; z-index: 30;
            background: #fff; border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; flex-wrap: wrap;
            box-shadow: 0 1px 3px rgba(15,23,42,.04);
        }
        .publico-brand {
            display: flex; align-items: center; gap: .5rem;
            font-size: 1rem; font-weight: 700; color: #0f172a;
            text-decoration: none;
        }
        .publico-brand img { height: 2rem; }
        .publico-nav-links { display: flex; gap: .35rem; flex-wrap: wrap; }
        .publico-nav-links a {
            padding: .4rem .75rem; border-radius: .4rem;
            font-size: .82rem; font-weight: 500; color: #475569;
            text-decoration: none; transition: background .12s, color .12s;
        }
        .publico-nav-links a:hover { background: #f1f5f9; color: #0f172a; }
        .publico-nav-links a.active { background: #2563eb; color: #fff; }

        /* Secciones */
        .pub-sec {
            background: #fff; border: 1px solid #e2e8f0; border-radius: .75rem;
            margin-bottom: 1.5rem; overflow: hidden;
        }
        .pub-sec-head {
            display: flex; justify-content: space-between; align-items: center;
            gap: 1rem; flex-wrap: wrap;
            padding: .85rem 1.1rem; border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        .pub-sec-head h3 { margin: 0; font-size: .95rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: .4rem; }
        .pub-sec-body { padding: 1rem 1.1rem; }

        /* Contadores */
        .pub-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: .75rem; margin-bottom: 1rem; }
        .pub-stat {
            background: #fff; border: 1px solid #e2e8f0; border-radius: .6rem;
            padding: .85rem .95rem; cursor: pointer; transition: all .15s;
            border-left: 4px solid #cbd5e1;
        }
        .pub-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(15,23,42,.08); }
        .pub-stat.active { box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
        .pub-stat .num { font-size: 1.5rem; font-weight: 800; color: #0f172a; line-height: 1; }
        .pub-stat .lbl { font-size: .68rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-top: .25rem; font-weight: 600; }
        .pub-stat.green { border-left-color: #16a34a; }
        .pub-stat.green .num { color: #166534; }
        .pub-stat.red { border-left-color: #dc2626; }
        .pub-stat.red .num { color: #991b1b; }
        .pub-stat.amber { border-left-color: #f59e0b; }
        .pub-stat.amber .num { color: #b45309; }
        .pub-stat.dark { border-left-color: #334155; }
        .pub-stat.slate { border-left-color: #64748b; }

        /* Tabla auxiliar interna */
        .pub-tabla { width: 100%; border-collapse: collapse; font-size: .82rem; }
        .pub-tabla th { background: #0f172a; color: #fff; text-align: left; padding: .55rem .75rem; font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; }
        .pub-tabla td { padding: .5rem .75rem; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .pub-tabla tbody tr:hover { background: #f8fafc; }

        /* Filtros */
        .pub-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .6rem; margin-bottom: 1rem; align-items: end; }
        .pub-filters .form-group { display: flex; flex-direction: column; gap: .2rem; }
        .pub-filters label { font-size: .7rem; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
        .pub-filters input, .pub-filters select {
            padding: .45rem .6rem; border: 1px solid #cbd5e1; border-radius: .4rem;
            font-family: inherit; font-size: .82rem; background: #fff; outline: none;
        }
        .pub-filters input:focus, .pub-filters select:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,.15); }

        /* Layout 2 columnas */
        .pub-2col { display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; }
        @media (max-width: 992px) { .pub-2col { grid-template-columns: 1fr; } }

        /* Panel detalle */
        .pub-detail { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .6rem; padding: 1rem; min-height: 200px; }

        /* Modal */
        .pub-modal-backdrop {
            display: none; position: fixed; inset: 0;
            background: rgba(15,23,42,.6); backdrop-filter: blur(3px);
            z-index: 1000; align-items: center; justify-content: center;
            padding: 1rem; overflow-y: auto;
        }
        .pub-modal-backdrop.open { display: flex; }
        .pub-modal {
            background: #fff; border-radius: 14px; width: 100%; max-width: 900px;
            max-height: 92vh; overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,.35);
        }
        .pub-modal-head {
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 1.4rem; border-bottom: 1px solid #e2e8f0;
            position: sticky; top: 0; background: #fff; z-index: 5;
        }
        .pub-modal-head h3 { margin: 0; font-size: 1rem; font-weight: 700; color: #0f172a; }
        .pub-modal-body { padding: 1.25rem; }
        .pub-modal-close {
            background: transparent; border: none; font-size: 1.7rem; line-height: 1;
            color: #94a3b8; cursor: pointer; padding: 0 .3rem; border-radius: .35rem;
        }
        .pub-modal-close:hover { background: #fee2e2; color: #dc2626; }
        #resumenBody .resumen-row:hover { background: #f8fafc; }
        #resumenBody .resumen-row.selected { background: #eff6ff; }
        #resumenBody .resumen-row.selected td { border-color: #bfdbfe; }

        /* Movimientos: link actions y scroll del panel */
        #movBody a[data-mov-action] {
            display: inline-flex;
            align-items: center;
            gap: .2rem;
        }
        #movBody a[data-mov-action]:hover {
            text-decoration: underline !important;
        }
        #movDetalle {
            scrollbar-width: thin;
        }
        #movDetalle::-webkit-scrollbar { width: 6px; }
        #movDetalle::-webkit-scrollbar-thumb { background: rgba(100,116,139,.35); border-radius: 4px; }
        /* ============================================
        FIX · Anclas con offset del navbar sticky
        ============================================ */
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;   /* compensa la altura del navbar fijo */
        }

        /* Marca la sección activa en el navbar */
        .publico-nav-links a.active {
            background: #2563eb !important;
            color: #fff !important;
        }
        /* Botón Filtrar con cambios pendientes */
        #btnMovFiltrar.has-changes {
            box-shadow: 0 0 0 3px rgba(245,158,11,.25);
            animation: pulseFil 1.4s ease-in-out infinite;
        }
        @keyframes pulseFil {
            0%, 100% { box-shadow: 0 0 0 3px rgba(245,158,11,.25); }
            50%      { box-shadow: 0 0 0 6px rgba(245,158,11,.15); }
        }
                /* ============================================
           BIENES PÚBLICO · Hero centrado
           ============================================ */
        .bn-hero {
            text-align: center;
            padding: 2rem 1rem 1.25rem;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border: 1px solid #e2e8f0;
            border-radius: .75rem;
            margin-bottom: 1.25rem;
        }
        .bn-hero-inst {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -.01em;
            line-height: 1.3;
            max-width: 820px;
            margin: 0 auto .85rem;
            text-transform: uppercase;
        }
        .bn-hero-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background: #eff6ff;
            color: #1e40af;
            margin-bottom: .5rem;
        }
        .bn-hero-icon svg { width: 1.5rem; height: 1.5rem; }
        .bn-hero-label {
            font-size: .7rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin: 0 0 .25rem;
        }
        .bn-hero-depto {
            font-size: 1.35rem;
            font-weight: 800;
            color: #1e40af;
            line-height: 1.2;
            margin: 0;
            text-transform: uppercase;
        }
        .bn-hero-meta {
            font-size: .78rem;
            color: #94a3b8;
            margin: .65rem 0 0;
        }
    </style>
</head>
<body>
    <nav class="publico-nav">
        <a href="#" class="publico-brand">
            <img src="<?= asset('img/logo.png') ?>" alt="SITGO">
            <span>SITGO · <?= htmlspecialchars($pageTitle ?? 'Público') ?></span>
        </a>
        <?php if (!isset($mostrarNav) || $mostrarNav !== false): ?>
        <div class="publico-nav-links">
            <a href="#sec-flota" class="active">Flota</a>
            <a href="#sec-aceite">Aceite</a>
            <a href="#sec-resumen">Resumen Flota</a>
            <a href="#sec-movimientos">Movimientos</a>
            <a href="#sec-estacion">Estación</a>
        </div>
        <?php endif; ?>
        <a href="<?= base_url('login') ?>" class="btn btn-ghost btn-sm">
            <i data-lucide="log-in" class="w-4 h-4"></i>
            <span>Ir al sistema</span>
        </a>
    </nav>