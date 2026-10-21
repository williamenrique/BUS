<?php
$user      = auth_user();
$pageTitle = $pageTitle ?? SITGO_APP_NAME;
$menu      = $menu ?? [];

// Nombre y avatar
$nombreCompleto = trim($user['nombre'] ?? '') ?: ($user['nick'] ?? 'Usuario');
$partes         = explode(' ', $nombreCompleto);
$nombreCorto    = $partes[0] ?? 'Usuario';
$apellidoCorto  = $partes[1] ?? '';

$avatarUrl = !empty($user['imagen'])
    ? asset('uploads/storage/' . $user['imagen'])
    : asset('img/default.png');

$iniciales = strtoupper(
    mb_substr($nombreCorto, 0, 1) . mb_substr($apellidoCorto, 0, 1)
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · <?= e(SITGO_APP_NAME) ?></title>
    <meta name="application-name" content="Sistema de Inventario y Gestión Operativa">
    <meta name="theme-color" content="#0d6efd">

    <!-- Autoría y propiedad -->
    <meta name="author" content="William Infante, Ing. de Sistemas">
    <meta name="creator" content="William Infante">
    <meta name="owner" content="William Infante">
    <meta name="copyright" content="© William Infante. Todos los derechos reservados.">
    <meta name="publisher" content="William Infante">

    <!-- Derechos y licencia (ajustar según corresponda) -->
    <meta name="rights" content="Uso interno. Prohibida la reproducción sin autorización.">
    <meta name="license" content="Propietario">

    <!-- Descripción breve y palabras clave -->
    <meta name="description" content="Sistema de inventario de almacén, creación de órdenes de despacho, venta y registro de combustible, relación de personal y carga de registro de aceite de unidades.">
    <meta name="keywords" content="inventario, almacén, órdenes de despacho, ventas, combustible, relación de personal, aceite, unidades, gestión operativa">

    <!-- SEO y control de indexación -->
    <meta name="robots" content="noindex, nofollow">

    <!-- Open Graph (para compartir) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Sistema de Inventario y Gestión Operativa">
    <meta property="og:description" content="Inventario de almacén, órdenes de despacho, ventas, registro de combustible, relación de personal y registro de aceite de unidades.">
    <meta property="og:site_name" content="Sistema de Inventario y Gestión Operativa">
    <meta property="og:locale" content="es_VE">

    <!-- Twitter Cards -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Sistema de Inventario y Gestión Operativa">
    <meta name="twitter:description" content="Inventario de almacén, órdenes de despacho, ventas, combustible, personal y aceite de unidades.">

    <link rel="icon" href="<?= asset('img/logo.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/tailwind.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/styles.css') ?>">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>

</head>
<body>

<div id="app">

    <!-- ============================================ -->
    <!-- OVERLAY (móvil)                               -->
    <!-- ============================================ -->
    <div id="sidebarOverlay"></div>

    <!-- ============================================ -->
    <!-- SIDEBAR                                       -->
    <!-- ============================================ -->
    <aside id="sidebar">

        <!-- Brand: solo el logo + nombre -->
        <div class="sidebar-header">
            <a href="<?= base_url('dashboard') ?>" class="sidebar-brand">
                <!-- <img src="<?= asset('img/logo.png') ?>" alt="SITGO" class="sidebar-logo"> -->
                <span class="sidebar-brand-text">SITGO</span>
            </a>
        </div>

        <!-- Usuario -->
        <div class="sidebar-user">
            <div class="sidebar-user-card">
                <?php if (!empty($user['imagen'])): ?>
                    <img src="<?= e($avatarUrl) ?>" alt="Avatar" class="sidebar-avatar"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="sidebar-avatar-fallback" style="display:none;"><?= e($iniciales) ?></div>
                <?php else: ?>
                    <div class="sidebar-avatar-fallback"><?= e($iniciales) ?></div>
                <?php endif; ?>

                <div class="sidebar-user-info">
                    <p class="sidebar-user-name" title="<?= e($nombreCompleto) ?>"><?= e($nombreCorto) ?></p>
                    <p class="sidebar-user-lastname" title="<?= e($nombreCompleto) ?>"><?= e($apellidoCorto) ?></p>
                    <span class="sidebar-user-role"><?= e($user['rol_nombre'] ?? '') ?></span>
                </div>
            </div>
        </div>

        <!-- Menú -->
        <nav class="sidebar-nav">
            <p class="sidebar-section-title">Menú principal</p>
            <ul class="sidebar-menu">

                <li>
                    <a href="<?= base_url('dashboard') ?>" class="sidebar-link" title="Inicio">
                        <?= icon_tag('home', 'home', 'sidebar-icon') ?>
                        <span class="sidebar-text">Inicio</span>
                    </a>
                </li>

                <?php if (!empty($menu)): ?>
                    <?php foreach ($menu as $item): ?>
                        <?php if (!empty($item['children'])): ?>
                            <li class="sidebar-group">
                                <button type="button" class="sidebar-link sidebar-toggle"
                                        title="<?= e($item['menu_nombre']) ?>">
                                    <?= icon_tag($item['menu_icono'] ?? null, 'folder', 'sidebar-icon') ?>
                                    <span class="sidebar-text sidebar-text-grow"><?= e($item['menu_nombre']) ?></span>
                                    <svg class="sidebar-chevron" fill="none" stroke="currentColor" stroke-width="2"
                                         viewBox="0 0 24 24" width="14" height="14">
                                        <path d="M9 18l6-6-6-6"/>
                                    </svg>
                                </button>
                                <ul class="sidebar-submenu">
                                    <?php foreach ($item['children'] as $child): ?>
                                        <?php if (!empty($child['menu_ruta'])): ?>
                                            <li>
                                                <a href="<?= base_url(ltrim($child['menu_ruta'], '/')) ?>"
                                                   class="sidebar-link sidebar-sublink"
                                                   title="<?= e($child['menu_nombre']) ?>">
                                                    <?= icon_tag($child['menu_icono'] ?? null, 'circle', 'sidebar-icon-sm') ?>
                                                    <span class="sidebar-text"><?= e($child['menu_nombre']) ?></span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php elseif (!empty($item['menu_ruta'])): ?>
                            <li>
                                <a href="<?= base_url(ltrim($item['menu_ruta'], '/')) ?>"
                                   class="sidebar-link" title="<?= e($item['menu_nombre']) ?>">
                                    <?= icon_tag($item['menu_icono'] ?? null, 'circle', 'sidebar-icon') ?>
                                    <span class="sidebar-text"><?= e($item['menu_nombre']) ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>

            </ul>
        </nav>

        <!-- Footer del sidebar -->
        <div class="sidebar-footer">
            <a href="<?= base_url('logout') ?>" class="sidebar-link sidebar-link-danger" title="Cerrar sesión">
                <?= icon_tag('log-out', 'log-out', 'sidebar-icon') ?>
                <span class="sidebar-text">Cerrar sesión</span>
            </a>
        </div>
    </aside>

    <!-- ============================================ -->
    <!-- CONTENIDO                                     -->
    <!-- ============================================ -->
    <div id="content">

        <!-- NAVBAR -->
        <header class="navbar">
            <div class="navbar-left">
                <!-- ÚNICO botón de toggle (sirve para desktop y móvil) -->
                <button id="toggleSidebar" class="navbar-btn" aria-label="Menú">
                    <span class="icon-desktop">
                        <?= icon_tag('panel-left-close', 'panel-left-close', 'w-5 h-5') ?>
                    </span>
                    <span class="icon-mobile">
                        <?= icon_tag('menu', 'menu', 'w-6 h-6') ?>
                    </span>
                </button>
                <h1 class="navbar-title"><?= e($pageTitle) ?></h1>
            </div>

            <div class="navbar-right">
                <div class="navbar-search">
                    <input type="text" id="globalSearch" placeholder="Buscar...">
                    <span class="navbar-search-icon">
                        <?= icon_tag('search', 'search', 'w-4 h-4') ?>
                    </span>
                </div>

                <div class="dropdown-wrap">
                    <button id="btnNotifications" class="navbar-btn" aria-label="Notificaciones">
                        <?= icon_tag('bell', 'bell', 'w-5 h-5') ?>
                        <span class="notif-badge" id="notifBadgeGlobal" style="display:none;">0</span>
                    </button>
                    <div id="dropdownNotifications" class="dropdown-panel">
                        <div class="dropdown-header">Notificaciones</div>

                        <?php if ((int)($user['departamento_id'] ?? 0) === (int)DEPARTAMENTO_SISTEMA): ?>
                        <a id="notifItemRecuperar" href="<?= base_url('recuperar') ?>"
                        class="dropdown-link" style="display:none; position:relative;">
                            <span style="position:relative; display:flex; align-items:center;">
                                <?= icon_tag('key-round', 'key-round', 'w-4 h-4') ?>
                                <span id="notifBadgeRecuperar" class="notif-badge" style="display:none; top:-6px; right:-8px;">0</span>
                            </span>
                            <span>Solicitudes de recuperación</span>
                        </a>
                        <?php endif; ?>

                        <ul class="dropdown-list">
                            <?php if (empty($notificaciones)): ?>
                                <li class="dropdown-empty">Sin notificaciones.</li>
                            <?php else: foreach ($notificaciones as $n): ?>
                                <li class="dropdown-item">
                                    <p><?= e($n['mensaje']) ?></p>
                                    <span><?= e($n['fecha_creacion']) ?></span>
                                </li>
                            <?php endforeach; endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="dropdown-wrap">
                    <button id="btnUser" class="navbar-btn navbar-user">
                        <?php if (!empty($user['imagen'])): ?>
                            <img src="<?= e($avatarUrl) ?>" alt="Avatar" class="navbar-avatar"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="navbar-avatar-fallback" style="display:none;"><?= e($iniciales) ?></div>
                        <?php else: ?>
                            <div class="navbar-avatar-fallback"><?= e($iniciales) ?></div>
                        <?php endif; ?>
                        <span class="navbar-username"><?= e($nombreCorto) ?></span>
                        <?= icon_tag('chevron-down', 'chevron-down', 'w-4 h-4') ?>
                    </button>
                    <div id="dropdownUser" class="dropdown-panel">
                        <div class="dropdown-header">
                            <strong><?= e($nombreCompleto) ?></strong>
                            <small><?= e($user['rol_nombre']) ?></small>
                        </div>
                        <a href="<?= base_url('perfil') ?>" class="dropdown-link">
                            <?= icon_tag('user', 'user', 'w-4 h-4') ?> Mi perfil
                        </a>
                        <a href="<?= base_url('logout') ?>" class="dropdown-link dropdown-link-danger">
                            <?= icon_tag('log-out', 'log-out', 'w-4 h-4') ?> Cerrar sesión
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Contenido principal -->
        <main class="main-content">