<?php
return [
    // ---------- Auth ----------
    ['path' => '/',          'method' => 'GET',  'handler' => 'ControllerAuth@showLogin'],
    ['path' => '/login',     'method' => 'GET',  'handler' => 'ControllerAuth@showLogin'],
    ['path' => '/login',     'method' => 'POST', 'handler' => 'ControllerAuth@login'],
    ['path' => '/logout',    'method' => 'GET',  'handler' => 'ControllerAuth@logout'],

    // ---------- Dashboard ----------
    ['path' => '/dashboard', 'method' => 'GET',  'handler' => 'ControllerDashboard@index'],
    // ---------- Personal ----------
    ['path' => '/personal',                     'method' => 'GET',  'handler' => 'ControllerPersonal@index'],
    ['path' => '/personal/listar',              'method' => 'GET',  'handler' => 'ControllerPersonal@listar'],
    ['path' => '/personal/combos',              'method' => 'GET',  'handler' => 'ControllerPersonal@combos'],
    ['path' => '/personal/obtener/{id}',        'method' => 'GET',  'handler' => 'ControllerPersonal@obtener'],
    ['path' => '/personal/guardar',             'method' => 'POST', 'handler' => 'ControllerPersonal@guardar'],
    ['path' => '/personal/actualizar/{id}',     'method' => 'POST', 'handler' => 'ControllerPersonal@actualizar'],
    ['path' => '/personal/eliminar/{id}',       'method' => 'POST', 'handler' => 'ControllerPersonal@eliminar'],
    ['path' => '/personal/cambiar-status/{id}', 'method' => 'POST', 'handler' => 'ControllerPersonal@cambiarStatus'],
    // ============================================================
    // Usuarios
    // ============================================================
    ['path' => '/usuarios',                       'method' => 'GET',  'handler' => 'ControllerUsuario@index'],
    ['path' => '/usuarios/listar',                'method' => 'GET',  'handler' => 'ControllerUsuario@listar'],
    ['path' => '/usuarios/combos',                'method' => 'GET',  'handler' => 'ControllerUsuario@combos'],
    ['path' => '/usuarios/siguiente-nick',        'method' => 'GET',  'handler' => 'ControllerUsuario@siguienteNick'],
    ['path' => '/usuarios/obtener/{id}',          'method' => 'GET',  'handler' => 'ControllerUsuario@obtener'],
    ['path' => '/usuarios/menus/{id}',            'method' => 'GET',  'handler' => 'ControllerUsuario@menusAsignados'],
    ['path' => '/usuarios/menus/guardar/{id}',    'method' => 'POST', 'handler' => 'ControllerUsuario@guardarMenus'],
    ['path' => '/usuarios/guardar',               'method' => 'POST', 'handler' => 'ControllerUsuario@guardar'],
    ['path' => '/usuarios/actualizar/{id}',       'method' => 'POST', 'handler' => 'ControllerUsuario@actualizar'],
    ['path' => '/usuarios/eliminar/{id}',         'method' => 'POST', 'handler' => 'ControllerUsuario@eliminar'],
    ['path' => '/usuarios/cambiar-status/{id}',   'method' => 'POST', 'handler' => 'ControllerUsuario@cambiarStatus'],
    ['path' => '/usuarios/reset-password/{id}',   'method' => 'POST', 'handler' => 'ControllerUsuario@resetPassword'],
    ['path' => '/usuarios/eliminar-imagen/{id}',  'method' => 'POST', 'handler' => 'ControllerUsuario@eliminarImagen'],
    // ---------- Recuperar ----------
    ['path' => '/recuperar',                    'method' => 'GET',  'handler' => 'ControllerRecuperar@index'],
    ['path' => '/recuperar/solicitar',          'method' => 'POST', 'handler' => 'ControllerRecuperar@solicitar'],
    ['path' => '/recuperar/contador',           'method' => 'GET',  'handler' => 'ControllerRecuperar@contador'],
    ['path' => '/recuperar/listar',             'method' => 'GET',  'handler' => 'ControllerRecuperar@listar'],
    ['path' => '/recuperar/resetear/{id}',      'method' => 'POST', 'handler' => 'ControllerRecuperar@resetear'],
    ['path' => '/recuperar/resolver/{id}',      'method' => 'POST', 'handler' => 'ControllerRecuperar@resolver'],
    ['path' => '/recuperar/debug', 'method' => 'GET', 'handler' => 'ControllerRecuperar@debug'],
    // ============================================================
    // Flota (multi-institución)
    // ============================================================
    ['path' => '/flota/i/{inst}',                       'method' => 'GET',  'handler' => 'ControllerFlota@index'],
    ['path' => '/flota/i/{inst}/listar',                'method' => 'GET',  'handler' => 'ControllerFlota@listar'],
    ['path' => '/flota/i/{inst}/todas',                 'method' => 'GET',  'handler' => 'ControllerFlota@todas'],
    ['path' => '/flota/i/{inst}/combos',                'method' => 'GET',  'handler' => 'ControllerFlota@combos'],
    ['path' => '/flota/i/{inst}/obtener/{id}',          'method' => 'GET',  'handler' => 'ControllerFlota@obtener'],
    ['path' => '/flota/i/{inst}/guardar',               'method' => 'POST', 'handler' => 'ControllerFlota@guardar'],
    ['path' => '/flota/i/{inst}/actualizar/{id}',       'method' => 'POST', 'handler' => 'ControllerFlota@actualizar'],
    ['path' => '/flota/i/{inst}/cambiar-status/{id}',   'method' => 'POST', 'handler' => 'ControllerFlota@cambiarStatus'],
    ['path' => '/flota/i/{inst}/historial/{id}',        'method' => 'GET',  'handler' => 'ControllerFlota@historial'],
    ['path' => '/flota/i/{inst}/historial/{id}/data',   'method' => 'POST', 'handler' => 'ControllerFlota@historialData'],

    // ============================================================
    // Cambio de Aceite (multi-institución)
    // ============================================================
    ['path' => '/flota/aceite/i/{inst}',                'method' => 'GET',  'handler' => 'ControllerFlota@aceite'],
    ['path' => '/flota/aceite/i/{inst}/status',         'method' => 'GET',  'handler' => 'ControllerFlota@aceiteStatus'],
    ['path' => '/flota/aceite/i/{inst}/km',             'method' => 'POST', 'handler' => 'ControllerFlota@setKilometraje'],
    ['path' => '/flota/aceite/i/{inst}/cambio',         'method' => 'POST', 'handler' => 'ControllerFlota@setCambioAceite'],
    ['path' => '/flota/aceite/i/{inst}/eliminar',       'method' => 'POST', 'handler' => 'ControllerFlota@eliminarRegistroAceite'],
    // ---------- Flota ----------
    ['path' => '/flota',                     'method' => 'GET',  'handler' => 'ControllerFlota@index'],
    ['path' => '/flota/listar',              'method' => 'GET',  'handler' => 'ControllerFlota@listar'],
    ['path' => '/flota/todas',               'method' => 'GET',  'handler' => 'ControllerFlota@todas'],
    ['path' => '/flota/combos',              'method' => 'GET',  'handler' => 'ControllerFlota@combos'],
    ['path' => '/flota/obtener/{id}',        'method' => 'GET',  'handler' => 'ControllerFlota@obtener'],
    ['path' => '/flota/guardar',             'method' => 'POST', 'handler' => 'ControllerFlota@guardar'],
    ['path' => '/flota/actualizar/{id}',     'method' => 'POST', 'handler' => 'ControllerFlota@actualizar'],
    ['path' => '/flota/cambiar-status/{id}', 'method' => 'POST', 'handler' => 'ControllerFlota@cambiarStatus'],
    ['path' => '/flota/historial/{id}',      'method' => 'GET',  'handler' => 'ControllerFlota@historial'],
    ['path' => '/flota/historial/{id}/data','method' => 'POST', 'handler' => 'ControllerFlota@historialData'],
        // ---------- Cambio de Aceite ----------
    ['path' => '/flota/aceite',              'method' => 'GET',  'handler' => 'ControllerFlota@aceite'],
    ['path' => '/flota/aceite/status',       'method' => 'GET',  'handler' => 'ControllerFlota@aceiteStatus'],
    ['path' => '/flota/aceite/km',           'method' => 'POST', 'handler' => 'ControllerFlota@setKilometraje'],
    ['path' => '/flota/aceite/cambio',       'method' => 'POST', 'handler' => 'ControllerFlota@setCambioAceite'],
    ['path' => '/flota/aceite/eliminar',     'method' => 'POST', 'handler' => 'ControllerFlota@eliminarRegistroAceite'],
        // ---------- Menú (admin) ----------
    ['path' => '/menu',                            'method' => 'GET',  'handler' => 'ControllerMenu@index'],
    ['path' => '/menu/arbol',                      'method' => 'GET',  'handler' => 'ControllerMenu@arbol'],
    ['path' => '/menu/obtener/{id}',               'method' => 'GET',  'handler' => 'ControllerMenu@obtener'],
    ['path' => '/menu/combos',                     'method' => 'GET',  'handler' => 'ControllerMenu@combos'],
    ['path' => '/menu/guardar',                    'method' => 'POST', 'handler' => 'ControllerMenu@guardar'],
    ['path' => '/menu/actualizar/{id}',            'method' => 'POST', 'handler' => 'ControllerMenu@actualizar'],
    ['path' => '/menu/eliminar/{id}',              'method' => 'POST', 'handler' => 'ControllerMenu@eliminar'],
    ['path' => '/menu/cambiar-estado/{id}',        'method' => 'POST', 'handler' => 'ControllerMenu@cambiarEstado'],
    ['path' => '/menu/rutas/{id}',                 'method' => 'GET',  'handler' => 'ControllerMenu@rutas'],
    ['path' => '/menu/rutas/guardar/{id}',         'method' => 'POST', 'handler' => 'ControllerMenu@guardarRuta'],
    ['path' => '/menu/rutas/eliminar/{rutaId}',    'method' => 'POST', 'handler' => 'ControllerMenu@eliminarRuta'],
    ['path' => '/menu/usuarios/{id}',              'method' => 'GET',  'handler' => 'ControllerMenu@usuariosAsignados'],
    ['path' => '/menu/usuarios/asignar/{id}',      'method' => 'POST', 'handler' => 'ControllerMenu@asignarUsuario'],
    ['path' => '/menu/usuarios/quitar/{id}',       'method' => 'POST', 'handler' => 'ControllerMenu@quitarUsuario'],
    ['path' => '/menu/departamentos/{id}',         'method' => 'GET',  'handler' => 'ControllerMenu@departamentosAsignados'],
    ['path' => '/menu/departamentos/asignar/{id}', 'method' => 'POST', 'handler' => 'ControllerMenu@asignarDepartamento'],
    ['path' => '/menu/departamentos/quitar/{id}',  'method' => 'POST', 'handler' => 'ControllerMenu@quitarDepartamento'],
];