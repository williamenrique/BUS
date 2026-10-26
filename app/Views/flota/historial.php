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

<div class="page-header">
    <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
        <a href="<?= base_url('flota/i/' . (int)$instId) ?>" class="btn btn-ghost">
            <?= icon_tag('arrow-left', 'arrow-left', 'w-4 h-4') ?>
            <span>Volver</span>
        </a>
        <div>
            <h2 class="page-title" style="margin:0;">
                <?= e($unidad['id_unidad']) ?>
                <span class="badge badge-info" style="font-size:.7rem; vertical-align:middle;"
                      title="<?= e($instNombreFull) ?>">
                    <?= e($instNombreCorto) ?>
                </span>
            </h2>
            <p class="page-subtitle" style="margin:.15rem 0 0;">
                Historial completo de la unidad
            </p>
        </div>
    </div>
    <button id="btnGenerarPdfHistorial" class="btn btn-primary">
        <?= icon_tag('file-text', 'file-text', 'w-4 h-4') ?>
        <span>Generar PDF</span>
    </button>
</div>

<div class="unidad-info">
    <div class="unidad-info-item">
        <span class="unidad-info-icon">
            <?= icon_tag('tag', 'tag', 'w-5 h-5') ?>
        </span>
        <div>
            <span class="unidad-info-label">Unidad</span>
            <strong><?= e($unidad['id_unidad']) ?></strong>
        </div>
    </div>
    <div class="unidad-info-item">
        <span class="unidad-info-icon">
            <?= icon_tag('badge-check', 'badge-check', 'w-5 h-5') ?>
        </span>
        <div>
            <span class="unidad-info-label">Marca</span>
            <strong><?= e($unidad['marca_unidad'] ?? '—') ?></strong>
        </div>
    </div>
    <div class="unidad-info-item">
        <span class="unidad-info-icon">
            <?= icon_tag('car', 'car', 'w-5 h-5') ?>
        </span>
        <div>
            <span class="unidad-info-label">Modelo</span>
            <strong><?= e($unidad['modelo_unidad'] ?? '—') ?></strong>
        </div>
    </div>
    <div class="unidad-info-item">
        <span class="unidad-info-icon">
            <?= icon_tag('hash', 'hash', 'w-5 h-5') ?>
        </span>
        <div>
            <span class="unidad-info-label">VIN</span>
            <strong><?= e($unidad['vim_unidad'] ?? '—') ?></strong>
        </div>
    </div>
    <div class="unidad-info-item">
        <span class="unidad-info-icon">
            <?= icon_tag('calendar', 'calendar', 'w-5 h-5') ?>
        </span>
        <div>
            <span class="unidad-info-label">Año</span>
            <strong><?= e($unidad['fecha_creacion'] ?: '—') ?></strong>
        </div>
    </div>
    <div class="unidad-info-item">
        <span class="unidad-info-icon">
            <?= icon_tag('users', 'users', 'w-5 h-5') ?>
        </span>
        <div>
            <span class="unidad-info-label">Capacidad</span>
            <strong><?= e($unidad['cap_pasajero'] ?: '—') ?> pasajeros</strong>
        </div>
    </div>
    <div class="unidad-info-item">
        <span class="unidad-info-icon">
            <?= icon_tag('fuel', 'fuel', 'w-5 h-5') ?>
        </span>
        <div>
            <span class="unidad-info-label">Combustible</span>
            <strong><?= e($unidad['tipo_combustible'] ?: '—') ?></strong>
        </div>
    </div>
    <div class="unidad-info-item">
        <span class="unidad-info-icon">
            <?= icon_tag('settings-2', 'settings-2', 'w-5 h-5') ?>
        </span>
        <div>
            <span class="unidad-info-label">Transmisión</span>
            <strong><?= e($unidad['transmision'] ?: '—') ?></strong>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form id="formFiltrosHistorial">
            <div class="historial-filtros">
                <div class="historial-filtros-row">
                    <div class="form-group">
                        <label>Desde</label>
                        <input type="date" name="desde" id="hf_desde">
                    </div>
                    <div class="form-group">
                        <label>Hasta</label>
                        <input type="date" name="hasta" id="hf_hasta">
                    </div>
                    <div class="form-group">
                        <label>Tipo de evento</label>
                        <select name="tipo" id="hf_tipo">
                            <option value="">Todos</option>
                            <option value="despacho">Despachos</option>
                            <option value="aceite">Cambios de aceite</option>
                            <option value="mantenimiento">Mantenimientos</option>
                            <option value="status">Cambios de estado</option>
                        </select>
                    </div>
                    <div class="form-group historial-filtros-search">
                        <label>Buscar</label>
                        <div class="filter-search">
                            <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                            <input type="text" name="q" id="hf_q" placeholder="Nº de orden, motivo, mecánico...">
                        </div>
                    </div>
                </div>

                <div class="historial-filtros-actions">
                    <button type="button" id="btnLimpiarFiltros" class="btn btn-ghost">
                        <?= icon_tag('x', 'x', 'w-4 h-4') ?>
                        <span>Limpiar</span>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <?= icon_tag('search', 'search', 'w-4 h-4') ?>
                        <span>Buscar</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="historial-resumen" id="historialResumen" style="display:none;">
    <div class="historial-counter blue">
        <span class="historial-counter-label">Despachos</span>
        <strong id="cntDespacho">0</strong>
    </div>
    <div class="historial-counter amber">
        <span class="historial-counter-label">Aceite</span>
        <strong id="cntAceite">0</strong>
    </div>
    <div class="historial-counter green">
        <span class="historial-counter-label">Mantenimiento</span>
        <strong id="cntMantenimiento">0</strong>
    </div>
    <div class="historial-counter purple">
        <span class="historial-counter-label">Estados</span>
        <strong id="cntStatus">0</strong>
    </div>
    <div class="historial-counter dark">
        <span class="historial-counter-label">Total</span>
        <strong id="cntTotal">0</strong>
    </div>
</div>

<div id="timelineWrap">
    <div id="timelineContainer">
        <div class="historial-loading">
            <?= icon_tag('loader-2', 'loader-2', 'w-8 h-8 mx-auto text-blue-600 animate-spin') ?>
            <p>Cargando historial...</p>
        </div>
    </div>
</div>

<div class="dt-footer" id="paginationWrap" style="display:none;">
    <div class="dt-info">
        Mostrando <strong id="startHistorial">0</strong>–<strong id="endHistorial">0</strong>
        de <strong id="totalHistorial">0</strong> eventos
    </div>
    <div class="dt-pagination" id="paginationHistorial"></div>
</div>

<script>
    window.SITGO_BASE     = "<?= BASE_URL ?>";
    window.SITGO_INST     = <?= (int)($instId ?? 1) ?>;
    window.SITGO_ID_FLOTA = <?= (int)$idFlota ?>;
    window.SITGO_UNIDAD_DATA = {
        id_unidad:     "<?= e($unidad['id_unidad'] ?? '') ?>",
        marca:         "<?= e($unidad['marca_unidad'] ?? '') ?>",
        modelo:        "<?= e($unidad['modelo_unidad'] ?? '') ?>",
        vin:           "<?= e($unidad['vim_unidad'] ?? '') ?>",
        anio:          "<?= e($unidad['fecha_creacion'] ?? '') ?>",
        capacidad:     "<?= e($unidad['cap_pasajero'] ?? '') ?>",
        combustible:   "<?= e($unidad['tipo_combustible'] ?? '') ?>",
        transmision:   "<?= e($unidad['transmision'] ?? '') ?>"
    };
</script>