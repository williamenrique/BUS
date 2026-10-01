<?= head($data)?>

<!-- ID de institución y nombre para JS -->
<input type="hidden" id="id_institucion" value="<?= $data['id_institucion'] ?>">
<input type="hidden" id="nombre_institucion" value="<?= $data['nombre_institucion'] ?>">

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-history"></i> <?= $data['page_title'] ?>

                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Home</a></li>
                        <li class="breadcrumb-item active">Historial </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Vista de la Tabla Resumen -->
            <div id="summaryView">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Resumen de Movimientos - <?= $data['nombre_institucion'] ?></h3>
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
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vista de Detalle -->
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
                        <div class="timeline" id="timelineContainer"></div>
                        <div class="card-footer clearfix">
                            <div id="pagination-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?= footer($data)?>