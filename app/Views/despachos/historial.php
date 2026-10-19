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

<!-- ============================================ -->
<!-- VISTA 1: LISTA DE PRODUCTOS                   -->
<!-- ============================================ -->
<div id="vistaResumen">

    <div class="page-header">
        <div>
            <h2 class="page-title">
                Historial de Productos
                <span class="badge badge-info" style="font-size:.7rem; vertical-align:middle;"
                      title="<?= e($instNombreFull) ?>">
                    <?= e($instNombreCorto) ?>
                </span>
            </h2>
            <p class="page-subtitle">
                Resumen de stock y despachos · Haz clic en el producto para ver su trayectoria
            </p>
        </div>
        <a href="<?= base_url('despachos/i/' . (int)$instId) ?>" class="btn btn-ghost">
            <?= icon_tag('arrow-left', 'arrow-left', 'w-4 h-4') ?>
            <span>Volver a Despachos</span>
        </a>
    </div>

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

            <div class="dt-toolbar-right">
                <div class="filter-search" style="min-width:280px;">
                    <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                    <input type="text" id="filtroBuscar" placeholder="Buscar producto, tipo, ubicación...">
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th>Artículo</th>
                        <th class="text-right">Stock Actual</th>
                        <th class="text-right">Total Despachado</th>
                        <th>Primer Despacho</th>
                        <th>Último Despacho</th>
                        <th class="text-right" style="width:100px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyHistorial">
                    <tr><td colspan="7" class="dt-loading">Cargando...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="dt-footer">
            <div class="dt-info">
                Mostrando <strong id="startHistorial">0</strong>–<strong id="endHistorial">0</strong>
                de <strong id="totalHistorial">0</strong> productos
            </div>
            <div class="dt-pagination" id="paginationHistorial"></div>
        </div>
    </div>

</div>

<!-- ============================================ -->
<!-- VISTA 2: TIMELINE DE UN PRODUCTO              -->
<!-- ============================================ -->
<div id="vistaTimeline" style="display:none;">

    <div class="page-header">
        <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
            <button type="button" id="btnVolverResumen" class="btn btn-ghost">
                <?= icon_tag('arrow-left', 'arrow-left', 'w-4 h-4') ?>
                <span>Volver</span>
            </button>
            <div>
                <h2 class="page-title" style="margin:0;" id="timelineTitle">Cargando...</h2>
                <p class="page-subtitle" style="margin:.15rem 0 0;">
                    Trayectoria completa de despachos
                </p>
            </div>
        </div>
    </div>

    <!-- Info del producto -->
    <div class="historial-producto-info" id="timelineProductoInfo">
        <!-- Se llena por JS -->
    </div>

    <!-- Timeline -->
    <div id="timelineWrap">
        <div id="timelineContainer">
            <div class="historial-loading">
                <?= icon_tag('loader-2', 'loader-2', 'w-8 h-8 mx-auto text-blue-600 animate-spin') ?>
                <p>Cargando historial...</p>
            </div>
        </div>
    </div>

    <!-- Paginación -->
    <div class="dt-footer" id="timelinePaginationWrap" style="display:none;">
        <div class="dt-info">
            Mostrando <strong id="startTimeline">0</strong>–<strong id="endTimeline">0</strong>
            de <strong id="totalTimeline">0</strong> despachos
        </div>
        <div class="dt-pagination" id="paginationTimeline"></div>
    </div>

</div>