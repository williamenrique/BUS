<?php
$instId = $instId ?? inst_actual();
$instNombreFull = 'Institución #' . $instId;
try {
    $row = \App\Core\Database::getConnection()
        ->query("SELECT nombre FROM table_instituciones WHERE id_institucion = " . (int)$instId)
        ->fetch();
    if ($row && !empty($row['nombre'])) $instNombreFull = $row['nombre'];
} catch (\Throwable $e) {}
$instNombreCorto = inst_nombre_corto($instNombreFull);
?>

<!-- Page header -->
<div class="page-header">
    <div>
        <h2 class="page-title">
            Compras
            <span class="badge badge-info" style="font-size:.7rem; vertical-align:middle;"
                  title="<?= e($instNombreFull) ?>">
                <?= e($instNombreCorto) ?>
            </span>
        </h2>
        <p class="page-subtitle">Costeo de artículos despachados</p>
    </div>
</div>

<!-- ============================================ -->
<!-- TABS                                          -->
<!-- ============================================ -->
<div class="compras-tabs">
    <button type="button" class="compras-tab active" data-tab="pendientes">
        <?= icon_tag('clock', 'clock', 'w-4 h-4') ?>
        <span>Pendientes</span>
        <span class="compras-tab-badge" id="badgePendientes">0</span>
    </button>
    <button type="button" class="compras-tab" data-tab="costeadas">
        <?= icon_tag('check-circle', 'check-circle', 'w-4 h-4') ?>
        <span>Costeadas</span>
    </button>
    <button type="button" class="compras-tab" data-tab="reportes">
        <?= icon_tag('bar-chart-3', 'bar-chart-3', 'w-4 h-4') ?>
        <span>Reportes</span>
    </button>
</div>

<!-- ============================================ -->
<!-- TAB: PENDIENTES                               -->
<!-- ============================================ -->
<div id="tabPendientes" class="compras-panel">
    <div class="card">
        <div class="dt-toolbar">
            <div class="dt-toolbar-left">
                <span class="dt-toolbar-label">Mostrar</span>
                <select id="filtroLimitPend" class="filter-select" style="min-width:70px;">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="dt-toolbar-label">registros</span>
            </div>
            <div class="dt-toolbar-right">
                <div class="filter-search" style="min-width:280px;">
                    <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                    <input type="text" id="filtroBuscarPend" placeholder="Buscar despacho, unidad...">
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Unidad</th>
                        <th class="text-center">Artículos</th>
                        <th class="text-center">Unidades</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyPendientes">
                    <tr><td colspan="6" class="dt-loading">Cargando...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="dt-footer">
            <div class="dt-info">
                Mostrando <strong id="startPendientes">0</strong>–<strong id="endPendientes">0</strong>
                de <strong id="totalPendientes">0</strong> despachos
            </div>
            <div class="dt-pagination" id="paginationPendientes"></div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- TAB: COSTEADAS                                -->
<!-- ============================================ -->
<div id="tabCosteadas" class="compras-panel" style="display:none;">
    <div class="card">
        <div class="dt-toolbar">
            <div class="dt-toolbar-left">
                <span class="dt-toolbar-label">Mostrar</span>
                <select id="filtroLimitCost" class="filter-select" style="min-width:70px;">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="dt-toolbar-label">registros</span>
            </div>

            <div class="dt-toolbar-right" style="gap:.4rem;">
                <input type="date" id="filtroCostoDesde"
                       class="filter-select"
                       title="Desde"
                       style="padding:.4rem .6rem; font-size:.8rem; height:34px; min-width:140px;">

                <input type="date" id="filtroCostoHasta"
                       class="filter-select"
                       title="Hasta"
                       style="padding:.4rem .6rem; font-size:.8rem; height:34px; min-width:140px;">

                <button type="button" id="btnFiltrarCosteadas"
                        class="btn btn-primary btn-sm" title="Aplicar filtros">
                    <?= icon_tag('filter', 'filter', 'w-4 h-4') ?>
                    <span>Aplicar</span>
                </button>

                <button type="button" id="btnLimpiarCosteadas"
                        class="action-btn" title="Limpiar filtros">
                    <?= icon_tag('rotate-ccw', 'rotate-ccw', 'w-4 h-4') ?>
                </button>

                <div class="filter-search" style="min-width:200px;">
                    <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                    <input type="text" id="filtroBuscarCost" placeholder="Buscar...">
                </div>

                <button type="button" id="btnPdfCosteadas"
                        class="btn btn-primary btn-sm" title="PDF de las costeadas filtradas">
                    <?= icon_tag('file-text', 'file-text', 'w-4 h-4') ?>
                    <span>PDF</span>
                </button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fecha</th>
                        <th>Unidad</th>
                        <th class="text-center">Artículos</th>
                        <th class="text-right">Total $</th>
                        <th class="text-right">Total Bs</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyCosteadas">
                    <tr><td colspan="7" class="dt-loading">Cargando...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="dt-footer">
            <div class="dt-info">
                Mostrando <strong id="startCosteadas">0</strong>–<strong id="endCosteadas">0</strong>
                de <strong id="totalCosteadas">0</strong> despachos
            </div>
            <div class="dt-pagination" id="paginationCosteadas"></div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- TAB: REPORTES                                 -->
<!-- ============================================ -->
<div id="tabReportes" class="compras-panel" style="display:none;">
    <div class="card">
        <div class="card-body" style="padding:1.25rem;">
            <h3 style="font-size:1rem; font-weight:600; color:#0f172a; margin:0 0 1rem;">
                <?= icon_tag('file-text', 'file-text', 'w-4 h-4') ?>
                Reporte por Unidad
            </h3>
            <div class="compras-filtros" style="grid-template-columns: 2fr 1fr 1fr auto;">
                <div class="form-group" style="margin:0;">
                    <label style="font-size:.72rem; color:#475569; font-weight:500; margin-bottom:.25rem; display:block;">Unidad</label>
                    <div id="wrapRepUnidad"></div>
                    <input type="hidden" id="repUnidad">
                </div>
                <div class="form-group" style="margin:0;">
                    <label style="font-size:.72rem; color:#475569; font-weight:500; margin-bottom:.25rem; display:block;">Desde</label>
                    <input type="date" id="repDesde" class="filter-select" style="padding:.5rem .65rem;">
                </div>
                <div class="form-group" style="margin:0;">
                    <label style="font-size:.72rem; color:#475569; font-weight:500; margin-bottom:.25rem; display:block;">Hasta</label>
                    <input type="date" id="repHasta" class="filter-select" style="padding:.5rem .65rem;">
                </div>
                <button type="button" id="btnGenerarReporte" class="btn btn-primary" disabled>
                    <?= icon_tag('file-text', 'file-text', 'w-4 h-4') ?>
                    <span>Generar PDF</span>
                </button>
            </div>
            <p id="repConteo" class="text-muted" style="font-size:.8rem; margin-top:.75rem;">
                Selecciona una unidad para ver las órdenes disponibles.
            </p>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL · ASIGNAR COSTO                         -->
<!-- ============================================ -->
<div id="modalCosto" class="modal-backdrop">
    <div class="modal" style="max-width:820px;">
        <div class="modal-header">
            <h3><?= icon_tag('dollar-sign', 'dollar-sign', 'w-4 h-4') ?> Asignar Costo</h3>
            <button type="button" class="modal-close" onclick="cerrarModalCosto()">&times;</button>
        </div>
        <form id="formCosto" style="padding:1.5rem;">
            <input type="hidden" id="costo_id_despacho">

            <div class="costo-info">
                <div>
                    <span class="costo-info-label">Despacho</span>
                    <strong id="costo_info_numero">—</strong>
                </div>
                <div>
                    <span class="costo-info-label">Unidad</span>
                    <strong id="costo_info_unidad">—</strong>
                </div>
                <div>
                    <span class="costo-info-label">Fecha</span>
                    <strong id="costo_info_fecha">—</strong>
                </div>
            </div>

            <div class="form-group" style="margin-top:1rem;">
                <label>Tasa del día (Bs por $) <span class="req">*</span></label>
                <input type="number" id="costo_tasa" min="0.01" step="0.01" style="max-width:220px;" required>
            </div>

            <div class="table-wrap" style="margin-top:1rem;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Artículo</th>
                            <th class="text-right">Cantidad</th>
                            <th class="text-right">Monto Unit. ($)</th>
                            <th class="text-right">Monto Total ($)</th>
                            <th class="text-right">Monto Total (Bs)</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyArticulosCosto">
                        <tr><td colspan="5" class="dt-loading">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="modal-footer" style="margin-top:1.25rem; padding-top:1rem;">
                <button type="button" class="btn btn-ghost" onclick="cerrarModalCosto()">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <?= icon_tag('save', 'save', 'w-4 h-4') ?>
                    <span>Guardar Costo</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL · VER DETALLE COSTEADO                  -->
<!-- ============================================ -->
<div id="modalDetalle" class="modal-backdrop">
    <div class="modal" style="max-width:820px;">
        <div class="modal-header">
            <h3><?= icon_tag('eye', 'eye', 'w-4 h-4') ?> Detalle de Costeo</h3>
            <button type="button" class="modal-close" onclick="cerrarModalDetalle()">&times;</button>
        </div>
        <div style="padding:1.5rem;">

            <div class="costo-info">
                <div>
                    <span class="costo-info-label">Despacho</span>
                    <strong id="det_info_numero">—</strong>
                </div>
                <div>
                    <span class="costo-info-label">Unidad</span>
                    <strong id="det_info_unidad">—</strong>
                </div>
                <div>
                    <span class="costo-info-label">Fecha</span>
                    <strong id="det_info_fecha">—</strong>
                </div>
            </div>

            <div class="table-wrap" style="margin-top:1rem;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Artículo</th>
                            <th class="text-right">Cantidad</th>
                            <th class="text-right">Tasa</th>
                            <th class="text-right">Monto $</th>
                            <th class="text-right">Monto Bs</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyDetalle">
                        <tr><td colspan="5" class="dt-loading">Cargando...</td></tr>
                    </tbody>
                    <tfoot id="tfootDetalle"></tfoot>
                </table>
            </div>

            <div class="modal-footer" style="margin-top:1.25rem; padding-top:1rem;">
                <button type="button" class="btn btn-ghost" onclick="cerrarModalDetalle()">Cerrar</button>
                <div style="display:flex; gap:.5rem;">
                    <button type="button" id="btnPdfDetalle" class="btn btn-primary">
                        <?= icon_tag('file-text', 'file-text', 'w-4 h-4') ?>
                        <span>PDF</span>
                    </button>
                    <button type="button" id="btnAnularDetalle" class="btn btn-danger">
                        <?= icon_tag('rotate-ccw', 'rotate-ccw', 'w-4 h-4') ?>
                        <span>Anular Costeo</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>