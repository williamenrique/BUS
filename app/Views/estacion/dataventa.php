<?php
$user = auth_user();
?>

<!-- Page header -->
<div class="page-header">
    <div>
        <h2 class="page-title">Data Venta · Administrador</h2>
        <p class="page-subtitle">Litros, ventas abiertas y cierres</p>
    </div>
    <div style="display:flex; gap:.5rem;">
        <button id="btnPdfLitros" class="btn btn-ghost">
            <?= icon_tag('file-text', 'file-text', 'w-4 h-4') ?>
            <span>PDF Litros</span>
        </button>
    </div>
</div>

<!-- ============================================ -->
<!-- FILTROS Y RESUMEN DE LITROS                   -->
<!-- ============================================ -->
<div class="card mb-4">
    <div class="card-body" style="padding:1rem 1.25rem;">

        <!-- Fila 1: Filtros -->
        <div class="litros-filtros">
            <div class="litros-filtro">
                <label class="litros-filtro-label">Agrupar por</label>
                <select id="litrosTipo" class="filter-select">
                    <option value="day" selected>Día</option>
                    <option value="month">Mes</option>
                </select>
            </div>
            <div class="litros-filtro">
                <label class="litros-filtro-label">Fecha</label>
                <input type="date" id="litrosFecha" class="filter-select">
            </div>
            <div class="litros-filtro">
                <label class="litros-filtro-label">Estación</label>
                <select id="litrosEstacion" class="filter-select">
                    <option value="0">Todas las estaciones</option>
                </select>
            </div>
            <div class="litros-filtro litros-filtro-btn">
                <label class="litros-filtro-label" style="visibility:hidden;">.</label>
                <button type="button" id="btnLitrosActualizar" class="btn btn-primary litros-btn-actualizar">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    <span>Actualizar</span>
                </button>
            </div>
        </div>

        <!-- Fila 2: Tarjetas resumen -->
        <div class="estacion-stats litros-stats">
            <div class="estacion-stat stat-blue">
                <div class="estacion-stat-icon"><i data-lucide="calendar" class="w-6 h-6"></i></div>
                <div>
                    <span class="estacion-stat-label">Litros en el período</span>
                    <strong id="litrosPeriodo">0.00 L</strong>
                    <small id="litrosPeriodoLabel" class="litros-stat-label">—</small>
                </div>
            </div>
            <div class="estacion-stat stat-green">
                <div class="estacion-stat-icon"><i data-lucide="fuel" class="w-6 h-6"></i></div>
                <div>
                    <span class="estacion-stat-label">Estación seleccionada</span>
                    <strong id="litrosEstacionSel">—</strong>
                    <small id="litrosEstacionLabel" class="litros-stat-label">Selecciona una estación</small>
                </div>
            </div>
            <div class="estacion-stat stat-amber">
                <div class="estacion-stat-icon"><i data-lucide="bar-chart-3" class="w-6 h-6"></i></div>
                <div>
                    <span class="estacion-stat-label">Estaciones con ventas</span>
                    <strong id="litrosEstacionesCount">0</strong>
                    <small id="litrosEstacionesLabel" class="litros-stat-label">En el período</small>
                </div>
            </div>
        </div>

        <!-- Fila 3: Gráfico comparativo (colapsable) -->
        <div class="litros-chart-section" id="litrosChartSection">
            <button type="button" class="litros-chart-toggle" id="btnToggleChart" aria-expanded="true">
                <span class="litros-chart-toggle-left">
                    <i data-lucide="bar-chart-3" class="w-4 h-4" style="color:#2563eb;"></i>
                    <strong>Comparativo de litros por estación</strong>
                </span>
                <span class="litros-chart-toggle-icon" id="litrosChartChevron">
                    <i data-lucide="chevron-up" class="w-4 h-4"></i>
                </span>
            </button>

            <div class="litros-chart-body" id="litrosChartBody">
                <canvas id="litrosPorEstacionChart" style="max-height:280px;"></canvas>
                <div id="litrosChartEmpty" style="text-align:center; padding:2rem 1rem; color:#94a3b8; font-size:.85rem; display:none;">
                    Sin datos para mostrar en el período seleccionado.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- VENTAS ABIERTAS                               -->
<!-- ============================================ -->
<div class="card mb-4">
    <div class="dt-toolbar">
        <div class="dt-toolbar-left">
            <span class="dt-toolbar-label">Mostrar</span>
            <select id="filtroLimitOpen" class="filter-select" style="min-width:70px;">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="dt-toolbar-label">registros</span>
        </div>
        <div class="dt-toolbar-right">
            <div class="filter-search" style="min-width:240px;">
                <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                <input type="text" id="filtroBuscarOpen" placeholder="Buscar empleado...">
            </div>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Fecha</th>
                    <th class="text-right">Litros</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyOpenSales">
                <tr><td colspan="4" class="dt-loading">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="dt-footer">
        <div class="dt-info">
            Mostrando <strong id="startOpenSales">0</strong>–<strong id="endOpenSales">0</strong>
            de <strong id="totalOpenSales">0</strong> ventas abiertas
        </div>
        <div class="dt-pagination" id="paginationOpenSales"></div>
    </div>
</div>

<!-- ============================================ -->
<!-- HISTORIAL DE CIERRES                          -->
<!-- ============================================ -->
<div class="card">
    <div class="dt-toolbar">
        <div class="dt-toolbar-left">
            <span class="dt-toolbar-label">Mostrar</span>
            <select id="filtroLimitCierres" class="filter-select" style="min-width:70px;">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="dt-toolbar-label">registros</span>
        </div>
        <div class="dt-toolbar-right">
            <select id="searchType" class="filter-select" style="min-width:120px;">
                <option value="day">Por día</option>
                <option value="month">Por mes</option>
            </select>
            <input type="date" id="searchDateDay" class="filter-select" style="padding:.55rem .75rem;">
            <input type="month" id="searchDateMonth" class="filter-select" style="padding:.55rem .75rem; display:none;">
            <div class="filter-search" style="min-width:240px;">
                <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                <input type="text" id="filtroBuscarCierres" placeholder="Buscar empleado o #cierre...">
            </div>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Empleado</th>
                    <th>Tasa</th>
                    <th>Fecha</th>
                    <th class="text-right">Efectivo Bs</th>
                    <th class="text-right">P. Venta Bs</th>
                    <th class="text-right">Total Bs</th>
                    <th class="text-right">Litros</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyCierres">
                <tr><td colspan="9" class="dt-loading">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="dt-footer">
        <div class="dt-info">
            Mostrando <strong id="startCierres">0</strong>–<strong id="endCierres">0</strong>
            de <strong id="totalCierres">0</strong> cierres
        </div>
        <div class="dt-pagination" id="paginationCierres"></div>
    </div>
</div>

<!-- ============================================ -->
<!-- DETALLE DEL CIERRE                            -->
<!-- ============================================ -->
<div id="ventasCierreSection" class="card mt-4" style="display:none;">
    <div class="dt-toolbar">
        <div class="dt-toolbar-left">
            <h3 id="cierreTitle" class="estacion-card-title" style="margin:0;">Detalle del cierre</h3>
        </div>
        <div class="dt-toolbar-right">
            <div class="filter-search" style="min-width:240px;">
                <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                <input type="text" id="filtroBuscarVentasCierre" placeholder="Buscar ticket, pago, vehículo...">
            </div>
            <select id="filtroLimitVentasCierre" class="filter-select" style="min-width:70px;">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Hora</th>
                    <th>Vehículo</th>
                    <th>Combustible</th>
                    <th class="text-right">Litros</th>
                    <th>Pago</th>
                    <th class="text-right">Monto</th>
                    <th class="text-right"></th>
                </tr>
            </thead>
            <tbody id="tbodyVentasCierre">
                <tr><td colspan="8" class="dt-loading">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="dt-footer">
        <div class="dt-info">
            Mostrando <strong id="startVentasCierre">0</strong>–<strong id="endVentasCierre">0</strong>
            de <strong id="totalVentasCierre">0</strong> ventas
        </div>
        <div style="display:flex; gap:.5rem; align-items:center;">
            <div class="dt-pagination" id="paginationVentasCierre"></div>
        </div>
    </div>
    <div style="display:flex; gap:.5rem; justify-content:flex-end; padding:1rem 1.25rem; border-top:1px solid #e2e8f0;">
        <button id="btnImprimirCierre" class="btn btn-primary">
            <?= icon_tag('printer', 'printer', 'w-4 h-4') ?>
            <span>Imprimir cierre</span>
        </button>
        <button id="btnImprimirPdf" class="btn btn-danger">
            <?= icon_tag('file-text', 'file-text', 'w-4 h-4') ?>
            <span>Generar PDF</span>
        </button>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL · Selector de reporte PDF               -->
<!-- ============================================ -->
<div id="modalPdfSelector" class="pdf-modal-backdrop">
    <div class="pdf-modal">
        <div class="pdf-modal-header">
            <h3>¿Qué reporte desea generar?</h3>
            <button type="button" class="pdf-modal-close" data-close="1">&times;</button>
        </div>
        <div class="pdf-modal-body">
            <button type="button" class="pdf-opt" data-seccion="resumen">
                <div class="pdf-opt-icon" style="background:#dbeafe; color:#1e40af;">
                    <?= icon_tag('bar-chart-3', 'bar-chart-3', 'w-5 h-5') ?>
                </div>
                <div class="pdf-opt-text">
                    <strong>Resumen General</strong>
                    <small>Totales y desgloses por combustible, vehículo y pago</small>
                </div>
            </button>
            <button type="button" class="pdf-opt" data-seccion="ventas">
                <div class="pdf-opt-icon" style="background:#dcfce7; color:#166534;">
                    <?= icon_tag('list', 'list', 'w-5 h-5') ?>
                </div>
                <div class="pdf-opt-text">
                    <strong>Todas las Ventas</strong>
                    <small>Listado completo del día (gasolina y diésel)</small>
                </div>
            </button>
            <button type="button" class="pdf-opt" data-seccion="gasolina">
                <div class="pdf-opt-icon" style="background:#fef3c7; color:#b45309;">
                    <?= icon_tag('fuel', 'fuel', 'w-5 h-5') ?>
                </div>
                <div class="pdf-opt-text">
                    <strong>Solo Gasolina</strong>
                    <small>Ventas de gasolina únicamente</small>
                </div>
            </button>
            <button type="button" class="pdf-opt" data-seccion="diesel">
                <div class="pdf-opt-icon" style="background:#ede9fe; color:#6d28d9;">
                    <?= icon_tag('droplet', 'droplet', 'w-5 h-5') ?>
                </div>
                <div class="pdf-opt-text">
                    <strong>Solo Diésel</strong>
                    <small>Ventas de diésel únicamente</small>
                </div>
            </button>
        </div>
    </div>
</div>