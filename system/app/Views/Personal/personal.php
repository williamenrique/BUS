<?= head($data)?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Formulario de Registro -->
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title" id="formTitle"><i class="fas fa-user-plus"></i> Registrar Nuevo Personal</h3>
                        </div>
                        <div class="card-body">
                            <form id="formPersonal">
                                <input type="hidden" id="idPersonal" name="idPersonal" value="">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="txtIdentificacion">Cédula</label>
                                            <input type="text" id="txtIdentificacion" name="txtIdentificacion" class="form-control" placeholder="V-12345678" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="txtNombre">Nombres</label>
                                            <input type="text" id="txtNombre" name="txtNombre" class="form-control" placeholder="Ej: Juan José" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="txtApellido">Apellidos</label>
                                            <input type="text" id="txtApellido" name="txtApellido" class="form-control" placeholder="Ej: Pérez Gómez" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="listCargo">Cargo</label>
                                            <select id="listCargo" name="listCargo" class="form-control" required></select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="txtTelefono">Teléfono</label>
                                            <input type="tel" id="txtTelefono" name="txtTelefono" class="form-control" placeholder="0412-1234567">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="txtEmail">Correo Electrónico</label>
                                            <input type="email" id="txtEmail" name="txtEmail" class="form-control" placeholder="correo@ejemplo.com">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="listTagPersonal">Enlace</label>
                                            <select id="listTagPersonal" name="listTagPersonal" class="form-control">
                                                <option value="0">SELECCIONE ENLACE</option>
                                                <option value="1">INFORMATICA</option>
                                                <option value="2">ALMACEN</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="listStatus">Estado</label>
                                            <select id="listStatus" name="listStatus" class="form-control">
                                                <option value="1">Activo</option>
                                                <option value="0">Inactivo</option>
                                                <option value="2">Vacaciones</option>
                                                <option value="3">Reposo</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="txtDireccion">Dirección</label>
                                            <textarea id="txtDireccion" name="txtDireccion" class="form-control" rows="2" placeholder="Dirección de habitación"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" id="btnActionForm" class="btn btn-primary">
                                        <i class="fas fa-check-circle"></i> <span id="btnText">Guardar</span>
                                    </button>
                                    <button type="button" id="btnCancel" class="btn btn-secondary" style="display: none;">
                                        <i class="fas fa-times-circle"></i> Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tabla de Personal -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-list"></i> Listado de Personal</h3>
                        </div>
                        <div class="card-body">
                            <!-- Contenedor para la tabla dinámica -->
                            <div id="tablePersonal_wrapper"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Modal para Ver Personal -->
<div class="modal fade" id="modalViewPersonal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h4 class="modal-title" id="modalTitle">Detalles del Personal</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tbody>
                        <tr><td><strong>Cédula:</strong></td><td id="viewCedula"></td></tr>
                        <tr><td><strong>Nombre:</strong></td><td id="viewNombre"></td></tr>
                        <tr><td><strong>Cargo:</strong></td><td id="viewCargo"></td></tr>
                        <tr><td><strong>Dirección:</strong></td><td id="viewDireccion"></td></tr>
                        <tr><td><strong>Email:</strong></td><td id="viewEmail"></td></tr>
                        <tr><td><strong>Teléfono:</strong></td><td id="viewTelefono"></td></tr>
                        <tr><td><strong>Enlace:</strong></td><td id="viewTag"></td></tr>
                        <tr><td><strong>Estado:</strong></td><td id="viewStatus"></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?= footer($data)?>