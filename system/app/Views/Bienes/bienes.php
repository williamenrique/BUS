<?= head($data)?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <!-- Tarjeta de Reportes -->
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-file-pdf"></i> Generación de Reportes</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-2 mb-md-0">
                                        <button id="btnPdfGeneral" class="btn btn-danger btn-block"><i class="fas fa-globe mr-2"></i>PDF General</button>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="input-group">
                                            <select id="listDeptoPDF" class="form-control">
                                                <option value="">Seleccionar departamento para reporte...</option>
                                            </select>
                                            <div class="input-group-append">
                                                <button id="btnPdfDepto" class="btn btn-info"><i class="fas fa-file-invoice mr-2"></i>PDF por Depto.</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- INICIO: Fila para búsqueda y exportación -->
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="input-group">
                                            <input type="text" id="txtBusquedaBien" class="form-control" placeholder="Buscar bien por descripción, grupo, etc...">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="button" id="btnExportarBusqueda">
                                                    <i class="fas fa-file-pdf"></i> Exportar Búsqueda
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Gestión de Inventario de Bienes</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-success" onclick="openModalQR();">
                                        <i class="fas fa-qrcode mr-2"></i> Generar QR
                                    </button>
                                    <button type="button" class="btn btn-primary" onclick="openModal();">
                                        <i class="fas fa-plus-circle mr-2"></i> Nuevo Bien
                                    </button>
                                </div>
                            </div>
                            <!-- /.card-header -->
                            <div class="card-body">
                                <table id="tableBienes" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Descripción</th>
                                            <th>Departamento</th>
                                            <th>Grupo</th>
                                            <th>Subgrupo</th>
                                            <th>Sección</th>
                                            <th>Estado del Bien</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                    </div>
                </div>
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

<!-- Modal para agregar/editar bien -->
<div class="modal fade" id="modalFormBien" tabindex="-1" role="dialog" aria-labelledby="titleModal" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titleModal">Nuevo Bien</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formBien" name="formBien">
                    <input type="hidden" id="id_bien" name="id_bien" value="">
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="departamento">Departamento <span class="text-danger">*</span></label>
                                <select id="departamento" name="departamento" class="form-control" required></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="grupo">Grupo <span class="text-danger">*</span></label>
                                <select id="grupo" name="grupo" class="form-control" required></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="subgrupo">Subgrupo <span class="text-danger">*</span></label>
                                <select id="subgrupo" name="subgrupo" class="form-control" required></select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="seccion">Sección <span class="text-danger">*</span></label>
                                <select id="seccion" name="seccion" class="form-control" required></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status_bien">Estado del Bien <span class="text-danger">*</span></label>
                                <select id="status_bien" name="status_bien" class="form-control" required>
                                    <option value="">Seleccionar Estado</option>
                                    <option value="EN USO">EN USO</option>
                                    <option value="EXTRAVIADO">EXTRAVIADO</option>
                                    <option value="EN REPARACION">EN REPARACION</option>
                                    <option value="DAÑADO">DAÑADO</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="fecha_adquisicion">Fecha de Adquisición</label>
                                <input type="date" id="fecha_adquisicion" name="fecha_adquisicion" class="form-control">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="submit" form="formBien" class="btn btn-primary"><i class="fas fa-save mr-2"></i><span id="btnActionText">Guardar</span></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para generar QR -->
<div class="modal fade" id="modalQR" tabindex="-1" role="dialog" aria-labelledby="modalQRLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalQRLabel">Generar Código QR por Departamento</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="listDeptoQR">Seleccionar Departamento</label>
                    <select id="listDeptoQR" class="form-control">
                        <option value="">Seleccionar Departamento</option>
                        <?php if(isset($data['departamentos']) && is_array($data['departamentos'])) {
                            foreach($data['departamentos'] as $departamento) { ?>
                                <option value="<?= $departamento['depatamento_bien_id'] ?>"><?= $departamento['departamento_bien'] ?></option>
                        <?php } } ?>
                    </select>
                </div>
                <div id="qrResult" class="text-center mt-4 d-none">
                    <div id="qrcode" class="d-inline-block p-2 bg-white rounded"></div>
                    <p class="mt-2 text-muted">Escanea este código para ver los bienes del departamento.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" onclick="generarQR()">
                    <i class="fas fa-qrcode mr-2"></i>Generar QR
                </button>
            </div>
        </div>
    </div>
</div>

<?= footer($data)?>