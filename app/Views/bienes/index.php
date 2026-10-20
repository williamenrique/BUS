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
            Bienes
            <span class="badge badge-info" style="font-size:.7rem; vertical-align:middle;"
                  title="<?= e($instNombreFull) ?>">
                <?= e($instNombreCorto) ?>
            </span>
        </h2>
        <p class="page-subtitle">Inventario de bienes de la institución</p>
    </div>
    <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
        <button id="btnPdfBienes" class="btn btn-secondary" title="Generar PDF con los filtros actuales">
            <?= icon_tag('file-text', 'file-text', 'w-4 h-4') ?>
            <span>PDF General</span>
        </button>
        <button id="btnPdfBienesDepto" class="btn btn-secondary" title="Generar PDF solo del departamento seleccionado">
            <?= icon_tag('building-2', 'building-2', 'w-4 h-4') ?>
            <span>PDF Departamento</span>
        </button>
        <button id="btnNuevo" class="btn btn-primary">
            <?= icon_tag('plus', 'plus', 'w-4 h-4') ?>
            <span id="btnNuevoText">Nuevo Bien</span>
        </button>
    </div>
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
                <h3 id="formSectionTitle">Nuevo Bien</h3>
                <p id="formSectionSubtitle" class="form-section-subtitle">
                    Completa los datos del bien
                </p>
            </div>
        </div>
        <button type="button" class="form-section-toggle" id="btnToggleForm" title="Mostrar/Ocultar">
            <?= icon_tag('chevron-down', 'chevron-down', 'w-5 h-5') ?>
        </button>
    </header>

    <div class="form-section-body">
        <form id="formBien" novalidate>
            <input type="hidden" name="id_bien" id="id_bien">

            <div class="form-grid">
                <div class="form-group">
                    <label>Departamento <span class="req">*</span></label>
                    <select name="bien_depatamento_id" id="bien_depatamento_id"></select>
                    <span class="error" data-error="bien_depatamento_id"></span>
                </div>

                <div class="form-group">
                    <label>Grupo</label>
                    <select name="grupo_id" id="grupo_id"></select>
                </div>

                <div class="form-group">
                    <label>Subgrupo</label>
                    <select name="subgrupo_id" id="subgrupo_id"></select>
                </div>

                <div class="form-group">
                    <label>Sección</label>
                    <select name="seccion_id" id="seccion_id"></select>
                </div>

                <div class="form-group">
                    <label>Fecha de adquisición</label>
                    <input type="date" name="fecha_adquisicion" id="fecha_adquisicion">
                </div>

                <div class="form-group">
                    <label>Estatus</label>
                    <select name="status_bien" id="status_bien">
                        <option value="1">Activo</option>
                        <option value="2">Inactivo</option>
                        <option value="3">En reparación</option>
                        <option value="4">Dado de baja</option>
                        <option value="EN USO">En uso</option>
                        <option value="DAÑADO">Dañado</option>
                        <option value="EXTRAVIADO">Extraviado</option>
                        <option value="FALTANTE POR UBICAR">Faltante por ubicar</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label>Descripción <span class="req">*</span></label>
                    <textarea name="descripcion_bien" id="descripcion_bien" rows="2"
                              data-uppercase placeholder="Descripción detallada del bien..."></textarea>
                    <span class="error" data-error="descripcion_bien"></span>
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

        <div class="dt-toolbar-right">
            <select id="filtroDepartamento" class="filter-select">
                <option value="">Todos los departamentos</option>
            </select>
            <select id="filtroGrupo" class="filter-select">
                <option value="">Todos los grupos</option>
            </select>
            <select id="filtroStatus" class="filter-select">
                <option value="">Todos los estatus</option>
                <option value="1">Activo</option>
                <option value="2">Inactivo</option>
                <option value="3">En reparación</option>
                <option value="4">Dado de baja</option>
                <option value="EN USO">En uso</option>
                <option value="DAÑADO">Dañado</option>
                <option value="EXTRAVIADO">Extraviado</option>
                <option value="FALTANTE POR UBICAR">Faltante por ubicar</option>
            </select>
            <div class="filter-search" style="min-width:240px;">
                <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                <input type="text" id="filtroBuscar" placeholder="Buscar descripción, grupo...">
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th>Departamento</th>
                    <th>Grupo</th>
                    <th>Subgrupo</th>
                    <th>Sección</th>
                    <th>Adquisición</th>
                    <th class="text-center">Estatus</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyBienes">
                <tr><td colspan="9" class="dt-loading">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="dt-footer">
        <div class="dt-info">
            Mostrando <strong id="startBienes">0</strong>–<strong id="endBienes">0</strong>
            de <strong id="totalBienes">0</strong> bienes
        </div>
        <div class="dt-pagination" id="paginationBienes"></div>
    </div>
</div>