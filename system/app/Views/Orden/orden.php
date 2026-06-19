<?= head($data)?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-dolly-flatbed"></i> <?= $data['page_title'] ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url() ?>dashboard">Inicio</a></li>
                        <li class="breadcrumb-item active"><?= $data['page_name'] ?></li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Progress bar -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="font-weight-bold">Progreso de órdenes este mes</span>
                        <span id="progressPercentage" class="font-weight-bold">0%</span>
                    </div>
                    <div class="progress">
                        <div id="progressBarFill" class="progress-bar bg-primary" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="text-center mt-1">
                        <span id="progressCurrent" class="text-muted text-sm">0 órdenes</span>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Main Form Section -->
                <div class="col-lg-8">
                    

                    <!-- Orders List Section -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-list-alt"></i> Órdenes Registradas</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tblOrdenes" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Fecha</th>
                                            <th>Unidad</th>
                                            <th class="text-center">Estado</th>
                                            <th>Creador</th>
                                            <th class="text-center">Artículos</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="boxInvoce">
                                        <!-- DataTables will populate this -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Section -->
                <div class="col-lg-4">

                    <!-- Search Card -->
                    <div class="card card-indigo">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-search"></i> Buscar Órdenes</h3>
                        </div>
                        <div class="card-body">
                            <form id="formBuscarDesp">
                                <div class="form-group">
                                    <label for="txtCod">Número de Orden</label>
                                    <input type="text" id="txtCod" name="txtCod" class="form-control" placeholder="Ej: 1001">
                                </div>
                                <div class="form-group">
                                    <label for="txtFecha">Fecha</label>
                                    <input type="date" id="txtFecha" name="txtFecha" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="txtUnidad">Unidad</label>
                                    <input type="text" id="txtUnidad" name="txtUnidad" class="form-control" placeholder="ID de unidad">
                                </div>
                                <div class="form-group">
                                    <label for="txtArt">Artículo</label>
                                    <input type="text" id="txtArt" name="txtArt" class="form-control" placeholder="Nombre del artículo">
                                </div>
                                <button type="submit" class="btn btn-indigo btn-block"><i class="fas fa-search"></i> Buscar</button>
                            </form>
                            <!-- Search Results Container -->
                            <div id="searchResultsContainer" class="mt-3">
                                <!-- Search results will be inserted here -->
                            </div>
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