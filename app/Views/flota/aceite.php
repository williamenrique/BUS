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
<!-- PAGE HEADER                                   -->
<!-- ============================================ -->
<div class="page-header">
    <div>
        <h2 class="page-title">
            Monitoreo de Cambio de Aceite
            <span class="badge badge-info" style="font-size:.7rem; vertical-align:middle;"
                  title="<?= e($instNombreFull) ?>">
                <?= e($instNombreCorto) ?>
            </span>
        </h2>
        <p class="page-subtitle">Estado de aceite de todas las unidades activas</p>
    </div>
    <a href="<?= base_url('flota/i/' . (int)$instId) ?>" class="btn btn-ghost">
        <?= icon_tag('arrow-left', 'arrow-left', 'w-4 h-4') ?>
        <span>Volver a Flota</span>
    </a>
</div>

<!-- ============================================ -->
<!-- CARDS RESUMEN (clickeables)                   -->
<!-- ============================================ -->
<div class="aceite-cards" id="aceiteCards">
    <button type="button" class="aceite-card red" data-status="Requerido">
        <div class="aceite-card-icon">
            <?= icon_tag('alert-triangle', 'alert-triangle', 'w-6 h-6') ?>
        </div>
        <div>
            <strong id="cntRequerido">0</strong>
            <span>Cambio Requerido</span>
        </div>
    </button>

    <button type="button" class="aceite-card amber" data-status="Próximo">
        <div class="aceite-card-icon">
            <?= icon_tag('clock', 'clock', 'w-6 h-6') ?>
        </div>
        <div>
            <strong id="cntProximo">0</strong>
            <span>Cambio Próximo</span>
        </div>
    </button>

    <button type="button" class="aceite-card green" data-status="Bien">
        <div class="aceite-card-icon">
            <?= icon_tag('check-circle', 'check-circle', 'w-6 h-6') ?>
        </div>
        <div>
            <strong id="cntBien">0</strong>
            <span>Estado Óptimo</span>
        </div>
    </button>

    <button type="button" class="aceite-card slate" data-status="Sin Registro">
        <div class="aceite-card-icon">
            <?= icon_tag('help-circle', 'help-circle', 'w-6 h-6') ?>
        </div>
        <div>
            <strong id="cntSinRegistro">0</strong>
            <span>Sin Registro</span>
        </div>
    </button>
</div>

<!-- ============================================ -->
<!-- TABLA                                        -->
<!-- ============================================ -->
<div class="card">
    <div class="dt-toolbar">
        <div class="dt-toolbar-left">
            <span class="dt-toolbar-label">Mostrar</span>
            <select id="filtroLimit" class="filter-select" style="min-width:70px;">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="dt-toolbar-label">registros</span>
        </div>

        <div class="dt-toolbar-right">
            <select id="filtroEstado" class="filter-select">
                <option value="">Todos los estados</option>
                <option value="Requerido">Requerido</option>
                <option value="Próximo">Próximo</option>
                <option value="Bien">Bien</option>
                <option value="Sin Registro">Sin Registro</option>
            </select>

            <div class="filter-search" style="min-width:240px;">
                <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                <input type="text" id="filtroBuscar" placeholder="Buscar unidad, marca, modelo...">
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Unidad</th>
                    <th class="text-right">KM Actual</th>
                    <th class="text-right">Último Cambio</th>
                    <th>Fecha Últ. Cambio</th>
                    <th class="text-right">Próximo</th>
                    <th class="text-right">Restantes</th>
                    <th class="text-center">Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyAceite">
                <tr><td colspan="8" class="dt-loading">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="dt-footer">
        <div class="dt-info">
            Mostrando <strong id="startAceite">0</strong>–<strong id="endAceite">0</strong>
            de <strong id="totalAceite">0</strong> unidades
        </div>
        <div class="dt-pagination" id="paginationAceite"></div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL · Actualizar kilometraje                -->
<!-- ============================================ -->
<div id="modalKm" class="modal-backdrop">
    <div class="modal" style="max-width:440px;">
        <div class="modal-header">
            <h3>Actualizar Kilometraje</h3>
            <button type="button" class="modal-close" onclick="cerrarModalAceite('modalKm')">&times;</button>
        </div>
        <form id="formKm" style="padding:1.25rem;">
            <input type="hidden" id="km_id_flota" name="id_flota">
            <p style="font-size:.85rem; margin:0 0 1rem; color:#64748b;">
                Unidad: <strong id="km_unidad_label"></strong>
            </p>
            <div class="form-group">
                <label>Kilometraje actual</label>
                <input type="number" name="kilometraje_actual" id="km_valor" min="0" required>
            </div>
            <div class="modal-footer" style="margin-top:1rem; padding-top:1rem;">
                <button type="button" class="btn btn-ghost" onclick="cerrarModalAceite('modalKm')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL · Registrar cambio de aceite            -->
<!-- ============================================ -->
<div id="modalCambio" class="modal-backdrop">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <h3>Registrar Cambio de Aceite</h3>
            <button type="button" class="modal-close" onclick="cerrarModalAceite('modalCambio')">&times;</button>
        </div>
        <form id="formCambio" style="padding:1.25rem;">
            <input type="hidden" id="cambio_id_flota" name="id_flota">
            <input type="hidden" id="cambio_km_anterior" name="kilometraje_anterior">
            <p style="font-size:.85rem; margin:0 0 1rem; color:#64748b;">
                Unidad: <strong id="cambio_unidad_label"></strong>
            </p>

            <div class="form-group">
                <label>Fecha del cambio <span class="req">*</span></label>
                <input type="date" name="fecha_cambio" id="cambio_fecha" required>
            </div>
            <div class="form-group">
                <label>Kilometraje del cambio <span class="req">*</span></label>
                <input type="number" name="kilometraje_cambio" id="cambio_km" min="0" required>
            </div>
            <p style="font-size:.75rem; color:#94a3b8; margin:.5rem 0 0;">
                El próximo cambio se calculará automáticamente a 5,000 km.
            </p>
            <div class="modal-footer" style="margin-top:1rem; padding-top:1rem;">
                <button type="button" class="btn btn-ghost" onclick="cerrarModalAceite('modalCambio')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Registrar</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL · Eliminar registro                    -->
<!-- ============================================ -->
<div id="modalEliminar" class="modal-backdrop">
    <div class="modal" style="max-width:460px;">
        <div class="modal-header">
            <h3>Eliminar Registro</h3>
            <button type="button" class="modal-close" onclick="cerrarModalAceite('modalEliminar')">&times;</button>
        </div>
        <div style="padding:1.25rem;">
            <p style="font-size:.85rem; color:#334155; margin:0 0 .25rem;">
                Unidad: <strong id="del_unidad_label"></strong>
            </p>
            <p style="font-size:.8rem; color:#dc2626; margin:0 0 1rem;">
                Se eliminará el registro más reciente. Esta acción no se puede deshacer.
            </p>
            <input type="hidden" id="del_id_flota">
            <div style="display:flex; flex-direction:column; gap:.5rem;">
                <button type="button" class="btn btn-ghost" data-del="kilometraje">
                    <?= icon_tag('gauge', 'gauge', 'w-4 h-4') ?>
                    <span>Eliminar último kilometraje</span>
                </button>
                <button type="button" class="btn btn-ghost" data-del="aceite" style="color:#dc2626;">
                    <?= icon_tag('droplet', 'droplet', 'w-4 h-4') ?>
                    <span>Eliminar último cambio de aceite</span>
                </button>
            </div>
            <div class="modal-footer" style="margin-top:1rem; padding-top:1rem;">
                <button type="button" class="btn btn-ghost" onclick="cerrarModalAceite('modalEliminar')">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.SITGO_BASE = "<?= BASE_URL ?>";
    window.SITGO_INST = <?= (int)($instId ?? 1) ?>;
</script>