<?= head($data)?>
    <input type="hidden" id="id_requisicion_url" value="<?= $data['id_requisicion_url'] ?? 0 ?>">
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6 d-flex align-items-center">
                        <h1><i class="fas fa-clipboard-list"></i> <?= $data['page_title'] ?></h1>
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

            <?php 
            $userDepartment = $_SESSION['userData']['departamento_nombre'] ?? '';
            ?>
            <div class="row">                
                <!-- Columna principal para el formulario -->
                <?php
                    // Determinar si el usuario es un creador de requisiciones
                    $isCreator = ($userDepartment === 'Operaciones' || $userDepartment === 'Sistemas');
                    // Ajustar el ancho de la columna principal
                    $mainColClass = $isCreator ? 'col-lg-8' : 'col-lg-12';
                ?>
                <div class="<?= $mainColClass ?>">
                    <!-- =========== SECCIÓN PARA CREAR NUEVA REQUISICIÓN =========== -->
                    <?php if ($userDepartment === 'Operaciones' || $userDepartment === 'Sistemas'): ?>
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-edit"></i>
                                <strong>Crear Nueva Requisición</strong>
                            </h3>
                        </div>
                        <div class="card-body">
                            <form id="formRequisicion" name="formRequisicion">
                                <input type="hidden" id="idRequisicion" name="idRequisicion" value="">
                                
                                <h5><i class="fas fa-info-circle text-primary"></i> Información Básica</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="listUnidad"><span class="text-danger">*</span> Unidad (Flota)</label>
                                        <select id="listUnidad" name="listUnidad" class="form-control" required></select>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="listMecanico"><span class="text-danger">*</span> Mecánico Asignado</label>
                                        <select id="listMecanico" name="listMecanico" class="form-control" required></select>
                                    </div>
                                </div>

                                <h5 class="mt-4"><i class="fas fa-clipboard-check text-success"></i> Detalles de la Solicitud</h5>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label><span class="text-danger">*</span> Tipo de Orden</label>
                                        <div class="d-flex flex-wrap">
                                            <div class="custom-control custom-radio mr-3">
                                                <input class="custom-control-input" type="radio" id="tipoServicio" name="tipoOrden" value="Servicio" checked>
                                                <label for="tipoServicio" class="custom-control-label font-weight-normal">Servicio</label>
                                            </div>
                                            <div class="custom-control custom-radio mr-3">
                                                <input class="custom-control-input" type="radio" id="tipoCompra" name="tipoOrden" value="Compra">
                                                <label for="tipoCompra" class="custom-control-label font-weight-normal">Compra</label>
                                            </div>
                                            <div class="custom-control custom-radio">
                                                <input class="custom-control-input" type="radio" id="tipoReparacion" name="tipoOrden" value="Reparacion">
                                                <label for="tipoReparacion" class="custom-control-label font-weight-normal">Reparación</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="txtObservacion">Observación / Diagnóstico</label>
                                        <textarea id="txtObservacion" name="txtObservacion" class="form-control" rows="2" placeholder="Diagnóstico del mecánico o detalles..."></textarea>
                                    </div>
                                </div>

                                <h5 class="mt-4"><i class="fas fa-boxes text-purple"></i> Artículos Solicitados</h5>
                                <hr>
                                <div class="row align-items-end">
                                    <div class="col-md-6 form-group">
                                        <label for="listArticulo">Artículo</label>
                                        <select id="listArticulo" name="listArticulo" class="form-control"></select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label for="txtCant">Cantidad</label>
                                        <input type="number" id="txtCant" name="txtCant" class="form-control" min="1" placeholder="0" disabled>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <button type="button" id="btnAgregaArticulo" class="btn btn-primary btn-block"><i class="fas fa-plus"></i> Agregar</button>
                                    </div>
                                    <div class="col-12">
                                        <small id="stockValidationRequisicion" class="form-text text-muted" style="height: 20px; display: block;"></small>
                                    </div>
                                </div>

                                <div class="table-responsive mt-3">
                                    <table id="tblArticulosAgregados" class="table table-sm table-striped table-bordered">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Artículo</th>
                                                <th class="text-center" style="width: 100px;">Cantidad</th>
                                                <th class="text-center" style="width: 80px;">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Los artículos se agregarán aquí dinámicamente -->
                                        </tbody>
                                    </table>
                                </div>

                                <div class="text-right mt-4">
                                    <button type="button" id="btnCancel" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</button>
                                    <button type="submit" id="btnActionForm" class="btn btn-success"><i class="fas fa-paper-plane"></i> Generar Requisición</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- =========== SECCIÓN PARA APROBAR REQUISICIÓN (MOVIDA AQUÍ) =========== -->
                    <?php if ($userDepartment === 'Compras' || $userDepartment === 'Sistemas'): ?>
                    <!-- La sección de aprobación solo es visible para Compras y Sistemas -->
                    <div id="viewRequisicionUrl" class="card card-info card-outline" style="display: none;">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-eye"></i> Detalle de Requisición #<span id="viewIdRequisicionUrl"></span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Información General</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Fecha:</strong> <span id="viewFechaRequisicion"></span></li>
                                        <li><strong>Unidad:</strong> <span id="viewUnidad"></span></li>
                                        <li><strong>Tipo de Orden:</strong> <span id="viewTipoOrden"></span></li>
                                        <li><strong>Estado:</strong> <span id="viewStatusRequisicion"></span></li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Personal Involucrado</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Jefe de Patio/Taller:</strong> <span id="viewJefePatio"></span></li>
                                        <li><strong>Mecánico:</strong> <span id="viewMecanico"></span></li>
                                    </ul>
                                </div>
                            </div>
                            <hr>
                            <h6>Diagnóstico / Observaciones</h6>
                            <p id="viewDiagnostico"></p>
                            <hr>
                            <h6>Artículos Solicitados</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light"><tr><th>Artículo</th><th>Cantidad</th></tr></thead>
                                    <tbody id="viewTablaArticulosReq"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <?php if ($userDepartment === 'Compras' || $userDepartment === 'Sistemas'): ?>
                            <button id="btnEnProcesoUrl" class="btn btn-warning"><i class="fas fa-bell"></i> Notificar En Proceso</button>
                            <?php endif; ?>
                            <button id="btnAprobarUrl" class="btn btn-success"><i class="fas fa-check"></i> Aprobar Requisición</button>
                            <button id="btnOcultarUrl" class="btn btn-secondary">Ocultar Detalle</button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Listado de Requisiciones -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-list-alt"></i> Listado de Requisiciones</h3>
                        </div>
                        <div class="card-body">
                            <table id="tableRequisicion" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Unidad</th>
                                        <th>Tipo</th>
                                        <th>Creador</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Contenido se cargará por Ajax -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Columna lateral para resumen y búsqueda -->
                <?php if ($isCreator): ?>
                    <div class="col-lg-4">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-clipboard-list"></i> Resumen de la Requisición</h3>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-unbordered mb-3">
                                    <li class="list-group-item">
                                        <b>Unidad</b> <a class="float-right" id="resumenUnidad">No seleccionada</a>
                                    </li>
                                    <li class="list-group-item">
                                        <b>Mecánico</b> <a class="float-right" id="resumenMecanico">No seleccionado</a>
                                    </li>
                                </ul>
                                <hr>
                                <strong><i class="fas fa-boxes"></i> Totales</strong>
                                <p class="text-muted">
                                    Artículos distintos: <span id="resumenTotalArticulos" class="float-right">0</span><br>
                                    Total Unidades: <span id="resumenTotalUnidades" class="float-right">0</span>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

<!-- Modal para ver detalles de requisición -->
<div class="modal fade" id="modalViewRequisicion" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titleModalView">Detalles de Requisición #<span id="modalViewIdRequisicion"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Información General</h6>
                        <ul class="list-unstyled">
                            <li><strong>Fecha:</strong> <span id="modalViewFechaRequisicion"></span></li>
                            <li><strong>Unidad:</strong> <span id="modalViewUnidad"></span></li>
                            <li><strong>Tipo de Orden:</strong> <span id="modalViewTipoOrden"></span></li>
                            <li><strong>Estado:</strong> <span id="modalViewStatusRequisicion"></span></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Personal Involucrado</h6>
                        <ul class="list-unstyled">
                            <li><strong>Creador (Jefe de Patio/Taller):</strong> <span id="modalViewJefePatio"></span></li>
                            <li><strong>Mecánico:</strong> <span id="modalViewMecanico"></span></li>
                            <li id="modalOperadorFinalContainer" style="display: none;"><strong>Operador (Recibió):</strong> <span id="modalViewOperadorFinal"></span></li>
                            <li id="modalDespachadorFinalContainer" style="display: none;"><strong>Despachador (Almacén):</strong> <span id="modalViewDespachadorFinal"></span></li>
                        </ul>
                    </div>
                </div>
                <hr>
                <h6>Diagnóstico / Observaciones</h6>
                <p id="modalViewDiagnostico"></p>
                <hr>
                <h6>Artículos Solicitados</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Artículo</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody id="modalViewTablaArticulosReq">
                            <!-- Artículos cargados por JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?= footer($data)?>