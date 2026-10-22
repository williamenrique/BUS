<?php
$user      = $user ?? auth_user();
$esSistema = $esSistema ?? false;
$deptoId   = (int)($user['departamento_id'] ?? 0);

// Mapeo departamento → sección visible
$MOSTRAR = [
    'flota'       => $esSistema || $deptoId === DEPARTAMENTO_OPERACIONES,
    'almacen'     => $esSistema || $deptoId === DEPARTAMENTO_ALMACEN || $deptoId === DEPARTAMENTO_TALLER,
    'compras'     => $esSistema || $deptoId === DEPARTAMENTO_COMPRAS,
    'bienes'      => $esSistema || $deptoId === DEPARTAMENTO_BIENES,
    'estacion'    => $esSistema || $deptoId === DEPARTAMENTO_ESTACION,
    'institucion' => $esSistema,
];
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Inicio</h2>
        <p class="page-subtitle">
            <?php if ($esSistema): ?>
                Dashboard general del sistema
            <?php else: ?>
                Bienvenido, <?= e(trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? ''))) ?>
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if ($MOSTRAR['institucion']): ?>
<div class="card mb-4" id="dashboard-inst-card">
    <div class="card-body" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem;">
        <div style="display:flex; align-items:center; gap:.85rem;">
            <div style="width:2.5rem; height:2.5rem; border-radius:.55rem; background:#dbeafe; color:#1e40af; display:flex; align-items:center; justify-content:center;">
                <i data-lucide="building-2" class="w-5 h-5"></i>
            </div>
            <div>
                <h5 style="margin:0; font-size:.95rem; font-weight:700; color:#0f172a;">Dashboard Institucional</h5>
                <small style="color:#64748b;">Seleccione la institución a visualizar</small>
            </div>
        </div>
        <div style="display:flex; align-items:center; gap:.5rem;">
            <label style="font-size:.78rem; font-weight:600; color:#475569;">Institución:</label>
            <select id="dash-institucion" class="filter-select" style="min-width:260px;"></select>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($esSistema): ?>
<!-- ============================================================ -->
<!-- SESIONES ACTIVAS (SOLO SISTEMA)                              -->
<!-- ============================================================ -->
<section class="card mb-4" id="sec-sesiones">
    <div class="card-body">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:.5rem;">
            <h3 class="estacion-card-title" style="margin:0;">
                <i data-lucide="users" class="w-5 h-5 text-primary"></i>
                Sesiones activas
                <span class="badge badge-info" id="sessions-count" style="margin-left:.35rem;">0</span>
            </h3>
            <button class="btn btn-ghost btn-sm" id="btnRefreshSessions">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                <span>Actualizar</span>
            </button>
        </div>
        <div id="active-users-list" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:.75rem;">
            <div class="text-muted" style="padding:1rem; text-align:center; grid-column:1/-1;">Cargando sesiones...</div>
        </div>
        <p id="no-active-users" class="text-muted" style="display:none; text-align:center; padding:1rem;">No hay usuarios con sesiones activas.</p>
    </div>
</section>
<?php endif; ?>


<?php if ($MOSTRAR['flota']): ?>
<!-- ============================================================ -->
<!-- FLOTA                                                        -->
<!-- ============================================================ -->
<section class="card mb-4" id="sec-flota">
    <div class="card-body">
        <h3 class="estacion-card-title">
            <i data-lucide="bus" class="w-5 h-5 text-primary"></i>
            Estado de Flota
        </h3>

        <div class="estacion-stats" id="flota-stats">
            <div class="estacion-stat stat-green" data-flota-status="1" style="cursor:pointer;">
                <div class="estacion-stat-icon"><i data-lucide="check-circle" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Operativas</span>
                    <strong id="flota-operativas">0</strong>
                </div>
            </div>
            <div class="estacion-stat stat-amber" data-flota-status="2" style="cursor:pointer;">
                <div class="estacion-stat-icon"><i data-lucide="alert-triangle" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Inoperativas</span>
                    <strong id="flota-inoperativas">0</strong>
                </div>
            </div>
            <div class="estacion-stat stat-blue" data-flota-status="3" style="cursor:pointer;">
                <div class="estacion-stat-icon"><i data-lucide="wrench" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Mantenimiento</span>
                    <strong id="flota-mantenimiento">0</strong>
                </div>
            </div>
            <div class="estacion-stat" data-flota-status="4" style="cursor:pointer; background:linear-gradient(135deg,#334155,#0f172a);">
                <div class="estacion-stat-icon"><i data-lucide="alert-octagon" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Críticas</span>
                    <strong id="flota-criticas">0</strong>
                </div>
            </div>
        </div>

        <!-- PANEL A · Resumen de aceite + top flota (por defecto) -->
        <div id="flota-panel-resumen">
            <div class="estacion-desgloses" style="grid-template-columns:1fr 1fr 1fr;">
                <div class="estacion-desglose">
                    <h4 class="estacion-desglose-title"><i data-lucide="droplet" class="w-4 h-4"></i> Aceite OK</h4>
                    <p style="margin:0; font-size:1.4rem; font-weight:800; color:#16a34a;" id="aceite-ok">0</p>
                </div>
                <div class="estacion-desglose">
                    <h4 class="estacion-desglose-title"><i data-lucide="clock" class="w-4 h-4"></i> Próximo</h4>
                    <p style="margin:0; font-size:1.4rem; font-weight:800; color:#b45309;" id="aceite-proximo">0</p>
                </div>
                <div class="estacion-desglose">
                    <h4 class="estacion-desglose-title"><i data-lucide="alert-circle" class="w-4 h-4"></i> Requerido</h4>
                    <p style="margin:0; font-size:1.4rem; font-weight:800; color:#dc2626;" id="aceite-requerido">0</p>
                </div>
            </div>

            <div class="table-wrap" style="max-height:340px; margin-top:1rem;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Marca / Modelo</th>
                            <th>Transmisión</th>
                            <th>Combustible</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Operativas</th>
                            <th class="text-center">Inoperativas</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-resumen-flota">
                        <tr><td colspan="6" class="dt-loading">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PANEL B · Unidades del estado seleccionado (oculto por defecto) -->
        <div id="flota-panel-estado" style="display:none;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:.75rem; flex-wrap:wrap; margin-bottom:.75rem;">
                <h4 class="estacion-card-title" style="margin:0; font-size:.92rem;">
                    <i data-lucide="list" class="w-4 h-4 text-primary"></i>
                    Unidades <span id="flota-estado-nombre">—</span>
                    <span class="badge badge-info" id="flota-estado-count" style="margin-left:.35rem;">0</span>
                </h4>
                <button class="btn btn-ghost btn-sm" id="btn-volver-resumen">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Volver al resumen</span>
                </button>
            </div>
            <div class="filter-search" style="max-width:320px; margin-bottom:.75rem;">
                <span class="filter-search-icon"><i data-lucide="search" class="w-4 h-4"></i></span>
                <input type="text" id="flota-estado-search" placeholder="Buscar unidad, marca, modelo...">
            </div>
            <div class="table-wrap" style="max-height:400px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Unidad</th>
                            <th>Marca / Modelo</th>
                            <th>VIN</th>
                            <th>Transmisión</th>
                            <th>Combustible</th>
                            <th class="text-right">KM</th>
                            <th class="text-right">Últ. Cambio</th>
                        </tr>
                    </thead>
                    <tbody id="flota-estado-body">
                        <tr><td colspan="7" class="dt-loading">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($MOSTRAR['almacen']): ?>
<!-- ============================================================ -->
<!-- ALMACÉN                                                      -->
<!-- ============================================================ -->
<section class="card mb-4" id="sec-almacen">
    <div class="card-body">
        <h3 class="estacion-card-title">
            <i data-lucide="package" class="w-5 h-5 text-primary"></i>
            Almacén
        </h3>

        <div class="estacion-stats" style="grid-template-columns:repeat(4,1fr);">
            <div class="estacion-stat stat-blue">
                <div class="estacion-stat-icon"><i data-lucide="package" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Total productos</span>
                    <strong id="almacen-total-productos">0</strong>
                </div>
            </div>
            <div class="estacion-stat" style="background:linear-gradient(135deg,#dc2626,#991b1b);">
                <div class="estacion-stat-icon"><i data-lucide="package-x" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Sin stock</span>
                    <strong id="almacen-productos-sin-stock">0</strong>
                </div>
            </div>
            <div class="estacion-stat stat-amber">
                <div class="estacion-stat-icon"><i data-lucide="package-minus" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Stock bajo</span>
                    <strong id="almacen-productos-stock-bajo">0</strong>
                </div>
            </div>
            <div class="estacion-stat stat-green">
                <div class="estacion-stat-icon"><i data-lucide="truck" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Despachadas mes</span>
                    <strong id="almacen-ordenes-despacho">0</strong>
                </div>
            </div>
        </div>

        <div class="estacion-desgloses" style="grid-template-columns:1fr 1fr 1fr;">
            <div class="estacion-desglose">
                <h4 class="estacion-desglose-title"><i data-lucide="clock" class="w-4 h-4"></i> Órdenes pendientes</h4>
                <p style="margin:0; font-size:1.4rem; font-weight:800; color:#b45309;" id="almacen-ordenes-pendientes">0</p>
            </div>
            <div class="estacion-desglose">
                <h4 class="estacion-desglose-title"><i data-lucide="droplet" class="w-4 h-4"></i> Lubricantes mes actual</h4>
                <p style="margin:0; font-size:1.4rem; font-weight:800; color:#1e40af;" id="almacen-lubricantes-actual">0 Lts</p>
            </div>
            <div class="estacion-desglose">
                <h4 class="estacion-desglose-title"><i data-lucide="calendar-clock" class="w-4 h-4"></i> Lubricantes mes anterior</h4>
                <p style="margin:0; font-size:1.4rem; font-weight:800; color:#64748b;" id="almacen-lubricantes-anterior">0 Lts</p>
            </div>
        </div>

        <h4 class="estacion-card-title" style="margin-top:1.25rem; font-size:.9rem;">
            <i data-lucide="trending-up" class="w-4 h-4 text-primary"></i>
            Top 10 productos despachados (mes actual)
        </h4>
        <div class="table-wrap" style="max-height:300px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width:40px;">#</th>
                        <th>Producto</th>
                        <th>Enlace</th>
                        <th>Ubicación</th>
                        <th class="text-right">Despachado</th>
                    </tr>
                </thead>
                <tbody id="tabla-top-productos">
                    <tr><td colspan="5" class="dt-loading">Cargando...</td></tr>
                </tbody>
            </table>
        </div>

        <h4 class="estacion-card-title" style="margin-top:1.25rem; font-size:.9rem;">
            <i data-lucide="list" class="w-4 h-4 text-primary"></i>
            Últimas 10 órdenes
        </h4>
        <div class="table-wrap" style="max-height:300px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Unidad</th>
                        <th>Operador</th>
                        <th class="text-center">Artículos</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody id="tabla-ultimas-ordenes">
                    <tr><td colspan="6" class="dt-loading">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($MOSTRAR['compras']): ?>
<!-- ============================================================ -->
<!-- COMPRAS                                                      -->
<!-- ============================================================ -->
<section class="card mb-4" id="sec-compras">
    <div class="card-body">
        <h3 class="estacion-card-title">
            <i data-lucide="shopping-cart" class="w-5 h-5 text-primary"></i>
            Compras
        </h3>

        <div class="estacion-stats" style="grid-template-columns:repeat(4,1fr);">
            <div class="estacion-stat" style="background:linear-gradient(135deg,#b45309,#92400e);">
                <div class="estacion-stat-icon"><i data-lucide="clock" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Requisiciones pend.</span>
                    <strong id="compras-requisiciones-pendientes">0</strong>
                </div>
            </div>
            <div class="estacion-stat stat-green">
                <div class="estacion-stat-icon"><i data-lucide="check-circle" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Órdenes aprobadas</span>
                    <strong id="compras-ordenes-aprobadas">0</strong>
                </div>
            </div>
            <div class="estacion-stat stat-blue">
                <div class="estacion-stat-icon"><i data-lucide="truck" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Despachadas mes</span>
                    <strong id="compras-ordenes-despachadas-mes">0</strong>
                </div>
            </div>
            <div class="estacion-stat" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);">
                <div class="estacion-stat-icon"><i data-lucide="dollar-sign" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Costeadas mes</span>
                    <strong id="compras-ordenes-costeadas-mes">0</strong>
                </div>
            </div>
        </div>

        <div class="estacion-desgloses" style="grid-template-columns:1fr 1fr 1fr 1fr;">
            <div class="estacion-desglose">
                <h4 class="estacion-desglose-title">Sin stock</h4>
                <p style="margin:0; font-size:1.4rem; font-weight:800; color:#dc2626;" id="compras-articulos-sin-stock">0</p>
            </div>
            <div class="estacion-desglose">
                <h4 class="estacion-desglose-title">Stock bajo</h4>
                <p style="margin:0; font-size:1.4rem; font-weight:800; color:#b45309;" id="compras-articulos-stock-bajo">0</p>
            </div>
            <div class="estacion-desglose">
                <h4 class="estacion-desglose-title">Total $ mes</h4>
                <p style="margin:0; font-size:1.4rem; font-weight:800; color:#1e40af;" id="compras-total-divisa-mes">$0.00</p>
            </div>
            <div class="estacion-desglose">
                <h4 class="estacion-desglose-title">Total Bs mes</h4>
                <p style="margin:0; font-size:1.4rem; font-weight:800; color:#166534;" id="compras-total-bs-mes">Bs. 0,00</p>
            </div>
        </div>

        <h4 class="estacion-card-title" style="margin-top:1.25rem; font-size:.9rem;">
            <i data-lucide="trending-up" class="w-4 h-4 text-primary"></i>
            Top 10 productos costeados (mes)
        </h4>
        <div class="table-wrap" style="max-height:300px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width:40px;">#</th>
                        <th>Producto</th>
                        <th>Proveedor</th>
                        <th>Enlace</th>
                        <th class="text-center">Costeos</th>
                        <th class="text-right">Total $</th>
                        <th class="text-right">Total Bs</th>
                    </tr>
                </thead>
                <tbody id="tabla-top-productos-costeados">
                    <tr><td colspan="7" class="dt-loading">Cargando...</td></tr>
                </tbody>
            </table>
        </div>

        <h4 class="estacion-card-title" style="margin-top:1.25rem; font-size:.9rem;">
            <i data-lucide="list" class="w-4 h-4 text-primary"></i>
            Últimas 10 requisiciones
        </h4>
        <div class="table-wrap" style="max-height:300px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Unidad</th>
                        <th>Solicitante</th>
                        <th class="text-center">Artículos</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody id="tabla-ultimas-requisiciones">
                    <tr><td colspan="6" class="dt-loading">Cargando...</td></tr>
                </tbody>
            </table>
        </div>

        <h4 class="estacion-card-title" style="margin-top:1.25rem; font-size:.9rem;">
            <i data-lucide="list" class="w-4 h-4 text-primary"></i>
            Últimas 10 compras costeadas
        </h4>
        <div class="table-wrap" style="max-height:300px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Unidad</th>
                        <th class="text-center">Artículos</th>
                        <th class="text-right">Total $</th>
                        <th class="text-right">Total Bs</th>
                    </tr>
                </thead>
                <tbody id="tabla-ultimas-compras-costeadas">
                    <tr><td colspan="6" class="dt-loading">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($MOSTRAR['bienes']): ?>
<!-- ============================================================ -->
<!-- BIENES                                                       -->
<!-- ============================================================ -->
<section class="card mb-4" id="sec-bienes">
    <div class="card-body">
        <h3 class="estacion-card-title">
            <i data-lucide="folder-open" class="w-5 h-5 text-primary"></i>
            Bienes
        </h3>

        <div class="estacion-stats" style="grid-template-columns:repeat(4,1fr);">
            <div class="estacion-stat stat-blue">
                <div class="estacion-stat-icon"><i data-lucide="folder" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Total bienes</span>
                    <strong id="bienes-total">0</strong>
                </div>
            </div>
            <div class="estacion-stat stat-green">
                <div class="estacion-stat-icon"><i data-lucide="check-circle" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Activos</span>
                    <strong id="bienes-activos">0</strong>
                </div>
            </div>
            <div class="estacion-stat stat-amber">
                <div class="estacion-stat-icon"><i data-lucide="wrench" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">En reparación</span>
                    <strong id="bienes-reparacion">0</strong>
                </div>
            </div>
            <div class="estacion-stat" style="background:linear-gradient(135deg,#dc2626,#991b1b);">
                <div class="estacion-stat-icon"><i data-lucide="trash-2" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Dados de baja</span>
                    <strong id="bienes-baja">0</strong>
                </div>
            </div>
        </div>

        <h4 class="estacion-card-title" style="margin-top:1.25rem; font-size:.9rem;">
            <i data-lucide="list" class="w-4 h-4 text-primary"></i>
            Bienes recientes
        </h4>
        <div class="table-wrap" style="max-height:300px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Departamento</th>
                        <th class="text-center">Adquisición</th>
                    </tr>
                </thead>
                <tbody id="tabla-bienes-recientes">
                    <tr><td colspan="3" class="dt-loading">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($MOSTRAR['estacion']): ?>
<!-- ============================================================ -->
<!-- ESTACIÓN                                                     -->
<!-- ============================================================ -->
<section class="card mb-4" id="sec-estacion">
    <div class="card-body">
        <h3 class="estacion-card-title">
            <i data-lucide="fuel" class="w-5 h-5 text-primary"></i>
            Estación de Servicio
        </h3>

        <div class="estacion-stats" style="grid-template-columns:repeat(3,1fr);">
            <div class="estacion-stat stat-blue">
                <div class="estacion-stat-icon"><i data-lucide="receipt" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Ventas hoy</span>
                    <strong id="totalVentasHoy">0</strong>
                </div>
            </div>
            <div class="estacion-stat stat-green">
                <div class="estacion-stat-icon"><i data-lucide="droplet" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Litros hoy</span>
                    <strong id="totalLitrosHoy">0.00 Lts</strong>
                </div>
            </div>
            <div class="estacion-stat stat-amber">
                <div class="estacion-stat-icon"><i data-lucide="users" class="w-5 h-5"></i></div>
                <div>
                    <span class="estacion-stat-label">Operadores activos</span>
                    <strong id="user_activo">0</strong>
                </div>
            </div>
        </div>

        <div class="card mb-3" style="margin-top:1rem;">
            <div class="card-body">
                <h4 style="margin:0 0 .75rem; font-size:.9rem; font-weight:700; color:#0f172a;">
                    <i data-lucide="bar-chart-3" class="w-4 h-4" style="display:inline; vertical-align:middle;"></i>
                    Ventas del día por usuario
                </h4>
                <div style="position:relative; height:280px;">
                    <canvas id="dailySalesChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h4 style="margin:0 0 .75rem; font-size:.9rem; font-weight:700; color:#0f172a;">
                    <i data-lucide="pie-chart" class="w-4 h-4" style="display:inline; vertical-align:middle;"></i>
                    Litros vendidos por rango de meses
                </h4>
                <div class="pub-filters" style="grid-template-columns:1fr 1fr auto;">
                    <div class="form-group">
                        <label>Desde</label>
                        <select id="startMonth" class="filter-select"></select>
                    </div>
                    <div class="form-group">
                        <label>Hasta</label>
                        <select id="endMonth" class="filter-select"></select>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary" id="generateChartBtn">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                            <span>Generar</span>
                        </button>
                    </div>
                </div>
                <div id="noLitersDataMessage" class="text-muted text-center" style="display:none; padding:1rem;">Selecciona un rango y presiona Generar.</div>
                <div style="position:relative; height:320px; display:none;" id="monthlyChartWrap">
                    <canvas id="monthlyLitersChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
