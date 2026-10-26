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
        <button id="btnQrBienes" class="btn btn-secondary" title="Generar QR del departamento seleccionado en el filtro">
            <?= icon_tag('qr-code', 'qr-code', 'w-4 h-4') ?>
            <span>Generar QR</span>
        </button>
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
                    <div id="wrapDepto"></div>
                    <input type="hidden" name="bien_depatamento_id" id="bien_depatamento_id">
                    <span class="error" data-error="bien_depatamento_id"></span>
                </div>

                <div class="form-group">
                    <label>Grupo</label>
                    <div id="wrapGrupo"></div>
                    <input type="hidden" name="grupo_id" id="grupo_id">
                </div>

                <div class="form-group">
                    <label>Subgrupo</label>
                    <div id="wrapSubgrupo"></div>
                    <input type="hidden" name="subgrupo_id" id="subgrupo_id">
                </div>

                <div class="form-group">
                    <label>Sección</label>
                    <div id="wrapSeccion"></div>
                    <input type="hidden" name="seccion_id" id="seccion_id">
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
<!-- PANEL QR (oculto por defecto)                 -->
<!-- ============================================ -->
<section id="panelQr" class="card" style="display:none; margin-top:1rem;">
    <div class="card-body" style="display:flex; align-items:center; gap:1.5rem; flex-wrap:wrap;">
        <div id="qrCanvasWrap" style="background:#fff; padding:.75rem; border:1px solid #e2e8f0; border-radius:.5rem;">
            <div id="qrCanvas"></div>
        </div>
        <div style="flex:1; min-width:220px;">
            <h4 style="margin:0 0 .35rem; font-size:.95rem; color:#0f172a;">
                QR · <span id="qrDeptoNombre">—</span>
            </h4>
            <p style="margin:0 0 .75rem; font-size:.8rem; color:#64748b; line-height:1.5;">
                Escanea este código para abrir la vista pública de bienes
                del departamento seleccionado.
            </p>
            <code id="qrUrl" style="display:block; font-size:.7rem; color:#475569; background:#f8fafc;
                  border:1px solid #e2e8f0; border-radius:.35rem; padding:.5rem .65rem;
                  word-break:break-all; margin-bottom:.75rem;">—</code>
            <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
                <a id="qrDescargar" class="btn btn-primary btn-sm" download>
                    <?= icon_tag('download', 'download', 'w-4 h-4') ?>
                    <span>Descargar PNG</span>
                </a>
                <a id="qrAbrir" class="btn btn-ghost btn-sm" target="_blank" rel="noopener">
                    <?= icon_tag('external-link', 'external-link', 'w-4 h-4') ?>
                    <span>Abrir vista pública</span>
                </a>
                <button type="button" id="qrCerrar" class="btn btn-ghost btn-sm">
                    <?= icon_tag('x', 'x', 'w-4 h-4') ?>
                    <span>Cerrar</span>
                </button>
            </div>
        </div>
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
            <div id="wrapFiltroDepto" style="min-width:200px;"></div>
            <input type="hidden" id="filtroDepartamento">

            <div id="wrapFiltroGrupo" style="min-width:180px;"></div>
            <input type="hidden" id="filtroGrupo">

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