<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta Pública de Movimientos - BUS Yaracuy</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --primary: #2c3e50;
        --primary-light: #34495e;
        --secondary: #1abc9c;
        --accent: #3498db;
        --success: #27ae60;
        --warning: #f39c12;
        --danger: #e74c3c;
        --light: #ecf0f1;
        --dark: #2c3e50;
    }

    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        padding: 20px 0;
    }

    .main-container {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    /* Header */
    .dashboard-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
        color: white;
        padding: 30px;
        position: relative;
        overflow: hidden;
    }

    .dashboard-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 60%;
        height: 200%;
        background: radial-gradient(circle, rgba(26, 188, 156, 0.1) 0%, transparent 70%);
    }

    .dashboard-header h1 {
        font-weight: 700;
        font-size: 1.75rem;
        margin: 0;
        position: relative;
        z-index: 1;
    }

    .dashboard-header p {
        margin: 8px 0 0 0;
        opacity: 0.9;
        font-size: 1rem;
        position: relative;
        z-index: 1;
    }

    .badge-public {
        background: var(--secondary);
        color: var(--dark);
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
        z-index: 1;
    }

    /* Stats Cards - Fleet Status */
    .stats-section {
        padding: 25px 30px;
        background: #fafbfc;
        border-bottom: 1px solid #eef0f2;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid #eef0f2;
        transition: all 0.3s ease;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
    }

    .stat-card .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .stat-card .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dark);
        line-height: 1;
        margin: 8px 0 4px 0;
    }

    .stat-card .stat-label {
        font-size: 0.8rem;
        color: #6c757d;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Fleet Status Cards */
    .stat-card.operativas .stat-icon {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .stat-card.inoperativas .stat-icon {
        background: #fdeaea;
        color: #c62828;
    }

    .stat-card.mantenimiento .stat-icon {
        background: #fff3e0;
        color: #ef6c00;
    }

    .stat-card.criticas .stat-icon {
        background: #fce4ec;
        color: #c2185b;
    }

    /* Oil Change Status Cards */
    .oil-section {
        padding: 25px 30px;
        background: white;
        border-bottom: 1px solid #eef0f2;
    }

    .stat-card.requerido .stat-icon {
        background: #fdeaea;
        color: #c62828;
    }

    .stat-card.proximo .stat-icon {
        background: #fff3e0;
        color: #ef6c00;
    }

    .stat-card.ok .stat-icon {
        background: #e8f5e9;
        color: #2e7d32;
    }

    /* Fleet Summary Table */
    .fleet-summary-section {
        padding: 0 30px 30px 30px;
    }

    .search-box {
        position: relative;
        max-width: 400px;
        margin-bottom: 20px;
    }

    .search-box input {
        padding-left: 40px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    .search-box i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    .table-section {
        padding: 0 30px 30px 30px;
    }

    .table-responsive-custom {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid #eef0f2;
    }

    /* Fleet Summary Table with scroll height control */
    .table-responsive-custom.fleet-summary-table {
        max-height: 400px;
        overflow-y: auto;
    }

    .table {
        margin: 0;
        font-size: 0.8rem;
    }

    .table thead th {
        background: var(--primary);
        color: white;
        border: none;
        padding: 14px 10px;
        font-weight: 600;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .table tbody td {
        padding: 12px 10px;
        vertical-align: middle;
        border-color: #f0f2f5;
    }

    .table tbody tr {
        transition: background-color 0.15s ease;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa !important;
    }

    /* Row type colors */
    .tipo-despacho {
        background-color: #f8fff8 !important;
    }

    .tipo-despacho:hover {
        background-color: #e8f5e9 !important;
    }

    .tipo-venta {
        background-color: #f8fbff !important;
    }

    .tipo-venta:hover {
        background-color: #e3f2fd !important;
    }

    .tipo-mantenimiento {
        background-color: #fffbf0 !important;
    }

    .tipo-mantenimiento:hover {
        background-color: #fff3e0 !important;
    }

    .tipo-compra {
        background-color: #fdf8fa !important;
    }

    .tipo-compra:hover {
        background-color: #fce4ec !important;
    }

    .tipo-aceite {
        background-color: #faf5fa !important;
    }

    .tipo-aceite:hover {
        background-color: #f3e5f5 !important;
    }

    .tipo-kilometraje {
        background-color: #f0fdfa !important;
    }

    .tipo-kilometraje:hover {
        background-color: #e0f2f1 !important;
    }

    .tipo-movimiento {
        font-weight: 600;
        font-size: 0.75rem;
    }

    .observacion-cell {
        max-width: 250px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.75rem;
        color: #495057;
    }

    .estado-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .estado-completado {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .estado-pendiente {
        background: #fff3e0;
        color: #ef6c00;
    }

    .estado-rechazado {
        background: #fdeaea;
        color: #c62828;
    }

    /* Filters */
    .filters-section {
        padding: 25px 30px;
        background: white;
        border-bottom: 1px solid #eef0f2;
    }

    .filter-group {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }

    .filter-item {
        flex: 1;
        min-width: 180px;
    }

    .filter-item label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #495057;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        display: block;
    }

    .filter-item .form-control,
    .filter-item .form-select {
        border-radius: 8px;
        border: 1px solid #dee2e6;
        padding: 10px 14px;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .filter-item .form-control:focus,
    .filter-item .form-select:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
    }

    .btn-filter {
        background: linear-gradient(135deg, var(--accent), #2980b9);
        border: none;
        border-radius: 8px;
        padding: 10px 24px;
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .btn-filter:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        color: white;
    }

    .btn-export {
        background: linear-gradient(135deg, var(--success), #219653);
        border: none;
        border-radius: 8px;
        padding: 10px 24px;
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .btn-export:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
        color: white;
    }

    .btn-export:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Table */
    .table-section {
        padding: 0 30px 30px 30px;
    }

    .table-responsive-custom {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        border: 1px solid #eef0f2;
    }

    .table {
        margin: 0;
        font-size: 0.8rem;
    }

    .table thead th {
        background: var(--primary);
        color: white;
        border: none;
        padding: 14px 10px;
        font-weight: 600;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .table tbody td {
        padding: 12px 10px;
        vertical-align: middle;
        border-color: #f0f2f5;
    }

    .table tbody tr {
        transition: background-color 0.15s ease;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa !important;
    }

    /* Row type colors */
    .tipo-despacho {
        background-color: #f8fff8 !important;
    }

    .tipo-despacho:hover {
        background-color: #e8f5e9 !important;
    }

    .tipo-venta {
        background-color: #f8fbff !important;
    }

    .tipo-venta:hover {
        background-color: #e3f2fd !important;
    }

    .tipo-mantenimiento {
        background-color: #fffbf0 !important;
    }

    .tipo-mantenimiento:hover {
        background-color: #fff3e0 !important;
    }

    .tipo-compra {
        background-color: #fdf8fa !important;
    }

    .tipo-compra:hover {
        background-color: #fce4ec !important;
    }

    .tipo-aceite {
        background-color: #faf5fa !important;
    }

    .tipo-aceite:hover {
        background-color: #f3e5f5 !important;
    }

    .tipo-kilometraje {
        background-color: #f0fdfa !important;
    }

    .tipo-kilometraje:hover {
        background-color: #e0f2f1 !important;
    }

    .tipo-movimiento {
        font-weight: 600;
        font-size: 0.75rem;
    }

    .observacion-cell {
        max-width: 250px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.75rem;
        color: #495057;
    }

    .estado-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .estado-completado {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .estado-pendiente {
        background: #fff3e0;
        color: #ef6c00;
    }

    .estado-rechazado {
        background: #fdeaea;
        color: #c62828;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 16px;
        opacity: 0.5;
    }

    .empty-state h4 {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
    }

    .empty-state p {
        margin: 0;
        font-size: 0.9rem;
    }

    /* Loading */
    .loading-container {
        text-align: center;
        padding: 60px 20px;
    }

    .spinner-custom {
        width: 48px;
        height: 48px;
        border: 4px solid #eef0f2;
        border-top-color: var(--accent);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 16px auto;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Footer */
    .dashboard-footer {
        background: #fafbfc;
        border-top: 1px solid #eef0f2;
        padding: 20px 30px;
        text-align: center;
        font-size: 0.8rem;
        color: #6c757d;
    }

    .dashboard-footer a {
        color: var(--accent);
        text-decoration: none;
    }

    .dashboard-footer a:hover {
        text-decoration: underline;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .dashboard-header h1 {
            font-size: 1.5rem;
        }

        .stat-card .stat-value {
            font-size: 1.5rem;
        }

        .table {
            font-size: 0.75rem;
        }
    }

    @media (max-width: 768px) {
        body {
            padding: 10px 0;
        }

        .main-container {
            border-radius: 12px;
        }

        .dashboard-header {
            padding: 20px;
        }

        .stats-section,
        .filters-section,
        .table-section {
            padding-left: 15px;
            padding-right: 15px;
        }

        .filter-group {
            flex-direction: column;
            align-items: stretch;
        }

        .filter-item {
            min-width: 100%;
        }

        .btn-filter,
        .btn-export {
            width: 100%;
        }

        .table-responsive-custom {
            border-radius: 8px;
        }
    }

    /* Print styles */
    @media print {

        .filters-section,
        .dashboard-footer,
        .btn-filter,
        .btn-export {
            display: none !important;
        }

        .main-container {
            box-shadow: none;
            border-radius: 0;
        }

        .dashboard-header {
            background: var(--primary) !important;
            -webkit-print-color-adjust: exact;
        }

        .stat-card {
            box-shadow: none;
            border: 1px solid #ddd;
        }
    }
    </style>
</head>

<body>
    <div class="container-fluid px-3 px-md-4">
        <div class="main-container">
            <!-- Header -->
            <div class="dashboard-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1><i class="fas fa-chart-line me-2"></i>Dashboard Público de Movimientos</h1>
                    <p class="mb-0">Consulta de movimientos del sistema BUS Yaracuy - Solo lectura</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-public"><i class="fas fa-eye me-1"></i>Acceso Público</span>
                </div>
            </div>

            <!-- Fleet Status Cards -->
            <div class="stats-section">
                <h5 class="mb-0"><i class="fas fa-bus me-2"></i>Estado de Flota</h5>
                <div class="row g-3">
                    <div class="col-6 col-md-3 col-lg-3">
                        <div class="stat-card total">
                            <div class="stat-icon"><i class="fas fa-bus"></i></div>
                            <div class="stat-value" id="unidadesTotal">0</div>
                            <div class="stat-label">Total Unidades</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <div class="stat-card operativas">
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-value" id="unidadesOperativas">0</div>
                            <div class="stat-label">Unidades Operativas</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <div class="stat-card inoperativas">
                            <div class="stat-icon"><i class="fas fa-ban"></i></div>
                            <div class="stat-value" id="unidadesInoperativas">0</div>
                            <div class="stat-label">Unidades Inoperativas</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <div class="stat-card mantenimiento">
                            <div class="stat-icon"><i class="fas fa-tools"></i></div>
                            <div class="stat-value" id="unidadesMantenimiento">0</div>
                            <div class="stat-label">En Mantenimiento</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-3">
                        <div class="stat-card criticas">
                            <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="stat-value" id="unidadesCriticas">0</div>
                            <div class="stat-label">Críticas</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Oil Change Status Cards -->
            <div class="oil-section">
                <h5 class="mb-0"><i class="fas fa-oil-can me-2"></i>Monitoreo de Cambio de Aceite</h5>
                <div class="row g-3">
                    <div class="col-6 col-md-4 col-lg-4">
                        <div class="stat-card ok">
                            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="stat-value" id="aceiteOK">0</div>
                            <div class="stat-label">Mantenimiento OK</div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-4">
                        <div class="stat-card proximo">
                            <div class="stat-icon"><i class="fas fa-clock"></i></div>
                            <div class="stat-value" id="aceiteProximo">0</div>
                            <div class="stat-label">Próximo a Cambio</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-4">
                        <div class="stat-card requerido">
                            <div class="stat-icon"><i class="fas fa-oil-can"></i></div>
                            <div class="stat-value" id="aceiteRequerido">0</div>
                            <div class="stat-label">Cambio Requerido</div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Fleet Summary Table Section -->
            <div class="fleet-summary-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fas fa-table me-2"></i>Resumen de Flota por Modelo</h5>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" class="form-control" id="fleetSearch"
                            placeholder="Buscar por marca, modelo, transmisión, combustible, estado...">
                    </div>
                </div>
                <div class="table-responsive-custom fleet-summary-table">
                    <table class="table table-hover mb-0" id="fleetSummaryTable">
                        <thead>
                            <tr>
                                <th>Marca / Modelo</th>
                                <th>Transmisión</th>
                                <th>Combustible</th>
                                <th style="width: 80px;">Total</th>
                                <th style="width: 100px;">Operativas</th>
                                <th style="width: 100px;">Inoperativas</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyFleetSummary">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <div class="filter-group">
                    <div class="filter-item">
                        <label>Fecha Inicio</label>
                        <input type="date" class="form-control" id="fechaInicio">
                    </div>
                    <div class="filter-item">
                        <label>Fecha Fin</label>
                        <input type="date" class="form-control" id="fechaFin">
                    </div>
                    <div class="filter-item">
                        <label>Tipo de Movimiento</label>
                        <select class="form-select" id="tipoMovimiento">
                            <option value="todos">Todos los movimientos</option>
                            <option value="despachos">Despachos Almacén</option>
                            <option value="mantenimientos">Mantenimientos Flota</option>
                            <option value="aceite">Cambios de Aceite</option>
                            <option value="kilometraje">Actualizaciones KM</option>
                        </select>
                    </div>
                    <div class="filter-item d-flex gap-2" style="min-width: 140px;">
                        <button type="button" class="btn-filter flex-fill" id="btnFiltrar">
                            <i class="fas fa-filter me-1"></i>Filtrar
                        </button>
                        <button type="button" class="btn-export flex-fill" id="btnExportarPDF" disabled>
                            <i class="fas fa-file-pdf me-1"></i>Exportar PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="table-section">
                <!-- Loading -->
                <div class="loading-container" id="loadingSpinner" style="display: none;">
                    <div class="spinner-custom"></div>
                    <p class="text-muted mb-0">Cargando movimientos...</p>
                </div>

                <!-- Empty State -->
                <div class="empty-state" id="emptyState" style="display: none;">
                    <i class="fas fa-inbox"></i>
                    <h4>No hay movimientos registrados</h4>
                    <p>No se encontraron movimientos para el período y filtro seleccionados.</p>
                </div>

                <!-- Table -->
                <div class="table-responsive-custom" id="tablaMovimientos" style="display: none;">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Tipo</th>
                                <th style="width: 110px;">Fecha</th>
                                <th style="width: 120px;">Referencia</th>
                                <th style="width: 100px;">Unidad</th>
                                <th style="width: 140px;">Operador</th>
                                <th style="width: 140px;">Mecánico</th>
                                <th style="width: 120px;">Despachador</th>
                                <th>Observación</th>
                                <th style="width: 130px;">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyMovimientos">
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="dashboard-footer">
                <div class="d-flex flex-wrap justify-content-center align-items-center gap-2">
                    <span><i class="fas fa-info-circle me-1"></i>Sistema BUS Yaracuy - Interfaz Pública de
                        Consulta</span>
                    <span class="text-muted">|</span>
                    <span>Datos actualizados en tiempo real desde la base de datos</span>
                    <span class="text-muted">|</span>
                    <span id="lastUpdate"><i class="fas fa-sync-alt me-1"></i>Última actualización: --</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo JS; ?>function.movimientos.js"></script>
    <script>
    // Update last update time
    function updateLastUpdate() {
        const now = new Date();
        document.getElementById('lastUpdate').innerHTML =
            '<i class="fas fa-sync-alt me-1"></i>Última actualización: ' + now.toLocaleTimeString('es-VE');
    }
    updateLastUpdate();
    setInterval(updateLastUpdate, 30000); // Update every 30 seconds
    </script>
</body>

</html>