<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Codificación y vista -->
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Identidad del sistema -->
    <title><?= $data['page_tag']?></title>
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

    <!-- Favicon (ajusta rutas reales) -->
    <link rel="icon" href="<?= IMG ?>logo.png" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?= IMG ?>logo.png">

    <script>
        const base_url = "<?= base_url()?>";
    </script>
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= PLUGINS ?>fontawesome-free/css/all.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="<?= PLUGINS ?>overlayScrollbars/css/OverlayScrollbars.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?= CSS ?>adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
		<!-- Select2 CSS -->
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />	
</head>
<body class="hold-transition sidebar-mini layout-fixed control-sidebar-slide-open layout-navbar-fixed">
    <!-- Site wrapper -->
    <div class="wrapper">
          <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block" id="currentDateContainer">
                    <a href="#" class="nav-link" id="currentDate"></a>
                </li>
            </ul>
            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
                <!-- Navbar Search -->
                <?php 
                // CORRECCIÓN: Mostrar notificaciones si el usuario es Administrador (rol_id = 1), sin importar su departamento.
                // Se usa el ID del rol porque es más fiable que el nombre.
                if (isset($_SESSION['userData']['usuario_rol_id']) && $_SESSION['userData']['usuario_rol_id'] == 1): 
                ?>
                <!-- Notifications Dropdown Menu -->
                <li class="nav-item dropdown" id="notification-bell-container">
                    <a id="notification-bell-button" class="nav-link" data-toggle="dropdown" href="#">
                        <i class="far fa-bell"></i>
                        <span id="notification-count" class="badge badge-warning navbar-badge" style="display: none;"></span>
                    </a>
                    <div id="notification-panel" class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item dropdown-header">Notificaciones</span>
                        <div class="dropdown-divider"></div>
                        <div id="notification-list">
                            <!-- Las notificaciones se cargarán aquí por JS -->
                            <p class="text-center text-muted p-3">Cargando...</p>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="<?= base_url() ?>user/recuperar" class="dropdown-item dropdown-footer">Ver todas las solicitudes</a>
                    </div>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <!-- /.navbar -->

        
        <!-- Main Sidebar Container -->
        <aside class="main-sidebar main-sidebar-custom sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="<?= BASE_URL()?>" class="brand-link">
            <!-- <img src="<?= IMG ?>logo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8"> -->
            <span class="brand-text text-center font-weight-light">SITGO</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar user (optional) -->
                <div class="user-panel mt-3 pb-3 mb-3 text-center">
                    <div class="image">
                        <img src="<?= BASE_URL().'/'.$_SESSION['userData']['usuario_imagen']?>" class="img-circle elevation-2" alt="User Image" style="width: 4.3rem; height: 4.3rem;">
                    </div>
                    <div class="info d-block">
                        <a href="#" class="d-block"><?= $_SESSION['userData']['personal_nombre'].' '.$_SESSION['userData']['personal_apellido']?></a>
                    </div>
                </div>

                <!-- SidebarSearch Form -->
                <div class="form-inline">
                    <div class="input-group" data-widget="sidebar-search">
                    <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
                    <div class="input-group-append">
                        <button class="btn btn-sidebar">
                        <i class="fas fa-search fa-fw"></i>
                        </button>
                    </div>
                    </div>
                </div>

                <!-- Sidebar Menu -->
                <?php
                    if (isset($_SESSION['userData']['usuario_nick'])) {
                        cargar_menu_dinamico($_SESSION['userData']['usuario_nick'], $data);
                    }
                ?>
            <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->

            <div class="sidebar-custom">
                <!-- INICIO DE LA MODIFICACIÓN -->
                <a href="<?= base_url() ?>/logout" class="nav-link d-flex align-items-center" data-toggle="tooltip" data-placement="top" title="Cerrar Sesión">
                    <i class="nav-icon fas fa-sign-out-alt text-danger fa-fw fa-lg"></i>
                    <p class="mb-0 ml-2 font-weight- text-white" style="font-size: 1.1rem;">Cerrar Sesión</p>
                </a>
                <!-- FIN DE LA MODIFICACIÓN -->
                <!-- <a href="#" class="btn btn-secondary hide-on-collapse pos-right">Help</a> -->
            </div>
            <!-- /.sidebar-custom -->
        </aside>
