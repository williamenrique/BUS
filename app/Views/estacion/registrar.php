<?php
$user = auth_user();
$esSistema = strtoupper($user['departamento_nombre'] ?? '') === 'SISTEMA' || (int)($user['es_sistema'] ?? 0) === 1;
?>

<div class="page-header">
    <div>
        <h2 class="page-title">
            <?= icon_tag('fuel', 'fuel', 'w-6 h-6') ?>
            Estación de Servicio
        </h2>
        <p class="page-subtitle" id="estacionActualNombre">
            <?php if ($esSistema): ?>
                Vista de Administrador
            <?php else: ?>
                <?= e($user['estacion'] ?? $user['departamento_nombre'] ?? 'Sin estación asignada') ?>
            <?php endif; ?>
        </p>
    </div>
    <div style="display:flex; gap:.5rem;">
        <?php if ($esSistema): ?>
            <a href="<?= base_url('estacion/dataventa') ?>" class="btn btn-ghost">
                <?= icon_tag('bar-chart-3', 'bar-chart-3', 'w-4 h-4') ?>
                <span>Data Venta</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- CARD · TASA DEL DÍA -->
<div class="card mb-4">
    <div class="card-body" style="padding:1rem 1.25rem;">
        <div class="estacion-tasa-row">
            <div class="estacion-tasa-label">
                <?= icon_tag('trending-up', 'trending-up', 'w-5 h-5') ?>
                <div>
                    <p style="margin:0; font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; font-weight:600;">Tasa del día</p>
                    <div class="estacion-tasa-value">
                        <span id="tasaDisplay" class="estacion-tasa-amount">0.00</span>
                        <input type="number" id="tasaInput" step="0.01" class="estacion-tasa-input" placeholder="0.00" style="display:none;">
                    </div>
                    <span id="tasaLastUpdate" class="estacion-tasa-date"></span>
                </div>
            </div>
            <button id="btnUpdateTasa" class="btn btn-primary" style="display:none;">
                <?= icon_tag('save', 'save', 'w-4 h-4') ?>
                <span>Actualizar tasa</span>
            </button>
        </div>
                <!-- ============================================ -->
        <!-- PDF POR FECHA · Reporte de un día específico -->
        <!-- ============================================ -->
        <div class="estacion-pdf-fecha" style="margin-top:1rem; padding-top:1rem; border-top:1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:.75rem;">
                <?= icon_tag('file-text', 'file-text', 'w-4 h-4') ?>
                <span style="font-size:.8rem; font-weight:700; color:#334155; text-transform:uppercase; letter-spacing:.03em;">
                    Reporte PDF por fecha
                </span>
                <span style="font-size:.72rem; color:#94a3b8; margin-left:auto;">
                    Solo tu estación · <?= e($user['estacion'] ?? '—') ?>
                </span>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1.5fr auto; gap:.5rem; align-items:end;">
                <div class="form-group" style="margin:0;">
                    <label style="font-size:.7rem; color:#64748b; font-weight:600;">Fecha</label>
                    <input type="date" id="pdfFecha" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label style="font-size:.7rem; color:#64748b; font-weight:600;">Usuario</label>
                    <select id="pdfUsuario" class="form-control">
                        <option value="0">Todos los usuarios de la estación</option>
                    </select>
                </div>
                <button type="button" id="btnPdfPorFecha" class="btn btn-primary">
                    <?= icon_tag('file-text', 'file-text', 'w-4 h-4') ?>
                    <span>Generar PDF</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- LAYOUT 2 COLUMNAS -->
<div class="estacion-layout">
    <div class="estacion-main">

        <!-- FORM VENTA -->
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="estacion-card-title">
                    <?= icon_tag('plus-circle', 'plus-circle', 'w-5 h-5') ?>
                    Registrar venta
                </h3>
                <form id="formVenta" novalidate>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tipo de vehículo</label>
                            <select id="tipoVehiculo" name="tipo_vehiculo"></select>
                        </div>
                        <div class="form-group">
                            <label>Tipo de combustible</label>
                            <select id="tipoCombustible" name="tipo_combustible">
                                <option value="1" selected>Gasolina</option>
                                <option value="2">Diesel</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Litros</label>
                            <input type="number" id="litros" name="litros" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label>Tipo de pago</label>
                            <select id="tipoPago" name="tipo_pago"></select>
                        </div>
                        <div class="form-group">
                            <label>Monto a pagar</label>
                            <input type="text" id="monto" name="monto" readonly>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?= icon_tag('save', 'save', 'w-4 h-4') ?>
                            <span>Registrar venta</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- RESUMEN DEL DÍA -->
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="estacion-card-title">
                    <?= icon_tag('bar-chart-3', 'bar-chart-3', 'w-5 h-5') ?>
                    Resumen de ventas del día
                </h3>
                <div class="estacion-stats">
                    <div class="estacion-stat stat-blue">
                        <div class="estacion-stat-icon"><?= icon_tag('fuel', 'fuel', 'w-6 h-6') ?></div>
                        <div>
                            <span class="estacion-stat-label">Total litros</span>
                            <strong id="totalLitros">0.00 L</strong>
                        </div>
                    </div>
                    <div class="estacion-stat stat-green">
                        <div class="estacion-stat-icon"><?= icon_tag('car', 'car', 'w-6 h-6') ?></div>
                        <div>
                            <span class="estacion-stat-label">Vehículos</span>
                            <strong id="totalVehiculos">0</strong>
                        </div>
                    </div>
                    <div class="estacion-stat stat-amber">
                        <div class="estacion-stat-icon"><?= icon_tag('banknote', 'banknote', 'w-6 h-6') ?></div>
                        <div>
                            <span class="estacion-stat-label">Total ingreso</span>
                            <strong id="totalBolivares">0.00 Bs</strong>
                        </div>
                    </div>
                </div>
                <div class="estacion-desgloses">
                    <div id="tiposPagosContainer" class="estacion-desglose" style="display:none;">
                        <h4 class="estacion-desglose-title"><?= icon_tag('wallet', 'wallet', 'w-4 h-4') ?> Por tipo de pago</h4>
                        <ul id="tiposPagosList"></ul>
                    </div>
                    <div id="tiposVehiculosContainer" class="estacion-desglose" style="display:none;">
                        <h4 class="estacion-desglose-title"><?= icon_tag('car', 'car', 'w-4 h-4') ?> Por tipo de vehículo</h4>
                        <ul id="tiposVehiculosList"></ul>
                    </div>
                    <div id="tiposCombustibleContainer" class="estacion-desglose" style="display:none; grid-column: 1 / -1;">
                        <h4 class="estacion-desglose-title"><?= icon_tag('droplet', 'droplet', 'w-4 h-4') ?> Por tipo de combustible</h4>
                        <ul id="tiposCombustibleList"></ul>
                    </div>
                </div>
                <div class="estacion-actions">
                    <button id="btnCerrarDia" class="btn btn-danger" style="display:none;">
                        <?= icon_tag('door-closed', 'door-closed', 'w-4 h-4') ?>
                        <span>Cerrar día</span>
                    </button>
                    <button id="btnGenerarPDF" class="btn btn-ghost" style="display:none;">
                        <?= icon_tag('file-text', 'file-text', 'w-4 h-4') ?>
                        <span>Generar PDF</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- CIERRES PENDIENTES -->
        <div id="cierrePendienteSection" class="card mb-4" style="display:none;">
            <div class="card-body">
                <h3 class="estacion-card-title" style="color:#dc2626;">
                    <?= icon_tag('alert-triangle', 'alert-triangle', 'w-5 h-5') ?>
                    Días pendientes de cierre
                </h3>
                <div id="cierrePendienteList"></div>
            </div>
        </div>
    </div>

    <div class="estacion-side">

        <!-- VISTA PREVIA TICKET -->
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="estacion-card-title">
                    <?= icon_tag('receipt', 'receipt', 'w-5 h-5') ?>
                    Vista previa del ticket
                </h3>
                <pre id="ticketPreview" class="estacion-preview"></pre>
            </div>
        </div>

        <!-- TICKETS DEL DÍA -->
        <div class="card">
            <div class="card-body">
                <h3 class="estacion-card-title">
                    <?= icon_tag('list', 'list', 'w-5 h-5') ?>
                    Tickets del día
                </h3>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Hora</th>
                                <th>Vehículo</th>
                                <th class="text-right">Litros</th>
                                <th class="text-right"></th>
                            </tr>
                        </thead>
                        <tbody id="tbodyTickets">
                            <tr><td colspan="5" class="dt-empty">Sin tickets</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL · Selector de reporte PDF               -->
<!-- (solo 2 opciones: Gasolina y Diésel)          -->
<!-- ============================================ -->
<div id="modalPdfSelector" class="pdf-modal-backdrop">
    <div class="pdf-modal">
        <div class="pdf-modal-header">
            <h3>¿Qué reporte desea generar?</h3>
            <button type="button" class="pdf-modal-close" data-close="1">&times;</button>
        </div>
        <div class="pdf-modal-body">

            <button type="button" class="pdf-opt" data-seccion="gasolina">
                <div class="pdf-opt-icon" style="background:#fef3c7; color:#b45309;">
                    <?= icon_tag('fuel', 'fuel', 'w-5 h-5') ?>
                </div>
                <div class="pdf-opt-text">
                    <strong>Solo Gasolina</strong>
                    <small>Ventas de gasolina únicamente</small>
                </div>
            </button>

            <button type="button" class="pdf-opt" data-seccion="diesel">
                <div class="pdf-opt-icon" style="background:#ede9fe; color:#6d28d9;">
                    <?= icon_tag('droplet', 'droplet', 'w-5 h-5') ?>
                </div>
                <div class="pdf-opt-text">
                    <strong>Solo Diésel</strong>
                    <small>Ventas de diésel únicamente</small>
                </div>
            </button>

        </div>
    </div>
</div>