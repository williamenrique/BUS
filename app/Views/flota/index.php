<?php
// ============================================
// Institución activa
// ============================================
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
<!-- PAGE HEADER                                   -->
<!-- ============================================ -->
<div class="page-header">
    <div>
        <h2 class="page-title">
            Flota
            <span class="badge badge-info" style="font-size:.7rem; vertical-align:middle;"
                  title="<?= e($instNombreFull) ?>">
                <?= e($instNombreCorto) ?>
            </span>
        </h2>
        <p class="page-subtitle">Gestión de unidades de transporte</p>
    </div>
    <button id="btnNuevo" class="btn btn-primary">
        <?= icon_tag('plus', 'plus', 'w-4 h-4') ?>
        <span id="btnNuevoText">Nueva Unidad</span>
    </button>
</div>

<!-- ============================================ -->
<!-- SECCIÓN FORMULARIO (colapsable)               -->
<!-- ============================================ -->
<section id="seccionForm" class="card form-section collapsed">
    <header class="form-section-header" id="toggleForm">
        <div class="form-section-title">
            <span id="formSectionIconWrap">
                <?= icon_tag('bus', 'bus', 'w-5 h-5') ?>
            </span>
            <div>
                <h3 id="formSectionTitle">Nueva Unidad</h3>
                <p id="formSectionSubtitle" class="form-section-subtitle">
                    Completa los datos de la unidad
                </p>
            </div>
        </div>
        <button type="button" class="form-section-toggle" id="btnToggleForm" title="Mostrar/Ocultar">
            <?= icon_tag('chevron-down', 'chevron-down', 'w-5 h-5') ?>
        </button>
    </header>

    <div class="form-section-body" id="formSectionBody">
        <form id="formFlota" novalidate>
            <input type="hidden" name="id_flota" id="id_flota">

            <div class="form-grid">
                <div class="form-group">
                    <label>ID Unidad <span class="req">*</span></label>
                    <input type="text" name="id_unidad" id="id_unidad" maxlength="20"
                           autocomplete="off" data-uppercase>
                    <span class="error" data-error="id_unidad"></span>
                </div>
                <div class="form-group">
                    <label>VIN <span class="req">*</span></label>
                    <input type="text" name="vim_unidad" id="vim_unidad" maxlength="20"
                           autocomplete="off" data-uppercase>
                    <span class="error" data-error="vim_unidad"></span>
                </div>
                <div class="form-group">
                    <label>Marca <span class="req">*</span></label>
                    <div id="wrapMarca"></div>
                    <input type="hidden" name="id_marca" id="id_marca">
                    <span class="error" data-error="id_marca"></span>
                </div>
                <div class="form-group">
                    <label>Modelo <span class="req">*</span></label>
                    <div id="wrapModelo"></div>
                    <input type="hidden" name="id_modelo" id="id_modelo">
                    <span class="error" data-error="id_modelo"></span>
                </div>

                <div class="form-group">
                    <label>Año Fabricación</label>
                    <input type="number" name="fecha_creacion" id="fecha_creacion"
                           min="1900" max="2099" placeholder="YYYY">
                </div>
                <div class="form-group">
                    <label>Capacidad Pasajeros</label>
                    <input type="number" name="cap_pasajero" id="cap_pasajero" min="0" max="999">
                </div>
                <div class="form-group">
                    <label>Tipo Combustible</label>
                    <select name="tipo_combustible" id="tipo_combustible" data-uppercase>
                        <option value="DIESEL">DIESEL</option>
                        <option value="GASOLINA">GASOLINA</option>
                        <option value="GAS">GAS</option>
                        <option value="N/A">N/A</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Transmisión</label>
                    <select name="transmision" id="transmision" data-uppercase>
                        <option value="SINCRONICO">SINCRÓNICO</option>
                        <option value="AUTOMATICO">AUTOMÁTICO</option>
                        <option value="N/A">N/A</option>
                    </select>
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
<!-- REPORTE DE OPERATIVIDAD                       -->
<!-- ============================================ -->
<section id="seccionReporte" class="card form-section">
    <header class="form-section-header" id="toggleReporte">
        <div class="form-section-title">
            <span style="background:#fef3c7; color:#b45309;">
                <?= icon_tag('bar-chart-3', 'bar-chart-3', 'w-5 h-5') ?>
            </span>
            <div>
                <h3>Reporte de Operatividad</h3>
                <p class="form-section-subtitle">
                    Agrupa unidades y calcula operatividad en tiempo real
                </p>
            </div>
        </div>
        <button type="button" class="form-section-toggle" id="btnToggleReporte" title="Mostrar/Ocultar">
            <?= icon_tag('chevron-down', 'chevron-down', 'w-5 h-5') ?>
        </button>
    </header>

    <div class="form-section-body">
        <div class="reporte-grid">
            <!-- Columna de Filtros -->
            <div class="reporte-col">
                <div class="reporte-col-header">
                    <span class="reporte-num">1</span>
                    <h4>Seleccione Grupos</h4>
                </div>

                <div class="filter-search mb-2">
                    <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                    <input type="text" id="filtroGrupos" placeholder="Buscar por modelo, combustible...">
                </div>

                <div id="reporteFiltros" class="reporte-filtros">
                    <p class="text-muted text-center py-4">Cargando grupos...</p>
                </div>
            </div>

            <!-- Columna de Vista Previa -->
            <div class="reporte-col">
                <div class="reporte-col-header">
                    <span class="reporte-num">2</span>
                    <h4>Vista Previa</h4>
                </div>

                <div class="reporte-resumen">
                    <div class="reporte-counter green">
                        <span class="reporte-counter-label">Op.</span>
                        <strong id="totalOperativas">0</strong>
                    </div>
                    <div class="reporte-counter amber">
                        <span class="reporte-counter-label">Inop.</span>
                        <strong id="totalInoperativas">0</strong>
                    </div>
                    <div class="reporte-counter red">
                        <span class="reporte-counter-label">Crít.</span>
                        <strong id="totalCriticas">0</strong>
                    </div>
                    <div class="reporte-counter dark">
                        <span class="reporte-counter-label">Total</span>
                        <strong id="totalSeleccionadas">0</strong>
                    </div>
                </div>

                <div class="reporte-tabla-wrap">
                    <table class="data-table" id="tablaResumen" style="display:none;">
                        <thead>
                            <tr>
                                <th>Grupo</th>
                                <th class="text-center">Cant.</th>
                                <th class="text-center">Op.</th>
                                <th class="text-center">Inop.</th>
                                <th class="text-center">Crít.</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyResumen"></tbody>
                    </table>
                    <p id="placeholderReporte" class="reporte-placeholder">
                        Selecciona grupos para ver el resumen aquí
                    </p>
                </div>

                <div class="reporte-actions">
                    <button type="button" class="btn btn-ghost btn-sm" id="btnLimpiarReporte">
                        <?= icon_tag('x', 'x', 'w-4 h-4') ?>
                        <span>Limpiar</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnGenerarPDF" disabled>
                        <?= icon_tag('file-text', 'file-text', 'w-4 h-4') ?>
                        <span>Generar PDF</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- TABLA PRINCIPAL                              -->
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
            <select id="filtroStatus" class="filter-select">
                <option value="">Todos los estatus</option>
                <option value="1">Operativas</option>
                <option value="2">Inoperativas</option>
                <option value="3">Mantenimiento</option>
                <option value="4">Por Desincorporar</option>
                <option value="5">Crítica</option>
                <option value="0">Desincorporada</option>
            </select>

            <div id="wrapFiltroMarca" style="min-width:180px;"></div>
            <input type="hidden" id="filtroMarca">

            <div class="filter-search" style="min-width:240px;">
                <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                <input type="text" id="filtroBuscar" placeholder="Buscar ID, VIN, marca...">
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID Unidad</th>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>VIN</th>
                    <th>Combustible</th>
                    <th>Transmisión</th>
                    <th class="text-center">Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyFlota">
                <tr><td colspan="8" class="dt-loading">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="dt-footer">
        <div class="dt-info">
            Mostrando <strong id="startFlota">0</strong>–<strong id="endFlota">0</strong>
            de <strong id="totalFlota">0</strong> registros
        </div>
        <div class="dt-pagination" id="paginationFlota"></div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL GENÉRICO                                -->
<!-- ============================================ -->
<div id="modalGenerico" class="modal-backdrop">
    <div class="modal" style="max-width:520px;">
        <div class="modal-header">
            <h3 id="modalGenericoTitle">Acción</h3>
            <button type="button" class="modal-close" onclick="cerrarModalGenerico()">&times;</button>
        </div>
        <div class="modal-body" id="modalGenericoBody" style="padding:1.5rem;"></div>
        <div class="modal-footer" style="padding:1rem 1.5rem;">
            <button type="button" class="btn btn-ghost" onclick="cerrarModalGenerico()">Cancelar</button>
            <button type="button" class="btn btn-primary" id="modalGenericoOk">Confirmar</button>
        </div>
    </div>
</div>

<script>
    window.SITGO_BASE = "<?= BASE_URL ?>";
    window.SITGO_INST = <?= (int)($instId ?? 1) ?>;
</script>