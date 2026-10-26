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
            Productos
            <span class="badge badge-info" style="font-size:.7rem; vertical-align:middle;"
                  title="<?= e($instNombreFull) ?>">
                <?= e($instNombreCorto) ?>
            </span>
        </h2>
        <p class="page-subtitle">Inventario del almacén</p>
    </div>
    <button id="btnNuevo" class="btn btn-primary">
        <?= icon_tag('plus', 'plus', 'w-4 h-4') ?>
        <span id="btnNuevoText">Nuevo Producto</span>
    </button>
</div>

<!-- ============================================ -->
<!-- SECCIÓN FORMULARIO (colapsable)               -->
<!-- ============================================ -->
<section id="seccionForm" class="card form-section collapsed">
    <header class="form-section-header" id="toggleForm">
        <div class="form-section-title">
            <span id="formSectionIconWrap">
                <?= icon_tag('package', 'package', 'w-5 h-5') ?>
            </span>
            <div>
                <h3 id="formSectionTitle">Nuevo Producto</h3>
                <p id="formSectionSubtitle" class="form-section-subtitle">
                    Completa los datos del producto
                </p>
            </div>
        </div>
        <button type="button" class="form-section-toggle" id="btnToggleForm" title="Mostrar/Ocultar">
            <?= icon_tag('chevron-down', 'chevron-down', 'w-5 h-5') ?>
        </button>
    </header>

    <div class="form-section-body">
        <form id="formProducto" novalidate>
            <input type="hidden" name="id_producto" id="id_producto">

            <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                    <label>Nombre del Artículo <span class="req">*</span></label>
                    <input type="text" name="producto" id="producto" maxlength="45"
                           autocomplete="off" data-uppercase placeholder="Ej: FIL- FILTRO DE ACEITE">
                    <span class="error" data-error="producto"></span>
                </div>

                <div class="form-group">
                    <label>Tipo / Enlace <span class="req">*</span></label>
                    <div id="wrapEnlace"></div>
                    <input type="hidden" name="id_enlace_producto" id="id_enlace_producto">
                    <span class="error" data-error="id_enlace_producto"></span>
                </div>

                <div class="form-group">
                    <label>Ubicación <span class="req">*</span></label>
                    <div id="wrapUbicacion"></div>
                    <input type="hidden" name="id_ubicacion" id="id_ubicacion">
                    <span class="error" data-error="id_ubicacion"></span>
                </div>

                <div class="form-group">
                    <label>Proveedor <span class="req">*</span></label>
                    <div id="wrapProveedor"></div>
                    <input type="hidden" name="id_proveedor" id="id_proveedor">
                    <span class="error" data-error="id_proveedor"></span>
                </div>

                <div class="form-group">
                    <label>Presentación</label>
                    <select name="present_producto" id="present_producto">
                        <option value="UNIDAD">Unidad</option>
                        <option value="LITRO">Litro</option>
                        <option value="JUEGO">Juego</option>
                        <option value="CAJA">Caja</option>
                        <option value="GALON">Galón</option>
                        <option value="KILO">Kilo</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tipo de Artículo</label>
                    <select name="tag_producto" id="tag_producto">
                        <option value="1">Consumible</option>
                        <option value="2">Repuesto</option>
                    </select>
                </div>

                <div class="form-group" id="cantidadGroup">
                    <label>Cantidad Inicial</label>
                    <input type="number" name="cant_producto" id="cant_producto" min="0" step="0.01" value="0">
                </div>
            </div>

            <div class="form-actions">
                <button type="button" id="btnCancelar" class="btn btn-ghost">
                    <?= icon_tag('x', 'x', 'w-4 h-4') ?>
                    <span>Cancelar</span>
                </button>
                <button type="submit" id="btnGuardar" class="btn btn-primary">
                    <?= icon_tag('save', 'save', 'w-4 h-4') ?>
                    <span class="btn-text">Guardar</span>
                </button>
            </div>
        </form>
    </div>
</section>

<!-- ============================================ -->
<!-- TABLA                                        -->
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

        <div class="dt-toolbar-right" style="gap:.5rem;">
            <div id="wrapFiltroEnlace" style="min-width:160px;"></div>
            <input type="hidden" id="filtroEnlace">

            <div id="wrapFiltroUbicacion" style="min-width:180px;"></div>
            <input type="hidden" id="filtroUbicacion">

            <select id="filtroStock" class="filter-select">
                <option value="">Todo el stock</option>
                <option value="sin">Sin stock</option>
                <option value="bajo">Stock bajo (&lt; 10)</option>
                <option value="ok">Con stock (≥ 10)</option>
            </select>
            <div class="filter-search" style="min-width:240px;">
                <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                <input type="text" id="filtroBuscar" placeholder="Buscar producto, proveedor...">
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Tipo</th>
                    <th>Proveedor</th>
                    <th>Ubicación</th>
                    <th class="text-center">Stock</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyProductos">
                <tr><td colspan="7" class="dt-loading">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="dt-footer">
        <div class="dt-info">
            Mostrando <strong id="startProductos">0</strong>–<strong id="endProductos">0</strong>
            de <strong id="totalProductos">0</strong> productos
        </div>
        <div class="dt-pagination" id="paginationProductos"></div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL · AGREGAR STOCK                         -->
<!-- ============================================ -->
<div id="modalStock" class="modal-backdrop">
    <div class="modal" style="max-width:460px;">
        <div class="modal-header">
            <h3>
                <?= icon_tag('plus-circle', 'plus-circle', 'w-4 h-4') ?>
                Agregar Stock
            </h3>
            <button type="button" class="modal-close" onclick="cerrarModalStock()">&times;</button>
        </div>
        <form id="formStock" style="padding:1.25rem;">
            <input type="hidden" id="stock_id_producto">
            <p style="font-size:.85rem; margin:0 0 1rem; color:#64748b;">
                Producto: <strong id="stock_producto_label"></strong>
            </p>
            <p style="font-size:.8rem; margin:0 0 1rem; color:#94a3b8;">
                Stock actual: <strong id="stock_actual_label">0</strong>
            </p>
            <div class="form-group">
                <label>Cantidad a agregar <span class="req">*</span></label>
                <input type="number" id="stock_cantidad" min="0.01" step="0.01" required
                       placeholder="Ej: 10">
            </div>
            <div class="modal-footer" style="margin-top:1rem; padding-top:1rem;">
                <button type="button" class="btn btn-ghost" onclick="cerrarModalStock()">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <?= icon_tag('plus', 'plus', 'w-4 h-4') ?>
                    <span>Agregar</span>
                </button>
            </div>
        </form>
    </div>
</div>