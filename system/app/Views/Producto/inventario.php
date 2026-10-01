<?= head($data)?>

<!-- ID de institución y nombre para JS -->
<input type="hidden" id="id_institucion" value="<?= $data['id_institucion'] ?>">
<input type="hidden" id="nombre_institucion" value="<?= $data['nombre_institucion'] ?>">

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-cubes"></i> <?= $data['page_title'] ?>

                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Home</a></li>
                        <li class="breadcrumb-item active">Inventario </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Inventario - <?= $data['nombre_institucion'] ?></h3>
                        <div class="card-tools">
                            <button onclick="fntReporteInventarioPDF()" class="btn btn-danger btn-sm">
                                <i class="fas fa-file-pdf"></i> Generar PDF
                            </button>
                            <button onclick="reloadInventarioTable()" class="btn btn-secondary btn-sm" title="Recargar">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableInventario" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Artículo</th>
                                    <th>Tipo</th>
                                    <th>Proveedor</th>
                                    <th>Ubicación</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?= footer($data)?>