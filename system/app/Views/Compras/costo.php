<?= head($data)?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                    <!-- Default box -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Generar Reporte de Compras</h3>
                        </div>
                        <div class="card-body">
                            <form id="formReporteCompras">
                                <div class="row align-items-end">
                                    <div class="col-md-4 form-group">
                                        <label for="listUnidadReporte">Unidad</label>
                                        <select id="listUnidadReporte" name="listUnidadReporte" class="form-control" required></select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label for="fechaInicio">Fecha Inicio</label>
                                        <input type="date" id="fechaInicio" name="fechaInicio" class="form-control" required>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label for="fechaFin">Fecha Fin</label>
                                        <input type="date" id="fechaFin" name="fechaFin" class="form-control" required>
                                    </div>
                                    <div class="col-md-2 form-group">
                                        <button type="submit" id="btnGenerarReporte" class="btn btn-success btn-block" disabled>
                                            <i class="fas fa-file-pdf mr-2"></i>Generar PDF
                                        </button>
                                    </div>
                                </div>
                                <div id="conteoReporte" class="form-text text-muted mt-2" style="height: 1.25rem;">
                                    <!-- Mensaje de conteo de órdenes se insertará aquí -->
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card card-primary card-tabs">
                        <div class="card-header p-0 pt-1">
                            <ul class="nav nav-tabs" id="comprasTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="pendientes-tab" data-toggle="pill" href="#pendientes" role="tab" aria-controls="pendientes" aria-selected="true">Pendientes</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="costeados-tab" data-toggle="pill" href="#costeados" role="tab" aria-controls="costeados" aria-selected="false">Costeados</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="comprasTabContent">
                                <div class="tab-pane fade show active" id="pendientes" role="tabpanel" aria-labelledby="pendientes-tab">
                                    <div class="table-responsive">
                                        <table id="tableComprasPendientes" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>ID Despacho</th>
                                                    <th>Fecha Despacho</th>
                                                    <th>Unidad</th>
                                                    <th>Artículos Pendientes</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="costeados" role="tabpanel" aria-labelledby="costeados-tab">
                                    <form id="formFiltroCosteadas" class="mb-4 p-3 border rounded">
                                        <div class="row align-items-end">
                                            <div class="col-md-3 form-group">
                                                <label for="fechaInicioCosteadas">Fecha Inicio</label>
                                                <input type="date" id="fechaInicioCosteadas" name="fechaInicioCosteadas" class="form-control">
                                            </div>
                                            <div class="col-md-3 form-group">
                                                <label for="fechaFinCosteadas">Fecha Fin</label>
                                                <input type="date" id="fechaFinCosteadas" name="fechaFinCosteadas" class="form-control">
                                            </div>
                                            <div class="col-md-6 form-group d-flex justify-content-start gap-2">
                                                <button type="submit" id="btnFiltrarCosteadas" class="btn btn-primary"><i class="fas fa-filter mr-1"></i>Filtrar</button>
                                                <button type="button" id="btnLimpiarFiltroCosteadas" class="btn btn-secondary"><i class="fas fa-eraser mr-1"></i>Limpiar</button>
                                                <button type="button" id="btnExportarPdfCosteadas" class="btn btn-danger"><i class="fas fa-file-pdf mr-1"></i>Exportar PDF</button>
                                            </div>
                                        </div>
                                    </form>
                                    <div class="table-responsive">
                                        <table id="tableComprasCosteadas" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>ID Despacho</th>
                                                    <th>Fecha Despacho</th>
                                                    <th>Unidad</th>
                                                    <th>Artículos</th>
                                                    <th>Total Divisa ($)</th>
                                                    <th>Total Bs</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal para Asignar Costo -->
                    <div class="modal fade" id="modalAsignarCosto" tabindex="-1" role="dialog" aria-labelledby="titleModalCosto" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="titleModalCosto">Asignar Costo a Artículo</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closeModalCosto()">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form id="formAsignarCosto" name="formAsignarCosto">
                                        <input type="hidden" id="idDespacho" name="idDespacho" value="">
                                        <input type="hidden" id="jsonArticulos" name="articulos">
                                        
                                        <div class="callout callout-info">
                                            <h5>Detalles del Despacho</h5>
                                            <p><strong>Unidad:</strong> <span id="infoUnidad"></span><br>
                                            <strong>Fecha Despacho:</strong> <span id="infoFecha"></span></p>
                                        </div>

                                        <div class="form-group">
                                            <label for="tasaDia">Tasa del Día (Bs por $)<span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" id="tasaDia" name="tasaDia" class="form-control" style="max-width: 200px;" required>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Artículo</th>
                                                        <th class="text-center">Cantidad</th>
                                                        <th class="text-center">Monto Divisa ($)</th>
                                                        <th class="text-center">Monto Bs (Calculado)</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tablaArticulosCosto"></tbody>
                                            </table>
                                        </div>
                                        <div id="no-articles-msg" class="text-center py-4 text-muted" style="display: none;">
                                            No hay artículos pendientes para este despacho.
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="closeModalCosto()">Cerrar</button>
                                    <button type="submit" form="formAsignarCosto" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i><span id="btnActionTextCosto">Guardar Costo</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal para Ver Detalle de Costos -->
                    <div class="modal fade" id="modalVerDetalle" tabindex="-1" role="dialog" aria-labelledby="titleModalDetalle" aria-hidden="true">
                        <div class="modal-dialog modal-xl" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="titleModalDetalle">Detalles del Despacho Costeado</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="cerrarModalDetalle()">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="callout callout-success">
                                        <h5>Información General</h5>
                                        <p><strong>Unidad:</strong> <span id="detalleInfoUnidad"></span><br>
                                        <strong>Fecha Despacho:</strong> <span id="detalleInfoFecha"></span></p>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Artículo</th>
                                                    <th class="text-center">Cantidad</th>
                                                    <th>Tasa Aplicada</th>
                                                    <th>Monto Divisa ($)</th>
                                                    <th>Monto Bs</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tablaDetalleCostos"></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer justify-content-between">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="cerrarModalDetalle()">Cerrar</button>
                                    <div>
                                        <button id="btnGenerarPdfCosto" type="button" class="btn btn-info">
                                            <i class="fas fa-file-pdf mr-2"></i>Generar PDF
                                        </button>
                                        <button id="btnAnularCosto" type="button" class="btn btn-danger" data-iddespacho="">
                                            <i class="fas fa-undo-alt mr-2"></i>Anular Costeo
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /box end -->
                    </div>
                </div>
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

<?= footer($data)?>
<?= footer($data)?>