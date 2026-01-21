<?= head($data)?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-oil-can"></i> Monitoreo de Cambio de Aceite</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url() ?>dashboard">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url() ?>flota">Flota</a></li>
                        <li class="breadcrumb-item active">Cambio de Aceite</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Fila de Resumen -->
            <div class="row">
                <div id="card-requerido" data-status="Requerido" class="col-lg-4 col-md-6 col-sm-12 status-card">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3 id="card-requeridas">0</h3>
                            <p>Unidades con Cambio Requerido</p>
                        </div>
                        <div class="icon"><i class="fas fa-oil-can"></i></div>
                    </div>
                </div>
                <div id="card-proximo" data-status="Próximo" class="col-lg-4 col-md-6 col-sm-12 status-card">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3 id="card-proximas">0</h3>
                            <p>Unidades con Cambio Próximo</p>
                        </div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
                <div id="card-ok" data-status="Bien" class="col-lg-4 col-md-6 col-sm-12 status-card">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3 id="card-optimas">0</h3>
                            <p>Unidades en Estado Óptimo</p>
                        </div>
                        <div class="icon"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
            </div>

            <!-- Panel de Reportes -->
            <div class="card mb-3">
                <div class="card-header bg-navy">
                    <h3 class="card-title"><i class="fas fa-file-pdf"></i> Reportes de Estado</h3>
                </div>
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-8">
                            <label>Incluir en Reporte:</label>
                            <div class="form-group d-flex flex-wrap">
                                <div class="custom-control custom-checkbox mr-4">
                                    <input class="custom-control-input" type="checkbox" id="checkRequerido" value="Requerido" checked>
                                    <label for="checkRequerido" class="custom-control-label text-danger font-weight-bold">Requerido</label>
                                </div>
                                <div class="custom-control custom-checkbox mr-4">
                                    <input class="custom-control-input" type="checkbox" id="checkProximo" value="Próximo" checked>
                                    <label for="checkProximo" class="custom-control-label text-warning font-weight-bold">Próximo</label>
                                </div>
                                <div class="custom-control custom-checkbox mr-4">
                                    <input class="custom-control-input" type="checkbox" id="checkBien" value="Bien" checked>
                                    <label for="checkBien" class="custom-control-label text-success font-weight-bold">Bien</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox" id="checkSinRegistro" value="Sin Registro" checked>
                                    <label for="checkSinRegistro" class="custom-control-label text-secondary font-weight-bold">Sin Registro</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-danger btn-block" onclick="fntGenerarReporteAceite()"><i class="fas fa-print"></i> Generar Reporte PDF</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Unidades -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Listado de Unidades</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableAceite" class="table table-bordered table-striped" style="width:100%">
                            <thead class="thead-light">
                                <tr>
                                    <th>Unidad</th>
                                    <th>KM Actual</th>
                                    <th>Último Cambio (KM)</th>
                                    <th>Fecha Últ. Cambio</th>
                                    <th>Próximo Cambio (KM)</th>
                                    <th>KM Restantes</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyAceite">
                                <!-- El contenido se generará con JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal para Actualizar Kilometraje -->
<div class="modal fade" id="modalKilometraje" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Actualizar Kilometraje - Unidad <span id="unidad_km_label"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formKilometraje">
                    <input type="hidden" id="id_flota_km" name="id_flota_km">
                    <div class="form-group">
                        <label for="kilometraje_actual">Kilometraje Actual</label>
                        <input type="number" id="kilometraje_actual" name="kilometraje_actual" class="form-control" placeholder="Ej: 150000" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" form="formKilometraje" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Registrar Cambio de Aceite -->
<div class="modal fade" id="modalAceite" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Cambio de Aceite - Unidad <span id="unidad_aceite_label"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formAceite">
                    <input type="hidden" id="id_flota_aceite" name="id_flota_aceite">
                    <input type="hidden" id="kilometraje_anterior_aceite" name="kilometraje_anterior_aceite">
                    <div class="form-group">
                        <label for="fecha_cambio_aceite">Fecha del Cambio</label>
                        <input type="date" id="fecha_cambio_aceite" name="fecha_cambio_aceite" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="kilometraje_cambio">Kilometraje del Cambio</label>
                        <input type="number" id="kilometraje_cambio" name="kilometraje_cambio" class="form-control" placeholder="Ej: 145000" required>
                    </div>
                    <p class="text-muted text-sm">Al registrar, el próximo cambio se calculará a 5,000 KM.</p>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" form="formAceite" class="btn btn-success">Registrar Cambio</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Eliminar Registros -->
<div class="modal fade" id="modalDeleteRecord" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Eliminar Registro - Unidad <span id="unidad_delete_label"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Qué tipo de registro desea eliminar para la unidad <strong id="unidad_delete_label_confirm"></strong>?</p>
                <p class="text-sm text-danger">Esta acción eliminará el registro más reciente y no se puede deshacer.</p>
                <div class="d-flex flex-column mt-3">
                    <button type="button" id="btnDeleteKilometraje" class="btn btn-danger mb-2">
                        <i class="fas fa-tachometer-alt mr-2"></i> Eliminar Kilometraje Actual
                    </button>
                    <button type="button" id="btnDeleteAceite" class="btn btn-danger">
                        <i class="fas fa-oil-can mr-2"></i> Eliminar Último Cambio de Aceite
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<?= footer($data)?>
