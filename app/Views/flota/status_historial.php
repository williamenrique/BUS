<!-- Page header -->
<div class="page-header">
    <div>
        <h2 class="page-title">Historial de Cambios de Estado</h2>
        <p class="page-subtitle">Registro de todos los cambios de estado de las unidades de flota</p>
    </div>
</div>

<!-- ============================================ -->
<!-- KPIs por estado                              -->
<!-- ============================================ -->
<div class="estacion-stats" id="status-kpis" style="grid-template-columns:repeat(4,1fr);">
    <div class="estacion-stat stat-green" data-status-kpi="activo" style="cursor:pointer;">
        <div class="estacion-stat-icon"><i data-lucide="check-circle" class="w-5 h-5"></i></div>
        <div>
            <span class="estacion-stat-label">Cambios a Activo</span>
            <strong id="kpi-activo">0</strong>
        </div>
    </div>
    <div class="estacion-stat stat-amber" data-status-kpi="inactivo" style="cursor:pointer;">
        <div class="estacion-stat-icon"><i data-lucide="power-off" class="w-5 h-5"></i></div>
        <div>
            <span class="estacion-stat-label">Cambios a Inactivo</span>
            <strong id="kpi-inactivo">0</strong>
        </div>
    </div>
    <div class="estacion-stat stat-blue" data-status-kpi="mantenimiento" style="cursor:pointer;">
        <div class="estacion-stat-icon"><i data-lucide="wrench" class="w-5 h-5"></i></div>
        <div>
            <span class="estacion-stat-label">Cambios a Mantenimiento</span>
            <strong id="kpi-mantenimiento">0</strong>
        </div>
    </div>
    <div class="estacion-stat" data-status-kpi="critico" style="cursor:pointer; background:linear-gradient(135deg,#334155,#0f172a);">
        <div class="estacion-stat-icon"><i data-lucide="alert-octagon" class="w-5 h-5"></i></div>
        <div>
            <span class="estacion-stat-label">Cambios a Crítico</span>
            <strong id="kpi-critico">0</strong>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- Tabla con filtros                            -->
<!-- ============================================ -->
<div class="card">
    <div class="dt-toolbar">
        <div class="dt-toolbar-left">
            <span class="dt-toolbar-label">Mostrar</span>
            <select id="filtroLimit" class="filter-select" style="min-width:70px;">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="dt-toolbar-label">registros</span>
        </div>

        <div class="dt-toolbar-right" style="flex-wrap:wrap; gap:.5rem;">
            <select id="filtroUnidad" class="filter-select" style="min-width:180px;">
                <option value="">Todas las unidades</option>
            </select>
            <select id="filtroStatus" class="filter-select" style="min-width:160px;">
                <option value="">Todos los estados</option>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
                <option value="2">En Mantenimiento</option>
                <option value="4">Crítico</option>
                <option value="5">Crítico (alt)</option>
            </select>
            <input type="date" id="filtroDesde" class="filter-select" style="min-width:140px;" title="Desde">
            <input type="date" id="filtroHasta" class="filter-select" style="min-width:140px;" title="Hasta">
            <button type="button" id="btnLimpiar" class="btn btn-ghost btn-sm">
                <?= icon_tag('x', 'x', 'w-4 h-4') ?>
                <span>Limpiar</span>
            </button>
            <div class="filter-search" style="min-width:240px;">
                <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                <input type="text" id="filtroBuscar" placeholder="Buscar unidad, VIN o motivo...">
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Unidad</th>
                    <th>Marca / Modelo</th>
                    <th>VIN</th>
                    <th>Estado</th>
                    <th>Motivo</th>
                    <th>Usuario</th>
                    <th class="text-center">Fecha</th>
                </tr>
            </thead>
            <tbody id="tbodyStatus">
                <tr><td colspan="7" class="dt-loading">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="dt-footer">
        <div class="dt-info">
            Mostrando <strong id="startStatus">0</strong>–<strong id="endStatus">0</strong>
            de <strong id="totalStatus">0</strong> registros
        </div>
        <div class="dt-pagination" id="paginationStatus"></div>
    </div>
</div>