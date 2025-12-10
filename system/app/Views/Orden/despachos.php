<?= head($data)?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6 d-flex align-items-center">
                        <h1><i class="fas fa-truck-loading text-success"></i> <?= $data['page_title'] ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="<?= base_url() ?>home">Inicio</a></li>
                            <li class="breadcrumb-item active"><?= $data['page_name'] ?></li>
                        </ol>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="row">
                <div class="col-lg-8">
                    <!-- =========== SECCIÓN PARA COMPLETAR EL DESPACHO =========== -->
                    <div id="formCompletarDespacho" class="card card-success card-outline" style="display: none;">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-truck"></i>
                                <strong>Completar Despacho de Orden #<span id="despachoIdTitulo"></span></strong>
                            </h3>
                            <div class="card-tools">
                                <button type="button" id="btnOcultarFormDespacho" class="btn btn-tool">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="formDespachoFinal">
                                <input type="hidden" id="id_despacho_completar" name="id_despacho_completar" value="">
                                
                                <!-- Fila 1: Datos de la Requisición (solo lectura) -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <p class="mb-0"><strong>Unidad:</strong></p>
                                        <p class="text-muted" id="despachoUnidad"></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-0"><strong>Fecha de Solicitud:</strong></p>
                                        <p class="text-muted" id="despachoFecha"></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-0"><strong>Mecánico Asignado:</strong></p>
                                        <p class="text-muted" id="despachoMecanico"></p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <p class="mb-0"><strong>Observación / Diagnóstico:</strong></p>
                                        <p class="text-muted" id="despachoDiagnostico"></p>
                                    </div>
                                </div>
                                <hr>

                                <!-- Fila 2: Campos a completar por Almacén -->
                                <h5 class="mb-3"><i class="fas fa-user-check"></i> Personal Involucrado en el Despacho</h5>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="listOperadorDespacho">Operador que Recibe</label>
                                        <!-- Contenedor para el select (si la orden está por despachar) -->
                                        <div id="operadorSelectContainer">
                                            <select id="listOperadorDespacho" name="listOperadorDespacho" class="form-control" required></select>
                                        </div>
                                        <!-- Contenedor para el texto (si la orden ya fue despachada) -->
                                        <p id="operadorText" class="form-control-plaintext" style="display: none;"></p>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="listDespachadorAlmacen">Despachador de Almacén</label>
                                        <!-- Contenedor para el select -->
                                        <div id="despachadorSelectContainer">
                                            <select id="listDespachadorAlmacen" name="listDespachadorAlmacen" class="form-control" required></select>
                                        </div>
                                        <!-- Contenedor para el texto -->
                                        <p id="despachadorText" class="form-control-plaintext" style="display: none;"></p>
                                    </div>
                                </div>

                                <hr>

                                <!-- Fila 3: Artículos Aprobados (solo lectura) -->
                                <h5 class="mb-3"><i class="fas fa-boxes"></i> Artículos Aprobados para Despacho</h5>
                                <div class="table-responsive">
                                    <table id="tblArticulosDespacho" class="table table-sm table-striped table-bordered">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Artículo</th>
                                                <th class="text-center" style="width: 150px;">Cantidad Aprobada</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Los artículos se cargarán aquí dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>

                                <div class="card-footer text-right bg-light">
                                    <button type="submit" id="btnConfirmarDespacho" class="btn btn-success"><i class="fas fa-check-circle"></i> Confirmar y Despachar</button>
                                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('formCompletarDespacho').style.display = 'none';">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Listado de Órdenes Pendientes -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-tasks"></i> Listado de Órdenes (Aprobadas y Despachadas)</h3>
                        </div>
                        <div class="card-body">
                            <table id="tblDespachos" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID Orden</th>
                                        <th>Fecha Aprob.</th>
                                        <th>Unidad</th>
                                        <th>Creador</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center">Artículos</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
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
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Buscar</button>
                            </form>
                            <!-- Search Results Container -->
                            <div id="searchResultsContainer" class="mt-3">
                                <!-- Search results will be inserted here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

<?= footer($data)?>