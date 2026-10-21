<?php
$pageTitle = $pageTitle ?? 'Movimientos Públicos';
require __DIR__ . '/inc/header.php';
?>

<div class="publico-wrap">

    <!-- ============================================================ -->
    <!-- SECCIÓN: ESTADO DE FLOTA                                      -->
    <!-- ============================================================ -->
    <section class="pub-sec" id="sec-flota">
        <div class="pub-sec-head">
            <h3><i data-lucide="fuel" class="w-5 h-5 text-primary"></i> Ventas de Estación</h3>
            <div style="display:flex; gap:.5rem; align-items:center;">
                <label style="font-size:.72rem; color:#64748b; font-weight:600;">Institución:</label>
                <select id="instEstacion" class="filter-select" style="min-width:180px;"></select>
            </div>
        </div>
        <div class="pub-sec-body">
            <div class="pub-stats" id="flotaStats">
                <div class="pub-stat" data-status="1"><div class="num" id="flotaOperativas">0</div><div class="lbl">Operativas</div></div>
                <div class="pub-stat red" data-status="2"><div class="num" id="flotaInoperativas">0</div><div class="lbl">Inoperativas</div></div>
                <div class="pub-stat amber" data-status="3"><div class="num" id="flotaMantenimiento">0</div><div class="lbl">Mantenimiento</div></div>
                <div class="pub-stat dark" data-status="4"><div class="num" id="flotaCriticas">0</div><div class="lbl">Críticas</div></div>
                <div class="pub-stat slate"><div class="num" id="flotaTotal">0</div><div class="lbl">Total Unidades</div></div>
            </div>
            <div id="flotaTablaWrap" style="display:none;">
                <div class="filter-search" style="max-width:320px; margin-bottom:.75rem;">
                    <span class="filter-search-icon"><i data-lucide="search" class="w-4 h-4"></i></span>
                    <input type="text" id="flotaSearch" placeholder="Buscar unidad, marca, modelo...">
                </div>
                <div class="table-wrap" style="max-height:400px;">
                    <table class="pub-tabla">
                        <thead>
                            <tr>
                                <th>Unidad</th><th>Marca / Modelo</th><th>VIN</th>
                                <th>Transmisión</th><th>Combustible</th>
                                <th class="text-right">KM</th><th class="text-right">Últ. Cambio</th>
                            </tr>
                        </thead>
                        <tbody id="flotaTablaBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- SECCIÓN: ACEITE                                               -->
    <!-- ============================================================ -->
    <section class="pub-sec" id="sec-aceite">
        <div class="pub-sec-head">
            <h3><i data-lucide="droplet" class="w-5 h-5 text-amber-600"></i> Monitoreo de Aceite</h3>
            <div style="display:flex; gap:.5rem; align-items:center;">
                <label style="font-size:.72rem; color:#64748b; font-weight:600;">Institución:</label>
                <select id="instAceite" class="filter-select" style="min-width:180px;"></select>
            </div>
        </div>
        <div class="pub-sec-body">
            <div class="pub-stats">
                <div class="pub-stat green" data-aceite="ok"><div class="num" id="aceiteOk">0</div><div class="lbl">Bien</div></div>
                <div class="pub-stat amber" data-aceite="proximo"><div class="num" id="aceiteProximo">0</div><div class="lbl">Próximo</div></div>
                <div class="pub-stat red" data-aceite="requerido"><div class="num" id="aceiteRequerido">0</div><div class="lbl">Requerido</div></div>
            </div>
            <div style="display:flex; gap:.5rem; margin-bottom:.75rem; flex-wrap:wrap;">
                <button class="btn btn-primary btn-sm" id="btnPdfAceite" disabled>
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <span>PDF del estado</span>
                </button>
                <button class="btn btn-ghost btn-sm" id="btnPdfAceiteCompleto">
                    <i data-lucide="file-stack" class="w-4 h-4"></i>
                    <span>PDF completo</span>
                </button>
            </div>
            <div id="aceiteTablaWrap" style="display:none;">
                <div class="filter-search" style="max-width:320px; margin-bottom:.75rem;">
                    <span class="filter-search-icon"><i data-lucide="search" class="w-4 h-4"></i></span>
                    <input type="text" id="aceiteSearch" placeholder="Buscar unidad...">
                </div>
                <div class="table-wrap" style="max-height:400px;">
                    <table class="pub-tabla">
                        <thead>
                            <tr>
                                <th>Unidad</th><th>Marca / Modelo</th><th>VIN</th>
                                <th class="text-right">KM Actual</th><th class="text-right">Últ. Cambio</th>
                                <th class="text-right">Próx. Cambio</th><th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="aceiteTablaBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- SECCIÓN: RESUMEN FLOTA                                        -->
    <!-- ============================================================ -->
    <section class="pub-sec" id="sec-resumen">
        <div class="pub-sec-head">
            <h3><i data-lucide="table-2" class="w-5 h-5 text-primary"></i> Resumen de Flota por Modelo</h3>
            <div style="display:flex; gap:.5rem; align-items:center;">
                <label style="font-size:.72rem; color:#64748b; font-weight:600;">Institución:</label>
                <select id="instResumen" class="filter-select" style="min-width:180px;"></select>
            </div>
        </div>
        <div class="pub-sec-body">
            <div class="pub-2col">

                <div>
                    <div style="display:flex; gap:.5rem; align-items:center; margin-bottom:.65rem; flex-wrap:wrap;">
                        <div class="filter-search" style="flex:1; min-width:200px;">
                            <span class="filter-search-icon"><i data-lucide="search" class="w-4 h-4"></i></span>
                            <input type="text" id="resumenSearch" placeholder="Buscar modelo, marca...">
                        </div>
                        <label style="display:flex; align-items:center; gap:.35rem; font-size:.78rem; font-weight:600; color:#334155; cursor:pointer; user-select:none; padding:.4rem .7rem; border:1px solid #e2e8f0; border-radius:.4rem; background:#f8fafc;">
                            <input type="checkbox" id="resumenSelectAll" style="width:16px; height:16px; accent-color:#2563eb; cursor:pointer;">
                            <span>Seleccionar todos</span>
                        </label>
                    </div>
                    <div class="table-wrap" style="max-height:440px;">
                        <table class="pub-tabla">
                            <thead>
                                <tr>
                                    <th style="width:40px;"><input type="checkbox" id="resumenSelectAllHeader" style="width:16px; height:16px; accent-color:#2563eb; cursor:pointer;"></th>
                                    <th>Marca / Modelo</th>
                                    <th>Transmisión</th>
                                    <th>Combustible</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Operat.</th>
                                    <th class="text-center">Inoperat.</th>
                                </tr>
                            </thead>
                            <tbody id="resumenBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="padding:1rem;">
                        <h4 style="margin:0 0 .75rem; font-size:.88rem; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:.4rem;">
                            <i data-lucide="check-square" class="w-4 h-4 text-primary"></i>
                            Grupos seleccionados
                            <span id="resumenSelCount" class="badge badge-info" style="margin-left:auto;">0</span>
                        </h4>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:.4rem; margin-bottom:.75rem;">
                            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:.5rem; padding:.55rem .65rem; text-align:center;">
                                <div style="font-size:.62rem; color:#64748b; text-transform:uppercase; font-weight:700;">Total</div>
                                <div id="resumenSelTotal" style="font-size:1.1rem; font-weight:800; color:#1e40af;">0</div>
                            </div>
                            <div style="background:#dcfce7; border:1px solid #bbf7d0; border-radius:.5rem; padding:.55rem .65rem; text-align:center;">
                                <div style="font-size:.62rem; color:#64748b; text-transform:uppercase; font-weight:700;">Operativas</div>
                                <div id="resumenSelOp" style="font-size:1.1rem; font-weight:800; color:#166534;">0</div>
                            </div>
                            <div style="background:#fee2e2; border:1px solid #fecaca; border-radius:.5rem; padding:.55rem .65rem; text-align:center;">
                                <div style="font-size:.62rem; color:#64748b; text-transform:uppercase; font-weight:700;">Inoperativas</div>
                                <div id="resumenSelInop" style="font-size:1.1rem; font-weight:800; color:#991b1b;">0</div>
                            </div>
                            <div style="background:#f1f5f9; border:1px solid #e2e8f0; border-radius:.5rem; padding:.55rem .65rem; text-align:center;">
                                <div style="font-size:.62rem; color:#64748b; text-transform:uppercase; font-weight:700;">Críticas</div>
                                <div id="resumenSelCrit" style="font-size:1.1rem; font-weight:800; color:#0f172a;">0</div>
                            </div>
                        </div>

                        <div style="max-height:260px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:.4rem;">
                            <table class="pub-tabla" style="font-size:.78rem;">
                                <thead>
                                    <tr>
                                        <th>Grupo</th>
                                        <th class="text-center">Cant</th>
                                        <th class="text-center">Op</th>
                                        <th class="text-center">Inop</th>
                                    </tr>
                                </thead>
                                <tbody id="resumenSelectedBody">
                                    <tr><td colspan="4" style="text-align:center; padding:1rem; color:#94a3b8; font-style:italic;">Selecciona grupos de la tabla</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <button class="btn btn-primary btn-sm" id="btnPdfResumen" disabled style="width:100%; margin-top:.75rem; justify-content:center;">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <span>Generar PDF Operatividad</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- SECCIÓN: MOVIMIENTOS                                          -->
    <!-- ============================================================ -->
    <section class="pub-sec" id="sec-movimientos">
        <div class="pub-sec-head">
            <h3><i data-lucide="list" class="w-5 h-5 text-primary"></i> Movimientos del Sistema</h3>
            <div style="display:flex; gap:.5rem; align-items:center;">
                <label style="font-size:.72rem; color:#64748b; font-weight:600;">Institución:</label>
                <select id="instMovimientos" class="filter-select" style="min-width:180px;"></select>
            </div>
        </div>
        <div class="pub-sec-body">
            <div class="pub-filters">
                <div class="form-group">
                    <label>Fecha Inicio</label>
                    <input type="date" id="movFechaInicio">
                </div>
                <div class="form-group">
                    <label>Fecha Fin</label>
                    <input type="date" id="movFechaFin">
                </div>
                <div class="form-group">
                    <label>Tipo Movimiento</label>
                    <select id="movTipo">
                        <option value="todos">Todos</option>
                        <option value="despachos">Despachos Almacén</option>
                        <option value="mantenimientos">Mantenimientos Flota</option>
                        <option value="aceite">Cambios de Aceite</option>
                        <option value="kilometraje">Actualizaciones KM</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary btn-sm" id="btnMovFiltrar">
                        <i data-lucide="filter" class="w-4 h-4"></i>
                        <span>Filtrar por fechas</span>
                    </button>
                </div>
            </div>

            <div class="pub-2col" style="align-items:start;">
                <div class="card">
                    <div class="dt-toolbar">
                        <div class="dt-toolbar-left">
                            <span class="dt-toolbar-label">Mostrar</span>
                            <select id="movLimit" class="filter-select" style="min-width:70px;">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="dt-toolbar-label">registros</span>
                        </div>
                        <div class="dt-toolbar-right">
                            <div class="filter-search" style="min-width:200px;">
                                <span class="filter-search-icon"><i data-lucide="search" class="w-4 h-4"></i></span>
                                <input type="text" id="movSearch" placeholder="Buscar...">
                            </div>
                        </div>
                    </div>
                    <div class="table-wrap" style="max-height:600px; overflow-y:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th><th>Tipo</th><th>Fecha</th><th>Referencia</th>
                                    <th>Unidad</th><th>Operador</th><th>Mecánico</th><th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="movBody">
                                <tr><td colspan="8" class="dt-loading">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="dt-footer">
                        <div class="dt-info">
                            Mostrando <strong id="movStart">0</strong>–<strong id="movEnd">0</strong>
                            de <strong id="movTotal">0</strong> movimientos
                        </div>
                        <div class="dt-pagination" id="movPagination"></div>
                    </div>
                </div>

                <div class="card" style="position:sticky; top:1rem;">
                    <div class="pub-detail" id="movDetalle" style="min-height:400px; max-height:calc(100vh - 200px); overflow-y:auto;">
                        <div style="text-align:center; padding:3rem 1rem; color:#94a3b8;">
                            <i data-lucide="mouse-pointer-click" style="width:40px; height:40px; margin:0 auto 1rem;"></i>
                            <h4 style="margin:0 0 .35rem; font-size:.9rem; color:#475569; font-weight:600;">Selecciona un movimiento</h4>
                            <p style="margin:0; font-size:.82rem; line-height:1.4;">
                                Haz clic en una <strong>referencia</strong> para ver el detalle completo, o en una <strong>unidad</strong> para ver su historial.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- SECCIÓN: VENTAS DE ESTACIÓN                                   -->
    <!-- ============================================================ -->
    <section class="pub-sec" id="sec-estacion">
        <div class="pub-sec-head">
            <h3><i data-lucide="fuel" class="w-5 h-5 text-primary"></i> Ventas de Estación</h3>
            <div style="display:flex; gap:.5rem;">
                <button class="btn btn-primary btn-sm" id="btnPdfEstacion">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <span>Generar PDF</span>
                </button>
            </div>
        </div>
        <div class="pub-sec-body">
            <div class="pub-filters">
                <div class="form-group">
                    <label>Fecha Inicio</label>
                    <input type="date" id="estFechaInicio">
                </div>
                <div class="form-group">
                    <label>Fecha Fin</label>
                    <input type="date" id="estFechaFin">
                </div>
                <div class="form-group">
                    <label>Estación</label>
                    <select id="estEstacion">
                        <option value="todas">Todas las estaciones</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary btn-sm" id="btnEstFiltrar">
                        <i data-lucide="filter" class="w-4 h-4"></i>
                        <span>Filtrar por fechas</span>
                    </button>
                </div>
            </div>

            <div class="pub-2col">
                <div class="card">
                    <div class="dt-toolbar">
                        <div class="dt-toolbar-left">
                            <span class="dt-toolbar-label">Mostrar</span>
                            <select id="estLimit" class="filter-select" style="min-width:70px;">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                            <span class="dt-toolbar-label">registros</span>
                        </div>
                        <div class="dt-toolbar-right">
                            <div class="filter-search" style="min-width:200px;">
                                <span class="filter-search-icon"><i data-lucide="search" class="w-4 h-4"></i></span>
                                <input type="text" id="estSearch" placeholder="Buscar...">
                            </div>
                        </div>
                    </div>
                    <div class="table-wrap" style="max-height:500px; overflow-y:auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th><th>Fecha</th><th>Estación</th>
                                    <th class="text-right">Litros</th>
                                    <th class="text-right">Ventas</th>
                                    <th class="text-right">Monto Bs</th>
                                    <th class="text-right"></th>
                                </tr>
                            </thead>
                            <tbody id="estBody">
                                <tr><td colspan="7" class="dt-loading">Cargando...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="dt-footer">
                        <div class="dt-info">
                            Mostrando <strong id="estStart">0</strong>–<strong id="estEnd">0</strong>
                            de <strong id="estTotal">0</strong> registros
                        </div>
                        <div class="dt-pagination" id="estPagination"></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h4 style="margin:0 0 .75rem; font-size:.9rem; font-weight:700; color:#0f172a;">
                            <i data-lucide="bar-chart-3" class="w-4 h-4" style="display:inline; vertical-align:middle;"></i>
                            Gráficos de Ventas
                        </h4>
                        <div style="margin-bottom:.75rem;">
                            <label style="font-size:.72rem; color:#475569; font-weight:600;">Tipo de gráfico</label>
                            <select id="estChartType" class="filter-select" style="width:100%; margin-top:.25rem;">
                                <option value="litros_dia">Litros por Día</option>
                                <option value="ventas_dia">Ventas por Día</option>
                                <option value="litros_tipo_vehiculo">Litros por Tipo Vehículo</option>
                                <option value="litros_tipo_combustible">Litros por Tipo Combustible</option>
                                <option value="ventas_vendedor">Ventas por Vendedor</option>
                                <option value="comparativo_estaciones">Comparativo Estaciones</option>
                            </select>
                        </div>
                        <canvas id="estChart" style="max-height:320px;"></canvas>
                        <div id="estChartEmpty" style="text-align:center; padding:2rem 1rem; color:#94a3b8; font-size:.85rem; display:none;">
                            Sin datos para mostrar
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ============================================ -->
    <!-- MODAL · Selector de reporte PDF de Estación   -->
    <!-- ============================================ -->
    <div id="modalPdfEstacion" class="pdf-modal-backdrop">
        <div class="pdf-modal">
            <div class="pdf-modal-header">
                <h3>¿Qué reporte desea generar?</h3>
                <button type="button" class="pdf-modal-close" data-close="1">&times;</button>
            </div>
            <div class="pdf-modal-body">
                <button type="button" class="pdf-opt" data-seccion="resumen">
                    <div class="pdf-opt-icon" style="background:#dbeafe; color:#1e40af;">
                        <i data-lucide="bar-chart-3" style="width:20px; height:20px;"></i>
                    </div>
                    <div class="pdf-opt-text">
                        <strong>Resumen General</strong>
                        <small>Totales y desgloses por combustible, vehículo y pago</small>
                    </div>
                </button>
                <button type="button" class="pdf-opt" data-seccion="ventas">
                    <div class="pdf-opt-icon" style="background:#dcfce7; color:#166534;">
                        <i data-lucide="list" style="width:20px; height:20px;"></i>
                    </div>
                    <div class="pdf-opt-text">
                        <strong>Todas las Ventas</strong>
                        <small>Listado completo del día (gasolina y diésel)</small>
                    </div>
                </button>
                <button type="button" class="pdf-opt" data-seccion="gasolina">
                    <div class="pdf-opt-icon" style="background:#fef3c7; color:#b45309;">
                        <i data-lucide="fuel" style="width:20px; height:20px;"></i>
                    </div>
                    <div class="pdf-opt-text">
                        <strong>Solo Gasolina</strong>
                        <small>Ventas de gasolina únicamente</small>
                    </div>
                </button>
                <button type="button" class="pdf-opt" data-seccion="diesel">
                    <div class="pdf-opt-icon" style="background:#ede9fe; color:#6d28d9;">
                        <i data-lucide="droplet" style="width:20px; height:20px;"></i>
                    </div>
                    <div class="pdf-opt-text">
                        <strong>Solo Diésel</strong>
                        <small>Ventas de diésel únicamente</small>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal detalle de ventas de estación -->
<div id="modalDetalleEstacion" class="pub-modal-backdrop">
    <div class="pub-modal">
        <div class="pub-modal-head">
            <h3 id="modalDetalleTitle">Detalle de Ventas</h3>
            <button type="button" class="pub-modal-close" data-close="1">&times;</button>
        </div>
        <div class="pub-modal-body" id="modalDetalleBody"></div>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>