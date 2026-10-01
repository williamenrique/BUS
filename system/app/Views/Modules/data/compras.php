<div id="dashboard-compras">

    <!-- ============================================================ -->
    <!-- SECCIÓN 1: CARDS DE ALERTA                                    -->
    <!-- ============================================================ -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3 id="compras-requisiciones-pendientes">0</h3>
                    <p>Requisiciones Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <a href="<?= base_url() ?>compras/costos" class="small-box-footer">
                    Ver requisiciones <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3 id="compras-ordenes-aprobadas">0</h3>
                    <p>Órdenes Aprobadas Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <a href="<?= base_url() ?>compras/costos" class="small-box-footer">
                    Ver órdenes <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3 id="compras-articulos-sin-stock">0</h3>
                    <p>Artículos Sin Stock</p>
                </div>
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <a href="<?= base_url() ?>producto/inventario" class="small-box-footer">
                    Ver inventario <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3 id="compras-articulos-stock-bajo">0</h3>
                    <p>Artículos Stock Bajo</p>
                </div>
                <div class="icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <a href="<?= base_url() ?>producto/inventario" class="small-box-footer">
                    Ver inventario <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SECCIÓN 2: MOVIMIENTOS DEL MES                                -->
    <!-- ============================================================ -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 id="compras-ordenes-despachadas-mes">0</h3>
                    <p>Órdenes Despachadas (Mes)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dolly-flatbed"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3 id="compras-ordenes-costeadas-mes">0</h3>
                    <p>Órdenes Costeadas (Mes)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3 id="compras-total-divisa-mes">$0.00</h3>
                    <p>Total Divisas (Mes)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3 id="compras-total-bs-mes">Bs. 0.00</h3>
                    <p>Total Bolívares (Mes)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SECCIÓN 3: TOP 10 PRODUCTOS COSTEADOS                         -->
    <!-- ============================================================ -->
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trophy mr-1"></i>
                        Top 10 Productos Costeados (Mes Actual)
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 350px;">
                        <table class="table table-sm table-striped table-hover mb-0">
                            <thead class="thead-dark sticky-top">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>Producto</th>
                                    <th>Proveedor</th>
                                    <th>Tipo</th>
                                    <th class="text-center">Veces</th>
                                    <th class="text-center">Total ($)</th>
                                    <th class="text-center">Total (Bs.)</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-top-productos-costeados">
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SECCIÓN 4: ÚLTIMAS REQUISICIONES                              -->
    <!-- ============================================================ -->
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list-alt mr-1"></i>
                        Últimas 10 Requisiciones / Órdenes
                    </h3>
                    <div class="card-tools">
                        <a href="<?= base_url() ?>compras/costos" class="btn btn-sm btn-warning">
                            <i class="fas fa-arrow-right"></i> Ver Todas
                        </a>
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-sm table-striped table-hover mb-0">
                            <thead class="thead-dark sticky-top">
                                <tr>
                                    <th style="width: 100px;">N° Orden</th>
                                    <th style="width: 110px;">Fecha</th>
                                    <th>Unidad</th>
                                    <th>Solicitante</th>
                                    <th class="text-center">Artículos</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-ultimas-requisiciones">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SECCIÓN 5: ÚLTIMAS COMPRAS COSTEADAS                          -->
    <!-- ============================================================ -->
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-invoice-dollar mr-1"></i>
                        Últimas 10 Compras Costeadas
                    </h3>
                    <div class="card-tools">
                        <a href="<?= base_url() ?>compras/costos" class="btn btn-sm btn-success">
                            <i class="fas fa-arrow-right"></i> Ver Todas
                        </a>
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-sm table-striped table-hover mb-0">
                            <thead class="thead-dark sticky-top">
                                <tr>
                                    <th style="width: 100px;">N° Orden</th>
                                    <th style="width: 110px;">Fecha</th>
                                    <th>Unidad</th>
                                    <th class="text-center">Artículos</th>
                                    <th class="text-center">Total ($)</th>
                                    <th class="text-center">Total (Bs.)</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-ultimas-compras-costeadas">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        <i class="fas fa-spinner fa-spin"></i> Cargando...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>