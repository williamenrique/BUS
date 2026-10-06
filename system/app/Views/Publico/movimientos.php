<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta Pública de Movimientos - BUS Yaracuy</title>
    <link rel="icon" href="<?= IMG ?>logo.png" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?= IMG ?>logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo CSS; ?>public.css">

    <style>
        .institucion-selector { display: flex; align-items: center; gap: 8px; }
        .institucion-selector label { font-size: 0.8rem; font-weight: 600; color: #495057; margin-bottom: 0; white-space: nowrap; }
        .institucion-selector select { font-size: 0.85rem; padding: 4px 8px; border-radius: 6px; border: 1px solid #ced4da; background-color: #ffffff; font-weight: 600; color: var(--primary); min-width: 130px; }
        .institucion-selector select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.15); }
        .section-header-with-selector { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; }
        .section-header-with-selector h5 { margin-bottom: 0; }
        @media (max-width: 768px) { .institucion-selector { width: 100%; } .institucion-selector select { flex: 1; } }
        /* Estilos para las sub-líneas de la tabla estación */
        .litros-breakdown { font-size: 0.75rem; color: #6c757d; }
        .litros-breakdown .gas { color: #0d6efd; }
        .litros-breakdown .die { color: #fd7e14; }
        .clickable-row { cursor: pointer; }
        .clickable-row:hover { background-color: rgba(13, 110, 253, 0.07); }
    </style>
</head>

<body>
    <div class="container-fluid px-3 px-md-4">
        <!-- Sticky Navigation Bar -->
        <nav class="sticky-navbar sticky-top bg-white border-bottom shadow-sm mb-4 py-2" id="stickyNavbar" role="navigation" aria-label="Navegación principal">
            <div class="nav-buttons-container d-flex flex-wrap justify-content-center gap-2">
                <button type="button" class="btn btn-outline-primary nav-btn active" data-target="#estadoFlota" title="Estado de Flota"><i class="fas fa-bus me-1"></i><span>Flota</span></button>
                <button type="button" class="btn btn-outline-primary nav-btn" data-target="#estadoAceite" title="Monitoreo de Aceite"><i class="fas fa-oil-can me-1"></i><span>Aceite</span></button>
                <button type="button" class="btn btn-outline-primary nav-btn" data-target="#resumenFlota" title="Resumen de Flota por Modelo"><i class="fas fa-table me-1"></i><span>Resumen Flota</span></button>
                <button type="button" class="btn btn-outline-primary nav-btn" data-target="#movimientosSistema" title="Movimientos del Sistema"><i class="fas fa-list me-1"></i><span>Movimientos</span></button>
                <button type="button" class="btn btn-outline-primary nav-btn" data-target="#estacionSistema" title="Ventas de Estación"><i class="fas fa-gas-pump me-1"></i><span>Estación</span></button>
            </div>
        </nav>

        <div class="main-container">
            <!-- Header -->
            <div class="dashboard-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1"><i class="fas fa-chart-line me-2 text-primary"></i>Dashboard Público de Movimientos</h1>
                    <p class="text-muted mb-0">Consulta de movimientos del sistema BUS Yaracuy - Solo lectura</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary px-3 py-2"><i class="fas fa-eye me-1"></i>Acceso Público</span>
                </div>
            </div>

            <!-- Fleet Status Cards -->
            <div class="stats-section section-block mb-5" id="estadoFlota">
                <div class="section-header-with-selector">
                    <div class="d-flex align-items-center flex-wrap gap-3">
                        <h5 class="mb-0"><i class="fas fa-bus me-2 text-primary"></i>Estado de Flota</h5>
                        <div class="d-flex align-items-center">
                            <span class="me-2 text-muted fw-bold">Total Unidades:</span>
                            <span id="unidadesTotal" class="h4 mb-0 fw-bold text-primary">0</span>
                        </div>
                    </div>
                    <div class="institucion-selector">
                        <label for="instSelectFlota"><i class="fas fa-building me-1"></i>Institución:</label>
                        <select id="instSelectFlota" data-section="flota"><option value="">Cargando...</option></select>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                        <div class="row g-3">
                            <div class="col-6"><div class="stat-card status-card-clickable border-start border-success border-4 p-3 shadow-sm rounded bg-white" data-status="1" style="cursor: pointer;" title="Click para ver unidades operativas"><div class="stat-icon text-success"><i class="fas fa-check-circle fa-2x"></i></div><div class="stat-value h3 mb-0 fw-bold" id="unidadesOperativas">0</div><div class="stat-label text-muted small">Unidades Operativas</div></div></div>
                            <div class="col-6"><div class="stat-card status-card-clickable border-start border-danger border-4 p-3 shadow-sm rounded bg-white" data-status="2" style="cursor: pointer;" title="Click para ver unidades inoperativas"><div class="stat-icon text-danger"><i class="fas fa-ban fa-2x"></i></div><div class="stat-value h3 mb-0 fw-bold" id="unidadesInoperativas">0</div><div class="stat-label text-muted small">Unidades Inoperativas</div></div></div>
                            <div class="col-6"><div class="stat-card status-card-clickable border-start border-warning border-4 p-3 shadow-sm rounded bg-white" data-status="3" style="cursor: pointer;" title="Click para ver unidades en mantenimiento"><div class="stat-icon text-warning"><i class="fas fa-tools fa-2x"></i></div><div class="stat-value h3 mb-0 fw-bold" id="unidadesMantenimiento">0</div><div class="stat-label text-muted small">En Mantenimiento</div></div></div>
                            <div class="col-6"><div class="stat-card status-card-clickable border-start border-dark border-4 p-3 shadow-sm rounded bg-white" data-status="4" style="cursor: pointer;" title="Click para ver unidades críticas"><div class="stat-icon text-dark"><i class="fas fa-exclamation-triangle fa-2x"></i></div><div class="stat-value h3 mb-0 fw-bold" id="unidadesCriticas">0</div><div class="stat-label text-muted small">Críticas</div></div></div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="h-100" id="estadoFlotaUnidadesContainer">
                            <div class="mb-2"><div class="input-group input-group-sm"><span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span><input type="text" class="form-control border-start-0" id="estadoFlotaUnidadesSearch" placeholder="Buscar unidad, marca, modelo..."></div></div>
                            <div id="estadoFlotaUnidadesTable" class="h-100">
                                <div class="text-center py-5 text-muted border rounded bg-light"><i class="fas fa-mouse-pointer fa-3x mb-3"></i><h6>Seleccione un estado</h6><p class="small mb-0">Haga clic en una tarjeta de estado para ver las unidades correspondientes.</p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Oil Change Status Cards -->
            <div class="oil-section section-block mb-5" id="estadoAceite">
                <div class="section-header-with-selector">
                    <h5 class="mb-0"><i class="fas fa-oil-can me-2 text-warning"></i>Monitoreo de Cambio de Aceite</h5>
                    <div class="institucion-selector">
                        <label for="instSelectAceite"><i class="fas fa-building me-1"></i>Institución:</label>
                        <select id="instSelectAceite" data-section="aceite"><option value="">Cargando...</option></select>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                        <div class="row g-3">
                            <div class="col-12 col-md-4"><div class="stat-card status-card-clickable border-start border-success border-4 p-3 shadow-sm rounded bg-white" data-status="ok" style="cursor: pointer;" title="Click para ver unidades con mantenimiento OK"><div class="stat-icon text-success"><i class="fas fa-check-circle fa-2x"></i></div><div class="stat-value h3 mb-0 fw-bold" id="aceiteOK">0</div><div class="stat-label text-muted small">Mantenimiento OK</div></div></div>
                            <div class="col-12 col-md-4"><div class="stat-card status-card-clickable border-start border-warning border-4 p-3 shadow-sm rounded bg-white" data-status="proximo" style="cursor: pointer;" title="Click para ver unidades próximas a cambio"><div class="stat-icon text-warning"><i class="fas fa-clock fa-2x"></i></div><div class="stat-value h3 mb-0 fw-bold" id="aceiteProximo">0</div><div class="stat-label text-muted small">Próximo a Cambio</div></div></div>
                            <div class="col-12 col-md-4"><div class="stat-card status-card-clickable border-start border-danger border-4 p-3 shadow-sm rounded bg-white" data-status="requerido" style="cursor: pointer;" title="Click para ver unidades con cambio requerido"><div class="stat-icon text-danger"><i class="fas fa-oil-can fa-2x"></i></div><div class="stat-value h3 mb-0 fw-bold" id="aceiteRequerido">0</div><div class="stat-label text-muted small">Cambio Requerido</div></div></div>
                        </div>
                        <div class="card mt-3 shadow-sm">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold"><i class="fas fa-file-pdf me-2 text-danger"></i>Generar Reporte de Aceite</h6>
                                <button type="button" class="btn btn-danger btn-sm" id="btnGenerarReporteAceite"><i class="fas fa-file-pdf me-1"></i>Generar PDF</button>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-6 col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="aceiteFiltroRequerido" value="Requerido" checked><label class="form-check-label small fw-bold text-danger" for="aceiteFiltroRequerido">Requerido</label></div></div>
                                    <div class="col-6 col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="aceiteFiltroProximo" value="Próximo" checked><label class="form-check-label small fw-bold text-warning" for="aceiteFiltroProximo">Próximo</label></div></div>
                                    <div class="col-6 col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="aceiteFiltroBien" value="Bien" checked><label class="form-check-label small fw-bold text-success" for="aceiteFiltroBien">Bien</label></div></div>
                                    <div class="col-6 col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="aceiteFiltroSinRegistro" value="Sin Registro" checked><label class="form-check-label small fw-bold text-secondary" for="aceiteFiltroSinRegistro">Sin Registro</label></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="h-100" id="estadoAceiteUnidadesContainer">
                            <div class="mb-2"><div class="input-group input-group-sm"><span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span><input type="text" class="form-control border-start-0" id="estadoAceiteUnidadesSearch" placeholder="Buscar unidad, marca, modelo..."></div></div>
                            <div id="estadoAceiteUnidadesTable" class="h-100">
                                <div class="text-center py-5 text-muted border rounded bg-light"><i class="fas fa-oil-can fa-3x mb-3"></i><h6>Seleccione un estado</h6><p class="small mb-0">Haga clic en una tarjeta de estado (OK, Próximo, Requerido) para ver las unidades correspondientes.</p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fleet Summary Section -->
            <div class="stats-section section-block mb-5" id="resumenFlota">
                <div class="section-header-with-selector">
                    <h5 class="mb-0"><i class="fas fa-table me-2 text-primary"></i>Resumen de Flota por Modelo</h5>
                    <div class="institucion-selector">
                        <label for="instSelectResumen"><i class="fas fa-building me-1"></i>Institución:</label>
                        <select id="instSelectResumen" data-section="resumen"><option value="">Cargando...</option></select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-8 mb-3 mb-lg-0">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                            <div class="input-group input-group-sm" style="max-width: 300px;"><span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span><input type="text" class="form-control border-start-0" id="fleetSearch" placeholder="Buscar por modelo, marca..."></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3 bg-light p-2 rounded border">
                            <div class="d-flex align-items-center gap-2">
                                <div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="selectAllFleet" onchange="toggleSelectAllFleet(this)"><label class="form-check-label fw-bold small" for="selectAllFleet">Seleccionar todos</label></div>
                                <span class="badge bg-primary ms-2" id="selectedFleetCount">0 seleccionados</span>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm" id="btnGenerarPdfFlota" disabled><i class="fas fa-file-pdf me-1"></i>Generar PDF Operatividad</button>
                        </div>
                        <div class="table-responsive border rounded" style="max-height: 450px;">
                            <table class="table table-hover mb-0" id="fleetSummaryTable">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th style="width: 40px;"><div class="form-check mb-0"><input class="form-check-input" type="checkbox" id="selectAllFleetHeader" onchange="toggleSelectAllFleet(this)"></div></th>
                                        <th>Marca / Modelo</th>
                                        <th>Transmisión</th>
                                        <th>Combustible</th>
                                        <th style="width: 80px;" class="text-center">Total</th>
                                        <th style="width: 90px;" class="text-center">Operat.</th>
                                        <th style="width: 90px;" class="text-center">Inoperat.</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyFleetSummary"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-light py-2"><h6 class="mb-0 fw-bold"><i class="fas fa-list-check me-2"></i>Grupos Seleccionados</h6></div>
                            <div class="card-body p-2">
                                <div class="row g-2 mb-2">
                                    <div class="col-6"><div class="p-2 border rounded text-center bg-primary bg-opacity-10 border-primary"><div class="h5 mb-0 fw-bold text-primary" id="selTotalCant">0</div><div class="small text-muted">Total Cant.</div></div></div>
                                    <div class="col-6"><div class="p-2 border rounded text-center bg-success bg-opacity-10 border-success"><div class="h5 mb-0 fw-bold text-success" id="selTotalOp">0</div><div class="small text-muted">Operativas</div></div></div>
                                    <div class="col-6"><div class="p-2 border rounded text-center bg-danger bg-opacity-10 border-danger"><div class="h5 mb-0 fw-bold text-danger" id="selTotalInop">0</div><div class="small text-muted">Inoperativas</div></div></div>
                                    <div class="col-6"><div class="p-2 border rounded text-center bg-dark bg-opacity-10 border-dark"><div class="h5 mb-0 fw-bold text-dark" id="selTotalCrit">0</div><div class="small text-muted">Críticas</div></div></div>
                                </div>
                                <div class="table-responsive" style="max-height: 320px;">
                                    <table class="table table-sm table-hover mb-0" id="selectedGroupsTable">
                                        <thead class="table-light"><tr class="text-center"><th class="text-start">Grupo</th><th>Cant.</th><th>Op.</th><th>Inop.</th><th>Crít.</th></tr></thead>
                                        <tbody id="tbodySelectedGroups"><tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-info-circle me-1"></i>Seleccione grupos de la tabla izquierda</td></tr></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section (Movements) -->
            <div class="table-section section-block mb-5" id="movimientosSistema">
                <div class="section-header-with-selector">
                    <h5 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Movimientos del Sistema</h5>
                    <div class="institucion-selector">
                        <label for="instSelectMovimientos"><i class="fas fa-building me-1"></i>Institución:</label>
                        <select id="instSelectMovimientos" data-section="movimientos"><option value="">Cargando...</option></select>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-lg-8 mb-3 mb-lg-0">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                                <h6 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Listado</h6>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <div class="input-group input-group-sm" style="width: 250px;"><span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span><input type="text" class="form-control border-start-0" id="movimientosSearch" placeholder="Buscar por Orden, Unidad, Operador, Mecánico..."></div>
                                </div>
                            </div>
                            <div class="card-body p-2 border-bottom bg-light">
                                <div class="row g-2">
                                    <div class="col-12 col-sm-3"><label class="form-label small mb-1 fw-bold">Fecha Inicio</label><input type="date" class="form-control form-control-sm" id="fechaInicio"></div>
                                    <div class="col-12 col-sm-3"><label class="form-label small mb-1 fw-bold">Fecha Fin</label><input type="date" class="form-control form-control-sm" id="fechaFin"></div>
                                    <div class="col-12 col-sm-3"><label class="form-label small mb-1 fw-bold">Tipo Movimiento</label>
                                        <select class="form-select form-select-sm" id="tipoMovimiento">
                                            <option value="todos" selected>Todos los tipos</option>
                                            <option value="despachos">Despachos Almacén</option>
                                            <option value="mantenimientos">Mantenimientos Flota</option>
                                            <option value="aceite">Cambios de Aceite</option>
                                            <option value="kilometraje">Actualizaciones KM</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-sm-3 d-flex align-items-end gap-1"><button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnFiltrar"><i class="fas fa-filter me-1"></i>Filtrar</button></div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-sm mb-0" id="movimientosTable" style="width: 100%;">
                                    <thead class="table-dark"><tr><th style="width: 40px;">#</th><th>Tipo</th><th style="width: 100px;">Fecha</th><th style="width: 110px;">Referencia</th><th style="width: 90px;">Unidad</th><th>Operador</th><th>Mecánico</th><th>Despachador</th><th>Observación</th><th style="width: 100px;">Estado</th></tr></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                        <div class="h-100" id="resumenMovimientosContainer">
                            <div class="card shadow-sm h-100">
                                <div class="card-header bg-light"><h6 class="mb-0 fw-bold" id="panelTitle"><i class="fas fa-info-circle me-2"></i>Detalle de Selección</h6></div>
                                <div class="card-body" id="resumenMovimientos">
                                    <div class="text-center py-5 text-muted"><i class="fas fa-mouse-pointer fa-3x mb-3"></i><h6>Seleccione un elemento</h6><p class="small mb-0">Haga clic en una fila de la tabla de movimientos para consultar sus detalles técnicos.</p></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Section (ESTACION) -->
            <div class="table-section section-block mb-5" id="estacionSistema">
                <div class="section-header-with-selector">
                    <h5 class="mb-0"><i class="fas fa-gas-pump me-2 text-primary"></i>Ventas de Estación</h5>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-danger btn-sm" id="btnGenerarPdfEstacion">
                            <i class="fas fa-file-pdf me-1"></i>Generar PDF
                        </button>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12 col-lg-8 mb-3 mb-lg-0">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                                <h6 class="mb-0"><i class="fas fa-gas-pump me-2 text-primary"></i>Listado</h6>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <div class="input-group input-group-sm" style="width: 250px;"><span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span><input type="text" class="form-control border-start-0" id="estacionSearch" placeholder="Buscar por estación, vendedor, tipo vehículo..."></div>
                                </div>
                            </div>
                            <div class="card-body p-2 border-bottom bg-light">
                                <div class="row g-2">
                                    <div class="col-12 col-sm-3"><label class="form-label small mb-1 fw-bold">Fecha Inicio</label><input type="date" class="form-control form-control-sm" id="estacionFechaInicio"></div>
                                    <div class="col-12 col-sm-3"><label class="form-label small mb-1 fw-bold">Fecha Fin</label><input type="date" class="form-control form-control-sm" id="estacionFechaFin"></div>
                                    <div class="col-12 col-sm-3"><label class="form-label small mb-1 fw-bold">Estación</label>
                                        <select class="form-select form-select-sm" id="estacionSelect">
                                            <option value="todas" selected>Todas las estaciones</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-sm-3 d-flex align-items-end gap-1"><button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnFiltrarEstacion"><i class="fas fa-filter me-1"></i>Filtrar</button></div>
                                </div>
                            </div>
                            <div class="table-responsive" style="max-height: 500px;">
                                <table class="table table-hover table-striped table-sm mb-0" id="estacionTable" style="width: 100%;">
                                    <thead class="table-dark sticky-top">
                                        <tr>
                                            <th style="width: 40px;">#</th>
                                            <th style="width: 100px;">Fecha</th>
                                            <th>Estación</th>
                                            <th style="width: 130px;">Litros</th>
                                            <th style="width: 90px;">Ventas</th>
                                            <th style="width: 130px;">Monto Bs</th>
                                            <th>Vendedores</th>
                                            <th>Tipos de Vehículo</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-light py-2"><h6 class="mb-0 fw-bold"><i class="fas fa-chart-bar me-2"></i>Gráficos de Ventas</h6></div>
                            <div class="card-body p-2">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Tipo de Gráfico</label>
                                    <select class="form-select form-select-sm" id="estacionChartType">
                                        <option value="litros_dia" selected>Litros por Día</option>
                                        <option value="ventas_dia">Ventas por Día</option>
                                        <option value="litros_tipo_vehiculo">Litros por Tipo Vehículo</option>
                                        <option value="litros_tipo_combustible">Litros por Tipo Combustible</option>
                                        <option value="ventas_vendedor">Ventas por Vendedor</option>
                                        <option value="comparativo_estaciones">Comparativo Estaciones</option>
                                    </select>
                                </div>
                                <div class="text-center py-4" id="estacionChartContainer">
                                    <canvas id="estacionChart" style="max-height: 350px;"></canvas>
                                    <div class="text-muted small mt-2" id="estacionChartEmpty">
                                        <i class="fas fa-chart-line fa-2x mb-2"></i>
                                        <p class="mb-0">Seleccione filtros y presione Filtrar para generar gráfico</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Detalle de Ventas de Estación -->
        <div class="modal fade" id="modalDetalleEstacion" tabindex="-1" aria-labelledby="modalDetalleEstacionLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalDetalleEstacionLabel"><i class="fas fa-list-ol me-2"></i>Detalle de Ventas</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body" id="modalDetalleEstacionBody"></div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="dashboard-footer py-3 border-top text-center">
            <div class="d-flex flex-wrap justify-content-center align-items-center gap-2 small text-muted">
                <span><i class="fas fa-info-circle me-1"></i>Sistema BUS Yaracuy - Interfaz de Consulta</span>
                <span>|</span>
                <span>Datos actualizados en tiempo real</span>
                <span>|</span>
                <span id="lastUpdate"><i class="fas fa-sync-alt me-1"></i>Última actualización: --</span>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const base_url = "<?php echo base_url(); ?>";
        </script>
        <script src="<?php echo JS; ?>function.movimientos.js"></script>
        <script>
            function updateLastUpdate() {
                const now = new Date();
                document.getElementById('lastUpdate').innerHTML = '<i class="fas fa-sync-alt me-1"></i>Última actualización: ' + now.toLocaleTimeString('es-VE');
            }
            updateLastUpdate();
            setInterval(updateLastUpdate, 30000);
        </script>
</body>

</html>