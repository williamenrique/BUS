<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= IMG ?>logo.png">
    <title><?= $data['page_tag']?></title>
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
<style>
    /* Animación pulsante personalizada */
    .icon-pulsate {
        animation: pulsate 1.5s infinite;
    }

    @keyframes pulsate {
        0% { transform: scale(1); }
        50% { transform: scale(1.25); }
        100% { transform: scale(1); }
    }
</style>
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
                <?php
                // Notificaciones de Recuperación de Usuario (Campana)
                // Visible solo para Administradores del departamento de Sistemas
                if (isset($_SESSION['userData']['rol_nombre']) && strtoupper($_SESSION['userData']['rol_nombre']) === 'ADMINISTRADOR' && $_SESSION['userData']['departamento_nombre'] === 'Sistemas'):
                ?>
                <li class="nav-item dropdown" id="userRecoveryNotificationsContainer"> 
                    <a id="userRecoveryButton" class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
                        <i class="fas fa-user-shield text-info fa-lg"></i>
                        <span id="userRecoveryCount" class="badge badge-warning navbar-badge" style="display: none;"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item dropdown-header">Solicitudes de Recuperación</span>
                        <div class="dropdown-divider"></div>
                        <div id="userRecoveryItems">
                            <p class="text-center text-muted p-3">No hay solicitudes nuevas.</p>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="<?= base_url() ?>user/recuperar" class="dropdown-item dropdown-footer">Ver todas las solicitudes</a>
                    </div>
                </li>
                <?php endif; ?>
                <?php
                // Lógica para mostrar el ícono de notificaciones de órdenes
                // Para Encargado de Compras y Administrador de Sistemas.
                $isCompras = (isset($_SESSION['userData']['rol_nombre']) && strtoupper($_SESSION['userData']['rol_nombre']) === 'ENCARGADO' && $_SESSION['userData']['departamento_nombre'] === 'Compras');
                $isSistemasAdmin = (isset($_SESSION['userData']['rol_nombre']) && strtoupper($_SESSION['userData']['rol_nombre']) === 'ADMINISTRADOR' && strtoupper($_SESSION['userData']['departamento_nombre']) === 'SISTEMAS');
                $isAlmacen = (isset($_SESSION['userData']['rol_nombre']) && strtoupper($_SESSION['userData']['rol_nombre']) === 'ENCARGADO' && strtoupper($_SESSION['userData']['departamento_nombre']) === 'ALMACEN');
                if ($isCompras || $isSistemasAdmin || $isAlmacen):
                ?>
                <li class="nav-item dropdown" id="orderNotificationsContainer"> 
                    <a class="nav-link" data-toggle="dropdown" href="#" id="requisitionNotificationsButton">
                        <i class="fas fa-boxes text-primary fa-lg"></i>
                        <span id="requisitionNotificationCount" class="badge badge-info navbar-badge" style="display: none;"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                        <span class="dropdown-item dropdown-header">Notificaciones de Órdenes</span>
                        <div class="dropdown-divider"></div>
                        <div id="requisitionNotificationItems">
                            <p class="text-center text-muted p-3">No hay requisiciones nuevas.</p>
                        </div>
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
            <a href="<?= BASE_URL()?>" class="brand-link d-flex justify-content-center">
            <!-- <img src="<?= IMG ?>logo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8"> -->
            <span class="brand-text font-weight-light" style="font-size: 1.5rem;">SITGO</span>
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
                        <span class="badge badge-info mt-1"><?= $_SESSION['userData']['departamento_nombre'] ?></span>
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
