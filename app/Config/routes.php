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
    // ---------- Usuarios ----------
    ['path' => '/usuarios',                       'method' => 'GET',  'handler' => 'ControllerUsuario@index'],
    ['path' => '/usuarios/listar',                'method' => 'GET',  'handler' => 'ControllerUsuario@listar'],
    ['path' => '/usuarios/combos',                'method' => 'GET',  'handler' => 'ControllerUsuario@combos'],
    ['path' => '/usuarios/obtener/{id}',          'method' => 'GET',  'handler' => 'ControllerUsuario@obtener'],
    ['path' => '/usuarios/guardar',               'method' => 'POST', 'handler' => 'ControllerUsuario@guardar'],
    ['path' => '/usuarios/actualizar/{id}',       'method' => 'POST', 'handler' => 'ControllerUsuario@actualizar'],
    ['path' => '/usuarios/eliminar/{id}',         'method' => 'POST', 'handler' => 'ControllerUsuario@eliminar'],
    ['path' => '/usuarios/cambiar-status/{id}',   'method' => 'POST', 'handler' => 'ControllerUsuario@cambiarStatus'],
    ['path' => '/usuarios/reset-password/{id}',   'method' => 'POST', 'handler' => 'ControllerUsuario@resetPassword'],
    ['path' => '/usuarios/siguiente-nick', 'method' => 'GET', 'handler' => 'ControllerUsuario@siguienteNick'],
    ['path' => '/usuarios/eliminar-imagen/{id}',  'method' => 'POST', 'handler' => 'ControllerUsuario@eliminarImagen'],
    // ---------- Recuperar ----------
    ['path' => '/recuperar',                    'method' => 'GET',  'handler' => 'ControllerRecuperar@index'],
    ['path' => '/recuperar/solicitar',          'method' => 'POST', 'handler' => 'ControllerRecuperar@solicitar'],
    ['path' => '/recuperar/contador',           'method' => 'GET',  'handler' => 'ControllerRecuperar@contador'],
    ['path' => '/recuperar/listar',             'method' => 'GET',  'handler' => 'ControllerRecuperar@listar'],
    ['path' => '/recuperar/resetear/{id}',      'method' => 'POST', 'handler' => 'ControllerRecuperar@resetear'],
    ['path' => '/recuperar/resolver/{id}',      'method' => 'POST', 'handler' => 'ControllerRecuperar@resolver'],
    ['path' => '/recuperar/debug', 'method' => 'GET', 'handler' => 'ControllerRecuperar@debug'],
];