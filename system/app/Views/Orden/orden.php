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
                    <!-- Order Form Card -->
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-plus-circle"></i> Crear Nueva Orden</h3>
                        </div>
                        <div class="card-body">
                            <form id="formDespacho">
                                <input type="hidden" id="idDespacho" name="idDespacho" value="">
                                <!-- Basic Information Section -->
                                <h5><i class="fas fa-info-circle text-primary"></i> Información Básica</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="listUnidad"><i class="fas fa-bus"></i> Unidad *</label>
                                        <select id="listUnidad" name="listUnidad" class="form-control" required>
                                            <option value="0">Cargando unidades...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="txtdate"><i class="far fa-calendar-alt"></i> Fecha *</label>
                                        <input type="date" id="txtdate" name="txtdate" class="form-control" required>
                                        <input type="hidden" id="strDate" name="strDate">
                                    </div>
                                </div>
                                <div class="card bg-light">
                                    <div class="card-header border-bottom-0">Información de la Unidad</div>
                                    <div class="card-body pt-0">
                                        <div class="row">
                                            <div class="col-6"><b>ID Unidad:</b> <span id="id_unidad">-</span></div>
                                            <div class="col-6"><b>VIN:</b> <span id="vim_unidad">-</span></div>
                                            <div class="col-6"><b>Marca:</b> <span id="marca_unidad">-</span></div>
                                            <div class="col-6"><b>Modelo:</b> <span id="modelo_unidad">-</span></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Personnel Section -->
                                <h5 class="mt-4"><i class="fas fa-users text-success"></i> Personal Asignado</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <label for="listOperador"><i class="fas fa-user-tie"></i> Operador *</label>
                                        <select id="listOperador" name="listOperador" class="form-control" required>
                                            <option value="0">Cargando operadores...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label for="listMecanico"><i class="fas fa-tools"></i> Mecánico *</label>
                                        <select id="listMecanico" name="listMecanico" class="form-control" required>
                                            <option value="0">Cargando mecánicos...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 form-group">
                                        <label for="listDespachador"><i class="fas fa-clipboard-check"></i> Despachador *</label>
                                        <select id="listDespachador" name="listDespachador" class="form-control" required>
                                            <option value="0">Cargando despachadores...</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Articles Section -->
                                <h5 class="mt-4"><i class="fas fa-boxes text-purple"></i> Artículos a Despachar</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="listArticulo"><i class="fas fa-box"></i> Artículo</label>
                                        <select id="listArticulo" class="form-control">
                                            <option value="0">Cargando artículos...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="txtCant"><i class="fas fa-hashtag"></i> Cantidad</label>
                                        <div class="input-group">
                                            <input type="number" id="txtCant" min="1" step="1" class="form-control">
                                            <div class="input-group-append">
                                                <button type="button" id="btnAgrega" class="btn btn-primary"><i class="fas fa-plus"></i></button>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <small class="text-muted">Disponible: <span id="stockDisponible">0</span></small>
                                            <input type="hidden" id="cantDispo">
                                        </div>
                                        <div id="stockValidation" class="text-danger text-sm mt-1" style="display: none;"></div>
                                    </div>
                                </div>
                                <table class="table table-bordered table-striped table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Artículo</th>
                                            <th>Cantidad</th>
                                            <th style="width: 50px;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="lista">
                                        <tr>
                                            <td colspan="4" class="text-center">No hay artículos agregados</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <!-- Observations Section -->
                                <div class="form-group mt-4">
                                    <label for="txtObs"><i class="fas fa-sticky-note"></i> Observaciones</label>
                                    <textarea id="txtObs" name="txtObs" rows="3" class="form-control" placeholder="Escribe aquí cualquier observación adicional..."></textarea>
                                </div>

                                <div class="text-right">
                                    <button type="button" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</button>
                                    <button type="submit" id="btnGenerar" class="btn btn-success"><i class="fas fa-paper-plane"></i> Generar Orden</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Orders List Section -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-list-alt"></i> Órdenes Registradas</h3>
                            <button id="btnImprimirLote" class="btn btn-warning btn-sm float-right" disabled><i class="fas fa-print"></i> Imprimir Seleccionados (0/2)</button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tblOrdenes" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 20px;">#</th>
                                            <th>ID</th>
                                            <th>Fecha</th>
                                            <th>Unidad</th>
                                            <th>Operador</th>
                                            <th>Artículos</th>
                                            <th>Acciones</th>
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
                    <!-- Order Summary Card -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-clipboard-list"></i> Resumen de la Orden</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-unbordered mb-3">
                                <li class="list-group-item">
                                    <b>Fecha</b> <a class="float-right" id="fechaDespacho">No seleccionada</a>
                                </li>
                                <li class="list-group-item">
                                    <b>Operador</b> <a class="float-right" id="operador">No seleccionado</a>
                                </li>
                                <li class="list-group-item">
                                    <b>Mecánico</b> <a class="float-right" id="mecanico">No seleccionado</a>
                                </li>
                                <li class="list-group-item">
                                    <b>Despachador</b> <a class="float-right" id="despachador">No seleccionado</a>
                                </li>
                            </ul>
                            <hr>
                            <strong><i class="fas fa-boxes"></i> Totales</strong>
                            <p class="text-muted">
                                Artículos: <span id="totalArticulos" class="float-right">0</span><br>
                                Total Unidades: <span id="totalUnidades" class="float-right">0</span>
                            </p>
                        </div>
                    </div>

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