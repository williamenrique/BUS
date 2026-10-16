<?php
return [
    // ---------- Auth ----------
    ['path' => '/',          'method' => 'GET',  'handler' => 'ControllerAuth@showLogin'],
    ['path' => '/login',     'method' => 'GET',  'handler' => 'ControllerAuth@showLogin'],
    ['path' => '/login',     'method' => 'POST', 'handler' => 'ControllerAuth@login'],
    ['path' => '/logout',    'method' => 'GET',  'handler' => 'ControllerAuth@logout'],
    ['path' => '/recuperar', 'method' => 'POST', 'handler' => 'ControllerAuth@recuperar'],

    // ---------- Dashboard ----------
    ['path' => '/dashboard', 'method' => 'GET',  'handler' => 'ControllerDashboard@index'],
];