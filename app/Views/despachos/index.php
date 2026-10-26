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
            Despachos
            <span class="badge badge-info" style="font-size:.7rem; vertical-align:middle;"
                  title="<?= e($instNombreFull) ?>">
                <?= e($instNombreCorto) ?>
            </span>
        </h2>
        <p class="page-subtitle">Órdenes de despacho de almacén</p>
    </div>
    <button id="btnNuevo" class="btn btn-primary">
        <?= icon_tag('plus', 'plus', 'w-4 h-4') ?>
        <span id="btnNuevoText">Nueva Orden</span>
    </button>
</div>

<!-- Progress bar -->
<div class="card mb-4">
    <div class="card-body" style="padding:1rem 1.25rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.5rem;">
            <span style="font-weight:600; font-size:.85rem;">Progreso de órdenes este mes</span>
            <span id="progressPercentage" style="font-weight:700; color:#2563eb;">0%</span>
        </div>
        <div style="height:8px; background:#e2e8f0; border-radius:999px; overflow:hidden;">
            <div id="progressBarFill" style="height:100%; width:0%; background:linear-gradient(90deg,#2563eb,#0ea5e9); transition:width .4s ease;"></div>
        </div>
        <div style="text-align:center; margin-top:.35rem;">
            <span id="progressCurrent" style="font-size:.75rem; color:#64748b;">0 órdenes</span>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- SECCIÓN FORMULARIO (colapsable)               -->
<!-- ============================================ -->
<section id="seccionForm" class="card form-section collapsed">
    <header class="form-section-header" id="toggleForm">
        <div class="form-section-title">
            <span id="formSectionIconWrap">
                <?= icon_tag('truck', 'truck', 'w-5 h-5') ?>
            </span>
            <div>
                <h3 id="formSectionTitle">Nueva Orden de Despacho</h3>
                <p id="formSectionSubtitle" class="form-section-subtitle">
                    Selecciona la unidad, personal y artículos
                </p>
            </div>
        </div>
        <button type="button" class="form-section-toggle" id="btnToggleForm" title="Mostrar/Ocultar">
            <?= icon_tag('chevron-down', 'chevron-down', 'w-5 h-5') ?>
        </button>
    </header>

    <div class="form-section-body">
        <form id="formDespacho" novalidate>
            <input type="hidden" name="id_despacho" id="id_despacho">

            <!-- Info básica -->
            <div class="form-grid">
                <div class="form-group">
                    <label>Unidad <span class="req">*</span></label>
                    <div id="wrapFlota"></div>
                    <input type="hidden" name="id_flota" id="id_flota">
                    <span class="error" data-error="id_flota"></span>
                </div>

                <div class="form-group">
                    <label>Fecha <span class="req">*</span></label>
                    <input type="date" name="fecha_despacho" id="fecha_despacho">
                    <span class="error" data-error="fecha_despacho"></span>
                </div>

                <div class="form-group">
                    <label>Operador <span class="req">*</span></label>
                    <div id="wrapOperador"></div>
                    <input type="hidden" name="operador_id" id="operador_id">
                    <span class="error" data-error="operador_id"></span>
                </div>

                <div class="form-group">
                    <label>Mecánico <span class="req">*</span></label>
                    <div id="wrapMecanico"></div>
                    <input type="hidden" name="mecanico_id" id="mecanico_id">
                    <span class="error" data-error="mecanico_id"></span>
                </div>

                <div class="form-group">
                    <label>Despachador <span class="req">*</span></label>
                    <div id="wrapDespachador"></div>
                    <input type="hidden" name="despachador_id" id="despachador_id">
                    <span class="error" data-error="despachador_id"></span>
                </div>

                <div class="form-group full">
                    <label>Observaciones</label>
                    <textarea name="observacion" id="observacion" rows="2" data-uppercase
                              placeholder="Observaciones adicionales..."></textarea>
                </div>
            </div>

            <!-- Info unidad -->
            <div id="unidadInfo" class="despacho-unidad-info" style="display:none;">
                <div class="despacho-unidad-item">
                    <span class="despacho-unidad-label">ID Unidad</span>
                    <strong id="info_id_unidad">—</strong>
                </div>
                <div class="despacho-unidad-item">
                    <span class="despacho-unidad-label">VIN</span>
                    <strong id="info_vim_unidad">—</strong>
                </div>
                <div class="despacho-unidad-item">
                    <span class="despacho-unidad-label">Marca</span>
                    <strong id="info_marca">—</strong>
                </div>
                <div class="despacho-unidad-item">
                    <span class="despacho-unidad-label">Modelo</span>
                    <strong id="info_modelo">—</strong>
                </div>
            </div>

            <!-- Artículos -->
            <div class="despacho-articulos-head">
                <h4>
                    <?= icon_tag('package', 'package', 'w-4 h-4') ?>
                    Artículos a despachar
                </h4>
            </div>

            <div class="despacho-articulo-form">
                <div class="form-group" style="flex:2;">
                    <label>Artículo</label>
                    <div id="wrapArticulo"></div>
                    <input type="hidden" id="sel_articulo">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Cantidad</label>
                    <div class="despacho-cant-wrap">
                        <input type="number" id="inp_cantidad" min="0.01" step="0.01" placeholder="0">
                        <button type="button" id="btnAddArticulo" class="btn btn-primary">
                            <?= icon_tag('plus', 'plus', 'w-4 h-4') ?>
                        </button>
                    </div>
                    <small class="text-muted" style="font-size:.7rem;">
                        Disponible: <strong id="stock_disponible">0</strong>
                    </small>
                </div>
            </div>

            <div class="table-wrap" style="margin-top:.75rem;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Artículo</th>
                            <th class="text-right">Cantidad</th>
                            <th class="text-right" style="width:60px;"></th>
                        </tr>
                    </thead>
                    <tbody id="tbodyArticulos">
                        <tr><td colspan="4" class="dt-empty">No hay artículos agregados</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="form-actions">
                <button type="button" id="btnCancelar" class="btn btn-ghost">
                    <?= icon_tag('x', 'x', 'w-4 h-4') ?>
                    <span>Cancelar</span>
                </button>
                <button type="submit" id="btnGuardar" class="btn btn-primary">
                    <?= icon_tag('save', 'save', 'w-4 h-4') ?>
                    <span class="btn-text">Generar Orden</span>
                </button>
            </div>
        </form>
    </div>
</section>

<!-- ============================================ -->
<!-- TABLA DE DESPACHOS                            -->
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

        <div class="dt-toolbar-right">
            <input type="date" id="filtroFecha" class="filter-select" style="padding:.55rem .75rem;">
            <div class="filter-search" style="min-width:240px;">
                <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                <input type="text" id="filtroBuscar" placeholder="Buscar orden, unidad...">
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
                    <th>Operador</th>
                    <th>Artículos</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyDespachos">
                <tr><td colspan="6" class="dt-loading">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="dt-footer">
        <div class="dt-info">
            Mostrando <strong id="startDespachos">0</strong>–<strong id="endDespachos">0</strong>
            de <strong id="totalDespachos">0</strong> órdenes
        </div>
        <div class="dt-pagination" id="paginationDespachos"></div>
    </div>
</div>
<!-- ============================================ -->
<!-- MODAL · VER DETALLE                           -->
<!-- ============================================ -->
<div id="modalDetalle" class="modal-backdrop" style="display:none;">
    <div class="modal" style="max-width:720px; background:#fff; border-radius:14px; box-shadow:0 25px 50px rgba(0,0,0,.25);">
        <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; padding:1.25rem 1.5rem; border-bottom:1px solid #e2e8f0;">
            <h3 id="modalDetalleTitle" style="margin:0; font-size:1.125rem; font-weight:700; color:#0f172a;">Detalle de Despacho</h3>
            <button type="button" class="modal-close" onclick="cerrarModalDespacho('modalDetalle')" style="background:none; border:none; font-size:1.5rem; line-height:1; color:#94a3b8; cursor:pointer;">&times;</button>
        </div>

        <div id="modalDetalleBody" style="padding:1.5rem; max-height:60vh; overflow-y:auto;"></div>

        <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:.75rem; padding:1rem 1.5rem; border-top:1px solid #e2e8f0;">
            <button type="button" class="btn btn-ghost" onclick="cerrarModalDespacho('modalDetalle')">Cerrar</button>
            <button type="button" class="btn btn-primary" id="btnImprimirPdfDetalle">
                <span>Imprimir PDF</span>
            </button>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL · ELIMINAR                              -->
<!-- ============================================ -->
<div id="modalEliminar" class="modal-backdrop">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <h3>Eliminar Orden</h3>
            <button type="button" class="modal-close" onclick="cerrarModalDespacho('modalEliminar')">&times;</button>
        </div>
        <form id="formEliminar" style="padding:1.5rem;">
            <input type="hidden" id="del_id_despacho">
            <p style="font-size:.85rem; color:#334155; margin:0 0 1rem;">
                Orden: <strong id="del_numero_orden"></strong>
            </p>
            <p style="font-size:.8rem; color:#dc2626; margin:0 0 1rem;">
                Se revertirán los artículos al stock. Esta acción no se puede deshacer.
            </p>
            <div class="form-group">
                <label>Motivo <span class="req">*</span></label>
                <textarea id="del_motivo" rows="3" required placeholder="Describe el motivo..."></textarea>
            </div>
            <div class="modal-footer" style="margin-top:1rem; padding-top:1rem;">
                <button type="button" class="btn btn-ghost" onclick="cerrarModalDespacho('modalEliminar')">Cancelar</button>
                <button type="submit" class="btn btn-danger">Eliminar y revertir</button>
            </div>
        </form>
    </div>
</div>