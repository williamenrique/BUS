<?php
return [
    // ============================================================
    // Público · Movimientos (sin login)
    // ============================================================
    ['path' => '/movimientos',                                  'method' => 'GET',  'handler' => 'ControllerPublico@index'],
    ['path' => '/movimientos/instituciones',                    'method' => 'GET',  'handler' => 'ControllerPublico@getInstituciones'],
    ['path' => '/movimientos/estado-flota',                     'method' => 'POST', 'handler' => 'ControllerPublico@getEstadoFlota'],
    ['path' => '/movimientos/unidades-por-estado',              'method' => 'POST', 'handler' => 'ControllerPublico@getUnidadesPorEstado'],
    ['path' => '/movimientos/estado-aceite',                    'method' => 'POST', 'handler' => 'ControllerPublico@getEstadoAceite'],
    ['path' => '/movimientos/unidades-por-estado-aceite',       'method' => 'POST', 'handler' => 'ControllerPublico@getUnidadesPorEstadoAceite'],
    ['path' => '/movimientos/resumen-flota',                    'method' => 'POST', 'handler' => 'ControllerPublico@getResumenFlota'],
    ['path' => '/movimientos/movimientos-data',                 'method' => 'POST', 'handler' => 'ControllerPublico@getMovimientosData'],
    ['path' => '/movimientos/detalle-orden',                    'method' => 'POST', 'handler' => 'ControllerPublico@getDetalleOrden'],
    ['path' => '/movimientos/detalle-aceite',                   'method' => 'POST', 'handler' => 'ControllerPublico@getDetalleAceite'],
    ['path' => '/movimientos/detalle-mantenimiento',            'method' => 'POST', 'handler' => 'ControllerPublico@getDetalleMantenimiento'],
    ['path' => '/movimientos/detalle-kilometraje',              'method' => 'POST', 'handler' => 'ControllerPublico@getDetalleKilometraje'],
    ['path' => '/movimientos/historial-unidad',                 'method' => 'POST', 'handler' => 'ControllerPublico@getHistorialUnidad'],
    ['path' => '/movimientos/ventas-estacion',                  'method' => 'POST', 'handler' => 'ControllerPublico@getVentasEstacionData'],
    ['path' => '/movimientos/pdf',     'method' => 'POST', 'handler' => 'ControllerPublico@pdfGenerar'],
    ['path' => '/movimientos/pdf-ver', 'method' => 'GET',  'handler' => 'ControllerPublico@pdfVer'],
    // ============================================================
    // Auth
    // ============================================================
    ['path' => '/',          'method' => 'GET',  'handler' => 'ControllerAuth@showLogin'],
    ['path' => '/login',     'method' => 'GET',  'handler' => 'ControllerAuth@showLogin'],
    ['path' => '/login',     'method' => 'POST', 'handler' => 'ControllerAuth@login'],
    ['path' => '/logout',    'method' => 'GET',  'handler' => 'ControllerAuth@logout'],

    // ============================================================
    // Dashboard
    // ============================================================
    ['path' => '/dashboard', 'method' => 'GET',  'handler' => 'ControllerDashboard@index'],

    // ============================================================
    // Personal
    // ============================================================
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
    ['path' => '/usuarios/sesiones-activas',  'method' => 'GET',  'handler' => 'ControllerUsuario@sesionesActivas'],
    ['path' => '/usuarios/forzar-logout',     'method' => 'POST', 'handler' => 'ControllerUsuario@forzarLogout'],
    // ============================================================
    // Perfil de usuario (mi perfil)
    // ============================================================
    ['path' => '/perfil',                    'method' => 'GET',  'handler' => 'ControllerUsuario@perfil'],
    ['path' => '/perfil/actualizar',         'method' => 'POST', 'handler' => 'ControllerUsuario@actualizarPerfil'],
    ['path' => '/perfil/imagen',             'method' => 'POST', 'handler' => 'ControllerUsuario@subirImagenPerfil'],
    ['path' => '/perfil/imagen/eliminar',    'method' => 'POST', 'handler' => 'ControllerUsuario@eliminarImagenPerfil'],
    ['path' => '/perfil/password',           'method' => 'POST', 'handler' => 'ControllerUsuario@cambiarPasswordPerfil'],
    // ============================================================
    // Recuperar
    // ============================================================
    ['path' => '/recuperar',                    'method' => 'GET',  'handler' => 'ControllerRecuperar@index'],
    ['path' => '/recuperar/solicitar',          'method' => 'POST', 'handler' => 'ControllerRecuperar@solicitar'],
    ['path' => '/recuperar/contador',           'method' => 'GET',  'handler' => 'ControllerRecuperar@contador'],
    ['path' => '/recuperar/listar',             'method' => 'GET',  'handler' => 'ControllerRecuperar@listar'],
    ['path' => '/recuperar/resetear/{id}',      'method' => 'POST', 'handler' => 'ControllerRecuperar@resetear'],
    ['path' => '/recuperar/resolver/{id}',      'method' => 'POST', 'handler' => 'ControllerRecuperar@resolver'],
    ['path' => '/recuperar/debug',              'method' => 'GET',  'handler' => 'ControllerRecuperar@debug'],
    ['path' => '/recuperar/contador', 'method' => 'GET', 'handler' => 'ControllerRecuperar@contador'],

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
    // Cambio de Aceite (multi-institución) — ESPECÍFICAS PRIMERO
    // ============================================================
    ['path' => '/flota/aceite/i/{inst}/status',         'method' => 'GET',  'handler' => 'ControllerFlota@aceiteStatus'],
    ['path' => '/flota/aceite/i/{inst}/km',             'method' => 'POST', 'handler' => 'ControllerFlota@setKilometraje'],
    ['path' => '/flota/aceite/i/{inst}/cambio',         'method' => 'POST', 'handler' => 'ControllerFlota@setCambioAceite'],
    ['path' => '/flota/aceite/i/{inst}/eliminar',       'method' => 'POST', 'handler' => 'ControllerFlota@eliminarRegistroAceite'],
    ['path' => '/flota/aceite/i/{inst}',                'method' => 'GET',  'handler' => 'ControllerFlota@aceite'],

    // ============================================================
    // Flota (compat sin institución)
    // ============================================================
    ['path' => '/flota',                     'method' => 'GET',  'handler' => 'ControllerFlota@index'],
    ['path' => '/flota/listar',              'method' => 'GET',  'handler' => 'ControllerFlota@listar'],
    ['path' => '/flota/todas',               'method' => 'GET',  'handler' => 'ControllerFlota@todas'],
    ['path' => '/flota/combos',              'method' => 'GET',  'handler' => 'ControllerFlota@combos'],
    ['path' => '/flota/obtener/{id}',        'method' => 'GET',  'handler' => 'ControllerFlota@obtener'],
    ['path' => '/flota/guardar',             'method' => 'POST', 'handler' => 'ControllerFlota@guardar'],
    ['path' => '/flota/actualizar/{id}',     'method' => 'POST', 'handler' => 'ControllerFlota@actualizar'],
    ['path' => '/flota/cambiar-status/{id}', 'method' => 'POST', 'handler' => 'ControllerFlota@cambiarStatus'],
    ['path' => '/flota/historial/{id}',      'method' => 'GET',  'handler' => 'ControllerFlota@historial'],
    ['path' => '/flota/historial/{id}/data', 'method' => 'POST', 'handler' => 'ControllerFlota@historialData'],

    // ---------- Cambio de Aceite (compat sin institución) ----------
    ['path' => '/flota/aceite/status',       'method' => 'GET',  'handler' => 'ControllerFlota@aceiteStatus'],
    ['path' => '/flota/aceite/km',           'method' => 'POST', 'handler' => 'ControllerFlota@setKilometraje'],
    ['path' => '/flota/aceite/cambio',       'method' => 'POST', 'handler' => 'ControllerFlota@setCambioAceite'],
    ['path' => '/flota/aceite/eliminar',     'method' => 'POST', 'handler' => 'ControllerFlota@eliminarRegistroAceite'],
    ['path' => '/flota/aceite',              'method' => 'GET',  'handler' => 'ControllerFlota@aceite'],
    // ============================================================
    // Flota · Historial de cambios de estado
    // ============================================================
    ['path' => '/flota/status/i/{inst}',         'method' => 'GET',  'handler' => 'ControllerFlotaStatus@index'],
    ['path' => '/flota/status/i/{inst}/listar',  'method' => 'GET',  'handler' => 'ControllerFlotaStatus@listar'],
    ['path' => '/flota/status/i/{inst}/combos',  'method' => 'GET',  'handler' => 'ControllerFlotaStatus@combos'],
    // ============================================================
    // Menú (admin)
    // ============================================================
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

    // ============================================================
    // Bienes (multi-institución)
    // ============================================================
    ['path' => '/bienes/i/{inst}',                    'method' => 'GET',  'handler' => 'ControllerBienes@index'],
    ['path' => '/bienes/i/{inst}/listar',             'method' => 'GET',  'handler' => 'ControllerBienes@listar'],
    ['path' => '/bienes/i/{inst}/combos',             'method' => 'GET',  'handler' => 'ControllerBienes@combos'],
    ['path' => '/bienes/i/{inst}/obtener/{id}',       'method' => 'GET',  'handler' => 'ControllerBienes@obtener'],
    ['path' => '/bienes/i/{inst}/guardar',            'method' => 'POST', 'handler' => 'ControllerBienes@guardar'],
    ['path' => '/bienes/i/{inst}/actualizar/{id}',    'method' => 'POST', 'handler' => 'ControllerBienes@actualizar'],
    ['path' => '/bienes/i/{inst}/eliminar/{id}',      'method' => 'POST', 'handler' => 'ControllerBienes@eliminar'],

    // ============================================================
    // Almacén · Productos (multi-institución)
    // ============================================================
    ['path' => '/productos/i/{inst}',                     'method' => 'GET',  'handler' => 'ControllerProductos@index'],
    ['path' => '/productos/i/{inst}/listar',              'method' => 'GET',  'handler' => 'ControllerProductos@listar'],
    ['path' => '/productos/i/{inst}/combos',              'method' => 'GET',  'handler' => 'ControllerProductos@combos'],
    ['path' => '/productos/i/{inst}/obtener/{id}',        'method' => 'GET',  'handler' => 'ControllerProductos@obtener'],
    ['path' => '/productos/i/{inst}/guardar',             'method' => 'POST', 'handler' => 'ControllerProductos@guardar'],
    ['path' => '/productos/i/{inst}/actualizar/{id}',     'method' => 'POST', 'handler' => 'ControllerProductos@actualizar'],
    ['path' => '/productos/i/{inst}/agregar-stock/{id}',  'method' => 'POST', 'handler' => 'ControllerProductos@agregarStock'],
    ['path' => '/productos/i/{inst}/eliminar/{id}',       'method' => 'POST', 'handler' => 'ControllerProductos@eliminar'],

    // ============================================================
    // Almacén · Despachos (multi-institución)
    // ============================================================
    ['path' => '/despachos/i/{inst}',                     'method' => 'GET',  'handler' => 'ControllerDespachos@index'],
    ['path' => '/despachos/i/{inst}/listar',              'method' => 'GET',  'handler' => 'ControllerDespachos@listar'],
    ['path' => '/despachos/i/{inst}/combos',              'method' => 'GET',  'handler' => 'ControllerDespachos@combos'],
    ['path' => '/despachos/i/{inst}/obtener/{id}',        'method' => 'GET',  'handler' => 'ControllerDespachos@obtener'],
    ['path' => '/despachos/i/{inst}/guardar',             'method' => 'POST', 'handler' => 'ControllerDespachos@guardar'],
    ['path' => '/despachos/i/{inst}/actualizar/{id}',     'method' => 'POST', 'handler' => 'ControllerDespachos@actualizar'],
    ['path' => '/despachos/i/{inst}/eliminar/{id}',       'method' => 'POST', 'handler' => 'ControllerDespachos@eliminar'],
    ['path' => '/despachos/i/{inst}/articulos/{id}',      'method' => 'GET',  'handler' => 'ControllerDespachos@articulos'],
    ['path' => '/despachos/i/{inst}/stats',               'method' => 'GET',  'handler' => 'ControllerDespachos@stats'],
    ['path' => '/despachos/i/{inst}/historial',           'method' => 'GET',  'handler' => 'ControllerDespachos@historialProductos'],
    ['path' => '/despachos/i/{inst}/historial/listar',    'method' => 'GET',  'handler' => 'ControllerDespachos@historialListar'],
    ['path' => '/despachos/i/{inst}/historial/detalle/{id}', 'method' => 'POST', 'handler' => 'ControllerDespachos@historialDetalle'],

    // ============================================================
    // Almacén · Compras (multi-institución)
    // ============================================================
    ['path' => '/compras/i/{inst}',                       'method' => 'GET',  'handler' => 'ControllerCompras@index'],
    ['path' => '/compras/i/{inst}/pendientes',            'method' => 'GET',  'handler' => 'ControllerCompras@pendientes'],
    ['path' => '/compras/i/{inst}/costeadas',             'method' => 'GET',  'handler' => 'ControllerCompras@costeadas'],
    ['path' => '/compras/i/{inst}/articulos/{id}',        'method' => 'GET',  'handler' => 'ControllerCompras@articulosDespacho'],
    ['path' => '/compras/i/{inst}/asignar-costo',         'method' => 'POST', 'handler' => 'ControllerCompras@asignarCosto'],
    ['path' => '/compras/i/{inst}/detalle/{id}',          'method' => 'GET',  'handler' => 'ControllerCompras@detalleCosteado'],
    ['path' => '/compras/i/{inst}/anular/{id}',           'method' => 'POST', 'handler' => 'ControllerCompras@anularCosto'],
    ['path' => '/compras/i/{inst}/flota',                 'method' => 'GET',  'handler' => 'ControllerCompras@flota'],
    ['path' => '/compras/i/{inst}/conteo-ordenes',        'method' => 'POST', 'handler' => 'ControllerCompras@conteoOrdenes'],

    
    // ============================================================
    // Estación de Servicio
    // ============================================================
    // Selección de estación al login
    ['path' => '/estacion/seleccionar',           'method' => 'GET',  'handler' => 'ControllerEstacion@seleccionar'],
    ['path' => '/estacion/seleccionar',           'method' => 'POST', 'handler' => 'ControllerEstacion@guardarSeleccion'],

    // Venta (departamento Estación)
    ['path' => '/estacion/registrar',             'method' => 'GET',  'handler' => 'ControllerEstacion@registrar'],
    ['path' => '/estacion/initial-data',          'method' => 'GET',  'handler' => 'ControllerEstacion@initialData'],
    ['path' => '/estacion/update-tasa',           'method' => 'POST', 'handler' => 'ControllerEstacion@updateTasa'],
    ['path' => '/estacion/registrar-venta',       'method' => 'POST', 'handler' => 'ControllerEstacion@registrarVenta'],
    ['path' => '/estacion/cerrar-dia',            'method' => 'POST', 'handler' => 'ControllerEstacion@cerrarDia'],
    ['path' => '/estacion/cerrar-turno-pendiente','method' => 'POST', 'handler' => 'ControllerEstacion@cerrarTurnoPendiente'],
    ['path' => '/estacion/detalle-ventas',        'method' => 'POST', 'handler' => 'ControllerEstacion@getDetalleVentas'],
    ['path' => '/estacion/ticket/{id}',           'method' => 'POST', 'handler' => 'ControllerEstacion@getTicket'],
    ['path' => '/estacion/reporte-pdf',           'method' => 'POST', 'handler' => 'ControllerEstacion@generarReportePdf'],
    ['path' => '/estacion/tasa-actual',           'method' => 'GET',  'handler' => 'ControllerEstacion@getTasaCurrent'],
    ['path' => '/estacion/litros-reporte',        'method' => 'POST', 'handler' => 'ControllerEstacion@getLitrosReporte'],

    // DataVenta (departamento Sistema)
    ['path' => '/estacion/dataventa',             'method' => 'GET',  'handler' => 'ControllerEstacion@dataventa'],
    ['path' => '/estacion/litros-totales',        'method' => 'GET',  'handler' => 'ControllerEstacion@getLitrosTotales'],
    ['path' => '/estacion/litros-por-fecha',      'method' => 'POST', 'handler' => 'ControllerEstacion@getLitrosPorFecha'],
    ['path' => '/estacion/historial-cierres',     'method' => 'GET',  'handler' => 'ControllerEstacion@getHistorialCierres'],
    ['path' => '/estacion/ventas-cierre',         'method' => 'POST', 'handler' => 'ControllerEstacion@getVentasByCierre'],
    ['path' => '/estacion/ventas-abiertas',       'method' => 'POST', 'handler' => 'ControllerEstacion@getVentasAbiertas'],
    ['path' => '/estacion/open-sales',            'method' => 'GET',  'handler' => 'ControllerEstacion@getOpenSales'],
    ['path' => '/estacion/cerrar-venta/{id}',     'method' => 'POST', 'handler' => 'ControllerEstacion@closeSale'],
    ['path' => '/estacion/eliminar-venta',        'method' => 'POST', 'handler' => 'ControllerEstacion@deleteVenta'],
    ['path' => '/estacion/eliminar-cierre',       'method' => 'POST', 'handler' => 'ControllerEstacion@deleteCierre'],
    ['path' => '/estacion/eliminar-cierre-total', 'method' => 'POST', 'handler' => 'ControllerEstacion@deleteCierreTotal'],
    ['path' => '/estacion/eliminar-ventas-abiertas','method' => 'POST','handler' => 'ControllerEstacion@deleteAllOpenSales'],
    ['path' => '/estacion/reporte-litros',        'method' => 'POST', 'handler' => 'ControllerEstacion@generarReporteLitros'],
    ['path' => '/estacion/data-cierre',           'method' => 'POST', 'handler' => 'ControllerEstacion@getDataCierre'],

    // DataTable paginado (admin)
    ['path' => '/estacion/cierres/listar',        'method' => 'GET',  'handler' => 'ControllerEstacion@listarCierres'],
    ['path' => '/estacion/open-sales/listar',     'method' => 'GET',  'handler' => 'ControllerEstacion@listarVentasAbiertasPaginado'],
    ['path' => '/estacion/ventas-cierre/listar',  'method' => 'GET',  'handler' => 'ControllerEstacion@listarVentasCierre'],
    ['path' => '/estacion/ticket-by-venta/{id}',  'method' => 'POST', 'handler' => 'ControllerEstacion@getTicketByVenta'],
    // ============================================================
    // PDF (centralizado)
    // ============================================================
    ['path' => '/pdf/generar', 'method' => 'POST', 'handler' => 'ControllerPdf@generar'],
    ['path' => '/pdf/ver',     'method' => 'GET',  'handler' => 'ControllerPdf@ver'],
    
     // ============================================================
    // DASHBOARD
    // ============================================================
    ['path' => '/dashboard/instituciones',              'method' => 'GET',  'handler' => 'ControllerDashboard@getInstituciones'],
    ['path' => '/dashboard/almacen',                    'method' => 'GET',  'handler' => 'ControllerDashboard@getAlmacenData'],
    ['path' => '/dashboard/almacen/top-productos',      'method' => 'GET',  'handler' => 'ControllerDashboard@getTopProductosAlmacen'],
    ['path' => '/dashboard/almacen/ultimas-ordenes',    'method' => 'GET',  'handler' => 'ControllerDashboard@getUltimasOrdenesAlmacen'],
    ['path' => '/dashboard/operaciones',                'method' => 'GET',  'handler' => 'ControllerDashboard@getOperacionesData'],
    ['path' => '/dashboard/compras',                    'method' => 'GET',  'handler' => 'ControllerDashboard@getComprasData'],
    ['path' => '/dashboard/compras/top-productos',      'method' => 'GET',  'handler' => 'ControllerDashboard@getTopProductosCosteados'],
    ['path' => '/dashboard/compras/ultimas-requisiciones','method' => 'GET',  'handler' => 'ControllerDashboard@getUltimasRequisiciones'],
    ['path' => '/dashboard/compras/ultimas-costeadas',  'method' => 'GET',  'handler' => 'ControllerDashboard@getUltimasComprasCosteadas'],
    ['path' => '/dashboard/bienes',                     'method' => 'GET',  'handler' => 'ControllerDashboard@getBienesData'],
    ['path' => '/dashboard/estacion',                   'method' => 'GET',  'handler' => 'ControllerDashboard@getEstacionData'],
    ['path' => '/dashboard/estacion/meses',             'method' => 'GET',  'handler' => 'ControllerDashboard@getAvailableMonths'],
    ['path' => '/dashboard/estacion/litros-mensuales',  'method' => 'POST', 'handler' => 'ControllerDashboard@getMonthlyLiters'],
    ['path' => '/dashboard/estacion/ventas-dia',        'method' => 'POST', 'handler' => 'ControllerDashboard@getDailySales'],
        // ============================================================
    // Dashboard (Sistema)
    // ============================================================
    ['path' => '/dashboard/almacen',                       'method' => 'GET',  'handler' => 'ControllerDashboard@getAlmacenData'],
    ['path' => '/dashboard/almacen/top-productos',         'method' => 'GET',  'handler' => 'ControllerDashboard@getTopProductosAlmacen'],
    ['path' => '/dashboard/almacen/ultimas-ordenes',       'method' => 'GET',  'handler' => 'ControllerDashboard@getUltimasOrdenesAlmacen'],
    ['path' => '/dashboard/operaciones',                   'method' => 'GET',  'handler' => 'ControllerDashboard@getOperacionesData'],
    ['path' => '/dashboard/compras',                       'method' => 'GET',  'handler' => 'ControllerDashboard@getComprasData'],
    ['path' => '/dashboard/compras/top-productos',         'method' => 'GET',  'handler' => 'ControllerDashboard@getTopProductosCosteados'],
    ['path' => '/dashboard/compras/ultimas-requisiciones', 'method' => 'GET',  'handler' => 'ControllerDashboard@getUltimasRequisiciones'],
    ['path' => '/dashboard/compras/ultimas-costeadas',     'method' => 'GET',  'handler' => 'ControllerDashboard@getUltimasComprasCosteadas'],
    ['path' => '/dashboard/bienes',                        'method' => 'GET',  'handler' => 'ControllerDashboard@getBienesData'],
    ['path' => '/dashboard/estacion',                      'method' => 'GET',  'handler' => 'ControllerDashboard@getEstacionData'],
    ['path' => '/dashboard/estacion/meses',                'method' => 'GET',  'handler' => 'ControllerDashboard@getAvailableMonths'],
    ['path' => '/dashboard/estacion/litros-mensuales',     'method' => 'POST', 'handler' => 'ControllerDashboard@getMonthlyLiters'],
    ['path' => '/dashboard/estacion/ventas-dia',           'method' => 'POST', 'handler' => 'ControllerDashboard@getDailySales'],
];