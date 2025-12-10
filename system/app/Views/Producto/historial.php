<?= head($data)?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-history"></i> <?= $data['page_title'] ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Home</a></li>
                        <li class="breadcrumb-item active">Historial</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Vista de la Tabla Resumen (Visible por defecto) -->
            <div id="summaryView">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Resumen de Movimientos</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tableHistory" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Artículo</th>
                                        <th>Stock Actual</th>
                                        <th>Total Despachado</th>
                                        <th>Primer Despacho</th>
                                        <th>Último Despacho</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded by DataTable -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vista de Detalle (Oculta por defecto) -->
            <div id="detailView" class="d-none">
                <div class="card">
                    <div class="card-header">
                        <h3 id="detailProductName" class="card-title">Historial de: </h3>
                        <div class="card-tools">
                            <button onclick="showSummaryView()" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver al Resumen
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Timeline -->
                        <div class="timeline" id="timelineContainer">
                            <!-- Los eventos del timeline se insertarán aquí -->
                            <div id="timelineEmptyState" class="d-none text-center py-5">
                                <i class="fas fa-box-open fa-3x text-muted"></i>
                                <p class="mt-3 text-muted">Este producto aún no ha sido despachado.</p>
                            </div>
                        </div>
                        <!-- Contenedor para la Paginación -->
                        <div class="card-footer clearfix">
                            <div id="pagination-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?= footer($data)?>