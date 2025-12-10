<?= head($data)?>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Fila de Tarjetas de Estadísticas (Small Box) -->
                <div class="row">
                    <div class="col-lg-6 col-md-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3 id="totalLitrosSistema">0 L</h3>
                                <p>Litros Vendidos (Total del Sistema)</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-gas-pump"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3 id="totalLitrosFecha">0 L</h3>
                                <div class="form-group mb-0">
                                    <label for="selectFechaCierre">Litros por Fecha</label>
                                    <select id="selectFechaCierre" class="form-control form-control-sm"></select>
                                </div>
                            </div>
                            <div class="icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección de Ventas Abiertas -->
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-exclamation-circle mr-2"></i>Ventas Abiertas</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover table-sm w-100">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Fecha</th>
                                        <th>Litros Vendidos</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="openSalesTableBody">
                                    <!-- Contenido llenado por JS -->
                                </tbody>
                            </table>
                        </div>
                        <div id="noOpenSalesMessage" class="mt-3 text-center text-muted" style="display: none;">
                            No hay ventas abiertas actualmente.
                        </div>
                    </div>
                </div>

                <!-- Sección de Historial de Cierres -->
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Historial de Cierres Realizados</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="cierresTable" class="table table-bordered table-striped table-hover table-sm w-100">
                                <thead>
                                    <tr>
                                        <th># Cierre</th>
                                        <th>Empleado</th>
                                        <th>Tasa</th>
                                        <th>Fecha</th>
                                        <th>Efectivo (Bs)</th>
                                        <th>P. Venta (Bs)</th>
                                        <th>Total (Bs)</th>
                                        <th>Litros</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="cierresTableBody">
                                    <!-- Contenido llenado por DataTables -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sección de Detalle de Ventas por Cierre (oculta por defecto) -->
                <div id="ventasCierreSection" class="card card-outline card-info" style="display: none;">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list-alt mr-2"></i>Detalle del Cierre #<span id="cierreIdTitle"></span>
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-info">Fecha: <span id="fechaCierre"></span></span>
                            <span class="badge badge-secondary ml-2">Empleado: <span id="nameEmp"></span></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="ventasCierreList" class="table-responsive">
                            <!-- La tabla de ventas del cierre será inyectada aquí por JS -->
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button id="btnImprimirCierre" class="btn btn-primary btn-sm" data-fecha="" data-iduser="">
                            <i class="fas fa-print mr-1"></i>Imprimir Cierre
                        </button>
                        <button id="btnImprimirPdf" class="btn btn-danger btn-sm" data-fecha="" data-iduser="">
                            <i class="fas fa-file-pdf mr-1"></i>Generar PDF
                        </button>
                    </div>
                </div>

            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

<?= footer($data)?>