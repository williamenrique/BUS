<div id="dashboard-almacen">

    <!-- ============================================================ -->
    <!-- SECCIÓN 1: CARDS DE ALERTA                                    -->
    <!-- ============================================================ -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3 id="almacen-productos-sin-stock">0</h3>
                    <p>Productos Sin Stock</p>
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
                    <h3 id="almacen-productos-stock-bajo">0</h3>
                    <p>Productos Stock Bajo</p>
                </div>
                <div class="icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <a href="<?= base_url() ?>producto/inventario" class="small-box-footer">
                    Ver inventario <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 id="almacen-ordenes-pendientes">0</h3>
                    <p>Órdenes Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <a href="<?= base_url() ?>orden/orden" class="small-box-footer">
                    Ver órdenes <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3 id="almacen-total-productos">0</h3>
                    <p>Total Productos Activos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <a href="<?= base_url() ?>producto/producto" class="small-box-footer">
                    Ver productos <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SECCIÓN 2: MOVIMIENTOS DEL MES                                -->
    <!-- ============================================================ -->
    <div class="row">
        <div class="col-lg-4 col-md-6 col-12">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3 id="almacen-lubricantes-actual">0 Lts</h3>
                    <p>Lubricante Despachado (Mes Actual)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-oil-can"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 col-12">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3 id="almacen-lubricantes-anterior">0 Lts</h3>
                    <p>Lubricante Despachado (Mes Anterior)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-history"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 col-12">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3 id="almacen-ordenes-despacho">0</h3>
                    <p>Órdenes Despachadas (Este Mes)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-dolly-flatbed"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- SECCIÓN 3: TOP PRODUCTOS DESPACHADOS                          -->
    <!-- ============================================================ -->
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-trophy mr-1"></i>
                        Top 10 Productos Despachados (Mes Actual)
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
                                    <th>Tipo</th>
                                    <th>Ubicación</th>
                                    <th class="text-center">Despachado</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-top-productos">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
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
    <!-- SECCIÓN 4: ÚLTIMAS ÓRDENES                                    -->
    <!-- ============================================================ -->
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list-alt mr-1"></i>
                        Últimas 10 Órdenes Registradas
                    </h3>
                    <div class="card-tools">
                        <a href="<?= base_url() ?>orden/orden" class="btn btn-sm btn-primary">
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
                                    <th>Operador</th>
                                    <th class="text-center">Artículos</th>
                                    <th class="text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-ultimas-ordenes">
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