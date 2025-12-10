<?= head($data)?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <!-- Columna Departamentos -->
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-building"></i> Gestión de Departamentos</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openModal();">
                                        <i class="fas fa-plus-circle mr-2"></i> Nuevo
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="tableDepartamentos" class="table table-bordered table-striped table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Columna Roles -->
                        <div class="card card-primary card-outline">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-user-tag"></i> Gestión de Roles</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openRolModal();">
                                        <i class="fas fa-plus-circle mr-2"></i> Nuevo
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="tableRoles" class="table table-bordered table-striped table-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Departamentos -->
            <div class="modal fade" id="modalFormDepto" tabindex="-1" role="dialog" aria-labelledby="modalFormDeptoLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header card-header">
                            <h5 class="modal-title" id="titleModal">Nuevo Departamento</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="formDepto" name="formDepto">
                            <div class="modal-body">
                                <input type="hidden" id="idDepartamento" name="idDepartamento" value="">
                                <div class="form-group">
                                    <label for="txtNombreDepto" class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" id="txtNombreDepto" name="txtNombre" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="txtDescripcionDepto" class="form-label">Descripción</label>
                                    <textarea id="txtDescripcionDepto" name="txtDescripcion" rows="3" class="form-control"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="listStatusDepto" class="form-label">Estado <span class="text-danger">*</span></label>
                                    <select id="listStatusDepto" name="listStatus" class="form-control" required>
                                        <option value="1">Activo</option>
                                        <option value="0">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                <button type="submit" class="btn btn-primary">
                                    <span id="btnActionTextDepto">Guardar</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Roles -->
            <div class="modal fade" id="modalFormRol" tabindex="-1" role="dialog" aria-labelledby="modalFormRolLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header card-header">
                            <h5 class="modal-title" id="titleModalRol">Nuevo Rol</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="formRol" name="formRol">
                            <div class="modal-body">
                                <input type="hidden" id="idRol" name="idRol" value="">
                                <div class="form-group">
                                    <label for="txtNombreRol" class="form-label">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" id="txtNombreRol" name="txtNombre" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="txtDescripcionRol" class="form-label">Descripción</label>
                                    <textarea id="txtDescripcionRol" name="txtDescripcion" rows="3" class="form-control"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="listStatusRol" class="form-label">Estado <span class="text-danger">*</span></label>
                                    <select id="listStatusRol" name="listStatus" class="form-control" required>
                                        <option value="1">Activo</option>
                                        <option value="0">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                <button type="submit" class="btn btn-primary">
                                    <span id="btnActionTextRol">Guardar</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <!-- /.content -->
    </div>


        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

<?= footer($data)?>