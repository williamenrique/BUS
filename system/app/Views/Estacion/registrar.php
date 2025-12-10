<?= head($data)?>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <!-- Columna Izquierda -->
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-body">
                                <div class="row align-items-start mb-4">
                                    <div class="col-md-6">
                                        <h2 class="font-weight-bold mb-0">Estación de Servicio</h2>
                                        <?php if ($_SESSION['userData']['departamento_nombre'] == 'SISTEMAS'): ?>
                                            <p class="text-muted">Vista de Administrador</p>
                                        <?php elseif (isset($_SESSION['userData']['estacion']) && !empty($_SESSION['userData']['estacion'])): ?>
                                            <p class="text-muted"><?= $_SESSION['userData']['estacion']; ?></p>
                                        <?php else: ?>
                                            <p class="text-danger">No posee estación asignada</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6 text-center">
                                        <label class="font-weight-semibold mb-1">Tasa del Día:</label>
                                        <div class="input-group">
                                            <span id="tasaDisplay" class="form-control font-weight-bold bg-light" style="font-size: 1.2rem;">0.00</span>
                                            <input type="number" name="txtTasa" id="txtTasa" step="0.01" class="form-control" placeholder="0.00" style="display: none;">
                                            <div class="input-group-append">
                                                <button type="button" id="btnUpdateTasa" class="btn btn-outline-secondary"><i class="fas fa-sync"></i></button>
                                            </div>
                                        </div>
                                        <div id="tasaLastUpdateContainer" class="mt-2">
                                            <span id="tasaLastUpdate" class="text-muted small"></span>
                                        </div>
                                    </div>
                                </div>

                                <form id="ventaForm">
                                    <div class="row">
                                        <div class="col-sm-6 form-group">
                                            <label for="txtListTipoVehiculo">Tipo de Vehículo</label>
                                            <select id="txtListTipoVehiculo" class="form-control"></select>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <label for="txtLTS">Litros</label>
                                            <input type="number" id="txtLTS" placeholder="0.00" step="0.01" class="form-control">
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <label for="txtListTipoPago">Tipo de Pago</label>
                                            <select id="txtListTipoPago" class="form-control"></select>
                                        </div>
                                        <div class="col-sm-6 form-group">
                                            <label for="txtMonto">Monto a Pagar</label>
                                            <input type="text" id="txtMonto" placeholder="0.00" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="text-right mt-3">
                                        <button type="submit" id="btnRegisterSale" class="btn btn-success">
                                            <i class="fas fa-save mr-2"></i> Registrar Venta
                                        </button>
                                    </div>
                                </form>

                                <hr class="my-4">

                                <div id="resumenVentas">
                                    <h3 class="font-weight-bold mb-3">Resumen de Ventas del Día</h3>
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="small-box bg-info">
                                                <div class="inner">
                                                    <h3><span id="totalLitros">0.00</span><sup style="font-size: 20px"> Lts</sup></h3>
                                                    <p>Total Litros</p>
                                                </div>
                                                <div class="icon"><i class="fas fa-gas-pump"></i></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <div class="small-box bg-success">
                                                <div class="inner">
                                                    <h3><span id="totalVehiculos">0</span></h3>
                                                    <p>Vehículos Atendidos</p>
                                                </div>
                                                <div class="icon"><i class="fas fa-car"></i></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-4 col-md-12 mb-3">
                                            <div class="small-box bg-warning">
                                                <div class="inner">
                                                    <h3><span id="totalBolivares">0.00</span></h3>
                                                    <p>Total Ingreso (Bs)</p>
                                                </div>
                                                <div class="icon"><i class="fas fa-money-check-alt"></i></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div id="tiposPagosContainer" class="col-md-6" style="display: none;">
                                            <div class="card card-outline card-primary">
                                                <div class="card-header"><h4 class="card-title font-weight-bold">Por Tipo de Pago</h4></div>
                                                <div class="card-body"><ul id="tiposPagosList" class="list-unstyled"></ul></div>
                                            </div>
                                        </div>
                                        <div id="tiposVehiculosContainer" class="col-md-6" style="display: none;">
                                            <div class="card card-outline card-secondary">
                                                <div class="card-header"><h4 class="card-title font-weight-bold">Por Tipo de Vehículo</h4></div>
                                                <div class="card-body"><ul id="tiposVehiculosList" class="list-unstyled"></ul></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 d-flex">
                                        <button id="btnCerrarDia" class="btn btn-danger mr-2"><i class="fas fa-door-closed mr-2"></i> Cerrar Día y Reportes</button>
                                        <button id="btnGenerarPDF" class="btn btn-secondary"><i class="fas fa-file-pdf mr-2"></i> Generar PDF</button>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div id="cierrePendienteSection" class="mt-4" style="display: none;">
                                    <h3 class="font-weight-bold text-danger mb-2">Ventas Pendientes por Cerrar</h3>
                                    <div id="cierrePendienteButtons"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha -->
                    <div class="col-md-5">
                        <div class="card mb-4">
                            <div class="card-header"><h3 class="card-title font-weight-bold">Vista Previa del Ticket</h3></div>
                            <div class="card-body">
                                <pre id="ticketPreview" class="p-3 bg-light rounded" style="white-space: pre-wrap;"></pre>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header"><h3 class="card-title font-weight-bold">Tickets de Venta del Día</h3></div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="ventasTable" class="table table-bordered table-striped table-sm w-100">
                                        <thead>
                                            <tr>
                                                <th>Ticket</th>
                                                <th>Fecha</th>
                                                <th>Vehículo</th>
                                                <th>Litros</th>
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
            </div>
        </section>
        <!-- /.content -->
    </div>

<?= footer($data)?>
